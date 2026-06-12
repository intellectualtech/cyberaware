<?php
// admin/manage-users-enhanced.php - Enhanced User Management with Email Notifications

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/email-notifications.php';

if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../index.php');
    exit();
}

$pdo = getDBConnection();
$message = '';
$message_type = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_user') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $role = trim($_POST['role'] ?? 'trainee');
        $department_id = (int)($_POST['department_id'] ?? 0) ?: null;
        $password = trim($_POST['password'] ?? '');
        $send_email = isset($_POST['send_email']);
        
        if (empty($username) || empty($email) || empty($password)) {
            $message = 'Username, email, and password are required.';
            $message_type = 'danger';
        } else {
            try {
                $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $check->execute([$username, $email]);
                if ($check->fetch()) {
                    $message = 'Username or email already exists.';
                    $message_type = 'danger';
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, password_hash, full_name, email, role, department_id, is_active, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                    ");
                    $stmt->execute([$username, $password_hash, $full_name ?: $username, $email, $role, $department_id]);
                    
                    // Send welcome email if requested
                    if ($send_email) {
                        notify_user_created($email, $full_name ?: $username, $username, $password);
                    }
                    
                    $message = "User '$username' created successfully" . ($send_email ? ' and welcome email sent.' : '.');
                    $message_type = 'success';
                }
            } catch (Exception $e) {
                $message = 'Error creating user: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
    
    elseif ($action === 'toggle_status') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("SELECT is_active, email, full_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                $new_status = $user['is_active'] ? 0 : 1;
                $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$new_status, $user_id]);
                
                $status_text = $new_status ? 'activated' : 'deactivated';
                $message = "User {$status_text} successfully.";
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error updating user: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
    
    elseif ($action === 'send_notification') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $notification_type = trim($_POST['notification_type'] ?? '');
        
        try {
            $stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                $sent = false;
                switch ($notification_type) {
                    case 'phishing':
                        $sent = notify_phishing_simulation($user['email'], $user['full_name']);
                        break;
                    case 'reminder':
                        $sent = notify_weekly_reminder($pdo, $user_id);
                        break;
                }
                
                if ($sent) {
                    $message = 'Notification sent successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Failed to send notification.';
                    $message_type = 'danger';
                }
            }
        } catch (Exception $e) {
            $message = 'Error sending notification: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Fetch users with stats
$users = $pdo->query("
    SELECT 
        u.id, u.username, u.full_name, u.email, u.role, u.is_active, u.created_at,
        d.name AS department_name,
        COUNT(DISTINCT ump.module_id) as modules_completed,
        COUNT(DISTINCT umr.module_id) as modules_registered
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN user_module_progress ump ON u.id = ump.user_id AND ump.passed = 1
    LEFT JOIN user_module_registrations umr ON u.id = umr.user_id
    WHERE u.role = 'trainee'
    GROUP BY u.id
    ORDER BY u.full_name
")->fetchAll(PDO::FETCH_ASSOC);

$departments = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - CyberAware Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --orange: #FF8C42;
            --dark: #1A1A2E;
            --grey: #F5F5F5;
            --grey2: #E8E8E8;
            --green: #10B981;
            --red: #EF4444;
            --blue: #3B82F6;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Manrope', sans-serif; background: linear-gradient(135deg, #EE8E46 0%, #E67A2E 100%); color: var(--dark); }
        .main-content { padding: 32px 24px 80px; max-width: 1400px; margin: 0 auto; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .page-header h1 { font-family: 'Space Grotesk', sans-serif; font-size: 32px; font-weight: 700; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-primary { background: var(--orange); color: white; }
        .btn-primary:hover { background: #E67A2E; transform: translateY(-2px); }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn-success { background: var(--green); color: white; }
        .btn-danger { background: var(--red); color: white; }
        .message { padding: 16px 20px; border-radius: 10px; margin-bottom: 24px; border-left: 4px solid; }
        .message.success { background: #ecfdf5; color: #065f46; border-color: #10b981; }
        .message.danger { background: #fef2f2; color: #991b1b; border-color: #ef4444; }
        .card { background: white; border-radius: 14px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 24px; }
        .card h2 { font-size: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 2px solid var(--grey2); border-radius: 8px; font-family: inherit; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--orange); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .checkbox-group { display: flex; align-items: center; gap: 8px; }
        .checkbox-group input { width: auto; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: var(--grey); padding: 12px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; }
        td { padding: 14px 12px; border-bottom: 1px solid var(--grey2); }
        tr:hover { background: #fafafa; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-trainee { background: #dbeafe; color: #1e40af; }
        .badge-admin { background: #fee2e2; color: #991b1b; }
        .badge-manager { background: #fef3c7; color: #92400e; }
        .status-active { color: var(--green); font-weight: 600; }
        .status-inactive { color: var(--red); font-weight: 600; }
        .actions { display: flex; gap: 8px; }
        .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 14px; padding: 32px; max-width: 500px; width: 90%; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { font-size: 22px; }
        .close-btn { background: none; border: none; font-size: 24px; cursor: pointer; color: #999; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; border-left: 4px solid var(--orange); }
        .stat-card h3 { font-size: 14px; color: #999; margin-bottom: 8px; }
        .stat-card .value { font-size: 28px; font-weight: 700; color: var(--dark); }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 16px; }
        }
    </style>
</head>
<body>

<?php include 'admin-sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <h1>👥 Manage Users</h1>
        <button class="btn btn-primary" onclick="openAddUserModal()">
            <i class="fas fa-user-plus"></i> Add New User
        </button>
    </div>

    <?php if ($message): ?>
        <div class="message <?= htmlspecialchars($message_type) ?>">
            <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Trainees</h3>
            <div class="value"><?= count($users) ?></div>
        </div>
        <div class="stat-card">
            <h3>Active Users</h3>
            <div class="value"><?= count(array_filter($users, fn($u) => $u['is_active'])) ?></div>
        </div>
        <div class="stat-card">
            <h3>Avg. Modules Completed</h3>
            <div class="value"><?= count($users) > 0 ? round(array_sum(array_column($users, 'modules_completed')) / count($users), 1) : 0 ?></div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <h2>All Trainees</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($user['full_name']) ?></strong><br>
                            <span style="color: #999; font-size: 12px;">@<?= htmlspecialchars($user['username']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['department_name'] ?: '—') ?></td>
                        <td>
                            <span style="font-weight: 600;"><?= (int)$user['modules_completed'] ?>/<?= (int)$user['modules_registered'] ?></span>
                            <div style="width: 100px; height: 6px; background: var(--grey2); border-radius: 3px; margin-top: 4px; overflow: hidden;">
                                <div style="height: 100%; background: var(--orange); width: <?= $user['modules_registered'] > 0 ? round(($user['modules_completed'] / $user['modules_registered']) * 100) : 0 ?>%;"></div>
                            </div>
                        </td>
                        <td>
                            <span class="status-<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $user['is_active'] ? '✓ Active' : '✗ Inactive' ?>
                            </span>
                        </td>
                        <td style="font-size: 12px; color: #999;">
                            <?= date('M d, Y', strtotime($user['created_at'])) ?>
                        </td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-sm" style="background: var(--blue); color: white;" onclick="openNotificationModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['full_name']) ?>')">
                                    <i class="fas fa-bell"></i> Notify
                                </button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn btn-sm" style="background: <?= $user['is_active'] ? 'var(--red)' : 'var(--green)' ?>; color: white;">
                                        <i class="fas fa-<?= $user['is_active'] ? 'ban' : 'check' ?>"></i> <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New User</h2>
            <button class="close-btn" onclick="closeAddUserModal()">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_user">
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required>
            </div>
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>Department</label>
                <select name="department_id">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="send_email" name="send_email" checked>
                <label for="send_email" style="margin: 0;">Send welcome email</label>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Create User</button>
                <button type="button" class="btn" style="flex: 1; background: var(--grey2); color: var(--dark);" onclick="closeAddUserModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Notification Modal -->
<div id="notificationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Send Notification</h2>
            <button class="close-btn" onclick="closeNotificationModal()">×</button>
        </div>
        <form method="POST" id="notificationForm">
            <input type="hidden" name="action" value="send_notification">
            <input type="hidden" name="user_id" id="notificationUserId">
            
            <p style="margin-bottom: 16px; color: #666;">Send notification to <strong id="notificationUserName"></strong></p>
            
            <div class="form-group">
                <label>Notification Type</label>
                <select name="notification_type" required>
                    <option value="">Select Type</option>
                    <option value="phishing">🎣 Weekly Phishing Simulation</option>
                    <option value="reminder">📚 Weekly Training Reminder</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Send Notification</button>
                <button type="button" class="btn" style="flex: 1; background: var(--grey2); color: var(--dark);" onclick="closeNotificationModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddUserModal() {
    document.getElementById('addUserModal').classList.add('active');
}

function closeAddUserModal() {
    document.getElementById('addUserModal').classList.remove('active');
}

function openNotificationModal(userId, userName) {
    document.getElementById('notificationUserId').value = userId;
    document.getElementById('notificationUserName').textContent = userName;
    document.getElementById('notificationModal').classList.add('active');
}

function closeNotificationModal() {
    document.getElementById('notificationModal').classList.remove('active');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const addModal = document.getElementById('addUserModal');
    const notifModal = document.getElementById('notificationModal');
    if (event.target === addModal) addModal.classList.remove('active');
    if (event.target === notifModal) notifModal.classList.remove('active');
}
</script>

</body>
</html>
