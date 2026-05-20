<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
require_once 'config.php';

// --- STAT QUERIES ---

// Total rooms
$totalRooms = $con->query("SELECT COUNT(*) FROM room")->fetch_row()[0];

// Total beds
$totalBeds = $con->query("SELECT COUNT(*) FROM bed")->fetch_row()[0];

// Occupied beds
$occupiedBeds = $con->query("
    SELECT COUNT(*) FROM bed WHERE occupancy_status = 'occupied'
")->fetch_row()[0];

// Active boarders (those with an active rental agreement)
$totalBoarders = $con->query("
    SELECT COUNT(*) FROM rental_agreement WHERE status = 'active'
")->fetch_row()[0];

// Recent maintenance logs — last 5 entries
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
        <h1>Dashboard</h1>
        <p>Welcome back, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    </div>

    <!-- Stat Cards -->
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

    <!-- Recent Maintenance Activity -->
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
                        <td><span class="badge badge--<?= strtolower($log['status']) ?>"><?= htmlspecialchars($log['status']) ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align:center; color: #888;">
                            No maintenance logs yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>