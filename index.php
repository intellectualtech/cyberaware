<?php
require_once 'config/database.php';
if (isLoggedIn()) {
    if (hasRole('superadmin')) {
        header('Location: superadmin/dashboard.php');
    } elseif (hasRole('manager')) {
        header('Location: manager/dashboard.php');
    } elseif (hasRole('admin')) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: trainee/dashboard.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CyberAware — Security Awareness Training</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--orange:#FF8C42;--orange-light:#FFF4EC;--orange-mid:#FFD4B3;--white:#FFFFFF;--grey:#F5F5F5;--grey2:#E8E8E8;--dark:#1A1A2E;--text:#333;}
body{font-family:'Manrope',sans-serif;background:var(--white);color:var(--text);overflow-x:hidden;}
/* NAV */
nav{background:var(--white);border-bottom:2px solid var(--grey2);padding:0 5%;display:flex;align-items:center;justify-content:space-between;height:70px;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.06);}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none;flex-shrink:0;}
.nav-logo .icon{width:40px;height:40px;background:var(--orange);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;}
.nav-logo span{font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;color:var(--dark);}
.nav-links{display:flex;align-items:center;gap:32px;position:absolute;left:50%;transform:translateX(-50%);}
.nav-links a{text-decoration:none;color:var(--text);font-weight:600;font-size:15px;transition:.2s;white-space:nowrap;}
.nav-links a:hover{color:var(--orange);}
.nav-cta{background:var(--orange);color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:700;font-size:15px;transition:.2s;flex-shrink:0;}
.nav-cta:hover{background:#e07030;transform:translateY(-1px);}
/* HERO */
.hero{background:linear-gradient(135deg,var(--orange-light) 0%,var(--white) 60%);padding:90px 5% 80px;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;min-height:88vh;}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:var(--orange-mid);color:#b85a00;padding:7px 16px;border-radius:20px;font-size:13px;font-weight:700;margin-bottom:24px;}
.hero h1{font-family:'Space Grotesk',sans-serif;font-size:52px;font-weight:700;line-height:1.15;color:var(--dark);margin-bottom:20px;}
.hero h1 span{color:var(--orange);}
.hero p{font-size:18px;color:#555;line-height:1.7;margin-bottom:36px;max-width:480px;}
.hero-buttons{display:flex;gap:16px;flex-wrap:wrap;}
.btn-primary{background:var(--orange);color:#fff;padding:15px 32px;border-radius:10px;text-decoration:none;font-weight:700;font-size:16px;display:inline-flex;align-items:center;gap:10px;transition:.2s;}
.btn-primary:hover{background:#e07030;transform:translateY(-2px);box-shadow:0 8px 24px rgba(255,140,66,.3);}
.btn-secondary{background:var(--white);color:var(--dark);padding:15px 32px;border-radius:10px;text-decoration:none;font-weight:700;font-size:16px;border:2px solid var(--grey2);display:inline-flex;align-items:center;gap:10px;transition:.2s;}
.btn-secondary:hover{border-color:var(--orange);color:var(--orange);}
.hero-visual{position:relative;}
.hero-card{background:var(--white);border-radius:20px;padding:32px;box-shadow:0 20px 60px rgba(0,0,0,.1);border:1px solid var(--grey2);}
.hero-card-top{display:flex;align-items:center;gap:12px;margin-bottom:20px;}
.hc-icon{width:48px;height:48px;background:var(--orange-light);border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--orange);font-size:22px;}
.hc-title{font-weight:700;font-size:16px;color:var(--dark);}
.hc-sub{font-size:13px;color:#888;}
.progress-row{margin-bottom:14px;}
.progress-label{display:flex;justify-content:space-between;font-size:13px;font-weight:600;margin-bottom:6px;color:var(--text);}
.progress-bar{height:10px;background:var(--grey2);border-radius:5px;overflow:hidden;}
.progress-fill{height:100%;border-radius:5px;background:var(--orange);}
.stat-row{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:20px;}
.stat-box{background:var(--grey);border-radius:10px;padding:14px;text-align:center;}
.stat-num{font-size:22px;font-weight:800;color:var(--orange);}
.stat-lbl{font-size:11px;color:#888;font-weight:600;}
.float-badge{position:absolute;top:-20px;right:-20px;background:var(--orange);color:#fff;border-radius:12px;padding:12px 18px;font-weight:700;font-size:13px;box-shadow:0 8px 24px rgba(255,140,66,.4);}
/* LOGOS */
.logos{background:var(--grey);padding:60px 5%;text-align:center;}
.logos p{font-size:13px;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.1em;margin-bottom:40px;}
.logo-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:30px;max-width:1200px;margin:0 auto;align-items:center;justify-items:center;}
.logo-item{background:var(--white);border-radius:14px;padding:24px;display:flex;align-items:center;justify-content:center;min-height:100px;border:2px solid transparent;transition:.3s;box-shadow:0 4px 12px rgba(0,0,0,.05);}
.logo-item:hover{border-color:var(--orange);transform:translateY(-4px);box-shadow:0 8px 20px rgba(255,140,66,.15);}
.logo-item img{max-width:100%;max-height:80px;object-fit:contain;}
.logo-item-text{font-size:13px;font-weight:700;color:#666;letter-spacing:.05em;text-align:center;}
/* FEATURES */
.features{padding:90px 5%;background:var(--white);}
.section-label{text-align:center;font-size:13px;font-weight:700;color:var(--orange);text-transform:uppercase;letter-spacing:.1em;margin-bottom:12px;}
.section-title{text-align:center;font-family:'Space Grotesk',sans-serif;font-size:40px;font-weight:700;color:var(--dark);margin-bottom:16px;}
.section-sub{text-align:center;font-size:17px;color:#666;max-width:560px;margin:0 auto 60px;}
.features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:28px;}
.feat-card{background:var(--grey);border-radius:16px;padding:32px;border:2px solid transparent;transition:.2s;}
.feat-card:hover{border-color:var(--orange);background:var(--orange-light);transform:translateY(-4px);}
.feat-icon{width:56px;height:56px;background:var(--orange);border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px;margin-bottom:20px;}
.feat-card h3{font-size:19px;font-weight:700;color:var(--dark);margin-bottom:10px;}
.feat-card p{font-size:15px;color:#666;line-height:1.6;}
/* MODULES */
.modules{padding:90px 5%;background:var(--orange-light);}
.modules-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-top:48px;}
.mod-card{background:var(--white);border-radius:14px;padding:24px;text-align:center;border:2px solid var(--grey2);transition:.2s;cursor:pointer;}
.mod-card:hover{border-color:var(--orange);transform:translateY(-4px);box-shadow:0 12px 32px rgba(255,140,66,.15);}
.mod-icon{width:56px;height:56px;border-radius:14px;background:var(--orange-light);display:flex;align-items:center;justify-content:center;font-size:24px;color:var(--orange);margin:0 auto 14px;}
.mod-card h4{font-size:15px;font-weight:700;color:var(--dark);margin-bottom:6px;}
.mod-card span{font-size:12px;color:#999;}
/* GAMIFICATION */
.gamify{padding:90px 5%;background:var(--white);display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;}
.gamify-visual{background:var(--dark);border-radius:24px;padding:36px;color:#fff;}
.game-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;}
.game-lives{display:flex;gap:6px;}
.heart{color:#ff4757;font-size:20px;}
.game-xp{background:var(--orange);color:#fff;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:700;}
.game-streak{background:#ffd700;color:#333;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:700;}
.lesson-path{display:flex;flex-direction:column;align-items:center;gap:16px;}
.lesson-node{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;border:3px solid rgba(255,255,255,.2);cursor:pointer;transition:.2s;}
.lesson-node.done{background:var(--orange);border-color:var(--orange);color:#fff;}
.lesson-node.active{background:var(--white);color:var(--dark);border-color:var(--white);box-shadow:0 0 0 6px rgba(255,255,255,.2);}
.lesson-node.locked{background:rgba(255,255,255,.1);color:rgba(255,255,255,.3);}
.gamify-text h2{font-family:'Space Grotesk',sans-serif;font-size:36px;font-weight:700;color:var(--dark);margin-bottom:16px;}
.gamify-text p{font-size:16px;color:#666;line-height:1.7;margin-bottom:24px;}
.gamify-pills{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:32px;}
.pill{background:var(--orange-light);color:#b85a00;padding:8px 16px;border-radius:20px;font-size:13px;font-weight:700;}
/* STATS */
.stats{background:var(--orange);padding:70px 5%;}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:32px;max-width:900px;margin:0 auto;text-align:center;}
.stats-num{font-size:52px;font-weight:800;color:#fff;margin-bottom:6px;}
.stats-lbl{font-size:15px;color:rgba(255,255,255,.8);font-weight:600;}
/* DEMO */
.demo{padding:90px 5%;background:var(--grey);display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;}
.demo h2{font-family:'Space Grotesk',sans-serif;font-size:38px;font-weight:700;color:var(--dark);margin-bottom:16px;}
.demo p{font-size:16px;color:#666;line-height:1.7;margin-bottom:32px;}
.demo-form{background:var(--white);border-radius:20px;padding:36px;box-shadow:0 8px 32px rgba(0,0,0,.08);}
.demo-form h3{font-size:22px;font-weight:700;color:var(--dark);margin-bottom:24px;}
.form-group{margin-bottom:18px;}
.form-group label{display:block;font-size:13px;font-weight:700;color:var(--dark);margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em;}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:12px 16px;border:2px solid var(--grey2);border-radius:10px;font-size:15px;font-family:'Manrope',sans-serif;transition:.2s;outline:none;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--orange);}
.form-group textarea{height:100px;resize:vertical;}
.btn-submit{width:100%;background:var(--orange);color:#fff;padding:14px;border-radius:10px;border:none;font-size:16px;font-weight:700;cursor:pointer;transition:.2s;font-family:'Manrope',sans-serif;}
.btn-submit:hover{background:#e07030;}
/* FOOTER */
footer{background:var(--dark);color:rgba(255,255,255,.7);padding:48px 5% 32px;}
.footer-top{display:grid;grid-template-columns:2fr 1fr 1fr;gap:48px;margin-bottom:40px;}
.footer-brand .icon{width:44px;height:44px;background:var(--orange);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;margin-bottom:12px;}
.footer-brand h3{color:#fff;font-size:20px;font-weight:700;margin-bottom:8px;}
.footer-brand p{font-size:14px;line-height:1.6;}
.footer-col h4{color:#fff;font-size:14px;font-weight:700;margin-bottom:16px;text-transform:uppercase;letter-spacing:.08em;}
.footer-col a{display:block;color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;margin-bottom:10px;transition:.2s;}
.footer-col a:hover{color:var(--orange);}
.footer-bottom{border-top:1px solid rgba(255,255,255,.1);padding-top:24px;display:flex;justify-content:space-between;align-items:center;font-size:13px;}
.btn-submit {
    padding: 12px 20px;
    border-radius: 999px;
    border: none;
    background: var(--orange);
    color: white;
    font-weight: 700;
    cursor: pointer;
    font-size: 15px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    width: 100%;
    justify-content: center;
}

.btn-submit:hover {
    background: #e07030;
    transform: translateY(-2px);
}
@media(max-width:900px){
.hero,.gamify,.demo{grid-template-columns:1fr;}
.hero-visual,.float-badge{display:none;}
.hero h1{font-size:36px;}
.nav-links{display:none;}
.footer-top{grid-template-columns:1fr;}
}
</style>
</head>
<body>

<!-- NAV -->
<nav>
    <a href="index.php" class="nav-logo">
        <div class="icon"><i class="fas fa-shield-alt"></i></div>
        <span>CyberAware</span>
    </a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="#features">Features</a>
        <a href="services.php">Services</a>
        <a href="about.php">About Us</a>
        <a href="compliance.php">Compliance</a>
        <a href="contact.php">Contact Us</a>
    </div>
    <a href="pages/login.php" class="nav-cta"><i class="fas fa-sign-in-alt"></i> Login</a>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-text">
        <div class="hero-badge"><i class="fas fa-star"></i> Trusted by organisations in Namibia</div>
        <h1>Turn your team into a <span>human firewall</span></h1>
        <p>Gamified cybersecurity awareness training that actually works. Real phishing simulations, interactive lessons, and measurable results.</p>
        <div class="hero-buttons">
            <a href="contact.php" class="btn-primary"><i class="fas fa-envelope"></i> Get in Touch</a>
            <a href="pages/login.php" class="btn-secondary"><i class="fas fa-sign-in-alt"></i> Login</a>
        </div>
    </div>
    <div class="hero-visual">
        <div class="float-badge">🔥 7-day streak!</div>
        <div class="hero-card">
            <div class="hero-card-top">
                <div class="hc-icon"><i class="fas fa-shield-alt"></i></div>
                <div>
                    <div class="hc-title">Security Progress</div>
                    <div class="hc-sub">John Doe — Finance Dept</div>
                </div>
            </div>
            <div class="progress-row">
                <div class="progress-label"><span>Phishing Recognition</span><span>85%</span></div>
                <div class="progress-bar"><div class="progress-fill" style="width:85%"></div></div>
            </div>
            <div class="progress-row">
                <div class="progress-label"><span>Password Security</span><span>72%</span></div>
                <div class="progress-bar"><div class="progress-fill" style="width:72%"></div></div>
            </div>
            <div class="progress-row">
                <div class="progress-label"><span>Social Engineering</span><span>91%</span></div>
                <div class="progress-bar"><div class="progress-fill" style="width:91%"></div></div>
            </div>
            <div class="stat-row">
                <div class="stat-box"><div class="stat-num">840</div><div class="stat-lbl">XP Earned</div></div>
                <div class="stat-box"><div class="stat-num">5/7</div><div class="stat-lbl">Modules</div></div>
                <div class="stat-box"><div class="stat-num">Low</div><div class="stat-lbl">Risk Level</div></div>
            </div>
        </div>
    </div>
</section>

<!-- LOGOS -->
<div class="logos">
    <p>Trusted by organisations across Namibia</p>
    <div class="logo-row">
        <div class="logo-item">
            <div style="text-align:center;">
                <img src="assets/logos/fist capital.jpg" alt="First Capital" style="max-width:100%;max-height:80px;object-fit:contain;margin:0 auto 8px;display:block;">
                <div class="logo-item-text">FIRST CAPITAL</div>
            </div>
        </div>
        <div class="logo-item">
            <div style="text-align:center;">
                <img src="assets/logos/agra co-op.png" alt="Agra Co-op" style="max-width:100%;max-height:80px;object-fit:contain;margin:0 auto 8px;display:block;">
                <div class="logo-item-text">AGRA CO-OP</div>
            </div>
        </div>
        <div class="logo-item">
            <div style="text-align:center;">
                <img src="assets/logos/intellectual technology.png" alt="Intellectual Technology" style="max-width:100%;max-height:80px;object-fit:contain;margin:0 auto 8px;display:block;">
                <div class="logo-item-text">INTELLECTUAL TECHNOLOGY</div>
            </div>
        </div>
        <div class="logo-item">
            <div style="text-align:center;">
                <img src="assets/logos/nust logo.jpg" alt="NUST" style="max-width:100%;max-height:80px;object-fit:contain;margin:0 auto 8px;display:block;">
                <div class="logo-item-text">NUST</div>
            </div>
        </div>
        <div class="logo-item">
            <div style="text-align:center;">
                <img src="assets/logos/your company.jpg" alt="Your Company" style="max-width:100%;max-height:80px;object-fit:contain;margin:0 auto 8px;display:block;">
                <div class="logo-item-text">YOUR COMPANY</div>
            </div>
        </div>
    </div>
</div>

<!-- FEATURES -->
<section class="features" id="features">
    <div class="section-label">Why CyberAware</div>
    <div class="section-title">Everything you need to protect your team</div>
    <p class="section-sub">From phishing simulations to gamified lessons — built for real organisations.</p>
    <div class="features-grid">
        <div class="feat-card">
            <div class="feat-icon"><i class="fas fa-fish"></i></div>
            <h3>Real Phishing Simulations</h3>
            <p>Send safe fake phishing emails to employees and track who clicks, who reports, and who ignores them.</p>
        </div>
        <div class="feat-card">
            <div class="feat-icon"><i class="fas fa-gamepad"></i></div>
            <h3>Gamified Like Duolingo</h3>
            <p>XP points, lives, streaks and badges make training fun. Employees actually want to come back.</p>
        </div>
        <div class="feat-card">
            <div class="feat-icon"><i class="fas fa-user-shield"></i></div>
            <h3>Per-Employee Tracking</h3>
            <p>See exactly where each employee passed or failed. Track improvement over time individually.</p>
        </div>
        <div class="feat-card">
            <div class="feat-icon"><i class="fas fa-chart-bar"></i></div>
            <h3>Security Posture Dashboard</h3>
            <p>Get a real-time view of your organisation's human defence score — by department and individual.</p>
        </div>
        <div class="feat-card">
            <div class="feat-icon"><i class="fas fa-bell"></i></div>
            <h3>Auto Notifications</h3>
            <p>Admins are automatically notified when new training content is available to assign to their teams.</p>
        </div>
        <div class="feat-card">
            <div class="feat-icon"><i class="fas fa-file-contract"></i></div>
            <h3>Compliance Ready</h3>
            <p>Aligned with ISO 27001, NIST, GDPR and POPIA. Export audit-ready reports anytime.</p>
        </div>
    </div>
</section>

<!-- MODULES -->
<section class="modules" id="modules">
    <div class="section-label">Training Content</div>
    <div class="section-title">7 real-world security modules</div>
    <p class="section-sub">Each module uses interactive simulations — not boring videos.</p>
    <div class="modules-grid">
        <div class="mod-card">
            <div class="mod-icon"><i class="fas fa-envelope"></i></div>
            <h4>Phishing Emails</h4>
        </div>
        <div class="mod-card">
            <div class="mod-icon"><i class="fas fa-key"></i></div>
            <h4>Fake Login Pages</h4>
        </div>
        <div class="mod-card">
            <div class="mod-icon"><i class="fas fa-phone-alt"></i></div>
            <h4>Social Engineering</h4>
        </div>
        <div class="mod-card">
            <div class="mod-icon"><i class="fas fa-paperclip"></i></div>
            <h4>Dangerous Attachments</h4>
        </div>
        <div class="mod-card">
            <div class="mod-icon"><i class="fas fa-lock"></i></div>
            <h4>Password Security</h4>
        </div>
        <div class="mod-card">
            <div class="mod-icon"><i class="fas fa-virus"></i></div>
            <h4>Ransomware Awareness</h4>
        </div>
        <div class="mod-card">
            <div class="mod-icon"><i class="fas fa-home"></i></div>
            <h4>Remote Work Security</h4>
        </div>
    </div>
</section>

<!-- STATS -->
<section class="stats">
    <div class="stats-grid">
        <div><div class="stats-num">1,000+</div><div class="stats-lbl">Users Trained</div></div>
        <div><div class="stats-num">7</div><div class="stats-lbl">Security Modules</div></div>
        <div><div class="stats-num">85%</div><div class="stats-lbl">Threat Awareness Increase</div></div>
        <div><div class="stats-num">72%</div><div class="stats-lbl">Incident Reduction</div></div>
    </div>
</section>

<!-- GAMIFICATION -->
<section class="gamify">
    <div class="gamify-visual">
        <div class="game-header">
            <div class="game-lives">
                <span class="heart">♥</span>
                <span class="heart">♥</span>
                <span class="heart">♥</span>
                <span class="heart" style="opacity:.3">♥</span>
                <span class="heart" style="opacity:.3">♥</span>
            </div>
            <span class="game-xp">⚡ 840 XP</span>
            <span class="game-streak">🔥 7 streak</span>
        </div>
        <div class="lesson-path">
            <div class="lesson-node done"><i class="fas fa-check"></i></div>
            <div class="lesson-node done"><i class="fas fa-check"></i></div>
            <div class="lesson-node active"><i class="fas fa-envelope"></i></div>
            <div class="lesson-node locked"><i class="fas fa-lock"></i></div>
            <div class="lesson-node locked"><i class="fas fa-lock"></i></div>
        </div>
        <div style="text-align:center;margin-top:20px;color:rgba(255,255,255,.6);font-size:14px;">Phishing Recognition — Level 3</div>
    </div>
    <div class="gamify-text">
        <h2>Training that feels like a game</h2>
        <p>We designed CyberAware inspired by Duolingo — short lessons, instant feedback, and a reward system that keeps employees coming back every day.</p>
        <div class="gamify-pills">
            <span class="pill">🏆 Earn XP</span>
            <span class="pill">❤️ 5 Lives per day</span>
            <span class="pill">🔥 Daily streaks</span>
            <span class="pill">🥇 Leaderboard</span>
            <span class="pill">🎓 Certificates</span>
        </div>
        <a href="#demo" class="btn-primary"><i class="fas fa-play"></i> See it in action</a>
    </div>
</section>


<?php include 'includes/footer.php'; ?>

</body>
</html>