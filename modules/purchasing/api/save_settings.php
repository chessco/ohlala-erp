<?php
/**
 * API: Save Module Settings
 * Location: modules/purchasing/api/save_settings.php
 */

session_start();
header('Content-Type: application/json');
include(__DIR__ . '/../../../conexion.php');

// Autoloader for Purchasing Module
spl_autoload_register(function ($class) {
    if (strpos($class, 'Purchasing\\') === 0) {
        $file = __DIR__ . '/../src/' . str_replace(['Purchasing\\', '\\'], ['', '/'], $class) . '.php';
        if (file_exists($file)) require $file;
    }
});

use Purchasing\Infrastructure\SettingsRepository;

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "No autorizado."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

try {
    $settingsRepo = new SettingsRepository($conexion);

    foreach ($_POST as $key => $value) {
        $settingsRepo->save($key, $value);
    }

    echo json_encode([
        "status" => "success",
        "message" => "Configuraciones guardadas correctamente."
    ]);

} catch (\Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
