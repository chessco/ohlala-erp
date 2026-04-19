<?php
/**
 * CFDI 4.0 FULL + QR + Validación SAT automática (WS)
 * Modo Hosting Compartido (Hostinger): extracción por REGEX (sin SimpleXML/DOM en SAT).
 *
 * Archivo sugerido: filexml2.php
 */

ini_set('display_errors', 1);            // DEBUG (quítalo cuando ya quede)
ini_set('display_startup_errors', 1);    // DEBUG (quítalo cuando ya quede)
error_reporting(E_ALL);                 // DEBUG (quítalo cuando ya quede)

header('Content-Type: text/html; charset=UTF-8');

require_once 'conexion.php';
$conn = $conexion;

if ($conn instanceof mysqli) {
    @mysqli_set_charset($conn, 'utf8mb4');
}

$datosFactura = null;
$error = null;

/**
 * ===============================
 * VALIDACIÓN SAT (WS SOAP)
 * Extracción por REGEX (robusta en Hostinger)
 * ===============================
 */
function consultarEstadoCfdiSAT(string $expresionImpresa): array
{
    $url = 'https://consultaqr.facturaelectronica.sat.gob.mx/ConsultaCFDIService.svc';

    $soap = '<?xml version="1.0" encoding="utf-8"?>'
        . '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
        . ' xmlns:xsd="http://www.w3.org/2001/XMLSchema"'
        . ' xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
        . '  <soap:Body>'
        . '    <Consulta xmlns="http://tempuri.org/">'
        . '      <expresionImpresa>' . htmlspecialchars($expresionImpresa, ENT_XML1) . '</expresionImpresa>'
        . '    </Consulta>'
        . '  </soap:Body>'
        . '</soap:Envelope>';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "http://tempuri.org/IConsultaCFDIService/Consulta"',
            'Accept: text/xml',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
        ],
        CURLOPT_POSTFIELDS => $soap,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '', // gzip/deflate auto
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) PHP-cURL CFDI SAT Consulta',
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'error' => $err ?: 'cURL error', 'http' => $http];
    }
    if ($http !== 200) {
        return ['ok' => false, 'error' => 'HTTP no OK', 'http' => $http, 'raw_preview' => mb_substr($response, 0, 900)];
    }

    // Quitar BOM si viniera
    $response = preg_replace('/^\xEF\xBB\xBF/', '', $response);

    // Helper: extrae contenido de tag, tolerante a prefijos y atributos.
    // Ejemplos:
    // <a:Estado>Vigente</a:Estado>
    // <Estado>Vigente</Estado>
    $get = function (string $tagLocalName) use ($response): string {
        // 1) Con prefijo (a:Estado, s:Estado, etc.)
        $pattern1 = '#<([a-zA-Z0-9_]+:)?' . preg_quote($tagLocalName, '#') . '\b[^>]*>(.*?)</([a-zA-Z0-9_]+:)?' . preg_quote($tagLocalName, '#') . '>#s';
        if (preg_match($pattern1, $response, $m)) {
            return trim(strip_tags($m[2]));
        }
        // 2) Autocierre <a:EstatusCancelacion/>
        $pattern2 = '#<([a-zA-Z0-9_]+:)?' . preg_quote($tagLocalName, '#') . '\b[^>]*/>#s';
        if (preg_match($pattern2, $response)) {
            return '';
        }
        return '';
    };

    $estado = $get('Estado');
    $codigo = $get('CodigoEstatus');
    $cancelable = $get('EsCancelable');
    $estatusCancel = $get('EstatusCancelacion');
    $efos = $get('ValidacionEFOS');

    return [
        'ok' => true,
        'Estado' => $estado,
        'CodigoEstatus' => $codigo,
        'EsCancelable' => $cancelable,
        'EstatusCancelacion' => $estatusCancel,
        'ValidacionEFOS' => $efos,
        // Para debug opcional:
        // 'raw_preview' => mb_substr($response, 0, 900),
    ];
}

/**
 * ===============================
 * PROCESAR CFDI 4.0 (XML)
 * ===============================
 */
function procesarCFDI(string $tmpFile): array
{
    $xml = simplexml_load_file($tmpFile);
    if (!$xml) {
        throw new Exception('No se pudo leer el XML (archivo inválido).');
    }

    $ns = $xml->getNamespaces(true);
    if (!isset($ns['cfdi'])) throw new Exception('El XML no contiene namespace CFDI.');
    if (!isset($ns['tfd'])) throw new Exception('El XML no contiene namespace TFD.');

    $xml->registerXPathNamespace('cfdi', $ns['cfdi']);
    $xml->registerXPathNamespace('tfd', $ns['tfd']);

    $tfdNodes = $xml->xpath('//tfd:TimbreFiscalDigital');
    if (!$tfdNodes || !isset($tfdNodes[0])) {
        throw new Exception('El CFDI no está timbrado (no existe TimbreFiscalDigital).');
    }
    $tfd = $tfdNodes[0];

    $emisorNodes = $xml->xpath('//cfdi:Emisor');
    $receptorNodes = $xml->xpath('//cfdi:Receptor');
    if (!$emisorNodes || !isset($emisorNodes[0])) throw new Exception('No se encontró Emisor.');
    if (!$receptorNodes || !isset($receptorNodes[0])) throw new Exception('No se encontró Receptor.');

    $emisor = $emisorNodes[0];
    $receptor = $receptorNodes[0];

    $data = [
        'folio'         => (string)$xml['Folio'],
        'fecha'         => (string)$xml['Fecha'],
        'forma_pago'    => (string)$xml['FormaPago'],
        'metodo_pago'   => (string)$xml['MetodoPago'],
        'moneda'        => (string)$xml['Moneda'],
        'tipo_comp'     => (string)$xml['TipoDeComprobante'],
        'exportacion'   => (string)$xml['Exportacion'],
        'lugar_exp'     => (string)$xml['LugarExpedicion'],
        'subtotal'      => (float)$xml['SubTotal'],
        'total'         => (float)$xml['Total'],

        'rfc_e' => (string)$emisor['Rfc'],
        'nom_e' => (string)$emisor['Nombre'],
        'regimen_e' => (string)$emisor['RegimenFiscal'],

        'rfc_r' => (string)$receptor['Rfc'],
        'nom_r' => (string)$receptor['Nombre'],
        'dom_r' => (string)$receptor['DomicilioFiscalReceptor'],
        'regimen_r' => (string)$receptor['RegimenFiscalReceptor'],
        'uso_cfdi' => (string)$receptor['UsoCFDI'],

        'uuid'          => (string)$tfd['UUID'],
        'sello_cfd'     => (string)$tfd['SelloCFD'],
        'sello_sat'     => (string)$tfd['SelloSAT'],
        'rfc_prov'      => (string)$tfd['RfcProvCertif'],
        'no_cert_sat'   => (string)$tfd['NoCertificadoSAT'],
        'fecha_timbrado'=> (string)$tfd['FechaTimbrado'],

        'conceptos'     => []
    ];

    // Conceptos + IVA
    $totalIVA = 0;
    foreach ($xml->xpath('//cfdi:Concepto') as $con) {
        $con->registerXPathNamespace('cfdi', $ns['cfdi']);

        $traslado = $con->xpath('.//cfdi:Traslado[@Impuesto="002"]');
        $ivaItem = isset($traslado[0]) ? (float)$traslado[0]['Importe'] : 0;
        $totalIVA += $ivaItem;

        $data['conceptos'][] = [
            'cant'  => (float)$con['Cantidad'],
            'desc'  => (string)$con['Descripcion'],
            'prec'  => (float)$con['ValorUnitario'],
            'imp'   => (float)$con['Importe'],
            'iva'   => $ivaItem
        ];
    }
    $data['iva_total'] = $totalIVA;

    // URL del verificador SAT (QR)
    $data['cadena_qr'] =
        "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx"
        . "?id={$data['uuid']}"
        . "&re={$data['rfc_e']}"
        . "&rr={$data['rfc_r']}"
        . "&tt=" . number_format($data['total'], 6, '.', '')
        . "&fe=" . substr($data['sello_cfd'], -8);

    // Expresión impresa para WS
    $query = parse_url($data['cadena_qr'], PHP_URL_QUERY);
    $data['expresion_impresa'] = $query ? ('?' . $query) : '';

    return $data;
}

if (isset($_POST['subir_xml'])) {
    try {
        $datosFactura = procesarCFDI($_FILES['archivo_xml']['tmp_name']);

        if (!empty($datosFactura['expresion_impresa'])) {
            $datosFactura['sat'] = consultarEstadoCfdiSAT($datosFactura['expresion_impresa']);
        } else {
            $datosFactura['sat'] = ['ok' => false, 'error' => 'No se pudo construir expresionImpresa'];
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $datosFactura = null;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>CFDI 4.0 Detallado - OhLaLa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        .invoice-card { background: white; padding: 40px; border: 1px solid #ddd; max-width: 950px; margin: 20px auto; font-size: 0.82rem; }
        .data-label { font-weight: bold; color: #555; text-transform: uppercase; font-size: 0.65rem; border-bottom: 1px solid #eee; display: block; margin-bottom: 5px; }
        .stamp-text { word-break: break-all; font-family: 'Courier New', monospace; font-size: 0.55rem; color: #555; line-height: 1.2; }
        .table-detalle th { background-color: #f8f9fa; }
        @media print { .no-print { display: none !important; } .invoice-card { border: none; margin: 0; width: 100%; } }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="no-print text-center mb-4">
        <form method="POST" enctype="multipart/form-data" class="card p-3 shadow-sm d-inline-block">
            <input type="file" name="archivo_xml" class="form-control mb-2" required>
            <button type="submit" name="subir_xml" class="btn btn-primary">Generar Factura Completa</button>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger text-center">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($datosFactura): ?>
    <div class="invoice-card shadow">
        <div class="row border-bottom pb-2 mb-3 align-items-center">
            <div class="col-7">
                <h2 class="text-primary fw-bold mb-0">PEDIDOS OHLALA</h2>
                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($datosFactura['nom_e'], ENT_QUOTES, 'UTF-8') ?></h6>
                <p class="mb-0">
                    RFC: <?= htmlspecialchars($datosFactura['rfc_e'], ENT_QUOTES, 'UTF-8') ?>
                    | Régimen: <?= htmlspecialchars($datosFactura['regimen_e'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
            <div class="col-5 text-end text-uppercase">
                <button onclick="window.print()" class="btn btn-sm btn-dark no-print mb-2">Imprimir</button>
                <h4 class="text-danger fw-bold mb-0">FOLIO: <?= htmlspecialchars($datosFactura['folio'], ENT_QUOTES, 'UTF-8') ?></h4>
                <p class="mb-0 small">
                    CP: <?= htmlspecialchars($datosFactura['lugar_exp'], ENT_QUOTES, 'UTF-8') ?>
                    | Tipo: <?= htmlspecialchars($datosFactura['tipo_comp'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-6 border-end">
                <span class="data-label">Datos del Receptor</span>
                <h6 class="mb-0 fw-bold"><?= htmlspecialchars($datosFactura['nom_r'], ENT_QUOTES, 'UTF-8') ?></h6>
                <p class="mb-0">RFC: <strong><?= htmlspecialchars($datosFactura['rfc_r'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                <p class="mb-0">
                    Dom. Fiscal: <?= htmlspecialchars($datosFactura['dom_r'], ENT_QUOTES, 'UTF-8') ?>
                    | Uso CFDI: <?= htmlspecialchars($datosFactura['uso_cfdi'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
            <div class="col-6 ps-4">
                <span class="data-label">Información de Pago</span>
                <div class="row">
                    <div class="col-6">
                        <strong>Fecha Emisión:</strong><br><?= htmlspecialchars($datosFactura['fecha'], ENT_QUOTES, 'UTF-8') ?><br>
                        <strong>Moneda:</strong> <?= htmlspecialchars($datosFactura['moneda'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="col-6">
                        <strong>Método:</strong> <?= htmlspecialchars($datosFactura['metodo_pago'], ENT_QUOTES, 'UTF-8') ?><br>
                        <strong>Forma:</strong> <?= htmlspecialchars($datosFactura['forma_pago'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </div>
        </div>

        <table class="table table-sm table-bordered table-detalle">
            <thead class="text-center small">
            <tr>
                <th>Cant.</th><th>Descripción</th><th>P. Unitario</th><th>Importe</th><th>IVA</th><th class="text-end">Total</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($datosFactura['conceptos'] as $c): ?>
                <tr>
                    <td class="text-center"><?= number_format($c['cant'], 2) ?></td>
                    <td><?= htmlspecialchars($c['desc'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end">$<?= number_format($c['prec'], 2) ?></td>
                    <td class="text-end">$<?= number_format($c['imp'], 2) ?></td>
                    <td class="text-end">$<?= number_format($c['iva'], 2) ?></td>
                    <td class="text-end fw-bold">$<?= number_format($c['imp'] + $c['iva'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="row justify-content-end mb-4">
            <div class="col-4">
                <table class="table table-sm table-borderless">
                    <tr><td>Subtotal:</td><td class="text-end">$<?= number_format($datosFactura['subtotal'], 2) ?></td></tr>
                    <tr><td>IVA (16%):</td><td class="text-end">$<?= number_format($datosFactura['iva_total'], 2) ?></td></tr>
                    <tr class="border-top border-dark fw-bold h6"><td>TOTAL:</td><td class="text-end">$<?= number_format($datosFactura['total'], 2) ?></td></tr>
                </table>
            </div>
        </div>

        <div class="border p-3 bg-light rounded">
            <div class="row align-items-center">
                <div class="col-3 text-center border-end">
                    <div id="qrcode"></div>
                    <div class="mt-2 small">
                        <a href="<?= htmlspecialchars($datosFactura['cadena_qr'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                            Abrir verificación SAT
                        </a>
                    </div>
                </div>

                <div class="col-9 ps-4">
                    <div class="row mb-2">
                        <div class="col-12">
                            <span class="fw-bold">Folio Fiscal (UUID):</span> <?= htmlspecialchars($datosFactura['uuid'], ENT_QUOTES, 'UTF-8') ?><br>
                            <span class="fw-bold">No. de Serie del Certificado del SAT:</span> <?= htmlspecialchars($datosFactura['no_cert_sat'], ENT_QUOTES, 'UTF-8') ?><br>
                            <span class="fw-bold">RFC Proveedor de Certificación:</span> <?= htmlspecialchars($datosFactura['rfc_prov'], ENT_QUOTES, 'UTF-8') ?><br>
                            <span class="fw-bold">Fecha y Hora de Certificación:</span> <?= htmlspecialchars($datosFactura['fecha_timbrado'], ENT_QUOTES, 'UTF-8') ?><br>

                            <hr class="my-2">

                            <span class="fw-bold">Validación SAT (automática WS):</span><br>
                            <?php if (!empty($datosFactura['sat']) && !empty($datosFactura['sat']['ok'])): ?>
                                <span class="fw-bold">Estado:</span> <?= htmlspecialchars($datosFactura['sat']['Estado'] ?: 'DESCONOCIDO', ENT_QUOTES, 'UTF-8') ?><br>
                                <span class="fw-bold">Código:</span> <?= htmlspecialchars($datosFactura['sat']['CodigoEstatus'] ?: 'DESCONOCIDO', ENT_QUOTES, 'UTF-8') ?><br>
                                <span class="fw-bold">Cancelable:</span> <?= htmlspecialchars($datosFactura['sat']['EsCancelable'] ?: 'DESCONOCIDO', ENT_QUOTES, 'UTF-8') ?><br>
                                <?php if (!empty($datosFactura['sat']['ValidacionEFOS'])): ?>
                                    <span class="fw-bold">Validación EFOS:</span> <?= htmlspecialchars($datosFactura['sat']['ValidacionEFOS'], ENT_QUOTES, 'UTF-8') ?><br>
                                <?php endif; ?>
                                <?php if (!empty($datosFactura['sat']['EstatusCancelacion'])): ?>
                                    <span class="fw-bold">Estatus Cancelación:</span> <?= htmlspecialchars($datosFactura['sat']['EstatusCancelacion'], ENT_QUOTES, 'UTF-8') ?><br>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-danger fw-bold">No se pudo consultar SAT:</span>
                                <?= htmlspecialchars($datosFactura['sat']['error'] ?? 'Error desconocido', ENT_QUOTES, 'UTF-8') ?>

                                <?php if (!empty($datosFactura['sat']['raw_preview'])): ?>
                                    <div class="mt-2"><b>Preview:</b></div>
                                    <pre class="mono p-2 bg-white border rounded" style="max-height:200px; overflow:auto;"><?= htmlspecialchars($datosFactura['sat']['raw_preview'], ENT_QUOTES, 'UTF-8') ?></pre>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="stamp-text mt-2">
                        <strong>Sello Digital del Emisor (CFDI):</strong><br><?= htmlspecialchars($datosFactura['sello_cfd'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="stamp-text mt-2">
                        <strong>Sello Digital del SAT:</strong><br><?= htmlspecialchars($datosFactura['sello_sat'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: <?= json_encode($datosFactura['cadena_qr'], JSON_UNESCAPED_UNICODE) ?>,
            width: 125,
            height: 125
        });
    </script>
    <?php endif; ?>
</div>
</body>
</html>
