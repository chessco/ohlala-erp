<?php
include('conexion.php');
session_start();

// 1. Validar sesión por seguridad
if (!isset($_SESSION['autenticado'])) { 
    die("No autorizado"); 
}

// CASO: ELIMINAR CLIENTE
if(isset($_POST['eliminar_id'])) {
    header('Content-Type: application/json');
    $id = intval($_POST['eliminar_id']);
    
    $sql_del = "DELETE FROM clientes WHERE id = $id";
    if(mysqli_query($conexion, $sql_del)) {
        echo json_encode(['status' => 'ok', 'message' => 'Cliente eliminado']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error SQL: ' . mysqli_error($conexion)]);
    }
    exit();
}

// CASO: GUARDAR (NUEVO O EDITAR)
if(isset($_POST['nombre'])) {
    // Limpieza de datos para evitar inyecciones SQL
    $id             = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $codigo_cliente = mysqli_real_escape_string($conexion, $_POST['codigo_cliente']);
    $nombre         = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $rfc            = mysqli_real_escape_string($conexion, $_POST['rfc']);
    $correo         = mysqli_real_escape_string($conexion, $_POST['correo']);
    $telefono       = mysqli_real_escape_string($conexion, $_POST['telefono']);
    $comercial      = mysqli_real_escape_string($conexion, $_POST['comercial']);
    
    // Capturamos el Vendedor Asignado (si viene vacío guardamos NULL)
    $id_usuario     = !empty($_POST['id_usuario']) ? intval($_POST['id_usuario']) : "NULL";

    if($id > 0) {
        // --- ACTUALIZAR CLIENTE EXISTENTE ---
        $sql = "UPDATE clientes SET 
                    codigo_cliente = '$codigo_cliente',
                    nombre = '$nombre', 
                    rfc = '$rfc', 
                    correo = '$correo', 
                    telefono = '$telefono', 
                    comercial = '$comercial',
                    id_usuario = $id_usuario 
                WHERE id = $id";
    } else {
        // --- INSERTAR NUEVO CLIENTE ---
        $sql = "INSERT INTO clientes (codigo_cliente, nombre, rfc, correo, telefono, comercial, id_usuario) 
                VALUES ('$codigo_cliente', '$nombre', '$rfc', '$correo', '$telefono', '$comercial', $id_usuario)";
    }

    // Ejecución y respuesta al AJAX
    if(mysqli_query($conexion, $sql)) {
        echo "ok";
    } else {
        echo "Error en Base de Datos: " . mysqli_error($conexion);
    }
}
?>