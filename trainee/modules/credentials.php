<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Fake Login Pages — CyberAware</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--orange:#FF8C42;--ol:#FFF4EC;--dark:#1A1A2E;--grey:#F5F5F5;--grey2:#E8E8E8;--green:#10B981;--red:#EF4444;--purple:#8B5CF6;}
body{font-family:'Manrope',sans-serif;background:var(--grey);min-height:100vh;}
.topbar{background:var(--dark);padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:200;}
.tb-left{display:flex;align-items:center;gap:16px;}
.tb-back{color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;font-weight:600;display:flex;align-items:center;gap:6px;}
.tb-back:hover{color:#fff;}
.tb-title{color:#fff;font-weight:700;font-size:15px;}
.tb-right{display:flex;align-items:center;gap:12px;}
.tb-lives{display:flex;gap:3px;}
.heart{color:#ff4757;font-size:18px;transition:all .3s;}
.heart.lost{color:rgba(255,255,255,.2);transform:scale(.85);}
.tb-xp{background:rgba(139,92,246,.3);color:#c4b5fd;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:700;}
.prog-wrap{background:rgba(255,255,255,.1);height:6px;}
.prog-bar{height:100%;background:linear-gradient(90deg,var(--purple),#a78bfa);transition:width .6s cubic-bezier(.4,0,.2,1);}
.screen{display:none;animation:fadeIn .4s ease;}
.screen.active{display:block;}
@keyframes fadeIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.lesson-wrap{max-width:720px;margin:40px auto;padding:0 20px 80px;}
.lesson-card{background:#fff;border-radius:20px;padding:36px;box-shadow:0 4px 24px rgba(0,0,0,.07);margin-bottom:20px;}
.lesson-badge{display:inline-flex;align-items:center;gap:8px;background:#ede9fe;color:#5b21b6;padding:7px 16px;border-radius:20px;font-size:13px;font-weight:700;margin-bottom:20px;}
.lesson-card h2{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;color:var(--dark);margin-bottom:14px;line-height:1.3;}
.lesson-card p{font-size:16px;color:#444;line-height:1.8;margin-bottom:16px;}
.highlight-box{background:#ede9fe;border-left:4px solid var(--purple);border-radius:0 12px 12px 0;padding:16px 20px;margin:20px 0;font-size:15px;color:#4c1d95;line-height:1.7;}
.red-flag-list{list-style:none;display:flex;flex-direction:column;gap:10px;margin:16px 0;}
.red-flag-list li{display:flex;align-items:flex-start;gap:12px;background:#fff5f5;border-radius:10px;padding:14px 16px;font-size:15px;color:#333;line-height:1.5;}
.rf-icon{color:var(--red);font-size:18px;flex-shrink:0;margin-top:2px;}
.tip-list{list-style:none;display:flex;flex-direction:column;gap:10px;margin:16px 0;}
.tip-list li{display:flex;align-items:flex-start;gap:12px;background:#f0fdf4;border-radius:10px;padding:14px 16px;font-size:15px;color:#333;line-height:1.5;}
.ti-icon{color:var(--green);font-size:18px;flex-shrink:0;margin-top:2px;}
/* FAKE LOGIN SIM */
.sim-wrap{margin:20px 0;}
.sim-label{text-align:center;font-size:14px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px;}
.browser-bar{background:#f3f4f6;border-radius:12px 12px 0 0;padding:10px 16px;display:flex;align-items:center;gap:10px;border:1px solid var(--grey2);border-bottom:none;}
.bb-dots{display:flex;gap:6px;}
.bb-dot{width:12px;height:12px;border-radius:50%;}
.bb-url{flex:1;background:#fff;border:1px solid var(--grey2);border-radius:6px;padding:6px 12px;font-size:13px;color:#333;font-family:monospace;}
.bb-url.suspicious{color:var(--red);background:#fff5f5;border-color:var(--red);}
.bb-lock{font-size:14px;margin-right:4px;}
.fake-login{border:1px solid var(--grey2);border-radius:0 0 12px 12px;background:#fff;padding:32px;text-align:center;}
.fl-logo{font-size:32px;font-weight:800;margin-bottom:6px;}
.fl-sub{font-size:14px;color:#888;margin-bottom:24px;}
.fl-input{width:100%;padding:12px 16px;border:2px solid var(--grey2);border-radius:8px;font-size:15px;margin-bottom:12px;font-family:'Manrope',sans-serif;text-align:left;}
.fl-btn{width:100%;padding:13px;background:#1877f2;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;margin-bottom:12px;}
.fl-hint{font-size:12px;color:#aaa;}
.sim-actions{display:flex;gap:12px;margin-top:16px;flex-wrap:wrap;}
.sa-btn{flex:1;padding:13px 20px;border-radius:10px;font-size:14px;font-weight:700;border:2px solid transparent;cursor:pointer;font-family:'Manrope',sans-serif;display:flex;align-items:center;justify-content:center;gap:8px;transition:.2s;min-width:140px;}
.sa-enter{background:var(--red);color:#fff;}
.sa-enter:hover{background:#dc2626;}
.sa-back{background:#fff;color:var(--green);border-color:var(--green);}
.sa-back:hover{background:var(--green);color:#fff;}
.sa-check{background:#fff;color:var(--purple);border-color:var(--purple);}
.sa-check:hover{background:var(--purple);color:#fff;}
/* CONTINUE */
.continue-bar{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid var(--grey2);padding:16px 24px;display:flex;justify-content:center;gap:16px;z-index:100;}
.btn-continue{background:var(--purple);color:#fff;border:none;padding:15px 48px;border-radius:12px;font-size:17px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;transition:.2s;display:flex;align-items:center;gap:10px;}
.btn-continue:hover{background:#7c3aed;transform:translateY(-2px);}
.btn-check{background:var(--dark);color:#fff;border:none;padding:15px 48px;border-radius:12px;font-size:17px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;transition:.2s;}
.btn-check:hover{background:#2d2d4e;}
.btn-check:disabled,.btn-continue:disabled{background:var(--grey2);color:#aaa;cursor:not-allowed;transform:none;}
/* QUIZ */
.quiz-wrap{max-width:720px;margin:40px auto;padding:0 20px 80px;}
.quiz-header{background:var(--dark);border-radius:16px;padding:24px 28px;color:#fff;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;}
.qh-left h2{font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;margin-bottom:4px;}
.qh-left p{color:rgba(255,255,255,.6);font-size:14px;}
.qh-right{background:rgba(255,255,255,.1);border-radius:12px;padding:12px 20px;text-align:center;}
.qh-num{font-size:28px;font-weight:800;color:#c4b5fd;}
.qh-lbl{font-size:11px;color:rgba(255,255,255,.6);font-weight:600;}
.q-card{background:#fff;border-radius:20px;padding:32px;box-shadow:0 4px 20px rgba(0,0,0,.07);margin-bottom:20px;}
.q-num{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--purple);margin-bottom:12px;}
.q-text{font-size:19px;font-weight:700;color:var(--dark);margin-bottom:24px;line-height:1.5;}
.options{display:flex;flex-direction:column;gap:12px;}
.option{display:flex;align-items:center;gap:14px;padding:16px 20px;border:2px solid var(--grey2);border-radius:12px;cursor:pointer;transition:all .2s;font-size:15px;font-weight:600;color:var(--dark);}
.option:hover{border-color:var(--purple);background:#ede9fe;}
.option.selected{border-color:var(--purple);background:#ede9fe;}
.option.correct{border-color:var(--green);background:#f0fdf4;color:#065f46;}
.option.wrong{border-color:var(--red);background:#fff5f5;color:#7f1d1d;}
.option-letter{width:32px;height:32px;border-radius:8px;background:var(--grey2);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;transition:.2s;}
.option.selected .option-letter{background:var(--purple);color:#fff;}
.option.correct .option-letter{background:var(--green);color:#fff;}
.option.wrong .option-letter{background:var(--red);color:#fff;}
.why-box{margin-top:16px;padding:16px 18px;background:#ede9fe;border-left:4px solid var(--purple);border-radius:0 10px 10px 0;font-size:14px;color:#4c1d95;line-height:1.6;display:none;}
.why-box.show{display:block;animation:fadeIn .3s ease;}
/* FEEDBACK */
.feedback-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:300;display:flex;align-items:center;justify-content:center;}
.feedback-box{background:#fff;border-radius:24px;padding:40px;max-width:440px;width:90%;text-align:center;animation:popIn .4s cubic-bezier(.175,.885,.32,1.275);}
@keyframes popIn{from{transform:scale(.7);opacity:0}to{transform:scale(1);opacity:1}}
.fb-icon{font-size:64px;margin-bottom:16px;}
.fb-title{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;margin-bottom:10px;}
.fb-title.good{color:var(--green);}
.fb-title.bad{color:var(--red);}
.fb-text{font-size:15px;color:#555;line-height:1.7;margin-bottom:24px;}
.fb-btn{background:var(--purple);color:#fff;border:none;padding:14px 32px;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;}
.fb-xp{background:#ede9fe;color:#5b21b6;padding:8px 20px;border-radius:20px;font-size:14px;font-weight:700;display:inline-block;margin-bottom:16px;}
.xp-pop{position:fixed;top:80px;right:24px;background:var(--purple);color:#fff;padding:10px 20px;border-radius:20px;font-weight:800;font-size:16px;z-index:400;animation:xpFloat 1.5s ease forwards;}
@keyframes xpFloat{0%{opacity:0;transform:translateY(10px)}20%{opacity:1;transform:translateY(0)}80%{opacity:1;transform:translateY(-20px)}100%{opacity:0;transform:translateY(-40px)}}
/* INTRO */
.intro-wrap{max-width:600px;margin:60px auto;padding:0 20px;}
.intro-card{background:#fff;border-radius:24px;padding:48px 40px;box-shadow:0 8px 40px rgba(0,0,0,.1);text-align:center;}
.intro-icon{width:90px;height:90px;background:linear-gradient(135deg,var(--purple),#a78bfa);border-radius:22px;display:flex;align-items:center;justify-content:center;font-size:40px;color:#fff;margin:0 auto 24px;}
.intro-card h1{font-family:'Space Grotesk',sans-serif;font-size:30px;font-weight:700;color:var(--dark);margin-bottom:12px;}
.intro-card p{font-size:16px;color:#555;line-height:1.7;margin-bottom:28px;}
.intro-pills{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-bottom:32px;}
.intro-pill{background:#ede9fe;color:#5b21b6;padding:8px 18px;border-radius:20px;font-size:13px;font-weight:700;}
.btn-start{background:var(--purple);color:#fff;border:none;padding:16px 56px;border-radius:12px;font-size:18px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;transition:.2s;}
.btn-start:hover{background:#7c3aed;transform:translateY(-2px);}
/* COMPLETE */
.complete-wrap{max-width:560px;margin:60px auto;padding:0 20px;text-align:center;}
.complete-card{background:#fff;border-radius:24px;padding:48px 40px;box-shadow:0 8px 40px rgba(0,0,0,.1);}
.trophy{font-size:80px;margin-bottom:20px;animation:bounce .6s ease infinite alternate;}
@keyframes bounce{from{transform:translateY(0)}to{transform:translateY(-12px)}}
.complete-card h1{font-family:'Space Grotesk',sans-serif;font-size:32px;font-weight:700;color:var(--dark);margin-bottom:10px;}
.complete-card p{font-size:16px;color:#666;margin-bottom:28px;}
.complete-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:28px 0;}
.cs{background:var(--grey);border-radius:14px;padding:18px;}
.cs-num{font-size:28px;font-weight:800;color:var(--purple);}
.cs-lbl{font-size:12px;color:#888;font-weight:700;text-transform:uppercase;margin-top:4px;}
.stars-wrap{display:flex;justify-content:center;gap:8px;margin:20px 0;}
.star{font-size:40px;opacity:0;animation:starPop .4s ease forwards;}
.star:nth-child(1){animation-delay:.2s;}
.star:nth-child(2){animation-delay:.4s;}
.star:nth-child(3){animation-delay:.6s;}
@keyframes starPop{from{opacity:0;transform:scale(0) rotate(-30deg)}to{opacity:1;transform:scale(1) rotate(0)}}
.btn-done{background:var(--purple);color:#fff;border:none;padding:16px 48px;border-radius:12px;font-size:17px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;transition:.2s;text-decoration:none;display:inline-block;}
.btn-done:hover{background:#7c3aed;}
</style>
</head>
<body>
<div class="topbar">
  <div class="tb-left">
    <a href="../dashboard.php" class="tb-back"><i class="fas fa-times"></i></a>
    <span class="tb-title">Fake Login Pages</span>
  </div>
  <div class="tb-right">
    <div class="tb-lives">
      <i class="fas fa-heart" id="h1" style="color:#ff4757;"></i><i class="fas fa-heart" id="h2" style="color:#ff4757;"></i><i class="fas fa-heart" id="h3" style="color:#ff4757;"></i><i class="fas fa-heart" id="h4" style="color:#ff4757;"></i><i class="fas fa-heart" id="h5" style="color:#ff4757;"></i>
    </div>
    <div class="tb-xp" id="xp-display"><i class="fas fa-bolt"></i> 0 XP</div>
  </div>
</div>
<div class="prog-wrap"><div class="prog-bar" id="prog-bar" style="width:0%"></div></div>

<!-- INTRO -->
<div class="screen active" id="screen-0">
  <div class="intro-wrap">
    <div class="intro-card">
      <div class="intro-icon"><i class="fas fa-key"></i></div>
      <h1>Fake Login Pages</h1>
      <p>Credential harvesting is when attackers build websites that look exactly like real login pages to steal your username and password. Learn to spot the difference before you type anything.</p>
      <div class="intro-pills">
        <span class="intro-pill"><i class="fas fa-key"></i> 4 Real lessons</span>
        <span class="intro-pill"><i class="fas fa-desktop"></i> Live simulation</span>
        <span class="intro-pill"><i class="fas fa-question"></i> 5 Quiz questions</span>
        <span class="intro-pill"><i class="fas fa-bolt"></i> 240 XP</span>
      </div>
      <button class="btn-start" onclick="goTo(1)"><i class="fas fa-play"></i> Start Module</button>
    </div>
  </div>
</div>

<!-- LESSON 1 -->
<div class="screen" id="screen-1">
  <div class="lesson-wrap">
    <div class="lesson-card">
      <div class="lesson-badge"><i class="fas fa-book-open"></i> Lesson 1 of 4</div>
      <h2>What is credential harvesting?</h2>
      <p>Credential harvesting is when a cybercriminal creates a website that looks identical to a real login page — your company portal, your bank, Microsoft 365, or Gmail — to trick you into entering your username and password.</p>
      <div class="highlight-box"><strong><i class="fas fa-bullseye"></i> Real impact:</strong> Once an attacker has your credentials, they can access your email, reset passwords on other accounts, transfer money, steal sensitive data, and impersonate you. A single stolen password can compromise an entire organisation.</div>
      <p>These fake pages are often pixel-perfect copies. They have the right logo, the right colours, the right layout. The only giveaway is the URL — and attackers work hard to make that look convincing too.</p>
      <h3 style="font-size:18px;font-weight:700;color:var(--dark);margin:20px 0 12px;">How it works</h3>
      <ul class="red-flag-list">
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>Step 1:</strong> You receive a phishing email with a link — "Your password expired, click here to reset"</div></li>
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>Step 2:</strong> The link takes you to a fake page that looks exactly like your real login portal</div></li>
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>Step 3:</strong> You enter your username and password — which go straight to the attacker</div></li>
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>Step 4:</strong> You are redirected to the real site so you don't notice anything wrong</div></li>
      </ul>
    </div>
  </div>
  <div class="continue-bar"><button class="btn-continue" onclick="goTo(2)"><i class="fas fa-arrow-right"></i> Continue</button></div>
</div>

<!-- LESSON 2 -->
<div class="screen" id="screen-2">
  <div class="lesson-wrap">
    <div class="lesson-card">
      <div class="lesson-badge"><i class="fas fa-book-open"></i> Lesson 2 of 4</div>
      <h2>The URL is your best defence</h2>
      <p>Before typing anything into a login page, always check the URL bar. The URL tells you exactly which server your data is being sent to.</p>
      <div style="background:var(--dark);border-radius:14px;padding:24px;margin:20px 0;">
        <div style="margin-bottom:16px;font-size:13px;color:rgba(255,255,255,.5);font-weight:700;text-transform:uppercase;">Real vs Fake — Microsoft 365</div>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <div style="background:rgba(16,185,129,.15);border:1px solid #10B981;border-radius:10px;padding:14px;font-family:monospace;color:#fff;">
            <span style="color:#10B981;font-weight:700;"><i class="fas fa-check"></i> REAL: </span>https://login.microsoftonline.com
          </div>
          <div style="background:rgba(239,68,68,.15);border:1px solid #EF4444;border-radius:10px;padding:14px;font-family:monospace;color:#fff;">
            <span style="color:#EF4444;font-weight:700;">✗ FAKE: </span>https://microsoft-login.com
          </div>
          <div style="background:rgba(239,68,68,.15);border:1px solid #EF4444;border-radius:10px;padding:14px;font-family:monospace;color:#fff;">
            <span style="color:#EF4444;font-weight:700;">✗ FAKE: </span>https://login.microsoftonline.com.phish.net
          </div>
          <div style="background:rgba(239,68,68,.15);border:1px solid #EF4444;border-radius:10px;padding:14px;font-family:monospace;color:#fff;">
            <span style="color:#EF4444;font-weight:700;">✗ FAKE: </span>https://microsoftonIine.com (capital i as l)
          </div>
        </div>
      </div>
      <div class="highlight-box"><strong>💡 The HTTPS myth:</strong> HTTPS (the padlock icon) means the connection is encrypted — it does NOT mean the site is legitimate. Attackers use HTTPS on fake sites too. The padlock tells you the connection is secure, not that the site is real.</div>
    </div>
  </div>
  <div class="continue-bar"><button class="btn-continue" onclick="goTo(3)"><i class="fas fa-arrow-right"></i> Continue</button></div>
</div>

<!-- LESSON 3 -->
<div class="screen" id="screen-3">
  <div class="lesson-wrap">
    <div class="lesson-card">
      <div class="lesson-badge"><i class="fas fa-book-open"></i> Lesson 3 of 4</div>
      <h2>Signs of a fake login page</h2>
      <ul class="red-flag-list">
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>Wrong URL domain</strong> — not the official domain. Check character by character.</div></li>
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>You arrived via a link</strong> — you clicked a link in an email rather than navigating directly.</div></li>
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>Browser warnings</strong> — any security warning means stop immediately.</div></li>
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>Slightly off design</strong> — wrong font, wrong colours, pixelated logo, or missing elements.</div></li>
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>No remember-me option</strong> — real portals often have account features fake pages skip.</div></li>
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>Asks for extra info</strong> — real login pages only need username and password. Any extras are a red flag.</div></li>
      </ul>
      <h3 style="font-size:18px;font-weight:700;color:var(--dark);margin:20px 0 12px;">Your safe habits</h3>
      <ul class="tip-list">
        <li><i class="fas fa-check-circle ti-icon"></i><div><strong>Bookmark important login pages</strong> — always navigate from your bookmark, never from a link</div></li>
        <li><i class="fas fa-check-circle ti-icon"></i><div><strong>Type the URL manually</strong> — if unsure, type the official URL directly in the browser</div></li>
        <li><i class="fas fa-check-circle ti-icon"></i><div><strong>Use a password manager</strong> — it will NOT autofill on fake sites because the domain won't match</div></li>
        <li><i class="fas fa-check-circle ti-icon"></i><div><strong>Enable MFA</strong> — even if your password is stolen, MFA blocks the attacker</div></li>
      </ul>
    </div>
  </div>
  <div class="continue-bar"><button class="btn-continue" onclick="goTo(4)"><i class="fas fa-arrow-right"></i> Continue</button></div>
</div>

<!-- LESSON 4 -->
<div class="screen" id="screen-4">
  <div class="lesson-wrap">
    <div class="lesson-card">
      <div class="lesson-badge"><i class="fas fa-book-open"></i> Lesson 4 of 4</div>
      <h2>What to do if you entered your credentials</h2>
      <p>If you realise you just entered your password on a fake site, every second counts. Act immediately:</p>
      <ul class="tip-list">
        <li><i class="fas fa-bolt ti-icon"></i><div><strong>Change your password immediately</strong> on the real website — do it right now, before the attacker does</div></li>
        <li><i class="fas fa-bolt ti-icon"></i><div><strong>Change it everywhere</strong> — if you reused that password on other sites, change it on all of them</div></li>
        <li><i class="fas fa-bolt ti-icon"></i><div><strong>Report to IT Security</strong> — they can monitor for unusual activity and protect the organisation</div></li>
        <li><i class="fas fa-bolt ti-icon"></i><div><strong>Enable MFA</strong> — immediately add multi-factor authentication if not already active</div></li>
        <li><i class="fas fa-bolt ti-icon"></i><div><strong>Watch for suspicious activity</strong> — check your email sent folder, account logins, and financial accounts</div></li>
        <li><i class="fas fa-bolt ti-icon"></i><div><strong>Don't be embarrassed</strong> — it happens to experts too. Reporting quickly limits the damage dramatically</div></li>
      </ul>
      <div class="highlight-box"><strong><i class="fas fa-hourglass-end"></i> Time is critical:</strong> Attackers use stolen credentials within minutes. The faster you act, the better your chances of preventing damage.</div>
    </div>
  </div>
  <div class="continue-bar"><button class="btn-continue" onclick="goTo(5)"><i class="fas fa-arrow-right"></i> Try the Simulation</button></div>
</div>

<!-- SIMULATION -->
<div class="screen" id="screen-5">
  <div class="lesson-wrap">
    <div class="lesson-card" style="text-align:center;padding:24px;">
      <div class="lesson-badge"><i class="fas fa-flask"></i> Live Simulation</div>
      <h2>Real or fake? Spot it before you type.</h2>
      <p style="color:#666;font-size:15px;">You followed a link from an email. Is this the real Facebook login? What do you do?</p>
    </div>
    <div class="sim-wrap">
      <div class="sim-label">Your browser is showing:</div>
      <div class="browser-bar">
        <div class="bb-dots">
          <div class="bb-dot" style="background:#ff5f57;"></div>
          <div class="bb-dot" style="background:#febc2e;"></div>
          <div class="bb-dot" style="background:#28c840;"></div>
        </div>
        <div class="bb-url suspicious"><i class="fas fa-lock"></i> https://facebook-login-secure.com/login</div>
      </div>
      <div class="fake-login">
        <div class="fl-logo" style="color:#1877f2;">facebook</div>
        <div class="fl-sub">Log in to your account</div>
        <input class="fl-input" type="email" placeholder="Email or phone number">
        <input class="fl-input" type="password" placeholder="Password">
        <button class="fl-btn">Log In</button>
        <div class="fl-hint">Forgotten password? · Create new account</div>
      </div>
    </div>
    <div class="sim-actions">
      <button class="sa-btn sa-enter" onclick="simAction('enter')"><i class="fas fa-sign-in-alt"></i> Enter my credentials</button>
      <button class="sa-btn sa-back" onclick="simAction('back')"><i class="fas fa-arrow-left"></i> Go back — URL looks wrong</button>
      <button class="sa-btn sa-check" onclick="simAction('check')"><i class="fas fa-search"></i> Check the URL first</button>
    </div>
  </div>
</div>

<!-- QUIZ -->
<div class="screen" id="screen-quiz">
  <div class="quiz-wrap">
    <div class="quiz-header">
      <div class="qh-left"><h2>Knowledge Check</h2><p>5 questions to complete the module</p></div>
      <div class="qh-right"><div class="qh-num" id="q-counter">1/5</div><div class="qh-lbl">Question</div></div>
    </div>
    <div id="quiz-container"></div>
  </div>
  <div class="continue-bar">
    <button class="btn-check" id="btn-check" onclick="checkAnswer()" disabled>Check Answer</button>
    <button class="btn-continue" id="btn-next" onclick="nextQuestion()" style="display:none">Next <i class="fas fa-arrow-right"></i></button>
  </div>
</div>

<!-- COMPLETE -->
<div class="screen" id="screen-complete">
  <div class="complete-wrap">
    <div class="complete-card">
      <div class="trophy"><i class="fas fa-lock"></i></div>
      <div class="stars-wrap"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
      <h1>Module Complete!</h1>
      <p>You can now spot fake login pages like a pro!</p>
      <div class="complete-stats">
        <div class="cs"><div class="cs-num" id="final-score">0%</div><div class="cs-lbl">Score</div></div>
        <div class="cs"><div class="cs-num" id="final-xp">0</div><div class="cs-lbl">XP Earned</div></div>
        <div class="cs"><div class="cs-num">4</div><div class="cs-lbl">Lessons</div></div>
      </div>
      <a href="../dashboard.php" class="btn-done"><i class="fas fa-home"></i> Back to Dashboard</a>
    </div>
  </div>
</div>

<!-- FEEDBACK -->
<div class="feedback-overlay" id="feedback-overlay" style="display:none">
  <div class="feedback-box">
    <div class="fb-icon" id="fb-icon"></div>
    <div class="fb-xp" id="fb-xp" style="display:none"></div>
    <div class="fb-title" id="fb-title"></div>
    <div class="fb-text" id="fb-text"></div>
    <button class="fb-btn" onclick="closeFeedback()">Continue</button>
  </div>
</div>

<script>
let xp=0,lives=5,quizScore=0,currentQuestion=0,selectedOption=null,answered=false,feedbackNext=null;
const questions=[
  {q:"You click a link from an email and land on a login page. The URL shows 'https://microsoft-login-portal.com'. What should you do?",opts:["Log in — it has HTTPS so it's secure","Do not log in — the domain is not microsoft.com","Log in quickly then change your password","Check if the page looks like Microsoft"],ans:1,why:"HTTPS only means the connection is encrypted — not that the site is legitimate. The real Microsoft login is login.microsoftonline.com. Any other domain is fake."},
  {q:"Which browser feature is the MOST reliable way to identify a fake login page?",opts:["The padlock/HTTPS icon","The full URL in the address bar","The page design and logo","The page loading speed"],ans:1,why:"The URL is the only reliable indicator. Attackers can copy designs perfectly and even use HTTPS. Always check the full domain name character by character."},
  {q:"You accidentally entered your work password on a fake site. What is the FIRST thing to do?",opts:["Wait and see if anything suspicious happens","Clear your browser history","Change your password on the real site IMMEDIATELY","Run an antivirus scan"],ans:2,why:"Every second counts. Attackers use stolen credentials within minutes. Change your password on the real site immediately, then report to IT Security and change it everywhere you reused it."},
  {q:"Why won't a password manager autofill your password on a fake login page?",opts:["Password managers don't work on fake sites","Password managers check the domain and won't match a fake URL","Fake sites block password managers","Password managers only work on HTTPS sites"],ans:1,why:"Password managers save credentials linked to specific domains. If the domain doesn't match exactly, they won't autofill — making them an excellent defence against credential harvesting sites."},
  {q:"An email says your company portal password expired and provides a login link. What is the safe approach?",opts:["Click the link since it's from IT","Close the email, open your browser, and type the official portal URL manually","Reply to IT asking if it's real","Forward the email to colleagues to warn them"],ans:1,why:"Never use links from emails to navigate to login pages. Always go directly to the official URL by typing it in your browser or using a trusted bookmark. This eliminates the risk entirely."}
];
function goTo(s){document.querySelectorAll('.screen').forEach(x=>x.classList.remove('active'));if(s==='quiz'){document.getElementById('screen-quiz').classList.add('active');loadQuestion(0);}else if(s==='complete'){document.getElementById('screen-complete').classList.add('active');showFinalScore();}else{document.getElementById('screen-'+s).classList.add('active');}updateProgress(s);window.scrollTo(0,0);}
function updateProgress(s){const steps=[0,1,2,3,4,5,'quiz','complete'];const idx=typeof s==='number'?s:steps.indexOf(s);document.getElementById('prog-bar').style.width=Math.round((idx/(steps.length-1))*100)+'%';}
function addXP(n){xp+=n;document.getElementById('xp-display').innerHTML='<i class="fas fa-bolt"></i> '+xp+' XP';const p=document.createElement('div');p.className='xp-pop';p.textContent='+'+n+' XP';document.body.appendChild(p);setTimeout(()=>p.remove(),1500);}
function loseHeart(){lives--;const h=document.getElementById('h'+(lives+1));if(h)h.classList.add('lost');}
function simAction(a){document.querySelectorAll('.sa-btn').forEach(b=>b.disabled=true);if(a==='back'||a==='check'){showFeedback('<i class="fas fa-bullseye"></i>','Great instinct!','good','You spotted the fake! The URL "facebook-login-secure.com" is NOT facebook.com. Always check the full domain before entering any credentials.',true,40);}else{loseHeart();showFeedback('<i class="fas fa-exclamation-triangle"></i>','That was a fake site!','bad','You just gave your credentials to an attacker. The URL "facebook-login-secure.com" is not Facebook. The real Facebook login is facebook.com — check the URL every time.',false,0);}}
function showFeedback(icon,title,type,text,gotoQuiz=false,xpAmt=0){document.getElementById('fb-icon').innerHTML=icon;document.getElementById('fb-title').textContent=title;document.getElementById('fb-title').className='fb-title '+(type==='good'?'good':'bad');document.getElementById('fb-text').textContent=text;const xpEl=document.getElementById('fb-xp');if(xpAmt>0){xpEl.textContent='+'+xpAmt+' XP earned!';xpEl.style.display='inline-block';addXP(xpAmt);}else xpEl.style.display='none';document.getElementById('feedback-overlay').style.display='flex';feedbackNext=gotoQuiz?'quiz':null;}
function closeFeedback(){document.getElementById('feedback-overlay').style.display='none';if(feedbackNext){goTo(feedbackNext);feedbackNext=null;}else goTo('quiz');}
function loadQuestion(idx){currentQuestion=idx;selectedOption=null;answered=false;document.getElementById('q-counter').textContent=(idx+1)+'/5';document.getElementById('btn-check').disabled=true;document.getElementById('btn-check').style.display='inline-flex';document.getElementById('btn-next').style.display='none';const q=questions[idx];const L=['A','B','C','D'];let h=`<div class="q-card"><div class="q-num">Question ${idx+1} of 5</div><div class="q-text">${q.q}</div><div class="options">`;q.opts.forEach((o,i)=>{h+=`<div class="option" id="opt-${i}" onclick="selectOption(${i})"><div class="option-letter">${L[i]}</div>${o}</div>`;});h+=`</div><div class="why-box" id="why-box">${q.why}</div></div>`;document.getElementById('quiz-container').innerHTML=h;}
function selectOption(idx){if(answered)return;selectedOption=idx;document.querySelectorAll('.option').forEach(o=>o.classList.remove('selected'));document.getElementById('opt-'+idx).classList.add('selected');document.getElementById('btn-check').disabled=false;}
function checkAnswer(){if(selectedOption===null||answered)return;answered=true;const q=questions[currentQuestion];const correct=selectedOption===q.ans;document.querySelectorAll('.option').forEach((o,i)=>{if(i===q.ans)o.classList.add('correct');else if(i===selectedOption&&!correct)o.classList.add('wrong');});document.getElementById('why-box').classList.add('show');if(correct){quizScore++;addXP(30);}else loseHeart();document.getElementById('btn-check').style.display='none';document.getElementById('btn-next').style.display='inline-flex';document.getElementById('btn-next').textContent=currentQuestion<4?'Next →':'See Results →';}
function nextQuestion(){if(currentQuestion<4)loadQuestion(currentQuestion+1);else goTo('complete');}
function showFinalScore(){const pct=Math.round((quizScore/5)*100);addXP(quizScore*20);document.getElementById('final-score').textContent=pct+'%';document.getElementById('final-xp').textContent=xp;saveModuleProgress(pct,quizScore*20);}
function saveModuleProgress(score,xp){const moduleId=window.CYBERAWARE_MODULE_ID||urlParams.get('module_id')||urlParams.get('module')||2;const formData=new FormData();formData.append('module_id',moduleId);formData.append('score',score);formData.append('xp',xp);fetch('../modules/save_module_progress.php',{method:'POST',body:formData}).then(response=>response.json()).then(data=>{console.log('Progress saved:',data);}).catch(error=>console.error('Error saving progress:',error));}
</script>
</body>
</html>
