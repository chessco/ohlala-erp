<?php
/**
 * API: Get Single Catalog Item
 */
session_start();
header('Content-Type: application/json');
include(__DIR__ . '/../../../conexion.php');

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(["status" => "error", "message" => "ID no proporcionado."]);
    exit;
}

$res = mysqli_query($conexion, "SELECT * FROM pur_catalog_items WHERE id = " . (int)$id);
$data = mysqli_fetch_assoc($res);

if ($data) {
    echo json_encode(["status" => "success", "data" => $data]);
} else {
    echo json_encode(["status" => "error", "message" => "Insumo no encontrado."]);
}
