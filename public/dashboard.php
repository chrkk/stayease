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
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard">
        <h1>Dashboard</h1>
        <p>Login successful! Welcome, <strong><?php echo $_SESSION['username']; ?></strong>.</p>
        <p>This login system is working.</p>
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>
