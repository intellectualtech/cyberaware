<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Remote Work Security — CyberAware</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--c:#0EA5E9;--cl:#F0F9FF;--dark:#1A1A2E;--grey:#F5F5F5;--grey2:#E8E8E8;--green:#10B981;--red:#EF4444;}
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
.badge{display:inline-flex;align-items:center;gap:8px;background:var(--cl);color:#0c4a6e;padding:7px 16px;border-radius:20px;font-size:13px;font-weight:700;margin-bottom:20px;}
h2{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;color:var(--dark);margin-bottom:14px;line-height:1.3;}
.card p{font-size:16px;color:#444;line-height:1.8;margin-bottom:16px;}
.hbox{background:var(--cl);border-left:4px solid var(--c);border-radius:0 12px 12px 0;padding:16px 20px;margin:20px 0;font-size:15px;color:#0c4a6e;line-height:1.7;}
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
.why{margin-top:16px;padding:16px 18px;background:var(--cl);border-left:4px solid var(--c);border-radius:0 10px 10px 0;font-size:14px;color:#0c4a6e;line-height:1.6;display:none;}
.why.show{display:block;animation:fadeIn .3s ease;}
.fov{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:300;display:flex;align-items:center;justify-content:center;}
.fbox{background:#fff;border-radius:24px;padding:40px;max-width:440px;width:90%;text-align:center;animation:popIn .4s cubic-bezier(.175,.885,.32,1.275);}
@keyframes popIn{from{transform:scale(.7);opacity:0}to{transform:scale(1);opacity:1}}
.ficon{font-size:64px;margin-bottom:16px;}.ftitle{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;margin-bottom:10px;}
.ftitle.good{color:var(--green);}.ftitle.bad{color:var(--red);}
.ftext{font-size:15px;color:#555;line-height:1.7;margin-bottom:24px;}
.fbtn{background:var(--c);color:#fff;border:none;padding:14px 32px;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;}
.fxp{background:var(--cl);color:#0c4a6e;padding:8px 20px;border-radius:20px;font-size:14px;font-weight:700;display:none;margin-bottom:16px;}
.xpop{position:fixed;top:80px;right:24px;background:var(--c);color:#fff;padding:10px 20px;border-radius:20px;font-weight:800;font-size:16px;z-index:400;animation:xpFloat 1.5s ease forwards;}
@keyframes xpFloat{0%{opacity:0;transform:translateY(10px)}20%{opacity:1}80%{opacity:1;transform:translateY(-20px)}100%{opacity:0;transform:translateY(-40px)}}
.iwrap{max-width:600px;margin:60px auto;padding:0 20px;}
.icard{background:#fff;border-radius:24px;padding:48px 40px;box-shadow:0 8px 40px rgba(0,0,0,.1);text-align:center;}
.iicon{width:90px;height:90px;background:var(--c);border-radius:22px;display:flex;align-items:center;justify-content:center;font-size:40px;color:#fff;margin:0 auto 24px;}
.icard h1{font-family:'Space Grotesk',sans-serif;font-size:30px;font-weight:700;color:var(--dark);margin-bottom:12px;}
.icard p{font-size:16px;color:#555;line-height:1.7;margin-bottom:28px;}
.ipills{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-bottom:32px;}
.ipill{background:var(--cl);color:#0c4a6e;padding:8px 18px;border-radius:20px;font-size:13px;font-weight:700;}
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
    <span class="tb-title">Remote Work Security</span>
  </div>
  <div style="display:flex;align-items:center;gap:12px;">
    <div style="display:flex;gap:3px;"><i class="fas fa-heart" id="h1" style="color:#ff4757;"></i><i class="fas fa-heart" id="h2" style="color:#ff4757;"></i><i class="fas fa-heart" id="h3" style="color:#ff4757;"></i><i class="fas fa-heart" id="h4" style="color:#ff4757;"></i><i class="fas fa-heart" id="h5" style="color:#ff4757;"></i></div>
    <div class="tb-xp" id="xpd"><i class="fas fa-bolt"></i> 0 XP</div>
  </div>
</div>
<div class="prog-wrap"><div class="prog-bar" id="pb" style="width:0%"></div></div>

<div class="screen active" id="screen-0">
  <div class="iwrap"><div class="icard">
    <div class="iicon"><i class="fas fa-home"></i></div>
    <h1>Remote Work Security</h1>
    <p>Working from home or public places introduces unique security risks. Learn how to protect your work data, secure your home network, and work remotely without compromising your organisation.</p>
    <div class="ipills"><span class="ipill"><i class="fas fa-home"></i> 4 Lessons</span><span class="ipill"><i class="fas fa-bullseye"></i> Simulation</span><span class="ipill"><i class="fas fa-question"></i> 5 Questions</span><span class="ipill"><i class="fas fa-bolt"></i> 220 XP</span></div>
    <button class="bstart" onclick="goTo(1)"><i class="fas fa-play"></i> Start Module</button>
  </div></div>
</div>

<div class="screen" id="screen-1"><div class="wrap"><div class="card"><div class="badge"><i class="fas fa-book-open"></i> Lesson 1 of 4</div><h2>Securing your home network</h2><p>Your home router is the gateway between your devices and the internet. A poorly secured router can give attackers access to everything on your network.</p><ul class="rfl"><li><i class="fas fa-exclamation-triangle ri"></i><div><strong>Default passwords:</strong> Most routers come with "admin/admin" or "admin/password". Change this immediately.</div></li><li><i class="fas fa-exclamation-triangle ri"></i><div><strong>Old firmware:</strong> Unpatched routers have known vulnerabilities. Update firmware regularly.</div></li><li><i class="fas fa-exclamation-triangle ri"></i><div><strong>WEP encryption:</strong> Outdated and crackable in minutes. Use WPA3 or WPA2.</div></li></ul><ul class="tl"><li><i class="fas fa-check-circle ti"></i><div>Change router admin password immediately on setup</div></li><li><i class="fas fa-check-circle ti"></i><div>Use WPA3 encryption on your Wi-Fi</div></li><li><i class="fas fa-check-circle ti"></i><div>Create a separate guest network for non-work devices</div></li><li><i class="fas fa-check-circle ti"></i><div>Update router firmware when updates are available</div></li></ul></div></div><div class="continue-bar"><button class="btn-c" onclick="goTo(2)"><i class="fas fa-arrow-right"></i> Continue</button></div></div>
<div class="screen" id="screen-2"><div class="wrap"><div class="card"><div class="badge"><i class="fas fa-book-open"></i> Lesson 2 of 4</div><h2>Public Wi-Fi dangers</h2><p>Public Wi-Fi — in coffee shops, airports, hotels, and shopping centres — is a significant security risk for work activities.</p><div class="hbox"><strong><i class="fas fa-exclamation-triangle"></i> Man-in-the-Middle attacks:</strong> An attacker on the same public Wi-Fi network can intercept your traffic, capture credentials, and inject malicious content into websites you visit.</div><ul class="rfl"><li><i class="fas fa-exclamation-triangle ri"></i><div>Never access company systems on public Wi-Fi without a VPN</div></li><li><i class="fas fa-exclamation-triangle ri"></i><div>Fake hotspots: Attackers create Wi-Fi networks named "Coffee Shop Free WiFi" to capture traffic</div></li><li><i class="fas fa-exclamation-triangle ri"></i><div>Shoulder surfing: People can see your screen in public — use a privacy screen</div></li></ul><div class="hbox"><strong><i class="fas fa-check"></i> Solution:</strong> Always use your company VPN when working outside the office. VPN encrypts all traffic, making public Wi-Fi interception useless.</div></div></div><div class="continue-bar"><button class="btn-c" onclick="goTo(3)"><i class="fas fa-arrow-right"></i> Continue</button></div></div>
<div class="screen" id="screen-3"><div class="wrap"><div class="card"><div class="badge"><i class="fas fa-book-open"></i> Lesson 3 of 4</div><h2>Physical security at home and in public</h2><ul class="rfl"><li><i class="fas fa-exclamation-triangle ri"></i><div><strong>Unlocked screen:</strong> Always lock your screen (Win+L or Ctrl+Cmd+Q) when stepping away — even at home</div></li><li><i class="fas fa-exclamation-triangle ri"></i><div><strong>Visible work documents:</strong> Confidential information should not be visible through windows or in public spaces</div></li><li><i class="fas fa-exclamation-triangle ri"></i><div><strong>Shared computers:</strong> Never do work on family or shared computers — they may have keyloggers or malware</div></li><li><i class="fas fa-exclamation-triangle ri"></i><div><strong>Printing:</strong> Do not print confidential documents on home printers unless authorised</div></li></ul><ul class="tl"><li><i class="fas fa-check-circle ti"></i><div>Set screen to auto-lock after 2 minutes of inactivity</div></li><li><i class="fas fa-check-circle ti"></i><div>Use a VPN before accessing any company resources</div></li><li><i class="fas fa-check-circle ti"></i><div>Use a privacy screen filter when working in public</div></li></ul></div></div><div class="continue-bar"><button class="btn-c" onclick="goTo(4)"><i class="fas fa-arrow-right"></i> Continue</button></div></div>
<div class="screen" id="screen-4"><div class="wrap"><div class="card"><div class="badge"><i class="fas fa-book-open"></i> Lesson 4 of 4</div><h2>Secure video conferencing</h2><p>Video calls for work carry security risks that are often overlooked.</p><ul class="rfl"><li><i class="fas fa-exclamation-triangle ri"></i><div><strong>Zoom bombing:</strong> Unsecured meetings can be joined by attackers who share meeting IDs</div></li><li><i class="fas fa-exclamation-triangle ri"></i><div><strong>Background exposure:</strong> Your home background may reveal sensitive information or be used in social engineering</div></li><li><i class="fas fa-exclamation-triangle ri"></i><div><strong>Screen sharing:</strong> Accidentally sharing the wrong window can expose confidential data</div></li></ul><ul class="tl"><li><i class="fas fa-check-circle ti"></i><div>Always use meeting passwords and waiting rooms</div></li><li><i class="fas fa-check-circle ti"></i><div>Use virtual backgrounds to hide your environment</div></li><li><i class="fas fa-check-circle ti"></i><div>Close all sensitive documents before screen sharing</div></li><li><i class="fas fa-check-circle ti"></i><div>Never share meeting links publicly on social media</div></li></ul></div></div><div class="continue-bar"><button class="btn-c" onclick="goTo(5)"><i class="fas fa-arrow-right"></i> Try Simulation</button></div></div>
<div class="screen" id="screen-5"><div class="wrap"><div class="card" style="text-align:center;padding:24px;"><div class="badge"><i class="fas fa-flask"></i> Live Simulation</div><h2>Working at a coffee shop</h2><p style="color:#666;">You are working remotely at a coffee shop. You need to access the company HR portal. What do you do?</p></div><div style="background:#fff;border-radius:14px;border:1px solid var(--grey2);padding:24px;margin:16px 0;"><div style="display:flex;align-items:center;gap:12px;padding:14px;background:#f9fafb;border-radius:10px;margin-bottom:20px;"><div style="font-size:24px;">📶</div><div><div style="font-weight:700;font-size:15px;">Coffee_Shop_FreeWiFi</div><div style="font-size:13px;color:#888;">Open network — no password — strong signal</div></div></div><div style="display:flex;flex-direction:column;gap:12px;"><button style="padding:14px;background:#EF4444;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:14px;font-family:Manrope,sans-serif;" onclick="simAction(false)">Connect and access HR portal directly</button><button style="padding:14px;background:#10B981;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:14px;font-family:Manrope,sans-serif;" onclick="simAction(true)">Connect VPN first then access HR portal</button><button style="padding:14px;background:#fff;color:#333;border:2px solid var(--grey2);border-radius:10px;font-weight:700;cursor:pointer;font-size:14px;font-family:Manrope,sans-serif;" onclick="simAction(false)">Use public Wi-Fi — HTTPS keeps it safe</button></div></div></div></div>

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
    <div class="trophy"><i class="fas fa-home"></i></div>
    <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
    <h1>Module Complete!</h1>
    <p style="font-size:16px;color:#666;margin-bottom:0;">Outstanding work finishing Remote Work Security!</p>
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
const questions=[{q:"You are working from a coffee shop and need to access company files. What must you do first?",opts:["Connect to the coffee shop Wi-Fi and start working","Connect your VPN before accessing any company resources","Only access files that are not confidential","Use your browser in incognito mode"],ans:1,why:"VPN encrypts all your traffic, making public Wi-Fi interception impossible. Without VPN on public Wi-Fi, attackers on the same network can intercept your credentials and data."},{q:"What is a man-in-the-middle attack on public Wi-Fi?",opts:["An attacker physically sitting between you and the router","An attacker intercepting traffic between your device and the internet","A type of virus transmitted over Wi-Fi","A way to crack Wi-Fi passwords"],ans:1,why:"MITM attacks intercept traffic between you and websites, allowing attackers to capture credentials, read emails, and inject malicious content — all invisible to you. VPN prevents this completely."},{q:"You need to step away from your work laptop for 5 minutes at home. What should you do?",opts:["Leave it — you are at home and it is safe","Lock your screen (Win+L)","Minimise all windows","Log out completely"],ans:1,why:"Always lock your screen when stepping away, even at home. Family members, cleaners, or visitors could access sensitive work information. Win+L on Windows, Ctrl+Cmd+Q on Mac."},{q:"A colleague joins your Zoom meeting but you do not recognise them. What do you do?",opts:["Assume IT added them","Continue — they joined with the link so they must be legitimate","Ask everyone to identify themselves and remove unrecognised attendees","End the meeting immediately"],ans:2,why:"Zoom bombing and uninvited attendees are real risks. Always verify all attendees, use waiting rooms, and require passwords for sensitive meetings. Remove anyone you cannot verify."},{q:"Which Wi-Fi security protocol should you use on your home router?",opts:["WEP — the original standard","WPA — widely compatible","WPA2 or WPA3 — current secure standards","Open network — easiest to connect"],ans:2,why:"WEP is crackable in minutes. WPA has known vulnerabilities. WPA2 and WPA3 are current secure standards. Always use WPA2 minimum, WPA3 if your router supports it."}];
function goTo(s){document.querySelectorAll('.screen').forEach(x=>x.classList.remove('active'));if(s==='quiz'){document.getElementById('screen-quiz').classList.add('active');loadQ(0);}else if(s==='complete'){document.getElementById('screen-complete').classList.add('active');showFinal();}else document.getElementById('screen-'+s).classList.add('active');updP(s);window.scrollTo(0,0);}
function updP(s){const st=[0,1,2,3,4,5,'quiz','complete'];const i=typeof s==='number'?s:st.indexOf(s);document.getElementById('pb').style.width=Math.round((i/(st.length-1))*100)+'%';}
function addXP(n){xp+=n;document.getElementById('xpd').innerHTML='<i class="fas fa-bolt"></i> '+xp+' XP';const p=document.createElement('div');p.className='xpop';p.textContent='+'+n+' XP';document.body.appendChild(p);setTimeout(()=>p.remove(),1500);}
function loseH(){lives--;const h=document.getElementById('h'+(lives+1));if(h)h.classList.add('lost');}
function simAction(correct){if(correct){showFB('<i class="fas fa-bullseye"></i>','Excellent!','good','Perfect response! You identified the safe approach correctly.',true,40);}else{loseH();showFB('<i class="fas fa-exclamation-triangle"></i>','Not quite!','bad','That was the risky choice. Review the lessons to understand why and try the quiz.',false,0);}}
function showFB(icon,title,type,text,gq=false,xa=0){document.getElementById('fi').innerHTML=icon;document.getElementById('ft').textContent=title;document.getElementById('ft').className='ftitle '+(type==='good'?'good':'bad');document.getElementById('ftx').textContent=text;const xe=document.getElementById('fx2');if(xa>0){xe.textContent='+'+xa+' XP earned!';xe.style.display='inline-block';addXP(xa);}else xe.style.display='none';document.getElementById('fov').style.display='flex';fn=gq?'quiz':null;}
function closeFeedback(){document.getElementById('fov').style.display='none';if(fn){goTo(fn);fn=null;}else goTo('quiz');}
function loadQ(idx){cq=idx;so=null;ansed=false;document.getElementById('qc').textContent=(idx+1)+'/5';document.getElementById('bck').disabled=true;document.getElementById('bck').style.display='inline-flex';document.getElementById('bnx').style.display='none';const q=questions[idx];const L=['A','B','C','D'];let h='<div class="qcard"><div class="qnum">Question '+(idx+1)+' of 5</div><div class="qtext">'+q.q+'</div><div class="opts">';q.opts.forEach((o,i)=>{h+='<div class="opt" id="o'+i+'" onclick="selOpt('+i+')"><div class="opt-l">'+L[i]+'</div>'+o+'</div>';});h+='</div><div class="why" id="why">'+q.why+'</div></div>';document.getElementById('qcon').innerHTML=h;}
function selOpt(i){if(ansed)return;so=i;document.querySelectorAll('.opt').forEach(o=>o.classList.remove('selected'));document.getElementById('o'+i).classList.add('selected');document.getElementById('bck').disabled=false;}
function checkAnswer(){if(so===null||ansed)return;ansed=true;const q=questions[cq];const c=so===q.ans;document.querySelectorAll('.opt').forEach((o,i)=>{if(i===q.ans)o.classList.add('correct');else if(i===so&&!c)o.classList.add('wrong');});document.getElementById('why').classList.add('show');if(c){qs++;addXP(30);}else loseH();document.getElementById('bck').style.display='none';document.getElementById('bnx').style.display='inline-flex';document.getElementById('bnx').textContent=cq<4?'Next →':'See Results →';}
function nextQuestion(){if(cq<4)loadQ(cq+1);else goTo('complete');}
function showFinal(){const p=Math.round((qs/5)*100);addXP(qs*20);document.getElementById('fs').textContent=p+'%';document.getElementById('fx').textContent=xp;}
</script>
</body>
</html>
