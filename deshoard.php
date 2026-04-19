<?php
session_start();

// Si el usuario no está autenticado, lo mandamos de vuelta al login
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Pedidos Ohlala</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-deep-blue: #050a14;
            --card-blue: #0f172a;
            --accent-blue: #2563eb;
            --text-silver: #94a3b8;
        }

        body {
            background-color: var(--bg-deep-blue);
            font-family: 'Poppins', sans-serif;
            color: white;
            margin: 0;
        }

        /* Barra de navegación superior */
        .navbar-custom {
            background-color: var(--card-blue);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding: 1rem 2rem;
        }

        .welcome-text {
            font-size: 0.9rem;
            color: var(--text-silver);
        }

        .btn-logout {
            color: #ea868f;
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .btn-logout:hover {
            color: #ff4d5a;
        }

        .main-container {
            padding: 3rem 2rem;
        }

        .stat-card {
            background: var(--card-blue);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 1.5rem;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .icon-box {
            width: 50px;
            height: 50px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--accent-blue);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<nav class="navbar-custom d-flex justify-content-between align-items-center">
    <h4 class="m-0" style="letter-spacing: -1px;">Ohlala <span style="color: var(--accent-blue);">Panel</span></h4>
    <div class="d-flex align-items-center">
        <span class="welcome-text me-3">Bienvenido, <strong><?php echo $_SESSION['usuario_nombre']; ?></strong></span>
        <a href="logout.php" class="btn-logout"><i class="fa-solid fa-power-off"></i> Salir</a>
    </div>
</nav>

<div class="container main-container">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="icon-box"><i class="fa-solid fa-cart-shopping"></i></div>
                <h6 class="text-muted">Pedidos de hoy</h6>
                <h2 class="m-0">0</h2>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card">
                <div class="icon-box"><i class="fa-solid fa-users"></i></div>
                <h6 class="text-muted">Clientes nuevos</h6>
                <h2 class="m-0">0</h2>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <div class="icon-box"><i class="fa-solid fa-dollar-sign"></i></div>
                <h6 class="text-muted">Ventas del mes</h6>
                <h2 class="m-0">$0.00</h2>
            </div>
        </div>
    </div>

    <div class="mt-5 text-center">
        <p class="text-muted">Has ingresado correctamente al sistema.</p>
    </div>
</div>

</body>
</html>