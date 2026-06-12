<?php
// admin/manage_users.php - Manage Users (with Excel/CSV Bulk Import)

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/theme-helper.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_theme') {
    $theme = isset($_POST['theme']) ? $_POST['theme'] : 'light';
    setThemeSession($theme);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'theme' => $theme]);
    exit;
}

$pdo = getDBConnection();
$admin_id = $_SESSION['user_id'];
$admin_name = 'Admin';
$admin_email = '';

try {
    $stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    if ($admin) {
        $admin_name = $admin['full_name'] ?? 'Admin';
        $admin_email = $admin['email'] ?? '';
    }
} catch (Exception $e) {
    $admin_name = $_SESSION['full_name'] ?? 'Admin';
}

// Handle file upload
$import_message = '';
$import_type = '';
$import_details = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['user_file']) && $_FILES['user_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['user_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, ['csv', 'xlsx'])) {
        $import_message = "Only .csv and .xlsx files are allowed.";
        $import_type = 'danger';
    } else {
        try {
            $pdo->beginTransaction();

            if ($ext === 'csv') {
                // ── CSV Import ───────────────────────────────────────────────
                $handle = fopen($file['tmp_name'], 'r');
                if (!$handle) throw new Exception("Cannot open CSV file");

                $header = fgetcsv($handle); // first row = headers
                if (!$header) throw new Exception("Empty CSV file");

                // Normalize headers (lowercase, trim)
                $header_map = array_map('trim', array_map('strtolower', $header));

                $col = [
                    'username' => array_search('username', $header_map),
                    'full_name' => array_search('full_name', $header_map) ?: array_search('name', $header_map),
                    'email' => array_search('email', $header_map),
                    'role' => array_search('role', $header_map),
                    'department' => array_search('department', $header_map) ?: array_search('department_code', $header_map),
                    'password' => array_search('password', $header_map),
                ];

                if ($col['username'] === false || $col['email'] === false || $col['role'] === false) {
                    throw new Exception("CSV must contain at least: username, email, role columns");
                }

                $row_num = 2; // starting from 2 because 1 = header
                while (($data = fgetcsv($handle)) !== false) {
                    $row_num++;

                    $username   = trim($data[$col['username']] ?? '');
                    $full_name  = trim($data[$col['full_name']] ?? '');
                    $email      = trim($data[$col['email']] ?? '');
                    $role       = strtolower(trim($data[$col['role']] ?? 'trainee'));
                    $dept_code  = trim($data[$col['department']] ?? '');
                    $plain_pass = trim($data[$col['password']] ?? ''); // optional

                    if (empty($username) || empty($email)) continue; // skip empty rows

                    // Validate role
                    if (!in_array($role, ['trainee','manager','compliance','admin'])) {
                        $import_details[] = "Row $row_num: Invalid role '$role' → skipped";
                        continue;
                    }

                    // Find department id
                    $dept_id = null;
                    if ($dept_code) {
                        $dstmt = $pdo->prepare("SELECT id FROM departments WHERE code = ? OR name = ? LIMIT 1");
                        $dstmt->execute([$dept_code, $dept_code]);
                        $dept = $dstmt->fetch();
                        if ($dept) $dept_id = $dept['id'];
                    }

                    // Check if user already exists (by email or username)
                    $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $check->execute([$username, $email]);
                    if ($check->fetch()) {
                        $import_details[] = "Row $row_num: User $username / $email already exists → skipped";
                        continue;
                    }

                    // Insert new user
                    $pass_hash = $plain_pass ? password_hash($plain_pass, PASSWORD_DEFAULT) : password_hash('ChangeMe123!', PASSWORD_DEFAULT);

                    $insert = $pdo->prepare("
                        INSERT INTO users (username, password_hash, full_name, email, role, department_id, is_active, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                    ");
                    $insert->execute([$username, $pass_hash, $full_name ?: $username, $email, $role, $dept_id]);

                    $import_details[] = "Row $row_num: User <strong>$username</strong> created successfully";
                }

                fclose($handle);
            } 
            else {
                // ── XLSX Import (using PhpSpreadsheet) ───────────────────────
                // Note: You need to install phpoffice/phpspreadsheet via composer first!
                // composer require phpoffice/phpspreadsheet
                require_once '../vendor/autoload.php'; // adjust path if needed

                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                $spreadsheet = $reader->load($file['tmp_name']);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                if (count($rows) < 2) throw new Exception("Excel file is empty or has no data");

                $header = array_map('trim', array_map('strtolower', $rows[0]));

                $col = [
                    'username'   => array_search('username', $header),
                    'full_name'  => array_search('full_name', $header) ?: array_search('name', $header),
                    'email'      => array_search('email', $header),
                    'role'       => array_search('role', $header),
                    'department' => array_search('department', $header) ?: array_search('department_code', $header),
                    'password'   => array_search('password', $header),
                ];

                if ($col['username'] === false || $col['email'] === false || $col['role'] === false) {
                    throw new Exception("Excel must contain at least: username, email, role columns");
                }

                for ($i = 1; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    $row_num = $i + 1;

                    $username   = trim($row[$col['username']] ?? '');
                    $full_name  = trim($row[$col['full_name']] ?? '');
                    $email      = trim($row[$col['email']] ?? '');
                    $role       = strtolower(trim($row[$col['role']] ?? 'trainee'));
                    $dept_code  = trim($row[$col['department']] ?? '');
                    $plain_pass = trim($row[$col['password']] ?? '');

                    if (empty($username) || empty($email)) continue;

                    if (!in_array($role, ['trainee','manager','compliance','admin'])) {
                        $import_details[] = "Row $row_num: Invalid role → skipped";
                        continue;
                    }

                    $dept_id = null;
                    if ($dept_code) {
                        $dstmt = $pdo->prepare("SELECT id FROM departments WHERE code = ? OR name = ? LIMIT 1");
                        $dstmt->execute([$dept_code, $dept_code]);
                        $dept = $dstmt->fetch();
                        if ($dept) $dept_id = $dept['id'];
                    }

                    $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $check->execute([$username, $email]);
                    if ($check->fetch()) {
                        $import_details[] = "Row $row_num: User already exists → skipped";
                        continue;
                    }

                    $pass_hash = $plain_pass ? password_hash($plain_pass, PASSWORD_DEFAULT) : password_hash('ChangeMe123!', PASSWORD_DEFAULT);

                    $insert = $pdo->prepare("
                        INSERT INTO users (username, password_hash, full_name, email, role, department_id, is_active, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                    ");
                    $insert->execute([$username, $pass_hash, $full_name ?: $username, $email, $role, $dept_id]);

                    $import_details[] = "Row $row_num: User <strong>$username</strong> created";
                }
            }

            $pdo->commit();

            $import_message = "Import completed! " . count($import_details) . " rows processed.";
            $import_type = 'success';

        } catch (Exception $e) {
            $pdo->rollBack();
            $import_message = "Import failed: " . $e->getMessage();
            $import_type = 'danger';
        }
    }
}

// Fetch existing users
$users = $pdo->query("
    SELECT u.id, u.username, u.full_name, u.email, u.role, u.is_active, d.name AS department_name
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    ORDER BY u.full_name
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Users — CyberAware</title>
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
:root{--orange:#FF8C42;--ol:#FFF4EC;--grey:#F5F5F5;--grey2:#E8E8E8;--dark:#1A1A2E;--green:#10B981;--purple:#8B5CF6;--red:#EF4444;}
body{font-family:'Manrope',sans-serif;background:var(--grey);min-height:100vh;}
.topbar{background:var(--dark);padding:0 24px;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
.tb-logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.tb-logo .ic{width:36px;height:36px;background:var(--orange);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;}
.tb-logo span{font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;color:#fff;}
.tb-right{display:flex;align-items:center;gap:16px;}
.tb-avatar-wrapper{position:relative;}
.tb-avatar{width:36px;height:36px;background:var(--orange);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;cursor:pointer;transition:.2s;}
.tb-avatar:hover{background:#e67e2f;transform:scale(1.05);}
.tb-dropdown{position:absolute;top:calc(100% + 8px);right:0;background:#fff;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.15);min-width:280px;opacity:0;visibility:hidden;transform:translateY(-10px);transition:.2s;z-index:1000;}
.tb-dropdown.active{opacity:1;visibility:visible;transform:translateY(0);}
.tb-dropdown-header{padding:16px;border-bottom:1px solid #f0f0f0;}
.tb-dropdown-user{display:flex;align-items:center;gap:12px;margin-bottom:8px;}
.tb-dropdown-avatar{width:48px;height:48px;background:var(--orange);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:18px;}
.tb-dropdown-name{font-weight:700;color:var(--dark);font-size:14px;}
.tb-dropdown-email{font-size:12px;color:#888;}
.tb-dropdown-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#bbb;margin-top:8px;}
.tb-dropdown-menu{padding:8px 0;}
.tb-dropdown-item{display:flex;align-items:center;gap:12px;padding:12px 16px;color:#333;text-decoration:none;font-size:14px;font-weight:600;transition:.2s;cursor:pointer;}
.tb-dropdown-item:hover{background:#f5f5f5;color:var(--orange);}
.tb-dropdown-item i{width:18px;text-align:center;color:var(--orange);}
.tb-dropdown-item.logout{color:var(--red);}
.tb-dropdown-item.logout:hover{background:#fff0f0;}
.tb-dropdown-item.logout i{color:var(--red);}
.layout{display:grid;grid-template-columns:280px 1fr;min-height:calc(100vh - 64px);}
.layout.sidebar-hidden{grid-template-columns:1fr;}
.sidebar{background:#fff;border-right:1px solid var(--grey2);padding:28px 20px;display:flex;flex-direction:column;gap:8px;position:relative;}
.sidebar.hidden{display:none;}
.toggle-sidebar{position:absolute;top:20px;right:20px;background:var(--orange);color:#fff;border:none;width:36px;height:36px;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;transition:.2s;z-index:50;}
.toggle-sidebar:hover{background:#e67e2f;}
.show-sidebar{position:fixed;bottom:20px;left:20px;background:var(--orange);border:none;color:#fff;border-radius:50%;width:50px;height:50px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:20px;box-shadow:0 4px 12px rgba(255,140,66,.3);transition:.2s;z-index:99;opacity:0;visibility:hidden;pointer-events:none;}
.show-sidebar.visible{opacity:1;visibility:visible;pointer-events:auto;}
.show-sidebar:hover{background:#e67e2f;transform:scale(1.1);}
body.dark-mode .show-sidebar{background:var(--orange);}
body.dark-mode .show-sidebar:hover{background:#e67e2f;}
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
.page-hdr{margin-bottom:28px;}
.page-hdr h1{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;color:var(--dark);margin-bottom:6px;}
.page-hdr p{color:#888;font-size:15px;}
.card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.05);border-left:4px solid var(--orange);margin-bottom:24px;transition:.2s;}
.card:hover{box-shadow:0 12px 32px rgba(0,0,0,.1);}
.card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--grey2);}
.card-title{font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;color:var(--dark);}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;cursor:pointer;border:none;transition:.2s;}
.btn-primary{background:var(--orange);color:#fff;}
.btn-primary:hover{background:#e67e2f;transform:translateY(-2px);box-shadow:0 8px 16px rgba(255,140,66,.3);}
.btn-secondary{background:var(--grey2);color:var(--dark);}
.btn-secondary:hover{background:#ddd;}
.message{padding:16px;border-radius:10px;margin:16px 0;font-weight:600;font-size:14px;}
.message-success{background:#ecfdf5;color:#065f46;border-left:4px solid #10b981;}
.message-danger{background:#fef2f2;color:#991b1b;border-left:4px solid #ef4444;}
.form-group{margin-bottom:20px;}
.form-label{display:block;font-weight:700;color:var(--dark);margin-bottom:8px;font-size:14px;}
.form-input,.form-select{width:100%;padding:10px 14px;border:1px solid var(--grey2);border-radius:8px;font-family:inherit;font-size:14px;}
.form-input:focus,.form-select:focus{outline:none;border-color:var(--orange);box-shadow:0 0 0 3px rgba(255,140,66,.1);}
.import-section{background:var(--ol);padding:20px;border-radius:12px;margin-bottom:24px;}
.import-section h3{color:var(--dark);font-family:'Space Grotesk',sans-serif;font-size:16px;margin-bottom:16px;}
.upload-area{display:flex;gap:12px;align-items:center;flex-wrap:wrap;}
.file-name{color:#888;font-size:14px;}
.info-box{background:rgba(139,92,246,.1);border-left:4px solid var(--purple);padding:14px;border-radius:8px;margin:16px 0;font-size:13px;color:#666;}
table{width:100%;border-collapse:collapse;margin-top:16px;}
th,td{padding:12px 14px;text-align:left;border-bottom:1px solid var(--grey2);font-size:14px;}
th{background:var(--grey);font-weight:700;color:var(--dark);}
tbody tr:hover{background:var(--ol);}
.badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:700;}
.badge-admin{background:#fce7f3;color:#831843;}
.badge-manager{background:#dbeafe;color:#0c4a6e;}
.badge-trainee{background:#dcfce7;color:#166534;}
.badge-compliance{background:#fef3c7;color:#92400e;}
.status-active{color:var(--green);font-weight:700;}
.status-inactive{color:var(--red);font-weight:700;}
.action-links{display:flex;gap:12px;font-size:13px;}
.action-links a{text-decoration:none;font-weight:600;color:var(--orange);transition:.2s;}
.action-links a:hover{color:#e67e2f;}
.action-links .delete{color:var(--red);}
.action-links .delete:hover{color:#dc2626;}
.details-log{max-height:240px;overflow-y:auto;background:var(--grey);padding:12px;border-radius:8px;margin-top:12px;}
.details-log ul{margin:0;padding-left:20px;}
.details-log li{padding:4px 0;color:#666;font-size:13px;}
body.dark-mode{background:#1A1A2E;color:#fff;}
body.dark-mode .topbar{background:#0f0f1e;}
body.dark-mode .sidebar{background:#1f1f2e;border-right-color:#333;}
body.dark-mode .nav-item{color:#aaa;}
body.dark-mode .nav-item:hover,body.dark-mode .nav-item.active{background:rgba(255,140,66,.15);color:var(--orange);}
body.dark-mode .sb-profile{background:rgba(255,140,66,.1);}
body.dark-mode .sb-name{color:#fff;}
body.dark-mode .sb-role{color:#aaa;}
body.dark-mode .main{background:#1A1A2E;}
body.dark-mode .page-hdr h1{color:#fff;}
body.dark-mode .page-hdr p{color:#aaa;}
body.dark-mode .card{background:#2a2a3e;box-shadow:0 2px 8px rgba(0,0,0,.3);}
body.dark-mode .card:hover{box-shadow:0 12px 32px rgba(0,0,0,.5);}
body.dark-mode .card-header{border-bottom-color:#333;}
body.dark-mode .card-title{color:#fff;}
body.dark-mode .form-label{color:#fff;}
body.dark-mode .form-input,.form-select{background:#1f1f2e;border-color:#333;color:#fff;}
body.dark-mode .form-input::placeholder{color:#666;}
body.dark-mode .import-section{background:rgba(255,140,66,.1);}
body.dark-mode .import-section h3{color:#fff;}
body.dark-mode .info-box{background:rgba(139,92,246,.2);border-left-color:var(--purple);}
body.dark-mode table{background:#2a2a3e;}
body.dark-mode th{background:#1f1f2e;color:#fff;}
body.dark-mode th,body.dark-mode td{border-bottom-color:#333;}
body.dark-mode tbody tr:hover{background:#333;}
body.dark-mode .tb-dropdown{background:#2a2a3e;}
body.dark-mode .tb-dropdown-header{border-bottom-color:#333;}
body.dark-mode .tb-dropdown-name{color:#fff;}
body.dark-mode .tb-dropdown-item{color:#fff;}
body.dark-mode .tb-dropdown-item:hover{background:#333;color:var(--orange);}
body.dark-mode .details-log{background:#1f1f2e;color:#aaa;}
@media(max-width:900px){.layout{grid-template-columns:1fr;}.sidebar{display:none;}}
</style>
</head>
<body class="<?= getThemeClass() ?>">

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
                            <div class="tb-dropdown-email"><?= htmlspecialchars($admin_email ?? '') ?></div>
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

<button id="showSidebarBtn" class="show-sidebar" onclick="toggleSidebar()" title="Show Sidebar">
    <i class="fas fa-chevron-right"></i>
</button>

<div class="layout" id="mainLayout">
    <aside class="sidebar" id="sidebarPanel">
        <button class="toggle-sidebar" onclick="toggleSidebar()" title="Hide Sidebar">
            <i class="fas fa-chevron-left"></i>
        </button>
        <div class="sb-profile">
            <div class="sb-avatar"><?= strtoupper(substr($admin_name,0,1)) ?></div>
            <div class="sb-name"><?= htmlspecialchars($admin_name) ?></div>
            <div class="sb-role">Administrator</div>
            <div class="sb-level">Admin Panel</div>
        </div>
        <div class="sb-section">Management</div>
        <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
        <a href="campaigns.php" class="nav-item"><i class="fas fa-calendar"></i> Manage Campaigns</a>
        <a href="manage-users.php" class="nav-item active"><i class="fas fa-users"></i> Manage Users</a>
        <a href="manage_modules.php" class="nav-item"><i class="fas fa-book"></i> Manage Modules</a>
        <a href="department-scores.php" class="nav-item"><i class="fas fa-chart-line"></i> Department Scores</a>
        <a href="risk-heatmap.php" class="nav-item"><i class="fas fa-fire"></i> Risk Heatmap</a>
        <a href="compliance-snapshot.php" class="nav-item"><i class="fas fa-check-circle"></i> Compliance Snapshot</a>
        <a href="export-report.php" class="nav-item"><i class="fas fa-file-export"></i> Export Report</a>
        <a href="incident-reports.php" class="nav-item"><i class="fas fa-exclamation-circle"></i> Incident Reports</a>
        <div class="sb-section">Account</div>
        <a href="../logout.php" class="nav-item nav-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <main class="main">
        <div class="page-hdr">
            <h1><i class="fas fa-users"></i> Manage Users</h1>
            <p>Create, edit, and manage user accounts and permissions across your organization.</p>
        </div>

        <!-- Bulk Import Section -->
        <div class="card import-section">
            <h3 style="margin-top:0;"><i class="fas fa-file-import"></i> Bulk Import Users (CSV or Excel)</h3>
            
            <!-- Info Box with Supported Columns -->
            <div class="info-box">
                <p style="margin: 0 0 0.75rem 0; font-weight: 600;">
                    <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>
                    Required & Optional Fields
                </p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 0.75rem;">
                    <div>
                        <p style="margin: 0 0 0.5rem 0; font-size: 12px; text-transform: uppercase; font-weight: 600;">Required</p>
                        <ul style="margin: 0; padding-left: 1.25rem; font-size: 13px;">
                            <li>Username</li>
                            <li>Full Name</li>
                            <li>Email</li>
                            <li>Role</li>
                        </ul>
                    </div>
                    <div>
                        <p style="margin: 0 0 0.5rem 0; font-size: 12px; text-transform: uppercase; font-weight: 600;">Optional</p>
                        <ul style="margin: 0; padding-left: 1.25rem; font-size: 13px;">
                            <li>Department or Department Code</li>
                            <li>Password (auto-generated if omitted)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <label for="user_file" class="btn btn-primary" style="cursor: pointer; margin: 0;">
                    <i class="fas fa-file-upload"></i> Choose File
                </label>
                <input type="file" id="user_file" name="user_file" accept=".csv,.xlsx" required style="display: none;" onchange="document.getElementById('file-name').textContent = this.files[0]?.name || 'No file chosen';">
                <span id="file-name" class="file-name">No file chosen</span>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload & Import
                </button>
            </form>

            <?php if ($import_message): ?>
            <div class="message message-<?= $import_type ?>">
                <?= $import_message ?>
            </div>
            <?php endif; ?>

            <?php if ($import_details): ?>
            <div class="details-log">
                <ul>
                    <?php foreach ($import_details as $detail): ?>
                        <li><?= $detail ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h2 style="margin:0;">All Users</h2>
                <a href="add_user.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add New User
                </a>
            </div>

            <?php if (empty($users)): ?>
                <p>No users found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['full_name'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['department_name'] ?: '—') ?></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($user['role']) ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td class="status-<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                            </td>
                            <td>
                                <div class="action-links">
                                    <a href="employee-profile.php?id=<?= $user['id'] ?>"><i class="fas fa-chart-line"></i> Profile</a>
                                    <a href="edit_user.php?id=<?= $user['id'] ?>"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="#" class="delete" onclick="return confirm('Delete user <?= htmlspecialchars($user['username']) ?>?')"><i class="fas fa-trash"></i> Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>

<script>
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

function toggleSidebar() {
    const sidebar = document.getElementById('sidebarPanel');
    const layout = document.getElementById('mainLayout');
    const showBtn = document.getElementById('showSidebarBtn');
    
    sidebar.classList.toggle('hidden');
    layout.classList.toggle('sidebar-hidden');
    showBtn.classList.toggle('visible');
    
    localStorage.setItem('sidebarHidden', sidebar.classList.contains('hidden') ? 'true' : 'false');
}

document.addEventListener('DOMContentLoaded', function() {
    const sidebarHidden = localStorage.getItem('sidebarHidden') === 'true';
    if (sidebarHidden) {
        const sidebar = document.getElementById('sidebarPanel');
        const layout = document.getElementById('mainLayout');
        const showBtn = document.getElementById('showSidebarBtn');
        
        sidebar.classList.add('hidden');
        layout.classList.add('sidebar-hidden');
        showBtn.classList.add('visible');
    }
});

document.querySelectorAll('.tb-dropdown-item').forEach(item => {
    item.addEventListener('click', function() {
        document.getElementById('profileDropdown').classList.remove('active');
    });
});
</script>
