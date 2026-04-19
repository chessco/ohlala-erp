<?php
include(__DIR__ . '/../../../../conexion.php');
$table = 'usuarios';
$res = mysqli_query($conexion, "DESCRIBE $table");
if ($res) {
    echo "Columns for $table:\n";
    while($row = mysqli_fetch_assoc($res)) {
        print_r($row);
    }
} else {
    echo "Error describing $table: " . mysqli_error($conexion) . "\n";
}
?>
