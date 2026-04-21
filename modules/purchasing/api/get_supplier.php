<?php
/**
 * API: Get Single Supplier
 * Location: modules/purchasing/api/get_supplier.php
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
    $res = mysqli_query($conexion, "SELECT * FROM pur_suppliers WHERE id = $id");
    $data = mysqli_fetch_assoc($res);
    
    if($data) {
        echo json_encode(["status" => "success", "data" => $data]);
    } else {
        echo json_encode(["status" => "error", "message" => "Proveedor no encontrado."]);
    }
} catch (\Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
