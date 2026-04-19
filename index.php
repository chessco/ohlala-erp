<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Acceso - Pedidos Ohlala</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-deep-blue: #050a14;      /* Azul muy oscuro */
            --card-blue: #0f172a;         /* Azul medio oscuro */
            --btn-blue-medium: #2563eb;   /* Azul para el botón */
            --btn-blue-hover: #1d4ed8;    /* Azul para el hover */
            --text-silver: #94a3b8;
            --accent-blue: #3a86ff;
        }

        body {
            background: radial-gradient(circle at center, #111d35 0%, var(--bg-deep-blue) 100%);
            font-family: 'Poppins', sans-serif;
            height: 100dvh;
            display: flex;
            align-items: center;
            color: #ffffff;
            margin: 0;
        }

        .card-login {
            background: var(--card-blue);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            padding: 2.5rem;
            width: 100%;
        }

        .logo-text {
            font-weight: 600;
            font-size: 2.5rem;
            letter-spacing: -1px;
            color: #ffffff;
            margin-bottom: 0.2rem;
            background: linear-gradient(to bottom, #ffffff, #94a3b8);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-label {
            color: var(--text-silver);
            font-weight: 400;
            font-size: 0.85rem;
            margin-bottom: 8px;
            margin-left: 5px;
        }

        .input-group-text {
            background-color: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--accent-blue);
            border-right: none;
            border-radius: 12px 0 0 12px;
        }

        .form-control {
            background-color: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 12px;
            border-radius: 0 12px 12px 0;
            font-size: 0.95rem;
        }

        .form-control:focus {
            background-color: rgba(0, 0, 0, 0.4);
            border-color: var(--btn-blue-medium);
            box-shadow: 0 0 10px rgba(37, 99, 235, 0.2);
            color: #fff;
        }

        .btn-ohlala {
            background-color: var(--btn-blue-medium);
            border: none;
            color: #ffffff;
            font-weight: 600;
            border-radius: 12px;
            padding: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-ohlala:hover {
            background-color: var(--btn-blue-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }

        .footer-text {
            color: #4b5563;
            font-size: 0.8rem;
        }

        .alert-custom {
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: #ea868f;
            border-radius: 12px;
            font-size: 0.85rem;
            padding: 10px;
        }

        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-9 col-md-7 col-lg-5 col-xl-4 fade-in">
            
            <div class="text-center mb-4">
                <h1 class="logo-text">Ohlala</h1>
                <p style="color: var(--text-silver); font-weight: 300;">Gestión de Pedidos • v2.1</p>
            </div>

            <div class="card card-login">
                <?php if(isset($_GET['error'])): ?>
                    <div class="alert alert-custom text-center mb-4">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> 
                        Credenciales incorrectas
                    </div>
                <?php endif; ?>

                <form action="login_process.php" method="POST">
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                            <input type="text" name="usuario" id="usuario" class="form-control" placeholder="Nombre de usuario" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-ohlala">
                            Iniciar Sesión <i class="fa-solid fa-arrow-right-to-bracket ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="text-center mt-5">
                <p class="footer-text">
                    &copy; 2025 Pedidos Ohlala <br> 
                    <small><i class="fa-solid fa-shield-halved"></i> Conexión Segura SSL</small>
                </p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('sw.js')
                .then(reg => console.log('SW registrado', reg))
                .catch(err => console.log('Error registro SW', err));
        });
    }
</script>
</body>
</html>