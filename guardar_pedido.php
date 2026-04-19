<?php
// ... (conexión y recepción de datos iniciales) ...

if ($id) {
    // 1. VALIDACIÓN DE SEGURIDAD: Consultar el estado actual en la base de datos
    $check_sql = "SELECT estado FROM pedidos WHERE id = $id";
    $res_check = mysqli_query($conexion, $check_sql);
    $pedido_actual = mysqli_fetch_assoc($res_check);

    if ($pedido_actual['estado'] === 'Cancelado') {
        die("Error: No se pueden modificar pedidos con estado 'Cancelado'.");
    }

    // 2. Si no está cancelado, proceder con el UPDATE normal
    $sql_p = "UPDATE pedidos SET fecha = '$fecha', cliente_nombre = '$cliente', estado = '$estado' 
              WHERE id = $id";
    mysqli_query($conexion, $sql_p);
    
    // ... (resto del código de actualización de detalles) ...
}