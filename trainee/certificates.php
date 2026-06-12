<?php
/**
 * Certificate Enrollment Page
 * Browse and enroll in certificate tracks
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

$message = '';
$message_type = '';

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enroll') {
    $track_id = (int)($_POST['track_id'] ?? 0);
    try {
        $cert_manager->enrollTrainee($user_id, $track_id);
        $message = 'Successfully enrolled! Module 1 is now unlocked.';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Get all tracks
$all_tracks = $cert_manager->getAllTracks();

// Get user's enrollments
$enrollments = $cert_manager->getTraineeEnrollments($user_id);
$enrolled_track_ids = array_column($enrollments, 'track_id');

// Separate enrolled and available tracks
$enrolled_tracks = array_filter($all_tracks, fn($t) => in_array($t['id'], $enrolled_track_ids));
$available_tracks = array_filter($all_tracks, fn($t) => !in_array($t['id'], $enrolled_track_ids));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Tracks – CyberAware</title>
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

        .page-header {
            background: rgba(17, 24, 39, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 18px;
            padding: 24px 28px;
            margin-bottom: 24px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #f8fafc;
            margin: 0;
        }

        .page-subtitle {
            color: #94a3b8;
            margin-top: 6px;
            font-size: 14px;
        }

        .container { max-width: 1200px; margin: 0 auto; }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .message-success { background: #d1fae5; color: #065f46; }
        .message-danger { background: #fee2e2; color: #991b1b; }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i { color: var(--primary); }

        .tracks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .track-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .track-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }

        .track-header {
            padding: 2rem;
            text-align: center;
            position: relative;
        }

        .track-icon {
            font-size: 48px;
            margin-bottom: 1rem;
        }

        .track-name {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .track-description {
            font-size: 13px;
            color: #666;
            margin-bottom: 1rem;
        }

        .track-meta {
            display: flex;
            justify-content: center;
            gap: 1rem;
            font-size: 12px;
            color: #999;
            margin-bottom: 1rem;
        }

        .track-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .track-body {
            padding: 0 2rem 2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .module-list {
            margin-bottom: 1.5rem;
            flex: 1;
        }

        .module-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0;
            font-size: 13px;
            color: #666;
        }

        .module-number {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .track-actions {
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
            background: #e5e7eb;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .badge-enrolled { background: #d1fae5; color: #065f46; }
        .badge-beginner { background: #dbeafe; color: #1e40af; }
        .badge-intermediate { background: #fef3c7; color: #92400e; }
        .badge-advanced { background: #fee2e2; color: #991b1b; }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .progress-text {
            font-size: 12px;
            color: #666;
            text-align: right;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .tracks-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <div class="page-title"><i class="fas fa-certificate"></i> Certificate Tracks</div>
                <div class="page-subtitle">Enroll in cybersecurity certificate programs and progress through structured learning paths</div>
            </div>

            <?php if ($message): ?>
            <div class="message message-<?= $message_type ?>">
                <?= $message ?>
            </div>
            <?php endif; ?>

            <!-- My Learning Paths -->
            <?php if (!empty($enrolled_tracks)): ?>
            <div>
                <h2 class="section-title"><i class="fas fa-graduation-cap"></i> My Learning Paths</h2>
                <div class="tracks-grid">
                    <?php foreach ($enrolled_tracks as $track): ?>
                        <?php
                        $enrollment = array_values(array_filter($enrollments, fn($e) => $e['track_id'] === $track['id']))[0];
                        ?>
                    <div class="track-card">
                        <div class="track-header" style="background: linear-gradient(135deg, <?= $track['color'] ?>20 0%, <?= $track['color'] ?>05 100%);">
                            <div class="badge badge-enrolled">Enrolled</div>
                            <div class="track-icon" style="color: <?= $track['color'] ?>;">
                                <i class="fas <?= $track['icon'] ?>"></i>
                            </div>
                            <div class="track-name"><?= htmlspecialchars($track['name']) ?></div>
                            <div class="track-description"><?= htmlspecialchars(substr($track['description'], 0, 60)) ?>...</div>
                        </div>
                        <div class="track-body">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $enrollment['progress_percentage'] ?>%"></div>
                            </div>
                            <div class="progress-text"><?= $enrollment['progress_percentage'] ?>% Complete</div>
                            <div style="margin-top: 1rem;">
                                <a href="learning-path.php?enrollment_id=<?= $enrollment['id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-play"></i> Continue Learning
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Available Tracks -->
            <?php if (!empty($available_tracks)): ?>
            <div>
                <h2 class="section-title"><i class="fas fa-lock-open"></i> Available Certificates</h2>
                <div class="tracks-grid">
                    <?php foreach ($available_tracks as $track): ?>
                    <div class="track-card">
                        <div class="track-header" style="background: linear-gradient(135deg, <?= $track['color'] ?>20 0%, <?= $track['color'] ?>05 100%);">
                            <div class="badge badge-<?= $track['difficulty_level'] ?>"><?= ucfirst($track['difficulty_level']) ?></div>
                            <div class="track-icon" style="color: <?= $track['color'] ?>;">
                                <i class="fas <?= $track['icon'] ?>"></i>
                            </div>
                            <div class="track-name"><?= htmlspecialchars($track['name']) ?></div>
                            <div class="track-description"><?= htmlspecialchars($track['description']) ?></div>
                        </div>
                        <div class="track-body">
                            <div class="module-list">
                                <strong style="font-size: 12px; color: #666;">Modules:</strong>
                                <?php
                                $track_details = $cert_manager->getTrackWithModules($track['id']);
                                foreach ($track_details['modules'] as $idx => $module):
                                ?>
                                <div class="module-item">
                                    <div class="module-number"><?= $idx + 1 ?></div>
                                    <span><?= htmlspecialchars($module['title']) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="track-meta">
                                <span><i class="fas fa-clock"></i> <?= $track['estimated_hours'] ?> hours</span>
                                <span><i class="fas fa-book"></i> <?= count($track_details['modules']) ?> modules</span>
                            </div>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="enroll">
                                <input type="hidden" name="track_id" value="<?= $track['id'] ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check"></i> Enroll Now
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($all_tracks)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No certificate tracks available yet</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
