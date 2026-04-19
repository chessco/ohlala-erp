<?php
include('conexion.php');
session_start();
if (!isset($_SESSION['autenticado'])) {
    exit('No autorizado');
}


$term = mysqli_real_escape_string($conexion, $_POST['term']);

// Subimos el LIMIT a 50 para que el scroll tenga sentido
$sql = "SELECT * FROM productos 
        WHERE nombre LIKE '%$term%' OR codigo LIKE '%$term%' 
        ORDER BY nombre ASC 
        LIMIT 50";

$res = mysqli_query($conexion, $sql);

if(mysqli_num_rows($res) > 0){
    while($row = mysqli_fetch_assoc($res)){
        // Formateamos el precio para que se vea profesional
        $precio_formateado = number_format($row['precio'], 2);
        $img_path = !empty($row['imagen']) ? 'uploads/productos/' . $row['imagen'] : 'https://placehold.co/100x100/1e293b/94a3b8?text=NO+IMG';
        
        echo '<a href="javascript:void(0)" class="list-group-item list-group-item-action py-2" 
              onclick="seleccionarProducto('.$row['id'].', \''.$row['codigo'].'\', \''.$row['nombre'].'\', \''.$img_path.'\')">
              <div class="d-flex align-items-center gap-3">
                <div style="width: 40px; height: 40px; overflow: hidden; border-radius: 8px; flex-shrink: 0; background: rgba(0,0,0,0.2);">
                    <img src="'.$img_path.'" alt="img" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div class="flex-grow-1">
                    <span class="fw-bold">'.$row['nombre'].'</span><br>
                    <small class="text-silver">'.$row['codigo'].'</small>
                </div>
                <span class="badge bg-primary text-white">$ '.$precio_formateado.'</span>
              </div>
              </a>';
    }
} else {
    echo '<div class="list-group-item text-muted">No se encontraron productos</div>';
}
?>
