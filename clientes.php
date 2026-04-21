<?php
include('conexion.php');
session_start();

// 1. CONTROL DE ACCESO: Solo Administradores
if (!isset($_SESSION['autenticado']) || $_SESSION['usuario_rol'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// 2. LÓGICA DE BÚSQUEDA Y PAGINACIÓN
$search = isset($_GET['q']) ? mysqli_real_escape_string($conexion, $_GET['q']) : '';
$por_pagina = 10;
$pagina = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pagina <= 0) $pagina = 1;
$inicio = ($pagina - 1) * $por_pagina;

$where = "";
if (!empty($search)) {
    $where = " WHERE nombre LIKE '%$search%' OR rfc LIKE '%$search%' OR codigo_cliente LIKE '%$search%' OR comercial LIKE '%$search%' ";
}

// Contar registros para la paginación
$total_res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM clientes $where");
$total_filas = mysqli_fetch_assoc($total_res)['total'] ?? 0;
$total_paginas = ceil($total_filas / $por_pagina);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Ohlala - Gestión de Clientes</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Modern Fonts & Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <style>
        :root {
            --primary-ink: #021619;
            --artisanal-gold: #BD9A5F;
            --paper-cream: #F7F5EB;
        }
        :root[data-theme="dark"] { --bg-deep: #050a14; --card-blue: #0f172a; --accent: #2563eb; --text-silver: #94a3b8; --text-main: #ffffff; --border-color: rgba(255,255,255,0.05); }
        :root[data-theme="light"] { --bg-deep: #f8fafc; --card-blue: #ffffff; --accent: #2563eb; --text-silver: #64748b; --text-main: #1e293b; --border-color: rgba(0,0,0,0.1); }
        :root[data-theme="custom"] { --bg-deep: #0f172a; --card-blue: #1e293b; --accent: #8b5cf6; --text-silver: #c084fc; --text-main: #f3f4f6; --border-color: rgba(139, 92, 246, 0.2); }
        :root[data-theme="ohlala"] { --bg-deep: #F7F5EB; --card-blue: #FFFFFF; --accent: #BD9A5F; --text-silver: #7a828a; --text-main: #071c1f; --border-color: rgba(189, 154, 95, 0.3); }

        body { background-color: var(--bg-deep); color: var(--text-main); font-family: 'Manrope', sans-serif; overflow-x: hidden; transition: background-color 0.3s, color 0.3s; }
        .font-headline { font-family: 'Playfair Display', serif; }
        
        .navbar-custom { background-color: var(--card-blue); border-bottom: 1px solid var(--border-color); padding: 0.8rem 1.2rem; position: sticky; top: 0; z-index: 1050; }
        .card-main { background: var(--card-blue); border-radius: 20px; padding: 20px; border: 1px solid var(--border-color); color: var(--text-main); }
        .form-control { background-color: rgba(0,0,0,0.1); border: 1px solid var(--border-color); color: var(--text-main); }
        [data-theme="dark"] .form-control { background-color: rgba(0,0,0,0.3); }
        .form-control:focus { background-color: rgba(0,0,0,0.2); border-color: var(--accent); color: var(--text-main); box-shadow: none; }
        .modal-content { background-color: var(--card-blue); color: var(--text-main); border: 1px solid var(--border-color); }
        .pagination .page-link { background: var(--card-blue); border: 1px solid var(--border-color); color: var(--text-main); font-size: 0.8rem; }
        .pagination .page-item.active .page-link { background: var(--accent); border-color: var(--accent); color: white; }
        .text-silver { color: var(--text-silver) !important; }

        @media (max-width: 768px) {
            thead { display: none; }
            tr { display: block; background: rgba(255,255,255,0.03); margin-bottom: 15px; border-radius: 15px; padding: 10px; border: 1px solid var(--border-color) !important; }
            td { display: flex; justify-content: space-between; align-items: center; border: none !important; padding: 8px 10px !important; }
            td::before { content: attr(data-label); font-weight: 600; color: var(--accent); text-transform: uppercase; font-size: 0.7rem; }
            .btn-group-mobile { width: 100%; display: flex; gap: 5px; margin-top: 10px; border-top: 1px solid var(--border-color); padding-top: 10px; }
            .btn-group-mobile button { flex: 1; }
        }

        [data-theme="light"] .table-dark, [data-theme="ohlala"] .table-dark { background-color: var(--card-blue); color: var(--text-main) !important; }
        [data-theme="light"] .btn-close, [data-theme="ohlala"] .btn-close { filter: invert(1); }
        [data-theme="light"] .text-white, [data-theme="ohlala"] .text-white { color: var(--text-main) !important; }
    </style>
</head>
<body class="bg-[#F7F5EB] font-body text-[#071c1f]">

<header class="flex justify-between items-center w-full px-6 py-4 z-50 bg-[#BD9A5F] top-0 shadow-lg shadow-[#021619]/20 sticky">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-[#021619] cursor-pointer md:hidden" onclick="history.back()">arrow_back</span>
            <h1 class="text-2xl font-headline font-bold text-[#021619] tracking-tight">Ohlala! Bistro (V3.1)</h1>
        </div>
    <div class="hidden md:flex gap-8 items-center">
        <nav class="flex gap-6 items-center text-[#021619]">
            <a class="font-headline opacity-80 hover:opacity-100 no-underline text-[#021619]" href="dashboardv3.php">Inicio</a>
            
            <!-- Dropdown Catálogos -->
            <div class="relative group">
                <button class="font-headline opacity-80 hover:opacity-100 flex items-center gap-1 py-1">
                    Catálogos <span class="material-symbols-outlined text-sm">expand_more</span>
                </button>
                <div class="absolute left-0 mt-2 w-48 bg-[#021619] text-[#F7F5EB] shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50 rounded-sm overflow-hidden border border-[#BD9A5F]/20">
                    <a href="clientes.php" class="block px-4 py-3 text-[10px] uppercase font-black tracking-widest hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5 no-underline text-[#F7F5EB] bg-[#BD9A5F]/10">Clientes</a>
                    <a href="productos.php" class="block px-4 py-3 text-[10px] uppercase font-black tracking-widest hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5 no-underline text-[#F7F5EB]">Productos</a>
                    <a href="modules/purchasing/ui/suppliers.php" class="block px-4 py-3 text-[10px] uppercase font-black tracking-widest hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5 no-underline text-[#F7F5EB]">Proveedores</a>
                    <a href="modules/purchasing/ui/items.php" class="block px-4 py-3 text-[10px] uppercase font-black tracking-widest hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5 no-underline text-[#F7F5EB]">Insumos</a>
                    <?php if($esAdmin): ?>
                    <a href="usuarios.php" class="block px-4 py-3 text-[10px] uppercase font-black tracking-widest hover:bg-[#BD9A5F] hover:text-[#021619] transition-all no-underline text-[#F7F5EB]">Usuarios</a>
                    <?php endif; ?>
                </div>
            </div>

            <a class="font-headline opacity-80 hover:opacity-100 no-underline text-[#021619]" href="modules/purchasing/ui/list_requests.php">Compras</a>
        </nav>
        <button onclick="location.href='logout.php'" class="bg-[#021619] text-[#F7F5EB] px-6 py-2 font-headline font-medium hover:bg-[#021619]/90 active:scale-95 transition-all">
            Salir
        </button>
    </div>
</header>

<div class="container-fluid px-3">
    <div class="row mb-3 g-2">
        <div class="col-8 col-md-9">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-dark border-secondary text-silver"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="inputBuscar" class="form-control" placeholder="Escribe para buscar..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        <div class="col-4 col-md-3">
            <button class="btn btn-primary w-100 shadow fw-bold" onclick="nuevoCliente()">
                <i class="fa-solid fa-plus"></i> <span class="d-none d-md-inline">NUEVO</span>
            </button>
        </div>
    </div>

    <div class="card-main shadow-lg">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="small text-secondary fw-bold">RESULTADOS: <?php echo $total_filas; ?></span>
            <div class="d-flex gap-3 align-items-center">
                <!-- <a href="borrar_clientes.php" class="btn btn-link btn-sm text-danger text-decoration-none p-0"><i class="fa-solid fa-trash-can"></i> Borrar Todos</a> -->
                <a href="importar_clientes.php" class="btn btn-link btn-sm text-info text-decoration-none p-0"><i class="fa-solid fa-file-excel"></i> Excel</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr class="text-secondary small border-bottom border-secondary">
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th>Comercial</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql_list = "SELECT * FROM clientes $where ORDER BY nombre ASC LIMIT $inicio, $por_pagina";
                    $res = mysqli_query($conexion, $sql_list);
                    if(mysqli_num_rows($res) > 0):
                        while($c = mysqli_fetch_assoc($res)):
                    ?>
                    <tr>
                        <td data-label="Cliente">
                            <span class="badge bg-primary mb-1" style="font-size: 0.65rem;"><?php echo htmlspecialchars($c['codigo_cliente'] ?? 'S/C'); ?></span>
                            <div class="fw-bold text-info"><?php echo htmlspecialchars($c['nombre']); ?></div>
                            <div class="text-secondary small"><?php echo htmlspecialchars($c['rfc']); ?></div>
                        </td>
                        <td data-label="Contacto">
                            <div class="small"><?php echo htmlspecialchars($c['correo']); ?></div>
                            <div class="text-white-50 small"><?php echo htmlspecialchars($c['telefono']); ?></div>
                        </td>
                        <td data-label="Comercial" class="small"><?php echo htmlspecialchars($c['comercial']); ?></td>
                        <td data-label="Acciones">
                            <div class="btn-group-mobile">
                                <button type="button" class="btn btn-sm btn-outline-warning border-0" onclick='editarCliente(<?php echo json_encode($c); ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="borrarCliente(event, <?php echo $c['id']; ?>)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="4" class="text-center py-5 text-secondary">No hay clientes que mostrar</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($total_paginas > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center flex-wrap">
                <?php if($pagina > 1): ?>
                    <li class="page-item"><a class="page-link" href="?p=<?php echo $pagina-1; ?>&q=<?php echo urlencode($search); ?>">&laquo;</a></li>
                <?php endif; ?>
                <?php for($i=1; $i<=$total_paginas; $i++): ?>
                    <li class="page-item <?php echo ($i==$pagina)?'active':''; ?>">
                        <a class="page-link" href="?p=<?php echo $i; ?>&q=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if($pagina < $total_paginas): ?>
                    <li class="page-item"><a class="page-link" href="?p=<?php echo $pagina+1; ?>&q=<?php echo urlencode($search); ?>">&raquo;</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg">
            <form id="formCliente">
                <input type="hidden" name="id" id="cli_id">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="modalTitle">Cliente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="small text-secondary mb-1">Código Cliente</label>
                            <input type="text" name="codigo_cliente" id="cli_codigo" class="form-control" maxlength="10">
                        </div>
                        <div class="col-md-8">
                            <label class="small text-secondary mb-1">Nombre / Razón Social</label>
                            <input type="text" name="nombre" id="cli_nombre" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="small text-secondary mb-1">RFC</label>
                            <input type="text" name="rfc" id="cli_rfc" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="small text-secondary mb-1">Correo Electrónico</label>
                            <input type="email" name="correo" id="cli_correo" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="small text-secondary mb-1">Teléfono</label>
                            <input type="text" name="telefono" id="cli_telefono" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="small text-secondary mb-1">Nombre Comercial</label>
                            <input type="text" name="comercial" id="cli_comercial" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1 py-2 fw-bold" data-bs-dismiss="modal">CERRAR</button>
                    <button type="button" class="btn btn-primary flex-grow-1 py-2 fw-bold" onclick="guardarCliente()">GUARDAR DATOS</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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

// --- LÓGICA DE BÚSQUEDA ---
$('#inputBuscar').on('keyup', function() {
    clearTimeout(timeout);
    timeout = setTimeout(function() {
        let valor = $('#inputBuscar').val();
        window.location.href = 'clientes.php?q=' + encodeURIComponent(valor);
    }, 700);
});

function nuevoCliente() {
    $('#formCliente')[0].reset();
    $('#cli_id').val('');
    $('#modalTitle').text('Registrar Nuevo Cliente');
    $('#modalCliente').modal('show');
}

function editarCliente(c) {
    $('#cli_id').val(c.id);
    $('#cli_codigo').val(c.codigo_cliente);
    $('#cli_nombre').val(c.nombre);
    $('#cli_rfc').val(c.rfc);
    $('#cli_correo').val(c.correo);
    $('#cli_telefono').val(c.telefono);
    $('#cli_comercial').val(c.comercial);
    $('#modalTitle').text('Editar Datos de Cliente');
    $('#modalCliente').modal('show');
}

function guardarCliente() {
    $.post('guardar_cliente.php', $('#formCliente').serialize(), function(r) {
        if(r.trim() === "ok") location.reload();
        else alert("Respuesta: " + r);
    });
}

function borrarCliente(e, id) {
    if(e) { e.preventDefault(); e.stopPropagation(); }

    if(confirm("¿Estás seguro de eliminar este cliente de forma permanente?")) {
        $.post('guardar_cliente.php', { eliminar_id: id }, function(r) {
            console.log("Respuesta servidor:", r);
            if(r.status === 'ok') {
                location.reload();
            } else {
                alert("No se pudo eliminar: " + (r.message || "Error desconocido"));
            }
        }, 'json')
        .fail(function() {
            alert("Error de conexión con el servidor.");
        });
    }
}
</script>
</body>
</html>