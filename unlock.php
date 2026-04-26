<?php
include('conexion.php');

if (!$conexion) die("Error de conexion");

echo "<h2>Limpiando bloqueos y configurando acceso...</h2>";

// 1. Limpiar intentos fallidos de todos los usuarios
$reset_intentos = mysqli_query($conexion, "UPDATE usuarios SET intentos_fallidos = 0, acceso_permitido = 1");
if ($reset_intentos) {
    echo "<p>✅ Bloqueos de IP y usuarios limpiados.</p>";
}

// 2. Configurar password admin123
$new_pass = password_hash('admin123', PASSWORD_DEFAULT);
$update = mysqli_query($conexion, "UPDATE usuarios SET password = '$new_pass' WHERE usuario = 'admin'");

if ($update) {
    echo "<p style='color:green'>✅ Usuario 'admin' listo con password: <b>admin123</b></p>";
}

echo "<p><b>Ya puedes entrar al ERP:</b> <a href='/'>Ir al Login</a></p>";
?>
