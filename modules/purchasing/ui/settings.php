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
    <header class="bg-[#BD9A5F] py-4 px-8 flex justify-between items-center shadow-2xl">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-[#021619] flex items-center justify-center">
                <span class="material-symbols-outlined text-[#BD9A5F]">settings</span>
            </div>
            <div>
                <h1 class="font-headline text-2xl text-[#021619] font-black tracking-tighter">Configuraciones</h1>
                <p class="text-[9px] uppercase tracking-[0.3em] font-black text-[#021619]/60">Gestión de Infraestructura y Flujos</p>
            </div>
        </div>
        <nav class="flex gap-6">
            <a class="font-headline text-[#021619]/80 hover:text-[#021619]" href="list_requests.php">Volver a Compras</a>
            <a class="font-headline text-[#021619]/80 hover:text-[#021619]" href="../../../usuarios.php">Usuarios</a>
            <a class="font-headline text-[#021619]/80 hover:text-[#021619]" href="../../../dashboard.php">Inicio</a>
        </nav>
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

            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-[#BD9A5F] text-[#021619] px-12 py-4 font-headline font-black uppercase tracking-widest hover:bg-[#E7C182] transition-all shadow-xl">
                    Guardar Cambios
                </button>
            </div>

        </form>
    </main>

    <script>
        $('#settingsForm').on('submit', function(e) {
            e.preventDefault();
            const data = $(this).serialize();
            
            $.post('../api/save_settings.php', data, function(res) {
                if(res.status === 'success') {
                    alert('Configuraciones actualizadas con éxito.');
                } else {
                    alert('Error: ' + res.message);
                }
            });
        });
    </script>
</body>
</html>
