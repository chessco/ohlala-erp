<?php
include('conexion.php');
require_once('SimpleXLSX.php');
use shuchkin\SimpleXLSX;

// 1. Configuración para procesos masivos
set_time_limit(0); 
ini_set('memory_limit', '256M');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_productos'])) {
    
    // 2. Intentar abrir el archivo Excel
    if ( $xlsx = SimpleXLSX::parse($_FILES['archivo_productos']['tmp_name']) ) {
        
        $conteo_exito = 0;
        $filas = $xlsx->rows();
        array_shift($filas); // Saltamos la primera fila (encabezados)

        foreach ($filas as $fila) {
            // Regla: Si no hay código (Col A) o Nombre (Col B), ignoramos
            if(empty($fila[0]) || empty($fila[1])) continue; 

            // 3. Limpieza y asignación de datos
            $codigo = mysqli_real_escape_string($conexion, trim($fila[0])); 
            $nombre = mysqli_real_escape_string($conexion, trim($fila[1]));
            $precio = isset($fila[2]) ? (float)$fila[2] : 0;

            // 4. SQL: Insertar o Actualizar
            $sql = "INSERT INTO productos (codigo, nombre, precio) 
                    VALUES ('$codigo', '$nombre', $precio)
                    ON DUPLICATE KEY UPDATE 
                    nombre='$nombre', precio=$precio";
            
            if (mysqli_query($conexion, $sql)) {
                $conteo_exito++;
            }
        }
        // 5. Retorno automático
        echo "<script>alert('Proceso completado: $conteo_exito productos importados/actualizados.'); window.location='productos';</script>";
    } else {
        echo "Error crítico al leer el archivo: " . SimpleXLSX::parseError();
    }
} else {
    header("Location: productos");
}
?>
