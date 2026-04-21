<?php
/**
 * UI: Professional Purchase Order Document (Ficha de la OC)
 * Location: modules/purchasing/ui/view_order.php
 */
include('../../../conexion.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['autenticado'])) {
    header("Location: ../../../index.php");
    exit();
}

$id = (int)($_GET['id'] ?? 0);

// Get Order and Supplier Data
$sql = "SELECT o.*, s.name as supplier_name, s.rfc, s.address as supplier_address, s.email as supplier_email, s.phone as supplier_phone
        FROM pur_orders o 
        LEFT JOIN pur_suppliers s ON o.supplier_id = s.id 
        WHERE o.id = $id";
$res = mysqli_query($conexion, $sql);
$order = mysqli_fetch_assoc($res);

if (!$order) {
    die("Orden no encontrada.");
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>OC <?php echo $order['folio']; ?> - Ohlala Executive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Manrope:wght@300;400;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background-color: white !important; color: black !important; }
            .print-border { border: 1px solid #eee !important; }
            .shadow-custom { shadow: none !important; }
        }
        body { font-family: 'Manrope', sans-serif; background-color: #F7F5EB; color: #021619; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .font-cinzel { font-family: 'Cinzel', serif; }
    </style>
</head>
<body class="p-4 md:p-10 flex flex-col items-center">

    <!-- Actions bar (No Print) -->
    <div class="w-full max-w-4xl no-print flex justify-between items-center mb-8 bg-white/50 backdrop-blur-sm p-4 border border-black/5 rounded-sm shadow-sm">
        <a href="list_orders.php" class="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-[#021619]/60 hover:text-[#021619] transition-all">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Volver al Listado
        </a>
        <div class="flex items-center gap-6">
            <div class="hidden sm:flex items-center gap-3 px-4 py-1 border-r border-[#021619]/10">
                <span class="material-symbols-outlined text-[#021619]/40 text-lg font-variation-settings-fill-1">account_circle</span>
                <div class="flex flex-col text-left">
                    <span class="text-[9px] font-black text-[#021619] leading-none uppercase tracking-tighter"><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></span>
                    <span class="text-[7px] font-bold text-[#021619]/40 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($_SESSION['usuario_rol']); ?></span>
                </div>
            </div>
            <button onclick="window.print()" class="bg-[#021619] text-white px-8 py-3 font-bold text-sm tracking-widest hover:bg-black transition-all flex items-center gap-2 shadow-xl">
                <span class="material-symbols-outlined text-sm">print</span> IMPRIMIR DOCUMENTO
            </button>
        </div>
    </div>

    <!-- Document Wrapper -->
    <div class="w-full max-w-4xl bg-white p-12 md:p-20 shadow-2xl relative border border-black/5">
        <!-- Gold Accent Strip -->
        <div class="absolute top-0 left-0 w-full h-1.5 bg-[#BD9A5F]"></div>

        <!-- Header -->
        <header class="flex flex-col md:flex-row justify-between items-start gap-10 mb-16">
            <div class="flex-1">
                <h1 class="font-cinzel text-3xl font-bold tracking-widest text-[#021619] mb-1">OHLALA ARTISANAL</h1>
                <p class="text-[10px] font-black uppercase tracking-[0.4em] text-[#BD9A5F] mb-6">Executive Procurement System</p>
                <div class="text-[10px] space-y-1 opacity-60 leading-relaxed font-bold">
                    <p>CALLE PRINCIPAL #123, COLONIA CENTRO</p>
                    <p>CIUDAD DE MÉXICO, CP 01000</p>
                    <p>RFC: OHL220419RT8</p>
                    <p>TEL: (55) 1234-5678</p>
                </div>
            </div>
            <div class="text-right flex flex-col items-end">
                <div class="bg-[#021619] text-white px-6 py-4 mb-4">
                    <h2 class="text-[10px] font-black uppercase tracking-[0.3em] opacity-60 mb-1">Orden de Compra</h2>
                    <p class="font-serif text-2xl font-bold italic"><?php echo $order['folio']; ?></p>
                </div>
                <div class="text-[10px] font-black uppercase tracking-widest space-y-2">
                    <p><span class="opacity-40">Emisión:</span> <?php echo date('d/m/Y', strtotime($order['created_at'])); ?></p>
                    <p><span class="opacity-40">Status:</span> <span class="text-emerald-700 uppercase"><?php echo $order['status']; ?></span></p>
                </div>
            </div>
        </header>

        <!-- Divider -->
        <div class="w-full h-px bg-[#021619]/10 mb-12"></div>

        <!-- Stakeholders -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-16 mb-16">
            <div>
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-[#BD9A5F] mb-6 border-b border-[#BD9A5F]/20 pb-2">Proveedor</h3>
                <p class="font-serif text-xl font-bold mb-2"><?php echo htmlspecialchars($order['supplier_name']); ?></p>
                <div class="text-xs space-y-2 opacity-70">
                    <p><span class="font-bold uppercase tracking-tighter mr-2">RFC:</span> <?php echo htmlspecialchars($order['rfc'] ?: 'N/A'); ?></p>
                    <p><span class="font-bold uppercase tracking-tighter mr-2">Dirección:</span> <?php echo nl2br(htmlspecialchars($order['supplier_address'])); ?></p>
                    <p><span class="font-bold uppercase tracking-tighter mr-2">Email:</span> <?php echo htmlspecialchars($order['supplier_email']); ?></p>
                    <p><span class="font-bold uppercase tracking-tighter mr-2">Tel:</span> <?php echo htmlspecialchars($order['supplier_phone']); ?></p>
                </div>
            </div>
            <div>
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-[#BD9A5F] mb-6 border-b border-[#BD9A5F]/20 pb-2">Destino / Entrega</h3>
                <p class="font-bold text-sm mb-4">Ohlala Central Kitchen</p>
                <div class="text-xs space-y-4">
                    <div>
                        <p class="font-black text-[9px] uppercase tracking-widest opacity-40 mb-1">Instrucciones / Notas:</p>
                        <p class="italic"><?php echo nl2br(htmlspecialchars($order['notes'] ?: 'Ninguna nota especial.')); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="mb-16">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[#021619] text-white text-[10px] font-black uppercase tracking-[3px]">
                        <th class="py-4 px-6 border-r border-white/10">Descripción del Insumo / Servicio</th>
                        <th class="py-4 px-6 border-r border-white/10 w-24 text-center">Cant.</th>
                        <th class="py-4 px-6 border-r border-white/10 w-32 text-right">P. Unit</th>
                        <th class="py-4 px-6 w-36 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <?php 
                    $resItems = mysqli_query($conexion, "SELECT * FROM pur_order_items WHERE order_id = $id");
                    $subtotalFinal = 0;
                    while($it = mysqli_fetch_assoc($resItems)):
                        $subtotalFinal += $it['subtotal'];
                    ?>
                    <tr class="border-b border-black/5 hover:bg-gray-50/50 transition-colors">
                        <td class="py-6 px-6 font-bold text-[#021619] italic"><?php echo htmlspecialchars($it['description']); ?></td>
                        <td class="py-6 px-6 text-center font-bold opacity-60"><?php echo number_format($it['quantity'], 2); ?></td>
                        <td class="py-6 px-6 text-right font-body opacity-60">$<?php echo number_format($it['unit_price'], 2); ?></td>
                        <td class="py-6 px-6 text-right font-headline font-bold">$<?php echo number_format($it['subtotal'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"></td>
                        <td class="py-10 px-6 text-right font-black text-[10px] uppercase tracking-widest opacity-40">Total Neto Orden</td>
                        <td class="py-10 px-6 text-right font-serif text-3xl font-bold decoration-[#BD9A5F] decoration-4 underline-offset-8 underline">$<?php echo number_format($order['total_amount'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Footer / Signatures -->
        <footer class="mt-20 pt-20 border-t border-black/5 grid grid-cols-1 md:grid-cols-2 gap-20">
            <div class="text-center">
                <div class="w-48 h-px bg-black mx-auto mb-4"></div>
                <p class="text-[9px] font-black uppercase tracking-widest">Autorizado por</p>
                <p class="font-serif italic font-bold">Dirección General</p>
            </div>
            <div class="text-center">
                <div class="w-48 h-px bg-black mx-auto mb-4"></div>
                <p class="text-[9px] font-black uppercase tracking-widest">Recibido por (Proveedor)</p>
                <p class="font-serif italic opacity-30 italic">Firma y Sello</p>
            </div>
        </footer>
        
        <div class="mt-24 text-center">
            <p class="text-[10px] text-[#BD9A5F] font-black uppercase tracking-[0.5em] italic">Excellence in Every Ingredient - Since 2022</p>
        </div>
    </div>
</body>
</html>
