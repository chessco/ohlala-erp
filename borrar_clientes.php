<?php
/**
 * Script de Utilidad: Limpiar todos los clientes
 * 
 * Este script elimina todos los clientes de la base de datos.
 * Dado que los pedidos guardan el nombre del cliente como string (cliente_nombre),
 * no se requiere desvinculación forzada para mantener la integridad de los pedidos.
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
    <title>Limpiar Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #050a14; color: white; font-family: "Poppins", sans-serif; }
        .card { background: #0f172a; border: 1px solid rgba(255,255,255,0.1); }
    </style>
</head>
<body class="p-5">';

echo '<div class="container">';
echo '<h1 class="mb-4"><i class="fa-solid fa-users-slash text-danger me-2"></i> Herramienta de Limpieza de Clientes</h1>';

if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'SI') {
    
    mysqli_begin_transaction($conexion);

    try {
        // 1. Eliminar todos los clientes
        $sql_delete = "DELETE FROM clientes";
        mysqli_query($conexion, $sql_delete);
        $clientes_eliminados = mysqli_affected_rows($conexion);

        mysqli_commit($conexion);

        echo '<div class="alert alert-success mt-4">
                <h4>¡Proceso Completado con Éxito!</h4>
                <ul>
                    <li>Clientes eliminados permanentemente: <strong>' . $clientes_eliminados . '</strong></li>
                </ul>
              </div>';
        
        echo '<a href="clientes.php" class="btn btn-primary mt-3">Volver a Clientes</a>';

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        echo '<div class="alert alert-danger mt-4">
                Error durante el proceso: ' . $e->getMessage() . '
              </div>';
    }

} else {
    // Formulario de confirmación
    echo '<div class="card text-white border-danger mb-3">
            <div class="card-header bg-danger text-white fw-bold">ADVERTENCIA CRÍTICA</div>
            <div class="card-body">
                <p class="card-text">
                    Esta acción <strong>ELIMINARÁ PERMANENTEMENTE</strong> todos los clientes registrados en el sistema.<br>
                    Los pedidos existentes conservarán el nombre del cliente grabado en el momento de la venta, pero no se podrá vincular a registros maestros inexistentes.
                </p>
                <form method="POST">
                    <input type="hidden" name="confirmar" value="SI">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger btn-lg fw-bold">Confirmar y Borrar TODO</button>
                        <a href="clientes.php" class="btn btn-outline-light btn-lg">Cancelar</a>
                    </div>
                </form>
            </div>
          </div>';
}

echo '</div></body></html>';
?>
