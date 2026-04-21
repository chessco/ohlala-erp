<?php
/**
 * API: Create Purchase Order (OC)
 * Location: modules/purchasing/api/create_order.php
 */
session_start();
header('Content-Type: application/json');
include(__DIR__ . '/../../../conexion.php');

if (!isset($_SESSION['autenticado'])) {
    echo json_encode(["status" => "error", "message" => "No autorizado."]);
    exit;
}

// Data retrieval
$requestId  = (int)($_POST['requestId'] ?? 0);
$supplierId = (int)($_POST['supplierId'] ?? 0);
$notes      = mysqli_real_escape_string($conexion, $_POST['notes'] ?? '');
$items      = $_POST['items'] ?? []; // JSON or Array? Let's expect JSON encoded string if complex
if (is_string($items)) {
    $items = json_decode($items, true);
}

if ($requestId <= 0 || $supplierId <= 0 || empty($items)) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos para generar la OC."]);
    exit;
}

try {
    mysqli_begin_transaction($conexion);

    // 1. Generate Folio (OC-YYYY-ID)
    $year = date('Y');
    $folio = "OC-" . $year . "-" . str_pad($requestId, 4, '0', STR_PAD_LEFT);
    
    // Check if OC already exists for this folio (just in case)
    $checkFolio = mysqli_query($conexion, "SELECT id FROM pur_orders WHERE folio = '$folio'");
    if (mysqli_num_rows($checkFolio) > 0) {
        $folio .= "-" . time(); // Append timestamp if conflict
    }

    // 2. Calculate Total
    $totalAmount = 0;
    foreach ($items as $item) {
        $totalAmount += (float)$item['quantity'] * (float)$item['unit_price'];
    }

    // 3. Create Order
    $sqlOrder = "INSERT INTO pur_orders (folio, request_id, supplier_id, total_amount, status, notes) 
                 VALUES ('$folio', $requestId, $supplierId, $totalAmount, 'sent', '$notes')";
    if (!mysqli_query($conexion, $sqlOrder)) {
        throw new \Exception("Error al crear Orden: " . mysqli_error($conexion));
    }
    $orderId = mysqli_insert_id($conexion);

    // 4. Create Order Items
    foreach ($items as $item) {
        $desc = mysqli_real_escape_string($conexion, $item['description']);
        $qty  = (float)$item['quantity'];
        $price = (float)$item['unit_price'];
        $subtotal = $qty * $price;
        
        $sqlItem = "INSERT INTO pur_order_items (order_id, description, quantity, unit_price, subtotal) 
                    VALUES ($orderId, '$desc', $qty, $price, $subtotal)";
        if (!mysqli_query($conexion, $sqlItem)) {
            throw new \Exception("Error al crear item de Orden: " . mysqli_error($conexion));
        }
    }

    // 5. Update Request Status (Optional but recommended)
    mysqli_query($conexion, "UPDATE pur_requests SET status = 'ordered' WHERE id = $requestId");

    mysqli_commit($conexion);
    echo json_encode(["status" => "success", "message" => "Orden de Compra $folio generada con éxito.", "order_id" => $orderId]);

} catch (\Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
