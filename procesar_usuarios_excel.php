<?php
include('conexion.php');
require_once('SimpleXLSX.php');
use shuchkin\SimpleXLSX;

set_time_limit(0); 
ini_set('memory_limit', '256M');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_usuarios'])) {
    
    if ( $xlsx = SimpleXLSX::parse($_FILES['archivo_usuarios']['tmp_name']) ) {
        
        $conteo_exito = 0;
        $filas = $xlsx->rows();
        array_shift($filas); // Saltamos encabezados

        foreach ($filas as $fila) {
            // Validar campos mínimos: Nombre(A), Usuario(D), Password(E)
            if(empty($fila[0]) || empty($fila[3]) || empty($fila[4])) continue; 

            $nombre = mysqli_real_escape_string($conexion, trim($fila[0]));
            $correo = mysqli_real_escape_string($conexion, trim($fila[1]));
            $telefono = mysqli_real_escape_string($conexion, trim($fila[2]));
            $usuario = mysqli_real_escape_string($conexion, trim($fila[3]));
            $password_raw = trim($fila[4]);
            $rol = mysqli_real_escape_string($conexion, strtolower(trim($fila[5])));

            // Validar rol (si está vacío, default a vendedor)
            if(empty($rol)) $rol = 'vendedor';

            // Hashing de contraseña
            $password_hash = password_hash($password_raw, PASSWORD_DEFAULT);

            // Insertar o Actualizar (si el usuario ya existe, actualizamos datos básicos pero NO el password para evitar reseteos accidentales masivos a menos que sea explícito, aunque en este caso simple actualizaremos todo si el usuario coincide)
            // Mejor estrategia: Si el usuario existe, actualizamos todo INCLUYENDO password (asumimos que el admin quiere resetear/importar estado deseado).
            
            $sql = "INSERT INTO usuarios (nombre_completo, correo, telefono, usuario, password, rol) 
                    VALUES ('$nombre', '$correo', '$telefono', '$usuario', '$password_hash', '$rol')
                    ON DUPLICATE KEY UPDATE 
                    nombre_completo='$nombre', correo='$correo', telefono='$telefono', password='$password_hash', rol='$rol'";
            
            if (mysqli_query($conexion, $sql)) {
                $conteo_exito++;
            }
        }
        echo "<script>alert('Proceso completado: $conteo_exito usuarios importados/actualizados.'); window.location='usuarios.php';</script>";
    } else {
        echo "Error crítico al leer el archivo: " . SimpleXLSX::parseError();
    }
} else {
    header("Location: usuarios.php");
}
?>
