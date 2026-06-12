<?php
ob_start();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/theme-helper.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'trainee') {
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
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT u.*, d.name AS dept FROM users u LEFT JOIN departments d ON d.id=u.department_id WHERE u.id=?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $name  = $user['full_name'] ?? $user['username'] ?? 'Trainee';
    $dept  = $user['dept'] ?? 'General';
    $email = $user['email'] ?? '';

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total,
               SUM(CASE WHEN completed_at IS NOT NULL THEN 1 ELSE 0 END) AS done,
               COALESCE(AVG(final_score),0) AS avg_score
        FROM training_sessions WHERE user_id=?
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();
    $done      = (int)$stats['done'];
    $total_mod = 7;
    $avg_score = round($stats['avg_score']);
    $xp        = $done * 140 + $avg_score * 4;
    $streak    = 3;
    $lives     = 5;
    $level     = max(1, ceil($done / 2));
    $pct       = $total_mod > 0 ? round(($done / $total_mod) * 100) : 0;
} catch(Exception $e) {
    $name = $_SESSION['full_name'] ?? 'Trainee';
    $dept = 'General';
    $email = '';
    $done = 0; $total_mod = 7; $avg_score = 0; $xp = 0; $streak = 0; $lives = 5; $level = 1; $pct = 0;
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Settings — CyberAware</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script>
// Apply theme immediately from localStorage to prevent flash
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
body.dark-mode{background:#1A1A2E;color:#fff;}
body.dark-mode .topbar{background:#0f0f1e;}
body.dark-mode .sidebar{background:#1f1f2e;border-right-color:#333;}
body.dark-mode .nav-item{color:#aaa;}
body.dark-mode .nav-item:hover,.dark-mode .nav-item.active{background:rgba(255,140,66,.15);color:var(--orange);}
body.dark-mode .sb-profile{background:rgba(255,140,66,.1);}
body.dark-mode .sb-name{color:#fff;}
body.dark-mode .sb-dept{color:#aaa;}
body.dark-mode .main{background:#1A1A2E;}
body.dark-mode .page-header h1{color:#fff;}
body.dark-mode .page-header p{color:#aaa;}
body.dark-mode .settings-card{background:#2a2a3e;box-shadow:0 2px 8px rgba(0,0,0,.3);}
body.dark-mode .card-header{border-bottom-color:#333;}
body.dark-mode .card-title{color:#fff;}
body.dark-mode .card-desc{color:#aaa;}
body.dark-mode .form-label{color:#fff;}
body.dark-mode .form-input,body.dark-mode .form-select{background:#1f1f2e;border-color:#333;color:#fff;}
body.dark-mode .form-input::placeholder{color:#666;}
body.dark-mode .form-help{color:#888;}
body.dark-mode .toggle-group{border-bottom-color:#333;}
body.dark-mode .toggle-label{color:#fff;}
body.dark-mode .toggle-desc{color:#888;}
body.dark-mode .toggle-switch{background:#333;}
body.dark-mode .twofa-badge{background:rgba(16,185,129,.1);border-color:rgba(16,185,129,.3);}
body.dark-mode .twofa-badge.disabled{background:#333;border-color:#444;}
body.dark-mode .twofa-text{color:#fff;}
body.dark-mode .twofa-text.disabled{color:#888;}
body.dark-mode .tb-dropdown{background:#2a2a3e;}
body.dark-mode .tb-dropdown-header{border-bottom-color:#333;}
body.dark-mode .tb-dropdown-name{color:#fff;}
body.dark-mode .tb-dropdown-item{color:#fff;}
body.dark-mode .tb-dropdown-item:hover{background:#333;color:var(--orange);}
/* TOPBAR */
.topbar{background:var(--dark);padding:0 24px;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
.tb-logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.tb-logo .ic{width:36px;height:36px;background:var(--orange);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;}
.tb-logo span{font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;color:#fff;}
.tb-right{display:flex;align-items:center;gap:16px;}
.tb-stat{display:flex;align-items:center;gap:6px;background:rgba(255,255,255,.08);padding:7px 14px;border-radius:20px;font-size:13px;font-weight:700;color:#fff;}
.tb-stat.xp{color:#fbbf24;}
.tb-stat.streak{color:#ff6b6b;}
.tb-stat.lives{color:#ff4757;}
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
.sb-dept{font-size:13px;color:#888;}
.sb-level{display:inline-block;background:var(--orange);color:#fff;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;margin-top:8px;}
.sb-xp-bar{height:6px;background:var(--grey2);border-radius:3px;margin-top:10px;overflow:hidden;}
.sb-xp-fill{height:100%;background:var(--orange);border-radius:3px;width:<?= min($pct,100) ?>%;}
.sb-xp-label{display:flex;justify-content:space-between;font-size:11px;color:#aaa;margin-top:4px;}
.nav-item{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;text-decoration:none;color:#555;font-weight:600;font-size:15px;transition:.2s;}
.nav-item:hover,.nav-item.active{background:var(--ol);color:var(--orange);}
.nav-item i{width:20px;text-align:center;font-size:16px;}
.sb-section{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#bbb;padding:12px 16px 4px;}
.nav-logout{color:var(--red);}
.nav-logout:hover{background:#fff0f0;color:var(--red);}
/* MAIN */
.main{padding:32px;padding-bottom:100px;}
/* PAGE HEADER */
.page-header{margin-bottom:28px;}
.page-header h1{font-family:'Space Grotesk',sans-serif;font-size:28px;font-weight:700;color:var(--dark);margin-bottom:4px;}
.page-header p{color:#888;font-size:15px;}
/* SETTINGS GRID */
.settings-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(500px,1fr));gap:24px;}
.settings-card{background:#fff;border-radius:16px;padding:28px;box-shadow:0 2px 8px rgba(0,0,0,.05);}
.card-header{display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--grey2);}
.card-icon{width:40px;height:40px;background:var(--ol);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--orange);font-size:18px;}
.card-title{font-size:18px;font-weight:700;color:var(--dark);}
.card-desc{font-size:13px;color:#888;margin-top:2px;}
/* FORM ELEMENTS */
.form-group{margin-bottom:20px;}
.form-group:last-child{margin-bottom:0;}
.form-label{display:block;font-size:14px;font-weight:700;color:var(--dark);margin-bottom:8px;}
.form-input,.form-select{width:100%;padding:12px 14px;border:1px solid var(--grey2);border-radius:10px;font-family:'Manrope',sans-serif;font-size:14px;color:var(--dark);transition:.2s;}
.form-input:focus,.form-select:focus{outline:none;border-color:var(--orange);box-shadow:0 0 0 3px rgba(255,140,66,.1);}
.form-input::placeholder{color:#aaa;}
.form-help{font-size:12px;color:#888;margin-top:6px;}
/* TOGGLES */
.toggle-group{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--grey2);}
.toggle-group:last-child{border-bottom:none;}
.toggle-label{font-size:14px;font-weight:600;color:var(--dark);}
.toggle-desc{font-size:12px;color:#888;margin-top:2px;}
.toggle-switch{position:relative;width:50px;height:28px;background:var(--grey2);border-radius:14px;cursor:pointer;transition:.2s;}
.toggle-switch.active{background:var(--green);}
.toggle-switch::after{content:'';position:absolute;width:24px;height:24px;background:#fff;border-radius:50%;top:2px;left:2px;transition:.2s;}
.toggle-switch.active::after{left:24px;}
/* BADGE */
.badge{display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:700;}
.badge.enabled{background:#d1fae5;color:#065f46;}
.badge.disabled{background:var(--grey2);color:#888;}
/* BUTTONS */
.btn{padding:12px 24px;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;transition:.2s;display:inline-flex;align-items:center;gap:8px;}
.btn-primary{background:var(--orange);color:#fff;}
.btn-primary:hover{background:#e67e2f;transform:translateY(-2px);}
.btn-danger{background:var(--red);color:#fff;}
.btn-danger:hover{background:#dc2626;transform:translateY(-2px);}
.btn-secondary{background:var(--grey2);color:var(--dark);}
.btn-secondary:hover{background:#ddd;}
.btn-block{width:100%;justify-content:center;}
/* 2FA BADGE */
.twofa-badge{display:flex;align-items:center;gap:8px;padding:12px 16px;background:#f0fdf4;border:1px solid #d1fae5;border-radius:10px;margin-top:12px;}
.twofa-badge.disabled{background:var(--grey);border-color:var(--grey2);}
.twofa-badge i{font-size:18px;color:#10b981;}
.twofa-badge.disabled i{color:#aaa;}
.twofa-text{font-size:13px;font-weight:600;}
.twofa-text.disabled{color:#888;}
/* THEME BUTTONS */
.theme-buttons{display:flex;gap:8px;margin-bottom:20px;}
.theme-btn{padding:10px 18px;border:2px solid var(--grey2);border-radius:20px;background:#fff;color:var(--dark);font-size:14px;font-weight:700;cursor:pointer;transition:.2s;font-family:'Manrope',sans-serif;display:flex;align-items:center;gap:6px;min-width:110px;justify-content:center;height:36px;}
.theme-btn:hover{border-color:var(--orange);}
.theme-btn.active{background:var(--orange);color:#fff;border-color:var(--orange);}
body.dark-mode .theme-btn{background:#2a2a3e;color:#fff;border-color:#333;}
body.dark-mode .theme-btn:hover{border-color:var(--orange);}
body.dark-mode .theme-btn.active{background:var(--orange);color:#fff;border-color:var(--orange);}
/* RESPONSIVE */
@media(max-width:900px){
    .layout{grid-template-columns:1fr;}
    .sidebar{display:none;}
    .settings-grid{grid-template-columns:1fr;}
}
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
        <div class="tb-stat xp"><i class="fas fa-bolt"></i> <?= $xp ?> XP</div>
        <div class="tb-stat streak"><i class="fas fa-fire"></i> <?= $streak ?> streak</div>
        <div class="tb-stat lives">
            <?php for($i=0;$i<5;$i++) echo $i<$lives ? '<i class="fas fa-heart"></i> ' : '<span style="opacity:.3"><i class="fas fa-heart"></i> </span>'; ?>
        </div>
        <div class="tb-avatar-wrapper">
            <div class="tb-avatar" onclick="toggleDropdown()"><?= strtoupper(substr($name,0,1)) ?></div>
            <div class="tb-dropdown" id="profileDropdown">
                <div class="tb-dropdown-header">
                    <div class="tb-dropdown-user">
                        <div class="tb-dropdown-avatar"><?= strtoupper(substr($name,0,1)) ?></div>
                        <div>
                            <div class="tb-dropdown-name"><?= htmlspecialchars($name) ?></div>
                            <div class="tb-dropdown-email"><?= htmlspecialchars($email) ?></div>
                        </div>
                    </div>
                    <div class="tb-dropdown-label">Signed in as</div>
                </div>
                <div class="tb-dropdown-menu">
                    <a href="profile.php" class="tb-dropdown-item">
                        <i class="fas fa-user"></i> My Profile
                    </a>
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
            <div class="sb-avatar"><?= strtoupper(substr($name,0,1)) ?></div>
            <div class="sb-name"><?= htmlspecialchars($name) ?></div>
            <div class="sb-dept"><?= htmlspecialchars($dept) ?></div>
            <div class="sb-level">Level <?= $level ?> Agent</div>
            <div class="sb-xp-bar"><div class="sb-xp-fill"></div></div>
            <div class="sb-xp-label"><span><?= $xp ?> XP</span><span>next level</span></div>
        </div>
        <div class="sb-section">Training</div>
        <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
        <a href="register-modules.php" class="nav-item"><i class="fas fa-plus-circle"></i> Register Modules</a>
        <a href="progress.php" class="nav-item"><i class="fas fa-chart-line"></i> My Progress</a>
        <a href="phish-inbox.php" class="nav-item"><i class="fas fa-inbox"></i> Phish Inbox</a>
        <div class="sb-section">Security</div>
        <a href="report-incident.php" class="nav-item"><i class="fas fa-exclamation-triangle"></i> Report Incident</a>
        <div class="sb-section">Account</div>
        <a href="certificate.php?module=1" class="nav-item"><i class="fas fa-award"></i> Certificates</a>
        <a href="settings.php" class="nav-item active"><i class="fas fa-cog"></i> Settings</a>
        <div class="sb-section"></div>
        <a href="../logout.php" class="nav-item nav-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <!-- MAIN -->
    <main class="main">
        <!-- PAGE HEADER -->
        <div class="page-header">
            <h1><i class="fas fa-cog"></i> Settings</h1>
            <p>Customize your account and preferences</p>
        </div>

        <!-- SETTINGS GRID -->
        <div class="settings-grid">
            <!-- THEME MODE CARD -->
            <div class="settings-card" style="grid-column:1/-1;">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-palette"></i></div>
                    <div>
                        <div class="card-title">Theme Mode</div>
                        <div class="card-desc">Choose your preferred interface theme</div>
                    </div>
                </div>
                <div style="margin-bottom:20px;">
                    <label class="form-label">DISPLAY THEME</label>
                    <div class="theme-buttons">
                        <button class="theme-btn <?= getCurrentTheme() === 'light' ? 'active' : '' ?>" id="lightBtn" onclick="setTheme('light')">
                            <i class="fas fa-sun"></i> Light
                        </button>
                        <button class="theme-btn <?= getCurrentTheme() === 'dark' ? 'active' : '' ?>" id="darkBtn" onclick="setTheme('dark')">
                            <i class="fas fa-moon"></i> Dark
                        </button>
                    </div>
                </div>
            </div>

            <!-- PROFILE CARD -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-user"></i></div>
                    <div>
                        <div class="card-title">Profile</div>
                        <div class="card-desc">Update your personal information</div>
                    </div>
                </div>
                <form>
                    <div class="form-group">
                        <label class="form-label">Display Name</label>
                        <input type="text" class="form-input" value="<?= htmlspecialchars($name) ?>" placeholder="Your name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-input" value="<?= htmlspecialchars($email) ?>" placeholder="your@email.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Change Password</label>
                        <input type="password" class="form-input" placeholder="Enter new password">
                        <div class="form-help">Leave blank to keep current password</div>
                    </div>
                </form>
            </div>

            <!-- NOTIFICATIONS CARD -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-bell"></i></div>
                    <div>
                        <div class="card-title">Notifications</div>
                        <div class="card-desc">Manage your notification preferences</div>
                    </div>
                </div>
                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">Streak Reminders</div>
                        <div class="toggle-desc">Daily reminder to keep your streak alive</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                </div>
                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">New Module Alerts</div>
                        <div class="toggle-desc">Notify when new modules are available</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                </div>
                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">Phish Inbox Alerts</div>
                        <div class="toggle-desc">Notify when new phishing emails arrive</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                </div>
                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">XP Milestones</div>
                        <div class="toggle-desc">Celebrate when you reach XP goals</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                </div>
                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">Email Notifications</div>
                        <div class="toggle-desc">Receive updates via email</div>
                    </div>
                    <div class="toggle-switch" onclick="toggleSwitch(this)"></div>
                </div>
            </div>

            <!-- TRAINING PREFERENCES CARD -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-book"></i></div>
                    <div>
                        <div class="card-title">Training Preferences</div>
                        <div class="card-desc">Customize your learning experience</div>
                    </div>
                </div>
                <form>
                    <div class="form-group">
                        <label class="form-label">Daily Lesson Goal</label>
                        <select class="form-select">
                            <option value="1">1 lesson per day</option>
                            <option value="2" selected>2 lessons per day</option>
                            <option value="3">3 lessons per day</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reminder Time</label>
                        <select class="form-select">
                            <option value="08:00">8:00 AM</option>
                            <option value="09:00">9:00 AM</option>
                            <option value="12:00" selected>12:00 PM</option>
                            <option value="14:00">2:00 PM</option>
                            <option value="17:00">5:00 PM</option>
                        </select>
                    </div>
                    <div class="toggle-group" style="border:none;padding:0;margin-top:16px;">
                        <div>
                            <div class="toggle-label">XP Animations</div>
                            <div class="toggle-desc">Show animations when earning XP</div>
                        </div>
                        <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                    </div>
                </form>
            </div>

            <!-- PRIVACY & SECURITY CARD -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-shield-alt"></i></div>
                    <div>
                        <div class="card-title">Privacy & Security</div>
                        <div class="card-desc">Control your privacy settings</div>
                    </div>
                </div>
                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">Show Progress to Admin</div>
                        <div class="toggle-desc">Allow admins to see your progress</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                </div>
                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">Appear on Leaderboard</div>
                        <div class="toggle-desc">Display your name on the leaderboard</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                </div>
                <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--grey2);">
                    <div class="toggle-label" style="margin-bottom:12px;">Two-Factor Authentication</div>
                    <div class="twofa-badge disabled">
                        <i class="fas fa-lock"></i>
                        <div class="twofa-text disabled">Not enabled</div>
                    </div>
                </div>
            </div>

            <!-- ACCOUNT CARD -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-trash"></i></div>
                    <div>
                        <div class="card-title">Account</div>
                        <div class="card-desc">Manage your account</div>
                    </div>
                </div>
                <p style="font-size:14px;color:#666;margin-bottom:20px;">Deleting your account is permanent and cannot be undone. All your data will be removed.</p>
                <button class="btn btn-danger btn-block" onclick="if(confirm('Are you sure? This cannot be undone.')) { alert('Account deletion would be processed here'); }">
                    <i class="fas fa-trash"></i> Delete Account
                </button>
            </div>
        </div>

        <!-- SAVE BUTTON -->
        <div style="margin-top:32px;display:flex;justify-content:flex-end;gap:12px;">
            <button class="btn btn-secondary" onclick="window.history.back()">Cancel</button>
            <button class="btn btn-primary" onclick="alert('Settings saved successfully!')">
                <i class="fas fa-check"></i> Save Changes
            </button>
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

function toggleSwitch(element) {
    element.classList.toggle('active');
}

// Theme Mode Functionality
function setTheme(theme) {
    const body = document.body;
    const lightBtn = document.getElementById('lightBtn');
    const darkBtn = document.getElementById('darkBtn');
    
    if (theme === 'dark') {
        body.classList.add('dark-mode');
        lightBtn.classList.remove('active');
        darkBtn.classList.add('active');
        localStorage.setItem('theme', 'dark');
    } else {
        body.classList.remove('dark-mode');
        lightBtn.classList.add('active');
        darkBtn.classList.remove('active');
        localStorage.setItem('theme', 'light');
    }
    
    // Save to server session via AJAX
    fetch('settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=set_theme&theme=' + theme
    }).catch(err => console.error('Theme save error:', err));
}

// Load saved theme on page load
function loadTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);
}

// Initialize theme on page load
document.addEventListener('DOMContentLoaded', loadTheme);

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('profileDropdown');
    const avatar = document.querySelector('.tb-avatar');
    
    if (!dropdown.contains(event.target) && !avatar.contains(event.target)) {
        dropdown.classList.remove('active');
    }
});

// Close dropdown when clicking on a menu item
document.querySelectorAll('.tb-dropdown-item').forEach(item => {
    item.addEventListener('click', function() {
        document.getElementById('profileDropdown').classList.remove('active');
    });
});
</script>
