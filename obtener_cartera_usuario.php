<?php
include('conexion.php');
session_start();

if(isset($_POST['usuario_id'])){
    $u_id = mysqli_real_escape_string($conexion, $_POST['usuario_id']);
    
    // IMPORTANTE: Aquí ya usamos id_usuario que es el nombre real en tu BD
    $query = "SELECT id, codigo_cliente, nombre FROM clientes WHERE id_usuario = '$u_id'";
    $res = mysqli_query($conexion, $query);
    
    if(mysqli_num_rows($res) > 0){
        while($c = mysqli_fetch_assoc($res)){
            $id = $c['id'];
            $cod = htmlspecialchars($c['codigo_cliente']);
            $nom = htmlspecialchars($c['nombre']);
            
            // Esta estructura HTML es la que recibe el JavaScript y mete en #listaCartera
            echo "
            <tr id='cli_row_$id'>
                <td class='ps-3 small text-info'>$cod</td>
                <td class='small'>$nom</td>
                <td class='text-center'>
                    <button type='button' class='btn btn-sm text-danger border-0' onclick='removerDeCartera($id)'>
                        <i class='fa-solid fa-trash-can'></i>
                    </button>
                    <input type='hidden' name='clientes_cartera[]' value='$id'>
                </td>
            </tr>";
        }
    } else {
        // Si no tiene clientes, no devolvemos nada para que la tabla empiece vacía
        echo "";
    }
}
?>