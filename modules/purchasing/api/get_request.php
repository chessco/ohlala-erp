<?php
/**
 * API: Get Single Purchase Request
 * Location: modules/purchasing/api/get_request.php
 */

session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json');
include(__DIR__ . '/../../../conexion.php');

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "ID inválido."]);
    exit;
}

try {
    $res = mysqli_query($conexion, "SELECT r.*, u.nombre_completo as requester_name 
                                    FROM pur_requests r 
                                    LEFT JOIN usuarios u ON r.requester_id = u.id 
                                    WHERE r.id = $id");
    $data = mysqli_fetch_assoc($res);

    if (!$data) {
        throw new \Exception("Solicitud no encontrada.");
    }

    // Fetch Items Detail
    $resItems = mysqli_query($conexion, "SELECT * FROM pur_request_items WHERE request_id = $id");
    $items = [];
    while($row = mysqli_fetch_assoc($resItems)) {
        // Find unit from catalog if available
        $resCat = mysqli_query($conexion, "SELECT unit FROM pur_catalog_items WHERE id = " . (int)$row['item_id']);
        $cat = mysqli_fetch_assoc($resCat);
        $row['unit'] = $cat['unit'] ?? 'N/A';
        $items[] = $row;
    }
    $data['items'] = $items;

    // Fetch current pending step to identify if the current user is the authorized approver
    $resStep = mysqli_query($conexion, "SELECT approver_id FROM pur_approval_steps WHERE request_id = $id AND status = 'pending' ORDER BY level ASC LIMIT 1");
    $step = mysqli_fetch_assoc($resStep);
    $data['current_approver_id'] = $step ? (int)$step['approver_id'] : null;

    // Fetch All Approval Steps for Visual Tracker with User Names and Comments
    $sqlSteps = "SELECT s.id as step_id, s.level, s.status, s.processed_at, s.comments, s.approver_id, u.nombre_completo as approver_name 
                 FROM pur_approval_steps s 
                 LEFT JOIN usuarios u ON s.approver_id = u.id 
                 WHERE s.request_id = $id 
                 ORDER BY s.level ASC";
    $resAllSteps = mysqli_query($conexion, $sqlSteps);
    $approvalSteps = [];
    while($s = mysqli_fetch_assoc($resAllSteps)) $approvalSteps[] = $s;
    $data['approval_steps'] = $approvalSteps;

    echo json_encode([
        "status" => "success",
        "data"   => $data
    ]);

} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
