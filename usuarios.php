<?php
include('conexion.php');
session_start();

// 1. CONTROL DE ACCESO (ADMIN SOLAMENTE)
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Ohlala v2.1 - Usuarios y Cartera</title>
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

        body { background-color: var(--bg-deep); color: var(--text-main); font-family: 'Manrope', sans-serif; transition: background-color 0.3s, color 0.3s; }
        .font-headline { font-family: 'Playfair Display', serif; }
        
        .navbar-custom { background-color: var(--card-blue); border-bottom: 1px solid var(--border-color); padding: 1rem 2rem; }
        .card-main { background: var(--card-blue); border-radius: 20px; padding: 25px; border: 1px solid var(--border-color); color: var(--text-main); }
        .table { color: var(--text-main) !important; border-color: var(--border-color); }
        .modal-content { background-color: var(--card-blue); color: var(--text-main); border: 1px solid var(--border-color); }
        .form-control, .form-select { background-color: rgba(0,0,0,0.1); border: 1px solid var(--border-color); color: var(--text-main); }
        [data-theme="dark"] .form-control, [data-theme="dark"] .form-select { background-color: rgba(0,0,0,0.3); }

        /* BUSCADOR FLOTANTE */
        #resBusquedaClientes { 
            position: absolute; width: 100%; z-index: 1200; 
            background: var(--card-blue); border: 1px solid var(--border-color); max-height: 250px; overflow-y: auto;
            border-radius: 0 0 10px 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.6);
        }
        .cliente-item { transition: 0.2s; border-bottom: 1px solid var(--border-color); color: var(--text-main); }
        .cliente-item:hover { background: rgba(255,255,255,0.05); }
        .text-silver { color: var(--text-silver) !important; }
        
        [data-theme="light"] .table-dark, [data-theme="ohlala"] .table-dark { background-color: var(--card-blue); color: var(--text-main) !important; }
        [data-theme="light"] .btn-close, [data-theme="ohlala"] .btn-close { filter: invert(1); }
        [data-theme="light"] .text-white, [data-theme="ohlala"] .text-white { color: var(--text-main) !important; }
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
                    <a href="productos.php" class="block px-4 py-3 text-[10px] uppercase font-black tracking-widest hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5 no-underline text-[#F7F5EB]">Productos</a>
                    <a href="modules/purchasing/ui/suppliers.php" class="block px-4 py-3 text-[10px] uppercase font-black tracking-widest hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5 no-underline text-[#F7F5EB]">Proveedores</a>
                    <a href="modules/purchasing/ui/items.php" class="block px-4 py-3 text-[10px] uppercase font-black tracking-widest hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5 no-underline text-[#F7F5EB]">Insumos</a>
                    <?php if($esAdmin): ?>
                    <a href="usuarios.php" class="block px-4 py-3 text-[10px] uppercase font-black tracking-widest hover:bg-[#BD9A5F] hover:text-[#021619] transition-all no-underline text-[#F7F5EB] bg-[#BD9A5F]/10">Usuarios</a>
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

<div class="container-fluid px-4">
    <div class="card-main shadow-lg">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="m-0"><i class="fa-solid fa-user-shield me-2 text-primary"></i> Gestión de Usuarios</h5>
                <p class="text-silver small mb-0">Configura accesos y carteras de clientes</p>
            </div>
            <div class="d-flex gap-2">
                <a href="importar_usuarios.php" class="btn btn-outline-info shadow">
                    <i class="fa-solid fa-file-excel me-1"></i> Excel
                </a>
                <button class="btn btn-primary px-4 shadow" onclick="nuevoUsuario()">
                    <i class="fa-solid fa-user-plus me-1"></i> Nuevo Usuario
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr class="text-secondary small uppercase">
                        <th>Usuario / Nombre</th>
                        <th>Contacto</th>
                        <th>Rol</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = mysqli_query($conexion, "SELECT * FROM usuarios ORDER BY id DESC");
                    while($u = mysqli_fetch_assoc($res)):
                        $badge = 'bg-secondary';
                        if($u['rol'] == 'admin') $badge = 'bg-primary';
                        if($u['rol'] == 'vendedor') $badge = 'bg-success';
                    ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-info"><?php echo htmlspecialchars($u['nombre_completo']); ?></div>
                            <code class="small text-silver"><?php echo htmlspecialchars($u['usuario']); ?></code>
                        </td>
                        <td>
                            <div class="small"><?php echo htmlspecialchars($u['correo']); ?></div>
                            <div class="small text-secondary"><?php echo htmlspecialchars($u['telefono']); ?></div>
                        </td>
                        <td><span class="badge <?php echo $badge; ?>"><?php echo strtoupper($u['rol']); ?></span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-warning border-0" onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($u)); ?>)">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <?php if($u['id'] != $_SESSION['usuario_id']): ?>
                            <button class="btn btn-sm btn-outline-danger border-0" onclick="eliminarUsuario(<?php echo $u['id']; ?>)">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg">
            <form id="formUsuario">
                <input type="hidden" name="id" id="user_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="small text-silver">Nombre Completo</label>
                            <input type="text" name="nombre_completo" id="user_nombre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-silver">Correo</label>
                            <input type="email" name="correo" id="user_correo" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-silver">Teléfono</label>
                            <input type="text" name="telefono" id="user_telefono" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="small text-silver">Usuario (Login)</label>
                            <input type="text" name="usuario" id="user_login" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-silver">Contraseña</label>
                            <input type="password" name="password" id="user_pass" class="form-control">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="small text-silver">Rol del Sistema</label>
                            <select name="rol" id="user_rol" class="form-select">
                                <option value="vendedor">Vendedor</option>
                                <option value="admin">Administrador</option>
                                <option value="mayorista">Mayorista</option>
                                <option value="sucursal">Sucursal</option>
                                <option value="cliente">Cliente</option>
                            </select>
                        </div>
                    </div>

                    <div class="border-top border-secondary pt-3 mt-2">
                        <label class="fw-bold text-accent mb-2"><i class="fa-solid fa-magnifying-glass me-1"></i> Asignar Clientes a Cartera</label>
                        <div class="position-relative mb-3">
                            <input type="text" id="busquedaCliente" class="form-control" placeholder="Escribe para buscar clientes..." onkeyup="buscarClientesCartera(this.value)">
                            <div id="resBusquedaClientes" style="display:none;"></div>
                        </div>

                        <div class="table-responsive" style="max-height: 250px; border: 1px solid #334155; border-radius: 10px;">
                            <table class="table table-sm mb-0">
                                <thead class="bg-dark text-silver">
                                    <tr class="small">
                                        <th class="ps-3">Código</th>
                                        <th>Cliente</th>
                                        <th width="50" class="text-center"></th>
                                    </tr>
                                </thead>
                                <tbody id="listaCartera"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer gap-2">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1 py-2 fw-bold" data-bs-dismiss="modal">CERRAR</button>
                    <button type="button" class="btn btn-primary flex-grow-1 py-2 fw-bold" onclick="guardarUsuarioCartera()">GUARDAR CAMBIOS</button>
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

// BUSCADOR EN TIEMPO REAL
function buscarClientesCartera(t) {
    if(t.length > 0) {
        $.post('buscar_clientes_ajax.php', { term: t, origen: 'cartera' }, function(data) {
            $('#resBusquedaClientes').html(data).show();
        });
    } else {
        $('#resBusquedaClientes').hide();
    }
}

// AGREGAR CON BOTÓN
function agregarACartera(id, codigo, nombre) {
    if ($(`#cli_row_${id}`).length > 0) {
        alert("Ya está en la lista."); return;
    }
    let fila = `<tr id="cli_row_${id}">
        <td class="ps-3 small text-info">${codigo}</td>
        <td class="small">${nombre}</td>
        <td class="text-center">
            <button type="button" class="btn btn-sm text-danger border-0" onclick="removerDeCartera(${id})"><i class="fa-solid fa-trash-can"></i></button>
            <input type="hidden" name="clientes_cartera[]" value="${id}">
        </td>
    </tr>`;
    $('#listaCartera').append(fila);
    $('#resBusquedaClientes').hide();
    $('#busquedaCliente').val('').focus();
}

function removerDeCartera(id) { $(`#cli_row_${id}`).remove(); }

function nuevoUsuario() {
    $('#formUsuario')[0].reset(); $('#user_id').val(''); $('#listaCartera').empty();
    $('#modalTitle').text('Nuevo Usuario'); $('#modalUsuario').modal('show');
}

function editarUsuario(u) {
    $('#user_id').val(u.id); $('#user_nombre').val(u.nombre_completo);
    $('#user_correo').val(u.correo); $('#user_telefono').val(u.telefono);
    $('#user_login').val(u.usuario); $('#user_rol').val(u.rol);
    $('#user_pass').val(''); $('#modalTitle').text('Editar Usuario');
    
    // Cargar Cartera
    $.post('obtener_cartera_usuario.php', { usuario_id: u.id }, function(data) {
        $('#listaCartera').html(data);
    });
    $('#modalUsuario').modal('show');
}

function guardarUsuarioCartera() {
    $.post('guardar_usuario_cartera.php', $('#formUsuario').serialize(), function(r) {
        if(r.trim() === "ok") location.reload(); else alert(r);
    });
}

function eliminarUsuario(id) {
    if(confirm("¿Eliminar usuario?")) {
        $.post('guardar_usuario_cartera.php', { eliminar_id: id }, function(r) {
            if(r.trim() === "ok") location.reload(); else alert(r);
        });
    }
}
</script>
</body>
</html>