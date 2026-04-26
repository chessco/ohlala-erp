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

if (!$requestId || !$rawAction) {
    echo json_encode(["status" => "error", "message" => "Faltan parámetros requeridos (ID o Acción)."]);
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
    $notifGateway = new NotificationGateway($conexion);
    $service = new ApprovalService($conexion, $approvalRepo, $purchaseRepo, $notifGateway);
    
    error_log("[Webhook Approval] START: Request $requestId, Action $action, Phone " . ($data['phone'] ?? 'N/A'));

    if (!$userId && isset($data['phone'])) {
        $phone = preg_replace('/\D/', '', $data['phone']);
        $last10 = substr($phone, -10); // Los últimos 10 dígitos son los más fiables
        
        // PRIORIDAD: Buscar si el usuario con ese teléfono es el aprobador ACTUAL de esta solicitud
        $sqlAuth = "SELECT u.id FROM usuarios u 
                    JOIN pur_approval_steps s ON u.id = s.approver_id 
                    WHERE s.request_id = $requestId 
                    AND s.status = 'pending' 
                    AND u.telefono LIKE '%$last10%' 
                    LIMIT 1";
        $resAuth = mysqli_query($conexion, $sqlAuth);
        
        if ($rowAuth = mysqli_fetch_assoc($resAuth)) {
            $userId = $rowAuth['id'];
        } else {
            // Fallback: Cualquier usuario con ese teléfono (comportamiento anterior)
            $resUser = mysqli_query($conexion, "SELECT id FROM usuarios WHERE telefono LIKE '%$last10%' LIMIT 1");
            if ($rowUser = mysqli_fetch_assoc($resUser)) {
                $userId = $rowUser['id'];
            }
        }
    }

    if (!$userId) {
        throw new \Exception("No se pudo identificar al usuario aprobador.");
    }

    // 5. Process Approval Flow
    $result = $service->process($requestId, $action, $userId);

    // Fetch user name for the response
    $userName = "Usuario";
    $resName = mysqli_query($conexion, "SELECT nombre_completo FROM usuarios WHERE id = $userId");
    if ($rowName = mysqli_fetch_assoc($resName)) {
        $userName = $rowName['nombre_completo'];
    }

    echo json_encode([
        "status" => "success",
        "approver_name" => $userName,
        "data"   => $result
    ]);

} catch (\Exception $e) {
    http_response_code(400);
    error_log("[Webhook Approval Error] Request $requestId: " . $e->getMessage());
    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}
