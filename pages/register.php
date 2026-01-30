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
            --cyber-yellow: #FF8C42;
            --primary: #FF8C42;
            --cyber-gold: #FF8C42;
            --white: #FFFFFF;
            --cyber-light: #F3F4F6;
            --dark-navy: #111827;
            --dark-slate: #374151;
            --charcoal: #6B7280;
            --shield-green: #10B981;
            --alert-red: #EF4444;
            --info-blue: #3B82F6;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
            --shadow-md: 0 6px 18px rgba(0,0,0,0.09);
            --shadow-lg: 0 12px 40px rgba(0,0,0,0.12);
            --shadow-accent: 0 6px 24px rgba(var(--primary-rgb),0.12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--dark-slate) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--dark-navy);
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(var(--primary-rgb),0.15) 0%, transparent 70%);
            border-radius: 50%;
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -10%;
            left: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(var(--primary-rgb),0.1) 0%, transparent 70%);
            border-radius: 50%;
            z-index: 0;
        }

        .register-container {
            background: var(--white);
            padding: 50px 45px;
            border-radius: 18px;
            box-shadow: var(--shadow-lg), var(--shadow-yellow);
            max-width: 520px;
            width: 100%;
            position: relative;
            z-index: 1;
            border-left: 5px solid var(--cyber-yellow);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--dark-slate) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            border: 3px solid var(--cyber-yellow);
            box-shadow: 0 8px 25px rgba(var(--primary-rgb),0.2);
        }

        .logo-icon i {
            font-size: 48px;
            color: var(--cyber-yellow);
        }

        h1 {
            text-align: center;
            color: var(--dark-navy);
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: var(--charcoal);
            font-size: 16px;
            margin-bottom: 35px;
            font-weight: 500;
        }

        .alert {
            padding: 15px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.5;
        }

        .alert i {
            font-size: 18px;
            flex-shrink: 0;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--alert-red);
            border-left: 4px solid var(--alert-red);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--shield-green);
            border-left: 4px solid var(--shield-green);
        }

        .form-group {
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            color: var(--dark-navy);
            font-weight: 700;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .required {
            color: var(--alert-red);
            margin-left: 4px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--cyber-yellow);
            font-size: 18px;
        }

        input,
        select {
            width: 100%;
            padding: 14px 16px 14px 50px;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            color: var(--dark-navy);
        }

        select {
            padding-left: 50px;
            cursor: pointer;
            background-color: var(--white);
        }

        input::placeholder,
        select::placeholder {
            color: var(--charcoal);
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--cyber-yellow);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb),0.15);
            background: rgba(var(--primary-rgb),0.02);
        }

        small {
            display: block;
            margin-top: 8px;
            color: var(--charcoal);
            font-size: 13px;
            font-weight: 500;
        }

        small i {
            color: var(--cyber-yellow);
            margin-right: 4px;
        }

        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--dark-slate) 100%);
            color: var(--cyber-yellow);
            border: 2px solid var(--cyber-yellow);
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn:hover {
            background: var(--cyber-yellow);
            color: var(--dark-navy);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(var(--primary-rgb),0.3);
        }

        .btn i {
            font-size: 18px;
        }

        .links {
            margin-top: 28px;
            text-align: center;
            font-size: 14px;
            line-height: 1.8;
            color: var(--dark-navy);
        }

        .links a {
            color: var(--dark-navy);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .links a:hover {
            color: var(--cyber-yellow);
        }

        .divider {
            margin: 0 4px;
            color: #E5E7EB;
        }

        @media (max-width: 576px) {
            .register-container {
                padding: 35px 24px;
            }

            h1 {
                font-size: 30px;
            }

            .logo-icon {
                width: 80px;
                height: 80px;
            }

            .logo-icon i {
                font-size: 40px;
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