<?php
include('conexion.php');
session_start();

if (!isset($_SESSION['usuario_id'])) {
    die("Sesión expirada");
}

$usuario_id = $_SESSION['usuario_id'];
$fecha_hoy = date('Y-m-d');

// 1. Buscar el último pedido de este usuario
$sql_ultimo = "SELECT id, cliente_nombre FROM pedidos WHERE id_usuario = $usuario_id ORDER BY id DESC LIMIT 1";
$res_ultimo = mysqli_query($conexion, $sql_ultimo);

if (mysqli_num_rows($res_ultimo) > 0) {
    $datos_viejos = mysqli_fetch_assoc($res_ultimo);
    $id_viejo = $datos_viejos['id'];
    $cliente = mysqli_real_escape_string($conexion, $datos_viejos['cliente_nombre']);

    // 2. Generar nuevo Folio
    $res_f = mysqli_query($conexion, "SELECT COUNT(*) as total FROM pedidos");
    $num = mysqli_fetch_assoc($res_f)['total'] + 1;
    $folio = "OH-" . str_pad($num, 4, "0", STR_PAD_LEFT);

    mysqli_begin_transaction($conexion);

    try {
        // 3. Insertar nuevo encabezado
        $sql_ins = "INSERT INTO pedidos (folio, id_usuario, cliente_nombre, fecha, estado) 
                    VALUES ('$folio', $usuario_id, '$cliente', '$fecha_hoy', 'Pendiente')";
        mysqli_query($conexion, $sql_ins);
        $nuevo_id = mysqli_insert_id($conexion);

        // 4. Copiar los productos (Incluyendo precio y subtotal que son NOT NULL)
        $sql_detalles = "INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
                         SELECT $nuevo_id, producto_id, cantidad, precio_unitario, subtotal 
                         FROM pedido_detalles WHERE pedido_id = $id_viejo";
        
        mysqli_query($conexion, $sql_detalles);

        mysqli_commit($conexion);
        echo "ok";

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "No tienes pedidos previos para duplicar.";
}
?>