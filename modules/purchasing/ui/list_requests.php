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

// --- REAL STATS ---
$monthStart = date('Y-m-01 00:00:00');
$resMonthTotal = mysqli_query($conexion, "SELECT SUM(total_amount) as total FROM pur_requests WHERE created_at >= '$monthStart' AND status = 'approved'");
$monthTotal = mysqli_fetch_assoc($resMonthTotal)['total'] ?? 0;

$resPendingCount = mysqli_query($conexion, "SELECT COUNT(*) as total FROM pur_requests WHERE status = 'pending'");
$pendingCount = mysqli_fetch_assoc($resPendingCount)['total'] ?? 0;
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
</head>
<body class="text-on-background font-body min-h-screen">
    <header class="flex justify-between items-center w-full px-6 py-4 z-50 bg-[#BD9A5F] sticky top-0 shadow-lg">
        <div class="flex items-center gap-4">
            <h1 class="text-2xl font-headline font-bold text-[#F7F5EB]">Ohlala! Boulangerie Bistro</h1>
        </div>
        <div class="hidden md:flex gap-8 items-center">
            <nav class="flex gap-6">
                <a class="font-headline text-[#F7F5EB]/80 hover:text-[#F7F5EB]" href="../../../dashboard.php">Inicio</a>
                <a class="font-headline text-[#F7F5EB] border-b-2 border-[#F7F5EB] pb-1" href="list_requests.php">Compras</a>
                <?php if ($esAdmin): ?>
                <a class="font-headline text-[#F7F5EB]/80 hover:text-[#F7F5EB]" href="settings.php">Configuración</a>
                <a class="font-headline text-[#F7F5EB]/80 hover:text-[#F7F5EB]" href="../../../usuarios.php">Usuarios</a>
                <?php endif; ?>
            </nav>
            
            <!-- Notification Bell -->
            <div class="relative group">
                <button id="notifBell" class="text-[#F7F5EB]/80 hover:text-[#F7F5EB] transition-all relative">
                    <span class="material-symbols-outlined text-2xl">notifications</span>
                    <span id="notifBadge" class="absolute -top-1 -right-1 w-4 h-4 bg-red-600 border-2 border-[#BD9A5F] rounded-full hidden animate-pulse"></span>
                </button>
                
                <!-- Notification Dropdown -->
                <div id="notifPanel" class="absolute right-0 mt-4 w-80 bg-[#021619]/95 backdrop-blur-xl border border-[#BD9A5F]/30 shadow-2xl rounded-lg hidden z-[1000] overflow-hidden">
                    <header class="p-4 border-b border-white/10 flex justify-between items-center">
                        <h3 class="text-xs font-black uppercase tracking-widest text-[#BD9A5F]">Notificaciones</h3>
                        <span id="notifCount" class="text-[10px] text-white/40">0 nuevas</span>
                    </header>
                    <div id="notifList" class="max-h-96 overflow-y-auto">
                        <!-- Notifications will be injected here -->
                        <div class="p-8 text-center text-white/20 italic text-xs">No hay notificaciones recientes</div>
                    </div>
                </div>
            </div>

            <button onclick="location.href='../../../logout.php'" class="bg-[#021619] text-[#F7F5EB] px-6 py-2 font-headline font-medium">Cerrar Sesión</button>
        </div>
    </header>

    <main class="p-6 md:p-10 space-y-12 max-w-7xl mx-auto">
        <!-- Module Header -->
        <header class="flex flex-col md:flex-row justify-between items-end gap-6 border-b border-white/5 pb-8">
            <div>
                <a href="../../../dashboard.php" class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mb-4 flex items-center hover:opacity-70 transition-all">
                    <span class="material-symbols-outlined text-sm mr-2">arrow_back</span> Regresar al Centro Operativo
                </a>
                <h1 class="text-5xl font-headline text-[#F7F5EB]">Centro de Compras</h1>
                <p class="text-primary/60 font-body italic mt-2">Logística y Abastecimiento de Insumos Premium.</p>
            </div>
            <button onclick="openModal()" class="bg-[#BD9A5F] text-white px-10 py-4 font-headline font-bold shadow-2xl hover:scale-105 active:scale-95 transition-all flex items-center gap-3">
                <span class="material-symbols-outlined">shopping_basket</span>
                NUEVA SOLICITUD
            </button>
        </header>

        <!-- Hero Section -->
        <section class="relative h-60 w-full overflow-hidden rounded-xl shadow-2xl group mb-12">
            <img src="https://images.unsplash.com/photo-1550989460-0adf9ea622e2?q=80&w=2070&auto=format&fit=crop" alt="Abastecimiento" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"/>
            <div class="absolute inset-0 bg-gradient-to-t from-[#021619] via-[#021619]/20 opacity-90"></div>
            <div class="absolute bottom-0 left-0 p-8">
                <h2 class="text-3xl font-headline text-[#F7F5EB] mb-1 italic">La Selección de lo Mejor</h2>
                <p class="text-primary text-sm font-headline tracking-[0.2em] uppercase font-bold opacity-80">Control de Abastecimiento & Calidad</p>
            </div>
        </section>

        <!-- Artisanal Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-[#BD9A5F]/10 p-8 border border-[#BD9A5F]/20 relative group">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <span class="material-symbols-outlined text-7xl text-white">account_balance_wallet</span>
                </div>
                <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.2em] mb-4">Total Aprobado (Mes)</p>
                <p class="text-4xl font-headline text-[#F7F5EB]">$<?php echo number_format($monthTotal, 0); ?><span class="text-xl opacity-40">.00</span></p>
            </div>
            <div class="bg-[#BD9A5F]/10 p-8 border border-[#BD9A5F]/20 relative group">
                <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.2em] mb-4">Pendientes Aprobación</p>
                <div class="flex items-center gap-4">
                    <p class="text-4xl font-headline text-[#F7F5EB]"><?php echo str_pad($pendingCount, 2, '0', STR_PAD_LEFT); ?></p>
                    <span class="px-3 py-1 bg-amber-500/20 text-amber-500 border border-amber-500/30 text-[9px] font-bold uppercase tracking-widest">Activas en Flujo</span>
                </div>
            </div>
            <div class="bg-[#BD9A5F]/10 p-8 border border-[#BD9A5F]/20 relative group">
                <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.2em] mb-4">Eficiencia Presupuesto</p>
                <div class="w-full bg-white/5 h-1.5 mt-2 rounded-full overflow-hidden">
                    <div class="bg-[#BD9A5F] h-full shadow-[0_0_10px_#BD9A5F]" style="width: 65%"></div>
                </div>
                <p class="text-right text-[10px] font-bold mt-3 text-primary opacity-60 italic">65% ejecucción mensual</p>
            </div>
        </div>

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
                            <th class="pb-6 px-4">Insumo / Servicio</th>
                            <th class="pb-6 px-4">Inversión</th>
                            <th class="pb-6 px-4 text-center">Estado</th>
                            <th class="pb-6 px-4 text-right">Gestión</th>
                        </tr>
                    </thead>
                    <tbody class="font-body">
                        <?php 
                        $resRequests = mysqli_query($conexion, "SELECT * FROM pur_requests ORDER BY id DESC LIMIT 10");
                        while($row = mysqli_fetch_assoc($resRequests)): 
                            $statusMap = [
                                'pending' => 'PENDIENTE',
                                'approved' => 'APROBADO',
                                'rejected' => 'RECHAZADO'
                            ];
                            $statusText = $statusMap[strtolower($row['status'])] ?? strtoupper($row['status']);
                            
                            $statusColor = 'text-amber-600';
                            if($row['status'] === 'approved') $statusColor = 'text-emerald-600';
                            if($row['status'] === 'rejected') $statusColor = 'text-red-600';
                        ?>
                        <tr class="hover:bg-[#021619]/5 transition-colors group">
                            <td class="py-4 px-4 border-b border-[#021619]/5 font-bold text-[#BD9A5F] text-xs">#PR-<?php echo $row['id']; ?></td>
                            <td class="py-4 px-4 border-b border-[#021619]/5 font-headline font-bold text-sm text-[#021619] opacity-70">
                                <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                            </td>
                            <td class="py-4 px-4 border-b border-[#021619]/5 font-headline font-bold text-sm text-[#021619]">
                                <?php echo htmlspecialchars($row['description']); ?>
                            </td>
                            <td class="py-4 px-4 border-b border-[#021619]/5 font-headline font-bold text-sm text-[#021619]">
                                $<?php echo number_format($row['total_amount'], 2); ?>
                            </td>
                            <td class="py-4 px-4 border-b border-[#021619]/5 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="font-headline font-black text-[10px] tracking-widest <?php echo $statusColor; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <span class="text-[8px] text-[#021619]/30 font-black italic">NIVEL <?php echo $row['current_level']; ?>/3</span>
                                        <?php if($row['status'] === 'pending'): ?>
                                            <span class="material-symbols-outlined text-[10px] text-green-600 notif-pulse" title="Notificaciones activas">chat</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 border-b border-[#021619]/5 text-right">
                                <div class="flex justify-end gap-2 transition-opacity">
                                    <button onclick="verSolicitud(<?php echo $row['id']; ?>)" class="w-8 h-8 rounded-full bg-[#BD9A5F]/10 text-[#BD9A5F] flex items-center justify-center hover:bg-[#BD9A5F] hover:text-white transition-all shadow-sm" title="Detalles">
                                        <span class="material-symbols-outlined text-xs">visibility</span>
                                    </button>
                                    <button onclick="editarSolicitud(<?php echo $row['id']; ?>)" class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="Editar">
                                        <span class="material-symbols-outlined text-xs">edit</span>
                                    </button>
                                    <button onclick="eliminarSolicitud(<?php echo $row['id']; ?>)" class="w-8 h-8 rounded-full bg-red-50 text-red-600 flex items-center justify-center hover:bg-red-600 hover:text-white transition-all shadow-sm" title="Eliminar">
                                        <span class="material-symbols-outlined text-xs">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>

                    </tbody>
                </table>
            </div>
            
            <div class="mt-12 pt-8 border-t border-[#021619]/5 flex justify-center">
                <p class="text-[10px] text-[#021619]/20 font-black uppercase tracking-[0.5em] italic">Ohlala Executive Procurement - Excellence in Every Ingredient</p>
            </div>
        </section>
    </main>

    <!-- Modal: Nueva Solicitud -->
    <div id="newRequestModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-[#021619]/90 backdrop-blur-sm p-4">
        <div class="bg-[#F7F5EB] w-full max-w-lg relative shadow-2xl overflow-hidden rounded-lg">
            <div class="absolute top-0 left-0 w-full h-2 bg-[#BD9A5F]"></div>
            
            <div class="p-8 md:p-12">
                <header class="mb-8">
                    <h2 class="text-3xl font-headline text-[#021619] mb-2">Nueva Solicitud</h2>
                    <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.2em]">Inicie un nuevo proceso de abastecimiento</p>
                </header>

                <form id="newRequestForm" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60 mb-2">Fecha de Registro</label>
                            <input type="text" id="dateInput" readonly 
                                   class="w-full bg-[#021619]/5 border-2 border-[#021619]/10 px-4 py-4 font-body text-sm text-[#021619] outline-none rounded-sm shadow-inner"/>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60 mb-2">Estado Actual</label>
                            <input type="text" id="statusInput" readonly 
                                   class="w-full bg-[#021619]/5 border-2 border-[#021619]/10 px-4 py-4 font-body text-sm text-[#021619] font-bold uppercase tracking-widest outline-none rounded-sm shadow-inner text-center"/>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60 mb-2">Descripción del Insumo / Servicio</label>
                        <input type="text" id="descInput" name="description" required placeholder="Ej: Harina de Trigo Integral Orgánica" 
                               class="w-full bg-[#021619]/5 border-2 border-[#021619]/10 px-4 py-4 font-body text-sm text-[#021619] placeholder:text-[#021619]/30 focus:border-[#BD9A5F] focus:bg-white transition-all outline-none rounded-sm shadow-inner"/>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60 mb-2">Inversión Estimada (MXN)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#021619]/60 font-bold">$</span>
                            <input type="number" id="amountInput" step="0.01" name="amount" required placeholder="0.00" 
                                   class="w-full bg-[#021619]/5 border-2 border-[#021619]/10 pl-10 pr-4 py-4 font-body text-sm text-[#021619] placeholder:text-[#021619]/30 focus:border-[#BD9A5F] focus:bg-white transition-all outline-none font-bold rounded-sm shadow-inner"/>
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

    <!-- Mobile Nav -->
    <nav class="fixed bottom-0 w-full z-50 flex justify-around items-center px-4 py-3 border-t border-white/5 md:hidden bg-[#021619]/95 backdrop-blur-xl">
        <a class="flex flex-col items-center text-primary/50 text-[#F7F5EB]/50" href="../../../dashboard.php"><span class="material-symbols-outlined">home</span><span class="text-[10px]">Inicio</span></a>
        <a class="flex flex-col items-center text-primary font-bold" href="#"><span class="material-symbols-outlined">shopping_cart</span><span class="text-[10px]">Compras</span></a>
        <a class="flex flex-col items-center text-white/50" href="../../../logout.php"><span class="material-symbols-outlined">logout</span><span class="text-[10px]">Salir</span></a>
    </nav>

    <script>
        function openModal(mode = 'create') {
            if (mode === 'create') {
                $('#newRequestForm')[0].reset();
                $('#requestId').val('');
                $('#dateInput').val(new Date().toISOString().split('T')[0]);
                $('#statusBadge').text('NUEVO').css({'background-color': 'rgba(2, 22, 25, 0.05)', 'color': 'rgba(2, 22, 25, 0.6)'});
            }
            
            $('#saveBtn').show().text('Enviar para Aprobación');
            $('#descInput, #amountInput').prop('readonly', false);
            
            if (mode === 'view') {
                $('#saveBtn').hide();
                $('#descInput, #amountInput').prop('readonly', true);
            } else if (mode === 'edit') {
                $('#saveBtn').text('Guardar Cambios');
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
                    $('#descInput').val(response.data.description);
                    $('#amountInput').val(response.data.total_amount);
                    $('#dateInput').val(new Date(response.data.created_at).toLocaleDateString('es-MX'));
                    
                    const statusMap = {
                        'pending': 'PENDIENTE',
                        'approved': 'APROBADO',
                        'rejected': 'RECHAZADO'
                    };
                    
                    const rawStatus = response.data.status.toLowerCase();
                    const statusText = statusMap[rawStatus] || rawStatus.toUpperCase();
                    
                    let textColor = '#BD9A5F';
                    if (rawStatus === 'approved') textColor = '#10b981';
                    if (rawStatus === 'rejected') textColor = '#ef4444';
                    
                    $('#statusInput').val(statusText).css({'color': textColor});
                    
                    openModal('view');
                } else {
                    alert('Error: ' + response.message);
                }
            });
        }

        function editarSolicitud(id) {
            $.get('../api/get_request.php', { id: id }, function(response) {
                if (response.status === 'success') {
                    $('#requestId').val(response.data.id);
                    $('#descInput').val(response.data.description);
                    $('#amountInput').val(response.data.total_amount);
                    $('#dateInput').val(new Date(response.data.created_at).toLocaleDateString('es-MX'));
                    
                    const statusMap = {
                        'pending': 'PENDIENTE',
                        'approved': 'APROBADO',
                        'rejected': 'RECHAZADO'
                    };
                    const statusText = statusMap[response.data.status.toLowerCase()] || 'PENDIENTE';
                    
                    $('#statusInput').val(statusText).css({'color': '#BD9A5F'});
                    
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
            const btn = $(this).find('button[type="submit"]');
            const id = $('#requestId').val();
            const url = id ? '../api/update_request.php' : '../api/create_request.php';
            
            btn.prop('disabled', true).text('Procesando...');

            $.ajax({
                url: url,
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.status === 'success') {
                        alert('¡Éxito! ' + response.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error crítico de comunicación.');
                },
                complete: function() {
                    btn.prop('disabled', false);
                }
            });
        });

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
                                <div class="p-4 border-b border-white/5 hover:bg-white/5 transition-all cursor-pointer group">
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

        // Close modal on escape
        $(document).keyup(function(e) {
            if (e.key === "Escape") closeModal();
        });
    </script>
</body>
</html>
