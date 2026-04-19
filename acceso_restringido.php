<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso Denegado</title>
    <style>
        body { background: #050a14; color: white; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; text-align: center; }
        .danger-box { border: 1px solid #dc3545; padding: 40px; border-radius: 20px; background: rgba(220, 53, 69, 0.1); }
        h1 { color: #dc3545; }
    </style>
</head>
<body>
    <div class="danger-box">
        <h1>ACCESO RESTRINGIDO</h1>
        <p>Tu cuenta o dirección IP ha sido bloqueada por seguridad tras múltiples intentos fallidos.</p>
        <p>Contacta al administrador de <b>Pedidos Ohlala</b> para reactivar tu acceso.</p>
        <small style="color: gray;">ID de rastreo: <?php echo $_SERVER['REMOTE_ADDR']; ?></small>
    </div>
</body>
</html>