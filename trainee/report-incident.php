<?php
/**
 * Employee Incident Reporting Form
 * Allows trainees to report suspicious emails, phishing attempts, and security incidents
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// Require login and trainee role
if (!isLoggedIn() || $_SESSION['role'] !== 'trainee') {
    header('Location: ../pages/login.php');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';
$report_id = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $incident_type = trim($_POST['incident_type'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $email_sender = trim($_POST['email_sender'] ?? '');
    $email_subject = trim($_POST['email_subject'] ?? '');
    $email_content = trim($_POST['email_content'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($incident_type)) $errors[] = 'Incident type is required';
    if (empty($subject)) $errors[] = 'Subject is required';
    if (empty($description)) $errors[] = 'Description is required';
    
    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $message_type = 'danger';
    } else {
        try {
            // Determine severity based on incident type
            $severity = 'medium';
            if (in_array($incident_type, ['phishing', 'credential_theft', 'malware'])) {
                $severity = 'high';
            }
            
            // Insert incident report
            $stmt = $pdo->prepare("
                INSERT INTO incident_reports (
                    user_id, incident_type, subject, description, 
                    email_sender, email_subject, email_content, 
                    severity, status, reported_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new', NOW())
            ");
            
            $stmt->execute([
                $user_id,
                $incident_type,
                $subject,
                $description,
                $email_sender ?: null,
                $email_subject ?: null,
                $email_content ?: null,
                $severity
            ]);
            
            $report_id = $pdo->lastInsertId();
            
            // Handle file upload
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['attachment'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($file['type'], $allowed_types)) {
                    $message = 'Report created, but file type not allowed. Allowed: JPG, PNG, GIF, PDF, TXT';
                    $message_type = 'warning';
                } elseif ($file['size'] > $max_size) {
                    $message = 'Report created, but file is too large. Maximum 5MB allowed.';
                    $message_type = 'warning';
                } else {
                    // Create upload directory if it doesn't exist
                    $upload_dir = '../uploads/incident_reports/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'incident_' . $report_id . '_' . time() . '.' . $file_ext;
                    $file_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $file_path)) {
                        // Update incident with attachment info
                        $stmt = $pdo->prepare("
                            UPDATE incident_reports 
                            SET attachment_path = ?, attachment_filename = ?, 
                                attachment_size = ?, attachment_mime_type = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $file_path,
                            $file['name'],
                            $file['size'],
                            $file['type'],
                            $report_id
                        ]);
                        
                        $message = 'Incident report submitted successfully with attachment!';
                        $message_type = 'success';
                    } else {
                        $message = 'Report created, but file upload failed.';
                        $message_type = 'warning';
                    }
                }
            } else {
                $message = 'Incident report submitted successfully!';
                $message_type = 'success';
            }
            
            // Send notification email to admin
            $admin_stmt = $pdo->query("SELECT email FROM users WHERE role IN ('admin', 'manager') LIMIT 1");
            $admin = $admin_stmt->fetch();
            if ($admin) {
                $user_stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
                $user_stmt->execute([$user_id]);
                $user = $user_stmt->fetch();
                
                $subject_email = "New Incident Report: " . $subject;
                $body = "A new incident report has been submitted.\n\n";
                $body .= "Reporter: " . $user['full_name'] . " (" . $user['email'] . ")\n";
                $body .= "Type: " . ucfirst(str_replace('_', ' ', $incident_type)) . "\n";
                $body .= "Severity: " . ucfirst($severity) . "\n";
                $body .= "Subject: " . $subject . "\n\n";
                $body .= "Description:\n" . $description . "\n\n";
                $body .= "View Report: " . $_SERVER['HTTP_HOST'] . "/cyberaware-new-Edits/admin/incident-reports.php?id=" . $report_id;
                
                // Suppress mail warning if server not configured
                @mail($admin['email'], $subject_email, $body);
            }
            
        } catch (Exception $e) {
            $message = 'Error submitting report: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Get user info
$stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Incident – CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #FF8C42 0%, #FF8C42 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #FF8C42 0%, #FF8C42 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .card-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .card-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .card-body {
            padding: 40px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .required {
            color: #e74c3c;
        }

        input[type="text"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #FF8C42;
            box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 30px;
            border: 2px dashed #FF8C42;
            border-radius: 8px;
            background: #fff9f5;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-label:hover {
            background: #ffe8d6;
            border-color: #FF8C42;
        }

        .file-upload input[type="file"]:focus + .file-upload-label {
            border-color: #FF8C42;
            box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
        }

        .file-name {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
        }

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        button {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit {
            background: linear-gradient(135deg, #FF8C42 0%, #FF8C42 100%);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 140, 66, 0.4);
        }

        .btn-cancel {
            background: #f0f0f0;
            color: #333;
        }

        .btn-cancel:hover {
            background: #e0e0e0;
        }

        .success-message {
            text-align: center;
            padding: 40px 20px;
        }

        .success-icon {
            font-size: 60px;
            color: #27ae60;
            margin-bottom: 20px;
        }

        .success-message h2 {
            color: #27ae60;
            margin-bottom: 10px;
        }

        .success-message p {
            color: #666;
            margin-bottom: 20px;
        }

        .incident-type-info {
            background: #fff9f5;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 12px;
            color: #666;
            border-left: 4px solid #FF8C42;
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .card-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1><i class="fas fa-exclamation-triangle"></i> Report Security Incident</h1>
                <p>Help us protect the organization by reporting suspicious activity</p>
            </div>

            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-circle' : 'times-circle'); ?>"></i>
                        <div><?php echo $message; ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($report_id && $message_type === 'success'): ?>
                    <div class="success-message">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2>Report Submitted Successfully!</h2>
                        <p>Your incident report has been submitted and assigned ID: <strong>#<?php echo $report_id; ?></strong></p>
                        <p>Our security team will review your report and take appropriate action.</p>
                        <button class="btn-submit" onclick="window.location.href='dashboard.php'" style="margin-top: 20px;">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </button>
                    </div>
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="incident_type">
                                    Incident Type <span class="required">*</span>
                                </label>
                                <select id="incident_type" name="incident_type" required onchange="updateIncidentInfo()">
                                    <option value="">-- Select Incident Type --</option>
                                    <option value="phishing">Phishing Email</option>
                                    <option value="malware">Malware/Suspicious File</option>
                                    <option value="suspicious_link">Suspicious Link</option>
                                    <option value="credential_theft">Credential Theft Attempt</option>
                                    <option value="social_engineering">Social Engineering</option>
                                    <option value="other">Other</option>
                                </select>
                                <div class="incident-type-info" id="incident-info" style="display: none;"></div>
                            </div>

                            <div class="form-group">
                                <label for="subject">
                                    Subject <span class="required">*</span>
                                </label>
                                <input type="text" id="subject" name="subject" placeholder="Brief summary of the incident" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">
                                Description <span class="required">*</span>
                            </label>
                            <textarea id="description" name="description" placeholder="Provide detailed information about the incident. What happened? When did you notice it? Any suspicious behavior?" required></textarea>
                            <div class="help-text">Include as much detail as possible to help our security team investigate.</div>
                        </div>

                        <div style="background: #fff9f5; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                            <h3 style="margin-bottom: 15px; font-size: 14px; color: #333;">
                                <i class="fas fa-envelope"></i> Email Details (Optional)
                            </h3>

                            <div class="form-row">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="email_sender">Sender Email Address</label>
                                    <input type="text" id="email_sender" name="email_sender" placeholder="e.g., sender@example.com">
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="email_subject">Email Subject</label>
                                    <input type="text" id="email_subject" name="email_subject" placeholder="Original email subject line">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email_content">Email Content</label>
                                <textarea id="email_content" name="email_content" placeholder="Paste the suspicious email content here" style="min-height: 100px;"></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="attachment">
                                <i class="fas fa-paperclip"></i> Attach Screenshot or File (Optional)
                            </label>
                            <div class="file-upload">
                                <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.gif,.pdf,.txt">
                                <label for="attachment" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div>
                                        <div>Click to upload or drag and drop</div>
                                        <div style="font-size: 12px; opacity: 0.7;">JPG, PNG, GIF, PDF, TXT (Max 5MB)</div>
                                    </div>
                                </label>
                                <div class="file-name" id="file-name"></div>
                            </div>
                        </div>

                        <div class="button-group">
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-paper-plane"></i> Submit Report
                            </button>
                            <button type="button" class="btn-cancel" onclick="window.location.href='dashboard.php'">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // File upload handling
        const fileInput = document.getElementById('attachment');
        const fileLabel = document.querySelector('.file-upload-label');
        const fileName = document.getElementById('file-name');

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileName.textContent = 'Selected: ' + this.files[0].name + ' (' + formatFileSize(this.files[0].size) + ')';
            } else {
                fileName.textContent = '';
            }
        });

        // Drag and drop
        fileLabel.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.background = '#ffe8d6';
            this.style.borderColor = '#FF8C42';
        });

        fileLabel.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.background = '#fff9f5';
            this.style.borderColor = '#FF8C42';
        });

        fileLabel.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.background = '#fff9f5';
            this.style.borderColor = '#FF8C42';
            
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                fileName.textContent = 'Selected: ' + e.dataTransfer.files[0].name + ' (' + formatFileSize(e.dataTransfer.files[0].size) + ')';
            }
        });

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // Update incident type info
        function updateIncidentInfo() {
            const type = document.getElementById('incident_type').value;
            const infoDiv = document.getElementById('incident-info');
            const infoMap = {
                'phishing': 'Phishing emails attempt to trick you into revealing sensitive information or clicking malicious links. Report any suspicious emails claiming to be from trusted sources.',
                'malware': 'Malware includes viruses, trojans, and other malicious software. Report suspicious files, especially unexpected attachments or downloads.',
                'suspicious_link': 'Report links that appear to lead to phishing sites, malware, or other malicious content.',
                'credential_theft': 'Report attempts to steal your login credentials or personal information.',
                'social_engineering': 'Report attempts to manipulate you into divulging confidential information or performing unauthorized actions.',
                'other': 'Report any other security incidents or suspicious activity not covered by the above categories.'
            };

            if (type && infoMap[type]) {
                infoDiv.textContent = infoMap[type];
                infoDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>
