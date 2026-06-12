<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Social Engineering — CyberAware</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--c:#3B82F6;--cl:#EFF6FF;--dark:#1A1A2E;--grey:#F5F5F5;--grey2:#E8E8E8;--green:#10B981;--red:#EF4444;}
body{font-family:'Manrope',sans-serif;background:var(--grey);min-height:100vh;}
.topbar{background:var(--dark);padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:200;}
.tb-back{color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;font-weight:600;display:flex;align-items:center;gap:6px;}
.tb-back:hover{color:#fff;}
.tb-title{color:#fff;font-weight:700;font-size:15px;}
.tb-lives{display:flex;gap:3px;}
.heart{color:#ff4757;font-size:18px;transition:all .3s;}
.heart.lost{color:rgba(255,255,255,.2);}
.tb-xp{background:rgba(59,130,246,.3);color:#93c5fd;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:700;}
.prog-wrap{background:rgba(255,255,255,.1);height:6px;}
.prog-bar{height:100%;background:linear-gradient(90deg,var(--c),#60a5fa);transition:width .6s ease;}
.screen{display:none;animation:fadeIn .4s ease;}
.screen.active{display:block;}
@keyframes fadeIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.lesson-wrap,.quiz-wrap,.intro-wrap,.complete-wrap{max-width:720px;margin:40px auto;padding:0 20px 80px;}
.complete-wrap{max-width:560px;text-align:center;}
.lesson-card{background:#fff;border-radius:20px;padding:36px;box-shadow:0 4px 24px rgba(0,0,0,.07);margin-bottom:20px;}
.lesson-badge{display:inline-flex;align-items:center;gap:8px;background:var(--cl);color:#1d4ed8;padding:7px 16px;border-radius:20px;font-size:13px;font-weight:700;margin-bottom:20px;}
h2{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;color:var(--dark);margin-bottom:14px;line-height:1.3;}
.lesson-card p{font-size:16px;color:#444;line-height:1.8;margin-bottom:16px;}
.highlight-box{background:var(--cl);border-left:4px solid var(--c);border-radius:0 12px 12px 0;padding:16px 20px;margin:20px 0;font-size:15px;color:#1e3a8a;line-height:1.7;}
.red-flag-list,.tip-list{list-style:none;display:flex;flex-direction:column;gap:10px;margin:16px 0;}
.red-flag-list li{display:flex;align-items:flex-start;gap:12px;background:#fff5f5;border-radius:10px;padding:14px 16px;font-size:15px;color:#333;line-height:1.5;}
.tip-list li{display:flex;align-items:flex-start;gap:12px;background:#f0fdf4;border-radius:10px;padding:14px 16px;font-size:15px;color:#333;line-height:1.5;}
.rf-icon{color:var(--red);font-size:18px;flex-shrink:0;margin-top:2px;}
.ti-icon{color:var(--green);font-size:18px;flex-shrink:0;margin-top:2px;}
/* PHONE SIM */
.phone-sim{background:var(--dark);border-radius:20px;padding:28px;margin:20px 0;color:#fff;}
.phone-header{display:flex;align-items:center;gap:14px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid rgba(255,255,255,.1);}
.caller-avatar{width:52px;height:52px;border-radius:50%;background:var(--c);display:flex;align-items:center;justify-content:center;font-size:22px;}
.caller-info h4{font-size:16px;font-weight:700;margin-bottom:2px;}
.caller-info span{font-size:13px;color:rgba(255,255,255,.5);}
.call-duration{margin-left:auto;background:rgba(255,255,255,.1);padding:4px 12px;border-radius:20px;font-size:12px;}
.transcript{display:flex;flex-direction:column;gap:14px;margin-bottom:20px;}
.msg{max-width:75%;}
.msg.caller{align-self:flex-start;}
.msg.you{align-self:flex-end;}
.msg-bubble{padding:12px 16px;border-radius:16px;font-size:14px;line-height:1.5;}
.caller .msg-bubble{background:rgba(255,255,255,.1);color:#fff;border-radius:4px 16px 16px 16px;}
.you .msg-bubble{background:var(--c);color:#fff;border-radius:16px 4px 16px 16px;}
.msg-label{font-size:11px;color:rgba(255,255,255,.4);margin-bottom:4px;font-weight:600;text-transform:uppercase;}
.phone-actions{display:flex;gap:12px;flex-wrap:wrap;}
.pa-btn{flex:1;padding:12px 16px;border-radius:10px;font-size:14px;font-weight:700;border:none;cursor:pointer;font-family:'Manrope',sans-serif;display:flex;align-items:center;justify-content:center;gap:8px;transition:.2s;min-width:120px;}
.pa-comply{background:var(--red);color:#fff;}
.pa-refuse{background:var(--green);color:#fff;}
.pa-verify{background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);}
.pa-btn:hover{opacity:.9;transform:translateY(-1px);}
/* CONTINUE/QUIZ/COMPLETE shared */
.continue-bar{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid var(--grey2);padding:16px 24px;display:flex;justify-content:center;gap:16px;z-index:100;}
.btn-continue{background:var(--c);color:#fff;border:none;padding:15px 48px;border-radius:12px;font-size:17px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;transition:.2s;display:flex;align-items:center;gap:10px;}
.btn-continue:hover{background:#2563eb;transform:translateY(-2px);}
.btn-check{background:var(--dark);color:#fff;border:none;padding:15px 48px;border-radius:12px;font-size:17px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;}
.btn-check:disabled,.btn-continue:disabled{background:var(--grey2);color:#aaa;cursor:not-allowed;transform:none;}
.quiz-header{background:var(--dark);border-radius:16px;padding:24px 28px;color:#fff;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;}
.qh-left h2{font-size:22px!important;margin-bottom:4px!important;}
.qh-left p{color:rgba(255,255,255,.6);font-size:14px;}
.qh-right{background:rgba(255,255,255,.1);border-radius:12px;padding:12px 20px;text-align:center;}
.qh-num{font-size:28px;font-weight:800;color:#93c5fd;}
.qh-lbl{font-size:11px;color:rgba(255,255,255,.6);font-weight:600;}
.q-card{background:#fff;border-radius:20px;padding:32px;box-shadow:0 4px 20px rgba(0,0,0,.07);margin-bottom:20px;}
.q-num{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--c);margin-bottom:12px;}
.q-text{font-size:19px;font-weight:700;color:var(--dark);margin-bottom:24px;line-height:1.5;}
.options{display:flex;flex-direction:column;gap:12px;}
.option{display:flex;align-items:center;gap:14px;padding:16px 20px;border:2px solid var(--grey2);border-radius:12px;cursor:pointer;transition:all .2s;font-size:15px;font-weight:600;color:var(--dark);}
.option:hover{border-color:var(--c);background:var(--cl);}
.option.selected{border-color:var(--c);background:var(--cl);}
.option.correct{border-color:var(--green);background:#f0fdf4;color:#065f46;}
.option.wrong{border-color:var(--red);background:#fff5f5;color:#7f1d1d;}
.option-letter{width:32px;height:32px;border-radius:8px;background:var(--grey2);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;}
.option.selected .option-letter{background:var(--c);color:#fff;}
.option.correct .option-letter{background:var(--green);color:#fff;}
.option.wrong .option-letter{background:var(--red);color:#fff;}
.why-box{margin-top:16px;padding:16px 18px;background:var(--cl);border-left:4px solid var(--c);border-radius:0 10px 10px 0;font-size:14px;color:#1e3a8a;line-height:1.6;display:none;}
.why-box.show{display:block;animation:fadeIn .3s ease;}
.feedback-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:300;display:flex;align-items:center;justify-content:center;}
.feedback-box{background:#fff;border-radius:24px;padding:40px;max-width:440px;width:90%;text-align:center;animation:popIn .4s cubic-bezier(.175,.885,.32,1.275);}
@keyframes popIn{from{transform:scale(.7);opacity:0}to{transform:scale(1);opacity:1}}
.fb-icon{font-size:64px;margin-bottom:16px;}
.fb-title{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;margin-bottom:10px;}
.fb-title.good{color:var(--green);}
.fb-title.bad{color:var(--red);}
.fb-text{font-size:15px;color:#555;line-height:1.7;margin-bottom:24px;}
.fb-btn{background:var(--c);color:#fff;border:none;padding:14px 32px;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;}
.fb-xp{background:var(--cl);color:#1d4ed8;padding:8px 20px;border-radius:20px;font-size:14px;font-weight:700;display:none;margin-bottom:16px;}
.xp-pop{position:fixed;top:80px;right:24px;background:var(--c);color:#fff;padding:10px 20px;border-radius:20px;font-weight:800;font-size:16px;z-index:400;animation:xpFloat 1.5s ease forwards;}
@keyframes xpFloat{0%{opacity:0;transform:translateY(10px)}20%{opacity:1}80%{opacity:1;transform:translateY(-20px)}100%{opacity:0;transform:translateY(-40px)}}
.intro-card{background:#fff;border-radius:24px;padding:48px 40px;box-shadow:0 8px 40px rgba(0,0,0,.1);text-align:center;}
.intro-icon{width:90px;height:90px;background:linear-gradient(135deg,var(--c),#60a5fa);border-radius:22px;display:flex;align-items:center;justify-content:center;font-size:40px;color:#fff;margin:0 auto 24px;}
.intro-card h1{font-family:'Space Grotesk',sans-serif;font-size:30px;font-weight:700;color:var(--dark);margin-bottom:12px;}
.intro-card p{font-size:16px;color:#555;line-height:1.7;margin-bottom:28px;}
.intro-pills{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-bottom:32px;}
.intro-pill{background:var(--cl);color:#1d4ed8;padding:8px 18px;border-radius:20px;font-size:13px;font-weight:700;}
.btn-start{background:var(--c);color:#fff;border:none;padding:16px 56px;border-radius:12px;font-size:18px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;transition:.2s;}
.btn-start:hover{background:#2563eb;transform:translateY(-2px);}
.complete-card{background:#fff;border-radius:24px;padding:48px 40px;box-shadow:0 8px 40px rgba(0,0,0,.1);}
.trophy{font-size:80px;margin-bottom:20px;animation:bounce .6s ease infinite alternate;}
@keyframes bounce{from{transform:translateY(0)}to{transform:translateY(-12px)}}
.complete-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:28px 0;}
.cs{background:var(--grey);border-radius:14px;padding:18px;}
.cs-num{font-size:28px;font-weight:800;color:var(--c);}
.cs-lbl{font-size:12px;color:#888;font-weight:700;text-transform:uppercase;margin-top:4px;}
.stars-wrap{display:flex;justify-content:center;gap:8px;margin:20px 0;}
.star{font-size:40px;opacity:0;animation:starPop .4s ease forwards;}
.star:nth-child(1){animation-delay:.2s;}.star:nth-child(2){animation-delay:.4s;}.star:nth-child(3){animation-delay:.6s;}
@keyframes starPop{from{opacity:0;transform:scale(0) rotate(-30deg)}to{opacity:1;transform:scale(1) rotate(0)}}
.btn-done{background:var(--c);color:#fff;border:none;padding:16px 48px;border-radius:12px;font-size:17px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;text-decoration:none;display:inline-block;}
</style>
</head>
<body>
<div class="topbar">
  <div style="display:flex;align-items:center;gap:16px;">
    <a href="../dashboard.php" class="tb-back"><i class="fas fa-times"></i></a>
    <span class="tb-title">Social Engineering Defense</span>
  </div>
  <div style="display:flex;align-items:center;gap:12px;">
    <div class="tb-lives"><i class="fas fa-heart" id="h1" style="color:#ff4757;"></i><i class="fas fa-heart" id="h2" style="color:#ff4757;"></i><i class="fas fa-heart" id="h3" style="color:#ff4757;"></i><i class="fas fa-heart" id="h4" style="color:#ff4757;"></i><i class="fas fa-heart" id="h5" style="color:#ff4757;"></i></div>
    <div class="tb-xp" id="xp-display"><i class="fas fa-bolt"></i> 0 XP</div>
  </div>
</div>
<div class="prog-wrap"><div class="prog-bar" id="prog-bar" style="width:0%"></div></div>

<div class="screen active" id="screen-0">
  <div class="intro-wrap">
    <div class="intro-card">
      <div class="intro-icon"><i class="fas fa-phone-alt"></i></div>
      <h1>Social Engineering Defense</h1>
      <p>Social engineering is hacking humans — manipulating people into revealing information or taking actions that compromise security. No technical skills needed by the attacker, just psychology.</p>
      <div class="intro-pills"><span class="intro-pill"><i class="fas fa-theater-masks"></i> 4 Real lessons</span><span class="intro-pill"><i class="fas fa-phone"></i> Live call simulation</span><span class="intro-pill"><i class="fas fa-question"></i> 5 Quiz questions</span><span class="intro-pill"><i class="fas fa-bolt"></i> 280 XP</span></div>
      <button class="btn-start" onclick="goTo(1)"><i class="fas fa-play"></i> Start Module</button>
    </div>
  </div>
</div>

<div class="screen" id="screen-1">
  <div class="lesson-wrap">
    <div class="lesson-card">
      <div class="lesson-badge"><i class="fas fa-book-open"></i> Lesson 1 of 4</div>
      <h2>What is social engineering?</h2>
      <p>Social engineering is the art of manipulating people so they give up confidential information or perform actions that benefit an attacker. It exploits human nature — our desire to be helpful, our respect for authority, our fear of consequences.</p>
      <div class="highlight-box"><strong><i class="fas fa-bullseye"></i> Why it works:</strong> It's far easier to trick a person than to hack a firewall. Attackers invest hours researching their targets on LinkedIn, social media, and company websites to make their attacks convincing and personal.</div>
      <h3 style="font-size:18px;font-weight:700;color:var(--dark);margin:20px 0 12px;">Common social engineering attacks</h3>
      <ul class="red-flag-list">
        <li><i class="fas fa-phone rf-icon"></i><div><strong>Vishing (Voice Phishing)</strong> — phone calls pretending to be IT support, your bank, SARS, or even your CEO</div></li>
        <li><i class="fas fa-comment rf-icon"></i><div><strong>Smishing (SMS Phishing)</strong> — fake text messages with malicious links or urgent requests</div></li>
        <li><i class="fas fa-user-secret rf-icon"></i><div><strong>Pretexting</strong> — creating a fabricated scenario to extract information (e.g., "I'm from the audit team and need your login")</div></li>
        <li><i class="fas fa-door-open rf-icon"></i><div><strong>Tailgating</strong> — physically following authorised personnel into secure areas</div></li>
        <li><i class="fas fa-gift rf-icon"></i><div><strong>Baiting</strong> — leaving a USB drive in a car park hoping someone plugs it in out of curiosity</div></li>
      </ul>
    </div>
  </div>
  <div class="continue-bar"><button class="btn-continue" onclick="goTo(2)"><i class="fas fa-arrow-right"></i> Continue</button></div>
</div>

<div class="screen" id="screen-2">
  <div class="lesson-wrap">
    <div class="lesson-card">
      <div class="lesson-badge"><i class="fas fa-book-open"></i> Lesson 2 of 4</div>
      <h2>The psychology attackers exploit</h2>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:20px 0;">
        <div style="background:#fff5f5;border-radius:12px;padding:18px;border-top:4px solid var(--red);"><div style="font-weight:700;color:var(--red);margin-bottom:8px;"><i class="fas fa-crown"></i> Authority</div><p style="font-size:14px;color:#333;line-height:1.5;">"This is the CEO. I need you to transfer funds urgently and keep this confidential."</p></div>
        <div style="background:#fffbeb;border-radius:12px;padding:18px;border-top:4px solid #f59e0b;"><div style="font-weight:700;color:#d97706;margin-bottom:8px;"><i class="fas fa-hourglass-end"></i> Urgency</div><p style="font-size:14px;color:#333;line-height:1.5;">"Your system is infected RIGHT NOW. I need remote access this minute or you'll lose everything."</p></div>
        <div style="background:#f0fdf4;border-radius:12px;padding:18px;border-top:4px solid var(--green);"><div style="font-weight:700;color:var(--green);margin-bottom:8px;"><i class="fas fa-handshake"></i> Reciprocity</div><p style="font-size:14px;color:#333;line-height:1.5;">"I helped you fix that printer last month — can you just give me your password quickly?"</p></div>
        <div style="background:var(--cl);border-radius:12px;padding:18px;border-top:4px solid var(--c);"><div style="font-weight:700;color:var(--c);margin-bottom:8px;"><i class="fas fa-flushed"></i> Fear</div><p style="font-size:14px;color:#333;line-height:1.5;">"SARS detected tax fraud on your account. Pay immediately or face arrest."</p></div>
      </div>
      <div class="highlight-box"><strong><i class="fas fa-shield-alt"></i> Your defence:</strong> Recognise the emotional state being triggered. If you feel pressured, scared, or obligated — that's the attack working. Stop, breathe, and verify through official channels.</div>
    </div>
  </div>
  <div class="continue-bar"><button class="btn-continue" onclick="goTo(3)"><i class="fas fa-arrow-right"></i> Continue</button></div>
</div>

<div class="screen" id="screen-3">
  <div class="lesson-wrap">
    <div class="lesson-card">
      <div class="lesson-badge"><i class="fas fa-book-open"></i> Lesson 3 of 4</div>
      <h2>CEO Fraud and Business Email Compromise</h2>
      <p>Business Email Compromise (BEC) is one of the most expensive cybercrimes — costing organisations billions annually. The attacker impersonates a senior executive to trick employees into transferring money or sensitive data.</p>
      <div style="background:var(--dark);border-radius:14px;padding:24px;margin:20px 0;color:#fff;">
        <div style="font-size:12px;color:rgba(255,255,255,.4);font-weight:700;text-transform:uppercase;margin-bottom:12px;">Example WhatsApp message</div>
        <div style="background:rgba(255,255,255,.08);border-radius:12px;padding:16px;">
          <div style="font-size:12px;color:rgba(255,255,255,.5);margin-bottom:8px;">David (CEO) — 09:47</div>
          <p style="font-size:15px;line-height:1.6;">Hi, I'm in a board meeting and need your help urgently. Please purchase 5x R2000 Takealot gift cards and send me the codes. I'll explain later. Keep this between us for now.</p>
        </div>
      </div>
      <ul class="red-flag-list">
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>Unusual request</strong> — gift cards, unusual transfers, or bypassing normal procedures</div></li>
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>Urgency + secrecy</strong> — "do it now" and "keep this between us" together is a major red flag</div></li>
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>Unavailable to verify</strong> — "I'm in a meeting and can't take calls" prevents you from confirming</div></li>
        <li><i class="fas fa-exclamation-triangle rf-icon"></i><div><strong>New or unknown number</strong> — the message comes from a number not in your contacts</div></li>
      </ul>
      <div class="highlight-box"><strong><i class="fas fa-check"></i> Rule:</strong> Always verify ANY financial request by calling the person directly on a known number — regardless of how urgent or legitimate it seems.</div>
    </div>
  </div>
  <div class="continue-bar"><button class="btn-continue" onclick="goTo(4)"><i class="fas fa-arrow-right"></i> Continue</button></div>
</div>

<div class="screen" id="screen-4">
  <div class="lesson-wrap">
    <div class="lesson-card">
      <div class="lesson-badge"><i class="fas fa-book-open"></i> Lesson 4 of 4</div>
      <h2>How to respond to social engineering</h2>
      <ul class="tip-list">
        <li><i class="fas fa-pause ti-icon"></i><div><strong>Slow down</strong> — urgency is the attacker's best weapon. Taking 60 seconds to think breaks the spell</div></li>
        <li><i class="fas fa-phone ti-icon"></i><div><strong>Verify independently</strong> — hang up and call back on an official number you already have</div></li>
        <li><i class="fas fa-shield-alt ti-icon"></i><div><strong>Trust company policy</strong> — "I need to follow our security procedure" is always a valid response</div></li>
        <li><i class="fas fa-ban ti-icon"></i><div><strong>Never share passwords</strong> — legitimate IT will NEVER ask for your password over the phone</div></li>
        <li><i class="fas fa-flag ti-icon"></i><div><strong>Report all attempts</strong> — even if you didn't fall for it. Your report protects colleagues</div></li>
        <li><i class="fas fa-users ti-icon"></i><div><strong>No shame in saying no</strong> — a real supervisor understands security procedures. Anyone pressuring you to bypass them is suspicious</div></li>
      </ul>
    </div>
  </div>
  <div class="continue-bar"><button class="btn-continue" onclick="goTo(5)"><i class="fas fa-arrow-right"></i> Try the Simulation</button></div>
</div>

<div class="screen" id="screen-5">
  <div class="lesson-wrap">
    <div class="lesson-card" style="text-align:center;padding:24px;">
      <div class="lesson-badge"><i class="fas fa-flask"></i> Live Simulation</div>
      <h2>Incoming call — how do you respond?</h2>
      <p style="color:#666;font-size:15px;">You just received this call at your desk. What do you do?</p>
    </div>
    <div class="phone-sim">
      <div class="phone-header">
        <div class="caller-avatar"><i class="fas fa-user"></i></div>
        <div class="caller-info"><h4>Unknown Number</h4><span>+264 81 234 5678</span></div>
        <div class="call-duration">2:14</div>
      </div>
      <div class="transcript">
        <div class="msg caller"><div class="msg-label">Caller</div><div class="msg-bubble">"Hi, this is Alex from IT Support. We've detected suspicious activity on your account. I need your username and current password to run a security scan immediately."</div></div>
        <div class="msg caller"><div class="msg-bubble">"This is urgent — we only have a few minutes before the threat spreads to the whole network."</div></div>
        <div class="msg you"><div class="msg-label">You</div><div class="msg-bubble">What do you say?</div></div>
      </div>
      <div class="phone-actions">
        <button class="pa-btn pa-comply" onclick="simAction('comply')"><i class="fas fa-key"></i> Give my password</button>
        <button class="pa-btn pa-refuse" onclick="simAction('refuse')"><i class="fas fa-times"></i> Refuse and hang up</button>
        <button class="pa-btn pa-verify" onclick="simAction('verify')"><i class="fas fa-phone"></i> Ask for callback number to verify</button>
      </div>
    </div>
  </div>
</div>

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

<div class="screen" id="screen-complete">
  <div class="complete-wrap">
    <div class="complete-card">
      <div class="trophy"><i class="fas fa-theater-masks"></i></div>
      <div class="stars-wrap"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
      <h1>Module Complete!</h1>
      <p>You can now defend against social engineering attacks!</p>
      <div class="complete-stats">
        <div class="cs"><div class="cs-num" id="final-score">0%</div><div class="cs-lbl">Score</div></div>
        <div class="cs"><div class="cs-num" id="final-xp">0</div><div class="cs-lbl">XP Earned</div></div>
        <div class="cs"><div class="cs-num">4</div><div class="cs-lbl">Lessons</div></div>
      </div>
      <a href="../dashboard.php" class="btn-done"><i class="fas fa-home"></i> Back to Dashboard</a>
    </div>
  </div>
</div>

<div class="feedback-overlay" id="feedback-overlay" style="display:none">
  <div class="feedback-box">
    <div class="fb-icon" id="fb-icon"></div>
    <div class="fb-xp" id="fb-xp"></div>
    <div class="fb-title" id="fb-title"></div>
    <div class="fb-text" id="fb-text"></div>
    <button class="fb-btn" onclick="closeFeedback()">Continue</button>
  </div>
</div>

<script>
let xp=0,lives=5,quizScore=0,currentQuestion=0,selectedOption=null,answered=false,feedbackNext=null;
const questions=[
  {q:"An unknown caller claims to be from IT Support and asks for your password to fix a security issue. What do you do?",opts:["Give it — IT needs your password to help you","Refuse — legitimate IT will NEVER ask for your password","Give a fake password to test them","Ask them to email you instead"],ans:1,why:"No legitimate IT department will ever ask for your password over the phone. This is a social engineering attack. Hang up and report it to your IT security team."},
  {q:"Your 'CEO' sends a WhatsApp message asking you to urgently buy gift cards and keep it secret. What is this?",opts:["A legitimate urgent request","CEO Fraud / Business Email Compromise — a scam","A company reward initiative","A test from HR"],ans:1,why:"CEO fraud is one of the most costly cybercrimes. The combination of urgency + secrecy + unusual request + unverifiable sender are all major red flags. Always verify financial requests by calling directly."},
  {q:"Someone holds the door open for you at the office security gate and follows you in without badging. This is called:",opts:["Being polite","Tailgating — a physical social engineering attack","A visitor following procedure","An authorised entry method"],ans:1,why:"Tailgating is when an attacker gains physical access to a secure area by following an authorised person. Always ensure visitors use proper access procedures — politeness should never override security."},
  {q:"You find a USB drive in the company car park. What should you do?",opts:["Plug it in to see if it belongs to someone","Hand it to IT Security without plugging it in","Plug it into a personal computer instead","Leave it where it is"],ans:1,why:"This is a baiting attack. USB drives left in public places often contain malware. Plugging it in can instantly compromise your computer. Always hand found drives to IT Security — never plug in an unknown device."},
  {q:"A caller creating extreme urgency is a social engineering tactic designed to:",opts:["Help you respond faster to real threats","Bypass your rational thinking before you can verify","Test your emergency response skills","Improve customer service response times"],ans:1,why:"Urgency is the most powerful social engineering tool. It triggers the fight-or-flight response, bypassing the rational brain. The moment you feel rushed or pressured, that is precisely when you must slow down and verify."}
];
function goTo(s){document.querySelectorAll('.screen').forEach(x=>x.classList.remove('active'));if(s==='quiz'){document.getElementById('screen-quiz').classList.add('active');loadQuestion(0);}else if(s==='complete'){document.getElementById('screen-complete').classList.add('active');showFinalScore();}else document.getElementById('screen-'+s).classList.add('active');updateProgress(s);window.scrollTo(0,0);}
function updateProgress(s){const steps=[0,1,2,3,4,5,'quiz','complete'];const idx=typeof s==='number'?s:steps.indexOf(s);document.getElementById('prog-bar').style.width=Math.round((idx/(steps.length-1))*100)+'%';}
function addXP(n){xp+=n;document.getElementById('xp-display').innerHTML='<i class="fas fa-bolt"></i> '+xp+' XP';const p=document.createElement('div');p.className='xp-pop';p.textContent='+'+n+' XP';document.body.appendChild(p);setTimeout(()=>p.remove(),1500);}
function loseHeart(){lives--;const h=document.getElementById('h'+(lives+1));if(h)h.classList.add('lost');}
function simAction(a){document.querySelectorAll('.pa-btn').forEach(b=>b.disabled=true);if(a==='refuse'||a==='verify'){showFeedback('<i class="fas fa-bullseye"></i>','Great response!','good','Correct! Legitimate IT will NEVER ask for your password. Refusing and reporting is the right move. If you want to verify, call IT on the official number — never use the number the caller gives you.',true,40);}else{loseHeart();showFeedback('<i class="fas fa-exclamation-triangle"></i>','Social engineering succeeded!','bad','You gave your password to an attacker. No legitimate IT support ever needs your password. The urgency tactic was designed to make you act without thinking. Always refuse and verify.',false,0);}}
function showFeedback(icon,title,type,text,gotoQuiz=false,xpAmt=0){document.getElementById('fb-icon').innerHTML=icon;document.getElementById('fb-title').textContent=title;document.getElementById('fb-title').className='fb-title '+(type==='good'?'good':'bad');document.getElementById('fb-text').textContent=text;const xpEl=document.getElementById('fb-xp');if(xpAmt>0){xpEl.textContent='+'+xpAmt+' XP earned!';xpEl.style.display='inline-block';addXP(xpAmt);}else xpEl.style.display='none';document.getElementById('feedback-overlay').style.display='flex';feedbackNext=gotoQuiz?'quiz':null;}
function closeFeedback(){document.getElementById('feedback-overlay').style.display='none';if(feedbackNext){goTo(feedbackNext);feedbackNext=null;}else goTo('quiz');}
function loadQuestion(idx){currentQuestion=idx;selectedOption=null;answered=false;document.getElementById('q-counter').textContent=(idx+1)+'/5';document.getElementById('btn-check').disabled=true;document.getElementById('btn-check').style.display='inline-flex';document.getElementById('btn-next').style.display='none';const q=questions[idx];const L=['A','B','C','D'];let h=`<div class="q-card"><div class="q-num">Question ${idx+1} of 5</div><div class="q-text">${q.q}</div><div class="options">`;q.opts.forEach((o,i)=>{h+=`<div class="option" id="opt-${i}" onclick="selectOption(${i})"><div class="option-letter">${L[i]}</div>${o}</div>`;});h+=`</div><div class="why-box" id="why-box">${q.why}</div></div>`;document.getElementById('quiz-container').innerHTML=h;}
function selectOption(idx){if(answered)return;selectedOption=idx;document.querySelectorAll('.option').forEach(o=>o.classList.remove('selected'));document.getElementById('opt-'+idx).classList.add('selected');document.getElementById('btn-check').disabled=false;}
function checkAnswer(){if(selectedOption===null||answered)return;answered=true;const q=questions[currentQuestion];const correct=selectedOption===q.ans;document.querySelectorAll('.option').forEach((o,i)=>{if(i===q.ans)o.classList.add('correct');else if(i===selectedOption&&!correct)o.classList.add('wrong');});document.getElementById('why-box').classList.add('show');if(correct){quizScore++;addXP(30);}else loseHeart();document.getElementById('btn-check').style.display='none';document.getElementById('btn-next').style.display='inline-flex';document.getElementById('btn-next').textContent=currentQuestion<4?'Next →':'See Results →';}
function nextQuestion(){if(currentQuestion<4)loadQuestion(currentQuestion+1);else goTo('complete');}
function showFinalScore(){const pct=Math.round((quizScore/5)*100);addXP(quizScore*20);document.getElementById('final-score').textContent=pct+'%';document.getElementById('final-xp').textContent=xp;saveModuleProgress(pct,quizScore*20);}
function saveModuleProgress(score,xp){const moduleId=window.CYBERAWARE_MODULE_ID||4;const formData=new FormData();formData.append('module_id',moduleId);formData.append('score',score);formData.append('xp',xp);fetch('../modules/save_module_progress.php',{method:'POST',body:formData}).then(response=>response.json()).then(data=>{console.log('Progress saved:',data);}).catch(error=>console.error('Error saving progress:',error));}
</script>
</body>
</html>
