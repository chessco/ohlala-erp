<?php
include('conexion.php');
session_start();

// 1. CONTROL DE ACCESO: Solo Administradores
if (!isset($_SESSION['autenticado']) || $_SESSION['usuario_rol'] !== 'admin') {
    header("Location: dashboard");
    exit();
}

// 2. LÓGICA DE BÚSQUEDA
$search = isset($_GET['q']) ? mysqli_real_escape_string($conexion, $_GET['q']) : '';

$where = "";
if (!empty($search)) {
    $where = " WHERE nombre LIKE '%$search%' OR codigo LIKE '%$search%' ";
}

// Obtener todos los productos filtrados por búsqueda inicial (DataTables manejará el resto)
$sql_list = "SELECT * FROM productos $where ORDER BY nombre ASC";
$res_total = mysqli_query($conexion, $sql_list);
$total_filas = mysqli_num_rows($res_total);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Ohlala - Gestión de Productos</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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

        /* DataTables overrides for theme consistency */
        .dataTables_wrapper .dataTables_paginate .page-link { background: var(--card-blue); border-color: var(--border-color); color: var(--text-main); font-size: 0.85rem; border-radius: 8px; margin: 0 2px; }
        .dataTables_wrapper .dataTables_paginate .page-item.active .page-link { background: var(--accent); border-color: var(--accent); color: white; }
        .dataTables_wrapper .dataTables_info { color: var(--text-silver); font-size: 0.85rem; }
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter { display: none; }
        
        table.dataTable thead th { border-bottom: 1px solid var(--border-color) !important; }
        table.dataTable td { border-bottom: 1px solid var(--border-color) !important; }
        
        /* Sorting icons colors */
        table.dataTable thead .sorting:before, table.dataTable thead .sorting_asc:before, table.dataTable thead .sorting_desc:before { color: var(--accent); opacity: 0.3; }
        table.dataTable thead .sorting:after, table.dataTable thead .sorting_asc:after, table.dataTable thead .sorting_desc:after { color: var(--accent); opacity: 0.3; }
        table.dataTable thead .sorting_asc:before, table.dataTable thead .sorting_desc:after { opacity: 1; }
    </style>
</head>
<body class="bg-[#F7F5EB] font-body text-[#071c1f]">

<header class="flex justify-between items-center w-full px-6 py-4 z-50 bg-[#BD9A5F] top-0 shadow-lg shadow-[#021619]/20 sticky">
    <div class="flex items-center gap-4">
        <a href="dashboardv3.php" class="material-symbols-outlined text-[#021619] cursor-pointer no-underline">arrow_back</a>
        <h1 class="text-xl font-headline font-bold text-[#F7F5EB] tracking-tight">Ohlala! Bistro (V3.1)</h1>
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
                    <a href="clientes.php" class="block px-4 py-3 text-[10px] uppercase font-black tracking-widest hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5 no-underline text-[#F7F5EB]">Clientes</a>
                    <a href="productos.php" class="block px-4 py-3 text-[10px] uppercase font-black tracking-widest hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5 no-underline text-[#F7F5EB] bg-[#BD9A5F]/10">Productos</a>
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
                <input type="text" id="inputBuscar" class="form-control" placeholder="Buscar producto..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        <div class="col-4 col-md-3">
            <button class="btn btn-primary w-100 shadow fw-bold" onclick="nuevoProducto()">
                <i class="fa-solid fa-plus"></i> <span class="d-none d-md-inline">NUEVO</span>
            </button>
        </div>
    </div>

    <div class="card-main shadow-lg">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="small text-secondary fw-bold">RESULTADOS: <?php echo $total_filas; ?></span>
            <div class="d-flex gap-3">
                <!-- <a href="borrar_productos.php" class="btn btn-link btn-sm text-danger text-decoration-none"><i class="fa-solid fa-trash-can"></i> Borrar Todos</a> -->
                <a href="importar_productos" class="btn btn-link btn-sm text-info text-decoration-none"><i class="fa-solid fa-file-excel"></i> Importar Excel</a>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaProductos" class="table table-dark table-hover align-middle">
                <thead>
                    <tr class="text-secondary small border-bottom border-secondary">
                        <th width="60">Img</th>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    mysqli_data_seek($res_total, 0); // Reiniciar puntero por si acaso
                    if(mysqli_num_rows($res_total) > 0):
                        while($p = mysqli_fetch_assoc($res_total)):
                    ?>
                    <tr>
                        <td data-label="Img">
                            <?php 
                            $img_p = !empty($p['imagen']) ? 'uploads/productos/' . $p['imagen'] : 'https://placehold.co/100x100/1e293b/94a3b8?text=NO+IMG';
                            ?>
                            <div style="width: 40px; height: 40px; overflow: hidden; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2);">
                                <img src="<?php echo $img_p; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        </td>
                        <td data-label="Código">
                            <span class="badge bg-secondary mb-1" style="font-size: 0.75rem;"><?php echo htmlspecialchars($p['codigo']); ?></span>
                        </td>
                        <td data-label="Producto">
                            <div class="fw-bold text-info"><?php echo htmlspecialchars($p['nombre']); ?></div>
                        </td>
                        <td data-label="Precio">
                            <span class="fw-bold text-success">$ <?php echo number_format($p['precio'], 2); ?></span>
                        </td>
                        <td data-label="Acciones">
                            <div class="btn-group-mobile">
                                <button class="btn btn-sm btn-outline-warning border-0" onclick='editarProducto(<?php echo json_encode($p); ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger border-0" onclick="eliminarProducto(<?php echo $p['id']; ?>)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="4" class="text-center py-5 text-secondary">No hay productos que mostrar</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="infoTable" class="mt-3"></div>
    </div>
</div>

<div class="modal fade" id="modalProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg">
            <form id="formProducto">
                <input type="hidden" name="id" id="prod_id">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="modalTitle">Producto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="small text-secondary mb-1">Código</label>
                            <input type="text" name="codigo" id="prod_codigo" class="form-control" maxlength="20" required>
                        </div>
                        <div class="col-md-12">
                            <label class="small text-secondary mb-1">Nombre del Producto</label>
                            <input type="text" name="nombre" id="prod_nombre" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="small text-secondary mb-1">Precio Unitario</label>
                            <input type="number" step="0.01" name="precio" id="prod_precio" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="small text-secondary mb-1">Imagen del Producto</label>
                            <div id="previewContainer" class="mb-2 d-none">
                                <div style="width: 100px; height: 100px; overflow: hidden; border-radius: 12px; border: 2px solid var(--accent); background: rgba(0,0,0,0.2);">
                                    <img id="imgPreview" src="" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <small class="text-silver">Imagen actual</small>
                            </div>
                            <input type="file" name="imagen" class="form-control" accept="image/*" onchange="previewUpdate(this)">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1 py-2 fw-bold" data-bs-dismiss="modal">CERRAR</button>
                    <button type="button" class="btn btn-primary flex-grow-1 py-2 fw-bold" onclick="guardarProducto()">GUARDAR PRODUCTO</button>
                </div>
            </form>
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
    const menu = document.querySelector('.dropdown-menu');
    if (theme === 'light' || theme === 'ohlala') menu.classList.remove('dropdown-menu-dark');
    else menu.classList.add('dropdown-menu-dark');
}
if (currentTheme === 'light' || currentTheme === 'ohlala') document.querySelector('.dropdown-menu').classList.remove('dropdown-menu-dark');

// --- INICIALIZACIÓN DATATABLES ---
let tableProductos;
$(document).ready(function() {
    tableProductos = $('#tablaProductos').DataTable({
        "language": { "url": "es-ES.json" },
        "order": [[2, "asc"]], // Por defecto ordenar por Nombre del Producto
        "pageLength": 10,
        "dom": 'itp', // Info, Tabla y Paginación
        "columnDefs": [
            { "orderable": false, "targets": [0, 4] } // Desactivar sort en Img y Acciones
        ]
    });

    // Conectar buscador personalizado con DataTables
    $('#inputBuscar').on('keyup', function() {
        tableProductos.search(this.value).draw();
    });
});

function nuevoProducto() {
    $('#formProducto')[0].reset();
    $('#prod_id').val('');
    $('#previewContainer').addClass('d-none');
    $('#imgPreview').attr('src', '');
    $('#modalTitle').text('Registrar Nuevo Producto');
    $('#modalProducto').modal('show');
}

function editarProducto(p) {
    $('#prod_id').val(p.id);
    $('#prod_codigo').val(p.codigo);
    $('#prod_nombre').val(p.nombre);
    $('#prod_precio').val(p.precio);
    
    // Vista previa de imagen
    if(p.imagen) {
        $('#imgPreview').attr('src', 'uploads/productos/' + p.imagen);
        $('#previewContainer').removeClass('d-none');
    } else {
        $('#previewContainer').addClass('d-none');
    }

    $('#modalTitle').text('Editar Producto');
    $('#modalProducto').modal('show');
}

function previewUpdate(input) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function(e) {
            $('#imgPreview').attr('src', e.target.result);
            $('#previewContainer').removeClass('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function guardarProducto() {
    let formData = new FormData($('#formProducto')[0]);
    $.ajax({
        url: 'guardar_producto.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(r) {
            if(r.trim() === "ok") location.reload();
            else alert("Respuesta: " + r);
        }
    });
}

function eliminarProducto(id) {
    if(confirm("¿Estás seguro de eliminar este producto?")) {
        $.post('guardar_producto.php', { eliminar_id: id }, function(r) {
            if(r.trim() === "ok") location.reload();
            else alert("Error: " + r);
        });
    }
}
</script>
</body>
</html>
