<?php
ob_start(); // Start output buffering to prevent any stray output

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
    $streak    = 3; // placeholder
    $lives     = 5;
    $level     = max(1, ceil($done / 2));
    $pct       = $total_mod > 0 ? round(($done / $total_mod) * 100) : 0;
    
    // Get current day of week (0 = Sunday, 1 = Monday, ..., 6 = Saturday)
    $current_day = date('w');
    // Convert to 0 = Monday, 1 = Tuesday, ..., 6 = Sunday
    $current_day_index = ($current_day + 6) % 7;
} catch(Exception $e) {
    $name = $_SESSION['full_name'] ?? 'Trainee';
    $dept = 'General';
    $done = 0; $total_mod = 7; $avg_score = 0; $xp = 0; $streak = 0; $lives = 5; $level = 1; $pct = 0;
    $current_day_index = 0;
}

// Get registered modules with their status
$stmt = $pdo->prepare("
    SELECT 
        umr.module_id,
        umr.registration_order,
        umr.status,
        tm.id,
        tm.title,
        tm.category,
        COALESCE(ump.passed, 0) as passed,
        COALESCE(ump.best_score, 0) as best_score
    FROM user_module_registrations umr
    JOIN training_modules tm ON umr.module_id = tm.id
    LEFT JOIN user_module_progress ump ON umr.user_id = ump.user_id AND umr.module_id = ump.module_id
    WHERE umr.user_id = ?
    ORDER BY umr.registration_order
");
$stmt->execute([$user_id]);
$registered_modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map modules with icons and colors
$icon_map = [
    'phishing' => 'fa-envelope',
    'credential' => 'fa-key',
    'social' => 'fa-phone-alt',
    'malware' => 'fa-paperclip',
    'link' => 'fa-link',
    'password' => 'fa-lock',
    'ransomware' => 'fa-virus'
];

$color_map = [
    'phishing' => '#FF8C42',
    'credential' => '#8B5CF6',
    'social' => '#3B82F6',
    'malware' => '#EF4444',
    'link' => '#10B981',
    'password' => '#6366F1',
    'ransomware' => '#DC2626'
];

$modules = [];
foreach ($registered_modules as $idx => $mod) {
    $is_locked = false;
    $lock_reason = '';
    
    // First module is always unlocked if registered
    if ($idx > 0) {
        // Check if previous module is passed
        $prev_module = $registered_modules[$idx - 1];
        if ((int)$prev_module['passed'] !== 1) {
            $is_locked = true;
            $lock_reason = 'Complete the previous module first';
        }
    }
    
    $modules[] = [
        'id' => $mod['module_id'],
        'title' => $mod['title'],
        'icon' => $icon_map[$mod['category']] ?? 'fa-book',
        'color' => $color_map[$mod['category']] ?? '#FF8C42',
        'time' => '10 min',
        'xp' => 200 + ($idx * 20),
        'file' => 'modules/training_module.php?module=' . $mod['module_id'],
        'status' => $mod['status'],
        'passed' => (int)$mod['passed'],
        'score' => (int)$mod['best_score'],
        'locked' => $is_locked,
        'lock_reason' => $lock_reason
    ];
}
ob_end_flush(); // Flush output buffer
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — CyberAware</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="theme-styles.css">
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
/* HERO STRIP */
.hero-strip{background:linear-gradient(135deg,var(--dark) 0%,#2d2d4e 100%);border-radius:20px;padding:28px 32px;color:#fff;display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;gap:24px;}
.hs-left h1{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;margin-bottom:6px;}
.hs-left p{color:rgba(255,255,255,.65);font-size:15px;}
.hs-stats{display:flex;gap:16px;flex-shrink:0;}
.hs-stat{background:rgba(255,255,255,.08);border-radius:12px;padding:14px 20px;text-align:center;}
.hs-stat-num{font-size:26px;font-weight:800;color:var(--orange);}
.hs-stat-lbl{font-size:11px;color:rgba(255,255,255,.6);font-weight:600;text-transform:uppercase;margin-top:2px;}
/* PROGRESS RING */
.progress-section{display:grid;grid-template-columns:auto 1fr;gap:24px;background:#fff;border-radius:16px;padding:24px;margin-bottom:24px;align-items:center;box-shadow:0 2px 8px rgba(0,0,0,.05);}
.ring-wrap{position:relative;width:100px;height:100px;}
.ring-wrap svg{transform:rotate(-90deg);}
.ring-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;}
.ring-pct{font-size:22px;font-weight:800;color:var(--dark);}
.ring-lbl{font-size:10px;color:#aaa;font-weight:700;text-transform:uppercase;}
.progress-info h3{font-size:17px;font-weight:700;color:var(--dark);margin-bottom:6px;}
.progress-info p{font-size:14px;color:#888;margin-bottom:12px;}
.progress-pills{display:flex;gap:10px;flex-wrap:wrap;}
.pp{padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700;}
.pp.green{background:#d1fae5;color:#065f46;}
.pp.orange{background:var(--ol);color:#b85a00;}
.pp.purple{background:#ede9fe;color:#5b21b6;}
/* MODULE GRID */
.section-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;}
.section-hdr h2{font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:700;color:var(--dark);}
.modules-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-bottom:28px;}
.mod-card{background:#fff;border-radius:16px;padding:22px;text-decoration:none;color:inherit;border:2px solid transparent;transition:.2s;box-shadow:0 2px 8px rgba(0,0,0,.05);position:relative;overflow:hidden;}
.mod-card::before{content:'';position:absolute;top:-30px;right:-30px;width:100px;height:100px;border-radius:50%;opacity:.08;}
.mod-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.1);}
.mod-card.locked{opacity:.55;cursor:not-allowed;}
.mod-card:not(.locked):hover{border-color:var(--orange);}
.mc-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.mc-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;}
.mc-badge{font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;}
.mc-badge.new{background:var(--ol);color:#b85a00;}
.mc-badge.done{background:#d1fae5;color:#065f46;}
.mc-badge.locked{background:var(--grey2);color:#999;}
.mc-title{font-size:15px;font-weight:700;color:var(--dark);margin-bottom:6px;}
.mc-meta{display:flex;gap:12px;font-size:12px;color:#aaa;margin-bottom:14px;}
.mc-xp{font-size:13px;font-weight:700;color:var(--orange);}
.mc-bar{height:4px;background:var(--grey2);border-radius:2px;overflow:hidden;}
.mc-bar-fill{height:100%;border-radius:2px;}
/* STREAK CARD */
.streak-card{background:linear-gradient(135deg,#FF8C42,#FF6B35);border-radius:16px;padding:24px;color:#fff;display:flex;align-items:center;gap:20px;margin-bottom:24px;}
.streak-fire{font-size:48px;}
.streak-info h3{font-size:20px;font-weight:700;margin-bottom:4px;}
.streak-info p{font-size:14px;opacity:.85;}
.streak-days{display:flex;gap:8px;margin-top:12px;}
.sd{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;}
.sd.done{background:rgba(255,255,255,.3);color:#fff;}
.sd.today{background:#fff;color:#FF8C42;}
.sd.upcoming{background:rgba(255,255,255,.1);color:rgba(255,255,255,.4);}
@media(max-width:900px){.layout{grid-template-columns:1fr;}.sidebar{display:none;}.hs-stats{display:none;}}

/* DARK MODE STYLES */
body.dark-mode{background:#1A1A2E;color:#fff;}
body.dark-mode .topbar{background:#0f0f1e;}
body.dark-mode .sidebar{background:#1f1f2e;border-right-color:#333;}
body.dark-mode .nav-item{color:#aaa;}
body.dark-mode .nav-item:hover,body.dark-mode .nav-item.active{background:rgba(255,140,66,.15);color:var(--orange);}
body.dark-mode .sb-profile{background:rgba(255,140,66,.1);}
body.dark-mode .sb-name{color:#fff;}
body.dark-mode .sb-dept{color:#aaa;}
body.dark-mode .main{background:#1A1A2E;}
body.dark-mode .hero-strip{background:linear-gradient(135deg,#0f0f1e 0%,#1a1a2e 100%);}
body.dark-mode .hs-left h1{color:#fff;}
body.dark-mode .hs-left p{color:rgba(255,255,255,.65);}
body.dark-mode .hs-stat{background:rgba(255,255,255,.08);}
body.dark-mode .hs-stat-num{color:var(--orange);}
body.dark-mode .hs-stat-lbl{color:rgba(255,255,255,.6);}
body.dark-mode .progress-section{background:#2a2a3e;box-shadow:0 2px 8px rgba(0,0,0,.3);}
body.dark-mode .ring-pct{color:#fff;}
body.dark-mode .ring-lbl{color:#888;}
body.dark-mode .progress-info h3{color:#fff;}
body.dark-mode .progress-info p{color:#aaa;}
body.dark-mode .section-hdr h2{color:#fff;}
body.dark-mode .section-hdr span{color:#666;}
body.dark-mode .mod-card{background:#2a2a3e;box-shadow:0 2px 8px rgba(0,0,0,.3);}
body.dark-mode .mod-card:hover{box-shadow:0 12px 32px rgba(0,0,0,.5);}
body.dark-mode .mc-title{color:#fff;}
body.dark-mode .mc-meta{color:#888;}
body.dark-mode .mc-bar{background:#333;}
body.dark-mode .tb-dropdown{background:#2a2a3e;}
body.dark-mode .tb-dropdown-header{border-bottom-color:#333;}
body.dark-mode .tb-dropdown-name{color:#fff;}
body.dark-mode .tb-dropdown-item{color:#fff;}
body.dark-mode .tb-dropdown-item:hover{background:#333;color:var(--orange);}

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
                            <div class="tb-dropdown-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
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
        <a href="dashboard.php" class="nav-item active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="register-modules.php" class="nav-item"><i class="fas fa-plus-circle"></i> Register Modules</a>
        <a href="progress.php" class="nav-item"><i class="fas fa-chart-line"></i> My Progress</a>
        <a href="phish-inbox.php" class="nav-item"><i class="fas fa-inbox"></i> Phish Inbox</a>
        <div class="sb-section">Security</div>
        <a href="report-incident.php" class="nav-item"><i class="fas fa-exclamation-triangle"></i> Report Incident</a>
        <div class="sb-section">Account</div>
        <a href="certificate.php?module=1" class="nav-item"><i class="fas fa-award"></i> Certificates</a>
        <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
        <div class="sb-section"></div>
        <a href="../logout.php" class="nav-item nav-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <!-- MAIN -->
    <main class="main">
        <!-- HERO -->
        <div class="hero-strip">
            <div class="hs-left">
                <h1>Welcome back, <?= htmlspecialchars(explode(' ',$name)[0]) ?>! 👋</h1>
                <p>Keep your streak alive — you're on a roll. <?= $done ?> of <?= $total_mod ?> modules complete.</p>
            </div>
            <div class="hs-stats">
                <div class="hs-stat">
                    <div class="hs-stat-num"><?= $xp ?></div>
                    <div class="hs-stat-lbl">XP Earned</div>
                </div>
                <div class="hs-stat">
                    <div class="hs-stat-num"><?= $avg_score ?>%</div>
                    <div class="hs-stat-lbl">Avg Score</div>
                </div>
                <div class="hs-stat">
                    <div class="hs-stat-num"><?= $done ?>/<?= $total_mod ?></div>
                    <div class="hs-stat-lbl">Modules</div>
                </div>
            </div>
        </div>

        <!-- STREAK -->
        <div class="streak-card">
            <div class="streak-fire"><i class="fas fa-fire"></i></div>
            <div class="streak-info">
                <h3><?= $streak ?>-day streak!</h3>
                <p>Complete at least one lesson today to keep your streak alive.</p>
                <div class="streak-days">
                    <?php 
                    $days = ['M','T','W','T','F','S','S'];
                    foreach($days as $i => $d): 
                        $class = '';
                        if ($i < $current_day_index) {
                            $class = 'done';
                        } elseif ($i === $current_day_index) {
                            $class = 'today';
                        } else {
                            $class = 'upcoming';
                        }
                    ?>
                    <div class="sd <?= $class ?>"><?= $d ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- PROGRESS -->
        <div class="progress-section">
            <div class="ring-wrap">
                <svg width="100" height="100" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#f3f4f6" stroke-width="10"/>
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#FF8C42" stroke-width="10"
                        stroke-dasharray="<?= round($pct * 2.638) ?> 264" stroke-linecap="round"/>
                </svg>
                <div class="ring-center">
                    <div class="ring-pct"><?= $pct ?>%</div>
                    <div class="ring-lbl">Done</div>
                </div>
            </div>
            <div class="progress-info">
                <h3>Your security journey</h3>
                <p><?= $done ?> of <?= $total_mod ?> modules completed · <?= $total_mod - $done ?> remaining</p>
                <div class="progress-pills">
                    <span class="pp green"><i class="fas fa-check"></i> <?= $done ?> Completed</span>
                    <span class="pp orange"><i class="fas fa-bolt"></i> <?= $xp ?> XP Total</span>
                    <span class="pp purple"><i class="fas fa-shield-alt"></i> Level <?= $level ?></span>
                </div>
            </div>
        </div>

        <!-- ADAPTIVE LEARNING RECOMMENDATIONS -->
        <?php 
        @include 'widgets/adaptive-learning-widget.php';
        ?>

        <!-- MODULES -->
        <div class="section-hdr">
            <h2>Training Modules</h2>
            <span style="font-size:13px;color:#aaa;font-weight:600;"><?= $done ?>/<?= count($modules) ?> complete</span>
        </div>
        
        <?php if (empty($modules)): ?>
            <div style="background:#fff;border-radius:16px;padding:40px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,.05);">
                <i class="fas fa-inbox" style="font-size:48px;color:#ddd;margin-bottom:15px;display:block;"></i>
                <p style="color:#888;margin-bottom:15px;">You haven't registered for any modules yet.</p>
                <a href="register-modules.php" style="display:inline-block;background:var(--orange);color:white;padding:12px 24px;border-radius:999px;text-decoration:none;font-weight:700;">
                    <i class="fas fa-plus"></i> Register for Modules
                </a>
            </div>
        <?php else: ?>
            <div class="modules-grid">
                <?php foreach ($modules as $i => $mod): ?>
                <a href="<?= $mod['locked'] ? '#' : $mod['file'] ?>" class="mod-card <?= $mod['locked'] ? 'locked' : '' ?>" 
                   title="<?= $mod['locked'] ? htmlspecialchars($mod['lock_reason']) : '' ?>"
                   onclick="<?= $mod['locked'] ? 'return false' : '' ?>">
                    <div style="position:absolute;top:-20px;right:-20px;width:80px;height:80px;border-radius:50%;background:<?= $mod['color'] ?>;opacity:.08;"></div>
                    <div class="mc-top">
                        <div class="mc-icon" style="background:<?= $mod['color'] ?>;"><i class="fas <?= $mod['icon'] ?>"></i></div>
                    <span class="mc-badge <?= $mod['locked'] ? 'locked' : ($mod['passed'] ? 'done' : ($mod['status'] === 'in_progress' ? 'new' : 'locked')) ?>">
                            <?php if ($mod['locked']): ?>
                                <i class="fas fa-lock"></i> Locked
                            <?php elseif ($mod['passed']): ?>
                                <i class="fas fa-check"></i> Done
                            <?php elseif ($mod['status'] === 'in_progress'): ?>
                                Start
                            <?php else: ?>
                                Start
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="mc-title"><?= htmlspecialchars($mod['title']) ?></div>
                    <div class="mc-meta">
                        <span><i class="fas fa-clock"></i> <?= $mod['time'] ?></span>
                        <span><i class="fas fa-signal"></i> <?= $i < 2 ? 'Beginner' : 'Intermediate' ?></span>
                    </div>
                    <div class="mc-xp">+<?= $mod['xp'] ?> XP</div>
                    <div class="mc-bar" style="margin-top:10px;">
                        <div class="mc-bar-fill" style="background:<?= $mod['color'] ?>;width:<?= $mod['passed'] ? '100' : '0' ?>%;"></div>
                    </div>
                    <?php if ($mod['locked']): ?>
                        <div style="margin-top:10px;font-size:12px;color:#888;font-weight:600;">
                            <i class="fas fa-info-circle"></i> <?= htmlspecialchars($mod['lock_reason']) ?>
                        </div>
                    <?php elseif ($mod['passed']): ?>
                        <div style="margin-top:10px;font-size:12px;color:#10b981;font-weight:600;">
                            <i class="fas fa-check"></i> Score: <?= $mod['score'] ?>%
                        </div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
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