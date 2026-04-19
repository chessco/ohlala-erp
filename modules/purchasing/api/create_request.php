<?php
/**
 * API: Create New Purchase Request
 * Location: modules/purchasing/api/create_request.php
 */

session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');
include(__DIR__ . '/../../../conexion.php');

// Autoloader for Purchasing Module
spl_autoload_register(function ($class) {
    if (strpos($class, 'Purchasing\\') === 0) {
        $file = __DIR__ . '/../src/' . str_replace(['Purchasing\\', '\\'], ['', '/'], $class) . '.php';
        if (file_exists($file)) require $file;
    }
});

use Purchasing\Infrastructure\PurchaseRepository;
use Purchasing\Infrastructure\ApprovalRepository;
use Purchasing\Infrastructure\NotificationGateway;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

// 1. Context & Inputs
$requesterId = $_SESSION['usuario_id'] ?? null;
$tenantId    = $_SESSION['tenant_id'] ?? 1; // Assuming tenant_id might be in session

if (!$requesterId) {
    echo json_encode(["status" => "error", "message" => "Sesión expirada. Por favor, reingrese."]);
    exit;
}

$description = mysqli_real_escape_string($conexion, $_POST['description'] ?? '');
$amount      = (float)($_POST['amount'] ?? 0);

if (empty($description) || $amount <= 0) {
    echo json_encode(["status" => "error", "message" => "Descripción y monto son obligatorios."]);
    exit;
}

try {
    // 2. Repositories
    $purchaseRepo = new PurchaseRepository($conexion);
    $approvalRepo = new ApprovalRepository($conexion);
    $notifGateway = new NotificationGateway($conexion);

    // 3. Create Request
    $sql = "INSERT INTO pur_requests (tenant_id, requester_id, total_amount, status, description, current_level) 
            VALUES ($tenantId, $requesterId, $amount, 'pending', '$description', 1)";
    
    if (!mysqli_query($conexion, $sql)) {
        throw new \Exception("Error al crear la solicitud: " . mysqli_error($conexion));
    }
    
    $requestId = mysqli_insert_id($conexion);

    use Purchasing\Infrastructure\SettingsRepository;
    $settingsRepo = new SettingsRepository($conexion);
    $settings = $settingsRepo->getAll();

    $appr1 = (int)($settings['approver_level_1'] ?? 2);
    $appr2 = (int)($settings['approver_level_2'] ?? 3);
    $appr3 = (int)($settings['approver_level_3'] ?? 4);

    // 4. Initialize Approval Steps
    $approvalRepo->initializeSteps($requestId, $appr1, $appr2, $appr3);

    // 5. Notify Level 1
    // We need Level 1 contact data
    $resUser = mysqli_query($conexion, "SELECT correo, telefono FROM usuarios WHERE id = $appr1");
    $approverData = mysqli_fetch_assoc($resUser);

    if ($approverData) {
        $notifGateway->notifyNextApprover($requestId, $approverData, $description, 1);
    }

    echo json_encode([
        "status" => "success",
        "message" => "Solicitud #$requestId creada correctamente y flujo de aprobación iniciado.",
        "id" => $requestId
    ]);

} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
