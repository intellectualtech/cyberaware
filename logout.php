<?php
// logout.php - Secure Logout

session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Redirect to login page (or home page)
header("Location: pages/login.php");
exit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Logging Out – CyberAware</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #1d4ed8;
            --gray-50: #f8fafc;
            --gray-800: #1e293b;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
            padding: 2rem;
        }

        .logout-message {
            background: white;
            padding: 3rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 500px;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        p {
            font-size: 1.1rem;
            color: var(--gray-800);
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-block;
            padding: 0.9rem 1.8rem;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.2s;
        }

        .btn:hover {
            background: #1e40af;
        }
    </style>
</head>
<body>

<div class="logout-message">
    <h1>You have been logged out</h1>
    <p>Thank you for using CyberAware. Your session has been securely ended.</p>
    <a href="pages/login.php" class="btn">Log In Again</a>
</div>

</body>
</html>