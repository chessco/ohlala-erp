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
$autoloader_file = __DIR__ . '/modules/purchasing/src/Infrastructure/SettingsRepository.php';
if (file_exists($autoloader_file)) {
    spl_autoload_register(function ($class) {
        if (strpos($class, 'Purchasing\\') === 0) {
            $file = __DIR__ . '/modules/purchasing/src/' . str_replace(['Purchasing\\', '\\'], ['', '/'], $class) . '.php';
            if (file_exists($file)) require $file;
        }
    });
}
use Purchasing\Infrastructure\SettingsRepository;
$settingsRepo = new SettingsRepository($conexion);
$dashboard_autohide_hero = $settingsRepo->get('dashboard_autohide_hero_on_move', '0');

// --- ESTADÍSTICAS ---
$usuario_id_sesion = $_SESSION['usuario_id'];
$filtro_usuario = ($esAdmin) ? "" : " AND id_usuario = '$usuario_id_sesion'";

$res_pedidos = mysqli_query($conexion, "SELECT COUNT(*) as total FROM pedidos WHERE 1=1 $filtro_usuario");
$total_pedidos = mysqli_fetch_assoc($res_pedidos)['total'] ?? 0;

$res_clientes = mysqli_query($conexion, "SELECT COUNT(*) as total FROM clientes");
$total_clientes = mysqli_fetch_assoc($res_clientes)['total'] ?? 0;

// --- COMPRAS/PEDIDOS STATS ---
$res_pur = mysqli_query($conexion, "SELECT COUNT(*) as total, SUM(total_amount) as inversion FROM pur_orders WHERE status != 'cancelled'");
$pur_stats = mysqli_fetch_assoc($res_pur);
$total_compras_conteo = $pur_stats['total'] ?? 0;
$total_compras_monto = $pur_stats['inversion'] ?? 0;

// --- KPI PREMIUM (ALTA DIRECCIÓN) ---
// 1. Ingresos Reales (Ventas) del mes actual
$first_day_month = date('Y-m-01');
$res_ingresos = mysqli_query($conexion, "
    SELECT SUM(pd.cantidad * p.precio) as total_ventas 
    FROM pedido_detalles pd 
    JOIN productos p ON pd.producto_id = p.id 
    JOIN pedidos ped ON pd.pedido_id = ped.id 
    WHERE ped.estado != 'Cancelado' AND ped.fecha >= '$first_day_month'
");
$ingresos_mes = mysqli_fetch_assoc($res_ingresos)['total_ventas'] ?? 0;

// 2. Gasto Pendiente (Pipeline de Aprobación)
$res_pipeline = mysqli_query($conexion, "SELECT SUM(total_amount) as total FROM pur_requests WHERE status IN ('pending', 'partially_approved')");
$pipeline_monto = mysqli_fetch_assoc($res_pipeline)['total'] ?? 0;

// 3. Distribución para Pie Chart (Gasto por Proveedor)
$res_dist = mysqli_query($conexion, "
    SELECT s.name as label, SUM(o.total_amount) as value 
    FROM pur_orders o 
    JOIN pur_suppliers s ON o.supplier_id = s.id 
    WHERE o.status != 'cancelled'
    GROUP BY o.supplier_id 
    ORDER BY value DESC 
    LIMIT 5
");
$pie_labels = [];
$pie_values = [];
while($row = mysqli_fetch_assoc($res_dist)) {
    $pie_labels[] = $row['label'];
    $pie_values[] = (float)$row['value'];
}
// Si no hay datos, poner placeholders
if(empty($pie_labels)) {
    $pie_labels = ['Sin datos'];
    $pie_values = [1];
}

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
            <h1 class="text-2xl font-headline font-bold text-[#F7F5EB] tracking-tight">Ohlala! Boulangerie Bistro (V3.1)</h1>
        </div>
        <div class="hidden md:flex gap-8 items-center">
            <nav class="flex gap-6 items-center">
                <a class="font-headline text-[#F7F5EB] border-b-2 border-[#F7F5EB] pb-1" href="dashboardv3.php">Inicio</a>
                <a class="font-headline text-[#F7F5EB]/80 hover:text-[#F7F5EB]" href="pedido.php">Pedidos</a>
                
                <!-- Dropdown Catálogos -->
                <div class="relative group">
                    <button class="font-headline text-[#F7F5EB]/80 hover:text-[#F7F5EB] flex items-center gap-1 py-1">
                        Catálogos <span class="material-symbols-outlined text-sm">expand_more</span>
                    </button>
                    <div class="absolute left-0 mt-2 w-56 bg-[#021619] border border-[#BD9A5F]/30 shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50 rounded-sm overflow-hidden">
                        <a href="clientes.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5">
                            <span class="material-symbols-outlined text-[16px]">group</span> Clientes
                        </a>
                        <a href="productos.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5">
                            <span class="material-symbols-outlined text-[16px]">bakery_dining</span> Productos
                        </a>
                        <a href="modules/purchasing/ui/suppliers.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5">
                            <span class="material-symbols-outlined text-[16px]">handshake</span> Proveedores
                        </a>
                        <a href="modules/purchasing/ui/items.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5">
                            <span class="material-symbols-outlined text-[16px]">inventory_2</span> Insumos
                        </a>
                        <?php if($esAdmin): ?>
                            <a href="usuarios.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-[#021619] transition-all">
                                <span class="material-symbols-outlined text-[16px]">person_outline</span> Usuarios
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <a class="font-headline text-[#F7F5EB]/80 hover:text-[#F7F5EB]" href="modules/purchasing/ui/list_requests.php">Compras</a>
                <?php if($esAdmin): ?>
                    <a class="font-headline text-[#F7F5EB]/80 hover:text-[#F7F5EB]" href="modules/purchasing/ui/settings.php">Configuración</a>
                <?php endif; ?>
                
                <!-- Notification Bell -->
                <div class="relative ml-4">
                    <button id="notifBell" class="flex items-center justify-center p-2 rounded-full hover:bg-white/10 transition-all relative">
                        <span class="material-symbols-outlined text-[#F7F5EB] text-2xl">notifications</span>
                        <span id="notifBadge" class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-[#BD9A5F] hidden"></span>
                    </button>
                    
                    <!-- Notification Panel -->
                    <div id="notifPanel" class="absolute right-0 mt-4 w-80 bg-[#021619] border border-[#BD9A5F]/30 shadow-2xl rounded-sm py-4 hidden z-[100] backdrop-blur-xl">
                        <header class="px-4 pb-3 border-b border-white/5 flex justify-between items-center">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-[#BD9A5F]">Notificaciones</h3>
                            <span id="notifCount" class="text-[8px] text-white/30 font-bold uppercase">0 nuevas</span>
                        </header>
                        <div id="notifList" class="max-h-96 overflow-y-auto custom-scrollbar">
                            <div class="p-8 text-center text-white/20 italic text-xs">Cargando avisos...</div>
                        </div>
                        <footer class="px-4 pt-3 border-t border-white/5">
                            <a href="modules/purchasing/ui/list_requests.php" class="block text-center text-[10px] font-black uppercase text-[#BD9A5F] hover:text-white transition-colors">Ver todas las solicitudes</a>
                        </footer>
                    </div>
                </div>
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
                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[#BD9A5F]/40 mt-6 mb-2 px-4 italic">Secciones</p>
                <a class="flex items-center gap-4 px-4 py-3 bg-[#BD9A5F]/10 border-l-4 border-[#BD9A5F] text-[#BD9A5F] font-bold" href="dashboardv3.php">
                    <span class="material-symbols-outlined">dashboard</span><span>Panel Principal</span>
                </a>
                <a class="flex items-center gap-4 px-4 py-3 text-[#F7F5EB]/60 hover:text-[#BD9A5F] hover:bg-[#F7F5EB]/5 transition-all group" href="pedido.php">
                    <span class="material-symbols-outlined text-[#BD9A5F]">receipt_long</span>
                    <span class="font-headline font-bold">Pedidos</span>
                </a>
                <div class="mt-4 px-4 flex justify-between items-center cursor-pointer group py-3 hover:bg-[#F7F5EB]/5 transition-all rounded-r-lg" onclick="toggleSubmenu('compras-v3-submenu')">
                    <div class="flex items-center gap-4">
                        <span class="material-symbols-outlined text-[#BD9A5F]">shopping_cart</span>
                        <p class="font-headline font-bold text-[#F7F5EB]/60 group-hover:text-[#BD9A5F] transition-all">Compras</p>
                    </div>
                    <span class="material-symbols-outlined text-[#BD9A5F]/40 group-hover:text-[#BD9A5F] transition-all text-sm" id="compras-v3-icon">expand_more</span>
                </div>
                <div id="compras-v3-submenu" class="flex flex-col gap-1 hidden">
                    <a class="flex items-center gap-4 px-4 py-3 text-[#F7F5EB]/60 hover:bg-[#F7F5EB]/5 transition-colors" href="modules/purchasing/ui/list_requests.php">
                        <span class="material-symbols-outlined text-sm">description</span><span>Solicitudes</span>
                    </a>
                    <a class="flex items-center gap-4 px-4 py-3 text-[#F7F5EB]/60 hover:bg-[#F7F5EB]/5 transition-colors" href="modules/purchasing/ui/list_orders.php">
                        <span class="material-symbols-outlined text-sm">receipt_long</span><span>Órdenes de Compra</span>
                    </a>
                </div>


                <div class="mt-4 px-4 flex justify-between items-center cursor-pointer group py-3 hover:bg-[#F7F5EB]/5 transition-all rounded-r-lg" onclick="toggleSubmenu('catalogos-submenu')">
                    <div class="flex items-center gap-4">
                        <span class="material-symbols-outlined text-[#BD9A5F]">menu_book</span>
                        <p class="font-headline font-bold text-[#F7F5EB]/60 group-hover:text-[#BD9A5F] transition-all">Catálogos</p>
                    </div>
                    <span class="material-symbols-outlined text-[#BD9A5F]/40 group-hover:text-[#BD9A5F] transition-all text-sm" id="catalogos-icon">expand_more</span>
                </div>
                <div id="catalogos-submenu" class="flex flex-col gap-1 hidden">
                    <a class="flex items-center gap-4 px-4 py-3 text-[#F7F5EB]/60 hover:bg-[#F7F5EB]/5 transition-colors" href="clientes.php">
                        <span class="material-symbols-outlined">group</span><span>Clientes</span>
                    </a>
                    <a class="flex items-center gap-4 px-4 py-3 text-[#F7F5EB]/60 hover:bg-[#F7F5EB]/5 transition-colors" href="productos.php">
                        <span class="material-symbols-outlined">bakery_dining</span><span>Productos</span>
                    </a>
                    <a class="flex items-center gap-4 px-4 py-3 text-[#F7F5EB]/60 hover:bg-[#F7F5EB]/5 transition-colors" href="modules/purchasing/ui/suppliers.php">
                        <span class="material-symbols-outlined">handshake</span><span>Proveedores</span>
                    </a>
                    <a class="flex items-center gap-4 px-4 py-3 text-[#F7F5EB]/60 hover:bg-[#F7F5EB]/5 transition-colors" href="modules/purchasing/ui/items.php">
                        <span class="material-symbols-outlined">inventory_2</span><span>Insumos</span>
                    </a>
                    <?php if($esAdmin): ?>
                        <a class="flex items-center gap-4 px-4 py-3 text-[#F7F5EB]/60 hover:bg-[#F7F5EB]/5 transition-colors" href="usuarios.php">
                            <span class="material-symbols-outlined">person_outline</span><span>Usuarios</span>
                        </a>
                    <?php endif; ?>
                </div>
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
            <div id="hero-container" class="transition-all duration-700 ease-in-out overflow-hidden max-h-[320px] opacity-100">
                <section class="relative h-80 w-full overflow-hidden rounded-xl shadow-2xl group">
                    <img src="https://images.unsplash.com/photo-1509440159596-0249088772ff?q=80&w=2072&auto=format&fit=crop" alt="Hero" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"/>
                    <div class="absolute inset-0 bg-gradient-to-t from-[#021619] via-transparent opacity-80"></div>
                    <div class="absolute bottom-0 left-0 p-8">
                        <h2 class="text-5xl font-headline text-[#F7F5EB] mb-2">El Arte de la Masa Madre</h2>
                        <p class="text-primary text-xl font-headline italic">Control de Producción & Ventas ERP</p>
                    </div>
                </section>
            </div>

            <!-- EXECUTIVE PREMIUM DASHBOARD SECTION -->
            <section class="space-y-8">
                <div class="flex items-center gap-4">
                    <div class="h-[1px] flex-1 bg-gradient-to-r from-transparent via-[#BD9A5F]/40 to-transparent"></div>
                    <h2 class="font-headline text-sm uppercase tracking-[0.5em] text-[#BD9A5F]">Soporte de Decisión Ejecutiva</h2>
                    <div class="h-[1px] flex-1 bg-gradient-to-r from-transparent via-[#BD9A5F]/40 to-transparent"></div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Column 1: Financial & Efficiency High-Level KPIs -->
                    <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-br from-[#0E2326] to-[#021619] border border-primary/20 p-8 rounded-2xl shadow-2xl relative overflow-hidden group">
                            <div class="absolute top-[-20px] right-[-20px] w-40 h-40 bg-primary/5 rounded-full blur-3xl group-hover:bg-primary/10 transition-all"></div>
                            <p class="text-primary/60 text-[10px] font-black uppercase tracking-widest mb-2">Ingresos Mes Actual</p>
                            <h4 class="text-5xl font-headline text-[#F7F5EB] mb-4">$<?php echo number_format($ingresos_mes, 2); ?></h4>
                            <div class="flex items-center gap-2 text-emerald-400 text-xs">
                                <span class="material-symbols-outlined text-sm">trending_up</span>
                                <span>Liquidez operativa activa</span>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-[#0E2326] to-[#021619] border border-primary/20 p-8 rounded-2xl shadow-2xl relative overflow-hidden group">
                            <p class="text-primary/60 text-[10px] font-black uppercase tracking-widest mb-2">Capital en Aprobación</p>
                            <h4 class="text-5xl font-headline text-[#F7F5EB] mb-4">$<?php echo number_format($pipeline_monto, 2); ?></h4>
                            <div class="flex items-center gap-2 text-amber-400 text-xs">
                                <span class="material-symbols-outlined text-sm">hourglass_empty</span>
                                <span>Monto total en pipeline de compra</span>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-[#0E2326] to-[#021619] border border-primary/20 p-8 rounded-2xl shadow-2xl col-span-1 md:col-span-2">
                             <div class="flex justify-between items-center mb-6">
                                <div>
                                    <h5 class="text-[#F7F5EB] font-headline text-xl">Resumen de Inversión</h5>
                                    <p class="text-primary/40 text-[10px] uppercase tracking-widest">Ejecución de Compras vs Presupuesto</p>
                                </div>
                             </div>
                             <div class="flex items-end gap-12">
                                <div>
                                    <p class="text-on-surface-variant text-[10px] uppercase font-bold mb-1">Gasto Real (OC)</p>
                                    <span class="text-3xl font-headline text-[#F7F5EB]">$<?php echo number_format($total_compras_monto, 2); ?></span>
                                </div>
                                <div class="flex-1 h-3 bg-white/5 rounded-full overflow-hidden relative mb-2">
                                    <div class="absolute left-0 top-0 h-full bg-primary shadow-[0_0_15px_rgba(231,193,130,0.5)]" style="width: 65%;"></div>
                                </div>
                                <div class="text-right">
                                    <p class="text-on-surface-variant text-[10px] uppercase font-bold mb-1">Eficiencia</p>
                                    <span class="text-3xl font-headline text-primary">84%</span>
                                </div>
                             </div>
                        </div>
                    </div>

                    <!-- Column 2: Visual Distribution (Pie Chart) -->
                    <div class="bg-gradient-to-br from-[#0E2326] to-[#021619] border border-primary/20 p-8 rounded-2xl shadow-2xl flex flex-col items-center">
                        <div class="w-full mb-6">
                            <h5 class="text-[#F7F5EB] font-headline text-xl">Gasto por Proveedor</h5>
                            <p class="text-primary/40 text-[10px] uppercase tracking-widest">Top 5 Proveedores Estratégicos</p>
                        </div>
                        <div class="w-full flex-1 flex items-center justify-center min-h-[250px]">
                            <canvas id="supplierPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-surface-container-low p-8 relative overflow-hidden group border border-[#F7F5EB]/5">
                    <span class="material-symbols-outlined text-6xl absolute top-0 right-0 p-4 opacity-10">payments</span>
                    <p class="font-label text-on-surface-variant text-sm tracking-widest uppercase mb-4">Ventas Totales</p>
                    <h3 class="font-headline text-4xl text-primary tracking-tighter"><?php echo number_format($total_pedidos); ?></h3>
                    <div class="mt-4 flex items-center gap-2 text-green-400 text-sm">
                        <span class="material-symbols-outlined text-xs">trending_up</span><span>Métricas en Vivo</span>
                    </div>
                </div>
                <div class="bg-surface-container-high p-8 relative border-t-4 border-primary shadow-xl">
                    <span class="material-symbols-outlined text-6xl absolute top-0 right-0 p-4 opacity-10">group</span>
                    <p class="font-label text-on-surface-variant text-sm tracking-widest uppercase mb-4">Clientes Registrados</p>
                    <h3 class="font-headline text-4xl text-[#F7F5EB] tracking-tighter"><?php echo number_format($total_clientes); ?></h3>
                    <div class="mt-4 flex items-center gap-2 text-primary text-sm">
                        <span class="material-symbols-outlined text-xs">group</span><span>Base de datos activa</span>
                    </div>
                </div>
                <div class="bg-surface-container-low p-8 relative border border-[#F7F5EB]/5 rounded-xl">
                    <span class="material-symbols-outlined text-6xl absolute top-0 right-0 p-4 opacity-10">inventory_2</span>
                    <p class="font-label text-on-surface-variant text-sm tracking-widest uppercase mb-4">Compras (OC)</p>
                    <h3 class="font-headline text-4xl text-primary tracking-tighter"><?php echo number_format($total_compras_conteo); ?></h3>
                    <div class="mt-4 flex items-center gap-2 text-amber-500 text-sm">
                        <span class="material-symbols-outlined text-xs">receipt_long</span><span>OC Registradas</span>
                    </div>
                </div>
                <div class="bg-surface-container-low p-8 relative border border-[#F7F5EB]/5 bg-gradient-to-br from-surface-container-low to-[#BD9A5F]/10">
                    <span class="material-symbols-outlined text-6xl absolute top-0 right-0 p-4 opacity-10">account_balance_wallet</span>
                    <p class="font-label text-on-surface-variant text-sm tracking-widest uppercase mb-4">Inversión Compras</p>
                    <h3 class="font-headline text-3xl text-emerald-500 tracking-tighter">$<?php echo number_format($total_compras_monto, 2); ?></h3>
                    <div class="mt-4 flex items-center gap-2 text-emerald-500 text-sm">
                        <span class="material-symbols-outlined text-xs">monetization_on</span><span>Monto Acumulado</span>
                    </div>
                </div>
            </section>

            <!-- Recent Orders Section -->
            <section class="space-y-6">
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-primary text-[10px] uppercase font-black tracking-[0.4em] mb-2">Abastecimiento</p>
                        <h2 class="text-3xl font-headline text-[#F7F5EB]">Órdenes de Compra (OC) Recientes</h2>
                    </div>
                    <a href="modules/purchasing/ui/list_orders.php" class="text-primary hover:text-white transition-colors flex items-center gap-2 text-sm uppercase font-black tracking-widest">
                        Ver Todo <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>

                <div class="bg-surface-container-low border border-[#F7F5EB]/5 rounded-sm overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-[#F7F5EB]/5 text-[#F7F5EB]/40 text-[10px] uppercase font-black tracking-widest">
                            <tr>
                                <th class="px-6 py-4">Folio</th>
                                <th class="px-6 py-4">Proveedor</th>
                                <th class="px-6 py-4">Monto</th>
                                <th class="px-6 py-4">Estado</th>
                                <th class="px-6 py-4 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="text-[#F7F5EB]/80 text-sm font-medium">
                            <?php 
                            $res_recientes = mysqli_query($conexion, "
                                SELECT o.*, s.name as supplier_name 
                                FROM pur_orders o 
                                JOIN pur_suppliers s ON o.supplier_id = s.id 
                                ORDER BY o.created_at DESC LIMIT 5
                            ");
                            while($oc = mysqli_fetch_assoc($res_recientes)):
                                $status_color = match($oc['status']) {
                                    'sent' => 'text-amber-500',
                                    'received' => 'text-emerald-500',
                                    'paid' => 'text-blue-500',
                                    default => 'text-[#F7F5EB]/40'
                                };
                            ?>
                            <tr class="border-t border-[#F7F5EB]/5 hover:bg-[#F7F5EB]/2 transition-colors">
                                <td class="px-6 py-4 font-headline font-bold text-primary"><?php echo $oc['folio']; ?></td>
                                <td class="px-6 py-4 uppercase text-[11px] tracking-tight"><?php echo htmlspecialchars($oc['supplier_name']); ?></td>
                                <td class="px-6 py-4 font-headline font-bold">$<?php echo number_format($oc['total_amount'], 2); ?></td>
                                <td class="px-6 py-4">
                                    <span class="flex items-center gap-2 <?php echo $status_color; ?> text-[10px] font-black uppercase tracking-widest">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        <?php echo $oc['status']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="modules/purchasing/ui/view_order.php?id=<?php echo $oc['id']; ?>" class="p-2 hover:bg-primary/20 rounded-full transition-all inline-block">
                                        <span class="material-symbols-outlined text-primary text-sm">visibility</span>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if(mysqli_num_rows($res_recientes) == 0): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-[#F7F5EB]/20 italic">No hay órdenes de compra registradas.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </main>
    </div>

    <!-- JS LOGIC -->
    <script>
        function toggleSubmenu(id) {
            const el = document.getElementById(id);
            const icon = document.getElementById(id.replace('submenu', 'icon'));
            if (el.classList.contains('hidden')) {
                el.classList.remove('hidden');
                if(icon) icon.innerText = 'expand_less';
            } else {
                el.classList.add('hidden');
                if(icon) icon.innerText = 'expand_more';
            }
        }

        // --- Global Notification Logic ---
        function fetchNotifications() {
            $.get('modules/purchasing/api/get_notifications.php', function(response) {
                if (response.status === 'success') {
                    const count = response.count;
                    if (count > 0) {
                        $('#notifBadge').removeClass('hidden');
                        $('#notifCount').text(`${count} activas`);
                        
                        let html = '';
                        response.data.forEach(n => {
                            const icon = n.type === 'approval_needed' ? 'pan_tool' : 'task_alt';
                            const iconColor = n.type === 'approval_needed' ? 'text-amber-500' : 'text-emerald-500';
                            
                            html += `
                                <div class="p-4 border-b border-white/5 hover:bg-white/5 transition-all cursor-pointer" onclick="location.href='modules/purchasing/ui/${n.link}'">
                                    <div class="flex gap-3">
                                        <span class="material-symbols-outlined ${iconColor} text-lg">${icon}</span>
                                        <div>
                                            <p class="text-xs font-bold text-white mb-1">${n.title}</p>
                                            <p class="text-[10px] text-white/50 leading-relaxed">${n.body}</p>
                                            <p class="text-[8px] text-[#BD9A5F] mt-2 opacity-60 uppercase tracking-tighter">${new Date(n.time).toLocaleString('es-MX')}</p>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        $('#notifList').html(html);
                    } else {
                        $('#notifBadge').addClass('hidden');
                        $('#notifCount').text('0 nuevas');
                        $('#notifList').html('<div class="p-8 text-center text-white/20 italic text-xs">Sin pendientes adicionales</div>');
                    }
                }
            });
        }

        $('#notifBell').on('click', function(e) {
            e.stopPropagation();
            $('#notifPanel').toggleClass('hidden');
        });

        $(document).on('click', function() {
            $('#notifPanel').addClass('hidden');
        });

        $('#notifPanel').on('click', function(e) {
            e.stopPropagation();
        });

        // --- Sidebar Accordion ---
        function toggleSubmenu(id) {
            const el = document.getElementById(id);
            const icon = document.getElementById(id.replace('submenu', 'icon'));
            if (el.classList.contains('hidden')) {
                el.classList.remove('hidden');
                if(icon) icon.innerText = 'expand_less';
            } else {
                el.classList.add('hidden');
                if(icon) icon.innerText = 'expand_more';
            }
        }

        // Initial fetch and polling
        fetchNotifications();
        setInterval(fetchNotifications, 30000); // Every 30s

        // --- Executive Pie Chart ---
        const ctx = document.getElementById('supplierPieChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($pie_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($pie_values); ?>,
                    backgroundColor: [
                        '#BD9A5F', // Primary Gold
                        '#0E2326', // Emerald Dark
                        '#1B4347', // Teal Medium
                        '#2A676E', // Blue Slate
                        '#E7C182'  // Lighter Gold
                    ],
                    borderWidth: 0,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#F7F5EB',
                            font: {
                                size: 10,
                                family: 'Manrope',
                                weight: 'bold'
                            },
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: '#021619',
                        titleFont: { family: 'Playfair Display' },
                        bodyFont: { family: 'Manrope' },
                        padding: 12,
                        borderColor: 'rgba(231,193,130,0.2)',
                        borderWidth: 1
                    }
                },
                cutout: '75%'
            }
        });
    </script>

    <!-- Mobile Nav -->
    <nav class="fixed bottom-0 w-full z-50 flex justify-around items-center px-4 py-3 border-t border-[#F7F5EB]/5 md:hidden bg-[#021619]/95 backdrop-blur-xl rounded-t-2xl shadow-2xl">
        <a class="flex flex-col items-center text-[#BD9A5F] font-bold" href="dashboard.php"><span class="material-symbols-outlined">home</span><span class="text-[10px]">Inicio</span></a>
        <a class="flex flex-col items-center text-[#F7F5EB]/50" href="modules/purchasing/ui/list_requests.php"><span class="material-symbols-outlined">shopping_cart</span><span class="text-[10px]">Compras</span></a>
        <a class="flex flex-col items-center text-[#F7F5EB]/50" href="productos.php"><span class="material-symbols-outlined">bakery_dining</span><span class="text-[10px]">Stock</span></a>
        <a class="flex flex-col items-center text-[#F7F5EB]/50" href="logout.php"><span class="material-symbols-outlined">logout</span><span class="text-[10px]">Salir</span></a>
    </nav>
    <?php if($dashboard_autohide_hero === '1'): ?>
    <script>
        (function() {
            let hideTimeout;
            let restTimeout;
            const hero = document.getElementById('hero-container');
            if (!hero) return;

            document.addEventListener('mousemove', () => {
                // Si estamos en reposo, mostramos el hero (si no está visible)
                // y empezamos el cronómetro de 10s para ocultarlo
                clearTimeout(restTimeout);

                if (!hideTimeout) {
                    hideTimeout = setTimeout(() => {
                        hero.style.maxHeight = '0px';
                        hero.style.opacity = '0';
                        hero.style.marginBottom = '0px';
                        hero.classList.remove('space-y-12'); // Si el padre tiene gap, esto ayuda
                    }, 10000); // 10 segundos de movimiento
                }

                // Definimos el reposo como 2 segundos sin movimiento
                restTimeout = setTimeout(() => {
                    hero.style.maxHeight = '320px';
                    hero.style.opacity = '1';
                    hero.style.marginBottom = '3rem'; // match space-y-12 gap (approx 3rem)
                    clearTimeout(hideTimeout);
                    hideTimeout = null;
                }, 2000);
            });
        })();
    </script>
    <?php endif; ?>
</body>
</html>
