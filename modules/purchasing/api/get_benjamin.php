<?php
include(__DIR__ . '/../../../conexion.php');
$res = mysqli_query($conexion, "SELECT nombre_completo, usuario, password FROM usuarios WHERE nombre_completo LIKE '%benjamin%' OR usuario LIKE '%benjamin%'");
while($row = mysqli_fetch_assoc($res)) {
    echo "Nombre: " . $row['nombre_completo'] . " | Usuario: " . $row['usuario'] . " | Password: " . $row['password'] . "\n";
}
