<?php
include('conexion.php');
session_start();

// 1. Validar sesión por seguridad
if (!isset($_SESSION['autenticado'])) {
    exit('No autorizado');
}

// Obtenemos datos del usuario actual
$usuario_id_sesion = $_SESSION['usuario_id'];
$rol_actual        = isset($_SESSION['usuario_rol']) ? $_SESSION['usuario_rol'] : '';
$esAdmin           = ($rol_actual === 'admin'); 

if (isset($_POST['term'])) {
    $term = mysqli_real_escape_string($conexion, $_POST['term']);

    // 2. REGLA DE CARTERA:
    // Si es admin, no hay filtro (busca en todos).
    // Si es vendedor, solo busca clientes donde usuario_id coincida con su sesión.
    $filtro_cartera = ($esAdmin) ? "" : " AND id_usuario = '$usuario_id_sesion'";

    // 3. Ejecutar consulta con filtro de búsqueda y filtro de cartera
    $query = "SELECT id, codigo_cliente, nombre, rfc 
              FROM clientes 
              WHERE (nombre LIKE '%$term%' 
              OR codigo_cliente LIKE '%$term%' 
              OR rfc LIKE '%$term%') 
              $filtro_cartera 
              LIMIT 10";

    $res = mysqli_query($conexion, $query);

    if ($res && mysqli_num_rows($res) > 0) {
        while ($c = mysqli_fetch_assoc($res)) {
            $id = $c['id'];
            $codigo = htmlspecialchars($c['codigo_cliente'] ?? 'S/C');
            $nombre = htmlspecialchars($c['nombre']);
            
            // Generamos el HTML de cada fila del buscador
            // Determinamos la acción JS según el origen
            $origen = isset($_POST['origen']) ? $_POST['origen'] : '';
            if ($origen === 'cartera') {
                $onClick = "agregarACartera($id, \"$codigo\", \"$nombre\")";
            } else {
                // Por defecto para dashboard (Nuevo Pedido)
                $onClick = "seleccionarCliente($id, \"$nombre\")";
            }

            echo "
            <div class='cliente-item d-flex justify-content-between align-items-center p-3 border-bottom'>
                <div class='d-flex flex-column'>
                    <span class='fw-bold text-white small'>$nombre</span>
                    <span class='text-info' style='font-size: 0.75rem;'>Código: $codigo</span>
                </div>
                <button type='button' class='btn btn-sm btn-primary px-3 shadow-sm' 
                    onclick='$onClick'>
                    <i class='fa-solid fa-plus-circle me-1'></i> Seleccionar
                </button>
            </div>";
        }
    } else {
        echo "<div class='p-4 text-center text-secondary small'>
                <i class='fa-solid fa-face-frown me-2'></i> No se encontraron clientes en tu cartera
              </div>";
    }
}
?>