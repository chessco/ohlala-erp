<?php
/**
 * API: Get Live Notifications
 * Location: modules/purchasing/api/get_notifications.php
 */

session_start();
header('Content-Type: application/json');
include(__DIR__ . '/../../../conexion.php');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(["status" => "error", "message" => "No autorizado."]);
    exit;
}

$userId = (int)$_SESSION['usuario_id'];
$notifications = [];

// 1. Pending Approvals (for Approvers)
$sqlApprovals = "
    SELECT s.id, s.request_id, r.description, s.level, r.created_at
    FROM pur_approval_steps s
    JOIN pur_requests r ON s.request_id = r.id
    WHERE s.approver_id = $userId AND s.status = 'pending' AND r.status = 'pending'
    ORDER BY r.created_at DESC LIMIT 5
";
$resApprovals = mysqli_query($conexion, $sqlApprovals);
while ($row = mysqli_fetch_assoc($resApprovals)) {
    $notifications[] = [
        "title" => "Nueva Solicitud #$row[request_id]",
        "body" => "Requiere su aprobación Nivel $row[level]: $row[description]",
        "time" => $row['created_at'],
        "type" => "approval_needed",
        "request_id" => $row['request_id'],
        "link" => "list_requests.php?id=$row[request_id]"
    ];
}

// 2. Final Decisions (for Requesters)
$sqlDecisions = "
    SELECT id, description, status, updated_at
    FROM pur_requests
    WHERE requester_id = $userId AND status IN ('approved', 'rejected')
    ORDER BY updated_at DESC LIMIT 5
";
$resDecisions = mysqli_query($conexion, $sqlDecisions);
while ($row = mysqli_fetch_assoc($resDecisions)) {
    $statusText = ($row['status'] === 'approved') ? 'APROBADA' : 'RECHAZADA';
    $notifications[] = [
        "title" => "Decisión Final: #$row[id]",
        "body" => "Su solicitud '$row[description]' ha sido $statusText.",
        "time" => $row['updated_at'],
        "type" => "decision_made",
        "request_id" => $row['id'],
        "link" => "list_requests.php?id=$row[id]"
    ];
}

// Sort by time
usort($notifications, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

echo json_encode([
    "status" => "success",
    "count" => count($notifications),
    "data" => $notifications
]);
