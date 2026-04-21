<?php
/**
 * API: Process Approval/Rejection Decision
 * Location: modules/purchasing/api/process_approval.php
 */

session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');
include(__DIR__ . '/../../../conexion.php');

// Autoloader for Purchasing Module
spl_autoload_register(function ($class) {
    if (strpos($class, 'Purchasing\\') === 0) {
        $file = __DIR__ . '/../src/' . str_replace(['Purchasing\\', '\\'], ['', '/'], $class) . '.php';
        if (file_exists($file)) require $file;
    }
});

use Purchasing\Infrastructure\PurchaseRepository;
use Purchasing\Infrastructure\ApprovalRepository;
use Purchasing\Infrastructure\NotificationGateway;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

$userId     = $_SESSION['usuario_id'] ?? null;
$requestId  = (int)($_POST['id'] ?? 0);
$decision   = $_POST['decision'] ?? ''; // 'approve' or 'reject'
$comments   = $_POST['comments'] ?? '';

if (!$userId || !$requestId || !in_array($decision, ['approve', 'reject'])) {
    echo json_encode(["status" => "error", "message" => "Datos insuficientes para procesar."]);
    exit;
}

try {
    $purchaseRepo = new PurchaseRepository($conexion);
    $approvalRepo = new ApprovalRepository($conexion);
    $notifGateway = new NotificationGateway($conexion);

    $request = $purchaseRepo->findById($requestId);
    if (!$request) throw new \Exception("Solicitud no encontrada.");

    // 1. Find the current step to see if this user is the authorized approver
    $currentStep = $approvalRepo->getCurrentStep($requestId);
    if (!$currentStep) throw new \Exception("No hay pasos de aprobación pendientes para esta solicitud.");

    if ((int)$currentStep['approver_id'] !== (int)$userId) {
        throw new \Exception("Usted no es el aprobador autorizado para el nivel actual (" . $currentStep['level'] . ").");
    }

    if ($decision === 'reject') {
        // --- REJECTED ---
        $approvalRepo->markRejected($currentStep['id'], $comments);
        $purchaseRepo->updateStatus($requestId, 'rejected');
        
        // Notify Requester
        $resRequester = mysqli_query($conexion, "SELECT correo, telefono FROM usuarios WHERE id = " . $request['requester_id']);
        $requesterData = mysqli_fetch_assoc($resRequester);
        if ($requesterData) {
            $notifGateway->notifyRequesterDecision($requestId, $requesterData, 'rejected');
        }

        echo json_encode(["status" => "success", "message" => "Solicitud #$requestId rechazada correctamente."]);
    } else {
        // --- APPROVED ---
        $approvalRepo->markApproved($currentStep['id'], $comments);
        
        $nextLevel = (int)$currentStep['level'] + 1;
        
        if ($nextLevel > 3) {
            // FINALLY APPROVED
            $purchaseRepo->updateStatus($requestId, 'approved');
            
            // Notify Requester
            $resRequester = mysqli_query($conexion, "SELECT correo, telefono FROM usuarios WHERE id = " . $request['requester_id']);
            $requesterData = mysqli_fetch_assoc($resRequester);
            if ($requesterData) {
                $notifGateway->notifyRequesterDecision($requestId, $requesterData, 'approved');
            }
            
            echo json_encode(["status" => "success", "message" => "Solicitud #$requestId aprobada FINALMENTE. El total se reflejará en estadísticas."]);
        } else {
            // MOVE TO NEXT LEVEL
            $purchaseRepo->moveToLevel($requestId, $nextLevel);
            
            $nextStep = $approvalRepo->getNextStep($requestId);
            
            if ($nextStep) {
                $resNext = mysqli_query($conexion, "SELECT nombre_completo, correo, telefono FROM usuarios WHERE id = " . $nextStep['approver_id']);
                $nextApproverData = mysqli_fetch_assoc($resNext);
                if ($nextApproverData) {
                    $nextApproverData['nombre'] = $nextApproverData['nombre_completo'];
                    $notifGateway->notifyNextApprover($requestId, $nextApproverData, $request['description'], $nextLevel);
                }
            }
            
            echo json_encode(["status" => "success", "message" => "Nivel ".($nextLevel-1)." aprobado. Solicitud movida al Nivel $nextLevel."]);
        }
    }

} catch (\Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
