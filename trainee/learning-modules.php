<?php
/**
 * Learning Modules Page
 * Shows registered modules in order and allows access to each module
 */

require_once '../config/database.php';
require_once '../includes/functions.php';
require_login();

if ($_SESSION['role'] !== 'trainee') {
    header('Location: ../pages/login.php');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get registered modules with progress
$stmt = $pdo->prepare("
    SELECT 
        umr.id as registration_id,
        umr.module_id,
        umr.registration_order,
        umr.status,
        umr.started_at,
        umr.completed_at,
        tm.title,
        tm.code,
        tm.description,
        ump.total_attempts,
        ump.best_score,
        ump.average_score,
        ump.passed,
        ump.passed_date
    FROM user_module_registrations umr
    JOIN training_modules tm ON umr.module_id = tm.id
    LEFT JOIN user_module_progress ump ON umr.user_id = ump.user_id AND umr.module_id = ump.module_id
    WHERE umr.user_id = ?
    ORDER BY umr.registration_order
");
$stmt->execute([$user_id]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user info
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate progress
$total_modules = count($modules);
$completed_modules = 0;
foreach ($modules as $mod) {
    if ($mod['passed'] == 1) {
        $completed_modules++;
    }
}
$progress_percentage = $total_modules > 0 ? round(($completed_modules / $total_modules) * 100) : 0;

// No need for file mapping - use training_module.php router instead

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Learning Path – CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --orange: #FF8C42;
            --dark: #1A1A2E;
            --grey: #F5F5F5;
            --grey2: #E8E8E8;
            --green: #10B981;
            --red: #EF4444;
            --yellow: #F59E0B;
        }
        body {
            font-family: 'Manrope', sans-serif;
            background: linear-gradient(135deg, #FFF4EC 0%, #fff 100%);
            min-height: 100vh;
            padding: 20px;
            padding-bottom: 100px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding-bottom: 100px;
        }
        }
        .header {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .header h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 10px;
        }
        .header p {
            color: #888;
            font-size: 15px;
        }
        .progress-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .progress-bar {
            width: 100%;
            height: 10px;
            background: var(--grey2);
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--orange), #ffb347);
            transition: width 0.3s ease;
        }
        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #888;
            font-weight: 600;
        }
        .modules-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .module-item {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            display: grid;
            grid-template-columns: 60px 1fr auto;
            gap: 20px;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .module-item:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }
        .module-item.locked {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .module-item.locked:hover {
            transform: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .module-number {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--orange), #ffb347);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 24px;
            flex-shrink: 0;
        }
        .module-item.locked .module-number {
            background: var(--grey2);
            color: #999;
        }
        .module-info h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 6px;
        }
        .module-info p {
            font-size: 13px;
            color: #888;
            margin-bottom: 8px;
        }
        .module-meta {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #999;
        }
        .module-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .module-status {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        .status-pending {
            background: #dbeafe;
            color: #1e40af;
        }
        .status-in-progress {
            background: #fef3c7;
            color: #92400e;
        }
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        .status-locked {
            background: var(--grey2);
            color: #666;
        }
        .module-score {
            font-size: 13px;
            font-weight: 700;
            color: var(--orange);
        }
        .btn-start {
            padding: 10px 20px;
            background: var(--orange);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-start:hover {
            background: #E67A2E;
            transform: translateY(-2px);
        }
        .btn-start:disabled {
            background: var(--grey2);
            color: #999;
            cursor: not-allowed;
            transform: none;
        }
        .empty-state {
            background: white;
            border-radius: 16px;
            padding: 60px 20px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }
        .empty-state p {
            color: #888;
            margin-bottom: 20px;
        }
        .btn-register {
            padding: 12px 24px;
            background: var(--orange);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            background: #E67A2E;
            transform: translateY(-2px);
        }
        @media (max-width: 768px) {
            .module-item {
                grid-template-columns: 1fr;
            }
            .module-status {
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

<?php include 'trainee-sidebar.php'; ?>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>Your Learning Path (in order)</h1>
        <p>Complete each module in sequence to progress through your training.</p>
    </div>

    <?php if (!empty($modules)): ?>
        <!-- Progress -->
        <div class="progress-section">
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $progress_percentage ?>%"></div>
            </div>
            <div class="progress-text">
                <span>Overall Progress</span>
                <span><?= $completed_modules ?>/<?= $total_modules ?> modules completed (<?= $progress_percentage ?>%)</span>
            </div>
        </div>

        <!-- Modules List -->
        <div class="modules-list">
            <?php foreach ($modules as $idx => $module): ?>
                <?php
                $is_first = ($idx === 0);
                $is_locked = ($idx > 0 && $modules[$idx - 1]['passed'] != 1);
                $is_completed = ($module['passed'] == 1);
                $is_in_progress = ($module['status'] === 'in_progress');
                $has_started = ($module['total_attempts'] > 0); // Check if module has been attempted before
                
                // Use training_module.php router with module ID
                $module_url = "modules/training_module.php?module=" . $module['module_id'];
                ?>
                <div class="module-item <?= $is_locked ? 'locked' : '' ?>">
                    <div class="module-number"><?= $idx + 1 ?></div>
                    <div class="module-info">
                        <h3><?= htmlspecialchars($module['title']) ?></h3>
                        <p><?= htmlspecialchars(substr($module['description'], 0, 80)) ?>...</p>
                        <div class="module-meta">
                            <?php if ($module['total_attempts']): ?>
                                <span><i class="fas fa-redo"></i> <?= $module['total_attempts'] ?> attempt<?= $module['total_attempts'] != 1 ? 's' : '' ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="module-status">
                        <?php if ($is_locked): ?>
                            <span class="status-badge status-locked">
                                <i class="fas fa-lock"></i> Locked
                            </span>
                        <?php elseif ($is_completed): ?>
                            <span class="status-badge status-completed">
                                <i class="fas fa-check-circle"></i> Completed
                            </span>
                            <div class="module-score"><?= $module['best_score'] ?>%</div>
                        <?php elseif ($has_started): ?>
                            <span class="status-badge status-in-progress">
                                <i class="fas fa-spinner"></i> In Progress
                            </span>
                            <a href="<?= $module_url ?>" class="btn-start">
                                <i class="fas fa-play"></i> Continue
                            </a>
                        <?php else: ?>
                            <span class="status-badge status-pending">
                                <i class="fas fa-circle"></i> Ready
                            </span>
                            <a href="<?= $module_url ?>" class="btn-start">
                                <i class="fas fa-play"></i> Start
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>You haven't registered for any modules yet.</p>
            <a href="register-modules.php" class="btn-register">
                <i class="fas fa-plus"></i> Register for Modules
            </a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
