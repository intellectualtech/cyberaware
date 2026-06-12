<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$message = '';
$message_type = '';
$step = 'request'; // request or reset

// Check if user is coming from reset link
if (isset($_GET['token'])) {
    $token = sanitize($_GET['token']);
    $pdo = getDBConnection();
    
    // Verify token exists and is not expired (valid for 24 hours)
    $stmt = $pdo->prepare("
        SELECT id, username, email FROM users 
        WHERE password_reset_token = ? 
        AND password_reset_expires > NOW()
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $message = 'Invalid or expired reset link. Please request a new one.';
        $message_type = 'danger';
        $step = 'request';
    } else {
        $step = 'reset';
    }
}

// Handle password reset request (step 1)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request') {
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $message_type = 'danger';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, username, full_name, email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $reset_token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // Store token in database
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET password_reset_token = ?, password_reset_expires = ?
                    WHERE id = ?
                ");
                $stmt->execute([$reset_token, $expires, $user['id']]);
                
                // Send reset email
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/cyberaware/pages/forgot-password.php?token=" . $reset_token;
                
                $email_subject = "CyberAware - Password Reset Request";
                $email_body = "
                    <h2>Password Reset Request</h2>
                    <p>Hi {$user['full_name']},</p>
                    <p>We received a request to reset your CyberAware password. Click the link below to proceed:</p>
                    <p><a href='{$reset_link}' style='background:#FF8C42;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;display:inline-block;'>Reset Password</a></p>
                    <p>This link will expire in 24 hours.</p>
                    <p>If you didn't request this, you can safely ignore this email.</p>
                    <p>Best regards,<br>CyberAware Team</p>
                ";
                
                // Send email
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: noreply@cyberaware.com\r\n";
                
                mail($user['email'], $email_subject, $email_body, $headers);
                
                $message = 'Password reset link sent to your email. Check your inbox (and spam folder) for instructions.';
                $message_type = 'success';
            } else {
                // Don't reveal if email exists (security best practice)
                $message = 'If an account exists with that email, you will receive password reset instructions.';
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error processing request. Please try again.';
            $message_type = 'danger';
        }
    }
}

// Handle password reset (step 2)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset') {
    $token = sanitize($_POST['token'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $message = 'Please enter and confirm your new password.';
        $message_type = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $message_type = 'danger';
    } elseif (strlen($new_password) < 8) {
        $message = 'Password must be at least 8 characters long.';
        $message_type = 'danger';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Verify token still valid
            $stmt = $pdo->prepare("
                SELECT id, username, email, full_name FROM users 
                WHERE password_reset_token = ? 
                AND password_reset_expires > NOW()
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $message = 'Invalid or expired reset link. Please request a new one.';
                $message_type = 'danger';
                $step = 'request';
            } else {
                // Update password and clear token
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL
                    WHERE id = ?
                ");
                $stmt->execute([$password_hash, $user['id']]);
                
                // Send confirmation email
                $email_subject = "CyberAware - Password Changed Successfully";
                $email_body = "
                    <h2>Password Changed Successfully</h2>
                    <p>Hi {$user['full_name']},</p>
                    <p>Your CyberAware password has been successfully reset.</p>
                    <p>You can now log in with your new password at:</p>
                    <p><a href='http://{$_SERVER['HTTP_HOST']}/cyberaware/pages/login.php' style='color:#FF8C42;'>Log In to CyberAware</a></p>
                    <p>If you didn't make this change, please contact your administrator immediately.</p>
                    <p>Best regards,<br>CyberAware Team</p>
                ";
                
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: noreply@cyberaware.com\r\n";
                
                mail($user['email'], $email_subject, $email_body, $headers);
                
                $message = 'Password reset successfully! You can now log in with your new password.';
                $message_type = 'success';
                $step = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error resetting password. Please try again.';
            $message_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --orange: #FF8C42;
            --orange-light: #FFF4EC;
            --grey: #F5F5F5;
            --grey2: #E8E8E8;
            --dark: #1A1A2E;
            --text: #333;
            --success: #10b981;
            --danger: #ef4444;
        }
        body {
            font-family: 'Manrope', sans-serif;
            background: linear-gradient(135deg, var(--orange-light) 0%, #fff 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .top-bar {
            padding: 20px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--orange);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
        }
        .logo span {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
        }
        .top-bar a.back {
            color: #888;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .top-bar a.back:hover {
            color: var(--orange);
        }
        .reset-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .reset-box {
            background: #fff;
            border-radius: 20px;
            padding: 44px 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 60px rgba(0,0,0,.08);
            border: 1px solid var(--grey2);
        }
        .reset-icon {
            width: 64px;
            height: 64px;
            background: var(--orange-light);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--orange);
            font-size: 28px;
            margin-bottom: 20px;
        }
        .reset-box h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 6px;
        }
        .reset-box .sub {
            font-size: 15px;
            color: #888;
            margin-bottom: 28px;
        }
        .message {
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .message.success {
            background: #f0fdf4;
            border: 1px solid #86efac;
            color: #065f46;
        }
        .message.danger {
            background: #fff0f0;
            border: 1px solid #ffc0c0;
            color: #cc0000;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #ccc;
            font-size: 16px;
        }
        .input-wrap input {
            width: 100%;
            padding: 13px 14px 13px 44px;
            border: 2px solid var(--grey2);
            border-radius: 10px;
            font-size: 15px;
            font-family: 'Manrope', sans-serif;
            transition: .2s;
            outline: none;
            color: var(--dark);
        }
        .input-wrap input:focus {
            border-color: var(--orange);
        }
        .input-wrap .toggle-pw {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #ccc;
            cursor: pointer;
            font-size: 16px;
            background: none;
            border: none;
            padding: 0;
        }
        .btn-reset {
            width: 100%;
            background: var(--orange);
            color: #fff;
            padding: 14px;
            border-radius: 10px;
            border: none;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s;
            font-family: 'Manrope', sans-serif;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-reset:hover {
            background: #e07030;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(255,140,66,.3);
        }
        .back-to-login {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: #888;
        }
        .back-to-login a {
            color: var(--orange);
            font-weight: 700;
            text-decoration: none;
        }
        .back-to-login a:hover {
            text-decoration: underline;
        }
        .password-requirements {
            background: var(--grey);
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #666;
        }
        .password-requirements li {
            margin-left: 20px;
            margin-bottom: 6px;
        }
        .success-message {
            text-align: center;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #f0fdf4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--success);
            font-size: 40px;
            margin: 0 auto 20px;
        }
        @media(max-width:480px) {
            .reset-box { padding: 32px 24px; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="../index.php" class="logo">
        <div class="logo-icon"><i class="fas fa-shield-alt"></i></div>
        <span>CyberAware</span>
    </a>
    <a href="../pages/login.php" class="back"><i class="fas fa-arrow-left"></i> Back to login</a>
</div>

<div class="reset-wrap">
    <div class="reset-box">
        <?php if ($step === 'success'): ?>
            <div class="success-message">
                <div class="success-icon"><i class="fas fa-check-circle"></i></div>
                <h1>Password Reset!</h1>
                <p class="sub">Your password has been successfully reset.</p>
                
                <?php if ($message): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
                <?php endif; ?>
                
                <div class="back-to-login">
                    <a href="login.php"><i class="fas fa-sign-in-alt"></i> Sign In Now</a>
                </div>
            </div>
        
        <?php elseif ($step === 'reset'): ?>
            <div class="reset-icon"><i class="fas fa-lock"></i></div>
            <h1>Create New Password</h1>
            <p class="sub">Enter your new password below</p>
            
            <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
            <?php endif; ?>
            
            <div class="password-requirements">
                <strong>Password Requirements:</strong>
                <ul>
                    <li>At least 8 characters long</li>
                    <li>Mix of uppercase and lowercase letters</li>
                    <li>Include numbers and special characters</li>
                </ul>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="reset">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="form-group">
                    <label>New Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="new_password" id="pw1" placeholder="Enter new password" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('pw1', 'pw1-icon')">
                            <i class="fas fa-eye" id="pw1-icon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" id="pw2" placeholder="Confirm new password" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('pw2', 'pw2-icon')">
                            <i class="fas fa-eye" id="pw2-icon"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn-reset">
                    <i class="fas fa-check"></i> Reset Password
                </button>
            </form>
            
            <div class="back-to-login">
                <a href="login.php"><i class="fas fa-arrow-left"></i> Back to login</a>
            </div>
        
        <?php else: ?>
            <div class="reset-icon"><i class="fas fa-envelope"></i></div>
            <h1>Forgot Password?</h1>
            <p class="sub">Enter your email to receive reset instructions</p>
            
            <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="action" value="request">
                
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Enter your email address" required autofocus>
                    </div>
                </div>
                
                <button type="submit" class="btn-reset">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>
            
            <div class="back-to-login">
                <a href="login.php"><i class="fas fa-arrow-left"></i> Back to login</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function togglePw(fieldId, iconId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(iconId);
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

</body>
</html>
