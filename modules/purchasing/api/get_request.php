<?php
/**
 * API: Get Single Purchase Request
 * Location: modules/purchasing/api/get_request.php
 */

session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');
include(__DIR__ . '/../../../conexion.php');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "ID inválido."]);
    exit;
}

try {
    $res = mysqli_query($conexion, "SELECT * FROM pur_requests WHERE id = $id");
    $data = mysqli_fetch_assoc($res);

    if (!$data) {
        throw new \Exception("Solicitud no encontrada.");
    }

    echo json_encode([
        "status" => "success",
        "data"   => $data
    ]);

} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
