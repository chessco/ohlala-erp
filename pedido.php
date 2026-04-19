<?php
include('conexion.php');
session_start();

// 1. Control de acceso
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header("Location: index.php");
    exit();
}

$nombre_vendedor = $_SESSION['usuario_nombre'];
$rol_actual = isset($_SESSION['usuario_rol']) ? $_SESSION['usuario_rol'] : 2;
$esAdmin = ($rol_actual == 1);

// 2. Estadísticas de hoy
$hoy_db = date('Y-m-d');
$res_pedidos = mysqli_query($conexion, "SELECT COUNT(*) as total FROM pedidos WHERE DATE(fecha) = '$hoy_db'");
$total_hoy = mysqli_fetch_assoc($res_pedidos)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Ohlala - Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        :root { --bg-deep: #050a14; --card-blue: #0f172a; --accent: #2563eb; --text-silver: #94a3b8; }
        body { background-color: var(--bg-deep); color: white; font-family: 'Poppins', sans-serif; }
        .navbar-custom { background-color: var(--card-blue); border-bottom: 1px solid rgba(255,255,255,0.05); padding: 1rem 2rem; }
        .stat-card { background: var(--card-blue); border-radius: 15px; padding: 1.5rem; border: 1px solid rgba(255,255,255,0.05); }
        .card-main { background: var(--card-blue); border-radius: 20px; padding: 25px; border: none; }
        .table { color: white !important; border-color: #334155; }
        .modal-content { background-color: var(--card-blue); color: white; border: 1px solid rgba(255,255,255,0.1); }
        .form-control, .form-select { background-color: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; }
        #resultadosBusqueda { z-index: 1100; max-height: 250px; overflow-y: auto; position: absolute; width: 100%; }
        .list-group-item { background-color: #1e293b; border-color: #334155; color: white; cursor: pointer; }
        .list-group-item:hover { background-color: var(--accent); }
    </style>
</head>
<body>

<nav class="navbar-custom d-flex justify-content-between align-items-center mb-4 shadow">
    <h4 class="m-0 text-white">Ohlala <span style="color: var(--accent);">Pedidos</span></h4>
    <div class="d-flex align-items-center">
        <span class="text-silver me-3 small">Bienvenido, <b><?php echo $nombre_vendedor; ?></b></span>
        <a href="logout.php" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-power-off"></i></a>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-silver small">Pedidos de Hoy</div>
                <h2 class="m-0 fw-bold"><?php echo $total_hoy; ?></h2>
            </div>
        </div>
        <div class="col-md-9 text-end">
            <a href="clientes.php" class="btn btn-outline-light me-1"><i class="fa-solid fa-users"></i> Clientes</a>
            <button class="btn btn-primary px-4 py-2" onclick="prepararNuevoPedido()">
                <i class="fa-solid fa-plus me-2"></i> Nuevo Pedido
            </button>
        </div>
    </div>

    <div class="card-main shadow-lg">
        <div class="table-responsive">
            <table id="tablaPedidos" class="table table-dark table-hover w-100">
                <thead>
                    <tr class="text-silver">
                        <th>Folio</th><th>Fecha</th><th>Cliente</th><th>Estado</th><th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM pedidos ORDER BY id DESC";
                    $res = mysqli_query($conexion, $sql);
                    while($row = mysqli_fetch_assoc($res)):
                        $badge = ($row['estado'] == 'Cancelado') ? 'bg-danger' : 'bg-warning text-dark';
                    ?>
                    <tr>
                        <td class="fw-bold text-primary"><?php echo $row['folio']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                        <td><?php echo $row['cliente_nombre']; ?></td>
                        <td><span class="badge <?php echo $badge; ?>"><?php echo $row['estado']; ?></span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-warning" onclick="editarPedido(<?php echo $row['id']; ?>, '<?php echo $row['estado']; ?>')">
                                <i class="fa-solid fa-pen-to-square"></i>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="tituloModal">Pedido</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formPedido">
                    <input type="hidden" id="pedido_id" name="id">
                    
                    <div class="row mb-3 g-3">
                        <div class="col-md-5">
                            <label class="form-label text-silver small">Cliente</label>
                            <input type="text" id="cliente_nombre" name="cliente_nombre" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-silver small">Fecha</label>
                            <input type="date" id="fecha_pedido" name="fecha" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-silver small">Estado</label>
                            <select id="estado" name="estado" class="form-select">
                                <option value="Pendiente">Pendiente</option>
                                <option value="Completado">Completado</option>
                                <option value="Incompleto">Incompleto</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 align-items-end mb-4 p-3 shadow-sm" style="background: rgba(255,255,255,0.03); border-radius: 10px;">
                        <div class="col-md-7 position-relative">
                            <label class="form-label text-silver small">1. Buscar Producto</label>
                            <input type="text" class="form-control" id="inputBuscarProd" onkeyup="buscarProducto(this.value)" placeholder="Código o nombre...">
                            <div id="resultadosBusqueda" class="list-group"></div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-silver small">2. Cant.</label>
                            <input type="number" id="temp_cantidad" class="form-control" value="1" min="1">
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-success w-100" onclick="confirmarSeleccion()">
                                <i class="fa-solid fa-cart-plus me-1"></i> Agregar
                            </button>
                        </div>
                    </div>

                    <input type="hidden" id="temp_id">
                    <input type="hidden" id="temp_nombre">
                    <input type="hidden" id="temp_img">

                    <table class="table table-sm align-middle">
                        <thead><tr class="text-silver"><th>Img</th><th>Producto</th><th width="120">Cantidad</th><th>Comentario</th><th width="40"></th></tr></thead>
                        <tbody id="listaProductos"></tbody>
                    </table>
                </form>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-primary w-100" onclick="guardarTodo()">Guardar Pedido</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#tablaPedidos').DataTable({ "language": {"url": "es-ES.json"}, "order": [[0, "desc"]] });
});

function prepararNuevoPedido() {
    $('#formPedido')[0].reset();
    $('#pedido_id').val('');
    $('#listaProductos').empty();
    $('#tituloModal').text('Nuevo Pedido');
    $('#cliente_nombre').val('<?php echo $nombre_vendedor; ?>');
    $('#fecha_pedido').val(new Date().toISOString().split('T')[0]);
    $('#modalPedido').modal('show');
}

function buscarProducto(t) {
    if(t.length > 2) $.post('buscar_productos_ajax.php', { term: t }, function(data) { $('#resultadosBusqueda').html(data).show(); });
    else $('#resultadosBusqueda').hide();
}

function seleccionarProducto(id, cod, nom, img) {
    $('#temp_id').val(id);
    $('#temp_nombre').val(nom);
    $('#temp_img').val(img);
    $('#inputBuscarProd').val(nom);
    $('#resultadosBusqueda').hide();
}

function confirmarSeleccion() {
    let id = $('#temp_id').val();
    let nom = $('#temp_nombre').val();
    let img = $('#temp_img').val();
    let cant = $('#temp_cantidad').val();

    if (!id) { alert("Busca y selecciona un producto."); return; }

    let fila = `
        <tr id="prod_${id}">
            <td>
                <div style="width: 40px; height: 40px; overflow: hidden; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2);">
                    <img src="${img}" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            </td>
            <td><small>${nom}</small></td>
            <td><input type="number" name="cant[]" class="form-control form-control-sm" value="${cant}" readonly></td>
            <td><input type="text" name="comentario[]" class="form-control form-control-sm" placeholder="Comentario..."></td>
            <td><button type="button" class="btn btn-sm text-danger" onclick="$('#prod_${id}').remove()"><i class="fa-solid fa-trash"></i></button></td>
            <input type="hidden" name="p_id[]" value="${id}">
        </tr>`;
    
    $('#listaProductos').append(fila);
    $('#temp_id').val('');
    $('#temp_img').val('');
    $('#inputBuscarProd').val('').focus();
    $('#temp_cantidad').val(1);
}

function editarPedido(id, estado) {
    if (estado === 'Cancelado') { alert("No se puede editar un pedido cancelado."); return; }
    $.post('obtener_pedido.php', { id: id }, function(data) {
        let p = JSON.parse(data);
        $('#pedido_id').val(p.id);
        $('#cliente_nombre').val(p.cliente_nombre);
        $('#fecha_pedido').val(p.fecha.split(' ')[0]);
        $('#estado').val(p.estado);
        $('#tituloModal').text('Editar Pedido #' + p.folio);
        $('#listaProductos').empty();
        
        p.items.forEach(item => {
            let img_path = item.imagen ? 'uploads/productos/' + item.imagen : 'https://placehold.co/100x100/1e293b/94a3b8?text=NO+IMG';
            let fila = `<tr id="prod_${item.id}">
                <td>
                    <div style="width: 40px; height: 40px; overflow: hidden; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2);">
                        <img src="${img_path}" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                </td>
                <td><small>${item.nombre}</small></td>
                <td><input type="number" name="cant[]" class="form-control form-control-sm" value="${item.cantidad}" readonly></td>
                <td><input type="text" name="comentario[]" class="form-control form-control-sm" value="${item.comentario || ''}" placeholder="Comentario..."></td>
                <td><button type="button" class="btn btn-sm text-danger" onclick="$('#prod_${item.id}').remove()"><i class="fa-solid fa-trash"></i></button></td>
                <input type="hidden" name="p_id[]" value="${item.id}">
            </tr>`;
            $('#listaProductos').append(fila);
        });
        $('#modalPedido').modal('show');
    });
}

function guardarTodo() {
    $.post('actualizar_pedido.php', $('#formPedido').serialize(), function(r) {
        if(r.trim() == "ok") location.reload();
        else alert("Error: " + r);
    });
}
</script>
</body>
</html>