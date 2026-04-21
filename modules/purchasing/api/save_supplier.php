<?php
/**
 * API: Save Supplier (Create/Update)
 * Location: modules/purchasing/api/save_supplier.php
 */
session_start();
header('Content-Type: application/json');
include(__DIR__ . '/../../../conexion.php');

if (!isset($_SESSION['autenticado'])) {
    echo json_encode(["status" => "error", "message" => "No autorizado."]);
    exit;
}

$id      = (int)($_POST['id'] ?? 0);
$name    = mysqli_real_escape_string($conexion, $_POST['name'] ?? '');
$rfc     = mysqli_real_escape_string($conexion, $_POST['rfc'] ?? '');
$contact = mysqli_real_escape_string($conexion, $_POST['contact_person'] ?? '');
$email   = mysqli_real_escape_string($conexion, $_POST['email'] ?? '');
$phone   = mysqli_real_escape_string($conexion, $_POST['phone'] ?? '');
$address = mysqli_real_escape_string($conexion, $_POST['address'] ?? '');

if (empty($name)) {
    echo json_encode(["status" => "error", "message" => "El nombre es obligatorio."]);
    exit;
}

try {
    if ($id > 0) {
        $sql = "UPDATE pur_suppliers SET 
                name = '$name', rfc = '$rfc', contact_person = '$contact', 
                email = '$email', phone = '$phone', address = '$address' 
                WHERE id = $id";
    } else {
        $sql = "INSERT INTO pur_suppliers (name, rfc, contact_person, email, phone, address) 
                VALUES ('$name', '$rfc', '$contact', '$email', '$phone', '$address')";
    }

    if (mysqli_query($conexion, $sql)) {
        echo json_encode(["status" => "success", "message" => "Proveedor guardado."]);
    } else {
        throw new \Exception(mysqli_error($conexion));
    }
} catch (\Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
