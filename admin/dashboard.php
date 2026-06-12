<?php
ob_start();

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

try {
    $pdo = getDBConnection();
    $admin_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    $admin_name = $admin['full_name'] ?? 'Admin';

    // Fetch Real Data from Database
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'trainee' AND is_active = 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_employees = $row ? (int)$row['total'] : 0;

    $stmt = $pdo->query("SELECT COUNT(*) as active FROM campaigns WHERE status = 'active'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $active_campaigns = $row ? (int)$row['active'] : 0;

    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_actions,
            SUM(CASE WHEN action_type = 'clicked' THEN 1 ELSE 0 END) as clicks,
            SUM(CASE WHEN action_type = 'reported' THEN 1 ELSE 0 END) as reports
        FROM training_actions ta
        JOIN training_sessions ts ON ta.session_id = ts.id
        WHERE ts.module_id = 1
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_phish_actions = $row ? (int)$row['total_actions'] : 0;
    $clicks = $row ? (int)$row['clicks'] : 0;
    $reports = $row ? (int)$row['reports'] : 0;

    $avg_click_rate = $total_phish_actions > 0 ? round(($clicks / $total_phish_actions) * 100, 1) : 0;
    $avg_report_rate = $total_phish_actions > 0 ? round(($reports / $total_phish_actions) * 100, 1) : 0;

    $stmt = $pdo->query("SELECT COUNT(*) as high_risk FROM user_training_summary WHERE risk_score > 60");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $high_risk_users = $row ? (int)$row['high_risk'] : 0;

    $stmt = $pdo->query("
        SELECT 
            c.name, 
            c.end_date,
            COUNT(DISTINCT ts.user_id) as completed_users
        FROM campaigns c 
        LEFT JOIN training_sessions ts ON ts.campaign_id = c.id AND ts.completed_at IS NOT NULL
        WHERE c.status = 'active' 
        GROUP BY c.id
        ORDER BY c.created_at DESC 
        LIMIT 1
    ");
    $active_campaign_row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($active_campaign_row) {
        $campaign_name = $active_campaign_row['name'];
        $campaign_completed_users = (int)$active_campaign_row['completed_users'];
        $campaign_end_date = $active_campaign_row['end_date'] 
            ? date('F j, Y', strtotime($active_campaign_row['end_date'])) 
            : 'Not set';
    } else {
        $campaign_name = 'No active campaign';
        $campaign_completed_users = 0;
        $campaign_end_date = 'Not set';
    }

    $campaign_progress = $total_employees > 0 
        ? round(($campaign_completed_users / $total_employees) * 100) 
        : 0;
    
} catch(Exception $e) {
    $admin_name = $_SESSION['full_name'] ?? 'Admin';
    $total_employees = 0;
    $active_campaigns = 0;
    $avg_click_rate = 0;
    $avg_report_rate = 0;
    $high_risk_users = 0;
    $campaign_name = 'No active campaign';
    $campaign_completed_users = 0;
    $campaign_end_date = 'Not set';
    $campaign_progress = 0;
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard — CyberAware</title>
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
.layout{display:grid;grid-template-columns:280px 1fr;min-height:calc(100vh - 64px);transition:.3s ease;}
.layout.sidebar-hidden{grid-template-columns:1fr;}
.sidebar{background:#fff;border-right:1px solid var(--grey2);padding:28px 20px;display:flex;flex-direction:column;gap:8px;transition:.3s ease;position:relative;}
.sidebar.hidden{display:none;}
.toggle-sidebar{position:absolute;top:20px;right:20px;background:var(--orange);color:#fff;border:none;width:36px;height:36px;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;transition:.2s;}
.toggle-sidebar:hover{background:#e67e2f;}
.show-sidebar{position:fixed;left:20px;top:80px;background:var(--orange);color:#fff;border:none;width:40px;height:40px;border-radius:50%;cursor:pointer;display:none;align-items:center;justify-content:center;font-size:18px;box-shadow:0 4px 12px rgba(255,140,66,.3);z-index:95;transition:.2s;}
.show-sidebar:hover{background:#e67e2f;transform:scale(1.1);}
.show-sidebar.visible{display:flex;}
body.dark-mode .toggle-sidebar{background:var(--orange);}
body.dark-mode .toggle-sidebar:hover{background:#e67e2f;}
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
.hero-strip{background:linear-gradient(135deg,var(--dark) 0%,#2d2d4e 100%);border-radius:20px;padding:28px 32px;color:#fff;margin-bottom:28px;}
.hs-left h1{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;margin-bottom:6px;}
.hs-left p{color:rgba(255,255,255,.65);font-size:15px;}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:28px;}
.kpi-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.05);border-left:4px solid var(--orange);transition:.2s;text-decoration:none;color:inherit;display:block;}
.kpi-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.1);}
.kpi-label{font-size:13px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;}
.kpi-value{font-size:32px;font-weight:800;color:var(--dark);margin-bottom:6px;}
.kpi-desc{font-size:13px;color:#aaa;}
.quick-links{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;}
.quick-link{background:#fff;border-radius:16px;padding:24px;text-decoration:none;color:inherit;box-shadow:0 2px 8px rgba(0,0,0,.05);transition:.2s;display:flex;flex-direction:column;align-items:center;text-align:center;gap:12px;}
.quick-link:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.1);}
.quick-link-icon{font-size:32px;color:var(--orange);}
.quick-link-title{font-weight:700;font-size:15px;}
.section-hdr{margin-bottom:16px;}
.section-hdr h2{font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:700;color:var(--dark);}
.campaign-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.05);border-left:4px solid var(--orange);}
.campaign-header{margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--grey2);}
.campaign-title{font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;color:var(--dark);margin-bottom:6px;display:flex;align-items:center;gap:10px;}
.campaign-title i{color:var(--orange);}
.campaign-subtitle{font-size:13px;color:#888;}
.info-row{display:flex;justify-content:space-between;align-items:center;padding:16px 0;border-bottom:1px solid var(--grey2);}
.info-row:last-child{border-bottom:none;}
.info-label{font-size:13px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.05em;}
.info-value{font-size:15px;color:var(--dark);font-weight:600;}
.progress-section{margin-top:20px;padding-top:20px;border-top:1px solid var(--grey2);}
.progress-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;}
.progress-label{font-size:13px;color:#888;font-weight:700;}
.progress-percent{font-size:18px;font-weight:800;color:var(--orange);}
.progress-bar{width:100%;height:8px;background:var(--grey2);border-radius:4px;overflow:hidden;}
.progress-fill{height:100%;background:var(--orange);border-radius:4px;transition:width .3s ease;}
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
body.dark-mode .campaign-card{background:#2a2a3e;box-shadow:0 2px 8px rgba(0,0,0,.3);border-left-color:var(--orange);}
body.dark-mode .campaign-header{border-bottom-color:#333;}
body.dark-mode .campaign-title{color:#fff;}
body.dark-mode .campaign-subtitle{color:#888;}
body.dark-mode .info-row{border-bottom-color:#333;}
body.dark-mode .info-label{color:#888;}
body.dark-mode .info-value{color:#fff;}
body.dark-mode .progress-section{border-top-color:#333;}
body.dark-mode .progress-label{color:#888;}
body.dark-mode .progress-percent{color:var(--orange);}
body.dark-mode .progress-bar{background:#333;}
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

<button class="show-sidebar" id="showSidebarBtn" onclick="toggleSidebar()" title="Show sidebar">
    <i class="fas fa-chevron-right"></i>
</button>

<div class="layout" id="mainLayout">
    <aside class="sidebar" id="sidebarPanel">
        <button class="toggle-sidebar" onclick="toggleSidebar()" title="Hide sidebar">
            <i class="fas fa-chevron-left"></i>
        </button>
        <div class="sb-profile">
            <div class="sb-avatar"><?= strtoupper(substr($admin_name,0,1)) ?></div>
            <div class="sb-name"><?= htmlspecialchars($admin_name) ?></div>
            <div class="sb-role">Administrator</div>
            <div class="sb-level">Admin Panel</div>
        </div>
        <div class="sb-section">Management</div>
        <a href="dashboard.php" class="nav-item active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="campaigns.php" class="nav-item"><i class="fas fa-calendar"></i> Manage Campaigns</a>
        <a href="manage-users.php" class="nav-item"><i class="fas fa-users"></i> Manage Users</a>
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
        <div class="hero-strip">
            <div class="hs-left">
                <h1>Welcome, <?= htmlspecialchars(explode(' ',$admin_name)[0]) ?>! 👋</h1>
                <p>Monitor security awareness and manage threat campaigns across your organization.</p>
            </div>
        </div>

        <div class="kpi-grid">
            <a href="manage-users.php" class="kpi-card">
                <div class="kpi-label">Protected Users</div>
                <div class="kpi-value"><?= number_format($total_employees) ?></div>
                <div class="kpi-desc">Active threat-aware users</div>
            </a>
            <a href="campaigns.php" class="kpi-card">
                <div class="kpi-label">Active Exercises</div>
                <div class="kpi-value"><?= $active_campaigns ?></div>
                <div class="kpi-desc">Live security simulations</div>
            </a>
            <a href="incident-reports.php" class="kpi-card">
                <div class="kpi-label">Threat Fall Rate</div>
                <div class="kpi-value"><?= $avg_click_rate ?>%</div>
                <div class="kpi-desc">Users vulnerable to phishing</div>
            </a>
            <a href="department-scores.php" class="kpi-card">
                <div class="kpi-label">Defense Rate</div>
                <div class="kpi-value"><?= $avg_report_rate ?>%</div>
                <div class="kpi-desc">Users reporting threats</div>
            </a>
            <a href="manage-users.php" class="kpi-card">
                <div class="kpi-label">Critical Risk</div>
                <div class="kpi-value"><?= $high_risk_users ?></div>
                <div class="kpi-desc">Users requiring immediate training</div>
            </a>
        </div>

        <div class="section-hdr">
            <h2>Quick Access</h2>
        </div>
        <div class="quick-links">
            <a href="campaigns.php" class="quick-link">
                <div class="quick-link-icon"><i class="fas fa-virus"></i></div>
                <div class="quick-link-title">Manage Campaigns</div>
            </a>
            <a href="department-scores.php" class="quick-link">
                <div class="quick-link-icon"><i class="fas fa-chart-bar"></i></div>
                <div class="quick-link-title">Risk Assessment</div>
            </a>
            <a href="export-report.php" class="quick-link">
                <div class="quick-link-icon"><i class="fas fa-file-contract"></i></div>
                <div class="quick-link-title">Export Reports</div>
            </a>
        </div>

        <!-- CAMPAIGN CARD -->
        <div class="section-hdr" style="margin-top: 32px;">
            <h2>Active Threat Simulation</h2>
        </div>
        <div class="campaign-card">
            <div class="campaign-header">
                <h3 class="campaign-title"><i class="fas fa-crosshairs"></i>Current Security Exercise</h3>
                <p class="campaign-subtitle">Performance metrics and completion tracking</p>
            </div>

            <div class="info-row">
                <span class="info-label">Campaign Name:</span>
                <span class="info-value"><?= htmlspecialchars($campaign_name) ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Simulation End Date:</span>
                <span class="info-value"><?= htmlspecialchars($campaign_end_date) ?></span>
            </div>

            <div class="progress-section">
                <div class="progress-header">
                    <span class="progress-label">User Completion Rate</span>
                    <span class="progress-percent"><?= $campaign_progress ?>%</span>
                </div>
                <div style="font-size:13px;color:#888;margin-bottom:10px;"><?= $campaign_completed_users ?> of <?= number_format($total_employees) ?> users trained</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width:<?= $campaign_progress ?>%"></div>
                </div>
            </div>
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

function toggleSidebar() {
    const sidebar = document.getElementById('sidebarPanel');
    const layout = document.getElementById('mainLayout');
    const showBtn = document.getElementById('showSidebarBtn');
    
    // Toggle sidebar visibility
    sidebar.classList.toggle('hidden');
    layout.classList.toggle('sidebar-hidden');
    
    // Toggle show button visibility
    showBtn.classList.toggle('visible');
    
    // Save state to localStorage
    const isHidden = sidebar.classList.contains('hidden');
    localStorage.setItem('sidebarHidden', isHidden);
}

// Restore sidebar state on page load
window.addEventListener('DOMContentLoaded', function() {
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
</script>
