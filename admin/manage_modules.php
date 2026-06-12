<?php
// admin/manage_modules.php - Manage Training Module Content (Videos + Rich Content)

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

// Ensure upload directory exists
$upload_dir = '../assets/videos/modules/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module_id = (int)($_POST['module_id'] ?? 0);
    $content_html = $_POST['content_html'] ?? '';
    $video_file = $_FILES['module_video'] ?? null;

    if ($module_id <= 0) {
        $message = "Invalid module selected.";
        $message_type = 'danger';
    } else {
        try {
            $pdo->beginTransaction();

            // Update content_html
            $stmt = $pdo->prepare("UPDATE training_modules SET content_html = ? WHERE id = ?");
            $stmt->execute([$content_html, $module_id]);

            // Handle video upload
            if ($video_file && $video_file['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['video/mp4', 'video/webm', 'video/ogg'];
                $max_size = 500 * 1024 * 1024; // 500MB max

                if (!in_array($video_file['type'], $allowed_types)) {
                    throw new Exception("Only MP4, WebM, or OGG videos allowed.");
                }
                if ($video_file['size'] > $max_size) {
                    throw new Exception("Video file too large (max 500MB).");
                }

                // Get current video path to delete old one
                $stmt = $pdo->prepare("SELECT video_path FROM training_modules WHERE id = ?");
                $stmt->execute([$module_id]);
                $old_path = $stmt->fetchColumn();

                // Generate unique filename
                $ext = pathinfo($video_file['name'], PATHINFO_EXTENSION);
                $filename = 'module_' . $module_id . '_' . time() . '.' . $ext;
                $target_path = $upload_dir . $filename;

                if (move_uploaded_file($video_file['tmp_name'], $target_path)) {
                    // Update DB with new path (relative for web access)
                    $web_path = 'assets/videos/modules/' . $filename;
                    $stmt = $pdo->prepare("UPDATE training_modules SET video_path = ? WHERE id = ?");
                    $stmt->execute([$web_path, $module_id]);

                    // Delete old video if exists
                    if ($old_path && file_exists('../' . $old_path)) {
                        unlink('../' . $old_path);
                    }
                } else {
                    throw new Exception("Failed to upload video.");
                }
            }

            $pdo->commit();
            $message = "Module content updated successfully!";
            $message_type = 'success';

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Fetch all modules
$stmt = $pdo->query("SELECT id, code, title, description, video_path, content_html FROM training_modules WHERE is_active = 1 ORDER BY id");
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// For editing, pre-select first module or from POST
$selected_module = null;
if (!empty($_POST['module_id'])) {
    foreach ($modules as $m) {
        if ($m['id'] == $_POST['module_id']) {
            $selected_module = $m;
            break;
        }
    }
}
if (!$selected_module && !empty($modules)) {
    $selected_module = $modules[0]; // default to first
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Modules — CyberAware</title>
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
.layout{display:grid;grid-template-columns:280px 1fr;min-height:calc(100vh - 64px);}
.layout.sidebar-hidden{grid-template-columns:1fr;}
.sidebar{background:#fff;border-right:1px solid var(--grey2);padding:28px 20px;display:flex;flex-direction:column;gap:8px;position:relative;}
.sidebar.hidden{display:none;}
.toggle-sidebar{position:absolute;top:20px;right:20px;background:var(--orange);color:#fff;border:none;width:36px;height:36px;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;transition:.2s;z-index:50;}
.toggle-sidebar:hover{background:#e67e2f;}
body.dark-mode .toggle-sidebar{background:var(--orange);}
body.dark-mode .toggle-sidebar:hover{background:#e67e2f;}
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
.card-title{font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;color:var(--dark);margin-bottom:16px;}
.form-group{margin-bottom:20px;}
.form-label{display:block;font-weight:700;color:var(--dark);margin-bottom:8px;font-size:14px;}
.form-input,.form-select,textarea{width:100%;padding:10px 14px;border:1px solid var(--grey2);border-radius:8px;font-family:inherit;font-size:14px;}
.form-input:focus,.form-select:focus,textarea:focus{outline:none;border-color:var(--orange);box-shadow:0 0 0 3px rgba(255,140,66,.1);}
textarea{min-height:300px;font-family:'Courier New',monospace;}
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;cursor:pointer;border:none;transition:.2s;}
.btn-primary{background:var(--orange);color:#fff;}
.btn-primary:hover{background:#e67e2f;transform:translateY(-2px);}
.message{padding:16px;border-radius:10px;margin:16px 0;font-weight:600;font-size:14px;}
.message-success{background:#ecfdf5;color:#065f46;border-left:4px solid var(--green);}
.message-danger{background:#fef2f2;color:#991b1b;border-left:4px solid var(--red);}
.module-selector{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:2rem;}
.module-option{padding:1rem;background:var(--ol);border-radius:10px;text-align:center;cursor:pointer;border:2px solid transparent;transition:.2s;font-weight:600;color:var(--dark);}
.module-option:hover,.module-option.selected{border-color:var(--orange);background:#fff;box-shadow:0 8px 16px rgba(255,140,66,.2);}
.video-preview{margin-top:1rem;max-width:100%;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.1);}
small{color:#888;font-size:13px;}
body.dark-mode{background:#1A1A2E;color:#fff;}
body.dark-mode .topbar{background:#0f0f1e;}
body.dark-mode .sidebar{background:#1f1f2e;border-right-color:#333;}
body.dark-mode .nav-item{color:#aaa;}
body.dark-mode .nav-item:hover,body.dark-mode .nav-item.active{background:rgba(255,140,66,.15);color:var(--orange);}
body.dark-mode .sb-profile{background:rgba(255,140,66,.1);}
body.dark-mode .sb-name{color:#fff;}
body.dark-mode .main{background:#1A1A2E;}
body.dark-mode .page-hdr h1{color:#fff;}
body.dark-mode .page-hdr p{color:#aaa;}
body.dark-mode .card{background:#2a2a3e;box-shadow:0 2px 8px rgba(0,0,0,.3);}
body.dark-mode .card:hover{box-shadow:0 12px 32px rgba(0,0,0,.5);}
body.dark-mode .card-title{color:#fff;}
body.dark-mode .form-label{color:#fff;}
body.dark-mode .form-input,.form-select,body.dark-mode textarea{background:#1f1f2e;border-color:#333;color:#fff;}
body.dark-mode .form-input::placeholder{color:#666;}
body.dark-mode .tb-dropdown{background:#2a2a3e;}
body.dark-mode .tb-dropdown-header{border-bottom-color:#333;}
body.dark-mode .tb-dropdown-name{color:#fff;}
body.dark-mode .tb-dropdown-item{color:#fff;}
body.dark-mode .tb-dropdown-item:hover{background:#333;color:var(--orange);}
body.dark-mode .module-option{background:#333;}
body.dark-mode .module-option.selected{background:#2a2a3e;}
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
        <a href="manage-users.php" class="nav-item"><i class="fas fa-users"></i> Manage Users</a>
        <a href="manage_modules.php" class="nav-item active"><i class="fas fa-book"></i> Manage Modules</a>
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
            <h1><i class="fas fa-book"></i> Manage Training Modules</h1>
            <p>Edit training content, videos, and learning materials for all modules.</p>
        </div>

        <?php if ($message): ?>
        <div class="message message-<?= $message_type ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-title">Select Module to Edit</div>
            <div class="module-selector">
                <?php foreach ($modules as $m): ?>
                <div class="module-option <?= $selected_module && $selected_module['id'] == $m['id'] ? 'selected' : '' ?>"
                     onclick="document.getElementById('module_<?= $m['id'] ?>').submit()">
                    <form id="module_<?= $m['id'] ?>" method="POST" style="display:none;">
                        <input type="hidden" name="module_id" value="<?= $m['id'] ?>">
                    </form>
                    <strong><?= htmlspecialchars($m['title']) ?></strong><br>
                    <small><?= htmlspecialchars($m['code']) ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($selected_module): ?>
        <div class="card">
            <div class="card-title">Editing: <?= htmlspecialchars($selected_module['title']) ?></div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="module_id" value="<?= $selected_module['id'] ?>">

                <div class="form-group">
                    <label class="form-label">Full Training Content (HTML)</label>
                    <textarea name="content_html" placeholder="&lt;h2&gt;Welcome&lt;/h2&gt;&lt;p&gt;Your training content here...&lt;/p&gt;"><?= htmlspecialchars($selected_module['content_html'] ?? '') ?></textarea>
                    <small>Use HTML for formatting, headings, lists, images, etc.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Training Video (MP4/WebM/OGG - max 500MB)</label>
                    <input type="file" name="module_video" class="form-input" accept="video/*">
                    <?php if ($selected_module['video_path']): ?>
                    <div style="margin-top:1rem;">
                        <strong>Current Video:</strong>
                        <video class="video-preview" controls>
                            <source src="../<?= htmlspecialchars($selected_module['video_path']) ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                        <p><small>Upload a new video to replace this one.</small></p>
                    </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
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
</script>
