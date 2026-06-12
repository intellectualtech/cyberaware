<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied — CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --orange: #FF8C42;
            --dark: #1A1A2E;
            --grey: #F5F5F5;
            --grey2: #E8E8E8;
        }

        body {
            font-family: 'Manrope', sans-serif;
            background: linear-gradient(135deg, var(--orange) 0%, #ff6b35 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .error-icon {
            width: 100px;
            height: 100px;
            background: #ffe8d6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: var(--orange);
            margin: 0 auto 24px;
        }

        h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 12px;
        }

        p {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .button-group {
            display: flex;
            gap: 12px;
            flex-direction: column;
        }

        a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--orange);
            color: white;
        }

        .btn-primary:hover {
            background: #e07030;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 140, 66, 0.3);
        }

        .btn-secondary {
            background: var(--grey);
            color: var(--dark);
            border: 2px solid var(--grey2);
        }

        .btn-secondary:hover {
            background: var(--grey2);
            border-color: var(--orange);
            color: var(--orange);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-lock"></i>
        </div>
        <h1>Access Denied</h1>
        <p>You don't have permission to access this page. Only administrators can view this section.</p>
        <div class="button-group">
            <?php if (isLoggedIn()): ?>
                <a href="../trainee/dashboard.php" class="btn-primary">
                    <i class="fas fa-home"></i> Go to Dashboard
                </a>
                <a href="../logout.php" class="btn-secondary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            <?php else: ?>
                <a href="login.php" class="btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="../index.php" class="btn-secondary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
