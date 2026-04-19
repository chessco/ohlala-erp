<?php
include('conexion.php');

$sql = "ALTER TABLE productos ADD COLUMN imagen VARCHAR(255) NULL AFTER precio";

if (mysqli_query($conexion, $sql)) {
    echo "<h1>Éxito</h1>";
    echo "<p>La columna 'imagen' ha sido agregada a la tabla 'productos' correctamente.</p>";
} else {
    $error = mysqli_error($conexion);
    if (strpos($error, 'Duplicate column name') !== false) {
        echo "<h1>Información</h1>";
        echo "<p>La columna 'imagen' ya existe en la tabla 'productos'.</p>";
    } else {
        echo "<h1>Error</h1>";
        echo "<p>No se pudo agregar la columna: " . $error . "</p>";
    }
}

echo '<br><a href="productos.php">Volver a Productos</a>';
?>
