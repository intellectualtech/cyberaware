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
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --cyber-yellow: #FF8C42;
            --primary: #FF8C42;
            --primary-rgb: 255,140,66;
            --cyber-gold: #FF8C42;
            --white: #FFFFFF;
            --cyber-light: #F3F4F6;
            --dark-navy: #111827;
            --dark-slate: #374151;
            --charcoal: #6B7280;
            --shield-green: #FF8C42;
            --alert-red: #EF4444;
            --info-blue: #3B82F6;
            --platinum: #E5E4E2;
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

        .login-container {
            background: var(--white);
            padding: 50px 45px;
            border-radius: 18px;
            box-shadow: var(--shadow-lg), var(--shadow-yellow);
            max-width: 480px;
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
        }

        .alert i {
            font-size: 18px;
            flex-shrink: 0;
        }

        .alert-error {
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
            margin-bottom: 24px;
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

        input {
            width: 100%;
            padding: 14px 16px 14px 50px;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            color: var(--dark-navy);
        }

        input::placeholder {
            color: var(--charcoal);
        }

        input:focus {
            outline: none;
            border-color: var(--cyber-yellow);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb),0.15);
            background: rgba(var(--primary-rgb),0.02);
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
            margin: 0 12px;
            color: #E5E7EB;
        }

        .demo-credentials {
            background: linear-gradient(135deg, rgba(var(--primary-rgb),0.1) 0%, rgba(var(--primary-rgb),0.05) 100%);
            padding: 20px;
            border-radius: 10px;
            margin-top: 28px;
            border: 2px solid rgba(var(--primary-rgb),0.3);
        }

        .demo-credentials h3 {
            color: var(--dark-navy);
            margin-bottom: 12px;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .demo-credentials h3 i {
            font-size: 16px;
            color: var(--cyber-yellow);
        }

        .demo-credentials p {
            margin: 8px 0;
            color: var(--charcoal);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .demo-credentials p i {
            color: var(--cyber-yellow);
            font-size: 14px;
        }

        .demo-credentials strong {
            color: var(--dark-navy);
            min-width: 65px;
            display: inline-block;
        }

        @media (max-width: 576px) {
            .login-container {
                padding: 35px 24px;
            }


        /* Orange + White + Platinum Grey refresh */
        body {
            font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: radial-gradient(900px 480px at 10% -10%, #fff1e4 0%, transparent 60%),
                linear-gradient(135deg, #ffffff 0%, var(--platinum) 100%);
        }

        h1 {
            font-family: 'Space Grotesk', 'Segoe UI', sans-serif;
        }

        .login-container {
            border-left: 0;
            border-radius: 22px;
            border: 1px solid rgba(255, 140, 66, 0.18);
        }

        .logo-icon {
            background: rgba(255, 140, 66, 0.12);
            border-color: rgba(255, 140, 66, 0.4);
        }

        .logo-icon i {
            color: var(--primary);
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