<?php
/**
 * Ohlala ERP - Webhook Handler for Hierarchical Approvals
 * Location: /webhooks/approval.php
 */

header('Content-Type: application/json');
include(__DIR__ . '/../conexion.php');

// Simple Autoloader for Purchasing Module
spl_autoload_register(function ($class) {
    $prefix = 'Purchasing\\';
    $base_dir = __DIR__ . '/../modules/purchasing/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

use Purchasing\Infrastructure\PurchaseRepository;
use Purchasing\Infrastructure\ApprovalRepository;
use Purchasing\Infrastructure\NotificationGateway;
use Purchasing\Application\ApprovalService;

// 1. Receive & Parse Payload
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Payload inválido."]);
    exit;
}

$requestId = $data['request_id'] ?? null;
$rawAction = strtolower(trim($data['action'] ?? ''));
$userId    = $data['user_id'] ?? null;

if (!$requestId || !$rawAction || !$userId) {
    echo json_encode(["status" => "error", "message" => "Faltan parámetros requeridos."]);
    exit;
}

// 2. Normalize Action
$action = null;
$approvedKeywords = ['aprobar', 'ok', 'approved', 'si', 'acepto', 'visto bueno', 'vobo'];
$rejectedKeywords = ['rechazar', 'no', 'rejected', 'cancelar', 'denegado'];

if (in_array($rawAction, $approvedKeywords)) {
    $action = 'approved';
} elseif (in_array($rawAction, $rejectedKeywords)) {
    $action = 'rejected';
}

if (!$action) {
    echo json_encode(["status" => "error", "message" => "Acción '$rawAction' no reconocida."]);
    exit;
}

// 3. Initialize Services
try {
    $purchaseRepo = new PurchaseRepository($conexion);
    $approvalRepo = new ApprovalRepository($conexion);
    $notifGateway = new NotificationGateway();
    $service = new ApprovalService($conexion, $approvalRepo, $purchaseRepo, $notifGateway);

    // 4. Process Approval Flow
    $result = $service->process($requestId, $action, $userId);

    echo json_encode([
        "status" => "success",
        "data"   => $result
    ]);

} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}
