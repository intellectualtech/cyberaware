<?php
require_once '../config/database.php';
requireLogin();

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$module_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get module details
$stmt = $conn->prepare("SELECT * FROM training_modules WHERE id = ? AND is_active = 1");
$stmt->execute([$module_id]);
$module = $stmt->fetch();

if (!$module) {
    header('Location: trainee_dashboard.php');
    exit();
}

// Create training session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_training'])) {
    $stmt = $conn->prepare("
        INSERT INTO training_sessions (user_id, module_id, started_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$user_id, $module_id]);
    $session_id = $conn->lastInsertId();
    
    // Redirect to simulation page
    header("Location: simulation.php?session=$session_id&module=$module_id");
    exit();
}

// Get module templates for preview
$stmt = $conn->prepare("
    SELECT COUNT(*) as template_count 
    FROM phishing_templates 
    WHERE module_id = ? AND is_active = 1
");
$stmt->execute([$module_id]);
$template_info = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($module['title']); ?> - CyberShield</title>
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
            background: radial-gradient(1000px 520px at 10% -10%, #fff1e4 0%, transparent 60%),
                linear-gradient(135deg, #ffffff 0%, var(--platinum) 100%);
            color: var(--ink);
        }

        h1, h2, h3 {
            font-family: 'Space Grotesk', 'Segoe UI', sans-serif;
        }

        .module-detail {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .module-header {
            background: linear-gradient(135deg, #ff8c42 0%, #ffd2b3 100%);
            color: white;
            padding: 50px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .module-icon-large {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .module-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .meta-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .meta-value {
            font-size: 32px;
            font-weight: bold;
            color: var(--primary);
            margin: 10px 0;
        }
        
        .meta-label {
            color: #666;
            font-size: 14px;
        }
        
        .content-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .learning-objectives {
            list-style: none;
            padding: 0;
        }
        
        .learning-objectives li {
            padding: 15px;
            margin: 10px 0;
            background: #f8f9fa;
            border-left: 4px solid var(--primary);
            border-radius: 4px;
        }
        
        .learning-objectives li:before {
            content: "✓ ";
            color: var(--primary);
            font-weight: bold;
            margin-right: 10px;
        }
        
        .red-flags-box {
            background: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .impact-box {
            background: #f8d7da;
            border-left: 5px solid #e74c3c;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .start-training-section {
            background: linear-gradient(135deg, #ff8c42 0%, #ffb884 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
        }
        
        .start-training-section h2 {
            color: white;
            margin-bottom: 15px;
        }
        
        .start-training-section p {
            margin-bottom: 25px;
            font-size: 18px;
        }
        
        .start-btn {
            font-size: 20px;
            padding: 15px 40px;
            background: white;
            color: var(--primary);
        }
        
        .start-btn:hover {
            background: #f0f0f0;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container module-detail">
        <div class="module-header">
            <div class="module-icon-large">
                <?php
                switch($module['category']) {
                    case 'phishing': echo '📧'; break;
                    case 'credential': echo '🔐'; break;
                    case 'social': echo '📞'; break;
                    case 'malware': echo '🦠'; break;
                    case 'link': echo '🔗'; break;
                    default: echo '📚';
                }
                ?>
            </div>
            <h1><?php echo htmlspecialchars($module['title']); ?></h1>
            <p style="font-size: 20px; margin-top: 15px;"><?php echo htmlspecialchars($module['description']); ?></p>
        </div>
        
        <div class="module-meta-grid">
            <div class="meta-item">
                <div class="meta-value">⏱️ <?php echo $module['estimated_minutes']; ?></div>
                <div class="meta-label">Minutes</div>
            </div>
            <div class="meta-item">
                <div class="meta-value"><?php echo str_repeat('⭐', $module['difficulty']); ?></div>
                <div class="meta-label">Difficulty</div>
            </div>
            <div class="meta-item">
                <div class="meta-value"><?php echo $template_info['template_count']; ?></div>
                <div class="meta-label">Scenarios</div>
            </div>
        </div>
        
        <div class="content-section">
            <h2>What You'll Learn</h2>
            <?php
            $learningObjectives = [];
            switch($module['category']) {
                case 'phishing':
                    $learningObjectives = [
                        'Recognize suspicious sender addresses and email patterns',
                        'Identify urgency language and manipulation tactics',
                        'Spot unexpected attachments and malicious links',
                        'Apply proper email verification procedures',
                        'Know when and how to report phishing attempts'
                    ];
                    break;
                case 'credential':
                    $learningObjectives = [
                        'Identify fake login pages and credential harvesting attempts',
                        'Verify website URLs before entering credentials',
                        'Understand why HTTPS alone isn\'t sufficient protection',
                        'Recognize typosquatting and look-alike domains',
                        'Use browser security indicators effectively'
                    ];
                    break;
                case 'social':
                    $learningObjectives = [
                        'Identify social engineering manipulation tactics',
                        'Respond appropriately to suspicious requests',
                        'Verify caller identity before sharing information',
                        'Apply company security policies in real scenarios',
                        'Report social engineering attempts effectively'
                    ];
                    break;
                case 'malware':
                    $learningObjectives = [
                        'Recognize dangerous file extensions',
                        'Identify double extension tricks (e.g., .pdf.exe)',
                        'Spot suspicious email attachments',
                        'Understand business impact of malware infections',
                        'Follow safe file handling procedures'
                    ];
                    break;
                case 'link':
                    $learningObjectives = [
                        'Inspect URLs using hover-to-preview technique',
                        'Compare legitimate vs fake domains',
                        'Recognize typosquatting attacks',
                        'Understand URL structure and security',
                        'Use link verification tools effectively'
                    ];
                    break;
            }
            ?>
            <ul class="learning-objectives">
                <?php foreach ($learningObjectives as $objective): ?>
                    <li><?php echo $objective; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="content-section">
            <h2>Key Red Flags to Watch For</h2>
            <div class="red-flags-box">
                <h3 style="color: #856404; margin-bottom: 15px;">🚩 Warning Signs</h3>
                <?php
                $redFlags = [];
                switch($module['category']) {
                    case 'phishing':
                        $redFlags = [
                            'Sender email doesn\'t match organization domain',
                            'Urgent or threatening language creating false pressure',
                            'Requests for passwords or personal information',
                            'Unexpected attachments or suspicious links',
                            'Poor grammar or spelling errors',
                            'Generic greetings like "Dear Customer"'
                        ];
                        break;
                    case 'credential':
                        $redFlags = [
                            'URL doesn\'t match expected domain',
                            'Misspelled domain names (micr0soft.com vs microsoft.com)',
                            'Login page appears after clicking email link',
                            'No security certificate or browser warnings',
                            'Unexpected password reset requests'
                        ];
                        break;
                    case 'social':
                        $redFlags = [
                            'Creating sense of urgency or fear',
                            'Claiming authority or impersonating executives',
                            'Requesting passwords or sensitive information',
                            'Unsolicited contact from "IT support"',
                            'Requests that bypass normal procedures'
                        ];
                        break;
                    case 'malware':
                        $redFlags = [
                            'Double file extensions (.pdf.exe)',
                            'Executable files from unknown senders',
                            'Macro-enabled documents (.docm, .xlsm)',
                            'Compressed archives from unexpected sources',
                            'Requests to "enable macros" or "enable content"'
                        ];
                        break;
                    case 'link':
                        $redFlags = [
                            'Numbers replacing letters (0 for O, 1 for l)',
                            'Extra characters in domain names',
                            'Different top-level domain (.co instead of .com)',
                            'Shortened URLs hiding real destination',
                            'IP addresses instead of domain names'
                        ];
                        break;
                }
                ?>
                <ul style="margin-left: 20px; color: #856404;">
                    <?php foreach ($redFlags as $flag): ?>
                        <li style="margin: 10px 0;"><?php echo $flag; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <div class="content-section">
            <h2>Real-World Business Impact</h2>
            <div class="impact-box">
                <h3 style="color: #721c24; margin-bottom: 15px;">⚠️ Potential Consequences</h3>
                <ul style="margin-left: 20px; color: #721c24;">
                    <li style="margin: 10px 0;"><strong>Financial Loss:</strong> Unauthorized transactions, ransomware payments, recovery costs</li>
                    <li style="margin: 10px 0;"><strong>Data Breach:</strong> Exposure of sensitive company and customer information</li>
                    <li style="margin: 10px 0;"><strong>Operational Downtime:</strong> Systems offline for hours or days during incident response</li>
                    <li style="margin: 10px 0;"><strong>Reputation Damage:</strong> Loss of customer trust and competitive advantage</li>
                    <li style="margin: 10px 0;"><strong>Legal Exposure:</strong> Regulatory fines and compliance violations</li>
                </ul>
            </div>
        </div>
        
        <div class="start-training-section">
            <h2>Ready to Begin?</h2>
            <p>This training will present you with realistic scenarios to test your awareness.</p>
            <p><strong>You will receive immediate feedback on every decision.</strong></p>
            <form method="POST">
                <button type="submit" name="start_training" class="btn start-btn">
                    🎯 Start Training Module
                </button>
            </form>
            <p style="margin-top: 20px; font-size: 14px; opacity: 0.9;">
                <a href="trainee_dashboard.php" style="color: white;">← Back to Dashboard</a>
            </p>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>