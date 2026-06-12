<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dangerous Attachments — CyberAware</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--c:#EF4444;--cl:#FFF5F5;--dark:#1A1A2E;--grey:#F5F5F5;--grey2:#E8E8E8;--green:#10B981;--red:#EF4444;}
body{font-family:'Manrope',sans-serif;background:var(--grey);min-height:100vh;}
.topbar{background:var(--dark);padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:200;}
.tb-back{color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;font-weight:600;display:flex;align-items:center;gap:6px;}.tb-back:hover{color:#fff;}
.tb-title{color:#fff;font-weight:700;font-size:15px;}
.heart{color:#ff4757;font-size:18px;transition:all .3s;}.heart.lost{color:rgba(255,255,255,.2);}
.tb-xp{background:rgba(239,68,68,.25);color:#fca5a5;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:700;}
.prog-wrap{background:rgba(255,255,255,.1);height:6px;}
.prog-bar{height:100%;background:linear-gradient(90deg,var(--c),#f87171);transition:width .6s ease;}
.screen{display:none;animation:fadeIn .4s ease;}.screen.active{display:block;}
@keyframes fadeIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.wrap{max-width:720px;margin:40px auto;padding:0 20px 80px;}
.card{background:#fff;border-radius:20px;padding:36px;box-shadow:0 4px 24px rgba(0,0,0,.07);margin-bottom:20px;}
.badge{display:inline-flex;align-items:center;gap:8px;background:var(--cl);color:#991b1b;padding:7px 16px;border-radius:20px;font-size:13px;font-weight:700;margin-bottom:20px;}
h2{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;color:var(--dark);margin-bottom:14px;line-height:1.3;}
.card p{font-size:16px;color:#444;line-height:1.8;margin-bottom:16px;}
.hbox{background:var(--cl);border-left:4px solid var(--c);border-radius:0 12px 12px 0;padding:16px 20px;margin:20px 0;font-size:15px;color:#7f1d1d;line-height:1.7;}
.rfl{list-style:none;display:flex;flex-direction:column;gap:10px;margin:16px 0;}
.rfl li{display:flex;align-items:flex-start;gap:12px;background:#fff5f5;border-radius:10px;padding:14px 16px;font-size:15px;color:#333;line-height:1.5;}
.tl{list-style:none;display:flex;flex-direction:column;gap:10px;margin:16px 0;}
.tl li{display:flex;align-items:flex-start;gap:12px;background:#f0fdf4;border-radius:10px;padding:14px 16px;font-size:15px;color:#333;line-height:1.5;}
.ri{color:var(--red);font-size:18px;flex-shrink:0;margin-top:2px;}
.ti{color:var(--green);font-size:18px;flex-shrink:0;margin-top:2px;}
.file-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;margin:20px 0;}
.file-item{border-radius:12px;padding:16px;text-align:center;border:2px solid transparent;}
.file-item.safe{background:#f0fdf4;border-color:#a7f3d0;}.file-item.danger{background:#fff5f5;border-color:#fca5a5;}.file-item.caution{background:#fffbeb;border-color:#fde68a;}
.file-ext{font-size:22px;font-weight:800;margin-bottom:6px;}.file-item.safe .file-ext{color:var(--green);}.file-item.danger .file-ext{color:var(--red);}.file-item.caution .file-ext{color:#d97706;}
.file-name{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;}
.file-item.safe .file-name{color:#065f46;}.file-item.danger .file-name{color:#7f1d1d;}.file-item.caution .file-name{color:#92400e;}
/* SIM */
.attach-sim{background:#fff;border-radius:14px;border:1px solid var(--grey2);overflow:hidden;margin:16px 0;}
.as-header{background:#f9fafb;padding:16px 20px;border-bottom:1px solid var(--grey2);font-weight:700;font-size:14px;color:var(--dark);}
.as-body{padding:20px;}
.as-file{display:flex;align-items:center;gap:14px;padding:16px;background:#f9fafb;border-radius:10px;border:2px solid var(--grey2);margin-bottom:16px;cursor:pointer;}
.as-file-icon{width:48px;height:48px;background:var(--cl);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--red);font-size:22px;}
.as-file-info h4{font-size:15px;font-weight:700;color:var(--dark);margin-bottom:2px;}
.as-file-info span{font-size:12px;color:#888;}
.as-actions{display:flex;gap:10px;flex-wrap:wrap;}
.as-btn{flex:1;padding:12px;border-radius:10px;font-size:14px;font-weight:700;border:none;cursor:pointer;font-family:'Manrope',sans-serif;display:flex;align-items:center;justify-content:center;gap:8px;transition:.2s;min-width:130px;}
.as-open{background:var(--red);color:#fff;}.as-report{background:var(--green);color:#fff;}.as-verify{background:#fff;color:var(--dark);border:2px solid var(--grey2);}
/* SHARED */
.continue-bar{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid var(--grey2);padding:16px 24px;display:flex;justify-content:center;gap:16px;z-index:100;}
.btn-c{background:var(--c);color:#fff;border:none;padding:15px 48px;border-radius:12px;font-size:17px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;transition:.2s;display:flex;align-items:center;gap:10px;}
.btn-c:hover{background:#dc2626;transform:translateY(-2px);}
.btn-ck{background:var(--dark);color:#fff;border:none;padding:15px 48px;border-radius:12px;font-size:17px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;}
.btn-ck:disabled,.btn-c:disabled{background:var(--grey2);color:#aaa;cursor:not-allowed;transform:none;}
.qwrap{max-width:720px;margin:40px auto;padding:0 20px 80px;}
.qhdr{background:var(--dark);border-radius:16px;padding:24px 28px;color:#fff;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;}
.qhdr h2{font-size:22px!important;margin-bottom:4px!important;}.qhdr p{color:rgba(255,255,255,.6);font-size:14px;}
.qhdr-stat{background:rgba(255,255,255,.1);border-radius:12px;padding:12px 20px;text-align:center;}
.qhdr-num{font-size:28px;font-weight:800;color:#fca5a5;}.qhdr-lbl{font-size:11px;color:rgba(255,255,255,.6);font-weight:600;}
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
.why{margin-top:16px;padding:16px 18px;background:var(--cl);border-left:4px solid var(--c);border-radius:0 10px 10px 0;font-size:14px;color:#7f1d1d;line-height:1.6;display:none;}
.why.show{display:block;animation:fadeIn .3s ease;}
.fov{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:300;display:flex;align-items:center;justify-content:center;}
.fbox{background:#fff;border-radius:24px;padding:40px;max-width:440px;width:90%;text-align:center;animation:popIn .4s cubic-bezier(.175,.885,.32,1.275);}
@keyframes popIn{from{transform:scale(.7);opacity:0}to{transform:scale(1);opacity:1}}
.ficon{font-size:64px;margin-bottom:16px;}.ftitle{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;margin-bottom:10px;}
.ftitle.good{color:var(--green);}.ftitle.bad{color:var(--red);}
.ftext{font-size:15px;color:#555;line-height:1.7;margin-bottom:24px;}
.fbtn{background:var(--c);color:#fff;border:none;padding:14px 32px;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;}
.fxp{background:var(--cl);color:#991b1b;padding:8px 20px;border-radius:20px;font-size:14px;font-weight:700;display:none;margin-bottom:16px;}
.xpop{position:fixed;top:80px;right:24px;background:var(--c);color:#fff;padding:10px 20px;border-radius:20px;font-weight:800;font-size:16px;z-index:400;animation:xpFloat 1.5s ease forwards;}
@keyframes xpFloat{0%{opacity:0;transform:translateY(10px)}20%{opacity:1}80%{opacity:1;transform:translateY(-20px)}100%{opacity:0;transform:translateY(-40px)}}
.iwrap{max-width:600px;margin:60px auto;padding:0 20px;}
.icard{background:#fff;border-radius:24px;padding:48px 40px;box-shadow:0 8px 40px rgba(0,0,0,.1);text-align:center;}
.iicon{width:90px;height:90px;background:linear-gradient(135deg,var(--c),#f87171);border-radius:22px;display:flex;align-items:center;justify-content:center;font-size:40px;color:#fff;margin:0 auto 24px;}
.icard h1{font-family:'Space Grotesk',sans-serif;font-size:30px;font-weight:700;color:var(--dark);margin-bottom:12px;}
.icard p{font-size:16px;color:#555;line-height:1.7;margin-bottom:28px;}
.ipills{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-bottom:32px;}
.ipill{background:var(--cl);color:#991b1b;padding:8px 18px;border-radius:20px;font-size:13px;font-weight:700;}
.bstart{background:var(--c);color:#fff;border:none;padding:16px 56px;border-radius:12px;font-size:18px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;transition:.2s;}
.bstart:hover{background:#dc2626;transform:translateY(-2px);}
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
    <span class="tb-title">Dangerous Attachments</span>
  </div>
  <div style="display:flex;align-items:center;gap:12px;">
    <div style="display:flex;gap:3px;"><i class="fas fa-heart" id="h1" style="color:#ff4757;"></i><i class="fas fa-heart" id="h2" style="color:#ff4757;"></i><i class="fas fa-heart" id="h3" style="color:#ff4757;"></i><i class="fas fa-heart" id="h4" style="color:#ff4757;"></i><i class="fas fa-heart" id="h5" style="color:#ff4757;"></i></div>
    <div class="tb-xp" id="xpd"><i class="fas fa-bolt"></i> 0 XP</div>
  </div>
</div>
<div class="prog-wrap"><div class="prog-bar" id="pb" style="width:0%"></div></div>

<div class="screen active" id="screen-0">
  <div class="iwrap"><div class="icard">
    <div class="iicon"><i class="fas fa-paperclip"></i></div>
    <h1>Dangerous Attachments</h1>
    <p>File attachments are one of the most common ways malware enters organisations. Learn which file types are dangerous, what tricks attackers use, and how to handle unexpected attachments safely.</p>
    <div class="ipills"><span class="ipill"><i class="fas fa-paperclip"></i> 4 Lessons</span><span class="ipill"><i class="fas fa-virus"></i> Simulation</span><span class="ipill"><i class="fas fa-question"></i> 5 Questions</span><span class="ipill"><i class="fas fa-bolt"></i> 180 XP</span></div>
    <button class="bstart" onclick="goTo(1)"><i class="fas fa-play"></i> Start Module</button>
  </div></div>
</div>

<div class="screen" id="screen-1">
  <div class="wrap"><div class="card">
    <div class="badge"><i class="fas fa-book-open"></i> Lesson 1 of 4</div>
    <h2>File types — safe vs dangerous</h2>
    <p>Not all file types are equally dangerous. Understanding which extensions can execute code helps you make smart decisions before opening any file.</p>
    <div class="file-grid">
      <div class="file-item danger"><div class="file-ext">.exe</div><div class="file-name">DANGEROUS</div></div>
      <div class="file-item danger"><div class="file-ext">.bat</div><div class="file-name">DANGEROUS</div></div>
      <div class="file-item danger"><div class="file-ext">.vbs</div><div class="file-name">DANGEROUS</div></div>
      <div class="file-item danger"><div class="file-ext">.ps1</div><div class="file-name">DANGEROUS</div></div>
      <div class="file-item caution"><div class="file-ext">.docm</div><div class="file-name">CAUTION</div></div>
      <div class="file-item caution"><div class="file-ext">.xlsm</div><div class="file-name">CAUTION</div></div>
      <div class="file-item caution"><div class="file-ext">.pdf</div><div class="file-name">CAUTION</div></div>
      <div class="file-item caution"><div class="file-ext">.zip</div><div class="file-name">CAUTION</div></div>
      <div class="file-item safe"><div class="file-ext">.txt</div><div class="file-name">LOWER RISK</div></div>
      <div class="file-item safe"><div class="file-ext">.png</div><div class="file-name">LOWER RISK</div></div>
    </div>
    <div class="hbox"><strong><i class="fas fa-exclamation-triangle"></i> Note:</strong> Even "safe" file types can carry exploits. The safest rule is: never open attachments you didn't expect, regardless of file type.</div>
  </div></div>
  <div class="continue-bar"><button class="btn-c" onclick="goTo(2)"><i class="fas fa-arrow-right"></i> Continue</button></div>
</div>

<div class="screen" id="screen-2">
  <div class="wrap"><div class="card">
    <div class="badge"><i class="fas fa-book-open"></i> Lesson 2 of 4</div>
    <h2>The double extension trick</h2>
    <p>Windows hides known file extensions by default. Attackers exploit this to disguise malware as safe files.</p>
    <div style="background:var(--dark);border-radius:14px;padding:24px;margin:20px 0;color:#fff;">
      <div style="margin-bottom:14px;font-size:13px;color:rgba(255,255,255,.4);font-weight:700;text-transform:uppercase;">What Windows shows vs reality</div>
      <div style="display:flex;flex-direction:column;gap:12px;">
        <div style="display:flex;justify-content:space-between;align-items:center;background:rgba(255,255,255,.08);border-radius:10px;padding:14px 18px;">
          <span style="font-family:monospace;color:#fff;">Invoice_2026.pdf</span>
          <span style="color:#EF4444;font-size:12px;font-weight:700;">ACTUALLY: Invoice_2026.pdf.exe</span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;background:rgba(255,255,255,.08);border-radius:10px;padding:14px 18px;">
          <span style="font-family:monospace;color:#fff;">Report.docx</span>
          <span style="color:#EF4444;font-size:12px;font-weight:700;">ACTUALLY: Report.docx.vbs</span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;background:rgba(255,255,255,.08);border-radius:10px;padding:14px 18px;">
          <span style="font-family:monospace;color:#fff;">Photo.jpg</span>
          <span style="color:#EF4444;font-size:12px;font-weight:700;">ACTUALLY: Photo.jpg.bat</span>
        </div>
      </div>
    </div>
    <div class="hbox"><strong><i class="fas fa-shield-alt"></i> Fix this:</strong> Enable "Show file extensions" in Windows Explorer → View → Options → View tab → uncheck "Hide extensions for known file types". This shows you the real file type always.</div>
  </div></div>
  <div class="continue-bar"><button class="btn-c" onclick="goTo(3)"><i class="fas fa-arrow-right"></i> Continue</button></div>
</div>

<div class="screen" id="screen-3">
  <div class="wrap"><div class="card">
    <div class="badge"><i class="fas fa-book-open"></i> Lesson 3 of 4</div>
    <h2>Macro attacks in Office documents</h2>
    <p>Microsoft Office documents (.docm, .xlsm) can contain macros — small programs that run automatically. Attackers hide malware inside macros and trick you into enabling them.</p>
    <div style="background:#fffbeb;border:2px solid #f59e0b;border-radius:14px;padding:24px;margin:20px 0;text-align:center;">
      <div style="font-size:28px;margin-bottom:12px;"><i class="fas fa-exclamation-triangle"></i></div>
      <div style="font-size:18px;font-weight:700;color:#92400e;margin-bottom:8px;">"This document requires macros to be enabled"</div>
      <div style="font-size:14px;color:#78350f;">"Enable Content to view this protected document"</div>
    </div>
    <div class="hbox"><strong><i class="fas fa-exclamation-triangle"></i> Never click "Enable Content" or "Enable Macros"</strong> on a document you received via email unless you are 100% certain it is from a trusted source and you specifically requested it. This single click can install ransomware.</div>
    <ul class="tl">
      <li><i class="fas fa-check-circle ti"></i><div>Verify with the sender by phone before enabling macros</div></li>
      <li><i class="fas fa-check-circle ti"></i><div>Open in Protected View first and do not click Enable</div></li>
      <li><i class="fas fa-check-circle ti"></i><div>Ask IT Security if you're unsure</div></li>
    </ul>
  </div></div>
  <div class="continue-bar"><button class="btn-c" onclick="goTo(4)"><i class="fas fa-arrow-right"></i> Continue</button></div>
</div>

<div class="screen" id="screen-4">
  <div class="wrap"><div class="card">
    <div class="badge"><i class="fas fa-book-open"></i> Lesson 4 of 4</div>
    <h2>Password-protected ZIPs — a clever trick</h2>
    <p>Attackers use password-protected ZIP files specifically to bypass email security scanners. The scanner can't inspect the contents — so the malware gets through. The password is included in the email body so you can open it yourself.</p>
    <ul class="rfl">
      <li><i class="fas fa-exclamation-triangle ri"></i><div><strong>Red flag:</strong> Unexpected ZIP file with a password provided in the email</div></li>
      <li><i class="fas fa-exclamation-triangle ri"></i><div><strong>Red flag:</strong> Sender you don't recognise or weren't expecting a file from</div></li>
      <li><i class="fas fa-exclamation-triangle ri"></i><div><strong>Red flag:</strong> File inside the ZIP is an executable or macro-enabled document</div></li>
    </ul>
    <ul class="tl">
      <li><i class="fas fa-check-circle ti"></i><div>Verify with the sender through a separate channel before opening</div></li>
      <li><i class="fas fa-check-circle ti"></i><div>Report to IT Security so they can analyse the file safely</div></li>
      <li><i class="fas fa-check-circle ti"></i><div>When in doubt, don't open it</div></li>
    </ul>
  </div></div>
  <div class="continue-bar"><button class="btn-c" onclick="goTo(5)"><i class="fas fa-arrow-right"></i> Try Simulation</button></div>
</div>

<div class="screen" id="screen-5">
  <div class="wrap">
    <div class="card" style="text-align:center;padding:24px;">
      <div class="badge"><i class="fas fa-flask"></i> Live Simulation</div>
      <h2>You received this email attachment</h2>
      <p style="color:#666;">Your colleague just emailed this to you unexpectedly. What do you do?</p>
    </div>
    <div class="attach-sim">
      <div class="as-header"><i class="fas fa-envelope"></i> From: colleague@yourcompany.com — "Salary_Review_2026.pdf"</div>
      <div class="as-body">
        <p style="font-size:14px;color:#555;margin-bottom:16px;">Hey, please review this document and let me know your thoughts. Password is: <strong>1234</strong></p>
        <div class="as-file">
          <div class="as-file-icon"><i class="fas fa-file-archive"></i></div>
          <div class="as-file-info"><h4>Salary_Review_2026.zip</h4><span>Password protected · 2.4 MB</span></div>
        </div>
        <div class="as-actions">
          <button class="as-btn as-open" onclick="simAction('open')"><i class="fas fa-folder-open"></i> Open it</button>
          <button class="as-btn as-report" onclick="simAction('report')"><i class="fas fa-flag"></i> Report to IT</button>
          <button class="as-btn as-verify" onclick="simAction('verify')"><i class="fas fa-phone"></i> Call colleague first</button>
        </div>
      </div>
    </div>
  </div>
</div>

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
    <div class="trophy"><i class="fas fa-shield-alt"></i></div>
    <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
    <h1>Module Complete!</h1>
    <p style="font-size:16px;color:#666;margin-bottom:28px;">You can now spot dangerous attachments!</p>
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
let xp=0,lives=5,qs=0,cq=0,so=null,ans=false,fn=null;
const questions=[
  {q:"A file called 'Invoice.pdf' arrives but Windows is configured to hide extensions. What could the real filename be?",opts:["Only Invoice.pdf","Invoice.pdf.exe — a dangerous executable disguised as a PDF","Invoice.pdf.doc — a Word document","The filename is exactly as shown"],ans:1,why:"Windows hides known extensions by default, so 'Invoice.pdf' could really be 'Invoice.pdf.exe'. Always enable 'Show file extensions' in Windows so you see the true file type."},
  {q:"A Word document asks you to 'Enable Content' to view it. What should you do?",opts:["Click Enable Content — you need to see the document","Never enable macros from unexpected emails — this is a common malware delivery method","Enable it only if the email looks professional","Enable it and run antivirus immediately after"],ans:1,why:"'Enable Content' triggers macros that can instantly install malware including ransomware. Legitimate documents rarely require you to enable macros. Always verify with the sender by phone before doing so."},
  {q:"You receive a password-protected ZIP from an unknown sender. The password is in the email. Why is this suspicious?",opts:["Password-protected ZIPs are always viruses","Encrypted ZIPs bypass email security scanners — the password lets you infect yourself","The sender is being extra secure","Passwords in emails are standard practice"],ans:1,why:"Email security scanners can't inspect encrypted ZIPs. Attackers include the password so the scanner is bypassed but you can still open the malware. This is a deliberate technique to evade detection."},
  {q:"Which of these files is ALWAYS highest risk to open from an unknown sender?",opts:[".jpg image file",".txt plain text file",".exe executable program",".pdf document"],ans:2,why:".exe files execute code directly on your computer. While PDFs and images can carry exploits, executable files (.exe, .bat, .vbs, .ps1, .scr) are always the highest risk and should never be opened from unknown senders."},
  {q:"Your IT colleague sends an unexpected attachment. What is the safest approach?",opts:["Open it — IT staff are trusted","Call your colleague on a known number to verify they sent it","Open it in a safe folder","Check the file size — small files are safe"],ans:1,why:"Attackers compromise email accounts and use them to send malware to contacts — this is highly effective because the email comes from a trusted person. Always verify unexpected attachments through a separate channel like a phone call."}
];
function goTo(s){document.querySelectorAll('.screen').forEach(x=>x.classList.remove('active'));if(s==='quiz'){document.getElementById('screen-quiz').classList.add('active');loadQ(0);}else if(s==='complete'){document.getElementById('screen-complete').classList.add('active');showFinal();}else document.getElementById('screen-'+s).classList.add('active');updP(s);window.scrollTo(0,0);}
function updP(s){const st=[0,1,2,3,4,5,'quiz','complete'];const i=typeof s==='number'?s:st.indexOf(s);document.getElementById('pb').style.width=Math.round((i/(st.length-1))*100)+'%';}
function addXP(n){xp+=n;document.getElementById('xpd').innerHTML='<i class="fas fa-bolt"></i> '+xp+' XP';const p=document.createElement('div');p.className='xpop';p.textContent='+'+n+' XP';document.body.appendChild(p);setTimeout(()=>p.remove(),1500);}
function loseH(){lives--;const h=document.getElementById('h'+(lives+1));if(h)h.classList.add('lost');}
function simAction(a){document.querySelectorAll('.as-btn').forEach(b=>b.disabled=true);if(a==='report'||a==='verify'){showFB('<i class="fas fa-bullseye"></i>','Smart move!','good','Exactly right! Calling to verify or reporting to IT are both correct responses. Never open unexpected password-protected ZIPs — they are a common malware delivery method.',true,40);}else{loseH();showFB('<i class="fas fa-exclamation-triangle"></i>','Malware installed!','bad','Opening that ZIP installed simulated malware. Unexpected password-protected ZIP files with passwords in the email body are a classic malware delivery technique — always verify or report first.',false,0);}}
function showFB(icon,title,type,text,gq=false,xa=0){document.getElementById('fi').innerHTML=icon;document.getElementById('ft').textContent=title;document.getElementById('ft').className='ftitle '+(type==='good'?'good':'bad');document.getElementById('ftx').textContent=text;const xe=document.getElementById('fx2');if(xa>0){xe.textContent='+'+xa+' XP earned!';xe.style.display='inline-block';addXP(xa);}else xe.style.display='none';document.getElementById('fov').style.display='flex';fn=gq?'quiz':null;}
function closeFeedback(){document.getElementById('fov').style.display='none';if(fn){goTo(fn);fn=null;}else goTo('quiz');}
function loadQ(idx){cq=idx;so=null;ans=false;document.getElementById('qc').textContent=(idx+1)+'/5';document.getElementById('bck').disabled=true;document.getElementById('bck').style.display='inline-flex';document.getElementById('bnx').style.display='none';const q=questions[idx];const L=['A','B','C','D'];let h=`<div class="qcard"><div class="qnum">Question ${idx+1} of 5</div><div class="qtext">${q.q}</div><div class="opts">`;q.opts.forEach((o,i)=>{h+=`<div class="opt" id="o${i}" onclick="selOpt(${i})"><div class="opt-l">${L[i]}</div>${o}</div>`;});h+=`</div><div class="why" id="why">${q.why}</div></div>`;document.getElementById('qcon').innerHTML=h;}
function selOpt(i){if(ans)return;so=i;document.querySelectorAll('.opt').forEach(o=>o.classList.remove('selected'));document.getElementById('o'+i).classList.add('selected');document.getElementById('bck').disabled=false;}
function checkAnswer(){if(so===null||ans)return;ans=true;const q=questions[cq];const c=so===q.ans;document.querySelectorAll('.opt').forEach((o,i)=>{if(i===q.ans)o.classList.add('correct');else if(i===so&&!c)o.classList.add('wrong');});document.getElementById('why').classList.add('show');if(c){qs++;addXP(30);}else loseH();document.getElementById('bck').style.display='none';document.getElementById('bnx').style.display='inline-flex';document.getElementById('bnx').textContent=cq<4?'Next →':'See Results →';}
function nextQuestion(){if(cq<4)loadQ(cq+1);else goTo('complete');}
function showFinal(){const p=Math.round((qs/5)*100);addXP(qs*20);document.getElementById('fs').textContent=p+'%';document.getElementById('fx').textContent=xp;saveModuleProgress(p,qs*20);}
function saveModuleProgress(score,xp){const moduleId=window.CYBERAWARE_MODULE_ID||5;const formData=new FormData();formData.append('module_id',moduleId);formData.append('score',score);formData.append('xp',xp);fetch('../modules/save_module_progress.php',{method:'POST',body:formData}).then(response=>response.json()).then(data=>{console.log('Progress saved:',data);}).catch(error=>console.error('Error saving progress:',error));}
</script>
</body>
</html>
