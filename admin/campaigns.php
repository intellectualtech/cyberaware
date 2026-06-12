<?php
// admin/campaigns.php - Campaign Management
// Updated: January 2026 - Working delete with custom modal

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/theme-helper.php';

require_admin();

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

// Handle POST actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Status change ───────────────────────────────────────
    if ($action === 'update_status') {
        $campaign_id = (int)($_POST['campaign_id'] ?? 0);
        $new_status  = $_POST['status'] ?? 'draft';

        if ($campaign_id > 0 && in_array($new_status, ['draft','active','completed','archived'])) {
            $stmt = $pdo->prepare("UPDATE campaigns SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $campaign_id]);
            $message = "Campaign status updated successfully";
            $message_type = "success";
        }
    }

    // ── Delete campaign ─────────────────────────────────────
    elseif ($action === 'delete') {
        $campaign_id = (int)($_POST['campaign_id'] ?? 0);
        if ($campaign_id > 0) {
            try {
                $pdo->beginTransaction();

                // Delete related department assignments
                $pdo->prepare("DELETE FROM campaign_departments WHERE campaign_id = ?")
                    ->execute([$campaign_id]);

                // Delete the campaign itself
                $pdo->prepare("DELETE FROM campaigns WHERE id = ?")
                    ->execute([$campaign_id]);

                $pdo->commit();

                $message = "Campaign deleted successfully";
                $message_type = "success";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Cannot delete this campaign.<br>Reason: " . htmlspecialchars($e->getMessage());
                $message_type = "danger";
            }
        }
    }
}

// Success message from create/edit pages
if (isset($_GET['success'])) {
    $message = htmlspecialchars($_GET['msg'] ?? "Operation completed successfully");
    $message_type = "success";
}

// Get all campaigns with target info
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        u.username AS creator_name,
        GROUP_CONCAT(d.name ORDER BY d.name SEPARATOR ', ') AS target_departments
    FROM campaigns c
    JOIN users u ON c.created_by = u.id
    LEFT JOIN campaign_departments cd ON cd.campaign_id = c.id
    LEFT JOIN departments d ON d.id = cd.department_id
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Campaigns — CyberAware</title>
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
.btn-edit{background:#3b82f6;color:#fff;padding:6px 12px;font-size:12px;}
.btn-edit:hover{background:#2563eb;}
.btn-delete{background:var(--red);color:#fff;padding:6px 12px;font-size:12px;}
.btn-delete:hover{background:#dc2626;}
.message{padding:16px;border-radius:10px;margin:16px 0;font-weight:600;font-size:14px;display:flex;align-items:center;gap:8px;}
.message-success{background:#ecfdf5;color:#065f46;border-left:4px solid var(--green);}
.message-danger{background:#fef2f2;color:#991b1b;border-left:4px solid var(--red);}
table{width:100%;border-collapse:collapse;margin-top:16px;}
th,td{padding:12px 14px;text-align:left;border-bottom:1px solid var(--grey2);font-size:14px;}
th{background:var(--grey);font-weight:700;color:var(--dark);}
tbody tr:hover{background:var(--ol);}
.status-badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase;}
.status-draft{background:#e5e7eb;color:#4b5563;}
.status-active{background:#d1fae5;color:#065f46;}
.status-completed{background:#dbeafe;color:#1d4ed8;}
.status-archived{background:#fef3c7;color:#92400e;}
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:2000;align-items:center;justify-content:center;}
.modal-content{background:#fff;border-radius:12px;width:90%;max-width:440px;padding:2rem;box-shadow:0 20px 25px -5px rgba(0,0,0,.2);}
.modal-header{font-size:1.4rem;font-weight:600;margin-bottom:1rem;color:var(--dark);display:flex;align-items:center;gap:12px;}
.modal-body{color:#666;margin-bottom:1.75rem;line-height:1.6;}
.modal-footer{display:flex;gap:1rem;justify-content:flex-end;}
.btn-modal{padding:10px 20px;border-radius:8px;font-weight:600;cursor:pointer;border:none;transition:.2s;}
.btn-cancel{background:var(--grey2);color:var(--dark);}
.btn-cancel:hover{background:#ddd;}
.btn-delete-confirm{background:var(--red);color:#fff;}
.btn-delete-confirm:hover{background:#dc2626;}
.empty-state{padding:5rem 1rem;text-align:center;color:#6b7280;}
.empty-state i{font-size:3.8rem;color:#d1d5db;margin-bottom:1.5rem;}
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
body.dark-mode table{background:#2a2a3e;color:#fff;}
body.dark-mode th{background:#1f1f2e;color:#fff;}
body.dark-mode th,body.dark-mode td{border-bottom-color:#333;}
body.dark-mode tbody tr:hover{background:#333;}
body.dark-mode .tb-dropdown{background:#2a2a3e;}
body.dark-mode .tb-dropdown-header{border-bottom-color:#333;}
body.dark-mode .tb-dropdown-name{color:#fff;}
body.dark-mode .tb-dropdown-item{color:#fff;}
body.dark-mode .tb-dropdown-item:hover{background:#333;color:var(--orange);}
body.dark-mode .modal-content{background:#2a2a3e;color:#fff;}
body.dark-mode .modal-header{color:#fff;}
body.dark-mode .modal-body{color:#aaa;}
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
        <a href="campaigns.php" class="nav-item active"><i class="fas fa-calendar"></i> Manage Campaigns</a>
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
        <div class="page-hdr">
            <h1><i class="fas fa-calendar"></i> Manage Campaigns</h1>
            <p>Create, launch, and track phishing simulations and security awareness campaigns.</p>
        </div>

        <?php if ($message): ?>
        <div class="message message-<?= $message_type ?>">
            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= $message ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-list-ul"></i> All Campaigns</h2>
                <a href="campaign-create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Campaign
                </a>
            </div>

            <?php if (empty($campaigns)): ?>
                <div class="empty-state">
                    <i class="far fa-folder-open"></i>
                    <p style="margin:1.25rem 0 0.75rem; font-size:1.1rem;">No campaigns have been created yet</p>
                    <p><a href="campaign-create.php" style="color:var(--orange)">Create your first campaign →</a></p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th>Target</th>
                                <th>Date Range</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th style="width:160px; text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($campaigns as $c): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600;"><?= htmlspecialchars($c['name']) ?></div>
                                    <?php if ($c['description']): ?>
                                    <div style="color:#6b7280; font-size:0.9rem; margin-top:0.25rem;">
                                        <?= htmlspecialchars(substr($c['description'],0,85)) . (strlen($c['description'])>85 ? '...' : '') ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($c['target_all']): ?>
                                        <strong style="color:#059669;">All Employees</strong>
                                    <?php elseif ($c['target_departments']): ?>
                                        <?= htmlspecialchars($c['target_departments']) ?>
                                    <?php else: ?>
                                        <em style="color:#9ca3af;">Specific users only</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    if ($c['start_date'] && $c['end_date']) {
                                        echo date('M j, Y', strtotime($c['start_date'])) . ' – ' .
                                             date('M j, Y', strtotime($c['end_date']));
                                    } elseif ($c['start_date']) {
                                        echo 'From ' . date('M j, Y', strtotime($c['start_date']));
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $c['status'] ?>">
                                        <?= ucfirst($c['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($c['creator_name']) ?></td>
                                <td style="text-align:center; white-space:nowrap;">
                                    <a href="campaign-edit.php?id=<?= $c['id'] ?>" class="btn btn-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-delete"
                                            onclick="showDeleteModal(<?= $c['id'] ?>, <?= htmlspecialchars(json_encode($c['name']), ENT_QUOTES) ?>)"
                                            title="Delete Campaign">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <i class="fas fa-exclamation-triangle"></i>
            Delete Campaign
        </div>
        <div class="modal-body">
            Are you sure you want to permanently delete<br>
            <strong id="campaignName" style="color:var(--dark);"></strong>?<br><br>
            <span style="color:var(--red); font-weight:600;">This action cannot be undone.</span>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <form id="deleteForm" method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="campaign_id" id="deleteCampaignId">
                <button type="submit" class="btn-modal btn-delete-confirm">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            </form>
        </div>
    </div>
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

function showDeleteModal(id, name) {
    document.getElementById('campaignName').textContent = name;
    document.getElementById('deleteCampaignId').value = id;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>
