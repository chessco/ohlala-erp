<?php
/**
 * API: Get Request Items
 * Location: modules/purchasing/api/get_request_items.php
 */
session_start();
header('Content-Type: application/json');
include(__DIR__ . '/../../../conexion.php');

if (!isset($_SESSION['autenticado'])) {
    echo json_encode(["status" => "error", "message" => "No autorizado."]);
    exit;
}

$id = (int)($_GET['id'] ?? 0);

try {
    $res = mysqli_query($conexion, "SELECT * FROM pur_request_items WHERE request_id = $id");
    $items = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $items[] = $row;
    }
    
    echo json_encode(["status" => "success", "data" => $items]);
} catch (\Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
