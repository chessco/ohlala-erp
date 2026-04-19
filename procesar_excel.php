<?php
include('conexion.php');

// 1. ELIMINAR LÍMITE DE TIEMPO: 0 significa "infinito"
set_time_limit(0);

// 2. OPCIONAL: Aumentar memoria si el Excel es muy grande (ej. 256MB)
ini_set('memory_limit', '256M');

if (!file_exists('SimpleXLSX.php')) {
    die("Error: No se encuentra 'SimpleXLSX.php'.");
}

require_once('SimpleXLSX.php');
use shuchkin\SimpleXLSX;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_excel'])) {
    
    if ( $xlsx = SimpleXLSX::parse($_FILES['archivo_excel']['tmp_name']) ) {
        
        $conteo_exito = 0;
        $filas = $xlsx->rows();
        array_shift($filas); // Saltamos encabezados

        foreach ($filas as $fila) {
            if(empty($fila[0])) continue; 

            $codigo = mysqli_real_escape_string($conexion, $fila[0]);
            $nombre = mysqli_real_escape_string($conexion, $fila[1]);
            $precio = floatval($fila[2]);

            // SQL sin la columna stock
            $sql = "INSERT INTO productos (codigo, nombre, precio) 
                    VALUES ('$codigo', '$nombre', '$precio')
                    ON DUPLICATE KEY UPDATE nombre='$nombre', precio='$precio'";
            
            if (mysqli_query($conexion, $sql)) {
                $conteo_exito++;
            }
        }
        echo "<script>alert('¡Proceso terminado! Se cargaron $conteo_exito productos.'); window.location='dashboard.php';</script>";
    } else {
        echo "Error al leer el Excel: " . SimpleXLSX::parseError();
    }
}
?>