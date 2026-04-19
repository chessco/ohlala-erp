<?php
/**
 * API: Delete Purchase Request
 * Location: modules/purchasing/api/delete_request.php
 */

session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');
include(__DIR__ . '/../../../conexion.php');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "ID inválido."]);
    exit;
}

try {
    // 1. Delete associated approval steps
    mysqli_query($conexion, "DELETE FROM pur_approval_steps WHERE request_id = $id");
    
    // 2. Delete the request
    // Note: In production we'd do logical deletion (deleted_at), but for this module 
    // we'll do physical to keep it clean for the demo.
    mysqli_query($conexion, "DELETE FROM pur_requests WHERE id = $id");

    echo json_encode([
        "status" => "success",
        "message" => "Solicitud #$id eliminada correctamente."
    ]);

} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
