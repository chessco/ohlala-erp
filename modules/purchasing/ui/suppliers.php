<?php
/**
 * UI: Supplier Management (Directorio de Proveedores)
 * Location: modules/purchasing/ui/suppliers.php
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
    <title>Proveedores - Ohlala Executive</title>
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
                <a class="font-headline opacity-80 hover:opacity-100" href="../../../dashboard.php">Inicio</a>
                
                <!-- Dropdown Catálogos -->
                <div class="relative group">
                    <button class="font-headline border-b-2 border-[#021619] flex items-center gap-1 py-1">
                        Catálogos <span class="material-symbols-outlined text-sm">expand_more</span>
                    </button>
                    <div class="absolute left-0 mt-2 w-56 bg-[#021619] text-[#F7F5EB] shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50 rounded-sm overflow-hidden border border-[#BD9A5F]/20">
                        <a href="../../../clientes.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5 no-underline">
                            <span class="material-symbols-outlined text-[16px]">group</span> Clientes
                        </a>
                        <a href="../../../productos.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5 no-underline">
                            <span class="material-symbols-outlined text-[16px]">bakery_dining</span> Productos
                        </a>
                        <a href="suppliers.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-[#021619] transition-all border-b border-white/5 no-underline bg-[#BD9A5F]/10">
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

    <!-- Header Section -->
    <header class="p-6 md:p-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 border-b border-white/5 bg-[#021619]">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-primary-container rounded-sm flex items-center justify-center shadow-lg transform -rotate-3">
                <span class="material-symbols-outlined text-background text-2xl">partner_exchange</span>
            </div>
            <div>
                <h1 class="text-3xl md:text-4xl font-headline font-bold text-primary tracking-tight">Directorio de Proveedores</h1>
                <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.4em] opacity-80">Ohlala Executive Procurement (V3.1)</p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <!-- Dropdown Catálogos -->
            <div class="relative group">
                <button class="px-6 py-3 bg-[#BD9A5F]/10 border border-[#BD9A5F]/30 text-[#BD9A5F] text-[10px] font-black uppercase tracking-widest hover:bg-[#BD9A5F] hover:text-background transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">widgets</span> Catálogos <span class="material-symbols-outlined text-[10px]">expand_more</span>
                </button>
                <div class="absolute left-0 mt-2 w-56 bg-[#021619] border border-[#BD9A5F]/30 shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50 rounded-sm overflow-hidden">
                    <a href="../../../clientes.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-background transition-all border-b border-white/5">
                        <span class="material-symbols-outlined text-[16px]">group</span> Clientes
                    </a>
                    <a href="../../../productos.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-background transition-all border-b border-white/5">
                        <span class="material-symbols-outlined text-[16px]">bakery_dining</span> Productos
                    </a>
                    <a href="suppliers.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-background transition-all border-b border-white/5 bg-[#BD9A5F]/10">
                        <span class="material-symbols-outlined text-[16px]">handshake</span> Proveedores
                    </a>
                    <a href="items.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-background transition-all border-b border-white/5">
                        <span class="material-symbols-outlined text-[16px]">inventory_2</span> Insumos
                    </a>
                    <?php if($esAdmin): ?>
                        <a href="../../../usuarios.php" class="flex items-center gap-3 px-6 py-4 text-[#F7F5EB] text-[10px] uppercase font-black tracking-[0.2em] hover:bg-[#BD9A5F] hover:text-background transition-all">
                            <span class="material-symbols-outlined text-[16px]">person_outline</span> Usuarios
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <a href="list_requests.php" class="px-6 py-3 border-2 border-primary/20 text-primary text-xs font-black uppercase tracking-widest hover:bg-primary/5 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">assignment</span> Solicitudes
            </a>
            <button onclick="openSupplierModal()" class="bg-[#BD9A5F] text-background px-8 py-3 font-headline font-bold shadow-2xl hover:translate-y-[-2px] transition-all flex items-center gap-2 rounded-sm active:scale-95 group">
                <span class="material-symbols-outlined group-hover:rotate-90 transition-transform">add</span> NUEVO PROVEEDOR
            </button>
        </div>
    </header>

    <main class="max-w-[1400px] mx-auto p-6 md:p-8">
        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-[#BD9A5F]/10 p-8 border border-[#BD9A5F]/20 relative group overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-6xl">groups</span>
                </div>
                <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.2em] mb-4">Total Aliados</p>
                <?php 
                    $resCount = mysqli_query($conexion, "SELECT COUNT(*) as total FROM pur_suppliers");
                    $totalSupp = mysqli_fetch_assoc($resCount)['total'] ?? 0;
                ?>
                <p class="text-4xl font-headline text-[#F7F5EB]"><?php echo str_pad($totalSupp, 2, '0', STR_PAD_LEFT); ?></p>
            </div>
            <div class="bg-[#BD9A5F]/10 p-8 border border-[#BD9A5F]/20 relative group">
                <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.2em] mb-4">Ubicación Estratégica</p>
                <p class="text-sm font-bold text-[#F7F5EB]/60 italic">Nacional e Internacional</p>
                <div class="mt-4 flex gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[9px] font-bold text-emerald-500 uppercase tracking-widest">Servicio Activo</span>
                </div>
            </div>
            <div class="bg-[#BD9A5F]/10 p-8 border border-[#BD9A5F]/20 relative group">
                <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.2em] mb-4">Evaluación Promedio</p>
                <div class="flex items-center gap-1 text-amber-400">
                    <span class="material-symbols-outlined text-sm">star</span>
                    <span class="material-symbols-outlined text-sm">star</span>
                    <span class="material-symbols-outlined text-sm">star</span>
                    <span class="material-symbols-outlined text-sm">star</span>
                    <span class="material-symbols-outlined text-sm">star_half</span>
                    <span class="ml-2 text-xl font-headline text-white">4.8</span>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <section class="bg-[#F7F5EB] text-[#021619] p-10 shadow-2xl relative rounded-t-xl overflow-hidden">
            <div class="absolute bottom-[-10px] left-0 w-full h-4 bg-[#F7F5EB] torn-edge transform z-10"></div>
            
            <div class="flex justify-between items-center mb-10">
                <h2 class="font-headline text-3xl">Socios Comerciales</h2>
                <div class="relative">
                    <input type="text" id="supplierSearch" placeholder="Buscar por nombre o RFC..." class="bg-[#021619]/5 border-b-2 border-[#021619]/10 px-10 py-2 outline-none focus:border-[#BD9A5F] transition-all text-sm font-body italic"/>
                    <span class="material-symbols-outlined absolute left-2 top-2 text-[#021619]/40 text-xl">search</span>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b-2 border-[#021619]/10 text-[10px] uppercase tracking-[0.2em] font-black text-[#021619]/40">
                            <th class="pb-6 px-4">Proveedor</th>
                            <th class="pb-6 px-4">RFC / Tributario</th>
                            <th class="pb-6 px-4">Contacto</th>
                            <th class="pb-6 px-4">Email</th>
                            <th class="pb-6 px-4 text-center">Gestión</th>
                        </tr>
                    </thead>
                    <tbody class="font-body">
                        <?php 
                        $resSuppliers = mysqli_query($conexion, "SELECT * FROM pur_suppliers ORDER BY name ASC");
                        if (mysqli_num_rows($resSuppliers) == 0):
                        ?>
                        <tr>
                            <td colspan="5" class="py-20 text-center text-[#021619]/30 italic">No se han registrado proveedores aún.</td>
                        </tr>
                        <?php endif; ?>
                        <?php while($row = mysqli_fetch_assoc($resSuppliers)): ?>
                        <tr class="hover:bg-[#021619]/5 transition-colors group">
                            <td class="py-6 px-4 border-b border-[#021619]/5 font-headline font-bold text-[#021619]"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="py-6 px-4 border-b border-[#021619]/5 font-body font-black text-[11px] text-[#021619]/60 tracking-wider"><?php echo htmlspecialchars($row['rfc'] ?: 'N/A'); ?></td>
                            <td class="py-6 px-4 border-b border-[#021619]/5">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold"><?php echo htmlspecialchars($row['contact_person'] ?: 'Sin contacto'); ?></span>
                                    <span class="text-[10px] opacity-40"><?php echo htmlspecialchars($row['phone'] ?: '-'); ?></span>
                                </div>
                            </td>
                            <td class="py-6 px-4 border-b border-[#021619]/5 text-sm underline decoration-[#BD9A5F]/30"><?php echo htmlspecialchars($row['email'] ?: '-'); ?></td>
                            <td class="py-6 px-4 border-b border-[#021619]/5 text-center">
                                <div class="flex justify-center gap-2">
                                    <button onclick="editSupplier(<?php echo $row['id']; ?>)" class="w-8 h-8 rounded-full bg-[#BD9A5F]/10 text-[#BD9A5F] flex items-center justify-center hover:bg-[#BD9A5F] hover:text-white transition-all shadow-sm" title="Editar">
                                        <span class="material-symbols-outlined text-xs">edit</span>
                                    </button>
                                    <button onclick="deleteSupplier(<?php echo $row['id']; ?>)" class="w-8 h-8 rounded-full bg-red-50 text-red-600 flex items-center justify-center hover:bg-red-600 hover:text-white transition-all shadow-sm" title="Eliminar">
                                        <span class="material-symbols-outlined text-xs">delete</span>
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

    <!-- Modal: Nuevo/Editar Proveedor -->
    <div id="supplierModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-[#021619]/90 backdrop-blur-sm p-4">
        <div class="bg-[#F7F5EB] w-full max-w-xl relative shadow-2xl overflow-hidden rounded-lg flex flex-col max-h-[90vh]">
            <div class="absolute top-0 left-0 w-full h-2 bg-[#BD9A5F] z-10"></div>
            <button onclick="closeSupplierModal()" class="absolute top-4 right-4 text-[#021619]/40 hover:text-[#021619] transition-all z-20">
                <span class="material-symbols-outlined">close</span>
            </button>
            
            <form id="supplierForm" class="p-10 md:p-14 overflow-y-auto custom-scrollbar">
                <header class="mb-10 text-center">
                    <h2 class="text-3xl font-headline text-[#021619] mb-2" id="modalTitle">Nuevo Proveedor</h2>
                    <p class="text-[10px] font-black text-[#BD9A5F] uppercase tracking-[0.2em]">Registrar aliado comercial en la cadena de valor</p>
                </header>

                <input type="hidden" id="suppId" name="id">
                
                <div class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60 mb-2">Nombre Comercial / Empresa</label>
                        <input type="text" name="name" required placeholder="Ej: Harinas de Occidente S.A." 
                               class="w-full bg-[#021619]/5 border-2 border-[#021619]/10 px-4 py-4 font-body text-sm text-[#021619] outline-none focus:border-[#BD9A5F] transition-all rounded-sm"/>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60 mb-4">RFC / Tax ID</label>
                            <input type="text" name="rfc" class="w-full bg-[#021619]/5 border-2 border-[#021619]/10 px-4 py-4 font-body text-sm text-[#021619] outline-none rounded-sm"/>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60 mb-4">Persona de Contacto</label>
                            <input type="text" name="contact_person" class="w-full bg-[#021619]/5 border-2 border-[#021619]/10 px-4 py-4 font-body text-sm text-[#021619] outline-none rounded-sm"/>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60 mb-4">Correo Corporativo</label>
                            <input type="email" name="email" class="w-full bg-[#021619]/5 border-2 border-[#021619]/10 px-4 py-4 font-body text-sm text-[#021619] outline-none rounded-sm"/>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60 mb-4">Teléfono</label>
                            <input type="text" name="phone" class="w-full bg-[#021619]/5 border-2 border-[#021619]/10 px-4 py-4 font-body text-sm text-[#021619] outline-none rounded-sm"/>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-widest text-[#021619]/60 mb-4">Dirección Fiscal / Oficina</label>
                        <textarea name="address" rows="3" class="w-full bg-[#021619]/5 border-2 border-[#021619]/10 px-4 py-4 font-body text-sm text-[#021619] outline-none rounded-sm resize-none"></textarea>
                    </div>
                </div>

                <div class="mt-12 flex gap-4">
                    <button type="submit" class="flex-1 bg-[#021619] text-[#F7F5EB] py-5 font-headline font-bold text-lg hover:bg-black transition-all shadow-xl active:scale-95">
                        Guardar Socio Comercial
                    </button>
                    <button type="button" onclick="closeSupplierModal()" class="px-8 py-5 font-headline font-bold text-[#021619]/40 hover:text-[#021619] transition-all">
                        Cerrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openSupplierModal(mode = 'create') {
            $('#supplierForm')[0].reset();
            $('#suppId').val('');
            $('#modalTitle').text(mode === 'create' ? 'Nuevo Proveedor' : 'Editar Proveedor');
            $('#supplierModal').removeClass('hidden').addClass('flex');
            $('body').addClass('overflow-hidden');
        }

        function closeSupplierModal() {
            $('#supplierModal').addClass('hidden').removeClass('flex');
            $('body').removeClass('overflow-hidden');
        }

        $('#supplierForm').on('submit', function(e) {
            e.preventDefault();
            const data = $(this).serialize();
            $.post('../api/save_supplier.php', data, function(response) {
                if (response.status === 'success') {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }, 'json');
        });

        function editSupplier(id) {
            $.get('../api/get_supplier.php', { id: id }, function(response) {
                if (response.status === 'success') {
                    openSupplierModal('edit');
                    $('#suppId').val(response.data.id);
                    $('input[name="name"]').val(response.data.name);
                    $('input[name="rfc"]').val(response.data.rfc);
                    $('input[name="contact_person"]').val(response.data.contact_person);
                    $('input[name="email"]').val(response.data.email);
                    $('input[name="phone"]').val(response.data.phone);
                    $('textarea[name="address"]').val(response.data.address);
                } else {
                    alert('Error al obtener datos');
                }
            }, 'json');
        }

        function deleteSupplier(id) {
            if (confirm('¿Está seguro de eliminar este proveedor?')) {
                $.post('../api/delete_supplier.php', { id: id }, function(response) {
                    if (response.status === 'success') {
                        location.reload();
                    } else {
                        alert('Error al eliminar');
                    }
                }, 'json');
            }
        }

        // Search logic
        $('#supplierSearch').on('keyup', function() {
            const val = $(this).val().toLowerCase();
            $('tbody tr').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(val) > -1);
            });
        });
    </script>
</body>
</html>
