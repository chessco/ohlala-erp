<?php
header('Content-Type: application/json; charset=utf-8');
include('../../../conexion.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['autenticado'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$current_user_id = $_SESSION['usuario_id'];

// 1. Fetch Requests (limited to 20 for performance)
$sql = "SELECT r.*, s.name as supplier_name 
        FROM pur_requests r
        LEFT JOIN pur_suppliers s ON r.supplier_id = s.id 
        ORDER BY r.id DESC LIMIT 20";
$resRequests = mysqli_query($conexion, $sql);
$requests = [];
while ($row = mysqli_fetch_assoc($resRequests)) {
    // Check if current user is the next approver for this request
    $requestId = (int)$row['id'];
    $resCheckStep = mysqli_query($conexion, "SELECT approver_id FROM pur_approval_steps WHERE request_id = $requestId AND status = 'pending' ORDER BY level ASC LIMIT 1");
    $checkStep = mysqli_fetch_assoc($resCheckStep);
    $row['is_my_decision'] = ($checkStep && (int)$checkStep['approver_id'] === (int)$current_user_id);
    
    // Ensure we have a string for supplier name even if NULL
    $row['supplier_name'] = $row['supplier_name'] ?? 'S/P (Sin Proveedor)';
    
    $requests[] = $row;
}

// 2. Fetch Real-time Stats
$monthStart = date('Y-m-01 00:00:00');
$resMonthTotal = mysqli_query($conexion, "SELECT SUM(total_amount) as total FROM pur_requests WHERE created_at >= '$monthStart' AND status = 'approved'");
$monthTotal = (float)(mysqli_fetch_assoc($resMonthTotal)['total'] ?? 0);

$resPendingCount = mysqli_query($conexion, "SELECT COUNT(*) as total FROM pur_requests WHERE status = 'pending'");
$pendingCount = (int)(mysqli_fetch_assoc($resPendingCount)['total'] ?? 0);

// 3. Efficiency Placeholder (could be calculated if target is known)
$efficiency = 65; 

echo json_encode([
    'status' => 'success',
    'requests' => $requests,
    'stats' => [
        'monthTotal' => $monthTotal,
        'pendingCount' => $pendingCount,
        'efficiency' => $efficiency
    ],
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
