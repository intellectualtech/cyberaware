<?php
// Shared module template — used by password.php, ransomware.php, remote.php
$user_id = $_SESSION['user_id'];
$name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Trainee';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($module_title) ?> — CyberAware</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--orange:#FF8C42;--orange-light:#FFF4EC;--grey:#F5F5F5;--grey2:#E8E8E8;--dark:#1A1A2E;--mod:<?= $module_color ?>;}
body{font-family:'Manrope',sans-serif;background:var(--grey);min-height:100vh;padding-bottom:100px;}
.topbar{background:var(--dark);padding:14px 5%;display:flex;align-items:center;gap:16px;}
.topbar a{color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;font-weight:600;display:flex;align-items:center;gap:6px;transition:.2s;}
.topbar a:hover{color:#fff;}
.topbar .sep{color:rgba(255,255,255,.3);}
.topbar .cur{color:var(--orange);font-weight:700;}
.hero{background:linear-gradient(135deg,var(--dark) 0%,#2d2d4e 100%);padding:48px 5%;color:#fff;display:flex;align-items:center;gap:32px;}
.hero-icon{width:80px;height:80px;border-radius:20px;background:var(--mod);display:flex;align-items:center;justify-content:center;font-size:36px;color:#fff;flex-shrink:0;}
.hero h1{font-family:'Space Grotesk',sans-serif;font-size:32px;font-weight:700;margin-bottom:8px;}
.hero p{color:rgba(255,255,255,.7);font-size:16px;line-height:1.6;max-width:600px;}
.hero-stats{display:flex;gap:24px;margin-top:16px;}
.hs{display:flex;align-items:center;gap:8px;font-size:14px;color:rgba(255,255,255,.8);}
.hs i{color:var(--orange);}
.container{max-width:860px;margin:40px auto;padding:0 5%;}
.xp-bar-wrap{background:#fff;border-radius:14px;padding:20px 24px;margin-bottom:28px;display:flex;align-items:center;gap:20px;box-shadow:0 2px 8px rgba(0,0,0,.06);}
.xp-icon{width:48px;height:48px;background:var(--orange-light);border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--orange);font-size:22px;flex-shrink:0;}
.xp-info{flex:1;}
.xp-label{font-size:13px;font-weight:700;color:#888;margin-bottom:6px;}
.xp-bar{height:10px;background:var(--grey2);border-radius:5px;overflow:hidden;}
.xp-fill{height:100%;background:linear-gradient(90deg,var(--orange),#ffb347);border-radius:5px;width:35%;}
.xp-num{font-size:20px;font-weight:800;color:var(--orange);white-space:nowrap;}
.section-title{font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;color:var(--dark);margin-bottom:20px;}
.lessons{display:flex;flex-direction:column;gap:14px;margin-bottom:36px;}
.lesson-card{background:#fff;border-radius:14px;padding:20px 24px;display:flex;align-items:center;gap:18px;box-shadow:0 2px 8px rgba(0,0,0,.06);border:2px solid transparent;transition:.2s;cursor:pointer;text-decoration:none;}
.lesson-card:hover{border-color:var(--orange);transform:translateX(4px);}
.lesson-card.done{border-color:#d1fae5;background:#f0fdf4;}
.lesson-card.locked{opacity:.5;cursor:not-allowed;}
.lc-icon{width:48px;height:48px;border-radius:12px;background:var(--orange-light);display:flex;align-items:center;justify-content:center;color:var(--orange);font-size:20px;flex-shrink:0;}
.lesson-card.done .lc-icon{background:#d1fae5;color:#10B981;}
.lc-body{flex:1;}
.lc-title{font-size:16px;font-weight:700;color:var(--dark);margin-bottom:4px;}
.lc-meta{font-size:13px;color:#888;display:flex;gap:12px;}
.lc-xp{background:var(--orange-light);color:#b85a00;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;}
.lc-status{margin-left:auto;flex-shrink:0;}
.status-done{color:#10B981;font-size:20px;}
.status-locked{color:#ccc;font-size:18px;}
.status-next{background:var(--orange);color:#fff;padding:8px 18px;border-radius:8px;font-size:13px;font-weight:700;}
.quiz-banner{background:linear-gradient(135deg,var(--orange) 0%,#ffb347 100%);border-radius:16px;padding:32px;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:28px;}
.qb-text h3{font-size:22px;font-weight:700;margin-bottom:6px;}
.qb-text p{font-size:15px;opacity:.9;}
.qb-btn{background:#fff;color:var(--orange);padding:13px 28px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px;white-space:nowrap;transition:.2s;}
.qb-btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.15);}
.info-cards{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.info-card{background:#fff;border-radius:14px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.06);}
.info-card h4{font-size:14px;font-weight:700;color:var(--dark);margin-bottom:10px;display:flex;align-items:center;gap:8px;}
.info-card h4 i{color:var(--orange);}
.info-card ul{list-style:none;display:flex;flex-direction:column;gap:7px;}
.info-card ul li{font-size:14px;color:#555;display:flex;align-items:flex-start;gap:8px;line-height:1.4;}
.info-card ul li::before{content:'→';color:var(--orange);font-weight:700;flex-shrink:0;}
@media(max-width:600px){.quiz-banner{flex-direction:column;}.info-cards{grid-template-columns:1fr;}.hero{flex-direction:column;}}
</style>
</head>
<body>

<div class="topbar">
    <a href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <span class="sep">/</span>
    <span class="cur"><?= htmlspecialchars($module_title) ?></span>
</div>

<div class="hero">
    <div class="hero-icon"><i class="fas <?= $module_icon ?>"></i></div>
    <div>
        <h1><?= htmlspecialchars($module_title) ?></h1>
        <p><?= htmlspecialchars($module_desc) ?></p>
        <div class="hero-stats">
            <div class="hs"><i class="fas fa-clock"></i> <?= count($lessons) * 4 ?> min total</div>
            <div class="hs"><i class="fas fa-list"></i> <?= count($lessons) ?> lessons</div>
            <div class="hs"><i class="fas fa-bolt"></i> <?= array_sum(array_column($lessons,'xp')) ?> XP available</div>
        </div>
    </div>
</div>

<div class="container">
    <div class="xp-bar-wrap">
        <div class="xp-icon"><i class="fas fa-bolt"></i></div>
        <div class="xp-info">
            <div class="xp-label">YOUR PROGRESS</div>
            <div class="xp-bar"><div class="xp-fill"></div></div>
        </div>
        <div class="xp-num">0 XP</div>
    </div>

    <div class="section-title">Lessons</div>
    <div class="lessons">
        <?php foreach ($lessons as $i => $lesson): ?>
        <a href="quiz.php?module=<?= $module_key ?>" class="lesson-card <?= $i === 0 ? '' : 'locked' ?>">
            <div class="lc-icon"><i class="fas <?= $lesson['icon'] ?>"></i></div>
            <div class="lc-body">
                <div class="lc-title"><?= htmlspecialchars($lesson['title']) ?></div>
                <div class="lc-meta">
                    <span><i class="fas fa-clock"></i> <?= $lesson['time'] ?></span>
                    <span class="lc-xp">+<?= $lesson['xp'] ?> XP</span>
                </div>
            </div>
            <div class="lc-status">
                <?php if ($i === 0): ?>
                <span class="status-next">Start →</span>
                <?php else: ?>
                <i class="fas fa-lock status-locked"></i>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="quiz-banner">
        <div class="qb-text">
            <h3>Ready to test your knowledge?</h3>
            <p>Take the quiz after completing the lessons to earn your XP and certificate.</p>
        </div>
        <a href="quiz.php?module=<?= $module_key ?>" class="qb-btn"><i class="fas fa-play"></i> Take Quiz</a>
    </div>

    <div class="info-cards">
        <div class="info-card">
            <h4><i class="fas fa-exclamation-triangle"></i> Key red flags</h4>
            <ul>
                <?php
                $flags = [
                    'password'   => ['Reusing the same password','Passwords under 12 characters','No MFA enabled','Sharing passwords with colleagues'],
                    'ransomware' => ['Unexpected file encryption','Ransom note appearing','Files renamed with strange extensions','System behaving slowly'],
                    'remote'     => ['Using public Wi-Fi without VPN','Unsecured home router','Sharing work screens in public','Weak home network passwords'],
                ];
                foreach (($flags[$module_key] ?? []) as $f): ?>
                <li><?= htmlspecialchars($f) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="info-card">
            <h4><i class="fas fa-lightbulb"></i> Best practices</h4>
            <ul>
                <?php
                $tips = [
                    'password'   => ['Use a password manager','Enable MFA on all accounts','Use 16+ character passphrases','Never reuse passwords'],
                    'ransomware' => ['Keep backups offline and tested','Never pay the ransom','Patch software regularly','Report immediately to IT'],
                    'remote'     => ['Always use company VPN','Lock screen when stepping away','Use secure video conferencing','Update home router firmware'],
                ];
                foreach (($tips[$module_key] ?? []) as $t): ?>
                <li><?= htmlspecialchars($t) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<?php include '../trainee-sidebar.php'; ?>
</body>
</html>
