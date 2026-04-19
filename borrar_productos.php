<?php
/**
 * Script de Utilidad: Limpiar todos los productos
 * 
 * Este script elimina todos los productos de la base de datos.
 * Para evitar errores de integridad referencial (Foreign Keys),
 * desvincula los productos de los detalles de los pedidos poniendo producto_id en NULL.
 */

include('conexion.php');
session_start();

// Control de acceso: Solo Administradores
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    die("Acceso denegado. Solo administradores.");
}

echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpiar Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #050a14; color: white; font-family: "Poppins", sans-serif; }
        .card { background: #0f172a; border: 1px solid rgba(255,255,255,0.1); }
    </style>
</head>
<body class="p-5">';

echo '<div class="container">';
echo '<h1 class="mb-4"><i class="fa-solid fa-box-archive text-primary me-2"></i> Herramienta de Limpieza de Productos</h1>';

if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'SI') {
    
    mysqli_begin_transaction($conexion);

    try {
        // 1. Desvincular productos de los detalles de los pedidos
        // Esto evita errores de llave foránea si el ON DELETE NO es CASCADE
        $sql_detalles = "UPDATE pedido_detalles SET producto_id = NULL";
        mysqli_query($conexion, $sql_detalles);
        $detalles_afectados = mysqli_affected_rows($conexion);

        // 2. Eliminar todos los productos
        $sql_delete = "DELETE FROM productos";
        mysqli_query($conexion, $sql_delete);
        $productos_eliminados = mysqli_affected_rows($conexion);

        mysqli_commit($conexion);

        echo '<div class="alert alert-success mt-4">
                <h4>¡Proceso Completado con Éxito!</h4>
                <ul>
                    <li>Detalles de pedidos desvinculados: <strong>' . $detalles_afectados . '</strong></li>
                    <li>Productos eliminados permanentemente: <strong>' . $productos_eliminados . '</strong></li>
                </ul>
              </div>';
        
        echo '<a href="productos.php" class="btn btn-primary mt-3">Volver a Productos</a>';

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        echo '<div class="alert alert-danger mt-4">
                Error durante el proceso: ' . $e->getMessage() . '
              </div>';
    }

} else {
    // Formulario de confirmación
    echo '<div class="card text-white border-warning mb-3">
            <div class="card-header bg-warning text-dark fw-bold">ADVERTENCIA CRÍTICA</div>
            <div class="card-body">
                <p class="card-text">
                    Esta acción <strong>ELIMINARÁ PERMANENTEMENTE</strong> todos los productos registrados en el sistema.<br>
                    Los detalles de los pedidos ya existentes conservarán el nombre y precio original, pero el vínculo al producto maestro se perderá (quedará en blanco).
                </p>
                <form method="POST">
                    <input type="hidden" name="confirmar" value="SI">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger btn-lg fw-bold">Confirmar y Borrar TODO</button>
                        <a href="productos.php" class="btn btn-outline-light btn-lg">Cancelar</a>
                    </div>
                </form>
            </div>
          </div>';
}

echo '</div></body></html>';
?>
