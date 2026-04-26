# Ohlala! ERP - Update Script V2 (Seguro y Rapido)

$SSH_USER = "u471794305"
$SSH_HOST = "185.212.71.206"
$SSH_PORT = "65002"
$REMOTE_PATH = "domains/ohlala-erp.pitayacode.io/public_html"

# --- CONFIGURACION DE EXCLUSIONES ---
$excludePatterns = @(
    "\.ps1$", "\.bat$", "\.sql$", "\.git", "conexion\.php$", 
    "unlock\.php$", "test_db\.php$", "import_db\.php$", "deploy_package\.tar\.gz$"
)

Write-Host "--- Analizando cambios en el proyecto ---" -ForegroundColor Cyan

# Obtener archivos cambiados o nuevos
$allFiles = (git diff --name-only) + (git ls-files --others --exclude-standard) | Sort-Object -Unique

# Filtrar archivos para que solo suba lo que realmente es del sitio web
$filteredFiles = $allFiles | Where-Object {
    $file = $_
    $keep = $true
    foreach ($pattern in $excludePatterns) {
        if ($file -match $pattern) { $keep = $false; break }
    }
    $keep
}

if ($filteredFiles.Count -eq 0) {
    Write-Host "No hay cambios relevantes para subir." -ForegroundColor Yellow
    exit
}

Write-Host "Archivos seleccionados para parche:" -ForegroundColor Gray
$filteredFiles | ForEach-Object { Write-Host " [+] $_" -ForegroundColor Green }

$confirm = Read-Host "`n¿Deseas subir estos $($filteredFiles.Count) archivos? (s/n)"
if ($confirm -ne 's') { exit }

Write-Host "`n--- Creando paquete de actualizacion ---" -ForegroundColor Yellow
$tempPackage = "patch_temp.tar.gz"

# Crear el tar solo con los archivos filtrados
# Usamos un archivo de texto temporal para pasar la lista a tar
$fileListPath = "$env:TEMP\patch_files.txt"
$filteredFiles | Out-File -FilePath $fileListPath -Encoding utf8

tar -czf $tempPackage -T $fileListPath

if (Test-Path $tempPackage) {
    Write-Host "--- Subiendo y aplicando cambios ---" -ForegroundColor Cyan
    Write-Host "Pidiendo password para el servidor..." -ForegroundColor White
    
    # Subir el paquete
    scp -P $SSH_PORT $tempPackage "${SSH_USER}@${SSH_HOST}:$REMOTE_PATH/"
    
    # Extraer en el servidor y borrar el temporal
    ssh -p $SSH_PORT "${SSH_USER}@${SSH_HOST}" "cd $REMOTE_PATH && tar -xzf $tempPackage && rm $tempPackage"
    
    # Limpiar local
    Remove-Item $tempPackage
    Remove-Item $fileListPath

    Write-Host "`n--- ACTUALIZACION EXITOSA ---" -ForegroundColor Green
    Write-Host "Los archivos se han sincronizado correctamente."
} else {
    Write-Host "Error al crear el paquete." -ForegroundColor Red
}
