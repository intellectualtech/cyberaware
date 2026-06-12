<?php
/**
 * Manager Dashboard
 * Team performance overview and management
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// Require login and manager role
if (!isLoggedIn()) {
    header('Location: ../pages/login.php');
    exit;
}

$role = strtolower(trim($_SESSION['role'] ?? ''));
if ($role !== 'manager') {
    header('Location: ../pages/access-denied.php');
    exit;
}

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];

    // Get manager info
    $stmt = $pdo->prepare("SELECT u.*, d.name AS dept FROM users u LEFT JOIN departments d ON d.id=u.department_id WHERE u.id=?");
    $stmt->execute([$user_id]);
    $manager = $stmt->fetch();
    $manager_name = $manager['full_name'] ?? $manager['username'] ?? 'Manager';
    $manager_dept = $manager['dept'] ?? 'General';

    // Get team members (employees in same department)
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.email, u.role,
               COUNT(DISTINCT ts.id) as modules_completed,
               COALESCE(AVG(ts.final_score), 0) as avg_score,
               MAX(ts.completed_at) as last_activity
        FROM users u
        LEFT JOIN training_sessions ts ON u.id = ts.user_id
        WHERE u.department_id = ? AND u.role = 'trainee'
        GROUP BY u.id
        ORDER BY u.full_name
    ");
    $stmt->execute([$manager['department_id']]);
    $team_members = $stmt->fetchAll();

    // Get team statistics
    $total_team = count($team_members);
    $total_completed = 0;
    $total_avg_score = 0;
    $highest_score = 0;
    $lowest_score = 100;
    
    foreach ($team_members as $member) {
        if ($member['modules_completed'] > 0) $total_completed++;
        $total_avg_score += $member['avg_score'];
        $highest_score = max($highest_score, $member['avg_score']);
        $lowest_score = min($lowest_score, $member['avg_score']);
    }
    
    $completion_rate = $total_team > 0 ? round(($total_completed / $total_team) * 100) : 0;
    $team_avg_score = $total_team > 0 ? round($total_avg_score / $total_team) : 0;
    $at_risk_count = 0;
    foreach ($team_members as $member) {
        if ($member['avg_score'] < 60 && $member['modules_completed'] > 0) {
            $at_risk_count++;
        }
    }

    // Get department incidents
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_incidents,
               SUM(CASE WHEN status = 'reported' THEN 1 ELSE 0 END) as pending,
               SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
        FROM incident_reports
        WHERE user_id IN (SELECT id FROM users WHERE department_id = ?)
    ");
    $stmt->execute([$manager['department_id']]);
    $incidents = $stmt->fetch();

} catch(Exception $e) {
    $manager_name = 'Manager';
    $manager_dept = 'General';
    $team_members = [];
    $total_team = 0;
    $completion_rate = 0;
    $team_avg_score = 0;
    $incidents = ['total_incidents' => 0, 'pending' => 0, 'resolved' => 0];
    $at_risk_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manager Dashboard — CyberAware</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--orange:#FF8C42;--ol:#FFF4EC;--grey:#F5F5F5;--grey2:#E8E8E8;--dark:#1A1A2E;--green:#10B981;--red:#EF4444;--yellow:#FBBF24;--blue:#3B82F6;}
body{font-family:'Manrope',sans-serif;background:var(--grey);min-height:100vh;}
/* TOPBAR */
.topbar{background:var(--dark);padding:0 24px;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
.tb-logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.tb-logo .ic{width:36px;height:36px;background:var(--orange);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;}
.tb-logo span{font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;color:#fff;}
.tb-right{display:flex;align-items:center;gap:16px;}
.tb-avatar-wrapper{position:relative;}
.tb-avatar{width:36px;height:36px;background:var(--orange);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;cursor:pointer;transition:all 0.2s;}
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
.sb-role{display:inline-block;background:var(--orange);color:#fff;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;margin-top:8px;}
.nav-item{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;text-decoration:none;color:#555;font-weight:600;font-size:15px;transition:.2s;}
.nav-item:hover,.nav-item.active{background:var(--ol);color:var(--orange);}
.nav-item i{width:20px;text-align:center;font-size:16px;}
.sb-section{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#bbb;padding:12px 16px 4px;}
.nav-logout{color:var(--red);}
.nav-logout:hover{background:#fff0f0;color:var(--red);}
/* MAIN */
.main{padding:32px;padding-bottom:100px;}
.container{max-width:1200px;margin:0 auto;}
/* PAGE HEADER */
.page-header{margin-bottom:32px;}
.page-header h1{font-family:'Space Grotesk',sans-serif;font-size:32px;font-weight:700;color:var(--dark);margin-bottom:6px;}
.page-header p{color:#888;font-size:15px;}
/* HERO STRIP */
.hero-strip{background:linear-gradient(135deg,var(--dark) 0%,#2d2d4e 100%);border-radius:20px;padding:28px 32px;color:#fff;display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;gap:24px;}
.hs-left h2{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;margin-bottom:6px;}
.hs-left p{color:rgba(255,255,255,.65);font-size:15px;}
.hs-stats{display:flex;gap:16px;flex-shrink:0;}
.hs-stat{background:rgba(255,255,255,.08);border-radius:12px;padding:14px 20px;text-align:center;}
.hs-stat-num{font-size:26px;font-weight:800;color:var(--orange);}
.hs-stat-lbl{font-size:11px;color:rgba(255,255,255,.6);font-weight:600;text-transform:uppercase;margin-top:2px;}
/* STATS GRID */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px;margin-bottom:32px;}
.stat-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.05);}
.stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;margin-bottom:12px;}
.stat-icon.orange{background:var(--orange);}
.stat-icon.green{background:var(--green);}
.stat-icon.red{background:var(--red);}
.stat-icon.blue{background:var(--blue);}
.stat-label{font-size:13px;color:#888;font-weight:600;text-transform:uppercase;margin-bottom:6px;}
.stat-value{font-size:28px;font-weight:800;color:var(--dark);}
.stat-sub{font-size:12px;color:#aaa;margin-top:6px;}
.section-title{font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:700;color:var(--dark);margin-bottom:16px;display:flex;align-items:center;gap:10px;}
.section-title i{color:var(--orange);}
.team-section{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.05);margin-bottom:32px;}
.team-table{width:100%;border-collapse:collapse;}
.team-table th{background:var(--ol);padding:12px;text-align:left;font-weight:700;font-size:13px;color:#666;text-transform:uppercase;}
.team-table td{padding:14px 12px;border-bottom:1px solid var(--grey2);font-size:14px;}
.team-table tr:hover{background:var(--ol);}
.member-name{font-weight:600;color:var(--dark);}
.member-email{color:#888;font-size:13px;}
.badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;}
.badge-active{background:#d1fae5;color:#065f46;}
.badge-inactive{background:var(--grey2);color:#666;}
.badge-atrisk{background:#fef2f2;color:#b91c1c;}
.progress-bar{width:100%;height:6px;background:var(--grey2);border-radius:3px;overflow:hidden;}
.progress-fill{height:100%;background:var(--orange);border-radius:3px;}
.progress-fill-red{height:100%;background:var(--red);border-radius:3px;}
.empty-state{text-align:center;padding:40px 20px;color:#888;}
.empty-state i{font-size:48px;color:#ddd;margin-bottom:12px;display:block;}
.btn{display:inline-block;padding:10px 20px;background:var(--orange);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;font-size:14px;text-decoration:none;transition:.2s;}
.btn:hover{background:#e07030;transform:translateY(-1px);}
.btn-secondary{background:var(--grey2);color:var(--dark);}
.btn-secondary:hover{background:#d1d5db;}
@media(max-width:900px){.layout{grid-template-columns:1fr;}.sidebar{display:none;}.hs-stats{display:none;}}
@media(max-width:768px){.stats-grid{grid-template-columns:1fr;}.team-table{font-size:12px;}.team-table td{padding:10px 8px;}}
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <a href="dashboard.php" class="tb-logo">
        <div class="ic"><i class="fas fa-users"></i></div>
        <span>CyberAware Manager</span>
    </a>
    <div class="tb-right">
        <div class="tb-avatar-wrapper">
            <div class="tb-avatar" id="avatarBtn" title="<?= htmlspecialchars($manager_name) ?>"><?= strtoupper(substr($manager_name,0,1)) ?></div>
            <div class="tb-dropdown" id="profileDropdown">
                <div class="tb-dropdown-header">
                    <div class="tb-dropdown-user">
                        <div class="tb-dropdown-avatar"><?= strtoupper(substr($manager_name,0,1)) ?></div>
                        <div>
                            <div class="tb-dropdown-name"><?= htmlspecialchars($manager_name) ?></div>
                            <div class="tb-dropdown-email"><?= htmlspecialchars($manager['email'] ?? '') ?></div>
                        </div>
                    </div>
                    <div class="tb-dropdown-label">Manager</div>
                </div>
                <div class="tb-dropdown-menu">
                    <a href="profile.php" class="tb-dropdown-item">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                    <a href="settings.php" class="tb-dropdown-item">
                        <i class="fas fa-cog"></i> Settings
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
            <div class="sb-avatar"><?= strtoupper(substr($manager_name,0,1)) ?></div>
            <div class="sb-name"><?= htmlspecialchars($manager_name) ?></div>
            <div class="sb-dept"><?= htmlspecialchars($manager_dept) ?></div>
            <div class="sb-role">Manager</div>
        </div>
        <div class="sb-section">Management</div>
        <a href="dashboard.php" class="nav-item active"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="#" class="nav-item"><i class="fas fa-users"></i> Team Members</a>
        <a href="#" class="nav-item"><i class="fas fa-file-alt"></i> Reports</a>
        <a href="#" class="nav-item"><i class="fas fa-exclamation-triangle"></i> Incidents</a>
        <div class="sb-section">Account</div>
        <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
        <div class="sb-section"></div>
        <a href="../logout.php" class="nav-item nav-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <!-- MAIN -->
    <main class="main">
        <div class="container">
            <!-- PAGE HEADER -->
            <div class="page-header">
                <h1>Welcome back, <?= htmlspecialchars(explode(' ',$manager_name)[0]) ?>! 👋</h1>
                <p><?= htmlspecialchars($manager_dept) ?> Department • <?= $total_team ?> team members</p>
            </div>

            <!-- HERO SECTION -->
            <div class="hero-strip">
                <div class="hs-left">
                    <h2>Team Performance Overview</h2>
                    <p>Track your team's training progress and security compliance at a glance.</p>
                </div>
                <div class="hs-stats">
                    <div class="hs-stat">
                        <div class="hs-stat-num"><?= $total_team ?></div>
                        <div class="hs-stat-lbl">Team Members</div>
                    </div>
                    <div class="hs-stat">
                        <div class="hs-stat-num"><?= $completion_rate ?>%</div>
                        <div class="hs-stat-lbl">Completion</div>
                    </div>
                    <div class="hs-stat">
                        <div class="hs-stat-num"><?= $team_avg_score ?>%</div>
                        <div class="hs-stat-lbl">Avg Score</div>
                    </div>
                </div>
            </div>

            <!-- STATS GRID -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-users"></i></div>
                    <div class="stat-label">Team Members</div>
                    <div class="stat-value"><?= $total_team ?></div>
                    <div class="stat-sub">Active trainees</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-label">Completion Rate</div>
                    <div class="stat-value"><?= $completion_rate ?>%</div>
                    <div class="stat-sub"><?= $total_completed ?> of <?= $total_team ?> completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-label">Avg Score</div>
                    <div class="stat-value"><?= $team_avg_score ?>%</div>
                    <div class="stat-sub">Team average</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-label">At Risk</div>
                    <div class="stat-value"><?= $at_risk_count ?></div>
                    <div class="stat-sub">Scoring below 60%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-bug"></i></div>
                    <div class="stat-label">Incidents</div>
                    <div class="stat-value"><?= $incidents['total_incidents'] ?? 0 ?></div>
                    <div class="stat-sub"><?= ($incidents['pending'] ?? 0) ?> pending</div>
                </div>
            </div>

            <!-- TEAM MEMBERS -->
            <div class="team-section">
                <h2 class="section-title"><i class="fas fa-users-cog"></i> Team Members Performance</h2>
                
                <?php if (empty($team_members)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No team members assigned yet</p>
                    </div>
                <?php else: ?>
                    <table class="team-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Modules</th>
                                <th>Score</th>
                                <th>Last Activity</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($team_members as $member): ?>
                            <tr>
                                <td>
                                    <div class="member-name"><?= htmlspecialchars($member['full_name']) ?></div>
                                </td>
                                <td class="member-email"><?= htmlspecialchars($member['email']) ?></td>
                                <td><?= $member['modules_completed'] ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div class="progress-bar" style="flex:1;max-width:100px;">
                                            <div class="progress-fill" style="width:<?= $member['avg_score'] ?>%;background:<?= $member['avg_score'] >= 70 ? 'var(--green)' : ($member['avg_score'] >= 60 ? 'var(--yellow)' : 'var(--red)') ?>;"></div>
                                        </div>
                                        <span style="min-width:35px;"><?= round($member['avg_score']) ?>%</span>
                                    </div>
                                </td>
                                <td><?= $member['last_activity'] ? date('M j, Y', strtotime($member['last_activity'])) : 'Never' ?></td>
                                <td>
                                    <?php if ($member['modules_completed'] > 0): ?>
                                        <?php if ($member['avg_score'] >= 70): ?>
                                            <span class="badge badge-active"><i class="fas fa-check"></i> On Track</span>
                                        <?php elseif ($member['avg_score'] >= 60): ?>
                                            <span class="badge" style="background:#fef3c7;color:#b45309;"><i class="fas fa-info-circle"></i> Review</span>
                                        <?php else: ?>
                                            <span class="badge badge-atrisk"><i class="fas fa-exclamation"></i> At Risk</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge badge-inactive"><i class="fas fa-clock"></i> Not Started</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- QUICK ACTIONS -->
            <div style="display:flex;gap:12px;justify-content:center;padding:20px;margin-top:20px;">
                <button class="btn btn-secondary" onclick="window.location.href='#'" style="margin-right:10px;">
                    <i class="fas fa-download"></i> Export Report
                </button>
                <button class="btn" onclick="window.location.href='#'">
                    <i class="fas fa-plus"></i> Add Team Member
                </button>
            </div>
        </div>
    </main>
</div>

</body>
</html>

<script>
// Avatar dropdown toggle
const avatarBtn = document.getElementById('avatarBtn');
const dropdownMenu = document.getElementById('dropdownMenu');

if (avatarBtn && dropdownMenu) {
    avatarBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdownMenu.classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.tb-avatar-wrapper')) {
            dropdownMenu.classList.remove('active');
        }
    });

    // Close dropdown when clicking on a menu item
    const dropdownItems = dropdownMenu.querySelectorAll('.dropdown-item');
    dropdownItems.forEach(item => {
        item.addEventListener('click', function() {
            dropdownMenu.classList.remove('active');
        });
    });
}
</script>
