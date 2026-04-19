<?php
// Script de prueba isolado para depurar borrado
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "------------------------------------------------\n";
echo "PRUEBA DE BORRADO DE CLIENTES (BACKEND DIRECTO)\n";
echo "------------------------------------------------\n";

// 1. Probar conexión
echo "1. Conectando a BD... ";
if(!file_exists('conexion.php')) die("ERROR: conexion.php no encontrado.\n");
include 'conexion.php';

if($conexion) {
    echo "OK.\n";
} else {
    die("FALLO: Error de conexión MySQL.\n");
}

// 2. Insertar cliente de prueba
$rand = rand(1000, 9999);
$codigo_test = "TEST_DEL_$rand";
echo "2. Intentando crear cliente de prueba ($codigo_test)... ";

$sql_insert = "INSERT INTO clientes (codigo_cliente, nombre, rfc, correo, telefono, comercial) 
               VALUES ('$codigo_test', 'Cliente De Prueba Para Borrar', 'XAXX010101000', 'test@delete.com', '5551234567', 'Test Commercial')";

if(mysqli_query($conexion, $sql_insert)) {
    $id_creado = mysqli_insert_id($conexion);
    echo "OK. ID generado: $id_creado\n";
} else {
    die("FALLO al crear: " . mysqli_error($conexion) . "\n");
}

echo "3. Verificando que existe... ";
$res = mysqli_query($conexion, "SELECT id, nombre FROM clientes WHERE id = $id_creado");
if(mysqli_num_rows($res) > 0) {
    echo "OK (Encontrado).\n";
} else {
    die("FALLO: No se encuentra el registro recien creado.\n");
}

// 3. Intentar borrar
echo "4. Intentando BORRAR ID $id_creado... ";
$sql_del = "DELETE FROM clientes WHERE id = $id_creado";

if(mysqli_query($conexion, $sql_del)) {
    $afectados = mysqli_affected_rows($conexion);
    if($afectados > 0) {
        echo "EXITO TOTAL. El borrado funciona correctamente a nivel BD.\n";
        echo "Filas afectadas: $afectados\n";
    } else {
        echo "ALERTA: La consulta corrió pero no borró nada (Filas afectadas: 0).\n";
    }
} else {
    echo "FALLO CRITICO SQL: " . mysqli_error($conexion) . "\n";
}
echo "------------------------------------------------\n";
?>
