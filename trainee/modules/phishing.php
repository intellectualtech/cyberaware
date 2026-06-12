<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Phishing Email Recognition — CyberAware</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--orange:#FF8C42;--ol:#FFF4EC;--dark:#1A1A2E;--grey:#F5F5F5;--grey2:#E8E8E8;--green:#10B981;--red:#EF4444;}
body{font-family:'Manrope',sans-serif;background:var(--grey);min-height:100vh;}

/* TOP BAR */
.topbar{background:var(--dark);padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:200;}
.tb-left{display:flex;align-items:center;gap:16px;}
.tb-back{color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;font-weight:600;display:flex;align-items:center;gap:6px;transition:.2s;}
.tb-back:hover{color:#fff;}
.tb-title{color:#fff;font-weight:700;font-size:15px;}
.tb-right{display:flex;align-items:center;gap:12px;}
.tb-lives{display:flex;gap:3px;}
.heart{color:#ff4757 !important;font-size:18px;transition:all .3s;}
.heart.lost{color:rgba(255,255,255,.2) !important;transform:scale(.85);}
.tb-xp{background:rgba(255,140,66,.2);color:var(--orange);padding:5px 14px;border-radius:20px;font-size:13px;font-weight:700;}

/* PROGRESS BAR */
.prog-wrap{background:rgba(255,255,255,.1);height:6px;}
.prog-bar{height:100%;background:linear-gradient(90deg,var(--orange),#ffb347);transition:width .6s cubic-bezier(.4,0,.2,1);}

/* SCREEN SYSTEM */
.screen{display:none;animation:fadeIn .4s ease;}
.screen.active{display:block;}
@keyframes fadeIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}

/* LESSON SCREENS */
.lesson-wrap{max-width:720px;margin:40px auto;padding:0 20px 80px;}
.lesson-card{background:#fff;border-radius:20px;padding:36px;box-shadow:0 4px 24px rgba(0,0,0,.07);margin-bottom:20px;}
.lesson-badge{display:inline-flex;align-items:center;gap:8px;background:var(--ol);color:#b85a00;padding:7px 16px;border-radius:20px;font-size:13px;font-weight:700;margin-bottom:20px;}
.lesson-card h2{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;color:var(--dark);margin-bottom:14px;line-height:1.3;}
.lesson-card p{font-size:16px;color:#444;line-height:1.8;margin-bottom:16px;}
.highlight-box{background:var(--ol);border-left:4px solid var(--orange);border-radius:0 12px 12px 0;padding:16px 20px;margin:20px 0;font-size:15px;color:#7a3e00;line-height:1.7;}
.red-flag-list{list-style:none;display:flex;flex-direction:column;gap:10px;margin:16px 0;}
.red-flag-list li{display:flex;align-items:flex-start;gap:12px;background:#fff5f5;border-radius:10px;padding:14px 16px;font-size:15px;color:#333;line-height:1.5;}
.red-flag-list li .rf-icon{color:var(--red);font-size:18px;flex-shrink:0;margin-top:2px;}
.tip-list{list-style:none;display:flex;flex-direction:column;gap:10px;margin:16px 0;}
.tip-list li{display:flex;align-items:flex-start;gap:12px;background:#f0fdf4;border-radius:10px;padding:14px 16px;font-size:15px;color:#333;line-height:1.5;}
.tip-list li .ti-icon{color:var(--green);font-size:18px;flex-shrink:0;margin-top:2px;}

/* EMAIL SIMULATION */
.email-sim{background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.1);margin:20px 0;border:1px solid var(--grey2);}
.email-toolbar{background:#f3f4f6;padding:10px 16px;display:flex;gap:8px;align-items:center;border-bottom:1px solid var(--grey2);}
.etb-btn{width:28px;height:28px;border-radius:6px;background:#e5e7eb;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6b7280;font-size:13px;}
.email-header{background:#fafafa;padding:18px 24px;border-bottom:1px solid var(--grey2);}
.eh-row{display:flex;gap:10px;margin-bottom:8px;font-size:14px;}
.eh-label{font-weight:700;color:#374151;min-width:50px;}
.eh-val{color:#111;flex:1;}
.eh-val.suspicious{color:#dc2626;font-weight:600;}
.email-subject{padding:14px 24px;font-size:18px;font-weight:700;color:#111;border-bottom:1px solid var(--grey2);}
.email-body{padding:20px 24px;font-size:15px;line-height:1.8;color:#333;}
.fake-link{color:#2563eb;text-decoration:underline;cursor:pointer;}
.email-actions{padding:16px 24px;background:#f9fafb;display:flex;gap:10px;flex-wrap:wrap;}
.ea-btn{padding:10px 20px;border-radius:8px;font-size:14px;font-weight:700;border:2px solid transparent;cursor:pointer;transition:all .2s;font-family:'Manrope',sans-serif;display:flex;align-items:center;gap:8px;}
.ea-report{background:var(--green);color:#fff;}
.ea-report:hover{background:#059669;transform:translateY(-2px);}
.ea-click{background:#fff;color:var(--red);border-color:var(--red);}
.ea-click:hover{background:var(--red);color:#fff;}
.ea-delete{background:#fff;color:#6b7280;border-color:#e5e7eb;}
.ea-delete:hover{background:#f3f4f6;}

/* FEEDBACK OVERLAY */
.feedback-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:300;display:flex;align-items:center;justify-content:center;animation:fadeIn .3s ease;}
.feedback-box{background:#fff;border-radius:24px;padding:40px;max-width:440px;width:90%;text-align:center;animation:popIn .4s cubic-bezier(.175,.885,.32,1.275);}
@keyframes popIn{from{transform:scale(.7);opacity:0}to{transform:scale(1);opacity:1}}
.fb-icon{font-size:64px;margin-bottom:16px;}
.fb-title{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;margin-bottom:10px;}
.fb-title.good{color:var(--green);}
.fb-title.bad{color:var(--red);}
.fb-text{font-size:15px;color:#555;line-height:1.7;margin-bottom:24px;}
.fb-btn{background:var(--orange);color:#fff;border:none;padding:14px 32px;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;transition:.2s;}
.fb-btn:hover{background:#e07030;}
.fb-xp{background:var(--ol);color:#b85a00;padding:8px 20px;border-radius:20px;font-size:14px;font-weight:700;display:inline-block;margin-bottom:16px;}

/* XP ANIMATION */
.xp-pop{position:fixed;top:80px;right:24px;background:var(--orange);color:#fff;padding:10px 20px;border-radius:20px;font-weight:800;font-size:16px;z-index:400;animation:xpFloat 1.5s ease forwards;}
@keyframes xpFloat{0%{opacity:0;transform:translateY(10px)}20%{opacity:1;transform:translateY(0)}80%{opacity:1;transform:translateY(-20px)}100%{opacity:0;transform:translateY(-40px)}}

/* QUIZ */
.quiz-wrap{max-width:720px;margin:40px auto;padding:0 20px 80px;}
.quiz-header{background:var(--dark);border-radius:16px;padding:24px 28px;color:#fff;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;}
.qh-left h2{font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;margin-bottom:4px;}
.qh-left p{color:rgba(255,255,255,.6);font-size:14px;}
.qh-right{background:rgba(255,255,255,.1);border-radius:12px;padding:12px 20px;text-align:center;}
.qh-num{font-size:28px;font-weight:800;color:var(--orange);}
.qh-lbl{font-size:11px;color:rgba(255,255,255,.6);font-weight:600;}
.q-card{background:#fff;border-radius:20px;padding:32px;box-shadow:0 4px 20px rgba(0,0,0,.07);margin-bottom:20px;}
.q-num{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--orange);margin-bottom:12px;}
.q-text{font-size:19px;font-weight:700;color:var(--dark);margin-bottom:24px;line-height:1.5;}
.options{display:flex;flex-direction:column;gap:12px;}
.option{display:flex;align-items:center;gap:14px;padding:16px 20px;border:2px solid var(--grey2);border-radius:12px;cursor:pointer;transition:all .2s;font-size:15px;font-weight:600;color:var(--dark);}
.option:hover{border-color:var(--orange);background:var(--ol);}
.option.selected{border-color:var(--orange);background:var(--ol);}
.option.correct{border-color:var(--green);background:#f0fdf4;color:#065f46;}
.option.wrong{border-color:var(--red);background:#fff5f5;color:#7f1d1d;}
.option-letter{width:32px;height:32px;border-radius:8px;background:var(--grey2);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;transition:.2s;}
.option.selected .option-letter{background:var(--orange);color:#fff;}
.option.correct .option-letter{background:var(--green);color:#fff;}
.option.wrong .option-letter{background:var(--red);color:#fff;}
.why-box{margin-top:16px;padding:16px 18px;background:var(--ol);border-left:4px solid var(--orange);border-radius:0 10px 10px 0;font-size:14px;color:#7a3e00;line-height:1.6;display:none;}
.why-box.show{display:block;animation:fadeIn .3s ease;}

/* CONTINUE BUTTON */
.continue-bar{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid var(--grey2);padding:16px 24px;display:flex;justify-content:center;gap:16px;z-index:100;}
.btn-continue{background:var(--orange);color:#fff;border:none;padding:15px 48px;border-radius:12px;font-size:17px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;transition:.2s;display:flex;align-items:center;gap:10px;}
.btn-continue:hover{background:#e07030;transform:translateY(-2px);}
.btn-continue:disabled{background:var(--grey2);color:#aaa;cursor:not-allowed;transform:none;}
.btn-check{background:var(--dark);color:#fff;border:none;padding:15px 48px;border-radius:12px;font-size:17px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;transition:.2s;}
.btn-check:hover{background:#2d2d4e;}
.btn-check:disabled{background:var(--grey2);color:#aaa;cursor:not-allowed;}

/* COMPLETION SCREEN */
.complete-wrap{max-width:560px;margin:60px auto;padding:0 20px;text-align:center;}
.complete-card{background:#fff;border-radius:24px;padding:48px 40px;box-shadow:0 8px 40px rgba(0,0,0,.1);}
.trophy{font-size:80px;margin-bottom:20px;animation:bounce .6s ease infinite alternate;}
@keyframes bounce{from{transform:translateY(0)}to{transform:translateY(-12px)}}
.complete-card h1{font-family:'Space Grotesk',sans-serif;font-size:32px;font-weight:700;color:var(--dark);margin-bottom:10px;}
.complete-card p{font-size:16px;color:#666;margin-bottom:28px;}
.complete-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:28px 0;}
.cs{background:var(--grey);border-radius:14px;padding:18px;}
.cs-num{font-size:28px;font-weight:800;color:var(--orange);}
.cs-lbl{font-size:12px;color:#888;font-weight:700;text-transform:uppercase;margin-top:4px;}
.btn-done{background:var(--orange);color:#fff;border:none;padding:16px 48px;border-radius:12px;font-size:17px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;transition:.2s;text-decoration:none;display:inline-block;}
.btn-done:hover{background:#e07030;}

/* STARS ANIMATION */
.stars-wrap{display:flex;justify-content:center;gap:8px;margin:20px 0;}
.star{font-size:40px;opacity:0;animation:starPop .4s ease forwards;}
.star:nth-child(1){animation-delay:.2s;}
.star:nth-child(2){animation-delay:.4s;}
.star:nth-child(3){animation-delay:.6s;}
@keyframes starPop{from{opacity:0;transform:scale(0) rotate(-30deg)}to{opacity:1;transform:scale(1) rotate(0)}}

/* INTRO SCREEN */
.intro-wrap{max-width:600px;margin:60px auto;padding:0 20px;}
.intro-card{background:#fff;border-radius:24px;padding:48px 40px;box-shadow:0 8px 40px rgba(0,0,0,.1);text-align:center;}
.intro-icon{width:90px;height:90px;background:linear-gradient(135deg,var(--orange),#ffb347);border-radius:22px;display:flex;align-items:center;justify-content:center;font-size:40px;color:#fff;margin:0 auto 24px;}
.intro-card h1{font-family:'Space Grotesk',sans-serif;font-size:30px;font-weight:700;color:var(--dark);margin-bottom:12px;}
.intro-card p{font-size:16px;color:#555;line-height:1.7;margin-bottom:28px;}
.intro-pills{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-bottom:32px;}
.intro-pill{background:var(--ol);color:#b85a00;padding:8px 18px;border-radius:20px;font-size:13px;font-weight:700;}
.btn-start{background:var(--orange);color:#fff;border:none;padding:16px 56px;border-radius:12px;font-size:18px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;transition:.2s;}
.btn-start:hover{background:#e07030;transform:translateY(-2px);box-shadow:0 8px 24px rgba(255,140,66,.3);}
</style>
</head>
<body>

<!-- TOP BAR -->
<div class="topbar">
  <div class="tb-left">
    <a href="../dashboard.php" class="tb-back"><i class="fas fa-times"></i></a>
    <span class="tb-title">Phishing Email Recognition</span>
  </div>
  <div class="tb-right">
    <div class="tb-lives">
      <i class="fas fa-heart" id="h1" style="color:#ff4757;"></i>
      <i class="fas fa-heart" id="h2" style="color:#ff4757;"></i>
      <i class="fas fa-heart" id="h3" style="color:#ff4757;"></i>
      <i class="fas fa-heart" id="h4" style="color:#ff4757;"></i>
      <i class="fas fa-heart" id="h5" style="color:#ff4757;"></i>
    </div>
    <div class="tb-xp" id="xp-display"><i class="fas fa-bolt"></i> 0 XP</div>
  </div>
</div>
<div class="prog-wrap"><div class="prog-bar" id="prog-bar" style="width:0%"></div></div>

<!-- ═══ SCREEN 0: INTRO ═══ -->
<div class="screen active" id="screen-0">
  <div class="intro-wrap">
    <div class="intro-card">
      <div class="intro-icon"><i class="fas fa-envelope"></i></div>
      <h1>Phishing Email Recognition</h1>
      <p>Learn to spot the tricks hackers use in emails to steal your credentials, money, and data. This module uses real-world examples and simulations.</p>
      <div class="intro-pills">
        <span class="intro-pill"><i class="fas fa-envelope"></i> 4 Real lessons</span>
        <span class="intro-pill"><i class="fas fa-bullseye"></i> 1 Simulation</span>
        <span class="intro-pill"><i class="fas fa-question"></i> 5 Quiz questions</span>
        <span class="intro-pill"><i class="fas fa-bolt"></i> 200 XP</span>
      </div>
      <button class="btn-start" onclick="goTo(1)"><i class="fas fa-play"></i> Start Module</button>
    </div>
  </div>
</div>

<!-- ═══ SCREEN 1: LESSON 1 ═══ -->
<div class="screen" id="screen-1">
  <div class="lesson-wrap">
    <div class="lesson-card">
      <div class="lesson-badge"><i class="fas fa-book-open"></i> Lesson 1 of 4</div>
      <h2>What is phishing?</h2>
      <p>Phishing is when a cybercriminal sends a fake email pretending to be someone you trust — your bank, your boss, or a company you use — to trick you into clicking a link, opening an attachment, or revealing sensitive information.</p>
      <div class="highlight-box">
        <strong><i class="fas fa-bullseye"></i> Key fact:</strong> Over 90% of all cyber attacks start with a phishing email. It is the number one way hackers break into organisations — not through sophisticated hacking, but through tricking humans.
      </div>
      <p>Phishing works because it exploits human psychology — urgency, fear, authority, and curiosity. The email looks real. The logo looks right. The language sounds professional. But something is always slightly off.</p>
      <h3 style="font-size:18px;font-weight:700;color:var(--dark);margin:20px 0 12px;">The anatomy of a phishing email</h3>
      <ul class="red-flag-list">
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>Spoofed sender address</strong> — looks like it's from a real company but the domain is wrong. E.g. support@paypa1.com instead of support@paypal.com</div></li>
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>Urgency and fear tactics</strong> — "Your account will be suspended in 24 hours!", "Immediate action required!"</div></li>
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>Generic greetings</strong> — "Dear Customer" or "Dear User" instead of your actual name</div></li>
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>Suspicious links</strong> — the displayed text says one thing but the real URL is different</div></li>
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>Unexpected attachments</strong> — files you didn't ask for, often .exe, .zip, or macro-enabled documents</div></li>
      </ul>
    </div>
  </div>
  <div class="continue-bar">
    <button class="btn-continue" onclick="goTo(2)"><i class="fas fa-arrow-right"></i> Continue</button>
  </div>
</div>

<!-- ═══ SCREEN 2: LESSON 2 ═══ -->
<div class="screen" id="screen-2">
  <div class="lesson-wrap">
    <div class="lesson-card">
      <div class="lesson-badge"><i class="fas fa-book-open"></i> Lesson 2 of 4</div>
      <h2>How to spot a fake sender address</h2>
      <p>The sender's email address is the first thing to check. Attackers use clever tricks to make fake addresses look real.</p>
      <div style="background:var(--dark);border-radius:14px;padding:24px;margin:20px 0;color:#fff;">
        <div style="margin-bottom:16px;font-size:13px;color:rgba(255,255,255,.5);font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Spot the difference</div>
        <div style="display:flex;flex-direction:column;gap:12px;">
          <div style="display:flex;align-items:center;justify-content:space-between;background:rgba(16,185,129,.15);border:1px solid #10B981;border-radius:10px;padding:14px 18px;">
            <span style="font-size:15px;font-weight:600;">security@nedbank.co.za</span>
            <span style="color:#10B981;font-weight:700;font-size:13px;"><i class="fas fa-check"></i> REAL</span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;background:rgba(239,68,68,.15);border:1px solid #EF4444;border-radius:10px;padding:14px 18px;">
            <span style="font-size:15px;font-weight:600;">security@nedbank-alerts.com</span>
            <span style="color:#EF4444;font-weight:700;font-size:13px;">✗ FAKE</span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;background:rgba(239,68,68,.15);border:1px solid #EF4444;border-radius:10px;padding:14px 18px;">
            <span style="font-size:15px;font-weight:600;">support@nedbank.co.za.login.com</span>
            <span style="color:#EF4444;font-weight:700;font-size:13px;">✗ FAKE</span>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;background:rgba(239,68,68,.15);border:1px solid #EF4444;border-radius:10px;padding:14px 18px;">
            <span style="font-size:15px;font-weight:600;">noreply@nedbank-secure.net</span>
            <span style="color:#EF4444;font-weight:700;font-size:13px;">✗ FAKE</span>
          </div>
        </div>
      </div>
      <div class="highlight-box">
        <strong>💡 The golden rule:</strong> The real domain is always the last part before the first slash. In "nedbank.co.za.login.com" — the actual domain is <strong>login.com</strong>, not nedbank. Never trust a domain just because it contains a brand name.
      </div>
      <h3 style="font-size:18px;font-weight:700;color:var(--dark);margin:20px 0 12px;">Common tricks attackers use</h3>
      <ul class="tip-list">
        <li><i class="fas fa-info-circle ti-icon"></i><div><strong>Extra words added:</strong> nedbank-secure.com, nedbank-alerts.net</div></li>
        <li><i class="fas fa-info-circle ti-icon"></i><div><strong>Character substitution:</strong> paypa<strong>1</strong>.com (number 1 instead of letter l)</div></li>
        <li><i class="fas fa-info-circle ti-icon"></i><div><strong>Wrong TLD:</strong> sars.gov.za becomes sars.gov.co or sars.com</div></li>
        <li><i class="fas fa-info-circle ti-icon"></i><div><strong>Subdomain tricks:</strong> nedbank.login-secure.com — nedbank is just a subdomain</div></li>
      </ul>
    </div>
  </div>
  <div class="continue-bar">
    <button class="btn-continue" onclick="goTo(3)"><i class="fas fa-arrow-right"></i> Continue</button>
  </div>
</div>

<!-- ═══ SCREEN 3: LESSON 3 ═══ -->
<div class="screen" id="screen-3">
  <div class="lesson-wrap">
    <div class="lesson-card">
      <div class="lesson-badge"><i class="fas fa-book-open"></i> Lesson 3 of 4</div>
      <h2>Urgency tactics and psychological tricks</h2>
      <p>Phishing emails are carefully designed to bypass your rational thinking. They create emotional states — fear, urgency, excitement, or curiosity — that make you act before you think.</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:20px 0;">
        <div style="background:#fff5f5;border-radius:12px;padding:18px;border-left:4px solid var(--red);">
          <div style="font-weight:700;color:var(--red);margin-bottom:8px;font-size:14px;">😨 FEAR</div>
          <p style="font-size:14px;color:#333;line-height:1.5;">"Your account has been compromised. Verify immediately or it will be permanently deleted."</p>
        </div>
        <div style="background:#fffbeb;border-radius:12px;padding:18px;border-left:4px solid #f59e0b;">
          <div style="font-weight:700;color:#d97706;margin-bottom:8px;font-size:14px;"><i class="fas fa-hourglass-end"></i> URGENCY</div>
          <p style="font-size:14px;color:#333;line-height:1.5;">"You have 24 hours to confirm your identity or your account will be suspended."</p>
        </div>
        <div style="background:#f0fdf4;border-radius:12px;padding:18px;border-left:4px solid var(--green);">
          <div style="font-weight:700;color:var(--green);margin-bottom:8px;font-size:14px;">🎁 GREED</div>
          <p style="font-size:14px;color:#333;line-height:1.5;">"Congratulations! You have won a R5,000 gift card. Click here to claim it now."</p>
        </div>
        <div style="background:#eff6ff;border-radius:12px;padding:18px;border-left:4px solid #3b82f6;">
          <div style="font-weight:700;color:#2563eb;margin-bottom:8px;font-size:14px;">👑 AUTHORITY</div>
          <p style="font-size:14px;color:#333;line-height:1.5;">"This is a message from CEO David. Please process this payment immediately and discreetly."</p>
        </div>
      </div>
      <div class="highlight-box">
        <strong><i class="fas fa-shield-alt"></i> Your defence:</strong> Whenever you feel pressure to act fast, STOP. Legitimate organisations never threaten to suspend accounts or demand immediate action via email. Take a breath and verify through official channels.
      </div>
    </div>
  </div>
  <div class="continue-bar">
    <button class="btn-continue" onclick="goTo(4)"><i class="fas fa-arrow-right"></i> Continue</button>
  </div>
</div>

<!-- ═══ SCREEN 4: LESSON 4 ═══ -->
<div class="screen" id="screen-4">
  <div class="lesson-wrap">
    <div class="lesson-card">
      <div class="lesson-badge"><i class="fas fa-book-open"></i> Lesson 4 of 4</div>
      <h2>What to do when you suspect phishing</h2>
      <ul class="tip-list">
        <li><i class="fas fa-check-circle ti-icon"></i><div><strong>Do not click any links</strong> — hover to preview the URL first. If it looks wrong, trust your instinct.</div></li>
        <li><i class="fas fa-check-circle ti-icon"></i><div><strong>Do not open attachments</strong> — especially unexpected ones, even from known senders.</div></li>
        <li><i class="fas fa-check-circle ti-icon"></i><div><strong>Do not reply</strong> — replying confirms your email address is active to the attacker.</div></li>
        <li><i class="fas fa-check-circle ti-icon"></i><div><strong>Report it</strong> — use your company's phishing report button or forward to your IT/security team.</div></li>
        <li><i class="fas fa-check-circle ti-icon"></i><div><strong>Verify separately</strong> — if the email claims to be from your bank, call them directly using the number on their official website.</div></li>
        <li><i class="fas fa-check-circle ti-icon"></i><div><strong>If you clicked</strong> — disconnect from the network immediately and report to IT. Act fast to limit damage.</div></li>
      </ul>
      <div class="highlight-box">
        <strong><i class="fas fa-trophy"></i> Remember:</strong> Reporting a phishing email protects your entire organisation — not just you. Your quick action can prevent colleagues from falling for the same attack.
      </div>
    </div>
  </div>
  <div class="continue-bar">
    <button class="btn-continue" onclick="goTo(5)"><i class="fas fa-arrow-right"></i> Try the Simulation</button>
  </div>
</div>

<!-- ═══ SCREEN 5: SIMULATION ═══ -->
<div class="screen" id="screen-5">
  <div class="lesson-wrap">
    <div class="lesson-card" style="text-align:center;padding:24px;">
      <div class="lesson-badge"><i class="fas fa-flask"></i> Live Simulation</div>
      <h2>Is this email real or phishing?</h2>
      <p style="color:#666;font-size:15px;">You just received this email at work. What do you do?</p>
    </div>
    <div class="email-sim">
      <div class="email-toolbar">
        <button class="etb-btn"><i class="fas fa-reply"></i></button>
        <button class="etb-btn"><i class="fas fa-share"></i></button>
        <button class="etb-btn"><i class="fas fa-trash"></i></button>
        <button class="etb-btn"><i class="fas fa-archive"></i></button>
      </div>
      <div class="email-header">
        <div class="eh-row"><span class="eh-label">From:</span><span class="eh-val suspicious">IT-Support &lt;itsupport@company-helpdesk.net&gt;</span></div>
        <div class="eh-row"><span class="eh-label">To:</span><span class="eh-val">you@yourcompany.com</span></div>
        <div class="eh-row"><span class="eh-label">Date:</span><span class="eh-val">Monday, 13 April 2026, 09:14</span></div>
      </div>
      <div class="email-subject"><i class="fas fa-exclamation-triangle"></i> URGENT: Your account password expires in 2 hours</div>
      <div class="email-body">
        <p>Dear Employee,</p><br>
        <p>Our security system has detected that your company account password will expire in <strong>2 hours</strong>. If you do not update your password immediately, you will lose access to all company systems including email, SharePoint, and Teams.</p><br>
        <p>Please click the link below to verify your identity and update your password:</p><br>
        <p><span class="fake-link">→ Click here to update your password now</span></p><br>
        <p>This link will expire in 2 hours. Do not share this link with anyone.</p><br>
        <p>If you did not receive this email, please contact the IT helpdesk immediately.</p><br>
        <p>Regards,<br>IT Support Team<br>Company Helpdesk</p>
      </div>
      <div class="email-actions">
        <button class="ea-btn ea-report" onclick="simAction('report')"><i class="fas fa-flag"></i> Report Phishing</button>
        <button class="ea-btn ea-click" onclick="simAction('click')"><i class="fas fa-mouse-pointer"></i> Click the Link</button>
        <button class="ea-btn ea-delete" onclick="simAction('delete')"><i class="fas fa-trash"></i> Just Delete It</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══ SCREEN 6-10: QUIZ ═══ -->
<div class="screen" id="screen-quiz">
  <div class="quiz-wrap">
    <div class="quiz-header">
      <div class="qh-left">
        <h2>Knowledge Check</h2>
        <p>Answer all 5 questions to complete the module</p>
      </div>
      <div class="qh-right">
        <div class="qh-num" id="q-counter">1/5</div>
        <div class="qh-lbl">Question</div>
      </div>
    </div>
    <div id="quiz-container"></div>
  </div>
  <div class="continue-bar">
    <button class="btn-check" id="btn-check" onclick="checkAnswer()" disabled>Check Answer</button>
    <button class="btn-continue" id="btn-next" onclick="nextQuestion()" style="display:none">Next <i class="fas fa-arrow-right"></i></button>
  </div>
</div>

<!-- ═══ SCREEN: COMPLETE ═══ -->
<div class="screen" id="screen-complete">
  <div class="complete-wrap">
    <div class="complete-card">
      <div class="trophy"><i class="fas fa-trophy"></i></div>
      <div class="stars-wrap">
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
        <i class="fas fa-star"></i>
      </div>
      <h1>Module Complete!</h1>
      <p>Outstanding work! You've mastered Phishing Email Recognition.</p>
      <div class="complete-stats">
        <div class="cs"><div class="cs-num" id="final-score">0%</div><div class="cs-lbl">Score</div></div>
        <div class="cs"><div class="cs-num" id="final-xp">0</div><div class="cs-lbl">XP Earned</div></div>
        <div class="cs"><div class="cs-num">4</div><div class="cs-lbl">Lessons</div></div>
      </div>
      <a href="../dashboard.php" class="btn-done"><i class="fas fa-home"></i> Back to Dashboard</a>
    </div>
  </div>
</div>

<!-- FEEDBACK OVERLAY -->
<div class="feedback-overlay" id="feedback-overlay" style="display:none">
  <div class="feedback-box" id="feedback-box">
    <div class="fb-icon" id="fb-icon"></div>
    <div class="fb-xp" id="fb-xp"></div>
    <div class="fb-title" id="fb-title"></div>
    <div class="fb-text" id="fb-text"></div>
    <button class="fb-btn" id="fb-btn" onclick="closeFeedback()">Continue</button>
  </div>
</div>

<script>
// ── STATE ──────────────────────────────────────────────
let currentScreen = 0;
let xp = 0;
let lives = 5;
let quizScore = 0;
let currentQuestion = 0;
let selectedOption = null;
let answered = false;

const TOTAL_SCREENS = 7; // intro + 4 lessons + sim + quiz

const questions = [
  {
    q: "You receive an email from 'support@nedbank-secure.net' asking you to verify your account. What is the FIRST thing you should do?",
    opts: ["Click the link — it mentions Nedbank so it must be real","Check the full domain — nedbank-secure.net is NOT nedbank.co.za","Reply to ask if it's legitimate","Delete it and forget about it"],
    ans: 1,
    why: "The real Nedbank domain is nedbank.co.za. Attackers add words like 'secure' to fake domains. Always verify the full domain — not just whether it contains a brand name."
  },
  {
    q: "An email says 'Your account will be suspended in 24 hours — act NOW!' This is a classic example of:",
    opts: ["A legitimate security alert","A phishing urgency tactic designed to make you act without thinking","A standard password reminder","An automated system notification"],
    ans: 1,
    why: "Creating artificial urgency is the most common phishing tactic. Legitimate organisations never threaten immediate account suspension via email without prior warning."
  },
  {
    q: "You hover over a link in an email and the status bar shows: http://paypa1.com/login. What does this mean?",
    opts: ["PayPal updated their website","This is a typosquatting attack — '1' replaces the letter 'l'","The link is safe because it mentions PayPal","This is a secure HTTPS link"],
    ans: 1,
    why: "Typosquatting replaces characters with similar-looking ones — the letter l with the number 1, or o with 0. Always inspect every character of a URL before clicking."
  },
  {
    q: "What is the BEST action when you receive a suspicious email at work?",
    opts: ["Delete it so it can't harm you","Click links to test if they're dangerous","Report it to IT Security using the phishing report tool","Reply to the sender to confirm their identity"],
    ans: 2,
    why: "Reporting allows IT Security to investigate, block the sender organisation-wide, and protect colleagues who may receive the same email. It's the most protective action you can take."
  },
  {
    q: "You accidentally clicked a suspicious link on your work computer. What must you do IMMEDIATELY?",
    opts: ["Clear your browser history and continue working","Disconnect from the network and report to IT Security immediately","Run a quick antivirus scan and carry on","Wait to see if anything happens"],
    ans: 1,
    why: "Disconnecting immediately limits the spread of any malware that may have been installed. Every second counts — quick action can prevent a minor incident from becoming a major breach."
  }
];

// ── NAVIGATION ─────────────────────────────────────────
function goTo(screenId) {
  document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
  if (screenId === 'quiz') {
    document.getElementById('screen-quiz').classList.add('active');
    loadQuestion(0);
  } else if (screenId === 'complete') {
    document.getElementById('screen-complete').classList.add('active');
    showFinalScore();
  } else {
    document.getElementById('screen-' + screenId).classList.add('active');
  }
  currentScreen = screenId;
  updateProgress(screenId);
  window.scrollTo(0,0);
}

function updateProgress(s) {
  const steps = [0,1,2,3,4,5,'quiz','complete'];
  const idx = typeof s === 'number' ? s : steps.indexOf(s);
  const pct = Math.round((idx / (steps.length - 1)) * 100);
  document.getElementById('prog-bar').style.width = pct + '%';
}

// ── XP SYSTEM ─────────────────────────────────────────
function addXP(amount) {
  xp += amount;
  document.getElementById('xp-display').innerHTML = '<i class="fas fa-bolt"></i> ' + xp + ' XP';
  const pop = document.createElement('div');
  pop.className = 'xp-pop';
  pop.textContent = '+' + amount + ' XP';
  document.body.appendChild(pop);
  setTimeout(() => pop.remove(), 1500);
}

function loseHeart() {
  lives--;
  const heart = document.getElementById('h' + (lives + 1));
  if (heart) heart.classList.add('lost');
  if (lives <= 0) {
    showFeedback('💔', 'Out of lives!', 'bad', "Don't worry — mistakes help you learn. Keep going!", false, true);
  }
}

// ── SIMULATION ─────────────────────────────────────────
function simAction(action) {
  document.querySelectorAll('.ea-btn').forEach(b => b.disabled = true);
  if (action === 'report') {
    showFeedback('<i class="fas fa-bullseye"></i>', 'Perfect!', 'good', 'Excellent! Reporting is always the best action. You protected yourself AND your colleagues. IT Security can now block this sender for everyone.', true, false, 40);
  } else if (action === 'click') {
    loseHeart();
    showFeedback('<i class="fas fa-exclamation-triangle"></i>', 'That was phishing!', 'bad', "You clicked the link — this was a simulated phishing email. Red flags included: suspicious domain (company-helpdesk.net), urgency tactics, and a generic greeting. Always hover over links first.", false, false, 0);
  } else {
    showFeedback('<i class="fas fa-thumbs-up"></i>', 'Good instinct!', 'good', "Deleting is safe, but REPORTING is even better! Reporting lets IT Security investigate and protect all your colleagues from the same attack.", true, false, 20);
  }
}

// ── FEEDBACK ──────────────────────────────────────────
let feedbackNext = null;
function showFeedback(icon, title, type, text, gotoQuiz = false, restart = false, xpAmount = 0) {
  document.getElementById('fb-icon').innerHTML = icon;
  document.getElementById('fb-title').textContent = title;
  document.getElementById('fb-title').className = 'fb-title ' + (type === 'good' ? 'good' : 'bad');
  document.getElementById('fb-text').textContent = text;
  document.getElementById('fb-xp').textContent = xpAmount > 0 ? '+' + xpAmount + ' XP earned!' : '';
  document.getElementById('fb-xp').style.display = xpAmount > 0 ? 'inline-block' : 'none';
  document.getElementById('feedback-overlay').style.display = 'flex';
  if (xpAmount > 0) addXP(xpAmount);
  feedbackNext = gotoQuiz ? 'quiz' : null;
}

function closeFeedback() {
  document.getElementById('feedback-overlay').style.display = 'none';
  if (feedbackNext) { goTo(feedbackNext); feedbackNext = null; }
  else if (currentScreen === 5) { goTo('quiz'); }
}

// ── QUIZ ──────────────────────────────────────────────
function loadQuestion(idx) {
  currentQuestion = idx;
  selectedOption = null;
  answered = false;
  document.getElementById('q-counter').textContent = (idx + 1) + '/5';
  document.getElementById('btn-check').disabled = true;
  document.getElementById('btn-check').style.display = 'inline-flex';
  document.getElementById('btn-next').style.display = 'none';

  const q = questions[idx];
  const letters = ['A','B','C','D'];
  let html = `<div class="q-card">
    <div class="q-num">Question ${idx+1} of 5</div>
    <div class="q-text">${q.q}</div>
    <div class="options">`;
  q.opts.forEach((opt, i) => {
    html += `<div class="option" id="opt-${i}" onclick="selectOption(${i})">
      <div class="option-letter">${letters[i]}</div>${opt}</div>`;
  });
  html += `</div><div class="why-box" id="why-box">${q.why}</div></div>`;
  document.getElementById('quiz-container').innerHTML = html;
}

function selectOption(idx) {
  if (answered) return;
  selectedOption = idx;
  document.querySelectorAll('.option').forEach(o => o.classList.remove('selected'));
  document.getElementById('opt-' + idx).classList.add('selected');
  document.getElementById('btn-check').disabled = false;
}

function checkAnswer() {
  if (selectedOption === null || answered) return;
  answered = true;
  const q = questions[currentQuestion];
  const correct = selectedOption === q.ans;

  document.querySelectorAll('.option').forEach((o, i) => {
    if (i === q.ans) o.classList.add('correct');
    else if (i === selectedOption && !correct) o.classList.add('wrong');
  });

  document.getElementById('why-box').classList.add('show');

  if (correct) {
    quizScore++;
    addXP(30);
  } else {
    loseHeart();
  }

  document.getElementById('btn-check').style.display = 'none';
  document.getElementById('btn-next').style.display = 'inline-flex';
  document.getElementById('btn-next').textContent = currentQuestion < 4 ? 'Next →' : 'See Results →';
}

function nextQuestion() {
  if (currentQuestion < 4) {
    loadQuestion(currentQuestion + 1);
  } else {
    goTo('complete');
  }
}

function showFinalScore() {
  const pct = Math.round((quizScore / 5) * 100);
  const bonusXP = quizScore * 20;
  addXP(bonusXP);
  document.getElementById('final-score').textContent = pct + '%';
  document.getElementById('final-xp').textContent = xp;
  updateProgress('complete');
  
  // Save module progress to database
  saveModuleProgress(pct, bonusXP);
}

function saveModuleProgress(score, xp) {
  // Get module ID from global variable set by training_module.php
  const moduleId = window.CYBERAWARE_MODULE_ID || 1;
  
  const formData = new FormData();
  formData.append('module_id', moduleId);
  formData.append('score', score);
  formData.append('xp', xp);
  
  fetch('../modules/save_module_progress.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    console.log('Progress saved:', data);
  })
  .catch(error => console.error('Error saving progress:', error));
}
</script>
</body>
</html>
