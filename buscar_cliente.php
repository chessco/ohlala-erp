<?php
include('conexion.php');
session_start();

if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header("Location: index.php");
    exit();
}

$nombre_vendedor = $_SESSION['usuario_nombre'];
$rol_actual      = $_SESSION['usuario_rol'];
$esAdmin         = ($rol_actual === 'admin');

// Estadísticas
$hoy = date('Y-m-d');
$res_p = mysqli_query($conexion, "SELECT COUNT(*) as total FROM pedidos WHERE DATE(fecha) = '$hoy'");
$total_hoy = ($res_p) ? mysqli_fetch_assoc($res_p)['total'] : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ohlala - Panel Principal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --bg-deep: #050a14; --card-blue: #0f172a; --accent: #2563eb; --text-silver: #94a3b8; }
        body { background-color: var(--bg-deep); color: white; font-family: 'Poppins', sans-serif; }
        .navbar-custom { background-color: var(--card-blue); border-bottom: 1px solid rgba(255,255,255,0.05); padding: 1rem 2rem; }
        .stat-card { background: var(--card-blue); border-radius: 15px; padding: 1.2rem; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s; }
        .action-card:hover { transform: translateY(-5px); border-color: var(--accent); cursor: pointer; }
        .card-main { background: var(--card-blue); border-radius: 20px; padding: 25px; border: none; }
        .table { color: white !important; }
        .ui-autocomplete { background: #1e293b !important; border: 1px solid #334155 !important; color: white !important; z-index: 3000 !important; }
        .modal-content { background-color: var(--card-blue); color: white; border: 1px solid rgba(255,255,255,0.1); }
        .form-control { background-color: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; }
    </style>
</head>
<body>

<nav class="navbar-custom d-flex justify-content-between align-items-center mb-4">
    <h4 class="m-0 text-white fw-bold">Ohlala <span class="text-primary">Pedidos</span></h4>
    <div class="d-flex align-items-center">
        <span class="text-silver me-3 small">Hola, <b><?php echo $nombre_vendedor; ?></b></span>
        <a href="logout.php" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-power-off"></i></a>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="stat-card">
                <div class="text-silver small">Hoy</div>
                <h3 class="m-0 fw-bold text-primary"><?php echo $total_hoy; ?></h3>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <a href="clientes.php" class="text-decoration-none text-white">
                <div class="stat-card action-card">
                    <div class="text-silver small">Directorio</div>
                    <h5 class="m-0"><i class="fa-solid fa-address-book text-success me-2"></i>Clientes</h5>
                </div>
            </a>
        </div>
        <?php if($esAdmin): ?>
        <div class="col-6 col-md-2">
            <a href="usuarios.php" class="text-decoration-none text-white">
                <div class="stat-card action-card">
                    <div class="text-silver small">Ajustes</div>
                    <h5 class="m-0"><i class="fa-solid fa-users-gear text-warning me-2"></i>Usuarios</h5>
                </div>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <div class="card-main shadow-lg">
        <div class="row mb-4">
            <div class="col-md-6"><h5>Historial de Pedidos</h5></div>
            <div class="col-md-6 text-end">
                <button class="btn btn-outline-primary me-2" onclick="duplicarPedido()">Duplicar Último</button>
                <button class="btn btn-primary" onclick="prepararNuevoPedido()">+ Nuevo Pedido</button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tablaPedidos" class="table table-dark table-hover w-100">
                <thead>
                    <tr><th>Folio</th><th>Fecha</th><th>Cliente</th><th>Estado</th><th class="text-center">Acciones</th></tr>
                </thead>
                <tbody>
                    <?php
                    $res = mysqli_query($conexion, "SELECT * FROM pedidos ORDER BY id DESC");
                    while($row = mysqli_fetch_assoc($res)):
                        $badge = ($row['estado'] == 'Cancelado') ? 'bg-danger' : 'bg-warning text-dark';
                    ?>
                    <tr>
                        <td class="text-primary fw-bold"><?php echo $row['folio']; ?></td>
                        <td><?php echo date('d/m/y', strtotime($row['fecha'])); ?></td>
                        <td><?php echo htmlspecialchars($row['cliente_nombre']); ?></td>
                        <td><span class="badge <?php echo $badge; ?>"><?php echo $row['estado']; ?></span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-info" onclick="editarPedido(<?php echo $row['id']; ?>, '<?php echo $row['estado']; ?>')">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitlePedido">Nuevo Pedido</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formPedido">
                    <input type="hidden" name="id_cliente" id="id_cliente_hidden">
                    <div class="mb-3">
                        <label class="small text-silver">Nombre del Cliente (Escribe para buscar)</label>
                        <input type="text" id="buscador_clientes" class="form-control" placeholder="Ej: Juan..." required>
                    </div>
                    <div class="mb-3">
                        <label class="small text-silver">Estado del Pedido</label>
                        <select name="estado" id="estado_pedido" class="form-select bg-dark text-white border-secondary">
                            <option value="Pendiente">Pendiente</option>
                            <option value="Pagado">Pagado</option>
                            <option value="Cancelado">Cancelado</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary w-100" onclick="guardarNuevoPedido()">Crear Pedido</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // 1. DataTables
    $('#tablaPedidos').DataTable({
        "language": {"url": "es-ES.json"},
        "order": [[0, "desc"]]
    });

    // 2. Autocomplete (Con corrección de Z-Index para que se vea sobre el modal)
    $("#buscador_clientes").autocomplete({
        source: "buscar_cliente.php",
        minLength: 2,
        select: function(event, ui) {
            $("#id_cliente_hidden").val(ui.item.id);
        }
    });
});

function prepararNuevoPedido() {
    $('#formPedido')[0].reset();
    $('#modalPedido').modal('show');
}

function editarPedido(id, estadoActual) {
    let nuevo = prompt("Nuevo estado (Pendiente, Pagado, Cancelado):", estadoActual);
    if(nuevo) {
        $.post('actualizar_estado.php', {id: id, estado: nuevo}, function(r){
            if(r.trim() === "ok") location.reload();
            else alert(r);
        });
    }
}

function guardarNuevoPedido() {
    // Aquí mandas los datos a tu archivo de guardado
    $.post('guardar_pedido.php', $('#formPedido').serialize(), function(r){
        if(r.trim() === "ok") location.reload();
        else alert(r);
    });
}

function duplicarPedido() {
    if(confirm("¿Duplicar el último pedido?")) {
        $.post('duplicar_pedido.php', function(r){
            if(r.trim() === "ok") location.reload();
        });
    }
}
</script>
</body>
</html>