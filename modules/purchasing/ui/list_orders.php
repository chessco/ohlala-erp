<?php
/**
 * UI: Purchase Order Ledger (Listado de Órdenes de Compra)
 * Location: modules/purchasing/ui/list_orders.php
 */
include('../../../conexion.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['autenticado'])) {
    header("Location: ../../../index.php");
    exit();
}

$current_user_id = $_SESSION['usuario_id'];
$rol_actual      = isset($_SESSION['usuario_rol']) ? strtolower($_SESSION['usuario_rol']) : '';
$esAdmin         = ($rol_actual === 'admin');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Órdenes de Compra - Ohlala Executive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        :root {
            --primary-ink: #021619;
            --artisanal-gold: #BD9A5F;
            --paper-cream: #F7F5EB;
        }
        body { font-family: 'Manrope', sans-serif; background-color: var(--primary-ink); color: var(--paper-cream); }
        .font-headline { font-family: 'Playfair Display', serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(189, 154, 95, 0.05); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(189, 154, 95, 0.3); border-radius: 10px; }
        .torn-edge {
            mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 24" preserveAspectRatio="none"><path d="M0 24V0c19.6 1.7 39.1 2.6 58.7 2.6 19.4 0 38.7-.3 58.1-.8 19.4-.5 38.8-1.2 58.2-1.2 19.3 0 38.6.6 57.9 1.8 19.2 1.2 38.4 2.8 57.7 2.8 19.2 0 38.4-1.5 57.6-2.5 19.2-1.1 38.4-1.6 57.6-1.1 19.3.5 38.5 1.7 57.8 2.5 19.2.9 38.5 1.3 57.7 1.3 19.2 0 38.4-.4 57.6-1.1s38.4-1.8 57.6-2.3c19.2-.5 38.4-.5 57.6 0 19.2.5 38.4 1.5 57.6 2s38.4.5 57.6 0c19.2-.5 38.4-1.5 57.6-2s38.4-.5 57.6 0c19.2.5 38.4 1.5 57.6 2 19.2.5 38.4.5 57.6 0 19.2-.5 38.4-1.5 57.6-2 19.2-.5 38.4-.5 57.6 0 19.2.5 38.4 1.5 57.6 2 19.2.5 38.4.5 57.6 0z" fill="white"/></svg>');
        }
    </style>
</head>
<body class="min-h-screen pb-20 md:pb-0 scroll-smooth custom-scrollbar">

    <!-- Global Navigation Header -->
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

                <a class="font-headline border-b-2 border-[#021619] pb-1" href="list_requests.php">Compras</a>
                <?php if($esAdmin): ?>
                    <a class="font-headline opacity-80 hover:opacity-100" href="settings.php">Configuración</a>
                <?php endif; ?>
            </nav>

            <div class="flex items-center gap-3 px-4 py-1 border-l border-[#021619]/10 ml-2">
                <span class="material-symbols-outlined text-[#021619]/60 font-variation-settings-fill-1">account_circle</span>
                <div class="flex flex-col text-left">
                    <span class="text-[10px] font-black text-[#021619] leading-none uppercase tracking-tighter"><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></span>
                    <span class="text-[8px] font-bold text-[#021619]/40 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($_SESSION['usuario_rol']); ?></span>
                </div>
            </div>

            <button onclick="location.href='../../../logout.php'" class="bg-[#021619] text-[#F7F5EB] px-6 py-2 font-headline font-medium hover:bg-[#021619]/90 active:scale-95 transition-all">
                Salir
            </button>
        </div>
    </header>
                Salir
            </button>
        </div>
    </header>

    <!-- Header Section -->
    <header class="p-6 md:p-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 border-b border-white/5 bg-[#021619]">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-600 rounded-sm flex items-center justify-center shadow-lg transform rotate-2">
                <span class="material-symbols-outlined text-paper-cream text-2xl">history_edu</span>
            </div>
            <div>
                <h1 class="text-3xl md:text-4xl font-headline font-bold text-primary tracking-tight">Órdenes de Compra</h1>
                <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.4em] opacity-80">Libro Maestro de Abastecimiento (V3.1)</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <!-- Dropdown Catálogos -->
            <div class="relative group">
                <button class="px-6 py-3 bg-[#BD9A5F]/10 border border-[#BD9A5F]/30 text-[#BD9A5F] text-[10px] font-black uppercase tracking-widest hover:bg-[#BD9A5F] hover:text-background transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">widgets</span> Catálogos <span class="material-symbols-outlined text-[10px]">expand_more</span>
                </button>
                <div class="absolute left-0 mt-2 w-56 bg-[#021619] border border-[#BD9A5F]/30 shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50 rounded-sm overflow-hidden text-[#F7F5EB]">
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

            <a href="list_requests.php" class="bg-[#BD9A5F] text-background px-8 py-3 font-headline font-bold shadow-2xl hover:translate-y-[-2px] transition-all flex items-center gap-2 rounded-sm active:scale-95 group">
                <span class="material-symbols-outlined group-hover:rotate-12 transition-transform">list_alt</span> SOLICITUDES
            </a>
        </div>
    </header>

    <main class="max-w-[1400px] mx-auto p-6 md:p-8">
        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <?php 
                $resStat = mysqli_query($conexion, "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) as received,
                    SUM(total_amount) as total_money 
                    FROM pur_orders");
                $stats = mysqli_fetch_assoc($resStat);
            ?>
            <div class="bg-[#BD9A5F]/10 p-8 border border-[#BD9A5F]/20 relative group">
                <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.2em] mb-4">Total Emitidas</p>
                <p class="text-4xl font-headline text-[#F7F5EB]"><?php echo str_pad($stats['total'] ?? 0, 2, '0', STR_PAD_LEFT); ?></p>
            </div>
            <div class="bg-[#BD9A5F]/10 p-8 border border-[#BD9A5F]/20 relative group">
                <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.2em] mb-4">En Tránsito</p>
                <p class="text-4xl font-headline text-amber-500"><?php echo str_pad($stats['sent'] ?? 0, 2, '0', STR_PAD_LEFT); ?></p>
            </div>
            <div class="bg-[#BD9A5F]/10 p-8 border border-[#BD9A5F]/20 relative group">
                <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.2em] mb-4">Recibidas / OK</p>
                <p class="text-4xl font-headline text-emerald-500"><?php echo str_pad($stats['received'] ?? 0, 2, '0', STR_PAD_LEFT); ?></p>
            </div>
            <div class="bg-[#BD9A5F]/10 p-8 border border-[#BD9A5F]/20 relative group">
                <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.2em] mb-4">Inversión Final</p>
                <p class="text-2xl font-headline text-[#F7F5EB]">$<?php echo number_format($stats['total_money'] ?? 0, 2); ?></p>
            </div>
        </div>

        <!-- Table Container -->
        <section class="bg-[#F7F5EB] text-[#021619] p-10 shadow-2xl relative rounded-t-xl overflow-hidden">
            <div class="absolute bottom-[-10px] left-0 w-full h-4 bg-[#F7F5EB] torn-edge transform z-10"></div>
            
            <div class="flex justify-between items-center mb-10">
                <h2 class="font-headline text-3xl">Control de Órdenes</h2>
                <div class="flex gap-4">
                    <select id="statusFilter" class="bg-[#021619]/5 border-b-2 border-[#021619]/10 px-4 py-2 outline-none text-[10px] font-black uppercase tracking-widest focus:border-[#BD9A5F]">
                        <option value="">Todos los Estados</option>
                        <option value="sent">Enviada</option>
                        <option value="received">Recibida</option>
                        <option value="paid">Pagada</option>
                    </select>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b-2 border-[#021619]/10 text-[10px] uppercase tracking-[0.2em] font-black text-[#021619]/40">
                            <th class="pb-6 px-4">Folio OC</th>
                            <th class="pb-6 px-4">Fecha</th>
                            <th class="pb-6 px-4">Proveedor</th>
                            <th class="pb-6 px-4">Importe</th>
                            <th class="pb-6 px-4 text-center">Estado</th>
                            <th class="pb-6 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="font-body">
                        <?php 
                        $sqlL = "SELECT o.*, s.name as supplier_name 
                                 FROM pur_orders o 
                                 LEFT JOIN pur_suppliers s ON o.supplier_id = s.id 
                                 ORDER BY o.id DESC";
                        $resO = mysqli_query($conexion, $sqlL);
                        if (mysqli_num_rows($resO) == 0):
                        ?>
                        <tr>
                            <td colspan="6" class="py-20 text-center text-[#021619]/30 italic">No hay órdenes de compra generadas.</td>
                        </tr>
                        <?php endif; ?>
                        <?php 
                        while($row = mysqli_fetch_assoc($resO)): 
                            $statusMap = [
                                'draft' => ['l' => 'Borrador', 'c' => 'bg-gray-100 text-gray-800'],
                                'sent' => ['l' => 'Enviada', 'c' => 'bg-amber-100 text-amber-800'],
                                'received' => ['l' => 'Recibida', 'c' => 'bg-emerald-100 text-emerald-800'],
                                'paid' => ['l' => 'Pagada', 'c' => 'bg-blue-100 text-blue-800'],
                                'cancelled' => ['l' => 'Cancelada', 'c' => 'bg-red-100 text-red-800']
                            ];
                            $cfg = $statusMap[$row['status']] ?? ['l' => $row['status'], 'c' => 'bg-gray-100'];
                        ?>
                        <tr class="hover:bg-[#021619]/5 transition-colors group">
                            <td class="py-6 px-4 border-b border-[#021619]/5 font-headline font-bold text-[#BD9A5F]"><?php echo $row['folio']; ?></td>
                            <td class="py-6 px-4 border-b border-[#021619]/5 text-[11px] opacity-60"><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                            <td class="py-6 px-4 border-b border-[#021619]/5 font-bold text-sm"><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                            <td class="py-6 px-4 border-b border-[#021619]/5 font-headline font-bold text-lg">$<?php echo number_format($row['total_amount'], 2); ?></td>
                            <td class="py-6 px-4 border-b border-[#021619]/5 text-center">
                                <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest <?php echo $cfg['c']; ?>">
                                    <?php echo $cfg['l']; ?>
                                </span>
                            </td>
                            <td class="py-6 px-4 border-b border-[#021619]/5 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="view_order.php?id=<?php echo $row['id']; ?>" class="w-10 h-10 rounded-full bg-emerald-600/10 text-emerald-600 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="Ver / Imprimir">
                                        <span class="material-symbols-outlined text-sm">print</span>
                                    </a>
                                    <button onclick="updateStatus(<?php echo $row['id']; ?>)" class="w-10 h-10 rounded-full bg-[#021619]/5 text-[#021619]/40 flex items-center justify-center hover:bg-[#021619] hover:text-white transition-all shadow-sm" title="Actualizar Estado">
                                        <span class="material-symbols-outlined text-sm">sync</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>

        function updateStatus(id) {
            const next = prompt('Ingrese el nuevo estado (sent, received, paid, cancelled):');
            if (next) {
                $.post('../api/update_order_status.php', { id: id, status: next }, function(r) {
                    if (r.status === 'success') location.reload(); else alert('Error: ' + r.message);
                }, 'json');
            }
        }

        $('#statusFilter').on('change', function() {
            const val = $(this).val().toLowerCase();
            if (!val) { $('tbody tr').show(); return; }
            $('tbody tr').each(function() {
                const text = $(this).find('td:nth-child(5)').text().toLowerCase();
                $(this).toggle(text.indexOf(val) > -1);
            });
        });
    </script>
</body>
</html>
