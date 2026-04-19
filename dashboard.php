<?php
/**
 * PROYECTO: PEDIDOS OHLALA V1.9
 * DASHBOARD PROFESIONAL - MODO OSCURO
 */
include('conexion.php');
session_start();

// 1.5 VERSIÓN DEL DASHBOARD (ROUTER)
if (isset($DASHBOARD_VERSION)) {
    if ($DASHBOARD_VERSION == 2) { include('dashboardv2.php'); exit(); }
    if ($DASHBOARD_VERSION == 3) { include('dashboardv3.php'); exit(); }
}

// 1. CONTROL DE ACCESO (URL LIMPIA)
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header("Location: index"); // Sin .php
    exit();
}

// 1.5 VERSIÓN DEL DASHBOARD (ROUTER)
if (isset($DASHBOARD_VERSION) && $DASHBOARD_VERSION == 2) {
    include('dashboardv2.php');
    exit();
}

// --- VARIABLES DE SESIÓN ---
$usuario_id_sesion = $_SESSION['usuario_id'];
$nombre_vendedor   = isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : 'Usuario';
$rol_actual        = isset($_SESSION['usuario_rol']) ? $_SESSION['usuario_rol'] : '';
$esAdmin           = ($rol_actual === 'admin'); 

// 2. ESTADÍSTICAS FILTRADAS
$hoy_db = date('Y-m-d');
$filtro_usuario = ($esAdmin) ? "" : " AND id_usuario = '$usuario_id_sesion'";

$res_pedidos = mysqli_query($conexion, "SELECT COUNT(*) as total FROM pedidos WHERE 1=1 $filtro_usuario");
$total_pedidos = mysqli_fetch_assoc($res_pedidos)['total'] ?? 0;

// 3. OBTENER CLIENTE ASOCIADO (Si existe uno)
$res_mi_cliente = mysqli_query($conexion, "SELECT nombre FROM clientes WHERE id_usuario = '$usuario_id_sesion' LIMIT 1");
$mi_cliente_asociado = ($row_c = mysqli_fetch_assoc($res_mi_cliente)) ? $row_c['nombre'] : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Ohlala V1.9 - Dashboard Profesional</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        :root[data-theme="dark"] { --bg-deep: #050a14; --card-blue: #0f172a; --accent: #2563eb; --text-silver: #94a3b8; --text-main: #ffffff; --border-color: rgba(255,255,255,0.05); }
        :root[data-theme="light"] { --bg-deep: #f8fafc; --card-blue: #ffffff; --accent: #2563eb; --text-silver: #64748b; --text-main: #1e293b; --border-color: rgba(0,0,0,0.1); }
        :root[data-theme="custom"] { --bg-deep: #0f172a; --card-blue: #1e293b; --accent: #8b5cf6; --text-silver: #c084fc; --text-main: #f3f4f6; --border-color: rgba(139, 92, 246, 0.2); }
        :root[data-theme="ohlala"] { --bg-deep: #F7F5EB; --card-blue: #FFFFFF; --accent: #BD9A5F; --text-silver: #7a828a; --text-main: #071c1f; --border-color: rgba(189, 154, 95, 0.3); }

        body { background-color: var(--bg-deep); color: var(--text-main); font-family: 'Poppins', sans-serif; overflow-x: hidden; transition: background-color 0.3s, color 0.3s; }
        
        /* Navbar */
        .navbar-custom { background-color: var(--card-blue); border-bottom: 1px solid var(--border-color); padding: 1rem; }
        
        /* Cards */
        .stat-card { background: var(--card-blue); border-radius: 15px; padding: 1.2rem; border: 1px solid var(--border-color); transition: 0.3s; color: var(--text-main); }
        .action-card:hover { transform: translateY(-3px); border-color: var(--accent); background: rgba(255,255,255,0.02); }
        .card-main { background: var(--card-blue); border-radius: 20px; padding: 20px; border: none; color: var(--text-main); }
        
        /* Tablas y Formularios */
        .table { color: var(--text-main) !important; border-color: var(--border-color); }
        .modal-content { background-color: var(--card-blue); color: var(--text-main); border: 1px solid var(--border-color); }
        .form-control, .form-select { background-color: rgba(0,0,0,0.1); border: 1px solid var(--border-color); color: var(--text-main); }
        [data-theme="dark"] .form-control, [data-theme="dark"] .form-select { background-color: rgba(0,0,0,0.3); }
        .form-control:focus { background-color: rgba(0,0,0,0.2); color: var(--text-main); border-color: var(--accent); }
        
        /* Resultados de búsqueda AJAX */
        .resultados-ajax { 
            z-index: 1100; 
            max-height: 250px; 
            overflow-y: auto; 
            position: absolute; 
            width: 100%; 
            border: 1px solid var(--border-color); 
            background: var(--card-blue); 
            border-radius: 0 0 8px 8px; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5); 
            display: none;
        }
        .list-group-item { background: transparent; color: var(--text-main); border-color: var(--border-color); }
        .list-group-item:hover { background-color: var(--accent); cursor: pointer; color: white; }
        .text-silver { color: var(--text-silver) !important; }

        /* Estilos específicos para temas Claros (Light / Ohlala) */
        [data-theme="light"] .table-dark, [data-theme="ohlala"] .table-dark { background-color: var(--card-blue); color: var(--text-main) !important; }
        [data-theme="light"] .list-group-item, [data-theme="ohlala"] .list-group-item { color: var(--text-main); }
        [data-theme="light"] .btn-close, [data-theme="ohlala"] .btn-close { filter: invert(1); }
        [data-theme="ohlala"] .navbar-custom h4 { color: #071c1f !important; }

        /* Visibilidad para temas claros */
        [data-theme="light"] .text-white, [data-theme="ohlala"] .text-white { color: var(--text-main) !important; }
        [data-theme="light"] .text-silver, [data-theme="ohlala"] .text-silver { color: var(--text-silver) !important; }
        [data-theme="light"] .stat-card, [data-theme="ohlala"] .stat-card { color: var(--text-main) !important; }
        
        /* Contraste para colores de alerta/aviso en fondo claro */
        [data-theme="light"] .text-warning, [data-theme="ohlala"] .text-warning { color: #856404 !important; }
        [data-theme="light"] .text-success, [data-theme="ohlala"] .text-success { color: #1a6f3e !important; }
        [data-theme="light"] .text-danger, [data-theme="ohlala"] .text-danger { color: #b02a37 !important; }
        [data-theme="light"] .text-info, [data-theme="ohlala"] .text-info { color: #087990 !important; }
 Broadway
 Broadway
    </style>
</head>
<body>

<nav class="navbar-custom d-flex justify-content-between align-items-center mb-4 shadow">
    <h4 class="m-0 text-white">Ohlala <span style="color: var(--accent);">Pedidos</span> <small class="ms-1" style="font-size: 0.65rem; opacity: 0.6;">v2.1</small></h4>
    <div class="d-flex align-items-center">
        <div class="dropdown me-3">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle border-0" type="button" data-bs-toggle="dropdown">
                <i class="fa-solid fa-palette me-1"></i> <span class="d-none d-md-inline">Tema</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-dark shadow">
                <li><a class="dropdown-item" href="javascript:void(0)" onclick="setTheme('dark')"><i class="fa-solid fa-moon me-2"></i> Oscuro</a></li>
                <li><a class="dropdown-item" href="javascript:void(0)" onclick="setTheme('light')"><i class="fa-solid fa-sun me-2"></i> Claro</a></li>
                <li><a class="dropdown-item" href="javascript:void(0)" onclick="setTheme('custom')"><i class="fa-solid fa-wand-magic-sparkles me-2"></i> Custom</a></li>
                <li><a class="dropdown-item" href="javascript:void(0)" onclick="setTheme('ohlala')"><i class="fa-solid fa-star me-2" style="color: #BD9A5F;"></i> Ohlala</a></li>
            </ul>
        </div>
        <span class="text-silver me-3 small d-none d-md-inline">Bienvenido, <b><?php echo $nombre_vendedor; ?></b></span>
        <a href="logout" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-power-off"></i> Salir</a>
    </div>
</nav>

<div class="container-fluid px-3 px-md-4">
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="stat-card shadow-sm h-100 border-start border-primary border-4">
                <div class="text-silver small"><?php echo $esAdmin ? 'Pedidos Global' : 'Mis Pedidos'; ?></div>
                <h3 class="m-0 fw-bold"><?php echo $total_pedidos; ?></h3>
            </div>
        </div>
        
        <?php if($esAdmin): ?>
        <div class="col-6 col-md-2">
            <a href="clientes.php" class="stat-card action-card shadow-sm h-100 d-block text-decoration-none text-white">
                <div class="text-silver small">Directorio</div>
                <h5 class="m-0 fw-bold text-success"><i class="fa-solid fa-users me-2"></i>Clientes</h5>
            </a>
        </div>
        <div class="col-6 col-md-2">
            <a href="productos" class="stat-card action-card shadow-sm h-100 d-block text-decoration-none text-white" style="border-left: 4px solid #f43f5e;">
                <div class="text-danger small text-truncate">Catálogo</div>
                <h5 class="m-0 fw-bold"><i class="fa-solid fa-cake-candles me-2"></i>Productos</h5>
            </a>
        </div>
        <div class="col-6 col-md-2">
            <a href="usuarios.php" class="stat-card action-card shadow-sm h-100 d-block text-decoration-none text-white" style="border-left: 4px solid #f59e0b;">
                <div class="text-warning small text-truncate">Sistema</div>
                <h5 class="m-0 fw-bold"><i class="fa-solid fa-user-gear me-2"></i>Usuarios</h5>
            </a>
        </div>
        <?php 
        // 3. VERIFICAR PERMISO CARGA MASIVA
        $q_em = mysqli_query($conexion, "SELECT correo FROM usuarios WHERE id = '$usuario_id_sesion'");
        $r_em = mysqli_fetch_assoc($q_em);
        $mi_email = $r_em['correo'] ?? '';
        $admins_carga = ['admin@pitayacode.io', 'chessco@pitayacode.io'];
        
        if(in_array($mi_email, $admins_carga)): 
        ?>
        <div class="col-6 col-md-2">
            <a href="filexxml2" class="stat-card action-card shadow-sm h-100 d-block text-decoration-none text-white">
                <div class="text-info small text-truncate">Carga Masiva</div>
                <h5 class="m-0 fw-bold"><i class="fa-solid fa-file-xml me-2"></i>XML CFDI</h5>
            </a>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="card-main shadow-lg mb-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <h5 class="mb-0 text-silver"><i class="fa-solid fa-list-check me-2 text-primary"></i>Gestión de Pedidos</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-info" onclick="duplicarUltimo()"><i class="fa-solid fa-copy me-1"></i> Duplicar</button>
                <button class="btn btn-sm btn-primary shadow px-3" onclick="prepararNuevoPedido()"><i class="fa-solid fa-plus-circle me-1"></i> NUEVO PEDIDO</button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaPedidos" class="table table-dark table-hover w-100 align-middle">
                <thead>
                    <tr class="text-silver small">
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $filtro_tabla = ($esAdmin) ? "" : " WHERE id_usuario = '$usuario_id_sesion'";
                    $sql = "SELECT * FROM pedidos $filtro_tabla ORDER BY id DESC";
                    $res = mysqli_query($conexion, $sql);

                    if($res):
                        while($row = mysqli_fetch_assoc($res)):
                            $badge = 'bg-secondary';
                            if ($row['estado'] == 'Cancelado') $badge = 'bg-danger';
                            if ($row['estado'] == 'Pendiente') $badge = 'bg-warning text-dark';
                            if ($row['estado'] == 'Preparando') $badge = 'bg-info text-dark';
                            if ($row['estado'] == 'Completado') $badge = 'bg-success';
                            if ($row['estado'] == 'Incompleto') $badge = 'bg-primary'; // Using primary for Incompleto
                    ?>
                    <tr>
                        <td class="fw-bold text-primary"><?php echo $row['folio']; ?></td>
                        <td class="small"><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                        <td class="text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($row['cliente_nombre']); ?></td>
                        <td><span class="badge <?php echo $badge; ?> px-2"><?php echo $row['estado']; ?></span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-warning border-0" onclick="editarPedido(<?php echo $row['id']; ?>, '<?php echo $row['estado']; ?>')">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <?php if($esAdmin): ?>
                            <button class="btn btn-sm btn-outline-danger border-0 ms-1" onclick="eliminarPedido(<?php echo $row['id']; ?>, '<?php echo $row['folio']; ?>')">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="tituloModal">Detalle de Pedido</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formPedido">
                    <input type="hidden" id="pedido_id" name="id">
                    
                    <div class="row mb-3 g-2">
                        <div class="col-12 col-md-5 position-relative">
                            <label class="form-label text-silver small">Cliente</label>
                            <input type="text" id="cliente_nombre" name="cliente_nombre" class="form-control" 
                                   onkeyup="buscarClienteCartera(this.value)" placeholder="Buscar cliente..." autocomplete="off" required>
                            <div id="resultadosCliente" class="resultados-ajax list-group"></div>
                        </div>

                        <div class="col-6 col-md-4">
                            <label class="form-label text-silver small">Fecha</label>
                            <input type="date" id="fecha_pedido" name="fecha" class="form-control" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label text-silver small">Estado</label>
                            <select id="estado" name="estado" class="form-select">
                                <option value="Pendiente">Pendiente</option>
                                <option value="Preparando">Preparando</option>
                                <option value="Completado">Completado</option>
                                <option value="Incompleto">Incompleto</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 align-items-end mb-4 p-3 shadow-sm border border-secondary border-opacity-25" style="background: rgba(255,255,255,0.02); border-radius: 12px;">
                        <div class="col-12 col-md-4 position-relative">
                            <label class="form-label text-silver small">Seleccionar Producto</label>
                            <input type="text" class="form-control" id="inputBuscarProd" onkeyup="buscarProducto(this.value)" placeholder="Escribe nombre del postre...">
                            <div id="resultadosBusqueda" class="resultados-ajax list-group"></div>
                        </div>
                        <div class="col-4 col-md-2">
                            <label class="form-label text-silver small">Cant.</label>
                            <input type="number" id="temp_cantidad" class="form-control text-center" value="1" min="1">
                        </div>
                        <div class="col-8 col-md-3">
                            <label class="form-label text-silver small">Comentario</label>
                            <input type="text" id="temp_comentario" class="form-control" placeholder="Opcional...">
                        </div>
                        <div class="col-12 col-md-3">
                            <button type="button" class="btn btn-success w-100 fw-bold" onclick="confirmarSeleccion()">
                                <i class="fa-solid fa-cart-plus me-1"></i> AÑADIR
                            </button>
                        </div>
                    </div>

                    <input type="hidden" id="temp_id">
                    <input type="hidden" id="temp_nombre">
                    <input type="hidden" id="temp_img">

                    <div class="table-responsive">
                        <table class="table table-sm border-secondary border-opacity-25">
                            <thead class="text-silver small">
                                <tr>
                                    <th width="50">Img</th>
                                    <th>Producto Seleccionado</th>
                                    <th width="100">Cantidad</th>
                                    <th>Comentario</th>
                                    <th width="40"></th>
                                </tr>
                            </thead>
                            <tbody id="listaProductos"></tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-secondary gap-2">
                <button type="button" class="btn btn-outline-secondary flex-grow-1 py-2 fw-bold" data-bs-dismiss="modal">CERRAR SIN CAMBIOS</button>
                <button type="button" class="btn btn-primary flex-grow-1 py-2 fw-bold" onclick="guardarTodo()">GUARDAR CAMBIOS EN PEDIDO</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
// --- GESTIÓN DE TEMAS ---
const currentTheme = localStorage.getItem('theme') || 'dark';
document.documentElement.setAttribute('data-theme', currentTheme);

function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    
    // Ajustar dropdown de Bootstrap si es necesario
    const menu = document.querySelector('.dropdown-menu');
    if (theme === 'light' || theme === 'ohlala') menu.classList.remove('dropdown-menu-dark');
    else menu.classList.add('dropdown-menu-dark');
}

// Inicializar menú según tema
if (currentTheme === 'light' || currentTheme === 'ohlala') document.querySelector('.dropdown-menu').classList.remove('dropdown-menu-dark');

// --- INICIALIZACIÓN ---
$(document).ready(function() {
    $('#tablaPedidos').DataTable({ 
        "language": {"url": "es-ES.json"}, 
        "order": [[0, "desc"]]
    });
});

// --- FUNCIONES AJAX ---
function buscarClienteCartera(t) {
    if(t.length > 1) {
        $.post('buscar_clientes_ajax.php', { term: t }, function(data) {
            $('#resultadosCliente').html(data).show();
        });
    } else { $('#resultadosCliente').hide(); }
}

function seleccionarCliente(id, nombre) {
    $('#cliente_nombre').val(nombre);
    $('#resultadosCliente').hide();
}

function buscarProducto(t) {
    if(t.length > 2) $.post('buscar_productos_ajax.php', { term: t }, function(data) { $('#resultadosBusqueda').html(data).show(); });
    else $('#resultadosBusqueda').hide();
}

function seleccionarProducto(id, cod, nom, img) {
    $('#temp_id').val(id); 
    $('#temp_nombre').val(nom);
    $('#temp_img').val(img);
    $('#inputBuscarProd').val(nom); 
    $('#resultadosBusqueda').hide();
}
function confirmarSeleccion() {
    let id = $('#temp_id').val(); 
    let nom = $('#temp_nombre').val(); 
    let img = $('#temp_img').val();
    let cant = $('#temp_cantidad').val();
    let coment = $('#temp_comentario').val();
    if (!id) { alert("Por favor, busca y selecciona un producto."); return; }
    
    let fila = `<tr id="prod_${id}" class="align-middle">
        <td>
            <div style="width: 40px; height: 40px; overflow: hidden; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2);">
                <img src="${img}" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </td>
        <td class="small">${nom}</td>
        <td><input type="number" name="cant[]" class="form-control form-control-sm text-center" value="${cant}" min="1"></td>
        <td><input type="text" name="comentario[]" class="form-control form-control-sm" value="${coment}" placeholder="Comentario..."></td>
        <td><button type="button" class="btn btn-sm text-danger border-0" onclick="$(this).closest('tr').remove()"><i class="fa-solid fa-trash"></i></button></td>
        <input type="hidden" name="p_id[]" value="${id}">
    </tr>`;
    
    $('#listaProductos').append(fila);
    $('#temp_id').val(''); 
    $('#temp_img').val('');
    $('#inputBuscarProd').val('').focus(); 
    $('#temp_cantidad').val(1);
    $('#temp_comentario').val('');
}

function prepararNuevoPedido() {
    $('#formPedido')[0].reset(); 
    $('#pedido_id').val(''); 
    $('#listaProductos').empty();
    $('#tituloModal').text('Registrar Nuevo Pedido'); 
    $('#fecha_pedido').val(new Date().toISOString().split('T')[0]);
    
    // Pre-cargar cliente si existe
    const miCliente = "<?php echo $mi_cliente_asociado; ?>";
    if(miCliente) $('#cliente_nombre').val(miCliente);

    $('#modalPedido').modal('show');
}

function editarPedido(id, estado) {
    if (estado === 'Cancelado') { alert("Este pedido está CANCELADO y no se puede editar."); return; }
    $.post('obtener_pedido.php', { id: id }, function(data) {
        let p = JSON.parse(data);
        $('#pedido_id').val(p.id); 
        $('#cliente_nombre').val(p.cliente_nombre);
        $('#fecha_pedido').val(p.fecha.split(' ')[0]); 
        $('#estado').val(p.estado);
        $('#tituloModal').text('Actualizar Pedido #' + p.folio);
        $('#listaProductos').empty();
        p.items.forEach(item => {
            let img_path = item.imagen ? 'uploads/productos/' + item.imagen : 'https://placehold.co/100x100/1e293b/94a3b8?text=NO+IMG';
            let fila = `<tr id="prod_${item.id}" class="align-middle">
                <td>
                    <div style="width: 40px; height: 40px; overflow: hidden; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2);">
                        <img src="${img_path}" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                </td>
                <td class="small">${item.nombre}</td>
                <td><input type="number" name="cant[]" class="form-control form-control-sm text-center" value="${item.cantidad}" min="1"></td>
                <td><input type="text" name="comentario[]" class="form-control form-control-sm" value="${item.comentario || ''}" placeholder="Comentario..."></td>
                <td><button type="button" class="btn btn-sm text-danger border-0" onclick="$(this).closest('tr').remove()"><i class="fa-solid fa-trash"></i></button></td>
                <input type="hidden" name="p_id[]" value="${item.id}">
            </tr>`;
            $('#listaProductos').append(fila);
        });
        $('#modalPedido').modal('show');
    });
}

function eliminarPedido(id, folio) {
    if(confirm("¿Estás seguro de ELIMINAR el pedido #" + folio + "?\nEsta acción no se puede deshacer.")) {
        $.post('eliminar_pedido.php', { id: id }, function(r) {
            if(r.trim() === "ok") {
                location.reload();
            } else {
                alert("Error al eliminar: " + r);
            }
        });
    }
}

function duplicarUltimo() {
    if (confirm("¿Deseas duplicar el último pedido registrado?")) {
        $.post('duplicar_pedido.php', function(r) {
            if (r.trim() === "ok") location.reload(); 
            else alert("Error al duplicar: " + r);
        });
    }
}

function guardarTodo() {
    const data = $('#formPedido').serialize();
    $.post('actualizar_pedido.php', data, function(r) {
        if(r.trim() == "ok") location.reload(); 
        else alert("Error al guardar pedido: " + r);
    });
}

// Registro de Service Worker para PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js')
            .then(reg => console.log('SW registrado', reg))
            .catch(err => console.log('Error registro SW', err));
    });
}
</script>
</body>
</html>