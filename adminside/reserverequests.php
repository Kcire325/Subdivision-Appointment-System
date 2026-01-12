<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "facilityreservationsystem");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_users'])) {
    $updates = 0;
    
    foreach ($_POST['user_assignments'] as $reservation_id => $user_id) {
        $reservation_id = intval($reservation_id);
        $user_id = intval($user_id);
        
        $update_sql = "UPDATE reservations SET user_id = $user_id WHERE id = $reservation_id";
        if ($conn->query($update_sql)) {
            $updates++;
        }
    }
    
    $message = "✓ Successfully updated $updates reservation(s) with correct user information!";
}

// Get all pending reservations
$reservations_sql = "SELECT 
                        r.id,
                        r.user_id,
                        r.facility_name,
                        r.phone,
                        r.event_start_date,
                        r.event_end_date,
                        r.time_start,
                        r.time_end,
                        u.FirstName,
                        u.LastName
                    FROM reservations r
                    LEFT JOIN users u ON r.user_id = u.UserID
                    WHERE LOWER(r.status) = 'pending'
                    ORDER BY r.id";
$reservations = $conn->query($reservations_sql);

// Get only users with Resident role
$users_sql = "SELECT UserID, GeneratedID, FirstName, LastName FROM users WHERE Role = 'Resident' ORDER BY FirstName";
$users = $conn->query($users_sql);
$users_array = [];
while ($user = $users->fetch_assoc()) {
    $users_array[] = $user;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Correct Users to Reservations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">👥 Assign Correct Users to Reservations</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <h5><?php echo $message; ?></h5>
            <a href="reserverequests.php" class="btn btn-primary mt-2">✓ View Updated Reservations</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="alert alert-info">
        <strong>Instructions:</strong> For each reservation below, select the correct user who made the reservation based on the phone number or other details.
    </div>

    <form method="POST">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Reservation ID</th>
                        <th>Facility</th>
                        <th>Phone</th>
                        <th>Event Date</th>
                        <th>Time</th>
                        <th>Current User</th>
                        <th>Select Correct User</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($res = $reservations->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $res['id']; ?></td>
                        <td><?php echo htmlspecialchars($res['facility_name']); ?></td>
                        <td><strong><?php echo htmlspecialchars($res['phone']); ?></strong></td>
                        <td>
                            <?php
                                echo date('M d, Y', strtotime($res['event_start_date']));
                                if ($res['event_start_date'] != $res['event_end_date']) {
                                    echo ' - ' . date('M d, Y', strtotime($res['event_end_date']));
                                }
                            ?>
                        </td>
                        <td>
                            <?php
                                echo date('g:i A', strtotime($res['time_start'])) .
                                     ' - ' .
                                     date('g:i A', strtotime($res['time_end']));
                            ?>
                        </td>
                        <td>
                            <span class="badge bg-secondary">
                                <?php echo htmlspecialchars($res['FirstName'] . ' ' . $res['LastName']); ?>
                            </span>
                        </td>
                        <td>
                            <select name="user_assignments[<?php echo $res['id']; ?>]" 
                                    class="form-select" required>
                                <?php foreach ($users_array as $user): ?>
                                <option value="<?php echo $user['UserID']; ?>"
                                        <?php echo ($user['UserID'] == $res['user_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']); ?>
                                    (ID: <?php echo $user['GeneratedID']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5>Available Residents</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($users_array as $user): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']); ?></h6>
                                <p class="card-text mb-0">
                                    <small>
                                        <strong>User ID:</strong> <?php echo $user['UserID']; ?><br>
                                        <strong>Generated ID:</strong> <?php echo $user['GeneratedID']; ?>
                                    </small>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" name="update_users" class="btn btn-success btn-lg">
                💾 Update All Reservations
            </button>
            <a href="reserverequests.php" class="btn btn-secondary btn-lg">Cancel</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>