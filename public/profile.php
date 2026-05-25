<?php
session_start();
require_once 'config.php';

// Security Gatekeeper
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';
$current_username = $_SESSION['username'];

// --- PRG PATTERN: CATCH SUCCESS REDIRECT ---
if (isset($_GET['status']) && $_GET['status'] === 'updated') {
    $message = 'Profile updated successfully.';
}

// --- LOGIC FOR UPDATING PROFILE ---
if (isset($_POST['btnUpdateProfile'])) {
    $new_username = mysqli_real_escape_string($con, trim($_POST['new_username']));
    $new_password = mysqli_real_escape_string($con, $_POST['new_password']);

    if (empty($new_username)) {
        $error = 'Username cannot be blank.';
    } else {
        if (!empty($new_password)) {
            // Update both username and password
            $stmtUpdate = $con->prepare("UPDATE user SET username = ?, password = ? WHERE username = ?");
            $stmtUpdate->bind_param('sss', $new_username, $new_password, $current_username);
        } else {
            // Update username only
            $stmtUpdate = $con->prepare("UPDATE user SET username = ? WHERE username = ?");
            $stmtUpdate->bind_param('ss', $new_username, $current_username);
        }

        if ($stmtUpdate->execute()) {
            $stmtUpdate->close();
            // Keep session in sync with the new username
            $_SESSION['username'] = $new_username;
            header("Location: profile.php?status=updated");
            exit();
        } else {
            $error = 'Unable to update profile. Please try again.';
            $stmtUpdate->close();
        }
    }
}

// Always read username from session (may have just been updated above)
$current_username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | StayEase</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .btn-hero {
            display: inline-block;
            padding: 10px 22px;
            background-color: #111;
            color: #fff;
            border: 2px solid #111;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .btn-hero:hover {
            background-color: #333;
            border-color: #333;
            color: #fff;
        }
    </style>
</head>
<body>
    <header class="app-header">
        <div class="header-brand">STAYEASE</div>
        <nav class="app-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="boarders.php">Boarders</a>
            <a href="boarder_register.php">Add Boarder</a>
            <a href="beds.php">Beds</a>
            <a href="rooms.php">Rooms</a>
            <a href="billing.php">Billing</a>
            <a href="maintenance.php">Maintenance</a>
            <a href="violations.php">Violations</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="page-content">
        <section class="page-hero">
            <div>
                <p class="section-label">Account Settings</p>
                <h1>Edit Profile</h1>
                <p>Update your administrator username and password.</p>
            </div>
            <a href="dashboard.php" class="btn-hero">Back to Dashboard</a>
        </section>

        <section class="panel form-panel">
            <div class="panel-heading">
                <h2>Admin Profile: <?= htmlspecialchars($current_username) ?></h2>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="profile.php" class="form-grid">

                <div class="input-group input-full">
                    <label for="new_username">Username</label>
                    <input type="text" id="new_username" name="new_username"
                           value="<?= htmlspecialchars($current_username) ?>"
                           placeholder="Enter username" required>
                </div>

                <div class="input-group input-full" style="border-top: 1px solid #eee; padding-top: 20px; margin-top: 10px;">
                    <label for="new_password">New Password <span style="font-weight:400; color:#888;">(Leave blank to keep current password)</span></label>
                    <input type="password" id="new_password" name="new_password" placeholder="••••••••">
                </div>

                <div class="input-group input-full actions-row">
                    <button type="submit" class="btn-primary" name="btnUpdateProfile">Save Changes</button>
                </div>

            </form>
        </section>
    </main>
</body>
</html>