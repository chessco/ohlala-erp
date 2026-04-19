<?php
/**
 * Script de Utilidad: Limpiar usuarios excepto Admin
 * 
 * Este script elimina todos los usuarios de la base de datos EXCEPTO el usuario 'admin'.
 * Para evitar errores de integridad referencial (Foreign Keys) y pérdida de datos,
 * reasigna todos los Clientes y Pedidos huérfanos al usuario admin antes de eliminar.
 */

include('conexion.php');
session_start();

// Opcional: Verificar si el usuario logueado es admin para mayor seguridad
// if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
//     die("Acceso denegado. Solo administradores.");
// }

$usuario_objetivo = 'admin';

echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpiar Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white p-5">';

echo '<div class="container">';
echo '<h1 class="mb-4">Herramienta de Limpieza de Usuarios</h1>';

// 1. Verificar si existe el usuario admin
$sql_check = "SELECT id, usuario FROM usuarios WHERE usuario = '$usuario_objetivo' LIMIT 1";
$res_check = mysqli_query($conexion, $sql_check);

if (mysqli_num_rows($res_check) == 0) {
    echo '<div class="alert alert-danger">
            <strong>ERROR CRÍTICO:</strong> No se encontró el usuario "admin". <br>
            No se puede proceder porque no habría a quién asignar los datos huérfanos.
          </div>';
    echo '</body></html>';
    exit;
}

$row_admin = mysqli_fetch_assoc($res_check);
$admin_id = $row_admin['id'];
$admin_user = $row_admin['usuario'];

echo '<div class="alert alert-info">Usuario Admin identificado: <strong>' . $admin_user . ' (ID: ' . $admin_id . ')</strong></div>';

if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'SI') {
    
    mysqli_begin_transaction($conexion);

    try {
        // 2. Reasignar Clientes
        $sql_clientes = "UPDATE clientes SET id_usuario = $admin_id WHERE id_usuario != $admin_id";
        mysqli_query($conexion, $sql_clientes);
        $clientes_afectados = mysqli_affected_rows($conexion);

        // 3. Reasignar Pedidos
        $sql_pedidos = "UPDATE pedidos SET id_usuario = $admin_id WHERE id_usuario != $admin_id";
        mysqli_query($conexion, $sql_pedidos);
        $pedidos_afectados = mysqli_affected_rows($conexion);

        // 4. Eliminar Usuarios (excepto admin)
        $sql_delete = "DELETE FROM usuarios WHERE id != $admin_id";
        mysqli_query($conexion, $sql_delete);
        $usuarios_eliminados = mysqli_affected_rows($conexion);

        mysqli_commit($conexion);

        echo '<div class="alert alert-success mt-4">
                <h4>¡Proceso Completado con Éxito!</h4>
                <ul>
                    <li>Clientes reasignados a admin: <strong>' . $clientes_afectados . '</strong></li>
                    <li>Pedidos reasignados a admin: <strong>' . $pedidos_afectados . '</strong></li>
                    <li>Usuarios eliminados: <strong>' . $usuarios_eliminados . '</strong></li>
                </ul>
              </div>';
        
        echo '<a href="usuarios.php" class="btn btn-primary mt-3">Ir al Panel de Usuarios</a>';

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        echo '<div class="alert alert-danger mt-4">
                Error durante el proceso: ' . $e->getMessage() . '
              </div>';
    }

} else {
    // Formulario de confirmación
    echo '<div class="card bg-secondary text-white border-warning mb-3">
            <div class="card-header bg-warning text-dark fw-bold">ADVERTENCIA</div>
            <div class="card-body">
                <p class="card-text">
                    Esta acción <strong>ELIMINARÁ PERMANENTEMENTE</strong> a todos los usuarios excepto "admin".<br>
                    Los pedidos y clientes asociados a los usuarios eliminados serán <strong>REASIGNADOS</strong> al usuario "admin" para no perder información.
                </p>
                <form method="POST">
                    <input type="hidden" name="confirmar" value="SI">
                    <button type="submit" class="btn btn-danger btn-lg fw-bold">Confirmar y Eliminar Usuarios</button>
                    <a href="index.php" class="btn btn-light btn-lg ms-2">Cancelar</a>
                </form>
            </div>
          </div>';
}

echo '</div></body></html>';
?>
