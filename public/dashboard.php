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
    LEFT JOIN room r ON ml.room_id = r.room_id
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
            <a href="dashboard.php" class="active">Dashboard</a>
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
                <p class="section-label">Overview</p>
                <h1>Dashboard</h1>
                <p>Welcome back, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
            </div>
            <a href="profile.php" class="btn-hero">Edit Profile</a>
        </section>

        <section class="summary-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="summary-card">
                <p class="summary-title">Total Rooms</p>
                <p class="summary-value"><?= $totalRooms ?></p>
            </div>
            <div class="summary-card">
                <p class="summary-title">Occupied Beds</p>
                <p class="summary-value"><?= $occupiedBeds ?> / <?= $totalBeds ?></p>
            </div>
            <div class="summary-card">
                <p class="summary-title">Active Boarders</p>
                <p class="summary-value"><?= $totalBoarders ?></p>
            </div>
            <div class="summary-card" style="background: #111; border-color: #111;">
                <p class="summary-title" style="color: #aaa;">Vacant Beds</p>
                <p class="summary-value" style="color: #fff;"><?= $vacancy ?></p>
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading">
                <h2>Recent Maintenance Activity</h2>
            </div>
            <div class="table-wrap">
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
                                <td><?= $log['room_number'] ? 'Room ' . htmlspecialchars($log['room_number']) : 'Unassigned' ?></td>
                                <td><?= htmlspecialchars($log['description']) ?></td>
                                <td>
                                    <?php 
                                        $statusClass = 'badge--pending';
                                        if ($log['status'] === 'Completed') $statusClass = 'badge--resolved';
                                        if ($log['status'] === 'In Progress') $statusClass = 'badge--ongoing';
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= htmlspecialchars($log['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-state">No maintenance logs yet. Everything is running smoothly!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

</body>
</html>