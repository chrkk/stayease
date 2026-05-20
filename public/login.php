<?php
session_start();
require_once 'config.php';

$error = "";

if (isset($_POST['btnLogin'])) {
    $uname = mysqli_real_escape_string($con, $_POST['txtUsername']);
    $pwd = mysqli_real_escape_string($con, $_POST['txtPassword']);

    $sql = "SELECT * FROM user WHERE username=? AND password=?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ss", $uname, $pwd);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $_SESSION['username'] = $uname; 
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "<span class='error-msg'>Invalid username or password</span>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | StayEase</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">

    <div class="login-left">
        <a href="index.html" class="login-logo">
            <span class="logo-mark"></span>
            STAYEASE
        </a>
        <div class="login-left-body">
            <p class="login-left-label">Property Management System</p>
            <h2 class="login-left-title">Your boarding house,<br>fully under control.</h2>
            <ul class="login-feature-list">
                <li>
                    <span class="lf-dot"></span>
                    Centralized tenant &amp; room records
                </li>
                <li>
                    <span class="lf-dot"></span>
                    Automated billing &amp; utility tracking
                </li>
                <li>
                    <span class="lf-dot"></span>
                    Real-time occupancy monitoring
                </li>
                <li>
                    <span class="lf-dot"></span>
                    Maintenance &amp; violation logs
                </li>
            </ul>
        </div>
        <p class="login-left-footer">Group 5 &mdash; StayEase &copy; 2025</p>
    </div>

    <div class="login-right">
        <div class="login-card">
            <p class="login-eyebrow">Landlord Portal</p>
            <h2 class="login-title">Welcome back</h2>
            <p class="login-sub">Sign in to manage your property</p>

            <form method="post" action="login.php">
                <div class="input-group">
                    <label for="txtUsername">Username</label>
                    <input type="text" id="txtUsername" placeholder="Enter your username" required name="txtUsername">
                </div>

                <div class="input-group">
                    <label for="txtPassword">Password</label>
                    <input type="password" id="txtPassword" placeholder="Enter your password" required name="txtPassword">
                </div>

                <?php echo $error; ?>

                <button type="submit" class="login-btn" name="btnLogin">Sign in to dashboard</button>
            </form>

            <p class="login-footer">
                <a href="index.html">&larr; Back to home</a>
            </p>
        </div>
    </div>

</body>
</html>