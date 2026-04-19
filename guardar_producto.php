<?php
include('conexion.php');
session_start();

if (!isset($_SESSION['autenticado']) || $_SESSION['usuario_rol'] !== 'admin') {
    die("Acceso denegado");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Casos: Eliminar
    if (isset($_POST['eliminar_id'])) {
        $id = (int)$_POST['eliminar_id'];

        // Borrar imagen antes de eliminar registro
        $res_old = mysqli_query($conexion, "SELECT imagen FROM productos WHERE id=$id");
        $old = mysqli_fetch_assoc($res_old);
        if ($old && !empty($old['imagen'])) {
            @unlink("uploads/productos/" . $old['imagen']);
        }

        $sql = "DELETE FROM productos WHERE id = $id";
        if (mysqli_query($conexion, $sql)) echo "ok";
        else echo "Error al eliminar: " . mysqli_error($conexion);
        exit();
    }

    // Casos: Crear/Editar
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $codigo = mysqli_real_escape_string($conexion, $_POST['codigo']);
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $precio = (float)$_POST['precio'];

    if (empty($codigo) || empty($nombre) || $_POST['precio'] === "") {
        die("Todos los campos son obligatorios.");
    }

    // Manejo de Imagen
    $nombre_imagen = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $img_ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $nombre_imagen = time() . "_" . bin2hex(random_bytes(4)) . "." . $img_ext;
        $ruta_destino = "uploads/productos/" . $nombre_imagen;
        
        if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
            die("Error al subir la imagen.");
        }
    }

    if (!empty($id)) {
        // Actualizar
        $sql_extra = "";
        if ($nombre_imagen) {
            // Borrar imagen anterior si existe
            $res_old = mysqli_query($conexion, "SELECT imagen FROM productos WHERE id=$id");
            $old = mysqli_fetch_assoc($res_old);
            if ($old && !empty($old['imagen'])) {
                @unlink("uploads/productos/" . $old['imagen']);
            }
            $sql_extra = ", imagen='$nombre_imagen'";
        }
        $sql = "UPDATE productos SET codigo='$codigo', nombre='$nombre', precio=$precio $sql_extra WHERE id=$id";
    } else {
        // Crear
        $sql = "INSERT INTO productos (codigo, nombre, precio, imagen) VALUES ('$codigo', '$nombre', $precio, '$nombre_imagen')";
    }

    if (mysqli_query($conexion, $sql)) {
        echo "ok";
    } else {
        echo "Error en base de datos: " . mysqli_error($conexion);
    }
}
?>
