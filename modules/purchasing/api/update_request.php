<?php
/**
 * API: Update Purchase Request
 * Location: modules/purchasing/api/update_request.php
 */

session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');
include(__DIR__ . '/../../../conexion.php');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

$id          = (int)($_POST['id'] ?? 0);
$description = mysqli_real_escape_string($conexion, $_POST['description'] ?? '');
$amount      = (float)($_POST['amount'] ?? 0);

if ($id <= 0 || empty($description) || $amount <= 0) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos para la actualización."]);
    exit;
}

try {
    $sql = "UPDATE pur_requests SET description = '$description', total_amount = $amount WHERE id = $id";
    if (!mysqli_query($conexion, $sql)) {
        throw new \Exception("Error al actualizar: " . mysqli_error($conexion));
    }

    echo json_encode([
        "status" => "success",
        "message" => "Solicitud #$id actualizada correctamente."
    ]);

} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
