<?php
include(__DIR__ . '/../../../conexion.php');

$password = "Pitaya.123";
$hash = password_hash($password, PASSWORD_BCRYPT);

$users = [
    ['nombre' => 'Supervisor Ohlala', 'usuario' => 'supervisor', 'correo' => 'supervisor@pitayacode.io', 'rol' => 'supervisor'],
    ['nombre' => 'Gerente Ohlala', 'usuario' => 'gerente', 'correo' => 'gerente@pitayacode.io', 'rol' => 'gerente'],
    ['nombre' => 'Director Ohlala', 'usuario' => 'director', 'correo' => 'director@pitayacode.io', 'rol' => 'director']
];

echo "--- CREATING USERS ---\n";

foreach ($users as $u) {
    // Check if already exists
    $check = mysqli_query($conexion, "SELECT id FROM usuarios WHERE usuario = '{$u['usuario']}'");
    if (mysqli_num_rows($check) > 0) {
        // Update existing for testing
        $sql = "UPDATE usuarios SET password = '$hash', correo = '{$u['correo']}', nombre_completo = '{$u['nombre']}' WHERE usuario = '{$u['usuario']}'";
        echo "Updating USER: {$u['usuario']}...\n";
    } else {
        // Insert new
        $sql = "INSERT INTO usuarios (nombre_completo, usuario, correo, password, rol, acceso_permitido, fecha_registro) 
                VALUES ('{$u['nombre']}', '{$u['usuario']}', '{$u['correo']}', '$hash', '{$u['rol']}', 1, NOW())";
        echo "Creating USER: {$u['usuario']}...\n";
    }
    
    if (mysqli_query($conexion, $sql)) {
        echo "SUCCESS: {$u['usuario']} ready.\n";
    } else {
        echo "ERROR: " . mysqli_error($conexion) . "\n";
    }
}
