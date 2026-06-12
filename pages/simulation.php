<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$session_id = isset($_GET['session']) ? (int)$_GET['session'] : 0;
$module_id = isset($_GET['module']) ? (int)$_GET['module'] : 0;

// Verify session belongs to user
$stmt = $conn->prepare("
    SELECT ts.*, tm.title, tm.category
    FROM training_sessions ts
    JOIN training_modules tm ON ts.module_id = tm.id
    WHERE ts.id = ? AND ts.user_id = ?
");
$stmt->execute([$session_id, $user_id]);
$session = $stmt->fetch();

if (!$session) {
    header('Location: trainee_dashboard.php');
    exit();
}

// Get random template for this module
$stmt = $conn->prepare("
    SELECT * FROM phishing_templates 
    WHERE module_id = ? AND is_active = 1 
    ORDER BY RAND() 
    LIMIT 1
");
$stmt->execute([$module_id]);
$template = $stmt->fetch();

// Handle user action submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize($_POST['action']);
    $is_correct = 0;
    $points = 0;
    
    // Determine if action is correct based on module type
    switch($action) {
        case 'reported':
            $is_correct = 1;
            $points = 15;
            
            // Increment phishing reports
            $stmt = $conn->prepare("
                UPDATE user_training_summary 
                SET phishing_reports = phishing_reports + 1,
                    risk_score = GREATEST(risk_score - 5, 0)
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            break;
            
        case 'clicked':
            $is_correct = 0;
            $points = -10;
            
            // Increment phishing clicks
            $stmt = $conn->prepare("
                UPDATE user_training_summary 
                SET phishing_clicks = phishing_clicks + 1,
                    risk_score = LEAST(risk_score + 10, 100)
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            break;
            
        case 'deleted':
            $is_correct = 0;
            $points = 5;
            break;
            
        case 'ignored':
            $is_correct = 0;
            $points = 0;
            break;
    }
    
    // Record the action
    $stmt = $conn->prepare("
        INSERT INTO training_actions (session_id, step_number, action_type, action_value, is_correct, points)
        VALUES (?, 1, 'email_action', ?, ?, ?)
    ");
    $stmt->execute([$session_id, $action, $is_correct, $points]);
    
    // Complete the session
    $stmt = $conn->prepare("
        UPDATE training_sessions 
        SET completed_at = NOW(),
            duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW()),
            final_score = ?,
            passed = ?
        WHERE id = ?
    ");
    $stmt->execute([$points, ($is_correct ? 1 : 0), $session_id]);
    
    // Update user summary
    $stmt = $conn->prepare("
        UPDATE user_training_summary 
        SET total_sessions = total_sessions + 1,
            completed_sessions = completed_sessions + 1,
            last_training_date = NOW()
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    
    // Redirect to results
    header("Location: training_results.php?session=$session_id&action=$action&correct=$is_correct");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Simulation - <?php echo htmlspecialchars($session['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        :root {
            --primary: #FF8C42;
            --platinum: #E5E4E2;
            --ink: #1f2937;
        }

        body {
            font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: radial-gradient(1000px 520px at 12% -10%, #fff1e4 0%, transparent 60%),
                linear-gradient(135deg, #ffffff 0%, var(--platinum) 100%);
            color: var(--ink);
        }

        h1, h2, h3 {
            font-family: 'Space Grotesk', 'Segoe UI', sans-serif;
        }
        .simulation-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .simulation-header {
            background: linear-gradient(135deg, #ff8c42 0%, #ffd2b3 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .email-preview {
            background: white;
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .email-header {
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .email-field {
            margin: 12px 0;
            display: flex;
            gap: 15px;
            align-items: start;
        }
        
        .email-label {
            font-weight: bold;
            color: #555;
            min-width: 80px;
        }
        
        .email-value {
            flex: 1;
        }
        
        .suspicious {
            color: #e74c3c;
            font-weight: 500;
        }
        
        .email-body {
            line-height: 1.8;
            color: #333;
        }
        
        .email-body p {
            margin: 15px 0;
        }
        
        .email-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        
        .action-btn {
            padding: 15px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .action-btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .action-btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }
        
        .action-btn-success {
            background: var(--primary);
            color: white;
        }
        
        .action-btn-success:hover {
            background: #ff7a20;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 140, 66, 0.35);
        }
        
        .action-btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .action-btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .help-text {
            background: #e7f3ff;
            border-left: 4px solid #0066cc;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .timer {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="simulation-container">
        <div class="simulation-header">
            <h1><?php echo htmlspecialchars($session['title']); ?> - Training Simulation</h1>
            <p style="margin-top: 10px;">Review the email below and decide how to respond</p>
            <div class="timer">
                <span>⏱️</span>
                <span id="timer">Time: <strong>0:00</strong></span>
            </div>
        </div>
        
        <div class="help-text">
            <strong>💡 Instructions:</strong> 
            Read the email carefully. Look for suspicious elements. Then choose the action that best represents how you would respond to this email in a real situation.
        </div>
        
        <div class="email-preview">
            <div class="email-header">
                <div class="email-field">
                    <span class="email-label">From:</span>
                    <span class="email-value <?php echo (strpos($template['sender_email'], 'secure') !== false || strpos($template['sender_email'], 'verify') !== false) ? 'suspicious' : ''; ?>">
                        <?php echo htmlspecialchars($template['sender_name']); ?> 
                        &lt;<?php echo htmlspecialchars($template['sender_email']); ?>&gt;
                    </span>
                </div>
                <div class="email-field">
                    <span class="email-label">To:</span>
                    <span class="email-value">you@company.com</span>
                </div>
                <div class="email-field">
                    <span class="email-label">Subject:</span>
                    <span class="email-value <?php echo (stripos($template['subject'], 'urgent') !== false || stripos($template['subject'], 'suspended') !== false) ? 'suspicious' : ''; ?>">
                        <?php echo htmlspecialchars($template['subject']); ?>
                    </span>
                </div>
                <div class="email-field">
                    <span class="email-label">Date:</span>
                    <span class="email-value"><?php echo date('F j, Y, g:i A'); ?></span>
                </div>
                <?php if ($template['has_attachment']): ?>
                    <div class="email-field">
                        <span class="email-label">Attachment:</span>
                        <span class="email-value suspicious">
                            📎 <?php echo htmlspecialchars($template['attachment_name']); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="email-body">
                <?php echo $template['body_html']; ?>
            </div>
        </div>
        
        <form method="POST" id="actionForm">
            <div class="email-actions">
                <button type="submit" name="action" value="clicked" class="action-btn action-btn-danger">
                    🖱️ Click Link / Open Attachment
                </button>
                <button type="submit" name="action" value="reported" class="action-btn action-btn-success">
                    🚩 Report as Phishing
                </button>
                <button type="submit" name="action" value="deleted" class="action-btn action-btn-secondary">
                    🗑️ Delete Email
                </button>
                <button type="submit" name="action" value="ignored" class="action-btn action-btn-secondary">
                    ⏭️ Ignore / Do Nothing
                </button>
            </div>
        </form>
        
        <div class="help-text" style="margin-top: 30px; background: #fff3cd; border-color: #ffc107;">
            <strong>⚠️ Remember:</strong> In a real phishing attack, clicking malicious links or opening suspicious attachments can compromise your credentials, install malware, or give attackers access to company systems.
        </div>
    </div>
    
    <script>
        // Timer functionality
        let startTime = new Date().getTime();
        
        function updateTimer() {
            let currentTime = new Date().getTime();
            let elapsed = Math.floor((currentTime - startTime) / 1000);
            let minutes = Math.floor(elapsed / 60);
            let seconds = elapsed % 60;
            document.getElementById('timer').innerHTML = `Time: <strong>${minutes}:${seconds.toString().padStart(2, '0')}</strong>`;
        }
        
        setInterval(updateTimer, 1000);
        
        // Confirm action before submission
        document.getElementById('actionForm').addEventListener('submit', function(e) {
            let action = e.submitter.value;
            let confirmMsg = '';
            
            switch(action) {
                case 'clicked':
                    confirmMsg = 'Are you sure you want to click the link/open attachment? This will be recorded as clicking on a potential phishing email.';
                    break;
                case 'reported':
                    confirmMsg = 'You are reporting this as phishing. This is the recommended action for suspicious emails.';
                    break;
                case 'deleted':
                    confirmMsg = 'You are choosing to delete this email without reporting it.';
                    break;
                case 'ignored':
                    confirmMsg = 'You are choosing to ignore this email and take no action.';
                    break;
            }
            
            if (!confirm(confirmMsg)) {
                e.preventDefault();
            }
        });
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>