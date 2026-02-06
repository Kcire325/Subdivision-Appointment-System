<?php
session_start();
header('Content-Type: application/json');

// Prevent direct access without admin session
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
    echo json_encode(['status' => false, 'msg' => 'Unauthorized']);
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'facilityreservationsystem';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => false, 'msg' => 'Database error']);
    exit();
}

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filters = isset($_GET['filters']) ? $_GET['filters'] : []; // Array of ActionTypes

// Build Clause
$whereClauses = ["1=1"];
$params = [];

// Apply ActionType Filters
if (!empty($filters)) {
    $filterClauses = [];
    $hasAdminFilter = false;
    $standardFilters = [];

    foreach ($filters as $filter) {
        if ($filter === 'Event_Created') {
            $hasAdminFilter = true;
        } else {
            $standardFilters[] = $filter;
        }
    }

    // Process Standard Filters (Approved, Rejected, etc.)
    if (!empty($standardFilters)) {
        $placeholders = [];
        foreach ($standardFilters as $i => $sf) {
            $pName = ":sf_$i";
            $params[$pName] = $sf;
            $placeholders[] = $pName;
        }
        $filterClauses[] = "a.ActionType IN (" . implode(', ', $placeholders) . ")";
    }

    // Process Admin Filter (ActionType OR Remarks)
    if ($hasAdminFilter) {
        // Includes both explicit ActionType OR the fallback Remarks
        $filterClauses[] = "(a.ActionType = 'Event_Created' OR a.Remarks = 'Admin occupied slot')";
    }

    if (!empty($filterClauses)) {
        // Combine with OR if user wants (Approved OR Admin), effectively standard behavior for multiple checkboxes
        $whereClauses[] = "(" . implode(' OR ', $filterClauses) . ")";
    }
} else {
    // Default exclusion if no filters are active (optional, based on preference)
    // To match user request "do not ignore", we can remove this or ensure it matches the view logic
    // For now, let's keep it open or match the overview logic. 
    // The previous overview logic had NO filter, so let's stick to NO filter by default
    // unless the user explicitly asks for one.
}

// Apply Search
if ($search) {
    $whereClauses[] = "(
        u1.FirstName LIKE :search OR u1.LastName LIKE :search OR
        u2.FirstName LIKE :search OR u2.LastName LIKE :search OR
        a.EntityDetails LIKE :search OR
        a.Remarks LIKE :search
    )";
    $params[':search'] = "%$search%";
}

$whereBuf = implode(' AND ', $whereClauses);

// Count Query
$sqlCount = "SELECT COUNT(*) as total 
             FROM auditlogs a
             LEFT JOIN userinfo ui1 ON a.AdminID = ui1.user_id
             LEFT JOIN userinfo ui2 ON a.UserID = ui2.user_id
             WHERE $whereBuf";

$stmtCount = $conn->prepare($sqlCount);
foreach ($params as $key => $val) {
    $stmtCount->bindValue($key, $val);
}
$stmtCount->execute();
$totalRecords = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRecords / $limit);

// Data Query
$sql = "SELECT 
            a.LogID, 
            a.ActionType, 
            a.Timestamp, 
            a.EntityDetails,
            a.Remarks,
            ui1.FirstName as AdminFirst, ui1.LastName as AdminLast, 
            ui2.FirstName as ResidentFirst, ui2.LastName as ResidentLast
        FROM auditlogs a
        LEFT JOIN userinfo ui1 ON a.AdminID = ui1.user_id
        LEFT JOIN userinfo ui2 ON a.UserID = ui2.user_id
        WHERE $whereBuf
        ORDER BY a.Timestamp DESC 
        LIMIT :offset, :limit";

$stmt = $conn->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process for Frontend
$formattedLogs = [];
foreach ($logs as $log) {
    $adminName = trim(($log['AdminFirst'] ?? '') . ' ' . ($log['AdminLast'] ?? '')) ?: 'System';
    $timestamp = date('F d, Y \a\t g:i A', strtotime($log['Timestamp']));
    $details = json_decode($log['EntityDetails'], true) ?? [];

    $residentName = trim(($log['ResidentFirst'] ?? '') . ' ' . ($log['ResidentLast'] ?? '')) ?: 'Unknown';
    $facilityName = $details['facility_name'] ?? 'Unknown Facility';
    $eventDate = isset($details['event_start_date']) ? date('F d, Y', strtotime($details['event_start_date'])) : 'N/A';

    $timeRange = '';
    if (!empty($details['time_start']) && !empty($details['time_end'])) {
        $timeRange = date('g:i A', strtotime($details['time_start'])) . ' - ' . date('g:i A', strtotime($details['time_end']));
    }

    $bgClass = 'bg-secondary';
    $iconSymbol = 'info';
    $actionMessage = 'performed an action';

    switch ($log['ActionType']) {
        case 'Approved':
            $bgClass = 'bg-success';
            $iconSymbol = 'check_circle';
            $actionMessage = 'approved a reservation request';
            break;
        case 'Rejected':
            $bgClass = 'bg-danger';
            $iconSymbol = 'cancel';
            $actionMessage = 'rejected a reservation request';
            break;
        case 'Event_Created':
            $bgClass = 'bg-primary';
            $iconSymbol = 'event_available';
            $actionMessage = 'occupied a reservation slot';
            break;
        case 'Updated':
            $bgClass = 'bg-warning';
            $iconSymbol = 'edit';
            $actionMessage = 'updated a reservation request';
            break;
        default:
            if (isset($log['Remarks']) && trim($log['Remarks']) === 'Admin occupied slot') {
                $bgClass = 'bg-primary';
                $iconSymbol = 'event_available';
                $actionMessage = 'occupied a reservation slot';
            }
    }

    // If Admin occupied the slot (Event_Created), we should NOT show "Resident: Admin Name"
    // The user requested it to "just disappear"
    $residentDisplay = $residentName;
    if ($log['ActionType'] === 'Event_Created' || (isset($log['Remarks']) && trim($log['Remarks']) === 'Admin occupied slot')) {
        $residentDisplay = null;
    }

    $formattedLogs[] = [
        'admin' => htmlspecialchars($adminName),
        'timestamp' => $timestamp,
        'action_message' => $actionMessage,
        'bg_class' => $bgClass,
        'icon' => $iconSymbol,
        'resident' => $residentDisplay ? htmlspecialchars($residentDisplay) : null,
        'facility' => htmlspecialchars($facilityName),
        'date' => htmlspecialchars($eventDate),
        'time' => htmlspecialchars($timeRange)
    ];
}

echo json_encode([
    'status' => true,
    'data' => $formattedLogs,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $totalRecords
    ]
]);
?>