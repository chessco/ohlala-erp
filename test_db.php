<?php
// Script de prueba de conexión Ohlala! ERP
$host = "localhost";
$user = "u471794305_ohlala_erp";
$pass = "Pit@ya.2019";
$db   = "u471794305_ohlala_erp";

echo "<h2>Probando conexión a base de datos...</h2>";

$conexion = mysqli_connect($host, $user, $pass, $db);

if (!$conexion) {
    echo "<p style='color:red'>❌ ERROR de conexión: " . mysqli_connect_error() . "</p>";
    echo "<p>Esto confirma que la contraseña <b>$pass</b> no es correcta para el usuario <b>$user</b>.</p>";
} else {
    echo "<p style='color:green'>✅ CONEXIÓN EXITOSA!</p>";
    echo "<p>La contraseña es correcta y la base de datos está lista.</p>";
    mysqli_close($conexion);
}
?>
