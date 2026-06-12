<?php
/**
 * Manager Profile Page
 * Allows managers to manage personal details, account security, and 2FA settings
 */

error_reporting(E_ALL & ~E_WARNING);
ini_set('display_errors', 0);

require_once '../config/database.php';
require_once '../includes/functions.php';

// Debug: Check session
// echo "Session role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";
// echo "Is logged in: " . (isLoggedIn() ? 'YES' : 'NO') . "<br>";

// Require login and manager role
if (!isLoggedIn()) {
    header('Location: ../pages/login.php');
    exit;
}

$role = strtolower(trim($_SESSION['role'] ?? ''));
if ($role !== 'manager') {
    header('Location: ../pages/access-denied.php');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'update_personal') {
            // Update personal details
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            
            // Validation
            if (empty($full_name)) {
                throw new Exception('Full name is required');
            }
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Valid email is required');
            }
            
            // Check if email is already used by another user
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_stmt->execute([$email, $user_id]);
            if ($check_stmt->fetch()) {
                throw new Exception('Email is already in use');
            }
            
            $update_stmt = $pdo->prepare("
                UPDATE users 
                SET full_name = ?, email = ?, phone = ?
                WHERE id = ?
            ");
            $update_stmt->execute([$full_name, $email, $phone, $user_id]);
            
            // Update session email if changed
            $_SESSION['email'] = $email;
            
            $message = 'Personal details updated successfully!';
            $message_type = 'success';
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } elseif ($action === 'change_password') {
            // Change password
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validation
            if (empty($current_password)) {
                throw new Exception('Current password is required');
            }
            if (empty($new_password)) {
                throw new Exception('New password is required');
            }
            if ($new_password !== $confirm_password) {
                throw new Exception('Passwords do not match');
            }
            if (strlen($new_password) < 8) {
                throw new Exception('Password must be at least 8 characters');
            }
            
            // Verify current password
            if (!password_verify($current_password, $user['password_hash'] ?? $user['password'])) {
                throw new Exception('Current password is incorrect');
            }
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $pwd_stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $pwd_stmt->execute([$hashed_password, $user_id]);
            
            $message = 'Password changed successfully!';
            $message_type = 'success';
            
        } elseif ($action === 'toggle_2fa') {
            // Toggle 2FA
            $enable_2fa = isset($_POST['enable_2fa']) ? 1 : 0;
            
            if ($enable_2fa && !$user['mfa_enabled']) {
                // Generate secret for 2FA
                $secret = bin2hex(random_bytes(32));
                $backup_codes = json_encode(array_map(function() {
                    return bin2hex(random_bytes(4));
                }, range(1, 10)));
                
                $mfa_stmt = $pdo->prepare("
                    UPDATE users 
                    SET mfa_enabled = 1, mfa_secret = ?, mfa_backup_codes = ?, mfa_enabled_at = NOW()
                    WHERE id = ?
                ");
                $mfa_stmt->execute([$secret, $backup_codes, $user_id]);
                
                $message = '2FA has been enabled! Please scan the QR code with your authenticator app.';
                $message_type = 'success';
            } elseif (!$enable_2fa && $user['mfa_enabled']) {
                // Disable 2FA
                $mfa_stmt = $pdo->prepare("
                    UPDATE users 
                    SET mfa_enabled = 0, mfa_secret = NULL, mfa_backup_codes = NULL
                    WHERE id = ?
                ");
                $mfa_stmt->execute([$user_id]);
                
                $message = '2FA has been disabled.';
                $message_type = 'success';
            }
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } elseif ($action === 'deactivate_account') {
            // Deactivate account
            $confirm_deactivate = $_POST['confirm_deactivate'] ?? '';
            
            if ($confirm_deactivate !== 'DEACTIVATE') {
                throw new Exception('Please type "DEACTIVATE" to confirm');
            }
            
            $deactivate_stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            $deactivate_stmt->execute([$user_id]);
            
            // Log out user
            session_destroy();
            header('Location: ../pages/login.php?deactivated=1');
            exit;
        }
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Generate QR code for 2FA if enabled
$qr_code_url = '';
if ($user['mfa_enabled'] && $user['mfa_secret']) {
    $issuer = 'CyberAware';
    $account_name = $user['email'];
    $secret = $user['mfa_secret'];
    $qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=otpauth://totp/" . urlencode($account_name) . "?secret=" . urlencode($secret) . "&issuer=" . urlencode($issuer);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile – CyberAware Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }

        .profile-container {
            display: flex;
            min-height: 100vh;
        }

        .profile-sidebar {
            width: 250px;
            background: white;
            border-right: 1px solid #eee;
            padding: 20px;
        }

        .profile-content {
            flex: 1;
            padding: 40px;
            max-width: 900px;
            margin: 0 auto;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 40px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FF8C42 0%, #FF8C42 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: 700;
        }

        .profile-info h1 {
            font-size: 24px;
            color: #1A1A2E;
            margin-bottom: 5px;
        }

        .profile-info p {
            color: #888;
            font-size: 14px;
        }

        .profile-status {
            display: inline-block;
            background: #d4edda;
            color: #155724;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 700;
            color: #1A1A2E;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #FF8C42;
        }

        .section-title i {
            color: #FF8C42;
            font-size: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="tel"]:focus {
            outline: none;
            border-color: #FF8C42;
            box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
        }

        .toggle-switch {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .toggle-switch-label {
            flex: 1;
        }

        .toggle-switch-label h4 {
            font-size: 14px;
            font-weight: 600;
            color: #1A1A2E;
            margin: 0 0 4px 0;
        }

        .toggle-switch-label p {
            font-size: 12px;
            color: #888;
            margin: 0;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 28px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 28px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #FF8C42;
        }

        input:checked + .slider:before {
            transform: translateX(22px);
        }

        .qr-code-container {
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            margin-top: 15px;
        }

        .qr-code-container img {
            max-width: 200px;
            margin-bottom: 10px;
        }

        .qr-code-container p {
            font-size: 12px;
            color: #888;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #FF8C42;
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            background: #e67e2f;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 140, 66, 0.3);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            flex: 1;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .deactivate-section {
            border-top: 2px solid #f0f0f0;
            padding-top: 20px;
            margin-top: 20px;
        }

        .deactivate-warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 13px;
        }

        .deactivate-warning strong {
            display: block;
            margin-bottom: 5px;
        }

        .help-text {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .profile-container {
                flex-direction: column;
            }

            .profile-sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #eee;
            }

            .profile-content {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <!-- Sidebar -->
        <div class="profile-sidebar">
            <div style="margin-bottom: 30px;">
                <a href="dashboard.php" style="display: block; padding: 10px; color: #888; text-decoration: none; border-radius: 6px; transition: all 0.3s;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="profile-content">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                    <span class="profile-status">
                        <i class="fas fa-check-circle"></i> Manager
                    </span>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <div><?php echo $message; ?></div>
                </div>
            <?php endif; ?>

            <!-- Personal Details Section -->
            <div class="section">
                <div class="section-title">
                    <i class="fas fa-user"></i> Personal Details
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="update_personal">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Save Personal Details
                    </button>
                </form>
            </div>

            <!-- Account Security Section -->
            <div class="section">
                <div class="section-title">
                    <i class="fas fa-shield-alt"></i> Account Security
                </div>

                <!-- Change Password -->
                <div style="margin-bottom: 30px;">
                    <h4 style="font-size: 14px; font-weight: 600; color: #1A1A2E; margin-bottom: 15px;">
                        <i class="fas fa-lock" style="color: #FF8C42; margin-right: 8px;"></i> Change Password
                    </h4>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        <div class="help-text">Password must be at least 8 characters</div>

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-key"></i> Update Password
                        </button>
                    </form>
                </div>

                <!-- Two-Factor Authentication -->
                <div style="border-top: 1px solid #eee; padding-top: 20px;">
                    <h4 style="font-size: 14px; font-weight: 600; color: #1A1A2E; margin-bottom: 15px;">
                        <i class="fas fa-mobile-alt" style="color: #FF8C42; margin-right: 8px;"></i> Two-Factor Authentication
                    </h4>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="toggle_2fa">
                        
                        <div class="toggle-switch">
                            <div class="toggle-switch-label">
                                <h4>Enable 2FA</h4>
                                <p>Add an extra layer of security to your account</p>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="enable_2fa" <?php echo ($user['mfa_enabled'] ?? 0) ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </form>

                    <?php if (($user['mfa_enabled'] ?? 0) && $qr_code_url): ?>
                        <div class="qr-code-container">
                            <p style="margin-bottom: 10px; font-weight: 600;">Scan with your authenticator app:</p>
                            <img src="<?php echo $qr_code_url; ?>" alt="2FA QR Code">
                            <p>Use an app like Google Authenticator, Microsoft Authenticator, or Authy</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Account Deactivation Section -->
            <div class="section deactivate-section">
                <div class="section-title" style="border-bottom-color: #dc3545;">
                    <i class="fas fa-ban" style="color: #dc3545;"></i> Deactivate Account
                </div>

                <div class="deactivate-warning">
                    <strong>⚠ Warning</strong>
                    Deactivating your account will disable your access to CyberAware. This action can be reversed by contacting an administrator.
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="deactivate_account">
                    
                    <div class="form-group">
                        <label for="confirm_deactivate">Type "DEACTIVATE" to confirm:</label>
                        <input type="text" id="confirm_deactivate" name="confirm_deactivate" placeholder="Type DEACTIVATE" required>
                        <div class="help-text">This action cannot be undone without administrator assistance</div>
                    </div>

                    <button type="submit" class="btn-danger" onclick="return confirm('Are you sure you want to deactivate your account?');">
                        <i class="fas fa-trash"></i> Deactivate Account
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
