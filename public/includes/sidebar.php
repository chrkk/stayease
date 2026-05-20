<?php
// Protect every page that includes this
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>
<nav class="navbar">
    <div class="nav-logo">
        <span class="logo-mark"></span>
        STAYEASE
    </div>
    <ul class="nav-links">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="rooms.php">Rooms</a></li>
        <li><a href="boarders.php">Boarders</a></li>
        <li><a href="billing.php">Billing</a></li>
        <li><a href="maintenance.php">Maintenance</a></li>
        <li><a href="logout.php" class="nav-login">Logout</a></li>
    </ul>
</nav>