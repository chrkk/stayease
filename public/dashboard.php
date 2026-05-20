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
    <link rel="stylesheet" href="/stayease/public/assets/css/style.css">
    
    <style>
        /* Neo-Minimalist Grayscale Theme */
        body {
            background-color: #f4f4f4;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            color: #111;
        }

        /* --- NAVBAR FIXES --- */
        .navbar, nav {
            background-color: #000 !important; 
            border-bottom: none !important;
            /* Force the navbar to stay at the very top */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 70px;
            z-index: 9999;
            /* Align items perfectly inside the black bar */
            display: flex;
            align-items: center;
        }
        
        /* Force the links to be clean, transparent text instead of white boxes */
        .navbar a, nav a {
            background: transparent !important; 
            color: #999 !important; /* Stylish subtle gray */
            text-decoration: none !important;
            font-weight: 600 !important;
            font-size: 0.95rem;
            padding: 8px 15px !important;
            border: none !important;
            border-radius: 0 !important;
            transition: color 0.2s ease;
        }

        /* Make them bright white when you hover over them */
        .navbar a:hover, nav a:hover {
            color: #fff !important; 
        }

        /* Keep the logo bright and bold */
        .nav-logo {
            color: #fff !important; 
            font-weight: 900 !important;
            font-size: 1.5rem !important;
            letter-spacing: 1px;
            background: transparent !important;
            padding-left: 20px !important;
        }

        /* --- MAIN CONTENT OVERLAP FIX --- */
        .dashboard-main {
            /* The 120px top padding pushes the content OUT from under the black navbar */
            padding: 120px 40px 40px 40px;
            max-width: 1200px;
            margin: 0 auto;
            box-sizing: border-box;
        }

        /* Header Layout */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e5e5e5;
            padding-bottom: 20px;
            margin-bottom: 35px;
        }

        .header-text h1 {
            margin: 0 0 5px 0;
            font-size: 2.4rem;
            letter-spacing: -1px;
            font-weight: 800;
        }

        .header-text p {
            margin: 0;
            color: #666;
            font-size: 1.05rem;
        }

        /* Premium Pill-Shaped Logout Button */
        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            background: transparent;
            color: #111;
            border: 2px solid #111;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
        }

        .btn-logout:hover {
            background: #111;
            color: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }

        .btn-logout svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            stroke-width: 2.5;
            fill: none;
        }

        /* Stat Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 45px;
        }

        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            border-top: 5px solid #111;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-label {
            margin: 0 0 12px 0;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #888;
            font-weight: 700;
        }

        .stat-value {
            margin: 0;
            font-size: 2.8rem;
            font-weight: 800;
            letter-spacing: -1px;
            color: #000;
        }

        /* Alert Card */
        .stat-card--alert {
            background-color: #111;
            background-image: radial-gradient(#444 1px, transparent 1px);
            background-size: 12px 12px;
            border: 1px solid #111;
            border-top: 5px solid #fff;
        }

        .stat-card--alert .stat-label { color: #aaa; }
        .stat-card--alert .stat-value { color: #fff; }

        /* Data Table */
        .dashboard-table-section {
            background: #fff;
            padding: 35px;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
        }

        .dashboard-table-section h3 {
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.4rem;
            letter-spacing: -0.5px;
            color: #111;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            padding: 16px 12px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #666;
            border-bottom: 2px solid #111;
        }

        .data-table td {
            padding: 18px 12px;
            border-bottom: 1px solid #f0f0f0;
            color: #222;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .data-table tbody tr:hover td { background-color: #f9f9f9; }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            border-radius: 30px;
            letter-spacing: 1px;
        }

        .badge--pending { background-color: #e5e5e5; color: #444; }
        .badge--completed, .badge--resolved { background-color: #111; color: #fff; }
        .badge--in-progress { background-color: #fff; color: #111; border: 2px solid #111; }
    </style>
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