<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$session_id = isset($_GET['session']) ? (int)$_GET['session'] : 0;
$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';
$is_correct = isset($_GET['correct']) ? (int)$_GET['correct'] : 0;

// Get session details
$stmt = $conn->prepare("
    SELECT ts.*, tm.title, tm.category, tm.description,
           pt.subject, pt.sender_email, pt.sender_name
    FROM training_sessions ts
    JOIN training_modules tm ON ts.module_id = tm.id
    LEFT JOIN phishing_templates pt ON ts.template_id = pt.id
    WHERE ts.id = ? AND ts.user_id = ?
");
$stmt->execute([$session_id, $user_id]);
$session = $stmt->fetch();

if (!$session) {
    header('Location: trainee_dashboard.php');
    exit();
}

// Get user's updated summary
$stmt = $conn->prepare("SELECT * FROM user_training_summary WHERE user_id = ?");
$stmt->execute([$user_id]);
$summary = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Results - CyberShield</title>
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
        .results-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .result-header {
            padding: 50px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
            color: white;
        }
        
        .result-correct {
            background: linear-gradient(135deg, #ff8c42 0%, #ffb884 100%);
        }
        
        .result-incorrect {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        
        .result-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .feedback-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin: 20px 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .feedback-box h2 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .feedback-box ul {
            margin-left: 30px;
            line-height: 2;
        }
        
        .feedback-box ul li {
            margin: 10px 0;
        }
        
        .red-flag-item {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        
        .impact-box {
            background: #f8d7da;
            border-left: 4px solid #e74c3c;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-box-value {
            font-size: 36px;
            font-weight: bold;
            color: var(--primary);
            margin: 10px 0;
        }
        
        .stat-box-label {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="results-container">
        <div class="result-header <?php echo $is_correct ? 'result-correct' : 'result-incorrect'; ?>">
            <div class="result-icon"><?php echo $is_correct ? '✅' : '❌'; ?></div>
            <h1><?php echo $is_correct ? 'Excellent Work!' : 'Learning Opportunity'; ?></h1>
            <p style="font-size: 24px; margin-top: 15px;">
                <?php 
                echo $is_correct ? 
                    'You correctly identified and handled this threat!' : 
                    'Let\'s review what happened and how to improve.';
                ?>
            </p>
        </div>
        
        <div class="feedback-box">
            <h2>📊 Your Response</h2>
            <p><strong>You chose to: </strong>
                <?php
                switch($action) {
                    case 'clicked':
                        echo '<span style="color: #e74c3c;">Click the link / Open attachment</span>';
                        break;
                    case 'reported':
                        echo '<span style="color: #FF8C42;">Report as Phishing</span>';
                        break;
                    case 'deleted':
                        echo '<span style="color: #f39c12;">Delete the email</span>';
                        break;
                    case 'ignored':
                        echo '<span style="color: #6c757d;">Ignore / Do nothing</span>';
                        break;
                }
                ?>
            </p>
            <p><strong>Result: </strong><?php echo $session['final_score']; ?> points</p>
            <p><strong>Time Taken: </strong><?php echo $session['duration_seconds']; ?> seconds</p>
        </div>
        
        <?php if ($action === 'reported'): ?>
            <div class="feedback-box" style="border-left: 5px solid #FF8C42;">
                <h2>✅ Perfect! Here's Why This Was the Right Choice</h2>
                <p><strong>What You Did Right:</strong></p>
                <ul>
                    <li>You recognized the suspicious elements in the email</li>
                    <li>You chose to report rather than interact with the threat</li>
                    <li>You helped protect your organization and colleagues</li>
                    <li>You followed security best practices</li>
                </ul>
                
                <h3 style="margin-top: 25px;">🚩 Red Flags You Should Have Noticed:</h3>
                <div class="red-flag-item">
                    <strong>Suspicious sender:</strong> "<?php echo htmlspecialchars($session['sender_email']); ?>" is not a legitimate domain
                </div>
                <div class="red-flag-item">
                    <strong>Subject line:</strong> "<?php echo htmlspecialchars($session['subject']); ?>" uses urgency tactics
                </div>
                <div class="red-flag-item">
                    <strong>Context:</strong> Unexpected security requests should always be verified
                </div>
                
                <h3 style="margin-top: 25px;">✅ What Happens Next:</h3>
                <ul>
                    <li>IT security team will investigate this email</li>
                    <li>The sender may be blocked organization-wide</li>
                    <li>Other employees will be warned about this threat</li>
                    <li>You've helped protect your entire organization!</li>
                </ul>
            </div>
        <?php elseif ($action === 'clicked'): ?>
            <div class="feedback-box" style="border-left: 5px solid #e74c3c;">
                <h2>❌ Critical Error - Here's What Happened</h2>
                <p><strong>By clicking the link/opening the attachment, you would have:</strong></p>
                <ul style="color: #721c24;">
                    <li>Been directed to a fake website designed to steal your credentials</li>
                    <li>Potentially installed malware on your computer</li>
                    <li>Given attackers access to company systems</li>
                    <li>Put sensitive data at risk</li>
                </ul>
                
                <h3 style="margin-top: 25px;">🚩 Red Flags You Missed:</h3>
                <div class="red-flag-item">
                    <strong>Sender domain:</strong> "<?php echo htmlspecialchars($session['sender_email']); ?>" is NOT legitimate
                </div>
                <div class="red-flag-item">
                    <strong>Urgency tactic:</strong> Subject line creates false pressure to act quickly
                </div>
                <div class="red-flag-item">
                    <strong>Unexpected request:</strong> Legitimate organizations don't send surprise security emails
                </div>
                
                <h3 style="margin-top: 25px;">📚 What You Should Have Done:</h3>
                <ul>
                    <li>✓ Report the email as phishing to your IT security team</li>
                    <li>✓ Delete the email without clicking any links</li>
                    <li>✓ If concerned, contact the sender directly using a known phone number</li>
                    <li>✓ Never click links in unexpected emails</li>
                </ul>
                
                <div class="impact-box">
                    <h3 style="color: #721c24; margin-bottom: 15px;">⚠️ Real-World Business Impact</h3>
                    <p><strong>Clicking phishing links can lead to:</strong></p>
                    <ul style="color: #721c24;">
                        <li><strong>Financial Loss:</strong> Stolen credentials, unauthorized transactions, ransom payments</li>
                        <li><strong>Data Breach:</strong> Exposure of confidential company and customer information</li>
                        <li><strong>Operational Downtime:</strong> Systems offline for days during incident response</li>
                        <li><strong>Legal Consequences:</strong> Regulatory fines and compliance violations</li>
                        <li><strong>Reputation Damage:</strong> Loss of customer trust and business</li>
                    </ul>
                </div>
            </div>
        <?php elseif ($action === 'deleted'): ?>
            <div class="feedback-box" style="border-left: 5px solid #f39c12;">
                <h2>⚠️ Good, But Not Best Practice</h2>
                <p><strong>What You Did:</strong> Deleting suspicious emails removes the immediate threat to you.</p>
                
                <h3 style="margin-top: 25px;">Why Reporting Is Better:</h3>
                <ul>
                    <li>📊 Helps IT track attack campaigns and patterns</li>
                    <li>🛡️ Protects other employees who may receive the same email</li>
                    <li>📈 Provides valuable security intelligence</li>
                    <li>🎯 Allows blocking of malicious senders organization-wide</li>
                </ul>
                
                <h3 style="margin-top: 25px;">🚩 Red Flags in This Email:</h3>
                <div class="red-flag-item">
                    Suspicious sender: "<?php echo htmlspecialchars($session['sender_email']); ?>"
                </div>
                <div class="red-flag-item">
                    Urgency tactic in subject: "<?php echo htmlspecialchars($session['subject']); ?>"
                </div>
                
                <h3 style="margin-top: 25px;">✅ Best Practice:</h3>
                <p><strong>Always report phishing emails before deleting them.</strong> It only takes a few seconds and helps protect everyone in your organization.</p>
            </div>
        <?php else: // ignored ?>
            <div class="feedback-box" style="border-left: 5px solid #6c757d;">
                <h2>❌ Risky - Do Not Ignore Suspicious Emails</h2>
                <p><strong>What Happened:</strong> Ignoring phishing emails leaves you and others vulnerable.</p>
                
                <h3 style="margin-top: 25px;">Why Ignoring Is Dangerous:</h3>
                <ul>
                    <li>✗ You might accidentally click it later</li>
                    <li>✗ Other employees remain at risk from the same attack</li>
                    <li>✗ IT security team has no visibility into the threat</li>
                    <li>✗ Attack patterns can't be tracked or blocked</li>
                    <li>✗ The email remains in your inbox as a temptation</li>
                </ul>
                
                <h3 style="margin-top: 25px;">✅ Correct Action:</h3>
                <p><strong>Report suspicious emails immediately.</strong> Even if you're not 100% sure it's phishing, let the security experts decide. It's better to report a false positive than to ignore a real threat.</p>
                
                <div class="red-flag-item">
                    <strong>Policy Reminder:</strong> All suspicious emails must be reported to IT security according to company policy.
                </div>
            </div>
        <?php endif; ?>
        
        <div class="feedback-box">
            <h2>📈 Your Updated Security Awareness Score</h2>
            <div class="stats-summary">
                <div class="stat-box">
                    <div class="stat-box-label">Completed Modules</div>
                    <div class="stat-box-value"><?php echo $summary['completed_sessions']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-box-label">Threats Reported</div>
                    <div class="stat-box-value" style="color: #FF8C42;"><?php echo $summary['phishing_reports']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-box-label">Phishing Clicks</div>
                    <div class="stat-box-value" style="color: <?php echo $summary['phishing_clicks'] > 5 ? '#e74c3c' : '#f39c12'; ?>">
                        <?php echo $summary['phishing_clicks']; ?>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-box-label">Risk Level</div>
                    <div class="stat-box-value" style="color: <?php 
                        echo $summary['risk_score'] < 30 ? '#FF8C42' : ($summary['risk_score'] < 70 ? '#f39c12' : '#e74c3c'); 
                    ?>">
                        <?php 
                        echo $summary['risk_score'] < 30 ? 'Low' : ($summary['risk_score'] < 70 ? 'Medium' : 'High'); 
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="trainee_dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
            <a href="modules.php" class="btn btn-success">Continue Training</a>
            <a href="my_progress.php" class="btn btn-secondary">View My Progress</a>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>