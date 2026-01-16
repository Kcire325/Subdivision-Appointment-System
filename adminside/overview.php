<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../login/login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "facilityreservationsystem");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT user_id, Email, Role, FirstName, LastName, Status FROM users WHERE Role = 'Resident' AND Status = 'Active' ORDER BY user_id ASC";
$result = $conn->query($sql);

// Get recent activities for preview
$recent_audit_sql = "SELECT * FROM v_audit_logs_detailed ORDER BY Timestamp DESC LIMIT 10";
$recent_audit_result = $conn->query($recent_audit_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Active Residents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <header class="sidebar-header">
            <img src="../asset/logo.png" alt="Header Logo" class="header-logo">
            <button class="sidebar-toggle">
                <span class="material-symbols-outlined">chevron_left</span>
            </button>
        </header>

        <div class="sidebar-content">
            <ul class="menu-list">
                <li class="menu-item">
                    <a href="overview.php" class="menu-link">
                        <img src="../asset/home.png" alt="Home Icon" class="menu-icon">
                        <span class="menu-label">Overview</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="reserverequests.php" class="menu-link">
                        <img src="../asset/makeareservation.png" alt="Make a Reservation Icon" class="menu-icon">
                        <span class="menu-label">Requests</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="reservations.php" class="menu-link">
                        <img src="../asset/reservations.png" alt="Reservations Icon" class="menu-icon">
                        <span class="menu-label">Reservations</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <img src="../asset/profile.png" alt="My Account Icon" class="menu-icon">
                        <span class="menu-label">My Account</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="create-account.php" class="menu-link">
                        <img src="../asset/profile.png" alt="My Account Icon" class="menu-icon">
                        <span class="menu-label">Create Account</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <div class="main-content">
        <div class="reservation-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1>Admin Dashboard - Active Residents</h1>
                </div>
                <form action ="log-out.php" method="post">
                    <button type="submit" class="btn btn-danger">Logout</button>
                </form>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Total Active Residents: <?php echo $result->num_rows; ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-striped mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>User ID</th>
                                    <th>Email</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td><?php echo $user['Email']; ?></td>
                                        <td><?php echo $user['FirstName']; ?></td>
                                        <td><?php echo $user['LastName']; ?></td>
                                        <td>
                                            <span class="badge bg-success">
                                                <?php echo $user['Status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No active residents found in the database.</div>
            <?php endif; ?>
        </div>

        <!-- Recent Activity Preview -->
        <div class="reservation-card mt-4">
            <?php if ($recent_audit_result && $recent_audit_result->num_rows > 0): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Activity</h5>
                            <a href="audit-logs.php" class="btn btn-sm">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php 
                        while($log = $recent_audit_result->fetch_assoc()): 
                            $bgClass = 'bg-success';
                            $iconSymbol = 'check_circle';
                            $actionText = '';
                            
                            switch($log['ActionType']) {
                                case 'Approved':
                                    $bgClass = 'bg-success';
                                    $iconSymbol = 'check_circle';
                                    $actionText = 'approved';
                                    break;
                                case 'Rejected':
                                    $bgClass = 'bg-danger';
                                    $iconSymbol = 'cancel';
                                    $actionText = 'rejected';
                                    break;
                                case 'Event_Created':
                                    $bgClass = 'bg-primary';
                                    $iconSymbol = 'add_circle';
                                    $actionText = 'created';
                                    break;
                                case 'Updated':
                                    $bgClass = 'bg-warning';
                                    $iconSymbol = 'edit';
                                    $actionText = 'updated';
                                    break;
                            }
                            
                            $adminName = $log['AdminName'] ?? 'System';
                            $residentName = $log['ResidentName'] ?? 'Unknown';
                            $facilityName = $log['FacilityName'] ?? 'Unknown Facility';
                            $eventDate = $log['EventStartDate'] ? date('F d, Y', strtotime($log['EventStartDate'])) : 'N/A';
                            $timeRange = '';
                            if ($log['TimeStart'] && $log['TimeEnd']) {
                                $timeRange = date('g:i A', strtotime($log['TimeStart'])) . ' - ' . date('g:i A', strtotime($log['TimeEnd']));
                            }
                            $timestamp = date('F d, Y \a\t g:i A', strtotime($log['Timestamp']));
                        ?>
                            <div class="d-flex align-items-start p-3 mb-3 rounded <?php echo $bgClass; ?> bg-opacity-10 border border-<?php echo str_replace('bg-', '', $bgClass); ?>">
                                <div class="me-3 mt-1">
                                    <span class="material-symbols-outlined text-white bg-<?php echo str_replace('bg-', '', $bgClass); ?> rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-size: 20px;">
                                        <?php echo $iconSymbol; ?>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold mb-1">
                                        <?php echo htmlspecialchars($adminName); ?> <?php echo $actionText; ?> a reservation request
                                    </div>
                                    <div class="small text-muted mb-2">
                                        <?php echo htmlspecialchars($timestamp); ?>
                                    </div>
                                    <div class="small">
                                        <div class="mb-1">
                                            <strong>Resident:</strong> <?php echo htmlspecialchars($residentName); ?>
                                        </div>
                                        <div class="mb-1">
                                            <strong>Facility:</strong> <?php echo htmlspecialchars($facilityName); ?>
                                        </div>
                                        <div class="mb-1">
                                            <strong>Event Date:</strong> <?php echo htmlspecialchars($eventDate); ?>
                                        </div>
                                        <?php if ($timeRange): ?>
                                        <div class="mb-1">
                                            <strong>Time:</strong> <?php echo htmlspecialchars($timeRange); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-0">No recent activity found.</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
<script src="../resident-side/javascript/sidebar.js"></script>
</body>
</html>
<?php
$conn->close();
?>