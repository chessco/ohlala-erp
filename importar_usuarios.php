<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importar Usuarios Ohlala</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #050a14; color: white; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .card-upload { background: #0f172a; border-radius: 20px; padding: 40px; border: 1px solid #1e293b; width: 100%; max-width: 500px; }
    </style>
</head>
<body>
    <div class="card-upload shadow-lg">
        <h4 class="text-center mb-4">Carga Masiva de Usuarios</h4>
        <p class="text-center text-muted small mb-4">
            Columnas requeridas:<br>
            <strong>A: Nombre | B: Correo | C: Teléfono</strong><br>
            <strong>D: Usuario | E: Password | F: Rol</strong>
        </p>
        <form action="procesar_usuarios_excel.php" method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="form-label small text-secondary">Seleccione archivo .xlsx</label>
                <input type="file" name="archivo_usuarios" class="form-control bg-dark text-white border-secondary" accept=".xlsx" required>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary py-2 fw-bold">COMENZAR IMPORTACIÓN</button>
                <a href="usuarios.php" class="btn btn-link text-secondary btn-sm">Cancelar y volver</a>
            </div>
        </form>
    </div>
</body>
</html>
