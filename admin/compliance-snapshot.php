<?php
// admin/compliance-snapshot.php - One-page compliance snapshot

require_once '../config/database.php';
require_once '../includes/functions.php';
require_admin();

$pdo = getDBConnection();

$module_total = (int)$pdo->query("SELECT COUNT(*) FROM training_modules WHERE is_active = 1")->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'trainee' AND is_active = 1");
$total_trainees = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM campaigns WHERE status = 'active'");
$active_campaigns = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT MAX(completed_at) FROM training_sessions WHERE completed_at IS NOT NULL");
$last_training = $stmt->fetchColumn();
$last_training_label = $last_training ? date('M j, Y g:i A', strtotime($last_training)) : 'No activity';

$stmt = $pdo->query("
    SELECT
        u.id,
        u.username,
        u.full_name,
        u.email,
        d.name AS department,
        COUNT(DISTINCT CASE WHEN ts.completed_at IS NOT NULL THEN ts.module_id END) AS completed_modules,
        AVG(CASE WHEN ts.completed_at IS NOT NULL THEN ts.final_score END) AS avg_score,
        MAX(ts.completed_at) AS last_completed
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN training_sessions ts ON ts.user_id = u.id
    WHERE u.role = 'trainee' AND u.is_active = 1
    GROUP BY u.id, u.username, u.full_name, u.email, d.name
        ORDER BY d.name, u.username
    ");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_completion = 0;
$completion_count = 0;
$total_avg_score = 0;
$score_count = 0;

foreach ($users as $user) {
    $completion = $module_total > 0 ? round(((int)$user['completed_modules'] / $module_total) * 100) : 0;
    $total_completion += $completion;
    $completion_count++;

    if ($user['avg_score'] !== null) {
        $total_avg_score += (float)$user['avg_score'];
        $score_count++;
    }
}

$avg_completion = $completion_count > 0 ? round($total_completion / $completion_count) : 0;
$avg_score = $score_count > 0 ? round($total_avg_score / $score_count) : 0;

$current_page = 'compliance-snapshot';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Snapshot – CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --brand: #FF8C42;
            --surface: #ffffff;
            --ink: #0f172a;
            --muted: #6b7280;
            --line: rgba(15, 23, 42, 0.12);
            --shadow-sm: 0 8px 18px rgba(15, 23, 42, 0.08);
            --shadow-md: 0 18px 40px rgba(15, 23, 42, 0.12);
            --radius-lg: 20px;
            --radius-md: 14px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Manrope', 'Segoe UI', sans-serif;
            background: radial-gradient(900px 520px at 12% -10%, #1f2937 0%, transparent 60%),
                linear-gradient(140deg, #0b1120 0%, #111827 100%);
            color: #e2e8f0;
        }

        .main-content {
            margin-left: 260px;
            padding: 32px 36px 80px;
            min-height: 100vh;
        }

        .page-header {
            background: rgba(17, 24, 39, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 18px;
            padding: 24px 28px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #f8fafc;
        }

        .page-subtitle {
            color: #94a3b8;
            margin-top: 6px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .summary-card {
            background: var(--surface);
            color: var(--ink);
            border-radius: var(--radius-md);
            padding: 18px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--line);
        }

        .summary-card span {
            display: block;
            color: var(--muted);
            font-size: 13px;
        }

        .summary-card strong {
            font-size: 24px;
            margin-top: 6px;
            display: block;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .btn {
            border: none;
            border-radius: 999px;
            padding: 10px 16px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn.primary { background: var(--brand); color: #111827; }
        .btn.ghost { background: rgba(255, 255, 255, 0.12); color: #f8fafc; }

        .table-card {
            background: var(--surface);
            color: var(--ink);
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--line);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            font-size: 14px;
        }

        th {
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: rgba(255, 140, 66, 0.15);
            color: #9a3412;
        }

        @media (max-width: 992px) {
            .main-content { margin-left: 0; margin-bottom: 72px; padding: 24px 20px 80px; }
        }

        @media print {
            body { background: #ffffff; color: #111827; }
            .main-content { margin: 0; padding: 0; }
            .page-header, .actions { display: none; }
            .table-card { box-shadow: none; border: none; }
        }
    </style>
</head>
<body>

<?php include 'admin-sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div class="page-title">Compliance Snapshot</div>
        <div class="page-subtitle">One-page overview for audits and internal reporting.</div>
    </div>

    <div class="summary-grid">
        <div class="summary-card">
            <span>Total trainees</span>
            <strong><?= $total_trainees ?></strong>
        </div>
        <div class="summary-card">
            <span>Average completion</span>
            <strong><?= $avg_completion ?>%</strong>
        </div>
        <div class="summary-card">
            <span>Average score</span>
            <strong><?= $avg_score ?>%</strong>
        </div>
        <div class="summary-card">
            <span>Active campaigns</span>
            <strong><?= $active_campaigns ?></strong>
        </div>
        <div class="summary-card">
            <span>Last training activity</span>
            <strong><?= htmlspecialchars($last_training_label) ?></strong>
        </div>
    </div>

    <div class="actions">
        <a class="btn primary" href="export-report.php"><i class="fas fa-file-export"></i> Export CSV</a>
        <button class="btn ghost" onclick="window.print()"><i class="fas fa-print"></i> Print snapshot</button>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Department</th>
                    <th>Completion</th>
                    <th>Avg Score</th>
                    <th>Last Completed</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="5">No trainee data available.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <?php
                            $completion = $module_total > 0 ? round(((int)$user['completed_modules'] / $module_total) * 100) : 0;
                            $last_completed = $user['last_completed'] ? date('M j, Y', strtotime($user['last_completed'])) : 'Never';
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></strong><br>
                                <small><?= htmlspecialchars($user['email'] ?: 'no-email') ?></small>
                            </td>
                            <td><?= htmlspecialchars($user['department'] ?: 'No Department') ?></td>
                            <td><span class="badge"><?= $completion ?>%</span></td>
                            <td><?= $user['avg_score'] !== null ? round((float)$user['avg_score']) . '%' : 'n/a' ?></td>
                            <td><?= htmlspecialchars($last_completed) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>
