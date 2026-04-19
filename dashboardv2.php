<?php
/**
 * PROYECTO: PEDIDOS OHLALA V2.0 (PREMIUM)
 * DASHBOARD EJECUTIVO - ESTILO STITCH (LUJO SILENCIOSO)
 */
include('conexion.php');

// --- VARIABLES DE SESIÓN ---
$usuario_id_sesion = $_SESSION['usuario_id'];
$nombre_vendedor   = isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : 'Usuario';
$rol_actual        = isset($_SESSION['usuario_rol']) ? $_SESSION['usuario_rol'] : '';
$esAdmin           = ($rol_actual === 'admin'); 

// --- ESTADÍSTICAS ---
$filtro_usuario = ($esAdmin) ? "" : " AND id_usuario = '$usuario_id_sesion'";
$filtro_tabla   = ($esAdmin) ? "" : " WHERE id_usuario = '$usuario_id_sesion'";

// 1. Total Pedidos
$res_pedidos = mysqli_query($conexion, "SELECT COUNT(*) as total FROM pedidos WHERE 1=1 $filtro_usuario");
$total_pedidos = mysqli_fetch_assoc($res_pedidos)['total'] ?? 0;

// 2. Clientes
$res_clientes = mysqli_query($conexion, "SELECT COUNT(*) as total FROM clientes");
$total_clientes = mysqli_fetch_assoc($res_clientes)['total'] ?? 0;

// 3. Productos
$res_prod = mysqli_query($conexion, "SELECT COUNT(*) as total FROM productos");
$total_productos = mysqli_fetch_assoc($res_prod)['total'] ?? 0;

// 4. Cliente asociado al usuario actual
$res_mi_cliente = mysqli_query($conexion, "SELECT nombre FROM clientes WHERE id_usuario = '$usuario_id_sesion' LIMIT 1");
$mi_cliente_asociado = ($row_c = mysqli_fetch_assoc($res_mi_cliente)) ? $row_c['nombre'] : '';

?>
<!DOCTYPE html>
<html lang="es" class="light">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Ohlala ERP - Gestión Ejecutiva Premium</title>
    
    <!-- Frameworks & Fonts -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200;400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "primary": "#041627",
                        "secondary": "#4e6073",
                        "background": "#f8f9fa"
                    },
                    "fontFamily": {
                        "headline": ["Manrope", "sans-serif"],
                        "body": ["Inter", "sans-serif"]
                    }
                },
            },
        }
    </script>

    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        h1, h2, h3, .font-headline { font-family: 'Manrope', sans-serif; }
        .glass-card { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); }
        .modal { background: rgba(0,0,0,0.5); }
        .resultados-ajax { z-index: 1100; max-height: 250px; overflow-y: auto; position: absolute; width: 100%; border: 1px solid #edeeef; background: white; border-radius: 0 0 8px 8px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); display: none; }
    </style>
</head>

<body class="text-on-surface">

    <!-- Sidebar -->
    <aside class="fixed left-0 top-0 h-screen w-64 bg-[#f8f9fa] flex flex-col p-6 space-y-8 shadow-[20px_0_40px_rgba(0,0,0,0.02)] z-40 hidden md:flex">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center text-white">
                <span class="material-symbols-outlined">corporate_fare</span>
            </div>
            <div>
                <h1 class="text-xl font-bold text-primary font-headline tracking-tight">Ohlala ERP</h1>
                <p class="text-[10px] uppercase tracking-[0.2em] text-secondary font-medium">The Curated Authority</p>
            </div>
        </div>
        <nav class="flex-1 space-y-2 pt-4">
            <a class="flex items-center space-x-3 px-4 py-3 bg-white text-primary rounded-lg shadow-sm font-bold transition-all duration-200" href="dashboard.php">
                <span class="material-symbols-outlined">dashboard_customize</span>
                <span class="text-sm">Dashboard</span>
            </a>
            <a class="flex items-center space-x-3 px-4 py-3 text-secondary opacity-70 hover:opacity-100 hover:translate-x-1 transition-transform duration-200" href="modules/purchasing/ui/list_requests.php">
                <span class="material-symbols-outlined">shopping_cart</span>
                <span class="text-sm">Compras</span>
            </a>
            <a class="flex items-center space-x-3 px-4 py-3 text-secondary opacity-70 hover:opacity-100 hover:translate-x-1 transition-transform duration-200" href="#">
                <span class="material-symbols-outlined">receipt_long</span>
                <span class="text-sm">Pedidos</span>
            </a>
            <?php if($esAdmin): ?>
            <a class="flex items-center space-x-3 px-4 py-3 text-secondary opacity-70 hover:opacity-100 hover:translate-x-1 transition-transform duration-200" href="clientes.php">
                <span class="material-symbols-outlined">badge</span>
                <span class="text-sm">Clientes</span>
            </a>
            <?php endif; ?>
        </nav>
        <div class="pt-6 border-t border-surface-container-low space-y-2">
            <a class="flex items-center space-x-3 px-4 py-3 text-secondary opacity-70 hover:opacity-100" href="logout">
                <span class="material-symbols-outlined">logout</span>
                <span class="text-sm">Cerrar Sesión</span>
            </a>
        </div>
    </aside>

    <!-- Top Bar -->
    <header class="fixed top-0 right-0 w-full md:w-[calc(100%-16rem)] z-30 bg-[#f8f9fa]/80 backdrop-blur-md h-20 flex justify-between items-center px-8 shadow-sm">
        <div class="flex items-center bg-[#f3f4f5] rounded-full px-4 py-2 w-64 md:w-96">
            <span class="material-symbols-outlined text-secondary mr-2">search</span>
            <input class="bg-transparent border-none focus:ring-0 text-sm w-full" placeholder="Buscar..." type="text"/>
        </div>
        <div class="flex items-center space-x-6">
            <div class="flex items-center space-x-3 pl-6 border-l border-gray-200">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold text-primary font-headline uppercase tracking-tighter"><?php echo $nombre_vendedor; ?></p>
                    <p class="text-[10px] text-secondary"><?php echo strtoupper($rol_actual); ?></p>
                </div>
                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold">
                    <?php echo substr($nombre_vendedor, 0, 1); ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="md:ml-64 pt-24 p-4 md:p-8 min-h-screen bg-background">
        <!-- Header -->
        <section class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div>
                <h2 class="text-3xl font-extrabold text-primary tracking-tight mb-1">Resumen Ejecutivo</h2>
                <p class="text-secondary">Bienvenido al nuevo centro de control de Ohlala.</p>
            </div>
            <button onclick="prepararNuevoPedido()" class="glass-card px-6 py-3 rounded-xl border border-white/20 shadow-xl flex items-center space-x-2 text-primary font-headline font-bold hover:scale-105 active:scale-95 transition-all">
                <span class="material-symbols-outlined">add_circle</span>
                <span>NUEVO PEDIDO</span>
            </button>
        </section>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-50 flex flex-col justify-between">
                <div class="w-12 h-12 rounded-lg bg-primary/5 flex items-center justify-center text-primary mb-4">
                    <span class="material-symbols-outlined">receipt_long</span>
                </div>
                <div>
                    <p class="text-secondary text-xs font-headline uppercase tracking-wider mb-1">Pedidos Totales</p>
                    <p class="text-3xl font-black text-primary tracking-tighter"><?php echo number_format($total_pedidos); ?></p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-50 flex flex-col justify-between">
                <div class="w-12 h-12 rounded-lg bg-primary/5 flex items-center justify-center text-primary mb-4">
                    <span class="material-symbols-outlined">group</span>
                </div>
                <div>
                    <p class="text-secondary text-xs font-headline uppercase tracking-wider mb-1">Clientes</p>
                    <p class="text-3xl font-black text-primary tracking-tighter"><?php echo number_format($total_clientes); ?></p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-50 flex flex-col justify-between">
                <div class="w-12 h-12 rounded-lg bg-primary/5 flex items-center justify-center text-primary mb-4">
                    <span class="material-symbols-outlined">inventory</span>
                </div>
                <div>
                    <p class="text-secondary text-xs font-headline uppercase tracking-wider mb-1">Productos</p>
                    <p class="text-3xl font-black text-primary tracking-tighter"><?php echo number_format($total_productos); ?></p>
                </div>
            </div>
        </div>

        <!-- NEW: PURCHASING QUICK ACCESS -->
        <section class="mb-10">
            <h3 class="text-sm font-bold text-secondary uppercase tracking-[0.2em] mb-6">Módulos Estratégicos</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <a href="modules/purchasing/ui/list_requests.php" class="group bg-primary text-white p-8 rounded-3xl shadow-2xl hover:scale-[1.02] transition-all relative overflow-hidden">
                    <div class="relative z-10">
                        <span class="material-symbols-outlined text-4xl mb-4 opacity-50 group-hover:opacity-100 transition-opacity">shopping_cart</span>
                        <h4 class="text-2xl font-black font-headline mb-2">Módulo de Compras</h4>
                        <p class="text-white/60 text-sm">Aprobaciones jerárquicas y gestión de suministros.</p>
                        <div class="mt-6 flex items-center text-xs font-bold uppercase tracking-widest gap-2">
                            <span>Entrar ahora</span>
                            <span class="material-symbols-outlined text-sm pt-0.5 group-hover:translate-x-1 transition-transform">arrow_forward</span>
                        </div>
                    </div>
                    <!-- Subtle background decoration -->
                    <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/5 rounded-full blur-3xl group-hover:bg-white/10 transition-colors"></div>
                </a>
            </div>
        </section>

        <!-- Recent Table placeholder -->
        <div class="bg-white p-12 rounded-3xl border border-gray-50 text-center opacity-50">
            <span class="material-symbols-outlined text-4xl mb-4">dataset</span>
            <p class="text-sm font-medium">Visualización de datos activa en V2</p>
        </div>
    </main>

    <!-- Original Modals would go here for full functionality -->
</body>
</html>
