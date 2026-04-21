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
use Purchasing\Infrastructure\SettingsRepository;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

// 1. Context & Inputs
$requesterId = $_SESSION['usuario_id'] ?? null;
$tenantId    = $_SESSION['tenant_id'] ?? 1;

if (!$requesterId) {
    echo json_encode(["status" => "error", "message" => "Sesión expirada."]);
    exit;
}

$description = mysqli_real_escape_string($conexion, $_POST['description'] ?? '');
$supplierId  = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 'NULL';
$items       = $_POST['items'] ?? [];

if (empty($items)) {
    echo json_encode(["status" => "error", "message" => "Debe incluir al menos un insumo."]);
    exit;
}

// Start Transaction
mysqli_begin_transaction($conexion);

try {
    // 2. Calculate Total
    $totalAmount = 0;
    foreach ($items as $item) {
        $totalAmount += (float)$item['quantity'] * (float)$item['unit_price'];
    }

    // 3. Insert Header
    $sqlHeader = "INSERT INTO pur_requests (tenant_id, requester_id, supplier_id, total_amount, status, description, current_level) 
                  VALUES ($tenantId, $requesterId, $supplierId, $totalAmount, 'pending', '$description', 1)";
    
    if (!mysqli_query($conexion, $sqlHeader)) {
        throw new \Exception("Error al crear cabecera: " . mysqli_error($conexion));
    }
    
    $requestId = mysqli_insert_id($conexion);

    // 4. Insert Items
    foreach ($items as $item) {
        $itemId = (int)$item['item_id'];
        $qty    = (float)$item['quantity'];
        $price  = (float)$item['unit_price'];
        $sub    = $qty * $price;
        
        // Fetch name from catalog for description snapshot
        $resCat = mysqli_query($conexion, "SELECT name FROM pur_catalog_items WHERE id = $itemId");
        $catItem = mysqli_fetch_assoc($resCat);
        $itemDesc = mysqli_real_escape_string($conexion, $catItem['name'] ?? 'Insumo Desconocido');

        $sqlItem = "INSERT INTO pur_request_items (request_id, item_id, description, quantity, unit_price, subtotal) 
                    VALUES ($requestId, $itemId, '$itemDesc', $qty, $price, $sub)";
        
        if (!mysqli_query($conexion, $sqlItem)) {
            throw new \Exception("Error al insertar partida: " . mysqli_error($conexion));
        }
    }

    // 5. Initialize Approval Steps
    $approvalRepo = new Purchasing\Infrastructure\ApprovalRepository($conexion);
    $settingsRepo = new Purchasing\Infrastructure\SettingsRepository($conexion);
    $settings = $settingsRepo->getAll();

    $appr1 = (int)($settings['approver_level_1'] ?? 2);
    $appr2 = (int)($settings['approver_level_2'] ?? 3);
    $appr3 = (int)($settings['approver_level_3'] ?? 4);

    $approvalRepo->initializeSteps($requestId, $appr1, $appr2, $appr3);

    mysqli_commit($conexion);

    // 6. Notify Level 1 (Fault-Tolerant)
    $notifGateway = new Purchasing\Infrastructure\NotificationGateway($conexion);
    $notifStatus = "y la notificación ha sido enviada.";
    try {
        $resUser = mysqli_query($conexion, "SELECT nombre_completo, correo, telefono FROM usuarios WHERE id = $appr1");
        $approverData = mysqli_fetch_assoc($resUser);
        if ($approverData) {
            $approverData['nombre'] = $approverData['nombre_completo']; 
            $notifGateway->notifyNextApprover($requestId, $approverData, $description ?: "Solicitud de Insumos", 1);
        }

        // Notify Creator that the process has started
        $resRequester = mysqli_query($conexion, "SELECT nombre_completo, correo FROM usuarios WHERE id = $requesterId");
        $requesterData = mysqli_fetch_assoc($resRequester);
        if ($requesterData) {
            $notifGateway->notifyProcessStarted($requestId, $requesterData, $totalAmount);
        }
    } catch (\Throwable $e_notif) {
        $notifStatus = "pero hubo un detalle con el envío: " . $e_notif->getMessage();
    }

    echo json_encode([
        "status" => "success",
        "message" => "Solicitud #$requestId creada correctamente $notifStatus",
        "id" => $requestId
    ]);

} catch (\Throwable $e) {
    if (isset($conexion)) mysqli_rollback($conexion);
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
