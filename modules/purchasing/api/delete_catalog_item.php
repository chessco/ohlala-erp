<?php
/**
 * API: Delete/Deactivate Catalog Item
 */
session_start();
header('Content-Type: application/json');
include(__DIR__ . '/../../../conexion.php');

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "No autorizado."]);
    exit;
}

$id = $_POST['id'] ?? null;
if (!$id) {
    echo json_encode(["status" => "error", "message" => "ID no proporcionado."]);
    exit;
}

// We deactivate instead of delete to maintain historical integrity
$sql = "UPDATE pur_catalog_items SET status = 'inactive' WHERE id = " . (int)$id;

if (mysqli_query($conexion, $sql)) {
    echo json_encode(["status" => "success", "message" => "Insumo desactivado correctamente."]);
} else {
    echo json_encode(["status" => "error", "message" => mysqli_error($conexion)]);
}
