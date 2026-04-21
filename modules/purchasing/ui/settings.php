<?php
/**
 * VIEW: Module Settings
 * Location: modules/purchasing/ui/settings.php
 */
include('../../../conexion.php');
session_start();

if (!isset($_SESSION['autenticado']) || $_SESSION['usuario_rol'] !== 'admin') {
    header("Location: ../../../index.php");
    exit();
}

// Autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'Purchasing\\') === 0) {
        $file = __DIR__ . '/../src/' . str_replace(['Purchasing\\', '\\'], ['', '/'], $class) . '.php';
        if (file_exists($file)) require $file;
    }
});

use Purchasing\Infrastructure\SettingsRepository;
$settingsRepo = new SettingsRepository($conexion);
$settings = $settingsRepo->getAll();

// Fetch Users for Dropdowns
$resUsers = mysqli_query($conexion, "SELECT id, nombre_completo FROM usuarios ORDER BY nombre_completo ASC");
$users = [];
while($u = mysqli_fetch_assoc($resUsers)) $users[] = $u;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { "primary": "#E7C182", "background": "#021619" },
                    fontFamily: { headline: ["Playfair Display", "serif"], body: ["Manrope", "sans-serif"] }
                }
            }
        }
    </script>
    <style>
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .input-dark { background: rgba(2, 22, 25, 0.5); border: 1px solid rgba(189, 154, 95, 0.2); color: white; border-radius: 2px; }
        .input-dark:focus { border-color: #BD9A5F; outline: none; }
    </style>
</head>
<body class="bg-[#021619] font-body text-[#F7F5EB] min-h-screen">

    <!-- Header -->
    <header class="flex justify-between items-center w-full px-6 py-4 z-50 bg-[#BD9A5F] top-0 shadow-lg shadow-[#021619]/20 sticky">
        <div class="flex items-center gap-4">
            <h1 class="text-2xl font-headline font-bold text-[#021619] tracking-tight">Ohlala! Bistro (V3.1)</h1>
        </div>
        <div class="hidden md:flex gap-8 items-center">
            <nav class="flex gap-6 items-center text-[#021619]">
                <a class="font-headline opacity-80 hover:opacity-100" href="../../../dashboard.php">Inicio</a>
                
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

                <a class="font-headline opacity-80 hover:opacity-100" href="list_requests.php">Compras</a>
                <a class="font-headline border-b-2 border-[#021619] pb-1" href="settings.php">Configuración</a>

                <!-- Notification Bell -->
                <div class="relative ml-4">
                    <button id="notifBell" class="flex items-center justify-center p-2 rounded-full hover:bg-black/10 transition-all relative">
                        <span class="material-symbols-outlined text-[#021619] text-2xl">notifications</span>
                        <span id="notifBadge" class="absolute top-1 right-1 w-2.5 h-2.5 bg-red-600 rounded-full border-2 border-[#BD9A5F] hidden"></span>
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

    <main class="p-8 max-w-5xl mx-auto">
        <form id="settingsForm" class="space-y-8">
            
            <!-- SMTP Section -->
            <section class="glass p-8 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-5">
                    <span class="material-symbols-outlined text-9xl text-white font-thin">alternate_email</span>
                </div>
                <h2 class="font-headline text-xl text-[#BD9A5F] mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined">mail</span> 
                    Configuración de Correo (SMTP)
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-10">
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-white/40 uppercase tracking-widest">Servidor SMTP</label>
                        <input type="text" name="smtp_host" value="<?php echo $settings['smtp_host'] ?? ''; ?>" class="input-dark p-3 text-sm" placeholder="smtp.gmail.com">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-white/40 uppercase tracking-widest">Puerto</label>
                        <input type="text" name="smtp_port" value="<?php echo $settings['smtp_port'] ?? ''; ?>" class="input-dark p-3 text-sm" placeholder="587">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-white/40 uppercase tracking-widest">Usuario (Gmail)</label>
                        <input type="text" name="smtp_user" value="<?php echo $settings['smtp_user'] ?? ''; ?>" class="input-dark p-3 text-sm">
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-white/40 uppercase tracking-widest">Contraseña de Aplicación</label>
                        <input type="password" name="smtp_pass" value="<?php echo $settings['smtp_pass'] ?? ''; ?>" class="input-dark p-3 text-sm">
                    </div>
                </div>
                
                <div class="mt-8 pt-6 border-t border-white/5 flex items-center justify-between">
                    <p class="text-[10px] text-white/30 italic">Nota: Se recomienda usar 'Contraseñas de Aplicación' de Google si usa Gmail.</p>
                    <button type="button" onclick="testSmtp()" class="flex items-center gap-2 bg-[#021619] border border-[#BD9A5F]/40 text-[#BD9A5F] px-6 py-2 text-xs font-bold uppercase tracking-widest hover:bg-[#BD9A5F] hover:text-[#021619] transition-all">
                        <span class="material-symbols-outlined text-sm">precision_manufacturing</span>
                        Probar Conexión SMTP
                    </button>
                </div>
            </section>

            <!-- Approval Section -->
            <section class="glass p-8 relative overflow-hidden">
                 <div class="absolute top-0 right-0 p-4 opacity-5">
                    <span class="material-symbols-outlined text-9xl text-white font-thin">account_tree</span>
                </div>
                <h2 class="font-headline text-xl text-[#BD9A5F] mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined">verified_user</span> 
                    Cadena de Aprobación
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative z-10">
                    <?php for($i=1; $i<=3; $i++): ?>
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-black text-white/40 uppercase tracking-widest">Aprobador Nivel <?php echo $i; ?></label>
                        <select name="approver_level_<?php echo $i; ?>" class="input-dark p-3 text-sm">
                            <?php foreach($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo (($settings['approver_level_'.$i] ?? '') == $user['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['nombre_completo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endfor; ?>
                </div>
            </section>

            <!-- Visual Architecture Section -->
            <section class="glass p-8 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-5">
                    <span class="material-symbols-outlined text-9xl text-white font-thin">visibility</span>
                </div>
                <h2 class="font-headline text-xl text-[#BD9A5F] mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined">dashboard_customize</span> 
                    Personalización Visual del Dashboard
                </h2>
                
                <div class="flex items-center justify-between p-4 bg-white/5 border border-white/10 rounded-sm">
                    <div>
                        <p class="text-sm font-bold text-white mb-1">Mostrar Cabecera del Dashboard</p>
                        <p class="text-[10px] text-white/40 italic uppercase tracking-wider">Incluye la imagen decorativa y las tarjetas de estadísticas principales.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="dashboard_show_hero" value="0">
                        <input type="checkbox" name="dashboard_show_hero" value="1" <?php echo ($settings['dashboard_show_hero'] ?? '1') == '1' ? 'checked' : ''; ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-[#021619] border border-white/20 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[#BD9A5F] rounded-full peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#BD9A5F]"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-white/5 border border-white/10 rounded-sm mt-4">
                    <div>
                        <p class="text-sm font-bold text-white mb-1">Auto-ocultar cabecera tras actividad (10s)</p>
                        <p class="text-[10px] text-white/40 italic uppercase tracking-wider">Oculta la imagen automáticamente tras 10s de movimiento del mouse y la muestra al reposar.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="dashboard_autohide_hero_on_move" value="0">
                        <input type="checkbox" name="dashboard_autohide_hero_on_move" value="1" <?php echo ($settings['dashboard_autohide_hero_on_move'] ?? '0') == '1' ? 'checked' : ''; ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-[#021619] border border-white/20 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[#BD9A5F] rounded-full peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#BD9A5F]"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-white/5 border border-white/10 rounded-sm mt-4">
                    <div>
                        <p class="text-sm font-bold text-white mb-1">Frecuencia de Sincronización (Dashboard)</p>
                        <p class="text-[10px] text-white/40 italic uppercase tracking-wider">Intervalo en segundos para la actualización automática de datos.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="number" name="dashboard_refresh_interval" 
                               value="<?php echo $settings['dashboard_refresh_interval'] ?? '60'; ?>" 
                               class="input-dark w-20 p-2 text-center text-sm font-bold" min="10" max="3600">
                        <span class="text-[10px] font-black text-[#BD9A5F] uppercase">SEG</span>
                    </div>
                </div>
            </section>

            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-[#BD9A5F] text-[#021619] px-12 py-4 font-headline font-black uppercase tracking-widest hover:bg-[#E7C182] transition-all shadow-xl">
                    Guardar Cambios
                </button>
            </div>

        </form>
    </main>

    <!-- SMTP TEST MODAL -->
    <div id="smtpModal" class="fixed inset-0 z-[200] hidden items-center justify-center bg-black/90 backdrop-blur-sm p-4">
        <div class="glass w-full max-w-2xl overflow-hidden shadow-2xl animate-in fade-in zoom-in duration-300">
            <header class="bg-[#BD9A5F] px-6 py-3 flex justify-between items-center text-[#021619]">
                <h3 class="font-headline font-bold uppercase tracking-widest text-sm">Terminal de Diagnóstico SMTP</h3>
                <button onclick="closeSmtpModal()" class="hover:rotate-90 transition-all duration-300">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </header>
            <div id="smtpConsole" class="p-6 bg-[#010b0c] font-mono text-xs text-emerald-400 min-h-[300px] max-h-[500px] overflow-y-auto whitespace-pre-wrap leading-relaxed">
                <p class="animate-pulse">Iniciando diagnóstico...</p>
            </div>
            <footer class="p-4 bg-black/50 border-t border-white/5 flex justify-end">
                <button onclick="closeSmtpModal()" class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#BD9A5F] hover:text-white transition-colors">Cerrar Consola</button>
            </footer>
        </div>
    </div>

    <script>
        $('#settingsForm').on('submit', function(e) {
            e.preventDefault();
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).text('GUARDANDO...');
            const data = $(this).serialize();
            
            $.post('../api/save_settings.php', data, function(res) {
                if(res.status === 'success') {
                    alert('Configuraciones actualizadas con éxito.');
                } else {
                    alert('Error: ' + res.message);
                }
            }, 'json').fail(function() {
                alert('Error de conexión.');
            }).always(function() {
                btn.prop('disabled', false).text('GUARDAR CAMBIOS');
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

        $('#notifBell').on('click', function(e) { e.stopPropagation(); $('#notifPanel').toggleClass('hidden'); });
        $(document).on('click', function() { $('#notifPanel').addClass('hidden'); });
        $('#notifPanel').on('click', function(e) { e.stopPropagation(); });

        fetchNotifications();
        setInterval(fetchNotifications, 30000);

        function testSmtp() {
            $('#smtpConsole').html('<p class="animate-pulse">Estableciendo conexión con el servidor...</p>');
            $('#smtpModal').removeClass('hidden').addClass('flex');
            $('body').addClass('overflow-hidden');

            $.get('../api/smtp_test.php?run=1&ajax=1', function(text) {
                $('#smtpConsole').text(text);
                
                // Colorize Error
                if (text.includes('[ERROR]')) {
                    $('#smtpConsole').addClass('text-red-400').removeClass('text-emerald-400');
                } else {
                    $('#smtpConsole').addClass('text-emerald-400').removeClass('text-red-400');
                }
            }).fail(function() {
                $('#smtpConsole').html('<p class="text-red-500">[ERROR CRITICO] No se pudo contactar con el script de prueba.</p>');
            });
        }

        function closeSmtpModal() {
            $('#smtpModal').addClass('hidden').removeClass('flex');
            $('body').removeClass('overflow-hidden');
        }
    </script>
</body>
</html>
