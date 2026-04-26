# Ohlala! ERP - Deployment Script for Hostinger
# Usage: .\deploy.ps1

$SSH_USER = "u471794305"
$SSH_HOST = "185.212.71.206"
$SSH_PORT = "65002"
# Ruta detectada en la imagen de Hostinger (nota la ortografía 'olhala')
$REMOTE_PATH = "domains/ohlala-erp.pitayacode.io/public_html" 

Write-Host "--- Iniciando Despliegue de Ohlala! ERP ---" -ForegroundColor Cyan

# 1. Preparar lista de exclusiones (basado en .gitignore y seguridad)
$EXCLUDES = @(
    ".git*",
    "scratch",
    ".vscode",
    "conexion.php",
    ".env",
    "*.log",
    "deploy.ps1",
    "deploy_package.tar.gz",
    "check_steps.php",
    "diag_approvals.php",
    "diag_save.php",
    "webhooks/diag_save.php",
    "modules/purchasing/api/test_instantiation.php"
)

# 2. Crear paquete comprimido para transferencia rápida
Write-Host "Step 1: Empaquetando archivos..." -ForegroundColor Yellow

if (Test-Path "deploy_package.tar.gz") { Remove-Item "deploy_package.tar.gz" }

# Construir argumentos de exclusión de forma segura para Windows tar (bsdtar)
$tarArgs = @("-czf", "deploy_package.tar.gz")
foreach ($ex in $EXCLUDES) { 
    $tarArgs += "--exclude=$ex" 
}
$tarArgs += "."

# Ejecutar tar directamente
& tar $tarArgs

if (-not (Test-Path "deploy_package.tar.gz")) {
    Write-Host "Error: No se pudo crear el paquete de despliegue. Verifica que tengas 'tar' instalado (viene por defecto en Windows 10/11)." -ForegroundColor Red
    exit
}

# 3. Subir el paquete via SCP
Write-Host "Step 2: Subiendo a Hostinger ($SSH_HOST)..." -ForegroundColor Yellow
Write-Host "Se te pedirá tu contraseña de SSH." -ForegroundColor Gray
scp -P $SSH_PORT deploy_package.tar.gz "${SSH_USER}@${SSH_HOST}:${REMOTE_PATH}/"

if ($LASTEXITCODE -ne 0) {
    Write-Host "Error en la transferencia SCP." -ForegroundColor Red
    Remove-Item "deploy_package.tar.gz"
    exit
}

# 4. Extraer en el servidor via SSH
Write-Host "Step 3: Extrayendo archivos en el servidor..." -ForegroundColor Yellow
ssh -p $SSH_PORT "${SSH_USER}@${SSH_HOST}" "mkdir -p ${REMOTE_PATH} && cd ${REMOTE_PATH} && tar -xzf deploy_package.tar.gz && rm deploy_package.tar.gz"

if ($LASTEXITCODE -ne 0) {
    Write-Host "Error al extraer en el servidor." -ForegroundColor Red
} else {
    Write-Host "--- DESPLIEGUE COMPLETADO EXITOSAMENTE ---" -ForegroundColor Green
    Write-Host "Recuerda verificar que conexion.php en el servidor tenga las credenciales de Hostinger." -ForegroundColor Cyan
}

# Limpieza local
Remove-Item "deploy_package.tar.gz"
