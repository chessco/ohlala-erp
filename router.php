<?php
// router.php

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$ext = pathinfo($path, PATHINFO_EXTENSION);

// 1. Si el archivo existe (css, js, imagenes), sírvelo tal cual.
if (file_exists($_SERVER["DOCUMENT_ROOT"] . $path) && $ext != "") {
    return false; // Deja que PHP sirva el archivo estático
}

// 2. Si no tiene extensión, intentamos añadir .php
if ($ext == "") {
    if (str_ends_with($path, '/dashboard')) {
        include('dashboard2.php');
        exit();
    }
    if (file_exists($_SERVER["DOCUMENT_ROOT"] . $path . '.php')) {
        include($_SERVER["DOCUMENT_ROOT"] . $path . '.php');
        exit();
    }
}

// 3. Si no es nada de lo anterior, servimos index.php (o 404 si prefieres)
// Por ahora, dejamos que el default maneje el resto o forzamos index si es raiz
if ($path == '/') {
    include('index.php');
    exit();
}

// 4. Si llegamos aqui, 404
http_response_code(404);
echo "404 Not Found (Router)";
?>
