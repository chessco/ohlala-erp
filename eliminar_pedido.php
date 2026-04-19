<?php
include('conexion.php');
session_start();

// Validar que sea admin
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    http_response_code(403);
    echo "No autorizado. Solo administradores pueden eliminar pedidos.";
    exit();
}

if(isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // Iniciar transacción para asegurar integridad
    mysqli_begin_transaction($conexion);
    
    try {
        // 1. Borrar detalles del pedido
        $sql_detalles = "DELETE FROM pedido_detalles WHERE pedido_id = $id";
        if(!mysqli_query($conexion, $sql_detalles)) {
            throw new Exception("Error al borrar detalles: " . mysqli_error($conexion));
        }
        
        // 2. Borrar el pedido principal
        $sql_pedido = "DELETE FROM pedidos WHERE id = $id";
        if(!mysqli_query($conexion, $sql_pedido)) {
            throw new Exception("Error al borrar pedido: " . mysqli_error($conexion));
        }
        
        mysqli_commit($conexion);
        echo "ok";
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "ID no proporcionado";
}
?>
