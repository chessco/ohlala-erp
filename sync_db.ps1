# Ohlala! ERP - Database Sync Script (v6 - Fixed Variables)
# Sincroniza Local (XAMPP) -> Remoto (Hostinger)

$LOCAL_DB = "pedidos_ohlala"
$SSH_USER = "u471794305"
$SSH_HOST = "185.212.71.206"
$SSH_PORT = "65002"
$REMOTE_PATH = "domains/ohlala-erp.pitayacode.io/public_html"

Write-Host "--- Iniciando Sincronizacion Automatizada ---" -ForegroundColor Cyan

# 1. Exportar Base de Datos Local
Write-Host "Step 1: Exportando contenido local..." -ForegroundColor Yellow
$dumpFile = "local_db_sync.sql"
& C:\xampp\mysql\bin\mysqldump.exe -u root $LOCAL_DB --result-file=$dumpFile

if ($LASTEXITCODE -ne 0) {
    Write-Host "Error al exportar localmente." -ForegroundColor Red
    exit
}

# 2. Crear script de importacion temporal (Escapamos los simbolos $ de PHP)
$importScript = "import_db.php"
$phpCode = @"
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!file_exists('conexion.php')) {
    die('Error: No se encontro conexion.php en el servidor.');
}

include('conexion.php');

if (!isset(`$conexion)) {
    die('Error: La variable `$conexion no esta definida en conexion.php');
}

if (mysqli_connect_errno()) {
    die('Error de conexion: ' . mysqli_connect_error());
}

`$sql = file_get_contents('$dumpFile');
if (!`$sql) {
    die('Error: No se pudo leer el archivo $dumpFile');
}

// Ejecutar multiples consultas
if (mysqli_multi_query(`$conexion, `$sql)) {
    do {
        if (`$result = mysqli_store_result(`$conexion)) {
            mysqli_free_result(`$result);
        }
    } while (mysqli_more_results(`$conexion) && mysqli_next_result(`$conexion));
    
    if (mysqli_errno(`$conexion)) {
        echo 'Error en consulta: ' . mysqli_error(`$conexion);
    } else {
        echo 'OK';
    }
} else {
    echo 'Error inicial: ' . mysqli_error(`$conexion);
}
unlink('$importScript');
unlink('$dumpFile');
?>
"@
Set-Content -Path $importScript -Value $phpCode

# 3. Subir archivos a Hostinger
Write-Host "Step 2: Subiendo archivos al servidor..." -ForegroundColor Yellow
scp -P $SSH_PORT $dumpFile $importScript "${SSH_USER}@${SSH_HOST}:${REMOTE_PATH}/"

if ($LASTEXITCODE -ne 0) {
    Write-Host "Error al subir archivos." -ForegroundColor Red
    exit
}

# 4. Ejecutar importacion via Web
Write-Host "Step 3: Ejecutando importacion en el servidor..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://ohlala-erp.pitayacode.io/$importScript" -UseBasicParsing -ErrorAction Stop
    if ($response.Content.Trim() -eq "OK") {
        Write-Host "--- SINCRONIZACION COMPLETADA EXITOSAMENTE ---" -ForegroundColor Green
    } else {
        Write-Host "Error en el servidor: $($response.Content)" -ForegroundColor Red
    }
} catch {
    Write-Host "Error en la peticion web. Es posible que el script haya tardado mucho o tenga un error." -ForegroundColor Red
    Write-Host "Detalle: $($_.Exception.Message)"
}

# Limpieza local
if (Test-Path $dumpFile) { Remove-Item $dumpFile }
if (Test-Path $importScript) { Remove-Item $importScript }
