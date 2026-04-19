<?php
include('conexion.php');
require_once('SimpleXLSX.php');
use shuchkin\SimpleXLSX;

// 1. Configuración para procesos masivos
set_time_limit(0); 
ini_set('memory_limit', '256M');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_clientes'])) {
    
    // 2. Intentar abrir el archivo Excel
    if ( $xlsx = SimpleXLSX::parse($_FILES['archivo_clientes']['tmp_name']) ) {
        
        $conteo_exito = 0;
        $filas = $xlsx->rows();
        array_shift($filas); // Saltamos la primera fila (encabezados)

        foreach ($filas as $fila) {
            // Regla: Si no hay nombre (Columna B), ignoramos la fila
            if(empty($fila[1])) continue; 

            // 3. Limpieza y asignación de datos (Basado en tu tabla SQL)
            $codigo_cli = mysqli_real_escape_string($conexion, substr($fila[0], 0, 10)); // Máx 10 carac.
            $nombre     = mysqli_real_escape_string($conexion, $fila[1]);
            $rfc        = mysqli_real_escape_string($conexion, $fila[2] ?? '');
            $correo     = mysqli_real_escape_string($conexion, $fila[3] ?? '');
            $telefono   = mysqli_real_escape_string($conexion, $fila[4] ?? '');
            $comercial  = mysqli_real_escape_string($conexion, $fila[5] ?? '');
            $cat1       = mysqli_real_escape_string($conexion, $fila[6] ?? '');
            $cat2       = mysqli_real_escape_string($conexion, $fila[7] ?? '');

            // 4. SQL Directo: Si el código existe, actualiza; si no, inserta.
            $sql = "INSERT INTO clientes (codigo_cliente, nombre, rfc, correo, telefono, comercial, categoria1, categoria2) 
                    VALUES ('$codigo_cli', '$nombre', '$rfc', '$correo', '$telefono', '$comercial', '$cat1', '$cat2')
                    ON DUPLICATE KEY UPDATE 
                    nombre='$nombre', rfc='$rfc', correo='$correo', telefono='$telefono', comercial='$comercial', 
                    categoria1='$cat1', categoria2='$cat2'";
            
            if (mysqli_query($conexion, $sql)) {
                $conteo_exito++;
            }
        }
        // 5. Retorno automático al directorio con mensaje de éxito
        echo "<script>alert('Proceso completado: $conteo_exito clientes actualizados.'); window.location='clientes.php';</script>";
    } else {
        echo "Error crítico al leer el archivo: " . SimpleXLSX::parseError();
    }
}
?>