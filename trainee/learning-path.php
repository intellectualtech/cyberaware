<?php
/**
 * Learning Path Page
 * Sequential module progression for enrolled certificates
 */

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/CertificateManager.php';

if (!isLoggedIn() || !hasRole('trainee')) {
    header('Location: ../index.php');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$cert_manager = new CertificateManager($pdo);

$enrollment_id = (int)($_GET['enrollment_id'] ?? 0);
$message = '';
$message_type = '';

if ($enrollment_id <= 0) {
    header('Location: certificates.php');
    exit;
}

// Verify ownership
$stmt = $pdo->prepare("SELECT user_id FROM trainee_enrollments WHERE id = ?");
$stmt->execute([$enrollment_id]);
$enrollment_check = $stmt->fetch();

if (!$enrollment_check || $enrollment_check['user_id'] != $user_id) {
    header('Location: certificates.php');
    exit;
}

// Get enrollment details
$enrollment = $cert_manager->getEnrollmentDetails($enrollment_id);

// Handle module completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete_module') {
    $certificate_module_id = (int)($_POST['certificate_module_id'] ?? 0);
    $quiz_score = (int)($_POST['quiz_score'] ?? 0);

    try {
        $result = $cert_manager->completeModule($enrollment_id, $certificate_module_id, $quiz_score);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'warning';

        if ($result['success']) {
            // Refresh enrollment data
            $enrollment = $cert_manager->getEnrollmentDetails($enrollment_id);
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($enrollment['track_name']) ?> – CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF8C42;
            --success: #10b981;
            --danger: #ef4444;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --sidebar-width: 260px;
            --sidebar-height: 72px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-100);
            margin: 0;
        }

        .main-content {
            margin-bottom: var(--sidebar-height);
            padding: 2rem;
        }

        .container { max-width: 1200px; margin: 0 auto; }

        .header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .track-icon {
            font-size: 48px;
            width: 80px;
            height: 80px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .header-info h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .header-info p {
            color: #666;
            font-size: 14px;
        }

        .progress-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #666;
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .message-success { background: #d1fae5; color: #065f46; }
        .message-warning { background: #fef3c7; color: #92400e; }
        .message-danger { background: #fee2e2; color: #991b1b; }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .module-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .module-card.locked {
            opacity: 0.6;
            pointer-events: none;
        }

        .module-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-bottom: 2px solid var(--gray-200);
        }

        .module-number {
            display: inline-block;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .module-title {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .module-description {
            font-size: 13px;
            color: #666;
        }

        .module-body {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .module-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .status-locked { background: #fee2e2; color: #991b1b; }
        .status-unlocked { background: #dbeafe; color: #1e40af; }
        .status-in-progress { background: #fef3c7; color: #92400e; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-failed { background: #fee2e2; color: #991b1b; }

        .module-info {
            font-size: 13px;
            color: #666;
            margin-bottom: 1rem;
            flex: 1;
        }

        .module-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: #E67A2E;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--gray-200);
            color: #333;
        }

        .lock-icon {
            font-size: 24px;
            color: #999;
            margin-bottom: 0.5rem;
        }

        .completion-badge {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-radius: 12px;
            margin-top: 2rem;
        }

        .completion-badge i {
            font-size: 48px;
            color: var(--success);
            margin-bottom: 1rem;
        }

        .completion-badge h2 {
            color: #065f46;
            margin-bottom: 0.5rem;
        }

        .completion-badge p {
            color: #047857;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }

            .modules-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="container">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <div class="track-icon" style="background: linear-gradient(135deg, <?= $enrollment['color'] ?> 0%, <?= $enrollment['color'] ?>dd 100%);">
                        <i class="fas <?= $enrollment['icon'] ?>"></i>
                    </div>
                    <div class="header-info">
                        <h1><?= htmlspecialchars($enrollment['track_name']) ?></h1>
                        <p><?= htmlspecialchars($enrollment['track_description']) ?></p>
                    </div>
                </div>
                <a href="certificates.php" class="btn btn-secondary" style="width: auto;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- Progress -->
            <div class="progress-section">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $enrollment['progress_percentage'] ?>%"></div>
                </div>
                <div class="progress-text">
                    <span>Progress</span>
                    <span><?= $enrollment['progress_percentage'] ?>% Complete</span>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="message message-<?= $message_type ?>">
                <?= $message ?>
            </div>
            <?php endif; ?>

            <!-- Modules -->
            <div class="modules-grid">
                <?php foreach ($enrollment['modules'] as $idx => $module): ?>
                    <?php
                    $is_locked = $module['progress_status'] === 'locked';
                    $is_completed = $module['progress_status'] === 'completed';
                    $is_failed = $module['progress_status'] === 'failed';
                    ?>
                <div class="module-card <?= $is_locked ? 'locked' : '' ?>">
                    <div class="module-header">
                        <div class="module-number"><?= $idx + 1 ?></div>
                        <div class="module-title"><?= htmlspecialchars($module['module_title']) ?></div>
                        <div class="module-description"><?= htmlspecialchars(substr($module['module_description'], 0, 60)) ?>...</div>
                    </div>
                    <div class="module-body">
                        <?php if ($is_locked): ?>
                            <div class="module-status status-locked">
                                <i class="fas fa-lock"></i> Locked
                            </div>
                            <div class="lock-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div class="module-info">
                                Complete the previous module to unlock this one.
                            </div>
                        <?php elseif ($is_completed): ?>
                            <div class="module-status status-completed">
                                <i class="fas fa-check"></i> Completed
                            </div>
                            <div class="module-info">
                                <strong>Score:</strong> <?= $module['best_quiz_score'] ?>%<br>
                                <strong>Completed:</strong> <?= date('M j, Y', strtotime($module['completion_date'])) ?>
                            </div>
                        <?php elseif ($is_failed): ?>
                            <div class="module-status status-failed">
                                <i class="fas fa-times"></i> Failed
                            </div>
                            <div class="module-info">
                                Your score was below the passing threshold (70%). Please retry.
                            </div>
                            <div class="module-actions">
                                <a href="../trainee/modules/<?= strtolower(str_replace(' ', '_', $module['module_title'])) ?>.php?module_id=<?= $module['module_id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-redo"></i> Retry
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="module-status status-unlocked">
                                <i class="fas fa-unlock"></i> Unlocked
                            </div>
                            <div class="module-info">
                                Ready to start? Complete all lessons and pass the quiz to unlock the next module.
                            </div>
                            <div class="module-actions">
                                <a href="../trainee/modules/<?= strtolower(str_replace(' ', '_', $module['module_title'])) ?>.php?module_id=<?= $module['module_id'] ?>&enrollment_id=<?= $enrollment_id ?>&cert_module_id=<?= $module['id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-play"></i> Start Module
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Completion Badge -->
            <?php if ($enrollment['status'] === 'completed'): ?>
            <div class="completion-badge">
                <i class="fas fa-trophy"></i>
                <h2>Certificate Completed!</h2>
                <p>Congratulations! You've successfully completed all modules.</p>
                <a href="certificates.php" class="btn btn-primary" style="margin-top: 1rem; width: auto;">
                    View Certificate
                </a>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
