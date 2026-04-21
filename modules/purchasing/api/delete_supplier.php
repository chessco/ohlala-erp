<?php
/**
 * API: Delete Supplier
 * Location: modules/purchasing/api/delete_supplier.php
 */
session_start();
header('Content-Type: application/json');
include(__DIR__ . '/../../../conexion.php');

if (!isset($_SESSION['autenticado'])) {
    echo json_encode(["status" => "error", "message" => "No autorizado."]);
    exit;
}

$id = (int)($_POST['id'] ?? 0);

try {
    // Note: In production, check for FK constraints (e.g., if there are orders)
    $sql = "DELETE FROM pur_suppliers WHERE id = $id";
    if (mysqli_query($conexion, $sql)) {
        echo json_encode(["status" => "success", "message" => "Proveedor eliminado."]);
    } else {
        throw new \Exception(mysqli_error($conexion));
    }
} catch (\Exception $e) {
    echo json_encode(["status" => "error", "message" => "No se puede eliminar: el proveedor puede tener órdenes asociadas."]);
}
