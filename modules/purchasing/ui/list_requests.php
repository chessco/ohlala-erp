<?php
include('../../../conexion.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['autenticado'])) {
    header("Location: ../../../index.php");
    exit();
}

$rol_actual        = isset($_SESSION['usuario_rol']) ? strtolower($_SESSION['usuario_rol']) : '';
$esAdmin           = ($rol_actual === 'admin'); 
$usuario_id_sesion = $_SESSION['usuario_id'];
$current_user_id   = $usuario_id_sesion; 

// --- SETTINGS ---
spl_autoload_register(function ($class) {
    if (strpos($class, 'Purchasing\\') === 0) {
        $file = __DIR__ . '/../src/' . str_replace(['Purchasing\\', '\\'], ['', '/'], $class) . '.php';
        if (file_exists($file)) require $file;
    }
});
use Purchasing\Infrastructure\SettingsRepository;
$settingsRepo = new SettingsRepository($conexion);
$dashboardSettings = $settingsRepo->getAll();
$showHero = ($dashboardSettings['dashboard_show_hero'] ?? '1') == '1';

// --- REAL STATS ---
$monthStart = date('Y-m-01 00:00:00');
$resMonthTotal = mysqli_query($conexion, "SELECT SUM(total_amount) as total FROM pur_requests WHERE created_at >= '$monthStart' AND status = 'approved'");
$monthTotal = mysqli_fetch_assoc($resMonthTotal)['total'] ?? 0;

$resPendingCount = mysqli_query($conexion, "SELECT COUNT(*) as total FROM pur_requests WHERE status = 'pending'");
$pendingCount = mysqli_fetch_assoc($resPendingCount)['total'] ?? 0;

// --- FETCH CATALOG FOR JS ---
$resCatalog = mysqli_query($conexion, "SELECT id, name, unit, base_price FROM pur_catalog_items WHERE status = 'active' ORDER BY name ASC");
$catalogData = [];
while ($c = mysqli_fetch_assoc($resCatalog)) $catalogData[] = $c;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Centro de Compras - Ohlala Artisanal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#E7C182",
                        "background": "#021619",
                        "on-background": "#D0E7EA",
                        "surface": "#021619",
                        "on-surface": "#D0E7EA",
                        "surface-variant": "#24383B",
                        "primary-container": "#BD9A5F",
                        "error": "#FFB4AB"
                    },
                    fontFamily: {
                        headline: ["Playfair Display", "serif"],
                        body: ["Manrope", "sans-serif"]
                    }
                }
            }
        }
    </script>
    <style>
        .torn-edge {
            mask-image: url("data:image/svg+xml,%3Csvg width='100' height='10' viewBox='0 0 100 10' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 10 L5 8 L10 10 L15 7 L20 10 L25 8 L30 10 L35 7 L40 10 L45 8 L50 10 L55 7 L60 10 L65 8 L70 10 L75 7 L80 10 L85 8 L90 10 L95 7 L100 10 V0 H0 Z' fill='black'/%3E%3C/svg%3E");
            mask-size: 100% 100%;
        }
        @keyframes pulse-gold {
            0% { transform: scale(0.95); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.5; }
        }
        .notif-pulse {
            animation: pulse-gold 2s infinite ease-in-out;
        }
        body { background-color: #021619; }
    </style>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(189, 154, 95, 0.05); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(189, 154, 95, 0.3); border-radius: 10px; }
        
        /* Autocomplete Styles */
        .autocomplete-container { position: relative; }
        .autocomplete-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #021619;
            border: 1px solid rgba(189, 154, 95, 0.3);
            border-top: none;
            z-index: 1000;
            max-height: 250px;
            overflow-y: auto;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
            border-radius: 0 0 4px 4px;
        }
        .autocomplete-item {
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .autocomplete-item:last-child { border-bottom: none; }
        .autocomplete-item:hover, .autocomplete-item.active {
            background: rgba(189, 154, 95, 0.15);
            color: #BD9A5F;
        }
        .item-input {
            background: transparent;
            font-style: italic;
            outline: none;
            border-bottom: 1px solid rgba(189, 154, 95, 0.1);
            padding-bottom: 4px;
            transition: all 0.3s;
            color: #021619 !important;
            opacity: 1 !important;
        }
        .item-input:focus {
            border-bottom: 1px solid #BD9A5F;
            background: rgba(189, 154, 95, 0.05);
        }
        .item-input:disabled, .item-input:read-only {
            cursor: default;
            border-bottom-color: transparent;
            opacity: 1 !important;
            color: #021619 !important;
        }
        .oc-input {
            color: #021619 !important;
            opacity: 1 !important;
            font-weight: 700;
        }
        /* Notes History Styles */
        .note-item {
            position: relative;
            padding-left: 1.5rem;
        }
        .note-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.5rem;
            bottom: -0.5rem;
            width: 2px;
            background: rgba(2, 22, 25, 0.05);
        }
        .note-item:last-child::before {
            display: none;
        }
        .note-dot {
            position: absolute;
            left: -4px;
            top: 0.5rem;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #BD9A5F;
            border: 2px solid white;
            box-shadow: 0 0 0 1px rgba(2, 22, 25, 0.1);
        }
    </style>

</head>
<body class="text-on-background font-body min-h-screen">
    <header class="flex justify-between items-center w-full px-6 py-4 z-50 bg-[#BD9A5F] top-0 shadow-lg shadow-[#021619]/20 sticky">
        <div class="flex items-center gap-4">
            <h1 class="text-2xl font-headline font-bold text-[#021619] tracking-tight">Ohlala! Bistro (V3.1)</h1>
        </div>
        <div class="hidden md:flex gap-8 items-center">
            <nav class="flex gap-6 items-center text-[#021619]">
                <a class="font-headline opacity-80 hover:opacity-100" href="../../../dashboardv3.php">Inicio</a>
                
                <!-- Dropdown Catálogos -->
                <div class="relative group">
                    <button class="font-headline opacity-80 hover:opacity-100 flex items-center gap-1 py-1">
                        Catálogos <span class="material-symbols-outlined text-sm">expand_more</span>
                    </button>
                    <div class="absolute left-0 mt-2 w-56 bg-[#021619] text-[#F7F5EB] shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50 rounded-sm overflow-hidden border border-[#BD9A5F]/20">
                        <a href="../../../clientes.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5 no-underline">
                            <span class="material-symbols-outlined text-[16px]">group</span> Clientes
                        </a>
                        <a href="../../../productos.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5 no-underline">
                            <span class="material-symbols-outlined text-[16px]">bakery_dining</span> Productos
                        </a>
                        <a href="suppliers.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5 no-underline">
                            <span class="material-symbols-outlined text-[16px]">handshake</span> Proveedores
                        </a>
                        <a href="items.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-[#021619] transition-all no-underline">
                            <span class="material-symbols-outlined text-[16px]">inventory_2</span> Insumos
                        </a>
                        <?php if($esAdmin): ?>
                            <a href="../../../usuarios.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-t border-white/5 no-underline">
                                <span class="material-symbols-outlined text-[16px]">person_outline</span> Usuarios
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <a class="font-headline text-[#F7F5EB] border-b-2 border-[#F7F5EB] pb-1" href="list_requests.php">Compras</a>
                <?php if($esAdmin): ?>
                    <a class="font-headline text-[#F7F5EB]/80 hover:text-[#F7F5EB]" href="settings.php">Configuración</a>
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
                            <a href="list_requests.php" class="block text-center text-[10px] font-black uppercase text-[#BD9A5F] hover:text-white transition-colors">Ver todas las solicitudes</a>
                        </footer>
                    </div>
                </div>
            </nav>

            <div class="flex items-center gap-3 px-4 py-1 border-l border-[#021619]/10 ml-2">
                <span class="material-symbols-outlined text-[#021619]/60 font-variation-settings-fill-1">account_circle</span>
                <div class="flex flex-col text-left">
                    <span class="text-[10px] font-black text-[#021619] leading-none uppercase tracking-tighter"><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></span>
                    <span class="text-[8px] font-bold text-[#021619]/40 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($_SESSION['usuario_rol']); ?></span>
                </div>
            </div>

            <button onclick="location.href='../../../logout.php'" class="bg-[#021619] text-[#F7F5EB] px-6 py-2 font-headline font-medium hover:bg-[#021619]/90 active:scale-95 transition-all">
                Cerrar Sesión
            </button>
        </div>
    </header>

    <main class="p-6 md:p-10 space-y-12 max-w-7xl mx-auto">
        <!-- Module Header -->
        <header class="flex flex-col md:flex-row justify-between items-end gap-6 border-b border-white/5 pb-8">
            <div>
                <a href="../../../dashboardv3.php" class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mb-4 flex items-center hover:opacity-70 transition-all">
                    <span class="material-symbols-outlined text-sm mr-2">arrow_back</span> Regresar al Centro Operativo
                </a>
                <h1 class="text-5xl font-headline text-[#F7F5EB]">Centro de Compras</h1>
                <p class="text-primary/60 font-body italic mt-2">Logística y Abastecimiento de Insumos Premium.</p>
            </div>
            <div class="flex flex-wrap gap-4">
                <!-- Dropdown Catálogos -->
                <div class="relative group">
                    <button class="px-6 py-3 bg-[#BD9A5F]/10 border border-[#BD9A5F]/30 text-[#BD9A5F] text-[10px] font-black uppercase tracking-widest hover:bg-[#BD9A5F] hover:text-background transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">widgets</span> Catálogos <span class="material-symbols-outlined text-[10px]">expand_more</span>
                    </button>
                    <div class="absolute left-0 mt-2 w-56 bg-[#021619] border border-[#BD9A5F]/30 shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50 rounded-sm">
                        <a href="items.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-background transition-all border-b border-white/5">
                            <span class="material-symbols-outlined text-[16px]">inventory_2</span> Insumos
                        </a>
                        <a href="suppliers.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-background transition-all">
                            <span class="material-symbols-outlined text-[16px]">handshake</span> Proveedores
                        </a>
                    </div>
                </div>

                <a href="list_orders.php" class="px-6 py-3 bg-[#BD9A5F]/10 border border-[#BD9A5F]/30 text-[#BD9A5F] text-[10px] font-black uppercase tracking-widest hover:bg-[#BD9A5F] hover:text-background transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">history_edu</span> Órdenes de Compra
                </a>
                <button onclick="openModal()" class="bg-[#BD9A5F] text-background px-8 py-3 font-headline font-bold shadow-2xl hover:translate-y-[-2px] transition-all flex items-center gap-2 rounded-sm active:scale-95 group">
                    <span class="material-symbols-outlined group-hover:rotate-90 transition-transform">add</span> NUEVA SOLICITUD
                </button>
            </div>
        </header>


        <!-- Table Container -->
        <section class="bg-[#F7F5EB] text-[#021619] p-10 shadow-2xl relative rounded-t-xl">
            <div class="absolute bottom-[-10px] left-0 w-full h-4 bg-[#F7F5EB] torn-edge transform z-10"></div>
            <!-- Version 2.1 - Grid Restructured -->
            <h2 class="font-headline text-3xl mb-10">Solicitudes Recientes</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b-2 border-[#021619]/10 text-[10px] uppercase tracking-[0.2em] font-black text-[#021619]/40">
                            <th class="pb-6 px-4">Folio</th>
                            <th class="pb-6 px-4">Fecha</th>
                            <th class="pb-6 px-4">Proveedor</th>
                            <th class="pb-6 px-4">Inversión</th>
                            <th class="pb-6 px-4 text-center">Estado</th>
                            <th class="pb-6 px-4 text-right">Gestión</th>
                        </tr>
                    </thead>
                    <tbody id="mainRequestsList" class="font-body">
                        <!-- Carga dinámica vía AJAX -->
                    </tbody>
                </table>
            </div>
            
            <div class="mt-12 pt-8 border-t border-[#021619]/5 flex justify-center">
                <p class="text-[10px] text-[#021619]/20 font-black uppercase tracking-[0.5em] italic">Ohlala Executive Procurement - Excellence in Every Ingredient</p>
            </div>
        </section>
        <?php if ($showHero): ?>
        <!-- Hero Section (Relocated) -->
        <section class="relative h-60 w-full overflow-hidden rounded-xl shadow-2xl group mt-12 mb-8">
            <img src="https://images.unsplash.com/photo-1550989460-0adf9ea622e2?q=80&w=2070&auto=format&fit=crop" alt="Abastecimiento" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"/>
            <div class="absolute inset-0 bg-gradient-to-t from-[#021619] via-[#021619]/20 opacity-90"></div>
            <div class="absolute bottom-0 left-0 p-8">
                <h2 class="text-3xl font-headline text-[#F7F5EB] mb-1 italic">La Selección de lo Mejor</h2>
                <p class="text-primary text-sm font-headline tracking-[0.2em] uppercase font-bold opacity-80">Control de Abastecimiento & Calidad</p>
            </div>
        </section>

        <!-- Artisanal Stats (Relocated) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 pb-12">
            <div class="bg-[#BD9A5F]/10 p-8 border border-[#BD9A5F]/20 relative group">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <span class="material-symbols-outlined text-7xl text-white">account_balance_wallet</span>
                </div>
                <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.2em] mb-4">Total Aprobado (Mes)</p>
                <p class="text-4xl font-headline text-[#F7F5EB]">$<span id="statMonthTotal"><?php echo number_format($monthTotal, 0); ?></span><span class="text-xl opacity-40">.00</span></p>
            </div>
            <div class="bg-[#BD9A5F]/10 p-8 border border-[#BD9A5F]/20 relative group">
                <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.2em] mb-4">Pendientes Aprobación</p>
                <div class="flex items-center gap-4">
                    <p class="text-4xl font-headline text-[#F7F5EB]" id="statPendingCount"><?php echo str_pad($pendingCount, 2, '0', STR_PAD_LEFT); ?></p>
                    <span class="px-3 py-1 bg-amber-500/20 text-amber-500 border border-amber-500/30 text-[9px] font-bold uppercase tracking-widest">Activas en Flujo</span>
                </div>
            </div>
            <div class="bg-[#BD9A5F]/10 p-8 border border-[#BD9A5F]/20 relative group">
                <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.2em] mb-4">Eficiencia Presupuesto</p>
                <div class="w-full bg-white/5 h-1.5 mt-2 rounded-full overflow-hidden">
                    <div id="statEfficiencyBar" class="bg-[#BD9A5F] h-full shadow-[0_0_10px_#BD9A5F]" style="width: 65%"></div>
                </div>
                <p id="statEfficiencyLabel" class="text-right text-[10px] font-bold mt-3 text-primary opacity-60 italic">65% ejecución mensual</p>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Modal: Nueva Solicitud (Refactored for Multi-Item) -->
    <div id="newRequestModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-[#021619]/90 backdrop-blur-sm p-4">
        <div class="bg-[#F7F5EB] w-full max-w-4xl relative shadow-2xl overflow-hidden rounded-lg max-h-[95vh] flex flex-col animate-in fade-in zoom-in duration-300">
            <div class="absolute top-0 left-0 w-full h-2 bg-[#BD9A5F] z-10"></div>
            
            <button onclick="closeModal()" class="absolute top-4 right-4 text-[#021619]/40 hover:text-[#021619] transition-all z-20">
                <span class="material-symbols-outlined">close</span>
            </button>

            <div class="p-8 md:p-12 overflow-y-auto custom-scrollbar">
                <header class="mb-8 border-b border-[#021619]/5 pb-6">
                    <h2 class="text-3xl font-headline text-[#021619] mb-2" id="modalTitle">Nueva Gestión de Abastecimiento</h2>
                    <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.2em]">Especifique insumos y proveedores para aprobación corporativa</p>
                </header>

                <form id="newRequestForm" class="space-y-8">
                    <input type="hidden" id="requestId" name="id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60 mb-2">Folio / Fecha</label>
                            <input type="text" id="dateInput" readonly 
                                   class="w-full bg-[#021619]/5 border-2 border-[#021619]/10 px-4 py-4 font-body text-sm text-[#021619] outline-none rounded-sm shadow-inner"/>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60 mb-2">Estado</label>
                            <input type="text" id="statusInput" readonly 
                                   class="w-full bg-[#021619]/5 border-2 border-[#021619]/10 px-4 py-4 font-body text-sm text-[#021619] font-bold uppercase tracking-widest outline-none rounded-sm shadow-inner text-center"/>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60 mb-2">Proveedor Sugerido (Opcional)</label>
                            <select name="supplier_id" id="supplierInput" class="w-full bg-[#021619]/5 border-2 border-[#021619]/10 px-4 py-4 font-body text-sm text-[#021619] outline-none focus:border-[#BD9A5F] rounded-sm appearance-none cursor-pointer">
                                <option value="">--- Selección de Proveedor ---</option>
                                <?php 
                                $resS = mysqli_query($conexion, "SELECT id, name FROM pur_suppliers ORDER BY name ASC");
                                while($s = mysqli_fetch_assoc($resS)) echo "<option value='{$s['id']}'>".htmlspecialchars($s['name'])."</option>";
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- Visual Progress Indicator -->
                    <div id="approvalProgress" class="hidden mt-8 pt-8 border-t border-[#021619]/5">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60 mb-6 font-headline">Estado de Aprobación Corporativa</label>
                        <div class="flex items-center justify-between max-w-2xl mx-auto relative px-4">
                            <div class="absolute top-1/2 left-0 w-full h-[2px] bg-[#021619]/10 -translate-y-1/2 z-0"></div>
                            
                            <div class="relative z-10 flex flex-col items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-emerald-500 border-2 border-emerald-500 text-white flex items-center justify-center transition-all duration-500 shadow-sm">
                                    <span class="material-symbols-outlined text-lg">person</span>
                                </div>
                                <span id="solicitanteStatus" class="text-[9px] font-black uppercase tracking-widest text-emerald-600 font-bold italic">Solicitante: ✅</span>
                            </div>

                            <div class="relative z-10 flex flex-col items-center gap-3">
                                <div id="level1Circle" class="w-10 h-10 rounded-full bg-white border-2 border-[#021619]/10 flex items-center justify-center transition-all duration-500">
                                    <span id="level1Icon" class="material-symbols-outlined text-lg">check</span>
                                </div>
                                <span id="level1Status" class="text-[9px] font-black uppercase tracking-widest text-[#021619]/40 font-bold italic">Usuario 1: ⏳</span>
                            </div>
                            <div class="relative z-10 flex flex-col items-center gap-3">
                                <div id="level2Circle" class="w-10 h-10 rounded-full bg-white border-2 border-[#021619]/10 flex items-center justify-center transition-all duration-500">
                                    <span id="level2Icon" class="material-symbols-outlined text-lg">hourglass_empty</span>
                                </div>
                                <span id="level2Status" class="text-[9px] font-black uppercase tracking-widest text-[#021619]/40 font-bold italic">Usuario 2: ⏳</span>
                            </div>
                            <div class="relative z-10 flex flex-col items-center gap-3">
                                <div id="level3Circle" class="w-10 h-10 rounded-full bg-white border-2 border-[#021619]/10 flex items-center justify-center transition-all duration-500">
                                    <span id="level3Icon" class="material-symbols-outlined text-lg">hourglass_empty</span>
                                </div>
                                <span id="level3Status" class="text-[9px] font-black uppercase tracking-widest text-[#021619]/40 font-bold italic">Usuario 3: ⏳</span>
                            </div>
                            <div class="relative z-10 flex flex-col items-center gap-3">
                                <div id="level4Circle" class="w-10 h-10 rounded-full bg-white border-2 border-[#021619]/10 flex items-center justify-center transition-all duration-500">
                                    <span id="level4Icon" class="material-symbols-outlined text-lg">hourglass_empty</span>
                                </div>
                                <span id="level4Status" class="text-[9px] font-black uppercase tracking-widest text-[#021619]/40 font-bold italic">Usuario 4: ⏳</span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-end">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60">Detalle de Insumos (Catálogo Ohlala)</label>
                            <button type="button" onclick="addRow()" id="addRowBtn" class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-[#BD9A5F] hover:text-[#021619] transition-colors">
                                <span class="material-symbols-outlined text-sm">add_circle</span> Añadir Partida
                            </button>
                        </div>

                        <div class="border border-[#021619]/10 rounded-sm overflow-hidden bg-white/50">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-[#021619]/5 text-[10px] font-black uppercase tracking-widest text-[#021619]/40 border-b border-[#021619]/10">
                                    <tr>
                                        <th class="px-4 py-3">Insumo</th>
                                        <th class="px-4 py-3 w-32">Unidad</th>
                                        <th class="px-4 py-3 w-28">Cant.</th>
                                        <th class="px-4 py-3 w-32">P. Unit</th>
                                        <th class="px-4 py-3 w-32 text-right">Subtotal</th>
                                        <th class="px-4 py-3 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody id="requestItemsBody">
                                    <!-- Dynamic rows -->
                                </tbody>
                                <tfoot>
                                     <tr class="bg-[#021619]/5 border-t-2 border-[#021619]/10">
                                         <td colspan="6" class="px-4 py-8 text-right">
                                            <div class="flex items-center justify-end gap-6 text-[#021619]">
                                                <span class="text-[10px] font-black uppercase tracking-[0.3em] opacity-40 mt-1">Inversión Total Estimada:</span>
                                                <span class="font-headline text-4xl text-[#BD9A5F] font-bold" id="requestTotalDisplay">.00</span>
                                            </div>
                                         </td>
                                     </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div id="notesSection" class="pt-6 border-t border-[#021619]/10">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60 mb-6">Historial de Notas y Observaciones</label>
                        
                        <div id="notesHistory" class="space-y-6 mb-8">
                            <!-- Notes Feed will be rendered here -->
                        </div>

                        <!-- Creating/Editing Note input -->
                        <div id="creationNoteArea">
                            <textarea id="descInput" name="description" placeholder="Resumen ejecutivo del abastecimiento..." 
                                    class="w-full bg-[#021619]/5 border-2 border-[#021619]/10 px-4 py-4 font-body text-sm text-[#021619] placeholder:text-[#021619]/30 focus:border-[#BD9A5F] focus:bg-white transition-all outline-none rounded-sm shadow-inner min-h-[80px]"></textarea>
                        </div>
                    </div>

                    <!-- Decision Section (Visible only during view mode for pending) -->
                    <div id="decisionSection" class="hidden pt-6 border-t-2 border-[#BD9A5F]/20 bg-[#BD9A5F]/5 p-6 rounded-sm">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-[#BD9A5F] mb-4 font-bold">Añadir Nueva Nota de Decisión</label>
                        <textarea id="decisionComments" class="w-full bg-white border-2 border-[#BD9A5F]/20 px-4 py-3 font-body text-sm text-[#021619] outline-none rounded-sm min-h-[100px] focus:border-[#BD9A5F] transition-all" placeholder="Escribe aquí los motivos detrás de tu aprobación o rechazo..."></textarea>
                        
                        <div class="flex gap-4 mt-6">
                            <button type="button" onclick="procesarDecision('approve')" class="flex-1 bg-emerald-600 text-white py-4 font-headline font-bold hover:bg-emerald-700 transition-all shadow-lg flex items-center justify-center gap-2 rounded-sm active:scale-95 text-xs tracking-widest">
                                <span class="material-symbols-outlined text-sm">verified</span> APROBAR SOLICITUD
                            </button>
                            <button type="button" onclick="procesarDecision('reject')" class="flex-1 bg-red-600 text-white py-4 font-headline font-bold hover:bg-red-700 transition-all shadow-lg flex items-center justify-center gap-2 rounded-sm active:scale-95 text-xs tracking-widest">
                                <span class="material-symbols-outlined text-sm">cancel</span> RECHAZAR SOLICITUD
                            </button>
                        </div>
                    </div>

                    <div class="pt-4 flex flex-col md:flex-row gap-4" id="modalActions">
                        <button type="submit" id="saveBtn" class="flex-1 bg-[#021619] text-[#F7F5EB] py-4 font-headline font-bold text-lg hover:bg-black transition-all shadow-xl">
                            Enviar para Aprobación
                        </button>
                        <button type="button" onclick="closeModal()" class="md:w-32 py-4 font-headline font-bold text-[#021619]/40 hover:text-[#021619] transition-all">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Generar Orden de Compra -->
    <div id="ocModal" class="fixed inset-0 z-[110] hidden flex items-center justify-center bg-[#021619]/95 backdrop-blur-md p-4">
        <div class="bg-[#F7F5EB] w-full max-w-4xl relative shadow-[0_0_50px_rgba(0,0,0,0.5)] overflow-hidden rounded-lg flex flex-col max-h-[90vh]">
            <div class="absolute top-0 left-0 w-full h-2 bg-emerald-600 z-10"></div>
            <button onclick="$('#ocModal').addClass('hidden').removeClass('flex')" class="absolute top-4 right-4 text-[#021619]/40 hover:text-[#021619] transition-all z-20">
                <span class="material-symbols-outlined">close</span>
            </button>

            <div class="p-8 md:p-12 overflow-y-auto custom-scrollbar">
                <header class="mb-8 border-b border-[#021619]/10 pb-6 flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-headline text-[#021619]">Generar Orden de Compra</h2>
                        <p class="text-[10px] font-black text-emerald-600 uppercase tracking-[0.2em]">Paso Final: Formalizar abastecimiento</p>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] font-black text-[#021619]/40 uppercase tracking-widest block">Solicitud Origen</span>
                        <span class="font-headline font-bold text-xl text-[#021619]" id="ocSourceFolio">#PR-00</span>
                    </div>
                </header>

                <form id="ocForm" class="space-y-8">
                    <input type="hidden" id="ocRequestId">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60 mb-3">Seleccionar Proveedor</label>
                            <select id="ocSupplierId" required class="w-full bg-[#021619]/5 border-2 border-[#021619]/10 px-4 py-4 font-body text-sm text-[#021619] outline-none focus:border-emerald-600 rounded-sm shadow-inner transition-all appearance-none cursor-pointer">
                                <option value="">--- Seleccione un aliado comercial ---</option>
                                <?php 
                                $resS = mysqli_query($conexion, "SELECT id, name FROM pur_suppliers ORDER BY name ASC");
                                while($s = mysqli_fetch_assoc($resS)) echo "<option value='{$s['id']}'>".htmlspecialchars($s['name'])."</option>";
                                ?>
                            </select>
                            <?php if(mysqli_num_rows($resS) == 0): ?>
                                <p class="mt-2 text-[10px] text-amber-600 font-bold italic flex items-center gap-1"><span class="material-symbols-outlined text-xs">warning</span> No hay proveedores registrados. <a href="suppliers.php" class="underline">Crear uno</a></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60 mb-3">Notas / Condiciones de Pago</label>
                            <textarea id="ocNotes" class="w-full bg-[#021619]/5 border-2 border-[#021619]/10 px-4 py-4 font-body text-sm text-[#021619] outline-none rounded-sm min-h-[58px]" placeholder="Ej: Crédito 30 días, entrega planta norte..."></textarea>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60">Detalle de Productos / Servicios (Edición Final)</label>
                        <div class="border border-[#021619]/10 rounded-sm overflow-hidden bg-white/50">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-[#021619]/5 text-[10px] font-black uppercase tracking-widest text-[#021619]/40">
                                    <tr>
                                        <th class="px-4 py-3">Insumo</th>
                                        <th class="px-4 py-3 w-28">Cant.</th>
                                        <th class="px-4 py-3 w-32">P. Unit</th>
                                        <th class="px-4 py-3 w-32 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="ocItemsBody">
                                    <!-- Dynamic -->
                                </tbody>
                                <tfoot>
                                    <tr class="bg-[#021619]/5 font-headline text-lg">
                                        <td colspan="3" class="px-4 py-4 text-right font-bold">TOTAL OC:</td>
                                        <td class="px-4 py-4 text-right text-emerald-700 font-bold" id="ocTotal"> $0.00 </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="pt-6 flex gap-4">
                        <button type="submit" class="flex-1 bg-emerald-600 text-white py-5 font-headline font-bold text-lg hover:bg-emerald-700 transition-all shadow-xl rounded-sm active:scale-95 flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">description</span> EMITIR ORDEN DE COMPRA
                        </button>
                        <button type="button" onclick="$('#ocModal').addClass('hidden')" class="px-10 py-5 font-headline font-bold text-[#021619]/40 hover:text-[#021619] transition-all">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

                    <!-- Mobile Nav -->
    <nav class="fixed bottom-0 w-full z-50 flex justify-around items-center px-4 py-3 border-t border-white/5 md:hidden bg-[#021619]/95 backdrop-blur-xl">
        <a class="flex flex-col items-center text-primary/50 text-[#F7F5EB]/50" href="../../../dashboard.php"><span class="material-symbols-outlined">home</span><span class="text-[10px]">Inicio</span></a>
        <a class="flex flex-col items-center text-primary font-bold" href="#"><span class="material-symbols-outlined">shopping_cart</span><span class="text-[10px]">Compras</span></a>
        <a class="flex flex-col items-center text-white/50" href="../../../logout.php"><span class="material-symbols-outlined">logout</span><span class="text-[10px]">Salir</span></a>
    </nav>
    </main>

    <script>
        const CURRENT_USER_ID = <?php echo (int)($current_user_id ?? 0); ?>;
        const CATALOG = <?php echo json_encode($catalogData); ?>;

        let itemsCount = 0;

        function addRow(data = null) {
            const container = $('#requestItemsBody');
            const id = itemsCount++;
            
            const initialName = data ? (CATALOG.find(i => i.id == data.item_id)?.name || '') : '';
            const initialId = data ? data.item_id : '';

            const html = `
                <tr class="border-b border-[#021619]/5 group" id="req_row_${id}">
                    <td class="px-4 py-4">
                        <div class="autocomplete-container" id="ac_container_${id}">
                            <input type="text" 
                                id="ac_input_${id}"
                                value="${initialName}"
                                placeholder="Escribe para buscar insumo..."
                                oninput="handleItemSearch(${id}, this.value)"
                                onkeydown="handleItemKey(${id}, event)"
                                autocomplete="off"
                                class="w-full item-input">
                            <input type="hidden" name="items[${id}][item_id]" id="item_id_${id}" value="${initialId}" required>
                            <div id="results_${id}" class="autocomplete-results hidden custom-scrollbar"></div>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-[10px] font-black text-[#021619]/40 uppercase tracking-widest" id="unit_${id}">${data ? data.unit : '-'}</td>
                    <td class="px-4 py-4">
                        <input type="number" step="0.01" name="items[${id}][quantity]" required 
                            class="w-full bg-[#021619]/10 border border-[#021619]/10 px-2 py-2 outline-none text-center font-bold text-sm text-[#021619] focus:border-[#BD9A5F] transition-all" 
                            value="${data ? data.quantity : 1}" onchange="recalcRequest()">
                    </td>
                    <td class="px-4 py-4">
                        <input type="number" step="0.01" name="items[${id}][unit_price]" required 
                            class="w-full bg-[#021619]/10 border border-[#021619]/10 px-2 py-2 outline-none text-center font-bold text-sm text-[#021619] focus:border-[#BD9A5F] transition-all" 
                            value="${data ? data.unit_price : 0}" onchange="recalcRequest()">
                    </td>
                    <td class="px-4 py-4 text-right font-bold text-[#021619]/60 text-sm" id="subtotal_${id}">$${data ? parseFloat(data.subtotal).toFixed(2) : '0.00'}</td>
                    <td class="px-4 py-4 text-right">
                        <button type="button" onclick="removeRow(${id})" class="text-red-400 opacity-0 group-hover:opacity-100 transition-opacity hover:text-red-600">
                            <span class="material-symbols-outlined text-sm">delete</span>
                        </button>
                    </td>
                </tr>
            `;
            container.append(html);
            recalcRequest();
        }

        function handleItemSearch(id, query) {
            const resultsDiv = $(`#results_${id}`);
            if (!query || query.length < 1) {
                resultsDiv.empty().addClass('hidden');
                $(`#item_id_${id}`).val('');
                return;
            }

            const filtered = CATALOG.filter(item => 
                item.name.toLowerCase().includes(query.toLowerCase())
            ).slice(0, 8);

            if (filtered.length > 0) {
                let html = '';
                filtered.forEach((item, index) => {
                    html += `
                        <div class="autocomplete-item text-xs" onclick="selectSuggestion(${id}, ${item.id}, '${item.name}', '${item.unit}', ${item.base_price})">
                            <div class="font-bold text-[#F7F5EB]">${item.name}</div>
                            <div class="text-[9px] text-[#BD9A5F] uppercase tracking-widest mt-1">${item.unit} • $${item.base_price} base</div>
                        </div>
                    `;
                });
                resultsDiv.html(html).removeClass('hidden');
            } else {
                resultsDiv.empty().addClass('hidden');
            }
        }

        function handleItemKey(id, e) {
            // Future enhancement: Up/Down arrow selection
            if (e.key === "Escape") {
                $(`#results_${id}`).addClass('hidden');
            }
        }

        function selectSuggestion(id, itemId, name, unit, price) {
            $(`#ac_input_${id}`).val(name);
            $(`#item_id_${id}`).val(itemId);
            $(`#results_${id}`).empty().addClass('hidden');
            
            $(`#unit_${id}`).text(unit);
            $(`input[name="items[${id}][unit_price]"]`).val(price);
            recalcRequest();
        }

        // Close autocompletes when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.autocomplete-container').length) {
                $('.autocomplete-results').addClass('hidden');
            }
        });

        function removeRow(id) {
            $(`#req_row_${id}`).remove();
            recalcRequest();
        }

        function recalcRequest() {
            let total = 0;
            $('#requestItemsBody tr').each(function() {
                const rowId = this.id.replace('req_row_', '');
                const qty = parseFloat($(`input[name="items[${rowId}][quantity]"]`).val()) || 0;
                const price = parseFloat($(`input[name="items[${rowId}][unit_price]"]`).val()) || 0;
                const sub = qty * price;
                total += sub;
                $(`#subtotal_${rowId}`).text('$' + sub.toFixed(2));
            });
            $('#requestTotalDisplay').text('$ ' + total.toLocaleString('es-MX', { minimumFractionDigits: 2 }));
        }

        function openModal(mode = 'create') {
            if (mode === 'create') {
                $('#approvalProgress').addClass('hidden');
                $('#modalTitle').text('Nueva Gestión de Abastecimiento');
                $('#newRequestForm')[0].reset();
                $('#requestId').val('');
                $('#dateInput').val(new Date().toISOString().split('T')[0]);
                $('#statusBadge').text('NUEVO').css({'background-color': 'rgba(2, 22, 25, 0.05)', 'color': 'rgba(2, 22, 25, 0.6)'});
                $('#requestItemsBody').empty();
                addRow(); // Start with one empty row
                $('#addRowBtn').show();
                $('#creationNoteArea').show();
                $('#notesHistory').empty().parent().hide();
            }
            
            $('#saveBtn').show().text('Enviar para Aprobación');
            $('.item-select, input[name*="quantity"], input[name*="unit_price"], #supplierInput, #descInput').prop('disabled', false);
            $('#decisionSection').addClass('hidden');
            $('#decisionComments').val('');
            
            if (mode === 'view') {
                $('#saveBtn').hide();
                $('.item-select, input[name*="quantity"], input[name*="unit_price"], #supplierInput, #descInput').prop('disabled', true);
                $('#addRowBtn').hide();
            }

            $('#newRequestModal').removeClass('hidden').addClass('flex');
            $('body').addClass('overflow-hidden');
        }

        function closeModal() {
            $('#newRequestModal').addClass('hidden').removeClass('flex');
            $('body').removeClass('overflow-hidden');
        }

        function verSolicitud(id) {
            $.get('../api/get_request.php', { id: id }, function(response) {
                if (response.status === 'success') {
                    const d = response.data;
                    $('#requestId').val(d.id);
                    $('#modalTitle').text(`Solicitud #${d.id}`);
                    $('#descInput').val(d.description);
                    $('#dateInput').val(new Date(d.created_at).toLocaleDateString('es-MX'));
                    $('#supplierInput').val(d.supplier_id || '');
                    
                    const statusMap = { 'pending': 'PENDIENTE', 'approved': 'APROBADO', 'rejected': 'RECHAZADO', 'ordered': 'ORDENADO' };
                    const rawStatus = (d.status || 'pending').toLowerCase();
                    const statusText = statusMap[rawStatus] || rawStatus.toUpperCase();
                    
                    let textColor = '#BD9A5F';
                    if (rawStatus === 'approved' || rawStatus === 'ordered') textColor = '#10b981';
                    if (rawStatus === 'rejected') textColor = '#ef4444';
                    $('#statusInput').val(statusText).css({'color': textColor});

                    $('#notesHistory').empty().parent().show();
                    $('#creationNoteArea').hide();
                    
                    // Add creation note to history
                    $('#notesHistory').append(`
                        <div class="note-item">
                            <div class="note-dot"></div>
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-black uppercase text-[#021619]">${d.requester_name || 'Solicitante'}</span>
                                    <span class="text-[9px] text-[#021619]/40 font-bold italic">Creó la solicitud</span>
                                </div>
                                <p class="text-sm text-[#021619]/70 leading-relaxed">${d.description || 'Sin glosa inicial.'}</p>
                            </div>
                        </div>
                    `);

                    // Render Visual Progress & Add approval notes to history
                    $('#solicitanteStatus').html(`${d.requester_name || 'Desconocido'}: ✅`);
                    $('#approvalProgress').removeClass('hidden');
                    if (d.approval_steps) {
                        d.approval_steps.forEach((step, index) => {
                            const level = step.level;
                            const status = step.status;
                            const circle = $(`#level${level}Circle`);
                            const icon = $(`#level${level}Icon`);
                            const statusText = $(`#level${level}Status`);
                            
                            circle.removeClass('bg-white bg-emerald-500 bg-red-500 bg-amber-500 border-emerald-500 border-red-500 border-amber-500 text-white');
                            
                            if (status === 'approved') {
                                circle.addClass('bg-emerald-500 border-emerald-500 text-white');
                                icon.text('check');
                                statusText.html(`${step.approver_name || 'Desconocido'}: ✅`).addClass('text-emerald-600').removeClass('text-[#021619]/40');
                            } else if (status === 'rejected') {
                                circle.addClass('bg-red-500 border-red-500 text-white');
                                icon.text('close');
                                statusText.html(`${step.approver_name || 'Desconocido'}: ❌`).addClass('text-red-600').removeClass('text-[#021619]/40');
                            } else {
                                circle.addClass('bg-amber-500/10 border-amber-500 text-amber-600');
                                icon.text('hourglass_empty');
                                statusText.html(`${step.approver_name || 'Sin asignar'}: ⏳`).addClass('text-[#021619]/40').removeClass('text-emerald-600 text-red-600');
                            }

                            // If processed, add to notes history
                            if (status !== 'pending' && step.comments) {
                                const nextStep = d.approval_steps[index + 1];
                                const isFinalLevel = !nextStep;
                                const isNextLevelPending = nextStep && nextStep.status === 'pending';
                                const canEdit = (step.approver_id == CURRENT_USER_ID && (isFinalLevel || isNextLevelPending));

                                const dateStr = step.processed_at ? new Date(step.processed_at).toLocaleString('es-MX', {day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit'}) : '';
                                $('#notesHistory').append(`
                                    <div class="note-item" id="note_step_${step.step_id}">
                                        <div class="note-dot" style="background: ${status === 'rejected' ? '#ef4444' : '#10b981'}"></div>
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] font-black uppercase text-[#021619]">${step.approver_name}</span>
                                                    <span class="text-[9px] ${status === 'rejected' ? 'text-red-600' : 'text-emerald-600'} font-black uppercase tracking-tighter italic">${status === 'rejected' ? 'Rechazó' : 'Autorizó'}</span>
                                                    ${canEdit ? `<button onclick="editNote('step', ${step.step_id}, this)" class="text-[#BD9A5F] hover:text-[#021619] transition-colors bg-amber-500/10 p-1 rounded-full"><span class="material-symbols-outlined text-[14px]">edit</span></button>` : ''}
                                                </div>
                                                <span class="text-[9px] text-[#021619]/40 font-bold">${dateStr}</span>
                                            </div>
                                            <div class="note-content-wrapper">
                                                <p class="note-text text-sm text-[#021619]/70 leading-relaxed italic border-l-2 border-[#021619]/5 pl-3 py-1 font-headline font-medium">"${step.comments}"</p>
                                            </div>
                                        </div>
                                    </div>
                                `);
                            }
                        });
                    }

                    // Render Items
                    $('#requestItemsBody').empty();
                    if (d.items && d.items.length > 0) {
                        d.items.forEach(item => addRow(item));
                    } else {
                        // Support for legacy requests without item breakdown
                        addRow({
                            item_id: '',
                            unit: 'Servicio',
                            quantity: 1,
                            unit_price: d.total_amount,
                            subtotal: d.total_amount
                        });
                    }
                    
                    openModal('view');

                    if (rawStatus === 'pending' && d.current_approver_id == CURRENT_USER_ID) {
                        $('#decisionSection').removeClass('hidden');
                    } else {
                        $('#decisionSection').addClass('hidden');
                    }
                } else {
                    alert('Error: ' + response.message);
                }
            });
        }

        function editarSolicitud(id) {
            $.get('../api/get_request.php', { id: id }, function(response) {
                if (response.status === 'success') {
                    const d = response.data;
                    $('#requestId').val(d.id);
                    $('#modalTitle').text(`Editando Solicitud #${d.id}`);
                    $('#descInput').val(d.description);
                    $('#dateInput').val(new Date(d.created_at).toLocaleDateString('es-MX'));
                    $('#supplierInput').val(d.supplier_id || '');
                    
                    const statusMap = { 'pending': 'PENDIENTE', 'approved': 'APROBADO', 'rejected': 'RECHAZADO', 'ordered': 'ORDENADO' };
                    const rawStatus = (d.status || 'pending').toLowerCase();
                    $('#statusInput').val(statusMap[rawStatus] || rawStatus.toUpperCase()).css({'color': '#BD9A5F'});

                    $('#solicitanteStatus').html(`${d.requester_name || 'Desconocido'}: ✅`);
                    
                    // Render Visual Progress in Edit mode too
                    $('#approvalProgress').removeClass('hidden');
                    if (d.approval_steps) {
                        d.approval_steps.forEach(step => {
                            const level = step.level;
                            const status = step.status;
                            const circle = $(`#level${level}Circle`);
                            const icon = $(`#level${level}Icon`);
                            const statusText = $(`#level${level}Status`);
                            
                            circle.removeClass('bg-white bg-emerald-500 bg-red-500 bg-amber-500 border-emerald-500 border-red-500 border-amber-500 text-white');
                            
                            if (status === 'approved') {
                                circle.addClass('bg-emerald-500 border-emerald-500 text-white');
                                icon.text('check');
                                statusText.html(`${step.approver_name || 'Desconocido'}: ✅`).addClass('text-emerald-600').removeClass('text-[#021619]/40');
                            } else if (status === 'rejected') {
                                circle.addClass('bg-red-500 border-red-500 text-white');
                                icon.text('close');
                                statusText.html(`${step.approver_name || 'Desconocido'}: ❌`).addClass('text-red-600').removeClass('text-[#021619]/40');
                            } else {
                                circle.addClass('bg-amber-500/10 border-amber-500 text-amber-600');
                                icon.text('hourglass_empty');
                                statusText.html(`${step.approver_name || 'Sin asignar'}: ⏳`).addClass('text-[#021619]/40').removeClass('text-emerald-600 text-red-600');
                            }
                        });
                    }

                    // Render Items
                    $('#requestItemsBody').empty();
                    if (d.items && d.items.length > 0) {
                        d.items.forEach(item => addRow(item));
                    }
                    
                    openModal('edit');
                } else {
                    alert('Error: ' + response.message);
                }
            });
        }

        function eliminarSolicitud(id) {
            if (confirm('¿Estás seguro de eliminar esta solicitud?')) {
                $.ajax({
                    url: '../api/delete_request.php',
                    method: 'POST',
                    data: { id: id },
                    success: function(response) {
                        if (response.status === 'success') {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error crítico de comunicación.');
                    }
                });
            }
        }

        $('#newRequestForm').on('submit', function(e) {
            e.preventDefault();
            const btn = $('#saveBtn');
            const id = $('#requestId').val();
            const url = id ? '../api/update_request.php' : '../api/create_request.php';
            
            // Basic validation
            if ($('#requestItemsBody tr').length === 0) {
                alert('Debe añadir al menos una partida de insumos.');
                return;
            }

            btn.prop('disabled', true).text('PROCESANDO...');

            $.ajax({
                url: url,
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.status === 'success') {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    let msg = 'Error crítico de comunicación.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg += '\n\nDetalle: ' + xhr.responseJSON.message;
                    }
                    alert(msg);
                },
                complete: function() {
                    btn.prop('disabled', false).text(id ? 'ACTUALIZAR' : 'ENVIAR PARA APROBACIÓN');
                }
            });
        });

        // --- Approval Logic ---
        function procesarDecision(decision) {
            const id = $('#requestId').val();
            const comments = $('#decisionComments').val();
            
            if (!confirm(`¿Está seguro de ${decision === 'approve' ? 'APROBAR' : 'RECHAZAR'} esta solicitud?`)) return;

            $.ajax({
                url: '../api/process_approval.php',
                method: 'POST',
                data: { id: id, decision: decision, comments: comments },
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error en la comunicación con el motor de aprobación.');
                }
            });
        }

        // --- Notification Logic ---
        function fetchNotifications() {
            $.get('../api/get_notifications.php', function(response) {
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
                                <div class="p-4 border-b border-white/5 hover:bg-white/5 transition-all cursor-pointer group" onclick="verSolicitud(${n.request_id})">
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

        // Poll every 20 seconds
        setInterval(fetchNotifications, 20000);
        fetchNotifications();

        function editNote(type, id, btn) {
            const wrapper = $(btn).closest('.note-item').find('.note-content-wrapper');
            const currentText = wrapper.find('.note-text').text().replace(/^"|"$/g, '');
            
            wrapper.html(`
                <div class="flex flex-col gap-2 mt-2">
                    <textarea class="w-full bg-[#F7F5EB] border border-[#BD9A5F]/20 rounded-sm p-3 text-sm focus:border-[#BD9A5F] outline-none font-medium text-[#021619] italic" rows="2">${currentText}</textarea>
                    <div class="flex gap-2 justify-end">
                        <button onclick="cancelEditNote('${type}', ${id}, '${currentText.replace(/'/g, "\\'")}', this)" class="text-[10px] uppercase font-black text-[#021619]/40 hover:text-red-500">Cancelar</button>
                        <button onclick="saveNote('${type}', ${id}, this)" class="text-[10px] uppercase font-black text-emerald-600 hover:text-emerald-700">Guardar</button>
                    </div>
                </div>
            `);
        }

        function cancelEditNote(type, id, originalText, btn) {
            const wrapper = $(btn).closest('.note-content-wrapper');
            wrapper.html(`<p class="note-text text-sm text-[#021619]/70 leading-relaxed italic border-l-2 border-[#021619]/5 pl-3 py-1 font-headline font-medium">"${originalText}"</p>`);
        }

        function saveNote(type, id, btn) {
            const wrapper = $(btn).closest('.note-content-wrapper');
            const newComment = wrapper.find('textarea').val();
            const requestId = $('#requestId').val();

            $(btn).prop('disabled', true).text('...');

            $.post('../api/update_comment.php', {
                stepId: type === 'step' ? id : 0,
                requestId: type === 'request' ? id : 0,
                comment: newComment
            }, function(response) {
                if (response.status === 'success') {
                    // Update UI without full reload
                    wrapper.html(`<p class="note-text text-sm text-[#021619]/70 leading-relaxed italic border-l-2 border-[#021619]/5 pl-3 py-1 font-headline font-medium">"${newComment}"</p>`);
                } else {
                    alert('Error: ' + response.message);
                    $(btn).prop('disabled', false).text('Guardar');
                }
            }, 'json');
        }

        // Close modal on escape
        $(document).keyup(function(e) {
            if (e.key === "Escape") closeModal();
        });
        // --- OC WORKFLOW ---
        function prepararOC(id) {
            $.get('../api/get_request.php', { id: id }, function(response) {
                if (response.status === 'success') {
                    const d = response.data;
                    $('#ocRequestId').val(id);
                    $('#ocSourceFolio').text('#PR-' + id);
                    $('#ocSupplierId').val(d.supplier_id || '');
                    
                    const container = $('#ocItemsBody');
                    container.empty();
                    let total = 0;

                    const items = (d.items && d.items.length > 0) ? d.items : [{
                        description: d.description,
                        quantity: 1,
                        unit_price: d.total_amount
                    }];

                    items.forEach((item, index) => {
                        const sub = parseFloat(item.quantity) * parseFloat(item.unit_price);
                        total += sub;
                        container.append(`
                            <tr class="border-b border-[#021619]/5">
                                <td class="px-4 py-4">
                                    <input type="text" class="bg-transparent w-full italic outline-none border-b border-dashed border-emerald-600/20 focus:border-emerald-600 oc-input" value="${item.description}" name="items[${index}][description]">
                                </td>
                                <td class="px-4 py-4">
                                    <input type="number" step="0.01" class="w-full bg-emerald-600/5 px-2 py-1 outline-none text-center oc-input" value="${item.quantity}" name="items[${index}][quantity]" onchange="recalcOC()">
                                </td>
                                <td class="px-4 py-4">
                                    <input type="number" step="0.01" class="w-full bg-emerald-600/5 px-2 py-1 outline-none text-center oc-input" value="${item.unit_price}" name="items[${index}][unit_price]" onchange="recalcOC()">
                                </td>
                                <td class="px-4 py-4 text-right font-bold text-[#021619]" id="item_sub_${index}">$${sub.toFixed(2)}</td>
                            </tr>
                        `);
                    });
                    $('#ocTotal').text('$ ' + total.toLocaleString('es-MX', { minimumFractionDigits: 2 }));
                    $('#ocModal').removeClass('hidden').addClass('flex');
                } else {
                    alert('Error: ' + response.message);
                }
            }, 'json');
        }

        function recalcOC() {
            let total = 0;
            $('#ocItemsBody tr').each(function(index) {
                const qty = parseFloat($(`input[name="items[${index}][quantity]"]`).val()) || 0;
                const price = parseFloat($(`input[name="items[${index}][unit_price]"]`).val()) || 0;
                const sub = qty * price;
                total += sub;
                $(`#item_sub_${index}`).text('$ ' + sub.toFixed(2));
            });
            $('#ocTotal').text('$ ' + total.toLocaleString('es-MX', { minimumFractionDigits: 2 }));
        }

        $('#ocForm').on('submit', function(e) {
            e.preventDefault();
            const supplierId = $('#ocSupplierId').val();
            if(!supplierId) { alert('Por favor seleccione un proveedor.'); return; }

            const items = [];
            $('#ocItemsBody tr').each(function(index) {
                items.push({
                    description: $(`input[name="items[${index}][description]"]`).val(),
                    quantity: $(`input[name="items[${index}][quantity]"]`).val(),
                    unit_price: $(`input[name="items[${index}][unit_price]"]`).val()
                });
            });

            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).text('GENERANDO...');

            $.post('../api/create_order.php', {
                requestId: $('#ocRequestId').val(),
                supplierId: supplierId,
                notes: $('#ocNotes').val(),
                items: JSON.stringify(items)
            }, function(response) {
                if (response.status === 'success') {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                    btn.prop('disabled', false).text('EMITIR ORDEN DE COMPRA');
                }
            }, 'json');
        });

        // --- MULTI-USER SYNC ---
        let lastSyncTime = Date.now();

        function loadRequests() {
            $.get('../api/list_requests_ajax.php', function(res) {
                if(res.status === 'success') {
                    renderRequestList(res.requests);
                    updateStats(res.stats);
                    lastSyncTime = Date.now();
                }
            });
        }

        function updateStats(stats) {
            $('#statMonthTotal').text(new Intl.NumberFormat('es-MX').format(Math.floor(stats.monthTotal)));
            $('#statPendingCount').text(String(stats.pendingCount).padStart(2, '0'));
            $('#statEfficiencyBar').css('width', stats.efficiency + '%');
            $('#statEfficiencyLabel').text(stats.efficiency + '% ejecución mensual');
        }

        function renderRequestList(requests) {
            const list = $('#mainRequestsList');
            list.empty();
            
            const statusMap = {
                'pending': {label: 'PENDIENTE', bg: 'bg-amber-100', text: 'text-amber-800', icon: 'schedule'},
                'approved': {label: 'APROBADO', bg: 'bg-emerald-100', text: 'text-emerald-800', icon: 'verified'},
                'ordered':  {label: 'ORDEN GENERADA', bg: 'bg-blue-100', text: 'text-blue-800', icon: 'shopping_cart'},
                'rejected': {label: 'RECHAZADO', bg: 'bg-red-100', text: 'text-red-800', icon: 'cancel'}
            };

            requests.forEach(row => {
                const status = (row.status || 'pending').toLowerCase();
                const cfg = statusMap[status] || {label: row.status.toUpperCase(), bg: 'bg-gray-100', text: 'text-gray-800', icon: 'info'};
                
                let actionButtons = `
                    <button onclick="verSolicitud(${row.id})" class="w-10 h-10 rounded-full bg-[#BD9A5F]/10 text-[#BD9A5F] flex items-center justify-center hover:bg-[#BD9A5F] hover:text-white transition-all shadow-sm group-hover:scale-110" title="Detalles">
                        <span class="material-symbols-outlined text-xs">visibility</span>
                    </button>
                `;

                if(row.status === 'approved') {
                    actionButtons += `
                        <button onclick="prepararOC(${row.id})" class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-all shadow-sm group-hover:scale-110" title="Generar Orden de Compra">
                            <span class="material-symbols-outlined text-xs">shopping_cart_checkout</span>
                        </button>
                    `;
                }

                actionButtons += `
                    <button onclick="editarSolicitud(${row.id})" class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="Editar">
                        <span class="material-symbols-outlined text-xs">edit</span>
                    </button>
                    <button onclick="eliminarSolicitud(${row.id})" class="w-8 h-8 rounded-full bg-red-50 text-red-600 flex items-center justify-center hover:bg-red-600 hover:text-white transition-all shadow-sm" title="Eliminar">
                        <span class="material-symbols-outlined text-xs">delete</span>
                    </button>
                `;

                const decisionBadge = row.is_my_decision ? '<span class="mr-2 px-2 py-1 bg-[#BD9A5F] text-[#021619] text-[8px] font-black uppercase tracking-widest rounded-sm animate-pulse shadow-[0_0_8px_rgba(189,154,95,0.4)]">Su Decisión</span>' : '';
                const dateFormataed = new Date(row.created_at).toLocaleDateString('es-MX');

                const tr = `
                    <tr class="hover:bg-[#021619]/5 transition-colors group">
                        <td class="py-4 px-4 border-b border-[#021619]/5 font-bold text-[#BD9A5F] text-xs">#PR-${row.id}</td>
                        <td class="py-4 px-4 border-b border-[#021619]/5 font-headline font-bold text-sm text-[#021619] opacity-70">${dateFormataed}</td>
                        <td class="py-4 px-4 border-b border-[#021619]/5">
                            <div class="flex flex-col">
                                <span class="font-headline font-bold text-sm text-[#021619]">${row.supplier_name}</span>
                                <span class="text-[9px] text-[#021619]/40 font-bold uppercase tracking-tighter truncate max-w-[200px]">${row.description}</span>
                            </div>
                        </td>
                        <td class="py-4 px-4 border-b border-[#021619]/5 font-headline font-bold text-sm text-[#021619]">$ ${new Intl.NumberFormat('es-MX', {minimumFractionDigits: 2}).format(row.total_amount)}</td>
                        <td class="py-4 px-4 border-b border-[#021619]/5 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <div class="px-3 py-1.5 ${cfg.bg} ${cfg.text} rounded-full flex items-center gap-1.5 shadow-sm border border-black/5">
                                    <span class="material-symbols-outlined text-sm">${cfg.icon}</span>
                                    <span class="font-headline font-black text-[11px] tracking-widest uppercase">${cfg.label}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[10px] text-[#021619] font-black tracking-tighter">NIVEL ${row.current_level}/3</span>
                                    ${row.status === 'pending' ? '<span class="material-symbols-outlined text-[12px] text-emerald-600 notif-pulse">chat</span>' : ''}
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-4 border-b border-[#021619]/5 text-right">
                            <div class="flex justify-end gap-2 items-center">
                                ${decisionBadge}
                                ${actionButtons}
                            </div>
                        </td>
                    </tr>
                `;
                list.append(tr);
            });
        }

        $(document).ready(function() {
            loadRequests();
            const intervalSecs = parseInt("<?php echo $dashboardSettings['dashboard_refresh_interval'] ?? '60'; ?>") || 60;
            setInterval(loadRequests, intervalSecs * 1000); 
        });
    </script>
</body>
</html>
