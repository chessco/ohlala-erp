<?php
include('conexion.php');
session_start();

// 1. Validar que el usuario tenga sesión activa
if (!isset($_SESSION['usuario_id'])) {
    die("Sesión expirada");
}


// Obtenemos el ID del usuario de la sesión para asignar el dueño del pedido
$usuario_id = $_SESSION['usuario_id'];

// 2. Recibir datos del formulario POST
$id             = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$cliente_nombre = mysqli_real_escape_string($conexion, $_POST['cliente_nombre'] ?? '');
$fecha          = mysqli_real_escape_string($conexion, $_POST['fecha'] ?? '');
$estado         = mysqli_real_escape_string($conexion, $_POST['estado'] ?? 'Pendiente');


// Arreglos de productos, cantidades y comentarios (p_id[], cant[] y comentario[] enviados desde el modal)
$productos_ids = isset($_POST['p_id']) ? $_POST['p_id'] : [];
$cantidades    = isset($_POST['cant']) ? $_POST['cant'] : [];
$comentarios   = isset($_POST['comentario']) ? $_POST['comentario'] : [];

if (empty($productos_ids)) {
    die("Error: El pedido debe tener al menos un producto.");
}

// 3. REGLA DE SEGURIDAD: Si es una edición, verificar que no esté cancelado ya en la BD
if (!empty($id)) {
    $id = (int)$id;
    $check_query = "SELECT estado FROM pedidos WHERE id = $id";
    $res_check = mysqli_query($conexion, $check_query);
    $pedido_previo = mysqli_fetch_assoc($res_check);

    if ($pedido_previo && $pedido_previo['estado'] === 'Cancelado') {
        die("Error: Este pedido ya está cancelado y no se puede modificar.");
    }
}

// 4. INICIAR TRANSACCIÓN
mysqli_begin_transaction($conexion);

try {
    if (empty($id)) {
        // --- CASO: NUEVO PEDIDO ---
        
        // Generar Folio automático (OH-0001, OH-0002...)
        $res_f = mysqli_query($conexion, "SELECT COUNT(*) as total FROM pedidos");
        $num_pedidos = mysqli_fetch_assoc($res_f)['total'] + 1;
        $folio = "OH-" . str_pad($num_pedidos, 4, "0", STR_PAD_LEFT);

        // INSERTAR: Incluimos usuario_id para que aparezca en el dashboard del vendedor
        $sql_header = "INSERT INTO pedidos (folio, id_usuario, cliente_nombre, fecha, estado) 
                       VALUES ('$folio', $usuario_id, '$cliente_nombre', '$fecha', '$estado')";
        
        if (!mysqli_query($conexion, $sql_header)) throw new Exception(mysqli_error($conexion));
        
        $pedido_id = mysqli_insert_id($conexion);

    } else {
        // --- CASO: ACTUALIZAR PEDIDO EXISTENTE ---
        $pedido_id = $id;

        $sql_update = "UPDATE pedidos SET 
                        cliente_nombre = '$cliente_nombre', 
                        fecha = '$fecha', 
                        estado = '$estado' 
                       WHERE id = $pedido_id";
        
        if (!mysqli_query($conexion, $sql_update)) throw new Exception(mysqli_error($conexion));

        // Borrar los detalles anteriores para re-insertar la lista actualizada
        mysqli_query($conexion, "DELETE FROM pedido_detalles WHERE pedido_id = $pedido_id");
    }

    // 5. INSERTAR LOS DETALLES
    foreach ($productos_ids as $index => $prod_id) {
        $p_id = (int)$prod_id;
        $cant = (int)$cantidades[$index];
        $coment = mysqli_real_escape_string($conexion, $comentarios[$index]);
        
        $sql_det = "INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, comentario) 
                    VALUES ($pedido_id, $p_id, $cant, '$coment')";
        
        if (!mysqli_query($conexion, $sql_det)) throw new Exception(mysqli_error($conexion));
    }

    // Si llegamos aquí sin errores, confirmamos todos los cambios en la BD
    mysqli_commit($conexion);
    echo "ok";

} catch (Exception $e) {
    // Si algo falló (SQL error, desconexión, etc.), revertimos todo
    mysqli_rollback($conexion);
    echo "Error técnico: " . $e->getMessage();
}
?>