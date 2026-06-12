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

// Handle theme change via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_theme') {
    $theme = isset($_POST['theme']) ? $_POST['theme'] : 'light';
    setThemeSession($theme);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'theme' => $theme]);
    exit;
}

try {
    $pdo = getDBConnection();
    $admin_id = $_SESSION['user_id'];

    // Get super admin info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    $admin_name = $admin['full_name'] ?? 'Super Admin';

    // Get platform KPIs
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM organizations WHERE is_active = 1");
    $stmt->execute();
    $org_count = $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
    $stmt->execute();
    $user_count = $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM training_modules");
    $stmt->execute();
    $module_count = $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'active'");
    $stmt->execute();
    $active_subs = $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(price), 0) as total FROM subscriptions WHERE status = 'active'");
    $stmt->execute();
    $revenue = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT u.id) as total_users,
            SUM(CASE WHEN ts.completed_at IS NOT NULL THEN 1 ELSE 0 END) as completed,
            COUNT(ts.id) as total_sessions
        FROM users u
        LEFT JOIN training_sessions ts ON u.id = ts.user_id
        WHERE u.role = 'trainee'
    ");
    $stmt->execute();
    $training_stats = $stmt->fetch();
    
} catch(Exception $e) {
    $admin_name = $_SESSION['full_name'] ?? 'Super Admin';
    $org_count = 0;
    $user_count = 0;
    $module_count = 0;
    $active_subs = 0;
    $revenue = 0;
    $training_stats = ['total_users' => 0, 'completed' => 0];
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Super Admin Dashboard — CyberAware</title>
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
/* TOPBAR */
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
/* LAYOUT */
.layout{display:grid;grid-template-columns:280px 1fr;min-height:calc(100vh - 64px);}
/* SIDEBAR */
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
/* MAIN */
.main{padding:32px;padding-bottom:100px;}
/* HERO STRIP */
.hero-strip{background:linear-gradient(135deg,var(--dark) 0%,#2d2d4e 100%);border-radius:20px;padding:28px 32px;color:#fff;margin-bottom:28px;}
.hs-left h1{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;margin-bottom:6px;}
.hs-left p{color:rgba(255,255,255,.65);font-size:15px;}
/* KPI GRID */
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:28px;}
.kpi-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.05);border-left:4px solid var(--orange);transition:.2s;}
.kpi-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.1);}
.kpi-label{font-size:13px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;}
.kpi-value{font-size:32px;font-weight:800;color:var(--dark);margin-bottom:6px;}
.kpi-desc{font-size:13px;color:#aaa;}
/* SECTION TITLE */
.section-hdr{margin-bottom:16px;}
.section-hdr h2{font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:700;color:var(--dark);}
/* QUICK LINKS */
.quick-links{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;}
.quick-link{background:#fff;border-radius:16px;padding:24px;text-decoration:none;color:inherit;box-shadow:0 2px 8px rgba(0,0,0,.05);transition:.2s;display:flex;flex-direction:column;align-items:center;text-align:center;gap:12px;}
.quick-link:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.1);}
.quick-link-icon{font-size:32px;color:var(--orange);}
.quick-link-title{font-weight:700;font-size:15px;}
/* DARK MODE */
body.dark-mode{background:#1A1A2E;color:#fff;}
body.dark-mode .topbar{background:#0f0f1e;}
body.dark-mode .sidebar{background:#1f1f2e;border-right-color:#333;}
body.dark-mode .nav-item{color:#aaa;}
body.dark-mode .nav-item:hover,body.dark-mode .nav-item.active{background:rgba(255,140,66,.15);color:var(--orange);}
body.dark-mode .sb-profile{background:rgba(255,140,66,.1);}
body.dark-mode .sb-name{color:#fff;}
body.dark-mode .sb-role{color:#aaa;}
body.dark-mode .main{background:#1A1A2E;}
body.dark-mode .hero-strip{background:linear-gradient(135deg,#0f0f1e 0%,#1a1a2e 100%);}
body.dark-mode .hs-left h1{color:#fff;}
body.dark-mode .hs-left p{color:rgba(255,255,255,.65);}
body.dark-mode .kpi-card{background:#2a2a3e;box-shadow:0 2px 8px rgba(0,0,0,.3);}
body.dark-mode .kpi-card:hover{box-shadow:0 12px 32px rgba(0,0,0,.5);}
body.dark-mode .kpi-value{color:#fff;}
body.dark-mode .kpi-label{color:#888;}
body.dark-mode .kpi-desc{color:#aaa;}
body.dark-mode .section-hdr h2{color:#fff;}
body.dark-mode .quick-link{background:#2a2a3e;box-shadow:0 2px 8px rgba(0,0,0,.3);}
body.dark-mode .quick-link:hover{box-shadow:0 12px 32px rgba(0,0,0,.5);}
body.dark-mode .quick-link-title{color:#fff;}
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
                            <div class="tb-dropdown-email"><?= htmlspecialchars($admin['email'] ?? '') ?></div>
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
        <a href="dashboard.php" class="nav-item active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="organizations.php" class="nav-item"><i class="fas fa-building"></i> Organizations</a>
        <a href="subscriptions.php" class="nav-item"><i class="fas fa-credit-card"></i> Subscriptions</a>
        <a href="training-content.php" class="nav-item"><i class="fas fa-book"></i> Training Content</a>
        <div class="sb-section">Account</div>
        <a href="../logout.php" class="nav-item nav-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <!-- MAIN -->
    <main class="main">
        <!-- HERO -->
        <div class="hero-strip">
            <div class="hs-left">
                <h1>Welcome, <?= htmlspecialchars(explode(' ',$admin_name)[0]) ?>! 👋</h1>
                <p>Manage platform subscriptions and training content for all organizations.</p>
            </div>
        </div>

        <!-- KPI CARDS -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Active Organizations</div>
                <div class="kpi-value"><?= $org_count ?></div>
                <div class="kpi-desc">Client organizations</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Total Users</div>
                <div class="kpi-value"><?= $user_count ?></div>
                <div class="kpi-desc">Across all orgs</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Training Modules</div>
                <div class="kpi-value"><?= $module_count ?></div>
                <div class="kpi-desc">Available modules</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Active Subscriptions</div>
                <div class="kpi-value"><?= $active_subs ?></div>
                <div class="kpi-desc">Active plans</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Monthly Revenue</div>
                <div class="kpi-value">$<?= number_format($revenue, 0) ?></div>
                <div class="kpi-desc">Current revenue</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Completion Rate</div>
                <div class="kpi-value"><?= $training_stats['total_users'] > 0 ? round(($training_stats['completed'] / $training_stats['total_users']) * 100) : 0 ?>%</div>
                <div class="kpi-desc">Training completion</div>
            </div>
        </div>

        <!-- QUICK ACCESS -->
        <div class="section-hdr">
            <h2>Quick Access</h2>
        </div>
        <div class="quick-links">
            <a href="organizations.php" class="quick-link">
                <div class="quick-link-icon"><i class="fas fa-building"></i></div>
                <div class="quick-link-title">Organizations</div>
            </a>
            <a href="subscriptions.php" class="quick-link">
                <div class="quick-link-icon"><i class="fas fa-credit-card"></i></div>
                <div class="quick-link-title">Subscriptions</div>
            </a>
            <a href="training-content.php" class="quick-link">
                <div class="quick-link-icon"><i class="fas fa-book"></i></div>
                <div class="quick-link-title">Training Content</div>
            </a>
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

document.querySelectorAll('.tb-dropdown-item').forEach(item => {
    item.addEventListener('click', function() {
        document.getElementById('profileDropdown').classList.remove('active');
    });
});
</script>
