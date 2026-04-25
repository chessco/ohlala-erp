<?php
require_once 'flow_connector.php';

// Datos de prueba (Reemplaza con un número real para probar)
$test_data = [
    'phone' => '526442221844', // Número de WhatsApp para MVP
    'folio' => '#PR-2026-TEST',
    'amount' => '$1,200.00 MXN',
    'item' => 'Insumo de Prueba (Flow Bridge)',
    'requestor' => 'Antigravity Test Bot',
    'id' => 999
];

echo "Iniciando prueba de integración PHP -> Flow...\n";
$result = FlowConnector::sendApprovalRequest($test_data);

if ($result['success']) {
    echo "✅ ÉXITO: Mensaje enviado correctamente.\n";
} else {
    echo "❌ ERROR: Código HTTP " . $result['code'] . "\n";
    echo "Respuesta: " . $result['response'] . "\n";
}
