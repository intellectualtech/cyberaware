<?php
// admin/department-scores.php - Department Performance Overview

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/theme-helper.php';

require_admin();

$pdo = getDBConnection();
$admin_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();
$admin_name = $admin['full_name'] ?? 'Admin';

// Fetch all departments with aggregated stats
$stmt = $pdo->query("
    SELECT 
        d.id,
        d.name,
        d.code,
        COUNT(u.id) as total_employees,
        AVG(ts.final_score) as avg_score,
        COUNT(ta.id) as total_actions,
        SUM(CASE WHEN ta.action_type = 'click' THEN 1 ELSE 0 END) as clicks,
        SUM(CASE WHEN ta.action_type = 'report' THEN 1 ELSE 0 END) as reports,
        SUM(CASE WHEN ts.final_score < 60 THEN 1 ELSE 0 END) as high_risk_count
    FROM departments d
    LEFT JOIN users u ON d.id = u.department_id AND u.role = 'trainee' AND u.is_active = 1
    LEFT JOIN training_sessions ts ON u.id = ts.user_id
    LEFT JOIN training_actions ta ON ts.id = ta.session_id AND ts.module_id = 1
    GROUP BY d.id, d.name, d.code
    ORDER BY d.name
");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$current_page = 'department-scores';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Department Scores — CyberAware</title>
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
body.dark-mode .toggle-sidebar{background:var(--orange);}
body.dark-mode .toggle-sidebar:hover{background:#e67e2f;}
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
.section-hdr{margin-bottom:16px;}
.section-hdr h2{font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:700;color:var(--dark);}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:28px;}
.kpi-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.05);border-left:4px solid var(--orange);transition:.2s;text-decoration:none;color:inherit;display:block;}
.kpi-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.1);}
.kpi-label{font-size:13px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;}
.kpi-value{font-size:32px;font-weight:800;color:var(--dark);margin-bottom:6px;}
.kpi-desc{font-size:13px;color:#aaa;}
.card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.05);}
.card-header{margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--grey2);}
.card-title{font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;color:var(--dark);display:flex;align-items:center;gap:10px;}
.card-title i{color:var(--orange);}
table{width:100%;border-collapse:collapse;}
th,td{padding:16px;text-align:left;border-bottom:1px solid var(--grey2);font-size:14px;}
th{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#888;background:var(--grey);}
tbody tr:hover{background:var(--grey);}
.score-badge{display:inline-block;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:700;}
.score-high{background:#E8F5E9;color:#10B981;}
.score-medium{background:#FFF3E0;color:#F59E0B;}
.score-low{background:#FFEBEE;color:#DC2626;}
.rate-good{color:#10B981;font-weight:600;}
.rate-bad{color:#EF4444;font-weight:600;}
.info-box{background:var(--ol);border-left:4px solid var(--orange);padding:16px 20px;border-radius:10px;margin-top:24px;}
.info-box p{font-size:14px;color:var(--dark);margin-bottom:8px;}
.info-box p:last-child{margin-bottom:0;}
.info-box strong{color:var(--orange);}
body.dark-mode{background:#1A1A2E;color:#fff;}
body.dark-mode .topbar{background:#0f0f1e;}
body.dark-mode .sidebar{background:#1f1f2e;border-right-color:#333;}
body.dark-mode .nav-item{color:#aaa;}
body.dark-mode .nav-item:hover,body.dark-mode .nav-item.active{background:rgba(255,140,66,.15);color:var(--orange);}
body.dark-mode .sb-profile{background:rgba(255,140,66,.1);}
body.dark-mode .sb-name{color:#fff;}
body.dark-mode .sb-role{color:#aaa;}
body.dark-mode .main{background:#1A1A2E;}
body.dark-mode .card{background:#2a2a3e;box-shadow:0 2px 8px rgba(0,0,0,.3);}
body.dark-mode .card-title{color:#fff;}
body.dark-mode .kpi-card{background:#2a2a3e;box-shadow:0 2px 8px rgba(0,0,0,.3);}
body.dark-mode .kpi-card:hover{box-shadow:0 12px 32px rgba(0,0,0,.5);}
body.dark-mode .kpi-value{color:#fff;}
body.dark-mode .kpi-label{color:#888;}
body.dark-mode .kpi-desc{color:#aaa;}
body.dark-mode .section-hdr h2{color:#fff;}
body.dark-mode table{color:#ddd;}
body.dark-mode th{background:#333;color:#aaa;}
body.dark-mode tbody tr:hover{background:#333;}
body.dark-mode .card-header{border-bottom-color:#333;}
body.dark-mode .tb-dropdown{background:#2a2a3e;}
body.dark-mode .tb-dropdown-header{border-bottom-color:#333;}
body.dark-mode .tb-dropdown-name{color:#fff;}
body.dark-mode .tb-dropdown-item{color:#fff;}
body.dark-mode .tb-dropdown-item:hover{background:#333;color:var(--orange);}
body.dark-mode .info-box{background:rgba(255,140,66,.1);}
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
        <a href="manage-users.php" class="nav-item"><i class="fas fa-users"></i> Manage Users</a>
        <a href="manage_modules.php" class="nav-item"><i class="fas fa-book"></i> Manage Modules</a>
        <a href="department-scores.php" class="nav-item active"><i class="fas fa-chart-line"></i> Department Scores</a>
        <a href="risk-heatmap.php" class="nav-item"><i class="fas fa-fire"></i> Risk Heatmap</a>
        <a href="compliance-snapshot.php" class="nav-item"><i class="fas fa-check-circle"></i> Compliance Snapshot</a>
        <a href="export-report.php" class="nav-item"><i class="fas fa-file-export"></i> Export Report</a>
        <a href="incident-reports.php" class="nav-item"><i class="fas fa-exclamation-circle"></i> Incident Reports</a>
        <div class="sb-section">Account</div>
        <a href="../logout.php" class="nav-item nav-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <main class="main">
        <div class="section-hdr">
            <h2>Department Performance Scores</h2>
        </div>

        <?php 
        $total_depts = count($departments);
        $total_employees = array_sum(array_column($departments, 'total_employees'));
        $avg_dept_score = $total_depts > 0 ? round(array_sum(array_column($departments, 'avg_score')) / $total_depts) : 0;
        $total_high_risk = array_sum(array_column($departments, 'high_risk_count'));
        ?>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Total Departments</div>
                <div class="kpi-value"><?= $total_depts ?></div>
                <div class="kpi-desc">Departments tracked</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Total Employees</div>
                <div class="kpi-value"><?= number_format($total_employees) ?></div>
                <div class="kpi-desc">Across all departments</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Avg Score</div>
                <div class="kpi-value"><?= $avg_dept_score ?>%</div>
                <div class="kpi-desc">Department average</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">High-Risk Users</div>
                <div class="kpi-value"><?= $total_high_risk ?></div>
                <div class="kpi-desc">Below 60% score</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar"></i> Performance Breakdown</h3>
            </div>
            <?php if (empty($departments)): ?>
                <p style="text-align:center;color:#aaa;padding:40px 0;"><i class="fas fa-building" style="font-size:36px;margin-bottom:12px;display:block;"></i>No departments found</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Code</th>
                        <th>Employees</th>
                        <th>Avg Score</th>
                        <th>Click Rate</th>
                        <th>Report Rate</th>
                        <th>High-Risk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $dept): 
                        $employees = $dept['total_employees'] ?? 0;
                        $avg_score = $dept['avg_score'] !== null ? round($dept['avg_score']) : 0;
                        $total_actions = $dept['total_actions'] ?? 0;
                        $click_rate = $total_actions > 0 ? round(($dept['clicks'] ?? 0) / $total_actions * 100, 1) : 0;
                        $report_rate = $total_actions > 0 ? round(($dept['reports'] ?? 0) / $total_actions * 100, 1) : 0;
                        $high_risk = $dept['high_risk_count'] ?? 0;
                        $score_class = $avg_score >= 80 ? 'high' : ($avg_score >= 60 ? 'medium' : 'low');
                        $click_class = $click_rate > 15 ? 'rate-bad' : 'rate-good';
                        $report_class = $report_rate > 20 ? 'rate-good' : 'rate-bad';
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($dept['name']) ?></strong></td>
                        <td><?= htmlspecialchars($dept['code'] ?? '-') ?></td>
                        <td><?= number_format($employees) ?></td>
                        <td><span class="score-badge score-<?= $score_class ?>"><?= $avg_score ?>%</span></td>
                        <td class="<?= $click_class ?>"><i class="fas fa-<?= $click_rate > 15 ? 'arrow-up' : 'arrow-down' ?>"></i> <?= $click_rate ?>%</td>
                        <td class="<?= $report_class ?>"><i class="fas fa-<?= $report_rate > 20 ? 'arrow-up' : 'arrow-down' ?>"></i> <?= $report_rate ?>%</td>
                        <td><?php if ($high_risk > 0): ?><span style="color:var(--red);font-weight:600;"><i class="fas fa-exclamation-circle"></i> <?= $high_risk ?></span><?php else: ?><span style="color:var(--green);"><i class="fas fa-check-circle"></i> 0</span><?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            <div class="info-box">
                <p><strong>Understanding the Metrics:</strong></p>
                <p><i class="fas fa-info-circle"></i> <strong>High-Risk Users:</strong> Employees with an average score below 60%</p>
                <p><i class="fas fa-info-circle"></i> <strong>Click Rate:</strong> Percentage of phishing emails clicked (lower is better)</p>
                <p><i class="fas fa-info-circle"></i> <strong>Report Rate:</strong> Percentage of phishing emails reported (higher is better)</p>
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
