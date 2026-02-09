<?php
// admin/risk-heatmap.php - Department/Role risk heatmap

require_once '../config/database.php';
require_once '../includes/functions.php';
require_admin();

$pdo = getDBConnection();

$roles = ['trainee', 'manager', 'admin', 'compliance'];

$stmt = $pdo->query("
    SELECT
        COALESCE(d.name, 'No Department') AS department,
        u.role,
        COUNT(DISTINCT u.id) AS user_count,
        SUM(CASE WHEN ts.completed_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_sessions,
        SUM(CASE WHEN ts.completed_at IS NOT NULL AND ts.final_score < 60 THEN 1 ELSE 0 END) AS failed_sessions
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN training_sessions ts ON ts.user_id = u.id
    WHERE u.is_active = 1
    GROUP BY department, u.role
    ORDER BY department
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$departments = [];
$matrix = [];
$top_risks = [];

foreach ($rows as $row) {
    $dept = $row['department'];
    $role = $row['role'];
    $departments[$dept] = true;

    $completed = (int)$row['completed_sessions'];
    $failed = (int)$row['failed_sessions'];
    $rate = $completed > 0 ? round(($failed / $completed) * 100) : null;

    $matrix[$dept][$role] = [
        'users' => (int)$row['user_count'],
        'completed' => $completed,
        'failed' => $failed,
        'rate' => $rate
    ];

    if ($rate !== null) {
        $top_risks[] = [
            'department' => $dept,
            'role' => $role,
            'rate' => $rate,
            'completed' => $completed
        ];
    }
}

$departments = array_keys($departments);

usort($top_risks, function ($a, $b) {
    return $b['rate'] <=> $a['rate'];
});
$top_risks = array_slice($top_risks, 0, 8);

$current_page = 'risk-heatmap';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Risk Heatmap – CyberAware</title>
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

        .card {
            background: var(--surface);
            color: var(--ink);
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--line);
            margin-bottom: 24px;
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

        .heat-cell {
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .heat-low { background: #ecfdf3; color: #0f766e; }
        .heat-medium { background: #fff7ed; color: #9a3412; }
        .heat-high { background: #fef2f2; color: #b91c1c; }
        .heat-critical { background: #fee2e2; color: #991b1b; }
        .heat-empty { background: #f8fafc; color: #94a3b8; }

        .heat-cell small { font-weight: 500; color: inherit; }

        .risk-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
        }

        .risk-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px;
            border: 1px solid rgba(15, 23, 42, 0.08);
        }

        .risk-item strong { display: block; margin-bottom: 6px; }

        @media (max-width: 992px) {
            .main-content { margin-left: 0; margin-bottom: 72px; padding: 24px 20px 80px; }
        }
    </style>
</head>
<body>

<?php include 'admin-sidebar.php'; ?>

<main class="main-content">
    <div class="page-header">
        <div class="page-title">Risk Heatmap</div>
        <div class="page-subtitle">Fail-rate matrix by department and role.</div>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Department</th>
                    <?php foreach ($roles as $role): ?>
                        <th><?= htmlspecialchars(ucfirst($role)) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($departments)): ?>
                    <tr><td colspan="<?= 1 + count($roles) ?>">No data available.</td></tr>
                <?php else: ?>
                    <?php foreach ($departments as $dept): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($dept) ?></strong></td>
                            <?php foreach ($roles as $role): ?>
                                <?php
                                    $cell = $matrix[$dept][$role] ?? null;
                                    $rate = $cell['rate'] ?? null;
                                    $completed = $cell['completed'] ?? 0;

                                    if ($rate === null) {
                                        $class = 'heat-empty';
                                        $label = 'n/a';
                                    } elseif ($rate < 20) {
                                        $class = 'heat-low';
                                        $label = $rate . '%';
                                    } elseif ($rate < 40) {
                                        $class = 'heat-medium';
                                        $label = $rate . '%';
                                    } elseif ($rate < 60) {
                                        $class = 'heat-high';
                                        $label = $rate . '%';
                                    } else {
                                        $class = 'heat-critical';
                                        $label = $rate . '%';
                                    }
                                ?>
                                <td>
                                    <div class="heat-cell <?= $class ?>">
                                        <span><?= $label ?></span>
                                        <small><?= $completed ?> sessions</small>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3 style="margin-bottom: 12px;">Highest risk combinations</h3>
        <?php if (empty($top_risks)): ?>
            <p>No completed sessions to score yet.</p>
        <?php else: ?>
            <div class="risk-list">
                <?php foreach ($top_risks as $risk): ?>
                    <div class="risk-item">
                        <strong><?= htmlspecialchars($risk['department']) ?> • <?= htmlspecialchars(ucfirst($risk['role'])) ?></strong>
                        <div>Fail rate: <?= $risk['rate'] ?>%</div>
                        <small><?= $risk['completed'] ?> completed sessions</small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
