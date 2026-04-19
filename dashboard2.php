<?php
/**
 * PROYECTO: PEDIDOS OHLALA V2.0
 * DASHBOARD PROFESIONAL - UX ENHANCED (Glassmorphism & Sidebar)
 */
include('conexion.php');
session_start();

// 1.5 VERSIÓN DEL DASHBOARD (ROUTER)
if (isset($DASHBOARD_VERSION) && $DASHBOARD_VERSION == 2) {
    include('dashboardv2.php');
    exit();
}

// 1. CONTROL DE ACCESO
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header("Location: index");
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
    <title>Ohlala Dashboard Pro</title>
    
    <!-- Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        :root { 
            --bg-body: #050505; 
            --sidebar-width: 260px;
            --accent-color: #6366f1;
            --accent-glow: rgba(99, 102, 241, 0.4);
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #e2e8f0;
            --text-muted: #94a3b8;
        }

        [data-theme="light"] {
            --bg-body: #f8fafc;
            --accent-color: #2563eb;
            --accent-glow: rgba(37, 99, 235, 0.2);
            --glass-bg: rgba(255, 255, 255, 0.8);
            --glass-border: rgba(0, 0, 0, 0.05);
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        [data-theme="ohlala"] {
            --bg-body: #F7F5EB;
            --accent-color: #BD9A5F;
            --accent-glow: rgba(189, 154, 95, 0.4);
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(189, 154, 95, 0.2);
            --text-main: #071c1f;
            --text-muted: #7a828a;
        }

        body { 
            background-color: var(--bg-body); 
            color: var(--text-main); 
            font-family: 'Outfit', sans-serif; 
            overflow-x: hidden; 
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(244, 63, 94, 0.1) 0%, transparent 40%);
            min-height: 100dvh;
            transition: background 0.3s, color 0.3s;
        }

        [data-theme="ohlala"] body {
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(189, 154, 95, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(189, 154, 95, 0.1) 0%, transparent 40%);
        }
        
        [data-theme="light"] body {
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(37, 99, 235, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(37, 99, 235, 0.02) 0%, transparent 40%);
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: var(--sidebar-width);
            height: 100dvh;
            position: fixed;
            left: 0;
            top: 0;
            background: rgba(10, 10, 10, 0.6);
            backdrop-filter: blur(12px);
            border-right: 1px solid var(--glass-border);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        .sidebar-brand {
            padding: 2rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            letter-spacing: 1px;
            text-shadow: 0 0 15px var(--accent-glow);
        }
        
        .nav-link {
            color: var(--text-muted);
            padding: 0.8rem 2rem;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
            font-weight: 500;
        }
        
        .nav-link:hover, .nav-link.active {
            color: #fff;
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.1) 0%, transparent 100%);
            border-left-color: var(--accent-color);
        }
        [data-theme="light"] .nav-link:hover, [data-theme="ohlala"] .nav-link:hover, 
        [data-theme="light"] .nav-link.active, [data-theme="ohlala"] .nav-link.active {
            color: var(--accent-color);
            background: rgba(0,0,0,0.05);
        }
        
        .nav-link i { width: 24px; text-align: center; }

        /* --- MAIN CONTENT --- */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }

        /* --- GLASS CARDS --- */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.5);
            border-color: rgba(255,255,255,0.15);
        }

        .hero-stat {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(10, 10, 10, 0.4) 100%);
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        /* --- TABLE STYLES --- */
        .custom-table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--text-main);
            --bs-table-border-color: var(--glass-border);
        }
        .custom-table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            color: var(--text-muted);
            border-bottom-width: 1px;
        }
        .custom-table tr { transition: background 0.2s; }
        .custom-table tr:hover { background: rgba(255,255,255,0.02); }

        /* --- FORMS & MODALS --- */
        .form-control, .form-select {
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--glass-border);
            color: white;
            border-radius: 8px;
            padding: 0.6rem 1rem;
        }
        .form-control:focus {
            background: rgba(0,0,0,0.5);
            border-color: var(--accent-color);
            color: white;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        
        .modal-content {
            background: #0f172a;
            border: 1px solid var(--glass-border);
        }
        [data-theme="light"] .modal-content, [data-theme="ohlala"] .modal-content { background: #ffffff; color: #1e293b; }
        .modal-header, .modal-footer { border-color: var(--glass-border); }
        [data-theme="light"] .btn-close, [data-theme="ohlala"] .btn-close { filter: invert(1); }
        
        /* Tema Claro/Ohlala para tablas */
        [data-theme="light"] .custom-table, [data-theme="ohlala"] .custom-table { --bs-table-color: var(--text-main); }
        [data-theme="light"] .text-white, [data-theme="ohlala"] .text-white { color: var(--text-main) !important; }
        [data-theme="light"] .text-muted, [data-theme="ohlala"] .text-muted { color: var(--text-muted) !important; }
        [data-theme="light"] .sidebar, [data-theme="ohlala"] .sidebar { background: rgba(255,255,255,0.8); }
        [data-theme="light"] .sidebar-brand, [data-theme="ohlala"] .sidebar-brand { color: var(--text-main); }
        [data-theme="light"] .hero-stat h1, [data-theme="ohlala"] .hero-stat h1 { color: var(--text-main) !important; }
        /* --- AJAX SEARCH --- */
        .resultados-ajax { 
            position: absolute; 
            width: 100%; 
            background: #1e293b; 
            border: 1px solid var(--glass-border);
            border-radius: 0 0 12px 12px;
            z-index: 9999;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            overflow: hidden;
            display: none;
        }
        .list-group-item {
            background: transparent;
            color: var(--text-main);
            border-color: var(--glass-border);
            padding: 10px 15px;
            cursor: pointer;
        }
        .list-group-item:hover { background: var(--accent-color); color: white; }

        /* --- ANIMATIONS --- */
        .fade-in-up { animation: fadeInUp 0.5s ease-out forwards; opacity: 0; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }

        /* --- BADGES --- */
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.5px; }
        .badge-pendiente { background: rgba(234, 179, 8, 0.15); color: #fbbf24; border: 1px solid rgba(234, 179, 8, 0.3); }
        .badge-preparando { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
        .badge-completado { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
        .badge-cancelado { background: rgba(244, 63, 94, 0.15); color: #f43f5e; border: 1px solid rgba(244, 63, 94, 0.3); }
        .badge-incompleto { background: rgba(168, 85, 247, 0.15); color: #a855f7; border: 1px solid rgba(168, 85, 247, 0.3); }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="fa-solid fa-shapes me-2 text-primary"></i> Ohlala <span style="font-size: 0.6rem; vertical-align: middle; opacity: 0.5;">v2.1</span>
    </div>
    
    <div class="d-flex flex-column h-100">
        <a href="#" class="nav-link active">
            <i class="fa-solid fa-chart-pie"></i> Dashboard
        </a>
        
        <?php if($esAdmin): ?>
        <p class="text-uppercase small text-muted ms-4 mt-4 mb-2 fw-bold" style="font-size: 0.7rem;">Administración</p>
        <a href="clientes.php" class="nav-link">
            <i class="fa-solid fa-users"></i> Clientes
        </a>
        <a href="productos" class="nav-link">
            <i class="fa-solid fa-cake-candles"></i> Productos
        </a>
        <a href="usuarios.php" class="nav-link">
            <i class="fa-solid fa-user-gear"></i> Usuarios
        </a>
        <a href="filexxml2" class="nav-link">
            <i class="fa-solid fa-file-invoice"></i> Facturación
        </a>
        <?php endif; ?>

        <div class="dropdown mx-3 mt-3">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle w-100 border-0 text-start px-3" type="button" data-bs-toggle="dropdown">
                <i class="fa-solid fa-palette me-2"></i> Tema
            </button>
            <ul class="dropdown-menu dropdown-menu-dark shadow">
                <li><a class="dropdown-item" href="javascript:void(0)" onclick="setTheme('dark')"><i class="fa-solid fa-moon me-2"></i> Oscuro</a></li>
                <li><a class="dropdown-item" href="javascript:void(0)" onclick="setTheme('light')"><i class="fa-solid fa-sun me-2"></i> Claro</a></li>
                <li><a class="dropdown-item" href="javascript:void(0)" onclick="setTheme('ohlala')"><i class="fa-solid fa-star me-2" style="color: #BD9A5F;"></i> Ohlala</a></li>
            </ul>
        </div>

        <div class="mt-auto mb-4 border-top border-secondary pt-3 mx-3">
            <div class="d-flex align-items-center mb-3">
                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" 
                     style="width: 35px; height: 35px; margin-right: 12px;">
                    <?= substr($nombre_vendedor, 0, 1) ?>
                </div>
                <div>
                    <div style="font-size: 0.85rem;" class="fw-bold text-white"><?= substr($nombre_vendedor, 0, 18) ?></div>
                    <div style="font-size: 0.7rem;" class="text-muted"><?= $rol_actual ?></div>
                </div>
            </div>
            <a href="logout" class="btn btn-outline-danger w-100 btn-sm rounded-pill">
                <i class="fa-solid fa-right-from-bracket me-2"></i> Cerrar Sesión
            </a>
        </div>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    
    <!-- HEADER MOBILE ONLY -->
    <div class="d-flex d-md-none justify-content-between align-items-center mb-4">
        <h4 class="m-0 fw-bold">Ohlala</h4>
        <button class="btn btn-dark border-secondary" onclick="document.getElementById('sidebar').classList.toggle('show')">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <!-- STATS ROW -->
    <div class="row g-4 mb-5">
        <div class="col-12 col-md-4 fade-in-up">
            <div class="glass-card hero-stat h-100 position-relative overflow-hidden">
                <div class="position-absolute end-0 top-0 p-3 opacity-25">
                    <i class="fa-solid fa-cart-shopping fa-3x"></i>
                </div>
                <h6 class="text-muted text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 1px;">
                    <?php echo $esAdmin ? 'Pedidos Global' : 'Mis Pedidos'; ?>
                </h6>
                <h1 class="display-4 fw-bold mb-0 text-white"><?php echo $total_pedidos; ?></h1>
            </div>
        </div>
        
        <?php if($esAdmin): ?>
        <div class="col-6 col-md-2 fade-in-up delay-1">
            <a href="clientes.php" class="text-decoration-none">
                <div class="glass-card h-100 text-center py-4">
                    <i class="fa-solid fa-users fa-2x text-success mb-2"></i>
                    <div class="text-muted small fw-bold">Clientes</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-2 fade-in-up delay-2">
            <a href="productos" class="text-decoration-none">
                <div class="glass-card h-100 text-center py-4">
                    <i class="fa-solid fa-cake-candles fa-2x text-danger mb-2"></i>
                    <div class="text-muted small fw-bold">Productos</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-2 fade-in-up delay-3">
            <a href="filexxml2" class="text-decoration-none">
                <div class="glass-card h-100 text-center py-4">
                    <i class="fa-solid fa-file-invoice fa-2x text-info mb-2"></i>
                    <div class="text-muted small fw-bold">CFDI</div>
                </div>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- MAIN TABLE CARD -->
    <div class="glass-card fade-in-up delay-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h4 class="mb-1 text-white fw-bold">Gestión de Pedidos</h4>
                <p class="mb-0 text-muted small">Administra los pedidos entrantes en tiempo real</p>
            </div>
            
            <div class="d-flex gap-2">
                <button class="btn btn-outline-light rounded-pill px-3" onclick="duplicarUltimo()">
                    <i class="fa-regular fa-copy me-2"></i> Duplicar
                </button>
                <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-lg" onclick="prepararNuevoPedido()" 
                        style="background: var(--accent-color); border:none;">
                    <i class="fa-solid fa-plus me-2"></i> NUEVO PEDIDO
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaPedidos" class="table custom-table w-100 align-middle">
                <thead>
                    <tr>
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
                            $badgeClass = 'badge-secondary';
                            if ($row['estado'] == 'Cancelado') $badgeClass = 'badge-cancelado';
                            if ($row['estado'] == 'Pendiente') $badgeClass = 'badge-pendiente';
                            if ($row['estado'] == 'Preparando') $badgeClass = 'badge-preparando';
                            if ($row['estado'] == 'Completado') $badgeClass = 'badge-completado';
                            if ($row['estado'] == 'Incompleto') $badgeClass = 'badge-incompleto';
                    ?>
                    <tr>
                        <td class="fw-bold text-white"><?php echo $row['folio']; ?></td>
                        <td class="text-muted small"><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                        <td class="text-truncate fw-medium" style="max-width: 250px;"><?php echo htmlspecialchars($row['cliente_nombre']); ?></td>
                        <td><span class="status-badge <?php echo $badgeClass; ?>"><?php echo $row['estado']; ?></span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-dark border-secondary rounded-circle me-1" 
                                    onclick="editarPedido(<?php echo $row['id']; ?>, '<?php echo $row['estado']; ?>')" title="Editar">
                                <i class="fa-solid fa-pen-to-square text-warning"></i>
                            </button>
                            <?php if($esAdmin): ?>
                            <button class="btn btn-sm btn-dark border-secondary rounded-circle" 
                                    onclick="eliminarPedido(<?php echo $row['id']; ?>, '<?php echo $row['folio']; ?>')" title="Eliminar">
                                <i class="fa-solid fa-trash text-danger"></i>
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

<!-- MODAL PEDIDO -->
<div class="modal fade" id="modalPedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content text-white">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="tituloModal">Detalle de Pedido</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formPedido">
                    <input type="hidden" id="pedido_id" name="id">
                    
                    <!-- Fila 1 -->
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6 position-relative">
                            <label class="form-label text-muted small text-uppercase fw-bold">Cliente</label>
                            <input type="text" id="cliente_nombre" name="cliente_nombre" class="form-control" 
                                   onkeyup="buscarClienteCartera(this.value)" placeholder="Buscar cliente..." autocomplete="off" required>
                            <div id="resultadosCliente" class="resultados-ajax list-group"></div>
                        </div>

                        <div class="col-6 col-md-3">
                            <label class="form-label text-muted small text-uppercase fw-bold">Fecha</label>
                            <input type="date" id="fecha_pedido" name="fecha" class="form-control" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label text-muted small text-uppercase fw-bold">Estado</label>
                            <select id="estado" name="estado" class="form-select">
                                <option value="Pendiente">Pendiente</option>
                                <option value="Preparando">Preparando</option>
                                <option value="Completado">Completado</option>
                                <option value="Incompleto">Incompleto</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Buscador Productos -->
                    <div class="p-3 mb-4 rounded-3" style="background: rgba(255,255,255,0.03); border: 1px dashed var(--glass-border);">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-7 position-relative">
                                <label class="form-label text-muted small text-uppercase fw-bold">Agregar Producto</label>
                                <input type="text" class="form-control" id="inputBuscarProd" onkeyup="buscarProducto(this.value)" placeholder="Escribe para buscar...">
                                <div id="resultadosBusqueda" class="resultados-ajax list-group"></div>
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label text-muted small text-uppercase fw-bold">Cant.</label>
                                <input type="number" id="temp_cantidad" class="form-control text-center" value="1" min="1">
                            </div>
                            <div class="col-8 col-md-3">
                                <button type="button" class="btn btn-success w-100 fw-bold rounded-3" onclick="confirmarSeleccion()">
                                    <i class="fa-solid fa-plus"></i> AGREGAR
                                </button>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="temp_id">
                    <input type="hidden" id="temp_nombre">
                    <input type="hidden" id="temp_img">

                    <!-- Tabla productos -->
                    <div class="table-responsive rounded-3 border border-secondary border-opacity-25 overflow-hidden">
                        <table class="table table-sm mb-0 text-white">
                            <thead class="bg-dark text-uppercase small text-muted">
                                <tr>
                                    <th class="ps-3 py-2" style="width:50px;">Img</th>
                                    <th>Producto</th>
                                    <th style="width:100px;">Cant.</th>
                                    <th>Comentario</th>
                                    <th style="width:50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="listaProductos"></tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 gap-2">
                <button type="button" class="btn btn-outline-light border-0 flex-grow-1" data-bs-dismiss="modal">Cerrar sin cambios</button>
                <button type="button" class="btn btn-primary px-4 rounded-pill fw-bold flex-grow-1" 
                        style="background: var(--accent-color); border:none;" onclick="guardarTodo()">
                    Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS -->
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
    const menu = document.querySelector('.dropdown-menu');
    if (theme === 'light' || theme === 'ohlala') menu.classList.remove('dropdown-menu-dark');
    else menu.classList.add('dropdown-menu-dark');
}
if (currentTheme === 'light' || currentTheme === 'ohlala') document.querySelector('.dropdown-menu').classList.remove('dropdown-menu-dark');

// --- INICIALIZACIÓN ---
$(document).ready(function() {
    $('#tablaPedidos').DataTable({ 
        "language": {"url": "es-ES.json"}, 
        "order": [[0, "desc"]],
        "lengthChange": false,
        "pageLength": 8,
        "dom": 'tp' // Solo tabla y paginación, quitamos el search default para limpiar UI
    });
});

// --- FUNCIONES AJAX (Misma lógica, mejor UI) ---
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
    $('#temp_cantidad').focus();
}

function confirmarSeleccion() {
    let id = $('#temp_id').val(); 
    let nom = $('#temp_nombre').val(); 
    let img = $('#temp_img').val();
    let cant = $('#temp_cantidad').val();
    if (!id) { alert("Por favor selecciona un producto de la lista."); return; }
    
    let fila = `<tr id="prod_${id}" class="align-middle">
        <td class="ps-3">
            <div style="width: 35px; height: 35px; overflow: hidden; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2);">
                <img src="${img}" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </td>
        <td class="text-white small">${nom}</td>
        <td><input type="number" name="cant[]" class="form-control form-control-sm text-center bg-transparent text-white border-0" value="${cant}" min="1" readonly></td>
        <td><input type="text" name="comentario[]" class="form-control form-control-sm bg-transparent text-white border-secondary border-opacity-25" placeholder="Comentario..."></td>
        <td class="text-center"><button type="button" class="btn btn-sm text-danger p-0" onclick="$(this).closest('tr').remove()"><i class="fa-solid fa-xmark"></i></button></td>
        <input type="hidden" name="p_id[]" value="${id}">
    </tr>`;
    
    $('#listaProductos').append(fila);
    $('#temp_id').val(''); 
    $('#temp_img').val('');
    $('#inputBuscarProd').val('').focus(); 
    $('#temp_cantidad').val(1);
    
    // Feedback visual
    const btn = event.currentTarget;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-check"></i>';
    setTimeout(() => btn.innerHTML = originalText, 1000);
}

function prepararNuevoPedido() {
    $('#formPedido')[0].reset(); 
    $('#pedido_id').val(''); 
    $('#listaProductos').empty();
    $('#tituloModal').text('Nuevo Pedido'); 
    $('#fecha_pedido').val(new Date().toISOString().split('T')[0]);

    // Pre-cargar cliente si existe
    const miCliente = "<?php echo $mi_cliente_asociado; ?>";
    if(miCliente) $('#cliente_nombre').val(miCliente);

    $('#modalPedido').modal('show');
}

function editarPedido(id, estado) {
    if (estado === 'Cancelado') { alert("No se puede editar un pedido cancelado."); return; }
    
    // Efecto loading visual opcional
    
    $.post('obtener_pedido.php', { id: id }, function(data) {
        let p = JSON.parse(data);
        $('#pedido_id').val(p.id); 
        $('#cliente_nombre').val(p.cliente_nombre);
        $('#fecha_pedido').val(p.fecha.split(' ')[0]); 
        $('#estado').val(p.estado);
        $('#tituloModal').text('Editar Pedido #' + p.folio);
        $('#listaProductos').empty();
        p.items.forEach(item => {
            let img_path = item.imagen ? 'uploads/productos/' + item.imagen : 'https://placehold.co/100x100/1e293b/94a3b8?text=NO+IMG';
            let fila = `<tr id="prod_${item.id}" class="align-middle">
                <td class="ps-3">
                    <div style="width: 35px; height: 35px; overflow: hidden; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2);">
                        <img src="${img_path}" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                </td>
                <td class="text-white small">${item.nombre}</td>
                <td><input type="number" name="cant[]" class="form-control form-control-sm text-center bg-transparent text-white border-0" value="${item.cantidad}" readonly></td>
                <td><input type="text" name="comentario[]" class="form-control form-control-sm bg-transparent text-white border-secondary border-opacity-25" value="${item.comentario || ''}" placeholder="Comentario..."></td>
                <td class="text-center"><button type="button" class="btn btn-sm text-danger p-0" onclick="$(this).closest('tr').remove()"><i class="fa-solid fa-xmark"></i></button></td>
                <input type="hidden" name="p_id[]" value="${item.id}">
            </tr>`;
            $('#listaProductos').append(fila);
        });
        $('#modalPedido').modal('show');
    });
}

function eliminarPedido(id, folio) {
    if(confirm("¿Eliminar pedido #" + folio + "?\nEsta acción es irreversible.")) {
        $.post('eliminar_pedido.php', { id: id }, function(r) {
            if(r.trim() === "ok") {
                location.reload();
            } else {
                alert("Error: " + r);
            }
        });
    }
}

function duplicarUltimo() {
    if (confirm("¿Duplicar el último pedido?")) {
        $.post('duplicar_pedido.php', function(r) {
            if (r.trim() === "ok") location.reload(); 
            else alert("Error: " + r);
        });
    }
}

function guardarTodo() {
    const data = $('#formPedido').serialize();
    $.post('actualizar_pedido.php', data, function(r) {
        if(r.trim() == "ok") location.reload(); 
        else alert("Error: " + r);
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
