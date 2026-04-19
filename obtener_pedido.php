<?php
include('conexion.php');
session_start();

if (!isset($_SESSION['autenticado'])) {
    exit('No autorizado');
}

if(isset($_POST['id'])) {
    $id = intval($_POST['id']);

    
    // Datos del pedido
    $res = mysqli_query($conexion, "SELECT * FROM pedidos WHERE id = $id");
    $pedido = mysqli_fetch_assoc($res);
    
    // Detalles del pedido
    $detalles = mysqli_query($conexion, "SELECT pd.*, p.nombre, p.imagen FROM pedido_detalles pd 
                                         JOIN productos p ON pd.producto_id = p.id 
                                         WHERE pd.pedido_id = $id");
    
    $items = [];
    while($d = mysqli_fetch_assoc($detalles)) {
        $items[] = [
            'id' => $d['producto_id'],
            'nombre' => $d['nombre'],
            'cantidad' => (int)$d['cantidad'],
            'imagen' => $d['imagen'],
            'comentario' => $d['comentario']
        ];
    }
    
    $pedido['items'] = $items;
    echo json_encode($pedido);
}
?>