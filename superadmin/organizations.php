<?php
ob_start();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/theme-helper.php';

// Require superadmin role
if (!isLoggedIn() || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../pages/login.php');
    exit;
}

$pdo = getDBConnection();
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['full_name'] ?? 'Super Admin';
$message = '';
$message_type = '';

// Handle add organization
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        if (empty($name)) {
            $message = 'Organization name is required';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO organizations (name, email, phone, address, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
                $stmt->execute([$name, $email, $phone, $address]);
                $message = 'Organization added successfully';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Error adding organization: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Get all organizations
$stmt = $pdo->prepare("SELECT * FROM organizations ORDER BY created_at DESC");
$stmt->execute();
$organizations = $stmt->fetchAll();

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Organizations — CyberAware</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../trainee/theme-styles.css">
<script>
(function() {
    const theme = localStorage.getItem('theme') || 'light';
    if (theme === 'dark') {
        document.documentElement.classList.add('dark-mode-init');
    }
})();
</script>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--orange:#FF8C42;--ol:#FFF4EC;--grey:#F5F5F5;--grey2:#E8E8E8;--dark:#1A1A2E;--green:#10B981;--red:#EF4444;}
body{font-family:'Manrope',sans-serif;background:var(--grey);min-height:100vh;}
.topbar{background:var(--dark);padding:0 24px;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
.tb-logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.tb-logo .ic{width:36px;height:36px;background:var(--orange);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;}
.tb-logo span{font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;color:#fff;}
.tb-right{display:flex;align-items:center;gap:16px;}
.tb-avatar-wrapper{position:relative;}
.tb-avatar{width:36px;height:36px;background:var(--orange);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;cursor:pointer;}
.tb-dropdown{position:absolute;top:calc(100% + 8px);right:0;background:#fff;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.15);min-width:280px;opacity:0;visibility:hidden;transform:translateY(-10px);transition:.2s;z-index:1000;}
.tb-dropdown.active{opacity:1;visibility:visible;transform:translateY(0);}
.tb-dropdown-header{padding:16px;border-bottom:1px solid #f0f0f0;}
.tb-dropdown-user{display:flex;align-items:center;gap:12px;margin-bottom:8px;}
.tb-dropdown-avatar{width:48px;height:48px;background:var(--orange);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:18px;}
.tb-dropdown-name{font-weight:700;color:var(--dark);font-size:14px;}
.tb-dropdown-email{font-size:12px;color:#888;}
.tb-dropdown-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#bbb;}
.tb-dropdown-menu{padding:8px 0;}
.tb-dropdown-item{display:flex;align-items:center;gap:12px;padding:12px 16px;color:#333;text-decoration:none;font-size:14px;font-weight:600;transition:.2s;}
.tb-dropdown-item:hover{background:#f5f5f5;color:var(--orange);}
.tb-dropdown-item i{width:18px;text-align:center;color:var(--orange);}
.tb-dropdown-item.logout{color:var(--red);}
.tb-dropdown-item.logout:hover{background:#fff0f0;}
.layout{display:grid;grid-template-columns:280px 1fr;min-height:calc(100vh - 64px);}
.sidebar{background:#fff;border-right:1px solid var(--grey2);padding:28px 20px;display:flex;flex-direction:column;gap:8px;}
.sb-profile{background:linear-gradient(135deg,var(--ol),#fff);border-radius:14px;padding:20px;margin-bottom:16px;text-align:center;}
.sb-avatar{width:60px;height:60px;background:var(--orange);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px;font-weight:700;margin:0 auto 12px;}
.sb-name{font-size:16px;font-weight:700;color:var(--dark);margin-bottom:2px;}
.sb-role{font-size:13px;color:#888;}
.sb-level{display:inline-block;background:var(--orange);color:#fff;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;margin-top:8px;}
.nav-item{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;text-decoration:none;color:#555;font-weight:600;font-size:15px;transition:.2s;}
.nav-item:hover,.nav-item.active{background:var(--ol);color:var(--orange);}
.nav-item i{width:20px;text-align:center;font-size:16px;}
.sb-section{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#bbb;padding:12px 16px 4px;}
.nav-logout{color:var(--red);}
.nav-logout:hover{background:#fff0f0;color:var(--red);}
.main{padding:32px;padding-bottom:100px;}
.page-header{margin-bottom:28px;}
.page-header h1{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;color:var(--dark);margin-bottom:6px;}
.page-header p{color:#888;font-size:14px;}
.header-bar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px;}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:10px;border:none;font-weight:700;cursor:pointer;transition:.2s;font-family:'Manrope',sans-serif;}
.btn-primary{background:var(--orange);color:#fff;}
.btn-primary:hover{background:#e67e2f;transform:translateY(-2px);}
.alert{padding:16px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:12px;}
.alert-success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.alert-error{background:#fee2e2;color:#7f1d1d;border:1px solid #fecaca;}
.table-container{background:#fff;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,.05);overflow:hidden;}
table{width:100%;border-collapse:collapse;}
th{background:var(--ol);padding:16px;text-align:left;font-weight:700;color:var(--dark);font-size:14px;border-bottom:1px solid var(--grey2);}
td{padding:16px;border-bottom:1px solid var(--grey2);font-size:14px;}
tr:hover{background:var(--ol);}
tr:last-child td{border-bottom:none;}
.status-badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;}
.status-active{background:#d1fae5;color:#065f46;}
.status-inactive{background:var(--grey2);color:#666;}
.action-links{display:flex;gap:8px;}
.action-links a{color:var(--orange);text-decoration:none;font-weight:600;font-size:13px;}
.action-links a:hover{text-decoration:underline;}
body.dark-mode{background:#1A1A2E;color:#fff;}
body.dark-mode .topbar{background:#0f0f1e;}
body.dark-mode .sidebar{background:#1f1f2e;border-right-color:#333;}
body.dark-mode .nav-item{color:#aaa;}
body.dark-mode .nav-item:hover,body.dark-mode .nav-item.active{background:rgba(255,140,66,.15);color:var(--orange);}
body.dark-mode .sb-profile{background:rgba(255,140,66,.1);}
body.dark-mode .sb-name{color:#fff;}
body.dark-mode .sb-role{color:#aaa;}
body.dark-mode .main{background:#1A1A2E;}
body.dark-mode .page-header h1{color:#fff;}
body.dark-mode .page-header p{color:#aaa;}
body.dark-mode .table-container{background:#2a2a3e;box-shadow:0 2px 8px rgba(0,0,0,.3);}
body.dark-mode th{background:rgba(255,140,66,.1);color:#fff;border-bottom-color:#333;}
body.dark-mode td{border-bottom-color:#333;}
body.dark-mode tr:hover{background:rgba(255,140,66,.05);}
body.dark-mode table{color:#fff;}
body.dark-mode .tb-dropdown{background:#2a2a3e;}
body.dark-mode .tb-dropdown-header{border-bottom-color:#333;}
body.dark-mode .tb-dropdown-name{color:#fff;}
body.dark-mode .tb-dropdown-item{color:#fff;}
body.dark-mode .tb-dropdown-item:hover{background:#333;color:var(--orange);}
@media(max-width:900px){.layout{grid-template-columns:1fr;}.sidebar{display:none;}}
</style>
</head>
<body class="<?= getThemeClass() ?>">

<!-- TOPBAR -->
<div class="topbar">
    <a href="dashboard.php" class="tb-logo">
        <div class="ic"><i class="fas fa-shield-alt"></i></div>
        <span>CyberAware</span>
    </a>
    <div class="tb-right">
        <div class="tb-avatar-wrapper">
            <div class="tb-avatar" onclick="toggleDropdown()"><?= strtoupper(substr($admin_name,0,1)) ?></div>
            <div class="tb-dropdown" id="profileDropdown">
                <div class="tb-dropdown-header">
                    <div class="tb-dropdown-user">
                        <div class="tb-dropdown-avatar"><?= strtoupper(substr($admin_name,0,1)) ?></div>
                        <div>
                            <div class="tb-dropdown-name"><?= htmlspecialchars($admin_name) ?></div>
                        </div>
                    </div>
                    <div class="tb-dropdown-label">Signed in as</div>
                </div>
                <div class="tb-dropdown-menu">
                    <a href="../logout.php" class="tb-dropdown-item logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sb-profile">
            <div class="sb-avatar"><?= strtoupper(substr($admin_name,0,1)) ?></div>
            <div class="sb-name"><?= htmlspecialchars($admin_name) ?></div>
            <div class="sb-role">Super Administrator</div>
            <div class="sb-level">Platform Admin</div>
        </div>
        <div class="sb-section">Management</div>
        <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
        <a href="organizations.php" class="nav-item active"><i class="fas fa-building"></i> Organizations</a>
        <a href="subscriptions.php" class="nav-item"><i class="fas fa-credit-card"></i> Subscriptions</a>
        <a href="training-content.php" class="nav-item"><i class="fas fa-book"></i> Training Content</a>
        <div class="sb-section">Account</div>
        <a href="../logout.php" class="nav-item nav-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <!-- MAIN -->
    <main class="main">
        <div class="page-header">
            <h1>Organizations</h1>
            <p>Manage all client organizations and their details.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="header-bar">
            <div></div>
            <button class="btn btn-primary" onclick="toggleAddForm()">
                <i class="fas fa-plus"></i> Add Organization
            </button>
        </div>

        <!-- Add Organization Form -->
        <div id="addForm" style="display:none;background:#fff;border-radius:16px;padding:24px;margin-bottom:24px;box-shadow:0 2px 8px rgba(0,0,0,.05);">
            <h2 style="font-size:18px;font-weight:700;margin-bottom:16px;color:var(--dark);">Add New Organization</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                    <input type="text" name="name" placeholder="Organization Name" required style="padding:10px 14px;border:1px solid var(--grey2);border-radius:8px;font-family:'Manrope',sans-serif;">
                    <input type="email" name="email" placeholder="Email" style="padding:10px 14px;border:1px solid var(--grey2);border-radius:8px;font-family:'Manrope',sans-serif;">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                    <input type="tel" name="phone" placeholder="Phone" style="padding:10px 14px;border:1px solid var(--grey2);border-radius:8px;font-family:'Manrope',sans-serif;">
                    <textarea name="address" placeholder="Address" style="padding:10px 14px;border:1px solid var(--grey2);border-radius:8px;font-family:'Manrope',sans-serif;"></textarea>
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Organization</button>
                    <button type="button" class="btn" style="background:var(--grey2);color:var(--dark);" onclick="toggleAddForm()"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>

        <!-- Organizations Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Organization Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($organizations as $org): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($org['name']) ?></strong></td>
                        <td><?= htmlspecialchars($org['email'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($org['phone'] ?? '-') ?></td>
                        <td><span class="status-badge <?= $org['is_active'] ? 'status-active' : 'status-inactive' ?>"><?= $org['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                        <td><?= date('M d, Y', strtotime($org['created_at'])) ?></td>
                        <td class="action-links">
                            <a href="#" onclick="editOrganization(<?= $org['id'] ?>)">Edit</a>
                            <a href="#" onclick="deleteOrganization(<?= $org['id'] ?>)" style="color:var(--red);">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>

<script>
function toggleAddForm() {
    const form = document.getElementById('addForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function toggleDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('active');
}

document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('profileDropdown');
    const avatar = document.querySelector('.tb-avatar');
    if (!dropdown.contains(event.target) && !avatar.contains(event.target)) {
        dropdown.classList.remove('active');
    }
});

function editOrganization(id) {
    alert('Edit feature coming soon');
}

function deleteOrganization(id) {
    if (confirm('Are you sure you want to delete this organization?')) {
        alert('Delete feature coming soon');
    }
}
</script>
