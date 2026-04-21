<?php
/**
 * API: Update Approval Comment
 * Location: modules/purchasing/api/update_comment.php
 */

session_start();
header('Content-Type: application/json');
include(__DIR__ . '/../../../conexion.php');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(["status" => "error", "message" => "No autorizado."]);
    exit;
}

$userId = (int)$_SESSION['usuario_id'];
$stepId = (int)($_POST['stepId'] ?? 0);
$requestId = (int)($_POST['requestId'] ?? 0);
$comment = mysqli_real_escape_string($conexion, $_POST['comment'] ?? '');

if ($stepId <= 0 && $requestId <= 0) {
    echo json_encode(["status" => "error", "message" => "ID de paso o solicitud inválido."]);
    exit;
}

try {
    if ($stepId > 0) {
        // --- UPDATING AN APPROVAL STEP COMMENT ---
        // 1. Fetch current step to verify ownership and level
        $sql = "SELECT request_id, level, approver_id FROM pur_approval_steps WHERE id = $stepId";
        $res = mysqli_query($conexion, $sql);
        $step = mysqli_fetch_assoc($res);

        if (!$step) throw new Exception("Paso no encontrado.");
        if ($step['approver_id'] != $userId) throw new Exception("No es el autor de este comentario.");

        // 2. Check if the NEXT level has already processed
        $nextLevel = $step['level'] + 1;
        $sqlNext = "SELECT status FROM pur_approval_steps WHERE request_id = $step[request_id] AND level = $nextLevel";
        $resNext = mysqli_query($conexion, $sqlNext);
        if ($nextNext = mysqli_fetch_assoc($resNext)) {
            if ($nextNext['status'] !== 'pending') {
                throw new Exception("El comentario ya no se puede editar porque el siguiente nivel ya procesó su decisión.");
            }
        } else {
            // No next level in steps table? Check if the overall request is already finalized
            $sqlReq = "SELECT status FROM pur_requests WHERE id = $step[request_id]";
            $resReq = mysqli_query($conexion, $sqlReq);
            $req = mysqli_fetch_assoc($resReq);
            if ($req && !in_array($req['status'], ['pending', 'approved'])) {
                throw new Exception("La solicitud ya ha sido procesada más allá del flujo de aprobación.");
            }
        }

        // 3. Update the comment
        $updateSql = "UPDATE pur_approval_steps SET comments = '$comment' WHERE id = $stepId";
        if (!mysqli_query($conexion, $updateSql)) throw new Exception("Error al actualizar el comentario.");

    } else if ($requestId > 0) {
        // --- UPDATING INITIAL REQUEST DESCRIPTION (GLOSA) ---
        $sql = "SELECT requester_id, status FROM pur_requests WHERE id = $requestId";
        $res = mysqli_query($conexion, $sql);
        $req = mysqli_fetch_assoc($res);

        if (!$req) throw new Exception("Solicitud no encontrada.");
        if ($req['requester_id'] != $userId) throw new Exception("No es el autor de esta solicitud.");

        // Check if ANY approval has happened (Level 1)
        $sqlL1 = "SELECT status FROM pur_approval_steps WHERE request_id = $requestId AND level = 1";
        $resL1 = mysqli_query($conexion, $sqlL1);
        if ($l1 = mysqli_fetch_assoc($resL1)) {
            if ($l1['status'] !== 'pending') {
                throw new Exception("No se puede editar la glosa inicial porque ya comenzó el proceso de aprobación.");
            }
        }

        $updateSql = "UPDATE pur_requests SET description = '$comment' WHERE id = $requestId";
        if (!mysqli_query($conexion, $updateSql)) throw new Exception("Error al actualizar la glosa.");
    }

    echo json_encode(["status" => "success", "message" => "Comentario actualizado correctamente."]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
