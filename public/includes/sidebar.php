<?php
// Protect every page that includes this
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>
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