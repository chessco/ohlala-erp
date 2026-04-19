<?php
/**
 * PROYECTO: PEDIDOS OHLALA V3.0 (ARTISANAL BOULANGERIE)
 * DASHBOARD EJECUTIVO - ESTILO SITIO WEB ACTUAL
 */
include('conexion.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['autenticado'])) {
    header("Location: index.php");
    exit();
}

$nombre_vendedor   = isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : 'Usuario';
$rol_actual        = isset($_SESSION['usuario_rol']) ? $_SESSION['usuario_rol'] : '';
$esAdmin           = ($rol_actual === 'admin'); 

// --- ESTADÍSTICAS ---
$usuario_id_sesion = $_SESSION['usuario_id'];
$filtro_usuario = ($esAdmin) ? "" : " AND id_usuario = '$usuario_id_sesion'";

$res_pedidos = mysqli_query($conexion, "SELECT COUNT(*) as total FROM pedidos WHERE 1=1 $filtro_usuario");
$total_pedidos = mysqli_fetch_assoc($res_pedidos)['total'] ?? 0;

$res_clientes = mysqli_query($conexion, "SELECT COUNT(*) as total FROM clientes");
$total_clientes = mysqli_fetch_assoc($res_clientes)['total'] ?? 0;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#E7C182",
                        "on-primary": "#422C00",
                        "primary-container": "#BD9A5F",
                        "on-primary-container": "#4A3200",
                        "secondary": "#C8C7BD",
                        "on-secondary": "#30312A",
                        "secondary-container": "#494942",
                        "background": "#021619",
                        "on-background": "#D0E7EA",
                        "surface": "#021619",
                        "on-surface": "#D0E7EA",
                        "surface-variant": "#24383B",
                        "on-surface-variant": "#D1C5B5",
                        "outline": "#9A8F81",
                        "error": "#FFB4AB",
                        "on-error": "#690005",
                        "surface-container-lowest": "#001114",
                        "surface-container-low": "#0A1E21",
                        "surface-container": "#0E2326",
                        "surface-container-high": "#192D30",
                        "surface-container-highest": "#24383B"
                    },
                    fontFamily: {
                        headline: ["Playfair Display", "serif"],
                        body: ["Manrope", "sans-serif"],
                        label: ["Manrope", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.125rem"
                    }
                }
            }
        }
    </script>
    <style>
        /* DataTables Custom Styles for Artisanal Theme */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            background-color: #F7F5EB;
            border: 1px solid rgba(2, 22, 25, 0.1);
            border-radius: 4px;
            padding: 4px 8px;
            font-family: 'Manrope', sans-serif;
            font-size: 0.75rem;
            color: #021619;
        }
        .dataTables_wrapper .dataTables_info {
            font-family: 'Manrope', sans-serif;
            font-size: 0.75rem;
            color: rgba(2, 22, 25, 0.6);
            margin-top: 20px;
        }
        .pagination {
            margin-top: 20px;
            gap: 5px;
        }
        .pagination .page-link {
            background-color: transparent;
            border: 1px solid rgba(2, 22, 25, 0.05);
            color: #021619;
            font-family: 'Manrope', sans-serif;
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 4px !important;
            transition: all 0.2s;
        }
        .pagination .page-item.active .page-link {
            background-color: #BD9A5F !important;
            border-color: #BD9A5F !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(189, 154, 95, 0.2);
        }
        .pagination .page-link:hover {
            background-color: rgba(189, 154, 95, 0.1);
            border-color: #BD9A5F;
            color: #BD9A5F;
        }
        .table > :not(caption) > * > * {
            background-color: transparent !important;
            box-shadow: none !important;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            line-height: 1;
        }
        .torn-edge {
            mask-image: url("data:image/svg+xml,%3Csvg width='100' height='10' viewBox='0 0 100 10' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 10 L5 8 L10 10 L15 7 L20 10 L25 8 L30 10 L35 7 L40 10 L45 8 L50 10 L55 7 L60 10 L65 8 L70 10 L75 7 L80 10 L85 8 L90 10 L95 7 L100 10 V0 H0 Z' fill='black'/%3E%3C/svg%3E");
            mask-size: 100% 100%;
        }
        .glass-panel {
            backdrop-filter: blur(20px);
            background: rgba(36, 56, 59, 0.7);
        }
        body { min-height: 100dvh; }
        
        .modal-content {
            background-color: #0E2326;
            color: #D0E7EA;
            border: 1px solid rgba(231, 193, 130, 0.2);
            border-radius: 20px;
        }
    </style>
</head>
<body class="bg-background text-on-background font-body min-h-screen">
    <header class="flex justify-between items-center w-full px-6 py-4 z-50 bg-[#BD9A5F] top-0 shadow-lg shadow-[#021619]/20">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-[#021619] cursor-pointer" onclick="document.getElementById('sidebar-menu').classList.toggle('-translate-x-full')">menu</span>
            <h1 class="text-2xl font-headline font-bold text-[#F7F5EB] tracking-tight">Ohlala! Boulangerie Bistro</h1>
        </div>
        <div class="hidden md:flex gap-8 items-center">
            <nav class="flex gap-6">
                <a class="font-headline text-[#F7F5EB] border-b-2 border-[#F7F5EB] pb-1" href="dashboard.php">Inicio</a>
                <a class="font-headline text-[#F7F5EB]/80 hover:text-[#F7F5EB]" href="modules/purchasing/ui/list_requests.php">Compras</a>
                <a class="font-headline text-[#F7F5EB]/80 hover:text-[#F7F5EB]" href="#">Bistro</a>
            </nav>
            <button onclick="location.href='logout.php'" class="bg-[#021619] text-[#F7F5EB] px-6 py-2 font-headline font-medium hover:bg-[#021619]/90 active:scale-95 transition-all">
                Cerrar Sesión
            </button>
        </div>
    </header>

    <div class="flex min-h-[calc(100vh-72px)]">
        <aside id="sidebar-menu" class="fixed left-0 top-0 h-full flex flex-col p-4 z-40 bg-[#021619] w-64 shadow-2xl transition-transform duration-300 -translate-x-full md:translate-x-0 md:sticky md:top-0 md:h-[calc(100vh-72px)]">
            <div class="flex items-center gap-4 mb-10 px-2 pt-4">
                <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-primary">
                    <img src="icon.png" alt="User" class="w-full h-full object-cover"/>
                </div>
                <div>
                    <p class="font-headline font-bold text-[#F7F5EB] text-lg leading-tight"><?php echo $nombre_vendedor; ?></p>
                    <p class="text-[#BD9A5F] text-xs uppercase tracking-widest font-bold"><?php echo strtoupper($rol_actual); ?></p>
                </div>
            </div>
            <nav class="flex flex-col gap-2">
                <a class="flex items-center gap-4 px-4 py-3 bg-[#BD9A5F]/10 border-l-4 border-[#BD9A5F] text-[#BD9A5F] font-bold" href="dashboard.php">
                    <span class="material-symbols-outlined">dashboard</span><span>Panel Principal</span>
                </a>
                <a class="flex items-center gap-4 px-4 py-3 text-[#F7F5EB]/60 hover:bg-[#F7F5EB]/5 transition-colors" href="modules/purchasing/ui/list_requests.php">
                    <span class="material-symbols-outlined">shopping_cart</span><span>Compras (Audit)</span>
                </a>
                <a class="flex items-center gap-4 px-4 py-3 text-[#F7F5EB]/60 hover:bg-[#F7F5EB]/5 transition-colors" href="clientes.php">
                    <span class="material-symbols-outlined">group</span><span>Clientes</span>
                </a>
                <a class="flex items-center gap-4 px-4 py-3 text-[#F7F5EB]/60 hover:bg-[#F7F5EB]/5 transition-colors" href="productos.php">
                    <span class="material-symbols-outlined">bakery_dining</span><span>Productos</span>
                </a>
            </nav>
            <div class="mt-auto p-4 bg-surface-container-low rounded-xl">
                <p class="text-on-surface-variant text-xs mb-2">Estado del Horno</p>
                <div class="w-full bg-surface-container-highest h-1 rounded-full overflow-hidden">
                    <div class="bg-primary h-full w-4/5 shadow-[0_0_8px_rgba(231,193,130,0.5)]"></div>
                </div>
                <p class="text-right text-[10px] mt-1 text-primary">220°C - Óptimo</p>
            </div>
        </aside>

        <main class="flex-1 p-6 md:p-10 space-y-12 overflow-y-auto">
            <section class="relative h-80 w-full overflow-hidden rounded-xl shadow-2xl group">
                <img src="https://images.unsplash.com/photo-1509440159596-0249088772ff?q=80&w=2072&auto=format&fit=crop" alt="Hero" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"/>
                <div class="absolute inset-0 bg-gradient-to-t from-[#021619] via-transparent opacity-80"></div>
                <div class="absolute bottom-0 left-0 p-8">
                    <h2 class="text-5xl font-headline text-[#F7F5EB] mb-2">El Arte de la Masa Madre</h2>
                    <p class="text-primary text-xl font-headline italic">Control de Producción & Ventas ERP</p>
                </div>
            </section>

            <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-surface-container-low p-8 relative overflow-hidden group border border-[#F7F5EB]/5">
                    <span class="material-symbols-outlined text-6xl absolute top-0 right-0 p-4 opacity-10">payments</span>
                    <p class="font-label text-on-surface-variant text-sm tracking-widest uppercase mb-4">Ventas Totales</p>
                    <h3 class="font-headline text-4xl text-primaryTracking-tighter"><?php echo number_format($total_pedidos); ?></h3>
                    <div class="mt-4 flex items-center gap-2 text-green-400 text-sm">
                        <span class="material-symbols-outlined text-xs">trending_up</span><span>Live Metrics</span>
                    </div>
                </div>
                <div class="bg-surface-container-high p-8 relative border-t-4 border-primary shadow-xl">
                    <span class="material-symbols-outlined text-6xl absolute top-0 right-0 p-4 opacity-10">shopping_cart</span>
                    <p class="font-label text-on-surface-variant text-sm tracking-widest uppercase mb-4">Clientes Registrados</p>
                    <h3 class="font-headline text-4xl text-[#F7F5EB] tracking-tighter"><?php echo number_format($total_clientes); ?></h3>
                    <div class="mt-4 flex items-center gap-2 text-primary text-sm">
                        <span class="material-symbols-outlined text-xs">group</span><span>Base de datos activa</span>
                    </div>
                </div>
                <div class="bg-surface-container-low p-8 relative border border-[#F7F5EB]/5">
                    <span class="material-symbols-outlined text-6xl absolute top-0 right-0 p-4 opacity-10">warning</span>
                    <p class="font-label text-on-surface-variant text-sm tracking-widest uppercase mb-4">Módulo Compras</p>
                    <h3 class="font-headline text-4xl text-error">ACTIVO</h3>
                    <div class="mt-4 flex items-center gap-2 text-primary text-sm underline cursor-pointer" onclick="location.href='modules/purchasing/ui/list_requests.php'">
                        <span class="material-symbols-outlined text-xs">arrow_forward</span><span>Gestionar Aprobaciones</span>
                    </div>
                </div>
            </section>

            <!-- Table Section with Torn Edge Effect -->
            <section class="bg-[#F7F5EB] text-[#021619] p-10 shadow-2xl relative rounded-t-xl">
                <div class="absolute bottom-[-10px] left-0 w-full h-4 bg-[#F7F5EB] torn-edge transform z-10"></div>
                <div class="flex flex-col md:flex-row justify-between items-md-center mb-10 gap-6">
                    <div>
                        <h2 class="font-headline text-3xl">Gestión de Pedidos</h2>
                        <p class="text-[#021619]/60 font-body text-sm">Registro histórico y control de operaciones.</p>
                    </div>
                    <div class="flex gap-4">
                        <button onclick="duplicarUltimo()" class="border-2 border-[#021619]/10 px-6 py-2 font-bold text-xs uppercase tracking-widest hover:bg-[#021619]/5 transition-all">Duplicar Último</button>
                        <button onclick="prepararNuevoPedido()" class="bg-[#BD9A5F] text-white px-8 py-3 font-headline font-bold shadow-lg hover:translate-y-[-1px] transition-all">NUEVO PEDIDO</button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table id="tablaPedidosV3" class="w-full text-left">
                        <thead>
                            <tr class="border-b-2 border-[#021619]/10 text-[10px] uppercase tracking-[0.2em] font-black text-[#021619]/40">
                                <th class="pb-4 px-4">Folio</th>
                                <th class="pb-4 px-4">Fecha</th>
                                <th class="pb-4 px-4">Cliente</th>
                                <th class="pb-4 px-4">Estado</th>
                                <th class="pb-4 px-4 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="font-headline text-lg">
                            <?php
                            $res_tabla = mysqli_query($conexion, "SELECT * FROM pedidos $filtro_usuario ORDER BY id DESC");
                            if($res_tabla):
                                while($row = mysqli_fetch_assoc($res_tabla)):
                                    $badge_class = "bg-[#BD9A5F]"; // Default
                                    if($row['estado'] == 'Cancelado') $badge_class = "bg-red-500";
                                    if($row['estado'] == 'Completado') $badge_class = "bg-emerald-600";
                                    if($row['estado'] == 'Pendiente') $badge_class = "bg-amber-500";
                                    if($row['estado'] == 'Incompleto') $badge_class = "bg-blue-600";
                                    if($row['estado'] == 'Preparando') $badge_class = "bg-cyan-600";
                            ?>
                            <tr class="hover:bg-[#021619]/5 transition-colors group">
                                <td class="py-6 px-4 font-bold border-b border-[#021619]/5 text-[#BD9A5F]"><?php echo $row['folio']; ?></td>
                                <td class="py-6 px-4 border-b border-[#021619]/5 text-sm font-body font-medium opacity-70"><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                                <td class="py-6 px-4 border-b border-[#021619]/5">
                                    <span class="text-sm font-body font-bold text-[#021619]"><?php echo htmlspecialchars($row['cliente_nombre']); ?></span>
                                </td>
                                <td class="py-6 px-4 border-b border-[#021619]/5">
                                    <span class="px-3 py-1 <?php echo $badge_class; ?> text-[#F7F5EB] text-[9px] font-bold uppercase tracking-widest rounded shadow-sm">
                                        <?php echo $row['estado']; ?>
                                    </span>
                                </td>
                                <td class="py-6 px-4 border-b border-[#021619]/5 text-center">
                                    <div class="flex justify-center gap-2 transition-opacity">
                                        <button onclick="editarPedido(<?php echo $row['id']; ?>, '<?php echo $row['estado']; ?>')" class="w-8 h-8 rounded-full bg-[#BD9A5F]/20 text-[#BD9A5F] flex items-center justify-center hover:bg-[#BD9A5F] hover:text-white transition-all shadow-sm">
                                            <span class="material-symbols-outlined text-sm">edit</span>
                                        </button>
                                        <?php if($esAdmin): ?>
                                        <button onclick="eliminarPedido(<?php echo $row['id']; ?>, '<?php echo $row['folio']; ?>')" class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center hover:bg-red-600 hover:text-white transition-all shadow-sm">
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- MODAL PEDIDO (ESTILO ARTISANAL) -->
    <div class="modal fade" id="modalPedido" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content overflow-hidden border-0 shadow-2xl">
                <div class="bg-[#BD9A5F] px-8 py-6 flex justify-between items-center text-white">
                    <h5 class="font-headline text-2xl font-bold" id="tituloModal">Detalle de Pedido</h5>
                    <button type="button" class="text-white/80 hover:text-white" data-bs-dismiss="modal">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
                <div class="p-8 bg-[#0E2326]">
                    <form id="formPedido">
                        <input type="hidden" id="pedido_id" name="id">
                        
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-8">
                            <div class="md:col-span-6">
                                <label class="block text-primary text-[10px] uppercase tracking-widest font-bold mb-2">Cliente</label>
                                <input type="text" id="cliente_nombre" name="cliente_nombre" class="w-full bg-[#021619] border border-primary/20 text-[#F7F5EB] px-4 py-3 rounded-lg focus:outline-none focus:border-primary transition-colors" onkeyup="buscarClienteCartera(this.value)" placeholder="Buscar cliente..." autocomplete="off" required>
                                <div id="resultadosCliente" class="absolute z-50 w-full mt-1 bg-[#24383B] rounded-lg shadow-2xl border border-primary/10 hidden max-h-60 overflow-y-auto"></div>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-primary text-[10px] uppercase tracking-widest font-bold mb-2">Fecha</label>
                                <input type="date" id="fecha_pedido" name="fecha" class="w-full bg-[#021619] border border-primary/20 text-[#F7F5EB] px-4 py-3 rounded-lg focus:outline-none focus:border-primary transition-colors" required>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-primary text-[10px] uppercase tracking-widest font-bold mb-2">Estado</label>
                                <select id="estado" name="estado" class="w-full bg-[#021619] border border-primary/20 text-[#F7F5EB] px-4 py-3 rounded-lg focus:outline-none focus:border-primary transition-colors">
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="Preparando">Preparando</option>
                                    <option value="Completado">Completado</option>
                                    <option value="Incompleto">Incompleto</option>
                                    <option value="Cancelado">Cancelado</option>
                                </select>
                            </div>
                        </div>

                        <div class="bg-[#021619]/50 p-6 rounded-2xl mb-8 border border-primary/5">
                            <h6 class="text-primary text-[10px] uppercase tracking-[0.2em] font-black mb-6">Añadir Producto al Pedido</h6>
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                <div class="md:col-span-5 relative">
                                    <input type="text" id="inputBuscarProd" onkeyup="buscarProducto(this.value)" class="w-full bg-[#021619] border-b border-primary/20 text-[#F7F5EB] px-1 py-3 focus:outline-none focus:border-primary transition-colors" placeholder="Nombre del postre...">
                                    <div id="resultadosBusqueda" class="absolute z-50 w-full mt-1 bg-[#24383B] rounded-lg shadow-2xl border border-primary/10 hidden max-h-60 overflow-y-auto"></div>
                                </div>
                                <div class="md:col-span-2">
                                    <input type="number" id="temp_cantidad" class="w-full bg-[#021619] border-b border-primary/20 text-[#F7F5EB] px-1 py-3 text-center focus:outline-none focus:border-primary transition-colors" value="1" min="1">
                                </div>
                                <div class="md:col-span-3">
                                    <input type="text" id="temp_comentario" class="w-full bg-[#021619] border-b border-primary/20 text-[#F7F5EB] px-1 py-3 focus:outline-none focus:border-primary transition-colors" placeholder="Opcional...">
                                </div>
                                <div class="md:col-span-2">
                                    <button type="button" onclick="confirmarSeleccion()" class="w-full h-12 bg-emerald-600 text-white font-bold rounded-lg hover:bg-emerald-500 transition-colors">
                                        <span class="material-symbols-outlined">add</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- LISTA DE PRODUCTOS -->
                        <div class="max-h-60 overflow-y-auto mb-8 pr-2 custom-scrollbar">
                            <table class="w-full">
                                <tbody id="listaProductos" class="divide-y divide-primary/5">
                                    <!-- Dinámico -->
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-end gap-4">
                            <button type="button" data-bs-dismiss="modal" class="px-8 py-3 text-primary/60 font-bold hover:text-primary transition-colors">CANCELAR</button>
                            <button type="button" onclick="guardarTodo()" class="bg-[#BD9A5F] text-white px-10 py-3 font-headline font-bold shadow-2xl hover:scale-105 active:scale-95 transition-all">GUARDAR PEDIDO</button>
                        </div>

                        <input type="hidden" id="temp_id">
                        <input type="hidden" id="temp_nombre">
                        <input type="hidden" id="temp_img">
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JS LOGIC (Parity with dashboard.php) -->
    <script>
        $(document).ready(function() {
            $('#tablaPedidosV3').DataTable({ 
                "language": {
                    "sProcessing":     "Procesando...",
                    "sLengthMenu":     "Mostrar _MENU_ registros",
                    "sZeroRecords":    "No se encontraron resultados",
                    "sEmptyTable":     "Ningún dato disponible en esta tabla",
                    "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                    "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
                    "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
                    "sSearch":         "Buscar:",
                    "sInfoPostFix":    "",
                    "sUrl":            "",
                    "sInfoThousands":  ",",
                    "sLoadingRecords": "Cargando...",
                    "oPaginate": {
                        "sFirst":    "Primero",
                        "sLast":     "Último",
                        "sNext":     "Siguiente",
                        "sPrevious": "Anterior"
                    },
                    "oAria": {
                        "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                    }
                }, 
                "order": [[0, "desc"]],
                "pageLength": 10
            });
        });

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
            
            let fila = `<tr id="prod_${id}" class="group">
                <td class="py-4">
                    <div class="flex items-center gap-4">
                        <img src="${img}" class="w-10 h-10 rounded-lg object-cover border border-primary/20">
                        <span class="text-sm font-bold text-[#F7F5EB]">${nom}</span>
                    </div>
                </td>
                <td class="py-4">
                    <input type="number" name="cant[]" class="bg-transparent border-b border-primary/20 text-white text-center w-16 focus:outline-none focus:border-primary" value="${cant}" min="1">
                </td>
                <td class="py-4">
                    <input type="text" name="comentario[]" class="bg-transparent border-b border-primary/20 text-white/60 text-xs w-full focus:outline-none focus:border-primary" value="${coment}" placeholder="Añadir nota...">
                </td>
                <td class="py-4 text-right">
                    <button type="button" class="text-red-400 hover:text-red-500" onclick="$(this).closest('tr').remove()">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </td>
                <input type="hidden" name="p_id[]" value="${id}">
            </tr>`;
            
            $('#listaProductos').append(fila);
            $('#temp_id').val(''); $('#temp_img').val('');
            $('#inputBuscarProd').val('').focus(); 
            $('#temp_cantidad').val(1); $('#temp_comentario').val('');
        }

        function prepararNuevoPedido() {
            $('#formPedido')[0].reset(); 
            $('#pedido_id').val(''); 
            $('#listaProductos').empty();
            $('#tituloModal').text('Registrar Nuevo Pedido'); 
            $('#fecha_pedido').val(new Date().toISOString().split('T')[0]);
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
                    let fila = `<tr id="prod_${item.id}">
                        <td class="py-4">
                            <div class="flex items-center gap-4">
                                <img src="${img_path}" class="w-10 h-10 rounded-lg object-cover border border-primary/20">
                                <span class="text-sm font-bold text-[#F7F5EB]">${item.nombre}</span>
                            </div>
                        </td>
                        <td class="py-4">
                            <input type="number" name="cant[]" class="bg-transparent border-b border-primary/20 text-white text-center w-16 focus:outline-none focus:border-primary" value="${item.cantidad}" min="1">
                        </td>
                        <td class="py-4">
                            <input type="text" name="comentario[]" class="bg-transparent border-b border-primary/20 text-white/60 text-xs w-full focus:outline-none focus:border-primary" value="${item.comentario || ''}" placeholder="Añadir nota...">
                        </td>
                        <td class="py-4 text-right">
                            <button type="button" class="text-red-400 hover:text-red-500" onclick="$(this).closest('tr').remove()">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        </td>
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
                    if(r.trim() === "ok") location.reload(); else alert("Error al eliminar: " + r);
                });
            }
        }

        function duplicarUltimo() {
            if (confirm("¿Deseas duplicar el último pedido registrado?")) {
                $.post('duplicar_pedido.php', function(r) {
                    if (r.trim() === "ok") location.reload(); else alert("Error al duplicar: " + r);
                });
            }
        }

        function guardarTodo() {
            const data = $('#formPedido').serialize();
            $.post('actualizar_pedido.php', data, function(r) {
                if(r.trim() == "ok") location.reload(); else alert("Error al guardar pedido: " + r);
            });
        }
    </script>

    <!-- Mobile Nav -->
    <nav class="fixed bottom-0 w-full z-50 flex justify-around items-center px-4 py-3 border-t border-[#F7F5EB]/5 md:hidden bg-[#021619]/95 backdrop-blur-xl rounded-t-2xl shadow-2xl">
        <a class="flex flex-col items-center text-[#BD9A5F] font-bold" href="dashboard.php"><span class="material-symbols-outlined">home</span><span class="text-[10px]">Inicio</span></a>
        <a class="flex flex-col items-center text-[#F7F5EB]/50" href="modules/purchasing/ui/list_requests.php"><span class="material-symbols-outlined">shopping_cart</span><span class="text-[10px]">Compras</span></a>
        <a class="flex flex-col items-center text-[#F7F5EB]/50" href="productos.php"><span class="material-symbols-outlined">bakery_dining</span><span class="text-[10px]">Stock</span></a>
        <a class="flex flex-col items-center text-[#F7F5EB]/50" href="logout.php"><span class="material-symbols-outlined">logout</span><span class="text-[10px]">Salir</span></a>
    </nav>
</body>
</html>
