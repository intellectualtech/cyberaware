<?php
require_once '../config/database.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT id, username, password_hash, full_name, role, is_active FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user && verifyPassword($password, $user['password_hash'])) {
                if (!$user['is_active']) {
                    $error = 'Your account has been suspended. Contact your administrator.';
                } else {
                    $conn->prepare("UPDATE users SET failed_login_attempts=0, last_login=NOW() WHERE id=?")->execute([$user['id']]);
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role']      = $user['role'];
                    if ($user['role'] === 'superadmin') {
                        header('Location: ../superadmin/dashboard.php');
                    } elseif ($user['role'] === 'manager') {
                        header('Location: ../manager/dashboard.php');
                    } elseif ($user['role'] === 'admin') {
                        header('Location: ../admin/dashboard.php');
                    } else {
                        header('Location: ../trainee/dashboard.php');
                    }
                    exit();
                }
            } else {
                if ($user) $conn->prepare("UPDATE users SET failed_login_attempts=failed_login_attempts+1 WHERE id=?")->execute([$user['id']]);
                $error = 'Invalid username or password.';
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
<title>Login — CyberAware</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--orange:#FF8C42;--orange-light:#FFF4EC;--grey:#F5F5F5;--grey2:#E8E8E8;--dark:#1A1A2E;--text:#333;}
body{font-family:'Manrope',sans-serif;background:linear-gradient(135deg,var(--orange-light) 0%,#fff 100%);min-height:100vh;display:flex;flex-direction:column;}
.top-bar{padding:20px 5%;display:flex;align-items:center;justify-content:space-between;}
.logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.logo-icon{width:40px;height:40px;background:var(--orange);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;}
.logo span{font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:700;color:var(--dark);}
.top-bar a.back{color:#888;text-decoration:none;font-size:14px;font-weight:600;display:flex;align-items:center;gap:6px;}
.top-bar a.back:hover{color:var(--orange);}
.login-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 20px;}
.login-box{background:#fff;border-radius:20px;padding:44px 40px;width:100%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,.08);border:1px solid var(--grey2);}
.login-icon{width:64px;height:64px;background:var(--orange-light);border-radius:16px;display:flex;align-items:center;justify-content:center;color:var(--orange);font-size:28px;margin-bottom:20px;}
.login-box h1{font-family:'Space Grotesk',sans-serif;font-size:28px;font-weight:700;color:var(--dark);margin-bottom:6px;}
.login-box .sub{font-size:15px;color:#888;margin-bottom:28px;}
.subscribers-note{background:var(--orange-light);border:1px solid #ffd4b3;border-radius:10px;padding:12px 16px;margin-bottom:24px;font-size:13px;color:#b85a00;display:flex;align-items:center;gap:10px;}
.error{background:#fff0f0;border:1px solid #ffc0c0;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:14px;color:#cc0000;display:flex;align-items:center;gap:10px;}
.form-group{margin-bottom:20px;}
.form-group label{display:block;font-size:13px;font-weight:700;color:var(--dark);margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em;}
.input-wrap{position:relative;}
.input-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#ccc;font-size:16px;}
.input-wrap input{width:100%;padding:13px 14px 13px 44px;border:2px solid var(--grey2);border-radius:10px;font-size:15px;font-family:'Manrope',sans-serif;transition:.2s;outline:none;color:var(--dark);}
.input-wrap input:focus{border-color:var(--orange);}
.input-wrap .toggle-pw{position:absolute;right:14px;top:50%;transform:translateY(-50%);color:#ccc;cursor:pointer;font-size:16px;background:none;border:none;padding:0;}
.forgot{text-align:right;margin-top:8px;}
.forgot a{font-size:13px;color:var(--orange);text-decoration:none;font-weight:600;}
.forgot a:hover{text-decoration:underline;}
.btn-login{width:100%;background:var(--orange);color:#fff;padding:14px;border-radius:10px;border:none;font-size:16px;font-weight:700;cursor:pointer;transition:.2s;font-family:'Manrope',sans-serif;margin-top:8px;display:flex;align-items:center;justify-content:center;gap:10px;}
.btn-login:hover{background:#e07030;transform:translateY(-1px);box-shadow:0 6px 20px rgba(255,140,66,.3);}
.divider{text-align:center;margin:24px 0;color:#ccc;font-size:13px;position:relative;}
.divider::before,.divider::after{content:'';position:absolute;top:50%;width:42%;height:1px;background:var(--grey2);}
.divider::before{left:0;}
.divider::after{right:0;}
.register-link{text-align:center;font-size:14px;color:#888;}
.register-link a{color:var(--orange);font-weight:700;text-decoration:none;}
.register-link a:hover{text-decoration:underline;}
.demo-note{margin-top:28px;background:var(--grey);border-radius:10px;padding:14px 16px;font-size:13px;color:#666;text-align:center;}
.demo-note strong{color:var(--dark);}
@media(max-width:480px){.login-box{padding:32px 24px;}}
</style>
</head>
<body>

<div class="top-bar">
    <a href="../index.php" class="logo">
        <div class="logo-icon"><i class="fas fa-shield-alt"></i></div>
        <span>CyberAware</span>
    </a>
    <a href="../index.php" class="back"><i class="fas fa-arrow-left"></i> Back to home</a>
</div>

<div class="login-wrap">
    <div class="login-box">
        <div class="login-icon"><i class="fas fa-shield-alt"></i></div>
        <h1>Welcome back</h1>
        <p class="sub">Sign in to your CyberAware account</p>

        <div class="subscribers-note">
            <i class="fas fa-info-circle"></i>
            <span>Access is for <strong>subscribers and super admins only</strong>. Email <strong>info@intellectualtechnology.com.na</strong> to get your organisation onboarded.</span>
        </div>

        <?php if ($error): ?>
        <div class="error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Enter your username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="pw" placeholder="Enter your password" required>
                    <button type="button" class="toggle-pw" onclick="togglePw()"><i class="fas fa-eye" id="pw-icon"></i></button>
                </div>
                <div class="forgot"><a href="forgot-password.php">Forgot password?</a></div>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>

        <div class="demo-note">
            <strong>Don't have an account?</strong><br>
            Your organisation needs to subscribe first.<br>
            <a href="../index.php#demo" style="color:var(--orange);font-weight:700;">Request a Demo →</a>
        </div>
    </div>
</div>

<script>
function togglePw() {
    const pw = document.getElementById('pw');
    const icon = document.getElementById('pw-icon');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pw.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
</body>
</html>