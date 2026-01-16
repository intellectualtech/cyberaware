<?php
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT id, username, password_hash, full_name, email, role, department_id, is_active 
                                    FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && verifyPassword($password, $user['password_hash'])) {
                // Reset failed login attempts
                $stmt = $conn->prepare("UPDATE users SET failed_login_attempts = 0, last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department_id'] = $user['department_id'];
                
                // Redirect based on role
                if ($user['role'] === 'admin' || $user['role'] === 'manager') {
                    header('Location: ../admin/dashboard.php');
                } else {
                    header('Location: ../trainee/dashboard.php');
                }
                exit();
            } else {
                // Increment failed login attempts
                if ($user) {
                    $stmt = $conn->prepare("UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?");
                    $stmt->execute([$user['id']]);
                }
                $error = 'Invalid username or password';
            }
        } catch(PDOException $e) {
            $error = 'Login error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CyberAware</title>
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
        }

        .login-container {
            background: var(--white);
            padding: 48px;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            max-width: 480px;
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
        }

        .alert i {
            font-size: 18px;
        }

        .alert-error {
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
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--gray-700);
            font-weight: 600;
            font-size: 14px;
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

        input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-lighter);
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
            margin: 0 8px;
            color: var(--gray-400);
        }

        .demo-credentials {
            background: var(--primary-light);
            padding: 20px;
            border-radius: 8px;
            margin-top: 24px;
            border: 1px solid var(--primary-lighter);
        }

        .demo-credentials h3 {
            color: var(--primary-dark);
            margin-bottom: 12px;
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .demo-credentials h3 i {
            font-size: 16px;
        }

        .demo-credentials p {
            margin: 8px 0;
            color: var(--gray-700);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .demo-credentials p i {
            color: var(--primary);
            font-size: 14px;
        }

        .demo-credentials strong {
            color: var(--dark);
            min-width: 65px;
            display: inline-block;
        }

        @media (max-width: 576px) {
            .login-container {
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
    <div class="login-container">
        <div class="logo-container">
            <div class="logo-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1>CyberAware</h1>
            <p class="subtitle">Security Awareness Training Platform</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" required autofocus placeholder="Enter your username">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i>
                Login
            </button>
        </form>
        
        <div class="links">
            <a href="../index.php">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
            <span class="divider">|</span>
            <a href="register.php">
                Register <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <div class="demo-credentials">
           
        </div>
    </div>
</body>
</html>