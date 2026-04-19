<?php
/**
 * Ohlala ERP - Approval Flow Verification Script
 * This script seeds test data and simulates the full approval cycle.
 */

include(__DIR__ . '/../../conexion.php');

// Autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'Purchasing\\') === 0) {
        $file = __DIR__ . '/src/' . str_replace(['Purchasing\\', '\\'], ['', '/'], $class) . '.php';
        if (file_exists($file)) require $file;
    }
});

use Purchasing\Infrastructure\PurchaseRepository;
use Purchasing\Infrastructure\ApprovalRepository;

$purchaseRepo = new PurchaseRepository($conexion);
$approvalRepo = new ApprovalRepository($conexion);

echo "--- INICIANDO VERIFICACIÓN DE FLUJO ---\n";

// 1. Asegurar que existan los usuarios para el demo
mysqli_query($conexion, "INSERT IGNORE INTO usuarios (id, usuario, nombre_completo, telefono) VALUES 
    (1, 'solicitante', 'Juan Peticiones', '5511223344'),
    (2, 'gerente', 'Gerente Nivel 1', '5522334455'),
    (3, 'director', 'Director Nivel 2', '5533445566'),
    (4, 'ceo', 'CEO Nivel 3', '5544556677')
");

// 2. Crear una solicitud de prueba
mysqli_query($conexion, "INSERT INTO pur_requests (tenant_id, requester_id, total_amount, status, description) 
                         VALUES (1, 1, 50000.00, 'pending', 'Suministros Demo $100K')");
$requestId = mysqli_insert_id($conexion);
echo "1. Solicitud #$requestId creada.\n";

// 3. Inicializar niveles
$approvalRepo->initializeSteps($requestId, 2, 3, 4);
echo "2. Niveles de aprobación (1, 2, 3) inicializados.\n";

// 4. Simular Webhook Level 1
echo "3. Simulando aprobación NIVEL 1 vía Webhook...\n";
simulateWebhook($requestId, 'aprobar', 2);

// 5. Simular Webhook Level 2
echo "4. Simulando aprobación NIVEL 2 vía Webhook...\n";
simulateWebhook($requestId, 'ok', 3);

// 6. Simular Webhook Level 3
echo "5. Simulando aprobación NIVEL 3 vía Webhook...\n";
simulateWebhook($requestId, 'visto bueno', 4);

// 7. Resultado Final
$request = $purchaseRepo->findById($requestId);
echo "--- RESULTADO FINAL ---\n";
echo "Estado Final: " . strtoupper($request['status']) . "\n";
echo "Nivel Actual: " . $request['current_level'] . "\n";

function simulateWebhook($requestId, $action, $userId) {
    $url = "http://localhost/ohlala-erp/webhooks/approval.php";
    $payload = json_encode([
        "request_id" => $requestId,
        "action" => $action,
        "user_id" => $userId
    ]);

    // Usar file_get_contents para simular POST (contexto interno)
    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-type: application/json',
            'content' => $payload
        ]
    ];
    $context  = stream_context_create($opts);
    // Para simplificar en CLI local, incluiremos el archivo directamente en un sub-proceso
    // pero aquí solo mostramos el comando que lo haría.
    echo "   [EXEC] Webhook call for ID $requestId (Agent: $userId, Msg: $action)\n";
    
    // Simulación de ejecución directa para validar lógica sin depender de red local
    global $conexion;
    $purchaseRepo = new PurchaseRepository($conexion);
    $approvalRepo = new ApprovalRepository($conexion);
    $notifGateway = new \Purchasing\Infrastructure\NotificationGateway();
    $service = new \Purchasing\Application\ApprovalService($conexion, $approvalRepo, $purchaseRepo, $notifGateway);
    $service->process($requestId, normalize($action), $userId);
}

function normalize($raw) {
    $approved = ['aprobar', 'ok', 'approved', 'si', 'acepto', 'visto bueno', 'vobo'];
    return in_array(strtolower($raw), $approved) ? 'approved' : 'rejected';
}
?>
