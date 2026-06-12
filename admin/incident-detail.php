<?php
/**
 * Incident Report Detail View
 * View full incident details, comments, and manage status
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../index.php');
    exit;
}

$pdo = getDBConnection();
$admin_id = $_SESSION['user_id'];
$incident_id = (int)($_GET['id'] ?? 0);
$message = '';
$message_type = '';

if ($incident_id === 0) {
    header('Location: incident-reports.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $status = trim($_POST['status'] ?? '');
        $resolution_notes = trim($_POST['resolution_notes'] ?? '');
        
        if (in_array($status, ['new', 'under_review', 'resolved', 'false_positive'])) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE incident_reports 
                    SET status = ?, reviewed_by = ?, reviewed_at = NOW(), resolution_notes = ?
                    WHERE id = ?
                ");
                $stmt->execute([$status, $admin_id, $resolution_notes, $incident_id]);
                $message = 'Incident status updated successfully.';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    } elseif ($action === 'add_comment') {
        $comment_text = trim($_POST['comment_text'] ?? '');
        
        if (!empty($comment_text)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO incident_report_comments (incident_id, user_id, comment_text, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([$incident_id, $admin_id, $comment_text]);
                $message = 'Comment added successfully.';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// Get incident details
$stmt = $pdo->prepare("
    SELECT 
        ir.*, 
        u.full_name, u.email, u.department_id,
        d.name as department,
        reviewer.full_name as reviewer_name
    FROM incident_reports ir
    JOIN users u ON ir.user_id = u.id
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN users reviewer ON ir.reviewed_by = reviewer.id
    WHERE ir.id = ?
");
$stmt->execute([$incident_id]);
$incident = $stmt->fetch();

if (!$incident) {
    header('Location: incident-reports.php');
    exit;
}

// Get comments
$comments_stmt = $pdo->prepare("
    SELECT 
        irc.*, 
        u.full_name, u.email
    FROM incident_report_comments irc
    JOIN users u ON irc.user_id = u.id
    WHERE irc.incident_id = ?
    ORDER BY irc.created_at DESC
");
$comments_stmt->execute([$incident_id]);
$comments = $comments_stmt->fetchAll();

// Get attachments
$attachments_stmt = $pdo->prepare("
    SELECT * FROM incident_report_attachments
    WHERE incident_id = ?
    ORDER BY uploaded_at DESC
");
$attachments_stmt->execute([$incident_id]);
$attachments = $attachments_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident #<?php echo $incident_id; ?> – CyberAware Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #EE8E46 0%, #E67A2E 100%); }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { margin-bottom: 30px; }
        .header a { color: #667eea; text-decoration: none; font-size: 14px; }
        .header h1 { font-size: 28px; color: #333; margin: 10px 0; }
        .grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { background: #f8f9fa; padding: 20px; border-bottom: 1px solid #eee; }
        .card-header h2 { font-size: 16px; color: #333; }
        .card-body { padding: 20px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-size: 12px; color: #666; font-weight: 600; text-transform: uppercase; }
        .info-value { font-size: 14px; color: #333; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-new { background: #e3f2fd; color: #1976d2; }
        .badge-review { background: #fff3e0; color: #f57c00; }
        .badge-resolved { background: #e8f5e9; color: #388e3c; }
        .badge-false { background: #f3e5f5; color: #7b1fa2; }
        .badge-low { background: #e8f5e9; color: #388e3c; }
        .badge-medium { background: #fff3e0; color: #f57c00; }
        .badge-high { background: #ffebee; color: #c62828; }
        .badge-critical { background: #b71c1c; color: white; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #666; margin-bottom: 8px; text-transform: uppercase; }
        .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Inter', sans-serif; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        .btn-secondary { background: #f0f0f0; color: #333; }
        .btn-secondary:hover { background: #e0e0e0; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .comment { background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 15px; }
        .comment-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .comment-author { font-weight: 600; color: #333; }
        .comment-time { font-size: 12px; color: #999; }
        .comment-text { color: #555; line-height: 1.5; }
        .attachment { background: #f8f9fa; padding: 12px; border-radius: 6px; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .attachment-icon { font-size: 18px; color: #667eea; }
        .attachment-info { flex: 1; }
        .attachment-name { font-weight: 600; color: #333; }
        .attachment-size { font-size: 12px; color: #999; }
        .attachment-link { color: #667eea; text-decoration: none; }
        @media (max-width: 900px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="incident-reports.php"><i class="fas fa-arrow-left"></i> Back to Reports</a>
            <h1>Incident Report #<?php echo $incident_id; ?></h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="grid">
            <div>
                <!-- Incident Details -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-info-circle"></i> Incident Details</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <div>
                                <div class="info-label">Subject</div>
                                <div class="info-value"><?php echo htmlspecialchars($incident['subject']); ?></div>
                            </div>
                        </div>

                        <div class="info-row">
                            <div>
                                <div class="info-label">Type</div>
                                <div class="info-value"><?php echo ucfirst(str_replace('_', ' ', $incident['incident_type'])); ?></div>
                            </div>
                            <div>
                                <div class="info-label">Severity</div>
                                <div class="info-value">
                                    <span class="badge badge-<?php echo strtolower($incident['severity']); ?>">
                                        <?php echo ucfirst($incident['severity']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="info-row">
                            <div>
                                <div class="info-label">Reporter</div>
                                <div class="info-value">
                                    <strong><?php echo htmlspecialchars($incident['full_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($incident['email']); ?></small>
                                </div>
                            </div>
                            <div>
                                <div class="info-label">Department</div>
                                <div class="info-value"><?php echo htmlspecialchars($incident['department'] ?? 'N/A'); ?></div>
                            </div>
                        </div>

                        <div class="info-row">
                            <div>
                                <div class="info-label">Reported At</div>
                                <div class="info-value"><?php echo date('M d, Y H:i', strtotime($incident['reported_at'])); ?></div>
                            </div>
                        </div>

                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                            <div class="info-label" style="margin-bottom: 10px;">Description</div>
                            <div style="color: #555; line-height: 1.6; white-space: pre-wrap;">
                                <?php echo htmlspecialchars($incident['description']); ?>
                            </div>
                        </div>

                        <?php if ($incident['email_content']): ?>
                            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                                <div class="info-label" style="margin-bottom: 10px;">Email Content</div>
                                <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; color: #555; line-height: 1.6; white-space: pre-wrap; font-size: 12px;">
                                    <?php echo htmlspecialchars($incident['email_content']); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($attachments)): ?>
                            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                                <div class="info-label" style="margin-bottom: 10px;">Attachments</div>
                                <?php foreach ($attachments as $attachment): ?>
                                    <div class="attachment">
                                        <div class="attachment-icon">
                                            <i class="fas fa-file"></i>
                                        </div>
                                        <div class="attachment-info">
                                            <div class="attachment-name"><?php echo htmlspecialchars($attachment['filename']); ?></div>
                                            <div class="attachment-size"><?php echo round($attachment['file_size'] / 1024, 2); ?> KB</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Comments Section -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h2><i class="fas fa-comments"></i> Comments (<?php echo count($comments); ?>)</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_comment">
                            <div class="form-group">
                                <label>Add Comment</label>
                                <textarea name="comment_text" placeholder="Add your comment here..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Post Comment
                            </button>
                        </form>

                        <div style="margin-top: 20px;">
                            <?php if (empty($comments)): ?>
                                <p style="color: #999; text-align: center; padding: 20px;">No comments yet</p>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment">
                                        <div class="comment-header">
                                            <div>
                                                <div class="comment-author"><?php echo htmlspecialchars($comment['full_name']); ?></div>
                                                <div style="font-size: 12px; color: #999;"><?php echo htmlspecialchars($comment['email']); ?></div>
                                            </div>
                                            <div class="comment-time"><?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?></div>
                                        </div>
                                        <div class="comment-text"><?php echo htmlspecialchars($comment['comment_text']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Management Sidebar -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-tasks"></i> Status Management</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_status">
                            
                            <div class="form-group">
                                <label>Current Status</label>
                                <div style="padding: 10px; background: #f8f9fa; border-radius: 6px;">
                                    <span class="badge badge-<?php echo str_replace('_', '', $incident['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $incident['status'])); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Update Status</label>
                                <select name="status" required>
                                    <option value="new" <?php echo $incident['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                    <option value="under_review" <?php echo $incident['status'] === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                                    <option value="resolved" <?php echo $incident['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="false_positive" <?php echo $incident['status'] === 'false_positive' ? 'selected' : ''; ?>>False Positive</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Resolution Notes</label>
                                <textarea name="resolution_notes" placeholder="Add resolution notes or findings..." style="min-height: 120px;"><?php echo htmlspecialchars($incident['resolution_notes'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-save"></i> Update Status
                            </button>
                        </form>

                        <?php if ($incident['reviewed_by']): ?>
                            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                                <div class="info-label">Last Reviewed By</div>
                                <div class="info-value"><?php echo htmlspecialchars($incident['reviewer_name']); ?></div>
                                <div class="info-label" style="margin-top: 10px;">Reviewed At</div>
                                <div class="info-value"><?php echo date('M d, Y H:i', strtotime($incident['reviewed_at'])); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
