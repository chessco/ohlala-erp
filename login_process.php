<?php
session_start();
include('conexion.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_form  = $_POST['usuario'] ?? 'ANONIMO';
    $password_form = $_POST['password'] ?? '';
    $ip_cliente    = $_SERVER['REMOTE_ADDR'];
    $user_agent    = mysqli_real_escape_string($conexion, $_SERVER['HTTP_USER_AGENT']);

    // Función helper para logging seguro
    function log_acceso($conexion, $usuario, $ip, $estado, $detalles) {
        $stmt_log = mysqli_prepare($conexion, "INSERT INTO log_accesos (usuario_intentado, ip_direccion, estado, detalles) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_log, "ssss", $usuario, $ip, $estado, $detalles);
        mysqli_stmt_execute($stmt_log);
    }

    // --- BLOQUE 0: VERIFICAR SI LA IP ESTÁ EN LISTA NEGRA ---
    $check_ip = mysqli_query($conexion, "SELECT * FROM ips_bloqueadas WHERE ip = '$ip_cliente' AND intentos >= 3");
    if (mysqli_num_rows($check_ip) > 0) {
        log_acceso($conexion, $usuario_form, $ip_cliente, 'bloqueado', 'IP Baneada intentó entrar');
        header("Location: acceso_restringido");
        exit();
    }

    // 1. Buscamos al usuario
    $query = "SELECT * FROM usuarios WHERE usuario = ?";
    $stmt  = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "s", $usuario_form);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    if ($user_data = mysqli_fetch_assoc($resultado)) {
        // --- LOGICA DE USUARIO EXISTENTE ---
        if ($user_data['acceso_permitido'] == 0) {
            header("Location: acceso_restringido");
            exit();
        }

        if (password_verify($password_form, $user_data['password'])) {
            // ÉXITO: Limpiamos fallos de usuario e IP
            mysqli_query($conexion, "UPDATE usuarios SET intentos_fallidos = 0 WHERE id = {$user_data['id']}");
            mysqli_query($conexion, "DELETE FROM ips_bloqueadas WHERE ip = '$ip_cliente'");
            
            log_acceso($conexion, $usuario_form, $ip_cliente, 'exitoso', 'Login correcto');

            $_SESSION['autenticado']    = true;
            $_SESSION['usuario_id']     = $user_data['id'];
            $_SESSION['usuario_nombre'] = $user_data['nombre_completo'];
            $_SESSION['usuario_rol']    = $user_data['rol']; 
            header("Location: dashboard.php");
            exit();
        } else {
            // CONTRASEÑA ERRÓNEA
            $intentos = $user_data['intentos_fallidos'] + 1;
            $bloqueo = ($intentos >= 3) ? ", acceso_permitido = 0" : "";
            mysqli_query($conexion, "UPDATE usuarios SET intentos_fallidos = $intentos $bloqueo WHERE id = {$user_data['id']}");
            
            log_acceso($conexion, $usuario_form, $ip_cliente, 'fallido', "Contraseña mal ($intentos/3)");
            
            if ($intentos >= 3) header("Location: acceso_restringido");
            else header("Location: index?error=1");
            exit();
        }
    } else {
        // --- BLOQUE NUEVO: EL USUARIO NO EXISTE ---
        mysqli_query($conexion, "INSERT INTO ips_bloqueadas (ip, intentos) VALUES ('$ip_cliente', 1) 
                                 ON DUPLICATE KEY UPDATE intentos = intentos + 1");
        
        $res_ip = mysqli_query($conexion, "SELECT intentos FROM ips_bloqueadas WHERE ip = '$ip_cliente'");
        $data_ip = mysqli_fetch_assoc($res_ip);
        
        log_acceso($conexion, $usuario_form, $ip_cliente, 'fallido', "Usuario inexistente. IP Fallo: {$data_ip['intentos']}/3");

        if ($data_ip['intentos'] >= 3) {
            header("Location: acceso_restringido");
        } else {
            header("Location: index?error=1");
        }
        exit();
    }
}
?>