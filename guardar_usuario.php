<?php
include('conexion.php');
session_start();

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    die("No autorizado");
}

// CASO ELIMINAR (Se queda igual...)
if(isset($_POST['eliminar_id'])) {
    $id = intval($_POST['eliminar_id']);
    if($id == $_SESSION['usuario_id']) die("No puedes eliminarte a ti mismo");
    mysqli_query($conexion, "DELETE FROM usuarios WHERE id = $id");
    echo "ok";
    exit();
}

// CASO AGREGAR (Con correo)
if(isset($_POST['usuario'])) {
    $nombre   = mysqli_real_escape_string($conexion, $_POST['nombre_completo']);
    $correo   = mysqli_real_escape_string($conexion, $_POST['correo']); // Recibir correo
    $usuario  = mysqli_real_escape_string($conexion, $_POST['usuario']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $rol      = mysqli_real_escape_string($conexion, $_POST['rol']);

    $check = mysqli_query($conexion, "SELECT id FROM usuarios WHERE usuario = '$usuario'");
    if(mysqli_num_rows($check) > 0) {
        die("Este nombre de usuario ya existe.");
    }

    // Insertar incluyendo el campo correo
    $sql = "INSERT INTO usuarios (nombre_completo, correo, usuario, password, rol) 
            VALUES ('$nombre', '$correo', '$usuario', '$password', '$rol')";

    if(mysqli_query($conexion, $sql)) {
        echo "ok";
    } else {
        echo "Error: " . mysqli_error($conexion);
    }
}
?>