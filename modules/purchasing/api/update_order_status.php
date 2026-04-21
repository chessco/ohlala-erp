<?php
/**
 * API: Update Purchase Order Status
 * Location: modules/purchasing/api/update_order_status.php
 */
session_start();
header('Content-Type: application/json');
include(__DIR__ . '/../../../conexion.php');

if (!isset($_SESSION['autenticado'])) {
    echo json_encode(["status" => "error", "message" => "No autorizado."]);
    exit;
}

$id     = (int)($_POST['id'] ?? 0);
$status = mysqli_real_escape_string($conexion, $_POST['status'] ?? '');

if (empty($status) || $id <= 0) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos."]);
    exit;
}

try {
    $sql = "UPDATE pur_orders SET status = '$status' WHERE id = $id";
    if (mysqli_query($conexion, $sql)) {
        echo json_encode(["status" => "success", "message" => "Estado actualizado a $status."]);
    } else {
        throw new \Exception(mysqli_error($conexion));
    }
} catch (\Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
