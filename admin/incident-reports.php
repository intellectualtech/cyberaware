<?php
/**
 * Admin Incident Reports Management Dashboard
 */

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/theme-helper.php';

if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../index.php');
    exit;
}

$pdo = getDBConnection();
$admin_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();
$admin_name = $admin['full_name'] ?? 'Admin';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $incident_id = (int)($_POST['incident_id'] ?? 0);
    
    if ($action === 'update_status') {
        $status = trim($_POST['status'] ?? '');
        $resolution_notes = trim($_POST['resolution_notes'] ?? '');
        
        if (in_array($status, ['new', 'under_review', 'resolved', 'false_positive'])) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE incident_reports 
                    SET status = ?, reviewed_by = ?, reviewed_at = NOW(), resolution_notes = ?
                    WHERE id = ?
                ");
                $stmt->execute([$status, $admin_id, $resolution_notes, $incident_id]);
                $message = 'Incident status updated successfully.';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Error updating incident: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

$status_filter = $_GET['status'] ?? '';
$severity_filter = $_GET['severity'] ?? '';
$type_filter = $_GET['type'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where_clauses = [];
$params = [];

if (!empty($status_filter)) {
    $where_clauses[] = "ir.status = ?";
    $params[] = $status_filter;
}
if (!empty($severity_filter)) {
    $where_clauses[] = "ir.severity = ?";
    $params[] = $severity_filter;
}
if (!empty($type_filter)) {
    $where_clauses[] = "ir.incident_type = ?";
    $params[] = $type_filter;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM incident_reports ir $where_sql");
$count_stmt->execute($params);
$total = $count_stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

$stmt = $pdo->prepare("
    SELECT 
        ir.id, ir.user_id, ir.incident_type, ir.subject, ir.status, ir.severity,
        ir.reported_at, ir.reviewed_by, ir.reviewed_at,
        u.full_name, u.email, u.department_id,
        d.name as department
    FROM incident_reports ir
    JOIN users u ON ir.user_id = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    $where_sql
    ORDER BY ir.reported_at DESC
    LIMIT ? OFFSET ?
");

$params[] = $per_page;
$params[] = $offset;
$stmt->execute($params);
$incidents = $stmt->fetchAll();

$stats_stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
        SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as review_count,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
        SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_count
    FROM incident_reports
");
$stats = $stats_stmt->fetch();

$current_page = 'incidents';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Incident Reports — CyberAware</title>
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
.sidebar{background:#fff;border-right:1px solid var(--grey2);padding:28px 20px;display:flex;flex-direction:column;gap:8px;}
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
.filters{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:20px;}
.filter-group{display:flex;align-items:center;gap:8px;}
.filter-group label{font-size:13px;font-weight:600;color:var(--dark);}
.filter-group select{padding:8px 12px;border:1px solid var(--grey2);border-radius:8px;font-size:13px;background:#fff;color:var(--dark);}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;border:none;cursor:pointer;transition:.2s;}
.btn-primary{background:var(--orange);color:#fff;}
.btn-primary:hover{background:#e67e2f;}
.btn-view{background:var(--orange);color:#fff;padding:6px 12px;font-size:13px;}
.btn-view:hover{background:#e67e2f;}
table{width:100%;border-collapse:collapse;}
th,td{padding:16px;text-align:left;border-bottom:1px solid var(--grey2);font-size:14px;}
th{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#888;background:var(--grey);}
tbody tr:hover{background:var(--grey);}
.badge{display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:700;}
.badge-new{background:#e3f2fd;color:#1976d2;}
.badge-review{background:#fff3e0;color:#f57c00;}
.badge-resolved{background:#e8f5e9;color:#388e3c;}
.badge-false{background:#f3e5f5;color:#7b1fa2;}
.badge-low{background:#e8f5e9;color:#388e3c;}
.badge-medium{background:#fff3e0;color:#f57c00;}
.badge-high{background:#ffebee;color:#c62828;}
.badge-critical{background:var(--red);color:#fff;}
.alert{padding:16px;border-radius:10px;margin-bottom:20px;}
.alert-success{background:#d4edda;color:#155724;}
.alert-danger{background:#f8d7da;color:#721c24;}
.pagination{display:flex;gap:8px;justify-content:center;margin-top:20px;}
.pagination a,.pagination span{padding:8px 12px;border:1px solid var(--grey2);border-radius:8px;color:var(--dark);text-decoration:none;}
.pagination a:hover{background:var(--orange);color:#fff;border-color:var(--orange);}
.pagination .active{background:var(--orange);color:#fff;border-color:var(--orange);}
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
body.dark-mode .filter-group select{background:#2a2a3e;color:#fff;border-color:#333;}
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
        <a href="department-scores.php" class="nav-item"><i class="fas fa-chart-line"></i> Department Scores</a>
        <a href="risk-heatmap.php" class="nav-item"><i class="fas fa-fire"></i> Risk Heatmap</a>
        <a href="compliance-snapshot.php" class="nav-item"><i class="fas fa-check-circle"></i> Compliance Snapshot</a>
        <a href="export-report.php" class="nav-item"><i class="fas fa-file-export"></i> Export Report</a>
        <a href="incident-reports.php" class="nav-item active"><i class="fas fa-exclamation-circle"></i> Incident Reports</a>
        <div class="sb-section">Account</div>
        <a href="../logout.php" class="nav-item nav-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <main class="main">
        <div class="section-hdr">
            <h2>Incident Reports</h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Total Reports</div>
                <div class="kpi-value"><?= $stats['total'] ?></div>
                <div class="kpi-desc">All incidents</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">New Reports</div>
                <div class="kpi-value"><?= $stats['new_count'] ?></div>
                <div class="kpi-desc">Awaiting review</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Under Review</div>
                <div class="kpi-value"><?= $stats['review_count'] ?></div>
                <div class="kpi-desc">In progress</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Resolved</div>
                <div class="kpi-value"><?= $stats['resolved_count'] ?></div>
                <div class="kpi-desc">Completed</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter"></i> Filter Reports</h3>
            </div>
            <form method="GET" class="filters">
                <div class="filter-group">
                    <label>Status:</label>
                    <select name="status">
                        <option value="">All</option>
                        <option value="new" <?= $status_filter === 'new' ? 'selected' : '' ?>>New</option>
                        <option value="under_review" <?= $status_filter === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                        <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                        <option value="false_positive" <?= $status_filter === 'false_positive' ? 'selected' : '' ?>>False Positive</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Severity:</label>
                    <select name="severity">
                        <option value="">All</option>
                        <option value="low" <?= $severity_filter === 'low' ? 'selected' : '' ?>>Low</option>
                        <option value="medium" <?= $severity_filter === 'medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="high" <?= $severity_filter === 'high' ? 'selected' : '' ?>>High</option>
                        <option value="critical" <?= $severity_filter === 'critical' ? 'selected' : '' ?>>Critical</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Type:</label>
                    <select name="type">
                        <option value="">All</option>
                        <option value="phishing" <?= $type_filter === 'phishing' ? 'selected' : '' ?>>Phishing</option>
                        <option value="malware" <?= $type_filter === 'malware' ? 'selected' : '' ?>>Malware</option>
                        <option value="suspicious_link" <?= $type_filter === 'suspicious_link' ? 'selected' : '' ?>>Suspicious Link</option>
                        <option value="credential_theft" <?= $type_filter === 'credential_theft' ? 'selected' : '' ?>>Credential Theft</option>
                        <option value="social_engineering" <?= $type_filter === 'social_engineering' ? 'selected' : '' ?>>Social Engineering</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list"></i> Incident List</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Reporter</th>
                        <th>Subject</th>
                        <th>Type</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($incidents)): ?>
                        <tr><td colspan="8" style="text-align:center;color:#aaa;padding:40px 0;"><i class="fas fa-inbox" style="font-size:32px;margin-bottom:12px;display:block;"></i>No incidents found</td></tr>
                    <?php else: ?>
                        <?php foreach ($incidents as $incident): ?>
                            <tr>
                                <td><strong>#<?= $incident['id'] ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($incident['full_name']) ?></strong><br>
                                    <small style="color:#888;"><?= htmlspecialchars($incident['email']) ?></small>
                                </td>
                                <td><?= htmlspecialchars(substr($incident['subject'], 0, 40)) ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', $incident['incident_type'])) ?></td>
                                <td><span class="badge badge-<?= strtolower($incident['severity']) ?>"><?= ucfirst($incident['severity']) ?></span></td>
                                <td><span class="badge badge-<?= str_replace('_', '', $incident['status']) ?>"><?= ucfirst(str_replace('_', ' ', $incident['status'])) ?></span></td>
                                <td><?= date('M d, Y', strtotime($incident['reported_at'])) ?></td>
                                <td><a href="incident-detail.php?id=<?= $incident['id'] ?>" class="btn btn-view"><i class="fas fa-eye"></i> View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?= $status_filter ? '&status=' . urlencode($status_filter) : '' ?>">First</a>
                    <a href="?page=<?= $page - 1 ?><?= $status_filter ? '&status=' . urlencode($status_filter) : '' ?>">Previous</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?><?= $status_filter ? '&status=' . urlencode($status_filter) : '' ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $status_filter ? '&status=' . urlencode($status_filter) : '' ?>">Next</a>
                    <a href="?page=<?= $total_pages ?><?= $status_filter ? '&status=' . urlencode($status_filter) : '' ?>">Last</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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
