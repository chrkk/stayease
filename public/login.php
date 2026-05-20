<?php
session_start();

// If they ARE already logged in, send them straight to the dashboard
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'config.php';
$error = '';

// Process the form when the user clicks "Sign In"
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Query the database to check if the user exists
    $stmt = $con->prepare("SELECT * FROM user WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Success! Set the session and redirect
        $_SESSION['username'] = $username;
        header("Location: dashboard.php");
        exit();
    } else {
        // Fail! Show an error message
        $error = "Invalid username or password. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landlord Login | StayEase</title>
    <link rel="stylesheet" href="/stayease/public/assets/css/style.css">
    
    <style>
        /* Minimalist Black & White Split-Screen Login */
        body { 
            margin: 0; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f0f0f0; /* Light gray background */
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
        }
        
        .login-wrapper { 
            display: flex; 
            width: 100%; 
            max-width: 900px; 
            background: #ffffff; 
            border-radius: 12px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.08); 
            overflow: hidden; 
        }
        
        /* Left Side: Branding (Dark Theme) */
        .login-brand { 
            flex: 1; 
            /* Sleek dark charcoal/black gradient */
            background: linear-gradient(135deg, #111111 0%, #2b2b2b 100%); 
            color: #ffffff; 
            padding: 50px 40px; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
        }
        
        .login-brand h1 { font-size: 2.5rem; margin-bottom: 15px; letter-spacing: -1px; }
        .login-brand p { font-size: 1.1rem; line-height: 1.6; color: #cccccc; margin-bottom: 30px; }
        .login-brand .feature-list { list-style: none; padding: 0; margin: 0; }
        .login-brand .feature-list li { margin-bottom: 10px; display: flex; align-items: center; font-size: 0.95rem; color: #e0e0e0; }
        /* White checkmarks instead of green */
        .login-brand .feature-list li::before { content: '✓'; margin-right: 10px; color: #ffffff; font-weight: bold; } 
        
        /* Right Side: The Form (Light Theme) */
        .login-form-container { 
            flex: 1; 
            padding: 50px 40px; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            background: #ffffff;
        }
        
        .login-form-container h2 { font-size: 1.8rem; color: #111; margin-bottom: 5px; }
        .login-form-container p.subtitle { color: #666; margin-bottom: 30px; font-size: 0.95rem; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 0.9rem; }
        
        /* Input fields with black focus borders */
        .form-group input { width: 100%; padding: 12px 15px; border: 1.5px solid #ddd; border-radius: 6px; font-size: 1rem; box-sizing: border-box; transition: border-color 0.3s ease; }
        .form-group input:focus { border-color: #111111; outline: none; }
        
        /* Solid black button */
        .btn-login { width: 100%; padding: 14px; background: #1a1a1a; color: #fff; border: none; border-radius: 6px; font-size: 1.05rem; font-weight: bold; cursor: pointer; transition: background 0.3s ease, transform 0.1s; }
        .btn-login:hover { background: #000000; }
        .btn-login:active { transform: scale(0.98); }
        
        /* Grayscale error message */
        .error-msg { background: #f8f8f8; color: #000000; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; border: 1px solid #000000; text-align: center; font-weight: 600; }
        
        /* Black links */
        .back-link { display: inline-block; margin-top: 25px; text-decoration: none; color: #444; font-size: 0.9rem; font-weight: 600; transition: color 0.2s; }
        .back-link:hover { color: #000; text-decoration: underline; }

        /* Responsive stack for smaller screens */
        @media (max-width: 768px) {
            .login-wrapper { flex-direction: column; max-width: 450px; margin: 20px; }
            .login-brand { padding: 40px 30px; }
            .login-form-container { padding: 40px 30px; }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        
        <div class="login-brand">
            <h1>StayEase</h1>
            <p>Your complete property management system. Centralize your tenants, automate your billing, and manage maintenance with ease.</p>
            <ul class="feature-list">
                <li>Real-time room & bed monitoring</li>
                <li>Automated utility expense tracking</li>
                <li>Comprehensive maintenance logs</li>
            </ul>
        </div>
        
        <div class="login-form-container">
            <h2>Welcome Back</h2>
            <p class="subtitle">Please enter your landlord credentials to continue.</p>
            
            <?php if ($error): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="e.g. admin" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn-login">Sign In</button>
            </form>
            
            <a href="index.html" class="back-link">&larr; Back to Home</a>
        </div>

    </div>

</body>
</html>