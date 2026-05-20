<?php
session_start();


if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | StayEase</title>
    <link rel="stylesheet" href="assets/css/style.css">
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

    <main class="page-content dashboard-page">
        <section class="page-hero">
            <div>
                <p class="section-label">Welcome back</p>
                <h1>Dashboard</h1>
                <p>Welcome, <strong><?php echo $_SESSION['username']; ?></strong>. Manage tenants, beds, and rental agreements from here.</p>
            </div>
            <div class="hero-actions-dashboard">
                <a href="boarders.php" class="btn-secondary">View Boarders</a>
                <a href="boarder_register.php" class="btn-primary">Add Boarder</a>
            </div>
        </section>

        <section class="panel dashboard-panel">
            <div class="panel-heading">
                <h2>Quick Actions</h2>
            </div>
            <div class="dashboard-grid">
                <div class="card">
                    <h3>Boarder registry</h3>
                    <p>Browse boarders and rental agreements at a glance.</p>
                    <a href="boarders.php" class="link-button">Open boarders</a>
                </div>
                <div class="card">
                    <h3>Register new boarder</h3>
                    <p>Create a boarder record, assign a bed, and save the rental agreement.</p>
                    <a href="boarder_register.php" class="link-button">Add boarder</a>
                </div>
                <div class="card">
                    <h3>Manage beds</h3>
                    <p>Add vacant beds and make them available for boarder assignments.</p>
                    <a href="beds.php" class="link-button">Open beds</a>
                </div>
                <div class="card">
                    <h3>Manage rooms</h3>
                    <p>Track room capacity and bed occupancy status across your property.</p>
                    <a href="rooms.php" class="link-button">Open rooms</a>
                </div>
                <div class="card">
                    <h3>Billing dashboard</h3>
                    <p>Record payments, track utility expenses, and view billing history.</p>
                    <a href="billing.php" class="link-button">Open billing</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
