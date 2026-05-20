<?php
session_start();

// Security check: If they are NOT logged in, kick them to login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// --- STAT QUERIES ---
$totalRooms = $con->query("SELECT COUNT(*) FROM room")->fetch_row()[0];
$totalBeds = $con->query("SELECT COUNT(*) FROM bed")->fetch_row()[0];
$occupiedBeds = $con->query("SELECT COUNT(*) FROM bed WHERE occupancy_status = 'occupied'")->fetch_row()[0];
$totalBoarders = $con->query("SELECT COUNT(*) FROM rental_agreement WHERE status = 'active'")->fetch_row()[0];
$recentLogs = $con->query("
    SELECT ml.description, ml.status, ml.maintenance_date, r.room_number
    FROM maintenance_log ml
    JOIN room r ON ml.room_id = r.room_id
    ORDER BY ml.maintenance_date DESC
    LIMIT 5
");
$vacancy = $totalBeds - $occupiedBeds;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | StayEase</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <div class="header-text">
            <h1>Dashboard</h1>
            <p>Welcome back, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
        </div>
        
        <a href="logout.php" class="btn-logout">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Sign Out
        </a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <p class="stat-label">Total Rooms</p>
            <h2 class="stat-value"><?= $totalRooms ?></h2>
        </div>
        <div class="stat-card">
            <p class="stat-label">Occupied Beds</p>
            <h2 class="stat-value"><?= $occupiedBeds ?> / <?= $totalBeds ?></h2>
        </div>
        <div class="stat-card">
            <p class="stat-label">Active Boarders</p>
            <h2 class="stat-value"><?= $totalBoarders ?></h2>
        </div>
        <div class="stat-card stat-card--alert">
            <p class="stat-label">Vacant Beds</p>
            <h2 class="stat-value"><?= $vacancy ?></h2>
        </div>
    </div>

    <div class="dashboard-table-section">
        <h3>Recent Maintenance Activity</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Room</th>
                    <th>Description</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recentLogs && $recentLogs->num_rows > 0): ?>
                    <?php while ($log = $recentLogs->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['maintenance_date']) ?></td>
                        <td>Room <?= htmlspecialchars($log['room_number']) ?></td>
                        <td><?= htmlspecialchars($log['description']) ?></td>
                        <td>
                            <span class="badge badge--<?= strtolower(str_replace(' ', '-', $log['status'])) ?>">
                                <?= htmlspecialchars($log['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align:center; padding: 40px; color: #888;">
                            No maintenance logs yet. Everything is running smoothly!
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>