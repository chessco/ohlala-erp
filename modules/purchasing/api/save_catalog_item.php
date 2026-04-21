<?php
/**
 * API: Save/Update Catalog Item
 */
session_start();
header('Content-Type: application/json');
include(__DIR__ . '/../../../conexion.php');

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "No autorizado."]);
    exit;
}

$id         = $_POST['id'] ?? null;
$name       = mysqli_real_escape_string($conexion, $_POST['name'] ?? '');
$unit       = mysqli_real_escape_string($conexion, $_POST['unit'] ?? '');
$basePrice  = (float)($_POST['base_price'] ?? 0);

if (empty($name) || empty($unit)) {
    echo json_encode(["status" => "error", "message" => "Nombre y unidad son obligatorios."]);
    exit;
}

if ($id) {
    $sql = "UPDATE pur_catalog_items SET name='$name', unit='$unit', base_price=$basePrice WHERE id=$id";
} else {
    $sql = "INSERT INTO pur_catalog_items (name, unit, base_price) VALUES ('$name', '$unit', $basePrice)";
}

if (mysqli_query($conexion, $sql)) {
    echo json_encode(["status" => "success", "message" => "Insumo guardado correctamente."]);
} else {
    echo json_encode(["status" => "error", "message" => mysqli_error($conexion)]);
}
