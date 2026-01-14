<?php
session_start();

// Redirect if not logged in or not a resident
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Resident') {
    header("Location: ../login/login.php");
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'facilityreservationsystem';
$username = 'root';
$password = '';

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch current user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT FirstName, LastName, Email, Birthday, Block, Lot, StreetName, ProfilePictureURL
    FROM users
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Profile picture fallback
$profilePic = !empty($user['ProfilePictureURL'])
    ? '../' . $user['ProfilePictureURL']
    : '../asset/default-profile.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Account</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Material Icons -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

    <!-- Layout Styles -->
    <link rel="stylesheet" href="my-account.css">
    <link rel="stylesheet" href="navigation.css">
</head>

<body>

<div class="app-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <header class="sidebar-header">
            <img src="../asset/logo.png" class="header-logo">
            <button class="sidebar-toggle">
                <span class="material-symbols-outlined">chevron_left</span>
            </button>
        </header>

        <div class="sidebar-content">
            <ul class="menu-list">
                <li class="menu-item">
                    <a href="#" class="menu-link active">
                        <img src="../asset/home.png" class="menu-icon">
                        <span class="menu-label">Home</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="../make-reservation/make-reservation.php" class="menu-link">
                        <img src="../asset/makeareservation.png" class="menu-icon">
                        <span class="menu-label">Make a Reservation</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="../my-reservations/my-reservations.php" class="menu-link">
                        <img src="../asset/reservations.png" class="menu-icon">
                        <span class="menu-label">Reservations</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="my-account.php" class="menu-link">
                        <img src="../asset/profile.png" class="menu-icon">
                        <span class="menu-label">My Account</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="reservation-card p-4">

            <div class="page-header mb-3">My Account</div>

            <div class="row g-4">

                <!-- PROFILE PICTURE -->
                <div class="col-md-4 text-center">

                    <img id="profilePreview"
                         src="<?= htmlspecialchars($profilePic) ?>"
                         class="rounded-circle img-thumbnail mb-3"
                         style="width:180px;height:180px;object-fit:cover;">

                    <form action="update_profile_picture.php" method="POST" enctype="multipart/form-data">

                        <input type="file"
                               name="profile_pic"
                               id="profilePicInput"
                               accept="image/*"
                               hidden
                               onchange="previewProfilePic(this)">

                        <button type="button"
                                class="btn btn-primary w-100 mb-2"
                                onclick="document.getElementById('profilePicInput').click();">
                            Choose New Picture
                        </button>

                        <button type="submit"
                                class="btn btn-success w-100">
                            Save Profile Picture
                        </button>
                    </form>
                </div>

                <!-- USER INFO -->
                <div class="col-md-8">

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success">
                            <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card p-3 mb-4">
                        <h5 class="mb-3">Personal Information</h5>
                        <p><strong>Name:</strong> <?= htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($user['Email']) ?></p>
                        <p><strong>Birthday:</strong> <?= htmlspecialchars($user['Birthday']) ?></p>
                        <p><strong>Address:</strong>
                            <?= "Blk. {$user['Block']}, Lt. {$user['Lot']}, {$user['StreetName']} St." ?>
                        </p>
                    </div>

                    <div class="card p-3">
                        <h5 class="mb-3">Change Password</h5>
                        <form action="update_password.php" method="POST">
                            <input type="password" name="old_password" class="form-control mb-2" placeholder="Old Password" required>
                            <input type="password" name="new_password" class="form-control mb-2" placeholder="New Password" required>
                            <input type="password" name="confirm_password" class="form-control mb-3" placeholder="Confirm Password" required>
                            <button class="btn btn-warning w-100">Update Password</button>
                        </form>
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../resident-side/javascript/sidebar.js"></script>

<script>
function previewProfilePic(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('profilePreview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>
