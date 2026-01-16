<?php
session_start();

if (!isset($_SESSION['social_attempts'])) {
    $_SESSION['social_attempts'] = 0;
    $_SESSION['social_correct'] = 0;
}

$feedback = null;
$feedback_class = '';
$show_explanation = false;

$scenario = [
    'title' => 'Urgent Call from "IT Support"',
    'description' => 'You receive a phone call from someone claiming to be from your company\'s IT department. They say your account has been compromised and they need your current password to "secure" it immediately.',
    'options' => [
        'give' => 'Give them your password to help fix the issue quickly',
        'ask' => 'Ask for more proof (employee ID, callback number, etc.)',
        'hangup' => 'Hang up immediately and report to real IT/security team',
        'transfer' => 'Transfer the call to your manager'
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $choice = $_POST['choice'] ?? '';

    $_SESSION['social_attempts']++;

    if ($choice === 'hangup') {
        $_SESSION['social_correct']++;
        $feedback = "Excellent choice! Never share passwords over the phone — even if the caller sounds convincing.";
        $feedback_class = 'success';
    } else {
        $feedback = "This was a classic vishing (voice phishing) attempt.<br>Attackers often use urgency and authority to trick people.";
        $feedback_class = 'danger';
    }

    $show_explanation = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Social Engineering Scenario – CyberAware</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Reuse same style variables from previous pages */
        :root {
            --primary: #1d4ed8;
            --success: #15803d;
            --danger: #b91c1c;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-600: #475569;
            --gray-800: #1e293b;
            --shadow-md: 0 4px 16px rgba(0,0,0,0.1);
            --radius: 12px;
        }

        body { font-family: 'Inter', system-ui, sans-serif; background: var(--gray-50); color: var(--gray-800); line-height: 1.6; min-height: 100vh; padding: 2rem 1rem; }
        .container { max-width: 900px; margin: 0 auto; }
        h2 { font-size: 2rem; font-weight: 700; text-align: center; margin-bottom: 2rem; }

        .scenario-box {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            padding: 2rem;
            margin: 2rem 0;
        }

        .scenario-title { font-size: 1.4rem; font-weight: 600; margin-bottom: 1rem; color: #111827; }
        .scenario-desc { font-size: 1.05rem; margin-bottom: 2rem; }

        .options-grid {
            display: grid;
            gap: 1.2rem;
        }

        @media (min-width: 640px) {
            .options-grid { grid-template-columns: 1fr 1fr; }
        }

        .option-btn {
            padding: 1.3rem;
            background: var(--gray-100);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            text-align: center;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .option-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }

        .feedback, .explanation { padding: 1.4rem; border-radius: var(--radius); margin: 2rem 0; }
        .feedback.success { background: #f0fdf4; color: #14532d; border-left: 5px solid var(--success); }
        .feedback.danger  { background: #fef2f2; color: #7f1d1d; border-left: 5px solid var(--danger); }
        .explanation { background: white; border: 1px solid var(--gray-200); }

        .stats { text-align: center; color: var(--gray-600); margin-top: 2rem; }
    </style>
</head>
<body>

<div class="container">
    <h2>Social Engineering Scenario</h2>

    <div class="scenario-box">
        <div class="scenario-title"><?= $scenario['title'] ?></div>
        <div class="scenario-desc">
            <?= $scenario['description'] ?>
        </div>

        <p style="font-weight:500; margin-bottom:1.2rem; text-align:center;">
            What would you do?
        </p>

        <form method="POST">
            <div class="options-grid">
                <?php foreach ($scenario['options'] as $key => $text): ?>
                    <button type="submit" name="choice" value="<?= $key ?>" class="option-btn">
                        <?= htmlspecialchars($text) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </form>
    </div>

    <?php if ($feedback): ?>
    <div class="feedback <?= $feedback_class ?>">
        <?= $feedback ?>
    </div>
    <?php endif; ?>

    <?php if ($show_explanation): ?>
    <div class="explanation">
        <h3>Correct Response & Key Red Flags</h3>
        <ul style="padding-left:1.5rem; margin:1rem 0;">
            <li>Unexpected unsolicited calls asking for credentials</li>
            <li>Creating urgency or fear ("account compromised", "immediate action")</li>
            <li>Pressure to bypass normal procedures</li>
            <li>Caller ID spoofing is very common – never trust the number displayed</li>
        </ul>
        <p style="margin-top:1.3rem; font-weight:500;">
            <strong>Best practice:</strong> Hang up. Call back using official company contact information.
            Report the incident to your security team immediately.
        </p>
    </div>
    <?php endif; ?>

    <div class="stats">
        This session: <?= $_SESSION['social_attempts'] ?> attempts • 
        <?= $_SESSION['social_correct'] ?> correct decisions
    </div>
</div>

</body>
</html>