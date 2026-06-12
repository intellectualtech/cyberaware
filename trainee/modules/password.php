<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Password Security — CyberAware</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--c:#6366F1;--cl:#EEF2FF;--dark:#1A1A2E;--grey:#F5F5F5;--grey2:#E8E8E8;--green:#10B981;--red:#EF4444;}
body{font-family:'Manrope',sans-serif;background:var(--grey);min-height:100vh;}
.topbar{background:var(--dark);padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:200;}
.tb-back{color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;font-weight:600;display:flex;align-items:center;gap:6px;}.tb-back:hover{color:#fff;}
.tb-title{color:#fff;font-weight:700;font-size:15px;}
.heart{color:#ff4757;font-size:18px;transition:all .3s;}.heart.lost{color:rgba(255,255,255,.2);}
.tb-xp{background:rgba(255,255,255,.1);color:rgba(255,255,255,.9);padding:5px 14px;border-radius:20px;font-size:13px;font-weight:700;}
.prog-wrap{background:rgba(255,255,255,.1);height:6px;}
.prog-bar{height:100%;background:var(--c);transition:width .6s ease;}
.screen{display:none;animation:fadeIn .4s ease;}.screen.active{display:block;}
@keyframes fadeIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.wrap{max-width:720px;margin:40px auto;padding:0 20px 80px;}
.card{background:#fff;border-radius:20px;padding:36px;box-shadow:0 4px 24px rgba(0,0,0,.07);margin-bottom:20px;}
.badge{display:inline-flex;align-items:center;gap:8px;background:var(--cl);color:#3730a3;padding:7px 16px;border-radius:20px;font-size:13px;font-weight:700;margin-bottom:20px;}
h2{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;color:var(--dark);margin-bottom:14px;line-height:1.3;}
.card p{font-size:16px;color:#444;line-height:1.8;margin-bottom:16px;}
.hbox{background:var(--cl);border-left:4px solid var(--c);border-radius:0 12px 12px 0;padding:16px 20px;margin:20px 0;font-size:15px;color:#3730a3;line-height:1.7;}
.rfl{list-style:none;display:flex;flex-direction:column;gap:10px;margin:16px 0;}
.rfl li{display:flex;align-items:flex-start;gap:12px;background:#fff5f5;border-radius:10px;padding:14px 16px;font-size:15px;color:#333;line-height:1.5;}
.tl{list-style:none;display:flex;flex-direction:column;gap:10px;margin:16px 0;}
.tl li{display:flex;align-items:flex-start;gap:12px;background:#f0fdf4;border-radius:10px;padding:14px 16px;font-size:15px;color:#333;line-height:1.5;}
.ri{color:var(--red);font-size:18px;flex-shrink:0;margin-top:2px;}
.ti{color:var(--green);font-size:18px;flex-shrink:0;margin-top:2px;}
.continue-bar{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid var(--grey2);padding:16px 24px;display:flex;justify-content:center;gap:16px;z-index:100;}
.btn-c{background:var(--c);color:#fff;border:none;padding:15px 48px;border-radius:12px;font-size:17px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;transition:.2s;display:flex;align-items:center;gap:10px;}
.btn-c:hover{filter:brightness(.9);transform:translateY(-2px);}
.btn-c:disabled{background:var(--grey2);color:#aaa;cursor:not-allowed;transform:none;}
.btn-ck{background:var(--dark);color:#fff;border:none;padding:15px 48px;border-radius:12px;font-size:17px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;}
.btn-ck:disabled{background:var(--grey2);color:#aaa;cursor:not-allowed;}
.qwrap{max-width:720px;margin:40px auto;padding:0 20px 80px;}
.qhdr{background:var(--dark);border-radius:16px;padding:24px 28px;color:#fff;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;}
.qhdr-stat{background:rgba(255,255,255,.1);border-radius:12px;padding:12px 20px;text-align:center;}
.qhdr-num{font-size:28px;font-weight:800;color:var(--c);}.qhdr-lbl{font-size:11px;color:rgba(255,255,255,.6);font-weight:600;}
.qcard{background:#fff;border-radius:20px;padding:32px;box-shadow:0 4px 20px rgba(0,0,0,.07);margin-bottom:20px;}
.qnum{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--c);margin-bottom:12px;}
.qtext{font-size:19px;font-weight:700;color:var(--dark);margin-bottom:24px;line-height:1.5;}
.opts{display:flex;flex-direction:column;gap:12px;}
.opt{display:flex;align-items:center;gap:14px;padding:16px 20px;border:2px solid var(--grey2);border-radius:12px;cursor:pointer;transition:all .2s;font-size:15px;font-weight:600;color:var(--dark);}
.opt:hover{border-color:var(--c);background:var(--cl);}
.opt.selected{border-color:var(--c);background:var(--cl);}
.opt.correct{border-color:var(--green);background:#f0fdf4;color:#065f46;}
.opt.wrong{border-color:var(--red);background:#fff5f5;color:#7f1d1d;}
.opt-l{width:32px;height:32px;border-radius:8px;background:var(--grey2);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;}
.opt.selected .opt-l{background:var(--c);color:#fff;}
.opt.correct .opt-l{background:var(--green);color:#fff;}
.opt.wrong .opt-l{background:var(--red);color:#fff;}
.why{margin-top:16px;padding:16px 18px;background:var(--cl);border-left:4px solid var(--c);border-radius:0 10px 10px 0;font-size:14px;color:#3730a3;line-height:1.6;display:none;}
.why.show{display:block;animation:fadeIn .3s ease;}
.fov{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:300;display:flex;align-items:center;justify-content:center;}
.fbox{background:#fff;border-radius:24px;padding:40px;max-width:440px;width:90%;text-align:center;animation:popIn .4s cubic-bezier(.175,.885,.32,1.275);}
@keyframes popIn{from{transform:scale(.7);opacity:0}to{transform:scale(1);opacity:1}}
.ficon{font-size:64px;margin-bottom:16px;}.ftitle{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;margin-bottom:10px;}
.ftitle.good{color:var(--green);}.ftitle.bad{color:var(--red);}
.ftext{font-size:15px;color:#555;line-height:1.7;margin-bottom:24px;}
.fbtn{background:var(--c);color:#fff;border:none;padding:14px 32px;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;}
.fxp{background:var(--cl);color:#3730a3;padding:8px 20px;border-radius:20px;font-size:14px;font-weight:700;display:none;margin-bottom:16px;}
.xpop{position:fixed;top:80px;right:24px;background:var(--c);color:#fff;padding:10px 20px;border-radius:20px;font-weight:800;font-size:16px;z-index:400;animation:xpFloat 1.5s ease forwards;}
@keyframes xpFloat{0%{opacity:0;transform:translateY(10px)}20%{opacity:1}80%{opacity:1;transform:translateY(-20px)}100%{opacity:0;transform:translateY(-40px)}}
.iwrap{max-width:600px;margin:60px auto;padding:0 20px;}
.icard{background:#fff;border-radius:24px;padding:48px 40px;box-shadow:0 8px 40px rgba(0,0,0,.1);text-align:center;}
.iicon{width:90px;height:90px;background:var(--c);border-radius:22px;display:flex;align-items:center;justify-content:center;font-size:40px;color:#fff;margin:0 auto 24px;}
.icard h1{font-family:'Space Grotesk',sans-serif;font-size:30px;font-weight:700;color:var(--dark);margin-bottom:12px;}
.icard p{font-size:16px;color:#555;line-height:1.7;margin-bottom:28px;}
.ipills{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-bottom:32px;}
.ipill{background:var(--cl);color:#3730a3;padding:8px 18px;border-radius:20px;font-size:13px;font-weight:700;}
.bstart{background:var(--c);color:#fff;border:none;padding:16px 56px;border-radius:12px;font-size:18px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;transition:.2s;}
.bstart:hover{filter:brightness(.9);transform:translateY(-2px);}
.cwrap{max-width:560px;margin:60px auto;padding:0 20px;text-align:center;}
.ccard{background:#fff;border-radius:24px;padding:48px 40px;box-shadow:0 8px 40px rgba(0,0,0,.1);}
.trophy{font-size:80px;margin-bottom:20px;animation:bounce .6s ease infinite alternate;}
@keyframes bounce{from{transform:translateY(0)}to{transform:translateY(-12px)}}
.ccard h1{font-family:'Space Grotesk',sans-serif;font-size:32px;font-weight:700;color:var(--dark);margin-bottom:10px;}
.cstats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:28px 0;}
.cs{background:var(--grey);border-radius:14px;padding:18px;}.cs-n{font-size:28px;font-weight:800;color:var(--c);}.cs-l{font-size:12px;color:#888;font-weight:700;text-transform:uppercase;margin-top:4px;}
.stars{display:flex;justify-content:center;gap:8px;margin:20px 0;}
.star{font-size:40px;opacity:0;animation:sp .4s ease forwards;}
.star:nth-child(1){animation-delay:.2s;}.star:nth-child(2){animation-delay:.4s;}.star:nth-child(3){animation-delay:.6s;}
@keyframes sp{from{opacity:0;transform:scale(0) rotate(-30deg)}to{opacity:1;transform:scale(1) rotate(0)}}
.bdone{background:var(--c);color:#fff;border:none;padding:16px 48px;border-radius:12px;font-size:17px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;text-decoration:none;display:inline-block;}
</style>
</head>
<body>
<div class="topbar">
  <div style="display:flex;align-items:center;gap:16px;">
    <a href="../dashboard.php" class="tb-back"><i class="fas fa-times"></i></a>
    <span class="tb-title">Password Security</span>
  </div>
  <div style="display:flex;align-items:center;gap:12px;">
    <div style="display:flex;gap:3px;"><i class="fas fa-heart" id="h1" style="color:#ff4757;"></i><i class="fas fa-heart" id="h2" style="color:#ff4757;"></i><i class="fas fa-heart" id="h3" style="color:#ff4757;"></i><i class="fas fa-heart" id="h4" style="color:#ff4757;"></i><i class="fas fa-heart" id="h5" style="color:#ff4757;"></i></div>
    <div class="tb-xp" id="xpd">⚡ 0 XP</div>
  </div>
</div>
<div class="prog-wrap"><div class="prog-bar" id="pb" style="width:0%"></div></div>

<div class="screen active" id="screen-0">
  <div class="iwrap"><div class="icard">
    <div class="iicon"><i class="fas fa-lock"></i></div>
    <h1>Password Security</h1>
    <p>Weak and reused passwords are behind a massive percentage of data breaches. Learn how to create strong passwords, use password managers, and protect your accounts with multi-factor authentication.</p>
    <div class="ipills"><span class="ipill">🔐 4 Lessons</span><span class="ipill">🎯 Simulation</span><span class="ipill">❓ 5 Questions</span><span class="ipill">⚡ 260 XP</span></div>
    <button class="bstart" onclick="goTo(1)"><i class="fas fa-play"></i> Start Module</button>
  </div></div>
</div>

<div class="screen" id="screen-1"><div class="wrap"><div class="card"><div class="badge"><i class="fas fa-book-open"></i> Lesson 1 of 4</div><h2>What makes a password strong?</h2><p>Password strength is about two things: length and unpredictability. Modern computers can crack short passwords in seconds using brute force attacks.</p><div style="background:var(--dark);border-radius:14px;padding:24px;margin:20px 0;color:#fff;"><div style="display:flex;flex-direction:column;gap:10px;"><div style="display:flex;align-items:center;justify-content:space-between;background:rgba(239,68,68,.15);border:1px solid #EF4444;border-radius:10px;padding:14px 18px;"><span style="font-family:monospace;">password123</span><span style="color:#EF4444;font-size:13px;font-weight:700;">Cracked in 3 seconds</span></div><div style="display:flex;align-items:center;justify-content:space-between;background:rgba(245,158,11,.15);border:1px solid #f59e0b;border-radius:10px;padding:14px 18px;"><span style="font-family:monospace;">P@ssw0rd!</span><span style="color:#f59e0b;font-size:13px;font-weight:700;">Cracked in 2 minutes</span></div><div style="display:flex;align-items:center;justify-content:space-between;background:rgba(16,185,129,.15);border:1px solid #10B981;border-radius:10px;padding:14px 18px;"><span style="font-family:monospace;">correct-horse-battery-staple</span><span style="color:#10B981;font-size:13px;font-weight:700;">550 years to crack</span></div></div></div><div class="hbox"><strong>💡 Use passphrases:</strong> Four random words strung together are both easy to remember and incredibly hard to crack. Long beats complex.</div></div></div><div class="continue-bar"><button class="btn-c" onclick="goTo(2)"><i class="fas fa-arrow-right"></i> Continue</button></div></div>
<div class="screen" id="screen-2"><div class="wrap"><div class="card"><div class="badge"><i class="fas fa-book-open"></i> Lesson 2 of 4</div><h2>Why password reuse is catastrophic</h2><p>Using the same password across multiple accounts is one of the most dangerous security habits. When one site is breached, attackers try those credentials everywhere — this is called credential stuffing.</p><div style="background:var(--dark);border-radius:14px;padding:24px;margin:20px 0;color:#fff;"><div style="font-size:13px;color:rgba(255,255,255,.5);font-weight:700;margin-bottom:14px;text-transform:uppercase;">Credential stuffing attack chain</div><div style="display:flex;flex-direction:column;gap:8px;font-size:14px;"><div style="background:rgba(255,255,255,.08);border-radius:8px;padding:12px;">1. Gaming site breached → your email + password exposed</div><div style="background:rgba(255,255,255,.08);border-radius:8px;padding:12px;">2. Attacker tries same password on Gmail → <span style="color:#EF4444;">ACCESS GRANTED</span></div><div style="background:rgba(255,255,255,.08);border-radius:8px;padding:12px;">3. Resets your bank password via Gmail → <span style="color:#EF4444;">BANK COMPROMISED</span></div><div style="background:rgba(255,255,255,.08);border-radius:8px;padding:12px;">4. Tries your work email → <span style="color:#EF4444;">ORGANISATION BREACHED</span></div></div></div><div class="hbox"><strong>✅ Solution:</strong> Use a unique password for every single account. A password manager makes this effortless.</div></div></div><div class="continue-bar"><button class="btn-c" onclick="goTo(3)"><i class="fas fa-arrow-right"></i> Continue</button></div></div>
<div class="screen" id="screen-3"><div class="wrap"><div class="card"><div class="badge"><i class="fas fa-book-open"></i> Lesson 3 of 4</div><h2>Password managers — your best tool</h2><p>A password manager generates, stores, and autofills unique strong passwords for every account. You only need to remember one master password.</p><ul class="tl"><li><i class="fas fa-check-circle ti"></i><div><strong>Generates strong passwords</strong> automatically — random, unique, long</div></li><li><i class="fas fa-check-circle ti"></i><div><strong>Autofills on real sites only</strong> — will not autofill on fake login pages (domain mismatch)</div></li><li><i class="fas fa-check-circle ti"></i><div><strong>Encrypted storage</strong> — even if the manager is breached, passwords are unreadable</div></li><li><i class="fas fa-check-circle ti"></i><div><strong>Free options:</strong> Bitwarden (recommended), KeePass. Paid: 1Password, LastPass</div></li></ul><div class="hbox"><strong>🚀 Start today:</strong> Install Bitwarden for free. Import your existing passwords and let it generate unique ones for new accounts. This single action dramatically improves your security posture.</div></div></div><div class="continue-bar"><button class="btn-c" onclick="goTo(4)"><i class="fas fa-arrow-right"></i> Continue</button></div></div>
<div class="screen" id="screen-4"><div class="wrap"><div class="card"><div class="badge"><i class="fas fa-book-open"></i> Lesson 4 of 4</div><h2>Multi-Factor Authentication (MFA)</h2><p>MFA adds a second verification step after your password — even if an attacker steals your password, they still cannot get in without the second factor.</p><div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:20px 0;"><div style="background:#f0fdf4;border-radius:12px;padding:18px;border-top:4px solid #10B981;"><div style="font-weight:700;color:#065f46;margin-bottom:8px;">📱 Authenticator app</div><p style="font-size:14px;color:#333;">Google Authenticator, Microsoft Authenticator — generates a 6-digit code. Most secure.</p></div><div style="background:#f0fdf4;border-radius:12px;padding:18px;border-top:4px solid #10B981;"><div style="font-weight:700;color:#065f46;margin-bottom:8px;">📲 SMS code</div><p style="font-size:14px;color:#333;">A code sent to your phone. Better than nothing but can be intercepted via SIM swapping.</p></div><div style="background:#f0fdf4;border-radius:12px;padding:18px;border-top:4px solid #10B981;"><div style="font-weight:700;color:#065f46;margin-bottom:8px;">🔑 Hardware key</div><p style="font-size:14px;color:#333;">Physical USB key (YubiKey). Most secure option — impossible to phish remotely.</p></div><div style="background:#fff5f5;border-radius:12px;padding:18px;border-top:4px solid #EF4444;"><div style="font-weight:700;color:#7f1d1d;margin-bottom:8px;">❌ No MFA</div><p style="font-size:14px;color:#333;">One stolen password = full access. Never acceptable for important accounts.</p></div></div></div></div><div class="continue-bar"><button class="btn-c" onclick="goTo(5)"><i class="fas fa-arrow-right"></i> Try Simulation</button></div></div>
<div class="screen" id="screen-5"><div class="wrap"><div class="card" style="text-align:center;padding:24px;"><div class="badge"><i class="fas fa-flask"></i> Live Simulation</div><h2>Which password is strongest?</h2><p style="color:#666;">Your IT team requires you to set a new password. Which do you choose?</p></div><div style="background:#fff;border-radius:14px;border:1px solid var(--grey2);padding:24px;margin:16px 0;display:flex;flex-direction:column;gap:12px;"><button style="padding:16px;background:#f9fafb;border:2px solid var(--grey2);border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:Manrope,sans-serif;transition:.2s;font-family:monospace;" onclick="simAction(false)" onmouseover="this.style.borderColor=`#EF4444`" onmouseout="this.style.borderColor=`var(--grey2)`">Password@2026</button><button style="padding:16px;background:#f9fafb;border:2px solid var(--grey2);border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:Manrope,sans-serif;font-family:monospace;" onclick="simAction(false)" onmouseover="this.style.borderColor=`#EF4444`" onmouseout="this.style.borderColor=`var(--grey2)`">Company123!</button><button style="padding:16px;background:#f0fdf4;border:2px solid #10B981;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:Manrope,sans-serif;font-family:monospace;" onclick="simAction(true)" onmouseover="this.style.background=`#dcfce7`" onmouseout="this.style.background=`#f0fdf4`">purple-cloud-notebook-river-42</button><button style="padding:16px;background:#f9fafb;border:2px solid var(--grey2);border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:Manrope,sans-serif;font-family:monospace;" onclick="simAction(false)" onmouseover="this.style.borderColor=`#EF4444`" onmouseout="this.style.borderColor=`var(--grey2)`">P@ssw0rd!</button></div></div></div>

<div class="screen" id="screen-quiz">
  <div class="qwrap">
    <div class="qhdr">
      <div><h2 style="font-family:'Space Grotesk',sans-serif;font-size:22px;font-weight:700;color:#fff;margin-bottom:4px;">Knowledge Check</h2><p style="color:rgba(255,255,255,.6);font-size:14px;">5 questions to complete</p></div>
      <div class="qhdr-stat"><div class="qhdr-num" id="qc">1/5</div><div class="qhdr-lbl">Question</div></div>
    </div>
    <div id="qcon"></div>
  </div>
  <div class="continue-bar">
    <button class="btn-ck" id="bck" onclick="checkAnswer()" disabled>Check Answer</button>
    <button class="btn-c" id="bnx" onclick="nextQuestion()" style="display:none">Next <i class="fas fa-arrow-right"></i></button>
  </div>
</div>

<div class="screen" id="screen-complete">
  <div class="cwrap"><div class="ccard">
    <div class="trophy">🔐</div>
    <div class="stars"><span class="star">⭐</span><span class="star">⭐</span><span class="star">⭐</span></div>
    <h1>Module Complete!</h1>
    <p style="font-size:16px;color:#666;margin-bottom:0;">Outstanding work finishing Password Security!</p>
    <div class="cstats">
      <div class="cs"><div class="cs-n" id="fs">0%</div><div class="cs-l">Score</div></div>
      <div class="cs"><div class="cs-n" id="fx">0</div><div class="cs-l">XP Earned</div></div>
      <div class="cs"><div class="cs-n">4</div><div class="cs-l">Lessons</div></div>
    </div>
    <a href="../dashboard.php" class="bdone"><i class="fas fa-home"></i> Back to Dashboard</a>
  </div></div>
</div>

<div class="fov" id="fov" style="display:none">
  <div class="fbox">
    <div class="ficon" id="fi"></div>
    <div class="fxp" id="fx2"></div>
    <div class="ftitle" id="ft"></div>
    <div class="ftext" id="ftx"></div>
    <button class="fbtn" onclick="closeFeedback()">Continue</button>
  </div>
</div>

<script>
let xp=0,lives=5,qs=0,cq=0,so=null,ansed=false,fn=null;
const questions=[{q:"Which password takes the longest to crack?",opts:["P@ssw0rd!","Company2026!","correct-horse-battery-staple-72","Tr0ub4dor&3"],ans:2,why:"Length beats complexity. A long passphrase of random words is exponentially harder to crack than a short complex password. correct-horse-battery-staple-72 would take hundreds of years."},{q:"What is credential stuffing?",opts:["A way to create strong passwords","Using breached username/password combos to access other accounts","A type of phishing attack","Filling in web forms automatically"],ans:1,why:"When one site is breached, attackers try those credentials on every other popular site. If you reuse passwords, a breach of any one site compromises all your accounts."},{q:"What is the main security advantage of a password manager?",opts:["It makes logging in faster","It generates and stores unique passwords for every account","It encrypts your hard drive","It protects against viruses"],ans:1,why:"A password manager generates a unique, strong, random password for every account. You only remember one master password. This completely eliminates credential stuffing attacks."},{q:"Which MFA method is most secure?",opts:["SMS text message codes","Email verification codes","Hardware security key (YubiKey)","Security questions"],ans:2,why:"Hardware keys are immune to phishing — even if you enter your password on a fake site, the key will not authenticate because the domain does not match. SMS can be intercepted via SIM swapping."},{q:"You receive a call asking for your password to fix a system issue. What do you do?",opts:["Give it — IT needs your password to help","Refuse — legitimate IT never needs your password","Give a temporary password","Ask them to email you instead"],ans:1,why:"No legitimate IT support, system administrator, or technical staff ever needs your actual password. This is a social engineering attack. Hang up and report it to your IT Security team."}];
function goTo(s){document.querySelectorAll('.screen').forEach(x=>x.classList.remove('active'));if(s==='quiz'){document.getElementById('screen-quiz').classList.add('active');loadQ(0);}else if(s==='complete'){document.getElementById('screen-complete').classList.add('active');showFinal();}else document.getElementById('screen-'+s).classList.add('active');updP(s);window.scrollTo(0,0);}
function updP(s){const st=[0,1,2,3,4,5,'quiz','complete'];const i=typeof s==='number'?s:st.indexOf(s);document.getElementById('pb').style.width=Math.round((i/(st.length-1))*100)+'%';}
function addXP(n){xp+=n;document.getElementById('xpd').textContent='⚡ '+xp+' XP';const p=document.createElement('div');p.className='xpop';p.textContent='+'+n+' XP';document.body.appendChild(p);setTimeout(()=>p.remove(),1500);}
function loseH(){lives--;const h=document.getElementById('h'+(lives+1));if(h)h.classList.add('lost');}
function simAction(correct){if(correct){showFB('🎯','Excellent!','good','Perfect response! You identified the safe approach correctly.',true,40);}else{loseH();showFB('⚠️','Not quite!','bad','That was the risky choice. Review the lessons to understand why and try the quiz.',false,0);}}
function showFB(icon,title,type,text,gq=false,xa=0){document.getElementById('fi').textContent=icon;document.getElementById('ft').textContent=title;document.getElementById('ft').className='ftitle '+(type==='good'?'good':'bad');document.getElementById('ftx').textContent=text;const xe=document.getElementById('fx2');if(xa>0){xe.textContent='+'+xa+' XP earned!';xe.style.display='inline-block';addXP(xa);}else xe.style.display='none';document.getElementById('fov').style.display='flex';fn=gq?'quiz':null;}
function closeFeedback(){document.getElementById('fov').style.display='none';if(fn){goTo(fn);fn=null;}else goTo('quiz');}
function loadQ(idx){cq=idx;so=null;ansed=false;document.getElementById('qc').textContent=(idx+1)+'/5';document.getElementById('bck').disabled=true;document.getElementById('bck').style.display='inline-flex';document.getElementById('bnx').style.display='none';const q=questions[idx];const L=['A','B','C','D'];let h='<div class="qcard"><div class="qnum">Question '+(idx+1)+' of 5</div><div class="qtext">'+q.q+'</div><div class="opts">';q.opts.forEach((o,i)=>{h+='<div class="opt" id="o'+i+'" onclick="selOpt('+i+')"><div class="opt-l">'+L[i]+'</div>'+o+'</div>';});h+='</div><div class="why" id="why">'+q.why+'</div></div>';document.getElementById('qcon').innerHTML=h;}
function selOpt(i){if(ansed)return;so=i;document.querySelectorAll('.opt').forEach(o=>o.classList.remove('selected'));document.getElementById('o'+i).classList.add('selected');document.getElementById('bck').disabled=false;}
function checkAnswer(){if(so===null||ansed)return;ansed=true;const q=questions[cq];const c=so===q.ans;document.querySelectorAll('.opt').forEach((o,i)=>{if(i===q.ans)o.classList.add('correct');else if(i===so&&!c)o.classList.add('wrong');});document.getElementById('why').classList.add('show');if(c){qs++;addXP(30);}else loseH();document.getElementById('bck').style.display='none';document.getElementById('bnx').style.display='inline-flex';document.getElementById('bnx').textContent=cq<4?'Next →':'See Results →';}
function nextQuestion(){if(cq<4)loadQ(cq+1);else goTo('complete');}
function showFinal(){const p=Math.round((qs/5)*100);addXP(qs*20);document.getElementById('fs').textContent=p+'%';document.getElementById('fx').textContent=xp;saveModuleProgress(p,qs*20);}
function saveModuleProgress(score,xp){const moduleId=window.CYBERAWARE_MODULE_ID||7;const formData=new FormData();formData.append('module_id',moduleId);formData.append('score',score);formData.append('xp',xp);fetch('../modules/save_module_progress.php',{method:'POST',body:formData}).then(response=>response.json()).then(data=>{console.log('Progress saved:',data);}).catch(error=>console.error('Error saving progress:',error));}
</script>
</body>
</html>
