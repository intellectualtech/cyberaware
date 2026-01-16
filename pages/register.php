<?php
session_start();

// Include the config file (functions only – no global $pdo yet)
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $full_name  = trim($_POST['full_name'] ?? '');
    $password   = $_POST['password'] ?? '';
    $role       = $_POST['role'] ?? 'trainee'; // default to trainee

    // Basic validation
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Get database connection
            $pdo = getDBConnection();

            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already taken. Please choose another.';
            } else {
                // Optional: check email uniqueness
                if (!empty($email)) {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        $error = 'This email is already registered.';
                    }
                }

                // Proceed if no errors
                if (!$error) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare("
                        INSERT INTO users 
                        (username, password_hash, full_name, email, role, is_active, created_at)
                        VALUES (?, ?, ?, ?, ?, 1, NOW())
                    ");
                    $stmt->execute([$username, $password_hash, $full_name, $email ?: null, $role]);

                    $success = 'Registration successful! You can now <a href="login.php" style="color:var(--primary); font-weight:600;">log in</a>.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Registration failed. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #FF8C42;
            --primary-dark: #E67A2E;
            --primary-light: #FFF4ED;
            --primary-lighter: #FFEAD9;
            --success: #00A65A;
            --danger: #DD4B39;
            --dark: #2C2C2C;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --white: #FFFFFF;
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--gray-800);
        }

        .register-container {
            background: var(--white);
            padding: 48px;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            max-width: 520px;
            width: 100%;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .logo-icon i {
            font-size: 40px;
            color: white;
        }

        h1 {
            text-align: center;
            color: var(--primary);
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: var(--gray-600);
            font-size: 15px;
            margin-bottom: 32px;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.5;
        }

        .alert i {
            font-size: 18px;
            flex-shrink: 0;
        }

        .alert-danger {
            background: #FFEBEE;
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .alert-success {
            background: #E8F5E9;
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--gray-700);
            font-weight: 600;
            font-size: 14px;
        }

        .required {
            color: var(--danger);
            margin-left: 2px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-600);
            font-size: 16px;
        }

        input,
        select {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }

        select {
            padding-left: 48px;
            cursor: pointer;
            background-color: white;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-lighter);
        }

        small {
            display: block;
            margin-top: 6px;
            color: var(--gray-600);
            font-size: 13px;
        }

        .btn {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 140, 66, 0.3);
        }

        .btn i {
            font-size: 18px;
        }

        .links {
            margin-top: 24px;
            text-align: center;
            font-size: 14px;
            line-height: 1.8;
        }

        .links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .links a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .divider {
            margin: 0 4px;
            color: var(--gray-400);
        }

        @media (max-width: 576px) {
            .register-container {
                padding: 32px 24px;
            }

            h1 {
                font-size: 28px;
            }

            .logo-icon {
                width: 70px;
                height: 70px;
            }

            .logo-icon i {
                font-size: 35px;
            }
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="logo-container">
        <div class="logo-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        <h1>CyberAware</h1>
        <p class="subtitle">Create your training account</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?= $success ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username">
                Username <span class="required">*</span>
            </label>
            <div class="input-wrapper">
                <i class="fas fa-user input-icon"></i>
                <input type="text" id="username" name="username" required autofocus 
                       placeholder="Choose a username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="full_name">Full Name</label>
            <div class="input-wrapper">
                <i class="fas fa-id-card input-icon"></i>
                <input type="text" id="full_name" name="full_name" 
                       placeholder="Enter your full name"
                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <div class="input-wrapper">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" id="email" name="email" 
                       placeholder="your.email@company.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="password">
                Password <span class="required">*</span>
            </label>
            <div class="input-wrapper">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" id="password" name="password" required
                       placeholder="Create a strong password">
            </div>
            <small><i class="fas fa-info-circle"></i> Minimum 8 characters</small>
        </div>

        <div class="form-group">
            <label for="role">Account Type</label>
            <div class="input-wrapper">
                <i class="fas fa-user-tag input-icon"></i>
                <select id="role" name="role">
                    <option value="trainee" selected>Trainee / Employee</option>
                    <option value="admin">Administrator</option>
                    <option value="manager">Manager</option>
                    <option value="compliance">Compliance Officer</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn">
            <i class="fas fa-user-plus"></i>
            Create Account
        </button>
    </form>

    <div class="links">
        Already have an account? <a href="login.php">Login here</a>
        <br>
        <a href="../index.php">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>
</div>

</body>
</html>