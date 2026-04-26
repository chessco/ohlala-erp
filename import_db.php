<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!file_exists('conexion.php')) {
    die('Error: No se encontro conexion.php en el servidor.');
}

include('conexion.php');

if (!isset($conexion)) {
    die('Error: La variable $conexion no esta definida en conexion.php');
}

if (mysqli_connect_errno()) {
    die('Error de conexion: ' . mysqli_connect_error());
}

$sql = file_get_contents('local_db_sync.sql');
if (!$sql) {
    die('Error: No se pudo leer el archivo local_db_sync.sql');
}

// Ejecutar multiples consultas
if (mysqli_multi_query($conexion, $sql)) {
    do {
        if ($result = mysqli_store_result($conexion)) {
            mysqli_free_result($result);
        }
    } while (mysqli_more_results($conexion) && mysqli_next_result($conexion));
    
    if (mysqli_errno($conexion)) {
        echo 'Error en consulta: ' . mysqli_error($conexion);
    } else {
        echo 'OK';
    }
} else {
    echo 'Error inicial: ' . mysqli_error($conexion);
}
unlink('import_db.php');
unlink('local_db_sync.sql');
?>
