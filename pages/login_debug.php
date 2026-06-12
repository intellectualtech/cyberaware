<?php
require_once '../config/database.php';
$error = '';
$debug = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $debug .= "Username: " . htmlspecialchars($username) . "<br>";
    $debug .= "Password length: " . strlen($password) . "<br>";
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $conn = getDBConnection();
            $debug .= "Database connected successfully<br>";
            
            $stmt = $conn->prepare("SELECT id, username, password_hash, full_name, role, is_active FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            $debug .= "User found: " . ($user ? "YES" : "NO") . "<br>";
            
            if ($user) {
                $debug .= "User ID: " . $user['id'] . "<br>";
                $debug .= "User role: " . $user['role'] . "<br>";
                $debug .= "User active: " . $user['is_active'] . "<br>";
                $debug .= "Password hash: " . substr($user['password_hash'], 0, 20) . "...<br>";
                
                $passwordMatch = verifyPassword($password, $user['password_hash']);
                $debug .= "Password match: " . ($passwordMatch ? "YES" : "NO") . "<br>";
                
                if ($passwordMatch) {
                    if (!$user['is_active']) {
                        $error = 'Your account has been suspended. Contact your administrator.';
                    } else {
                        $conn->prepare("UPDATE users SET failed_login_attempts=0, last_login=NOW() WHERE id=?")->execute([$user['id']]);
                        $_SESSION['user_id']   = $user['id'];
                        $_SESSION['username']  = $user['username'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['role']      = $user['role'];
                        $debug .= "Login successful! Redirecting...<br>";
                        if (in_array($user['role'], ['admin','manager','superadmin'])) {
                            header('Location: ../admin/dashboard.php');
                        } else {
                            header('Location: ../trainee/dashboard.php');
                        }
                        exit();
                    }
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Invalid username or password.';
            }
        } catch(PDOException $e) {
            $error = 'Login error: ' . $e->getMessage();
            $debug .= "PDO Error: " . $e->getMessage() . "<br>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Debug — CyberAware</title>
<style>
body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
.container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
h1 { color: #333; }
.error { background: #ffebee; border: 1px solid #ef5350; color: #c62828; padding: 15px; border-radius: 4px; margin: 20px 0; }
.debug { background: #e3f2fd; border: 1px solid #42a5f5; color: #1565c0; padding: 15px; border-radius: 4px; margin: 20px 0; font-family: monospace; font-size: 12px; }
.form-group { margin: 20px 0; }
label { display: block; margin-bottom: 5px; font-weight: bold; }
input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
button { background: #ff8c42; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
button:hover { background: #e07030; }
</style>
</head>
<body>

<div class="container">
    <h1>CyberAware Login (Debug Mode)</h1>
    
    <?php if ($error): ?>
    <div class="error">
        <strong>Error:</strong> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($debug): ?>
    <div class="debug">
        <strong>Debug Info:</strong><br>
        <?= $debug ?>
    </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="admin" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Uncle@foddy1" required>
        </div>
        <button type="submit">Sign In</button>
    </form>
    
    <p style="margin-top: 30px; color: #666; font-size: 12px;">
        <strong>Test Credentials:</strong><br>
        Username: admin<br>
        Password: Uncle@foddy1
    </p>
</div>

</body>
</html>
