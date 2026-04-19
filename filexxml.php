<?php
/**
 * PROYECTO: PEDIDOS OHLALA - CFDI 4.0 FULL DATA + COMPLEMENTO SAT
 */

$host = "localhost"; $user = "root"; $pass = ""; $db = "pedidos_ohlala";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) die("Error de conexión: " . $conn->connect_error);

$datosFactura = null;

function procesarCFDI($tmpFile, $conn) {
    $xml = simplexml_load_file($tmpFile);
    $ns = $xml->getNamespaces(true);
    $xml->registerXPathNamespace('cfdi', $ns['cfdi']);
    $xml->registerXPathNamespace('tfd', $ns['tfd']);

    $tfd = $xml->xpath('//tfd:TimbreFiscalDigital')[0];
    $emisor = $xml->xpath('//cfdi:Emisor')[0];
    $receptor = $xml->xpath('//cfdi:Receptor')[0];

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
        'rfc_e' => (string)$emisor['Rfc'], 'nom_e' => (string)$emisor['Nombre'],
        'regimen_e' => (string)$emisor['RegimenFiscal'],
        'rfc_r' => (string)$receptor['Rfc'], 'nom_r' => (string)$receptor['Nombre'],
        'dom_r' => (string)$receptor['DomicilioFiscalReceptor'],
        'regimen_r' => (string)$receptor['RegimenFiscalReceptor'],
        'uso_cfdi' => (string)$receptor['UsoCFDI'],
        // --- DATOS DEL COMPLEMENTO (SOLICITADOS) ---
        'uuid'          => (string)$tfd['UUID'],
        'sello_cfd'     => (string)$tfd['SelloCFD'],
        'sello_sat'     => (string)$tfd['SelloSAT'],
        'rfc_prov'      => (string)$tfd['RfcProvCertif'],
        'no_cert_sat'   => (string)$tfd['NoCertificadoSAT'],
        'fecha_timbrado'=> (string)$tfd['FechaTimbrado'],
        'conceptos'     => []
    ];

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
    $data['cadena_qr'] = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id={$data['uuid']}&re={$data['rfc_e']}&rr={$data['rfc_r']}&tt=".number_format($data['total'], 6, '.', '')."&fe=".substr($data['sello_cfd'], -8);

    return $data;
}

if (isset($_POST['subir_xml'])) {
    $datosFactura = procesarCFDI($_FILES['archivo_xml']['tmp_name'], $conn);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CFDI 4.0 Detallado - OhLaLa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        .invoice-card { background: white; padding: 40px; border: 1px solid #ddd; max-width: 950px; margin: 20px auto; font-size: 0.82rem; }
        .data-label { font-weight: bold; color: #555; text-transform: uppercase; font-size: 0.65rem; border-bottom: 1px solid #eee; display: block; margin-bottom: 5px; }
        .stamp-text { word-break: break-all; font-family: 'Courier New', monospace; font-size: 0.55rem; color: #555; line-height: 1.2; }
        .table-detalle th { background-color: #f8f9fa; }
        @media print { .no-print { display: none !important; } .invoice-card { border: none; margin: 0; width: 100%; } }
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

    <?php if ($datosFactura): ?>
    <div class="invoice-card shadow">
        <div class="row border-bottom pb-2 mb-3 align-items-center">
            <div class="col-7">
                <h2 class="text-primary fw-bold mb-0">PEDIDOS OHLALA</h2>
                <h6 class="mb-0 fw-bold"><?php echo $datosFactura['nom_e']; ?></h6>
                <p class="mb-0">RFC: <?php echo $datosFactura['rfc_e']; ?> | Régimen: <?php echo $datosFactura['regimen_e']; ?></p>
            </div>
            <div class="col-5 text-end text-uppercase">
                <button onclick="window.print()" class="btn btn-sm btn-dark no-print mb-2">Imprimir</button>
                <h4 class="text-danger fw-bold mb-0">FOLIO: <?php echo $datosFactura['folio']; ?></h4>
                <p class="mb-0 small">CP: <?php echo $datosFactura['lugar_exp']; ?> | Tipo: <?php echo $datosFactura['tipo_comp']; ?></p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-6 border-end">
                <span class="data-label">Datos del Receptor</span>
                <h6 class="mb-0 fw-bold"><?php echo $datosFactura['nom_r']; ?></h6>
                <p class="mb-0">RFC: <strong><?php echo $datosFactura['rfc_r']; ?></strong></p>
                <p class="mb-0">Dom. Fiscal: <?php echo $datosFactura['dom_r']; ?> | Uso CFDI: <?php echo $datosFactura['uso_cfdi']; ?></p>
            </div>
            <div class="col-6 ps-4">
                <span class="data-label">Información de Pago</span>
                <div class="row">
                    <div class="col-6">
                        <strong>Fecha Emisión:</strong><br><?php echo $datosFactura['fecha']; ?><br>
                        <strong>Moneda:</strong> <?php echo $datosFactura['moneda']; ?>
                    </div>
                    <div class="col-6">
                        <strong>Método:</strong> <?php echo $datosFactura['metodo_pago']; ?><br>
                        <strong>Forma:</strong> <?php echo $datosFactura['forma_pago']; ?>
                    </div>
                </div>
            </div>
        </div>

        <table class="table table-sm table-bordered table-detalle">
            <thead class="text-center small">
                <tr><th>Cant.</th><th>Descripción</th><th>P. Unitario</th><th>Importe</th><th>IVA</th><th class="text-end">Total</th></tr>
            </thead>
            <tbody>
                <?php foreach ($datosFactura['conceptos'] as $c): ?>
                <tr>
                    <td class="text-center"><?php echo number_format($c['cant'], 2); ?></td>
                    <td><?php echo $c['desc']; ?></td>
                    <td class="text-end">$<?php echo number_format($c['prec'], 2); ?></td>
                    <td class="text-end">$<?php echo number_format($c['imp'], 2); ?></td>
                    <td class="text-end">$<?php echo number_format($c['iva'], 2); ?></td>
                    <td class="text-end fw-bold">$<?php echo number_format($c['imp'] + $c['iva'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="row justify-content-end mb-4">
            <div class="col-4">
                <table class="table table-sm table-borderless">
                    <tr><td>Subtotal:</td><td class="text-end">$<?php echo number_format($datosFactura['subtotal'], 2); ?></td></tr>
                    <tr><td>IVA (16%):</td><td class="text-end">$<?php echo number_format($datosFactura['iva_total'], 2); ?></td></tr>
                    <tr class="border-top border-dark fw-bold h6"><td>TOTAL:</td><td class="text-end">$<?php echo number_format($datosFactura['total'], 2); ?></td></tr>
                </table>
            </div>
        </div>

        <div class="border p-3 bg-light rounded">
            <div class="row align-items-center">
                <div class="col-3 text-center border-end">
                    <div id="qrcode"></div>
                </div>
                <div class="col-9 ps-4">
                    <div class="row mb-2">
                        <div class="col-12">
                            <span class="fw-bold">Folio Fiscal (UUID):</span> <?php echo $datosFactura['uuid']; ?><br>
                            <span class="fw-bold">No. de Serie del Certificado del SAT:</span> <?php echo $datosFactura['no_cert_sat']; ?><br>
                            <span class="fw-bold">RFC Proveedor de Certificación:</span> <?php echo $datosFactura['rfc_prov']; ?><br>
                            <span class="fw-bold">Fecha y Hora de Certificación:</span> <?php echo $datosFactura['fecha_timbrado']; ?>
                        </div>
                    </div>
                    <div class="stamp-text mt-2">
                        <strong>Sello Digital del Emisor (CFDI):</strong><br><?php echo $datosFactura['sello_cfd']; ?>
                    </div>
                    <div class="stamp-text mt-2">
                        <strong>Sello Digital del SAT:</strong><br><?php echo $datosFactura['sello_sat']; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo $datosFactura['cadena_qr']; ?>",
            width: 125, height: 125
        });
    </script>
    <?php endif; ?>
</div>
</body>
</html>