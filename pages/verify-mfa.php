<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user has started login process
if (!isset($_SESSION['mfa_pending_user_id'])) {
    header('Location: login.php');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['mfa_pending_user_id'];
$error = '';

// Get user info
$stmt = $pdo->prepare("SELECT id, username, full_name, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Handle MFA verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = sanitize($_POST['code'] ?? '');
    $use_backup = isset($_POST['use_backup']);
    
    if (empty($code)) {
        $error = 'Please enter a code.';
    } else {
        // Get user's MFA secret
        $stmt = $pdo->prepare("SELECT mfa_secret, mfa_backup_codes FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $mfa_data = $stmt->fetch();
        
        $code_valid = false;
        
        if ($use_backup) {
            // Check backup codes
            $backup_codes = json_decode($mfa_data['mfa_backup_codes'], true);
            if (in_array($code, $backup_codes)) {
                // Remove used backup code
                $backup_codes = array_diff($backup_codes, [$code]);
                $stmt = $pdo->prepare("UPDATE users SET mfa_backup_codes = ? WHERE id = ?");
                $stmt->execute([json_encode($backup_codes), $user_id]);
                $code_valid = true;
            }
        } else {
            // Verify TOTP code
            if (verifyTOTPCode($code, $mfa_data['mfa_secret'])) {
                $code_valid = true;
            }
        }
        
        if ($code_valid) {
            // Log successful MFA attempt
            $stmt = $pdo->prepare("
                INSERT INTO mfa_attempts (user_id, attempt_type, ip_address, user_agent)
                VALUES (?, 'success', ?, ?)
            ");
            $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
            
            // Complete login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            unset($_SESSION['mfa_pending_user_id']);
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user_id]);
            
            // Redirect based on role
            if (in_array($user['role'], ['admin', 'manager', 'superadmin'])) {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../trainee/dashboard.php');
            }
            exit;
        } else {
            // Log failed MFA attempt
            $stmt = $pdo->prepare("
                INSERT INTO mfa_attempts (user_id, attempt_type, ip_address, user_agent)
                VALUES (?, 'failure', ?, ?)
            ");
            $stmt->execute([$user_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
            
            $error = 'Invalid code. Please try again.';
        }
    }
}

// Verify TOTP code
function verifyTOTPCode($code, $secret, $window = 1) {
    $code = (int)$code;
    $secret_decoded = base32_decode($secret);
    $time = floor(time() / 30);
    
    for ($i = -$window; $i <= $window; $i++) {
        $hash = hash_hmac('sha1', pack('N*', 0) . pack('N*', $time + $i), $secret_decoded, true);
        $offset = ord($hash[19]) & 0xf;
        $totp = (((ord($hash[$offset]) & 0x7f) << 24) |
                ((ord($hash[$offset + 1]) & 0xff) << 16) |
                ((ord($hash[$offset + 2]) & 0xff) << 8) |
                (ord($hash[$offset + 3]) & 0xff)) % 1000000;
        
        if ($totp == $code) {
            return true;
        }
    }
    
    return false;
}

// Base32 decoding
function base32_decode($input) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $output = '';
    $v = 0;
    $vbits = 0;
    
    for ($i = 0; $i < strlen($input); $i++) {
        $v = ($v << 5) | strpos($alphabet, $input[$i]);
        $vbits += 5;
        if ($vbits >= 8) {
            $vbits -= 8;
            $output .= chr(($v >> $vbits) & 255);
        }
    }
    
    return $output;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify MFA — CyberAware</title>
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
        .verify-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .verify-box {
            background: #fff;
            border-radius: 20px;
            padding: 44px 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 60px rgba(0,0,0,.08);
            border: 1px solid var(--grey2);
        }
        .verify-icon {
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
        .verify-box h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 6px;
        }
        .verify-box .sub {
            font-size: 15px;
            color: #888;
            margin-bottom: 28px;
        }
        .error {
            background: #fff0f0;
            border: 1px solid #ffc0c0;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #cc0000;
            display: flex;
            align-items: center;
            gap: 10px;
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
        .input-wrap input {
            width: 100%;
            padding: 13px 14px;
            border: 2px solid var(--grey2);
            border-radius: 10px;
            font-size: 24px;
            font-family: monospace;
            font-weight: 700;
            letter-spacing: 8px;
            text-align: center;
            transition: .2s;
            outline: none;
            color: var(--dark);
        }
        .input-wrap input:focus {
            border-color: var(--orange);
        }
        .btn-verify {
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
        .btn-verify:hover {
            background: #e07030;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(255,140,66,.3);
        }
        .backup-toggle {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
        }
        .backup-toggle a {
            color: var(--orange);
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }
        .backup-toggle a:hover {
            text-decoration: underline;
        }
        .backup-form {
            display: none;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--grey2);
        }
        .backup-form.show {
            display: block;
        }
        @media(max-width:480px) {
            .verify-box { padding: 32px 24px; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="../index.php" class="logo">
        <div class="logo-icon"><i class="fas fa-shield-alt"></i></div>
        <span>CyberAware</span>
    </a>
</div>

<div class="verify-wrap">
    <div class="verify-box">
        <div class="verify-icon"><i class="fas fa-lock"></i></div>
        <h1>Verify Your Identity</h1>
        <p class="sub">Enter the 6-digit code from your authenticator app</p>

        <?php if ($error): ?>
        <div class="error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" id="mfa-form">
            <div class="form-group">
                <label>Authentication Code</label>
                <div class="input-wrap">
                    <input type="text" name="code" placeholder="000000" maxlength="6" inputmode="numeric" required autofocus>
                </div>
            </div>
            <button type="submit" class="btn-verify">
                <i class="fas fa-check"></i> Verify
            </button>
        </form>

        <div class="backup-toggle">
            <a onclick="toggleBackupForm()">Don't have your phone? Use a backup code</a>
        </div>

        <div class="backup-form" id="backup-form">
            <form method="POST">
                <div class="form-group">
                    <label>Backup Code</label>
                    <div class="input-wrap">
                        <input type="text" name="code" placeholder="XXXX-XXXX" required>
                    </div>
                </div>
                <input type="hidden" name="use_backup" value="1">
                <button type="submit" class="btn-verify">
                    <i class="fas fa-check"></i> Verify Backup Code
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleBackupForm() {
    document.getElementById('backup-form').classList.toggle('show');
    document.getElementById('mfa-form').style.display = 
        document.getElementById('backup-form').classList.contains('show') ? 'none' : 'block';
}
</script>

</body>
</html>
