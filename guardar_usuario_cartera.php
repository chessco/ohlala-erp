<?php
include('conexion.php');
session_start();

// 1. SEGURIDAD: Solo admin
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    exit("Acceso denegado");
}

// --- LÓGICA PARA ELIMINAR ---
if (isset($_POST['eliminar_id'])) {
    $id = (int)$_POST['eliminar_id'];
    
    // Limpiar la referencia en clientes antes de borrar al usuario
    mysqli_query($conexion, "UPDATE clientes SET id_usuario = NULL WHERE id_usuario = $id");
    
    $query = "DELETE FROM usuarios WHERE id = $id";
    if (mysqli_query($conexion, $query)) {
        echo "ok";
    } else {
        echo "Error: " . mysqli_error($conexion);
    }
    exit;
}

// --- LÓGICA PARA GUARDAR / ACTUALIZAR ---
if (isset($_POST['usuario'])) {
    $id              = (int)$_POST['id'];
    $nombre_completo = mysqli_real_escape_string($conexion, $_POST['nombre_completo']);
    $correo          = mysqli_real_escape_string($conexion, $_POST['correo']);
    $telefono        = mysqli_real_escape_string($conexion, $_POST['telefono']);
    $usuario_login   = mysqli_real_escape_string($conexion, $_POST['usuario']);
    $rol             = mysqli_real_escape_string($conexion, $_POST['rol']);
    $pass            = $_POST['password'];
    
    $clientes_cartera = isset($_POST['clientes_cartera']) ? $_POST['clientes_cartera'] : [];

    if ($id === 0) {
        // --- NUEVO USUARIO ---
        $password_hash = password_hash($pass, PASSWORD_DEFAULT);
        $query = "INSERT INTO usuarios (nombre_completo, correo, telefono, usuario, password, rol) 
                  VALUES ('$nombre_completo', '$correo', '$telefono', '$usuario_login', '$password_hash', '$rol')";
        
        if (mysqli_query($conexion, $query)) {
            $usuario_id_final = mysqli_insert_id($conexion);
        } else {
            die("Error al insertar: " . mysqli_error($conexion));
        }
    } else {
        // --- ACTUALIZAR EXISTENTE ---
        $usuario_id_final = $id;
        $query = "UPDATE usuarios SET 
                  nombre_completo = '$nombre_completo', 
                  correo = '$correo', 
                  telefono = '$telefono', 
                  usuario = '$usuario_login', 
                  rol = '$rol' 
                  WHERE id = $usuario_id_final";
        
        mysqli_query($conexion, $query);

        // Actualizar password solo si se escribió algo
        if (!empty($pass)) {
            $password_hash = password_hash($pass, PASSWORD_DEFAULT);
            mysqli_query($conexion, "UPDATE usuarios SET password = '$password_hash' WHERE id = $usuario_id_final");
        }
    }

    // --- ACTUALIZAR CARTERA DE CLIENTES ---
    // 1. Limpiar asignaciones previas del usuario (usando id_usuario)
    mysqli_query($conexion, "UPDATE clientes SET id_usuario = NULL WHERE id_usuario = $usuario_id_final");

    // 2. Asignar los nuevos clientes de la tabla
    if (!empty($clientes_cartera)) {
        foreach ($clientes_cartera as $cliente_id) {
            $c_id = (int)$cliente_id;
            mysqli_query($conexion, "UPDATE clientes SET id_usuario = $usuario_id_final WHERE id = $c_id");
        }
    }

    echo "ok";
}
?>