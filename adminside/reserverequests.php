<?php
session_start();

// Check if user is logged in and is an Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
    header("Location: ../login/login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "facilityreservationsystem");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

// Handle approve / reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['approve'])) {
        $id = intval($_POST['approve']);
        $conn->query("UPDATE reservations SET status = 'approved', updated_at = NOW() WHERE id = $id");
        $message = "Reservation #$id approved.";
    }

    if (isset($_POST['reject'])) {
        $id = intval($_POST['reject']);
        $conn->query("UPDATE reservations SET status = 'rejected', updated_at = NOW() WHERE id = $id");
        $message = "Reservation #$id rejected.";
    }
}

// Get ONLY pending reservations
$reservations_sql = "
    SELECT 
        r.id,
        r.user_id,
        r.facility_name,
        r.phone,
        r.event_start_date,
        r.event_end_date,
        r.time_start,
        r.time_end,
        r.status,
        u.FirstName,
        u.LastName
    FROM reservations r
    LEFT JOIN users u ON r.user_id = u.user_id
    WHERE r.status = 'pending'
    ORDER BY r.id DESC
";

$reservations = $conn->query($reservations_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Requests - Admin</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

    <link rel="stylesheet" href="admin.css">
</head>
<body>

<div class="app-layout">

    <!-- SIDEBAR -->
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
                        <img src="../asset/home.png" class="menu-icon">
                        <span class="menu-label">Overview</span>
                    </a>
                </li>
                <li class="menu-item active">
                    <a href="reserverequests.php" class="menu-link">
                        <img src="../asset/makeareservation.png" class="menu-icon">
                        <span class="menu-label">Requests</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="reservations.php" class="menu-link">
                        <img src="../asset/reservations.png" class="menu-icon">
                        <span class="menu-label">Reservations</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <img src="../asset/profile.png" class="menu-icon">
                        <span class="menu-label">My Account</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="createaccount.php" class="menu-link">
                        <img src="../asset/profile.png" class="menu-icon">
                        <span class="menu-label">Create Account</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <div class="reservation-card">

            <h1 class="mb-4">Pending Reservation Requests</h1>

            <?php if ($message): ?>
                <div class="alert alert-info alert-dismissible fade show">
                    <h5><?= $message ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="alert alert-warning">
                Only <strong>pending</strong> reservations are displayed here.
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Resident</th>
                            <th>Facility</th>
                            <th>Phone</th>
                            <th>Event Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php if ($reservations->num_rows == 0): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    No pending reservations found.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php while ($res = $reservations->fetch_assoc()): ?>
                        <tr>
                            <td><?= $res['id'] ?></td>

                            <td><?= htmlspecialchars($res['FirstName'] . " " . $res['LastName']) ?></td>

                            <td><?= htmlspecialchars($res['facility_name']) ?></td>

                            <td><strong><?= htmlspecialchars($res['phone']) ?></strong></td>

                            <td>
                                <?= date('M d, Y', strtotime($res['event_start_date'])) ?>
                                <?php if ($res['event_start_date'] != $res['event_end_date']): ?>
                                    - <?= date('M d, Y', strtotime($res['event_end_date'])) ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= date('g:i A', strtotime($res['time_start'])) ?>
                                -
                                <?= date('g:i A', strtotime($res['time_end'])) ?>
                            </td>

                            <td>
                                <span class="badge bg-warning text-dark">Pending</span>
                            </td>

                            <td>
                                <form method="POST" class="d-flex gap-2">

                                    <button type="submit"
                                            name="approve"
                                            value="<?= $res['id'] ?>"
                                            class="btn btn-success btn-sm">
                                            Approve
                                    </button>

                                    <button type="submit"
                                            name="reject"
                                            value="<?= $res['id'] ?>"
                                            class="btn btn-danger btn-sm">
                                            Reject
                                    </button>

                                </form>
                            </td>

                        </tr>
                        <?php endwhile; ?>

                    </tbody>
                </table>
            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="../resident-side/javascript/sidebar.js"></script>

</body>
</html>

<?php $conn->close(); ?>
