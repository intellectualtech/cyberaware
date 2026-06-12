<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Require login
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare("SELECT mfa_enabled, mfa_secret FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$message = '';
$message_type = '';
$step = 'intro'; // intro, setup, verify, success, manage

// If MFA already enabled, show manage page
if ($user['mfa_enabled']) {
    $step = 'manage';
}

// Handle setup initiation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'start_setup' && !$user['mfa_enabled']) {
        // Generate new secret
        $secret = generateTOTPSecret();
        
        // Store temporarily (not yet enabled)
        $stmt = $pdo->prepare("UPDATE users SET mfa_secret = ? WHERE id = ?");
        $stmt->execute([$secret, $user_id]);
        
        $step = 'setup';
        $user['mfa_secret'] = $secret;
    }
    
    elseif ($action === 'verify_setup') {
        $code = sanitize($_POST['code'] ?? '');
        $secret = sanitize($_POST['secret'] ?? '');
        
        if (empty($code)) {
            $message = 'Please enter the 6-digit code from your authenticator app.';
            $message_type = 'danger';
            $step = 'setup';
        } elseif (!verifyTOTPCode($code, $secret)) {
            $message = 'Invalid code. Please check your authenticator app and try again.';
            $message_type = 'danger';
            $step = 'setup';
        } else {
            // Generate backup codes
            $backup_codes = generateBackupCodes();
            $backup_codes_json = json_encode($backup_codes);
            
            // Enable MFA
            $stmt = $pdo->prepare("
                UPDATE users 
                SET mfa_enabled = 1, mfa_backup_codes = ?, mfa_enabled_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$backup_codes_json, $user_id]);
            
            $step = 'success';
            $message = 'Multi-Factor Authentication has been enabled successfully!';
            $message_type = 'success';
        }
    }
    
    elseif ($action === 'disable_mfa') {
        $password = $_POST['password'] ?? '';
        
        // Verify password
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();
        
        if (!verifyPassword($password, $user_data['password_hash'])) {
            $message = 'Invalid password. MFA was not disabled.';
            $message_type = 'danger';
            $step = 'manage';
        } else {
            // Disable MFA
            $stmt = $pdo->prepare("
                UPDATE users 
                SET mfa_enabled = 0, mfa_secret = NULL, mfa_backup_codes = NULL
                WHERE id = ?
            ");
            $stmt->execute([$user_id]);
            
            $message = 'Multi-Factor Authentication has been disabled.';
            $message_type = 'success';
            $step = 'intro';
            $user['mfa_enabled'] = 0;
        }
    }
}

// Generate QR code URL for authenticator app
function getQRCodeURL($secret, $email) {
    $issuer = 'CyberAware';
    $label = urlencode("$issuer ($email)");
    $secret_encoded = urlencode($secret);
    return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=otpauth://totp/$label?secret=$secret_encoded&issuer=$issuer";
}

// Generate TOTP secret (base32 encoded)
function generateTOTPSecret() {
    $bytes = random_bytes(20);
    return base32_encode($bytes);
}

// Base32 encoding
function base32_encode($input) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $output = '';
    $v = 0;
    $vbits = 0;
    
    for ($i = 0; $i < strlen($input); $i++) {
        $v = ($v << 8) | ord($input[$i]);
        $vbits += 8;
        while ($vbits >= 5) {
            $vbits -= 5;
            $output .= $alphabet[($v >> $vbits) & 31];
        }
    }
    
    if ($vbits > 0) {
        $output .= $alphabet[($v << (5 - $vbits)) & 31];
    }
    
    return $output;
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

// Generate backup codes
function generateBackupCodes($count = 10) {
    $codes = [];
    for ($i = 0; $i < $count; $i++) {
        $code = strtoupper(bin2hex(random_bytes(4)));
        $codes[] = substr($code, 0, 4) . '-' . substr($code, 4, 4);
    }
    return $codes;
}

// Get user email
$stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_info = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multi-Factor Authentication — CyberAware</title>
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
            --warning: #f59e0b;
        }
        body {
            font-family: 'Manrope', sans-serif;
            background: linear-gradient(135deg, var(--orange-light) 0%, #fff 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .header h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
        }
        .header p {
            font-size: 16px;
            color: #888;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,.08);
            border: 1px solid var(--grey2);
            margin-bottom: 20px;
        }
        .message {
            border-radius: 10px;
            padding: 14px 16px;
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
        .message.warning {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            color: #92400e;
        }
        .step-icon {
            width: 80px;
            height: 80px;
            background: var(--orange-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--orange);
            font-size: 36px;
            margin: 0 auto 20px;
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
            padding: 12px 14px;
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
        .code-input {
            font-size: 24px;
            letter-spacing: 8px;
            text-align: center;
            font-family: monospace;
            font-weight: 700;
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        .qr-code img {
            max-width: 300px;
            border-radius: 10px;
            border: 2px solid var(--grey2);
        }
        .secret-display {
            background: var(--grey);
            border-radius: 10px;
            padding: 14px 16px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 14px;
            word-break: break-all;
            color: var(--dark);
        }
        .backup-codes {
            background: var(--grey);
            border-radius: 10px;
            padding: 14px 16px;
            margin: 20px 0;
        }
        .backup-codes h4 {
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .backup-codes ul {
            list-style: none;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .backup-codes li {
            font-family: monospace;
            font-size: 13px;
            color: #666;
            padding: 6px 8px;
            background: #fff;
            border-radius: 6px;
            border: 1px solid var(--grey2);
        }
        .btn {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: none;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s;
            font-family: 'Manrope', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-primary {
            background: var(--orange);
            color: #fff;
            margin-bottom: 10px;
        }
        .btn-primary:hover {
            background: #e07030;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(255,140,66,.3);
        }
        .btn-secondary {
            background: var(--grey);
            color: var(--dark);
        }
        .btn-secondary:hover {
            background: var(--grey2);
        }
        .btn-danger {
            background: var(--danger);
            color: #fff;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
        .info-box {
            background: var(--orange-light);
            border: 1px solid #ffd4b3;
            border-radius: 10px;
            padding: 14px 16px;
            margin: 20px 0;
            font-size: 14px;
            color: #b85a00;
        }
        .info-box strong {
            display: block;
            margin-bottom: 8px;
        }
        .info-box ul {
            margin-left: 20px;
        }
        .info-box li {
            margin-bottom: 6px;
        }
        .success-icon {
            width: 100px;
            height: 100px;
            background: #f0fdf4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--success);
            font-size: 50px;
            margin: 0 auto 20px;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: var(--orange);
            text-decoration: none;
            font-weight: 600;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        @media(max-width:480px) {
            .card { padding: 24px; }
            .header h1 { font-size: 24px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1><i class="fas fa-shield-alt"></i> Multi-Factor Authentication</h1>
        <p>Secure your CyberAware account with an extra layer of protection</p>
    </div>

    <?php if ($step === 'intro'): ?>
        <div class="card">
            <div class="step-icon"><i class="fas fa-lock"></i></div>
            <h2 style="text-align:center;margin-bottom:20px;color:var(--dark);">Protect Your Account</h2>
            
            <div class="info-box">
                <strong>What is Multi-Factor Authentication?</strong>
                <p>MFA adds a second verification step when you log in. Even if someone has your password, they can't access your account without your authenticator app.</p>
            </div>
            
            <div style="background:var(--grey);border-radius:10px;padding:20px;margin:20px 0;">
                <h3 style="font-size:16px;font-weight:700;color:var(--dark);margin-bottom:14px;">How it works:</h3>
                <ol style="margin-left:20px;color:#666;line-height:1.8;">
                    <li>Download an authenticator app (Google Authenticator, Microsoft Authenticator, Authy)</li>
                    <li>Scan the QR code we provide</li>
                    <li>Your app generates a 6-digit code every 30 seconds</li>
                    <li>When you log in, enter your password + the 6-digit code</li>
                </ol>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="start_setup">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i> Enable MFA
                </button>
            </form>
            
            <div class="back-link">
                <a href="javascript:history.back()"><i class="fas fa-arrow-left"></i> Go Back</a>
            </div>
        </div>

    <?php elseif ($step === 'setup'): ?>
        <div class="card">
            <div class="step-icon"><i class="fas fa-qrcode"></i></div>
            <h2 style="text-align:center;margin-bottom:20px;color:var(--dark);">Step 1: Scan QR Code</h2>
            
            <div class="info-box">
                <strong>Download an Authenticator App</strong>
                <ul>
                    <li><strong>Google Authenticator</strong> - Free, iOS & Android</li>
                    <li><strong>Microsoft Authenticator</strong> - Free, iOS & Android</li>
                    <li><strong>Authy</strong> - Free, iOS & Android</li>
                </ul>
            </div>
            
            <p style="text-align:center;color:#666;margin-bottom:20px;">Scan this QR code with your authenticator app:</p>
            
            <div class="qr-code">
                <img src="<?= getQRCodeURL($user['mfa_secret'], $user_info['email']) ?>" alt="QR Code">
            </div>
            
            <p style="text-align:center;color:#666;margin-bottom:10px;font-size:13px;">Can't scan? Enter this code manually:</p>
            <div class="secret-display"><?= htmlspecialchars($user['mfa_secret']) ?></div>
            
            <form method="POST">
                <input type="hidden" name="action" value="verify_setup">
                <input type="hidden" name="secret" value="<?= htmlspecialchars($user['mfa_secret']) ?>">
                
                <div class="form-group">
                    <label>Enter the 6-digit code from your app:</label>
                    <div class="input-wrap">
                        <input type="text" name="code" class="code-input" placeholder="000000" maxlength="6" inputmode="numeric" required autofocus>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Verify & Enable MFA
                </button>
            </form>
            
            <div class="back-link">
                <a href="javascript:history.back()"><i class="fas fa-arrow-left"></i> Cancel</a>
            </div>
        </div>

    <?php elseif ($step === 'success'): ?>
        <div class="card">
            <div class="success-icon"><i class="fas fa-check-circle"></i></div>
            <h2 style="text-align:center;margin-bottom:20px;color:var(--dark);">MFA Enabled!</h2>
            
            <?php if ($message): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
            <?php endif; ?>
            
            <div class="info-box">
                <strong>Save Your Backup Codes</strong>
                <p>If you lose access to your authenticator app, you can use these backup codes to log in. Store them in a safe place.</p>
            </div>
            
            <?php
            $backup_codes = json_decode($_SESSION['backup_codes'] ?? '[]', true);
            if (empty($backup_codes)) {
                $stmt = $pdo->prepare("SELECT mfa_backup_codes FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $result = $stmt->fetch();
                $backup_codes = json_decode($result['mfa_backup_codes'], true);
            }
            ?>
            
            <div class="backup-codes">
                <h4><i class="fas fa-key"></i> Backup Codes</h4>
                <ul>
                    <?php foreach ($backup_codes as $code): ?>
                    <li><?= htmlspecialchars($code) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <p style="text-align:center;color:#666;margin:20px 0;font-size:13px;">
                <i class="fas fa-info-circle"></i> Each code can only be used once
            </p>
            
            <button onclick="downloadBackupCodes()" class="btn btn-secondary">
                <i class="fas fa-download"></i> Download Backup Codes
            </button>
            
            <div class="back-link">
                <a href="javascript:history.back()"><i class="fas fa-arrow-left"></i> Done</a>
            </div>
        </div>

    <?php elseif ($step === 'manage'): ?>
        <div class="card">
            <div class="step-icon"><i class="fas fa-check-circle" style="color:var(--success);"></i></div>
            <h2 style="text-align:center;margin-bottom:20px;color:var(--dark);">MFA is Enabled</h2>
            
            <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
            <?php endif; ?>
            
            <div class="info-box">
                <strong>Your account is protected</strong>
                <p>Multi-Factor Authentication is active. You'll need to enter a code from your authenticator app when you log in.</p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="disable_mfa">
                
                <div class="form-group">
                    <label>Enter your password to disable MFA:</label>
                    <div class="input-wrap">
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure? This will disable MFA on your account.');">
                    <i class="fas fa-times"></i> Disable MFA
                </button>
            </form>
            
            <div class="back-link">
                <a href="javascript:history.back()"><i class="fas fa-arrow-left"></i> Go Back</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function downloadBackupCodes() {
    const codes = document.querySelectorAll('.backup-codes li');
    let text = 'CyberAware Backup Codes\n';
    text += 'Generated: ' + new Date().toLocaleString() + '\n';
    text += 'Store these codes in a safe place\n\n';
    
    codes.forEach((code, i) => {
        text += (i + 1) + '. ' + code.textContent + '\n';
    });
    
    const element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', 'cyberaware-backup-codes.txt');
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
}
</script>

</body>
</html>
