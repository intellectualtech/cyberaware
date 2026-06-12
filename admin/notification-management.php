<?php
/**
 * Admin Notification Management
 * View and manage notification history and queue
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

$pdo = getDBConnection();
$tab = $_GET['tab'] ?? 'queue';
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Get queue statistics
$stmt = $pdo->prepare("
    SELECT 
        status,
        COUNT(*) as count
    FROM notification_queue
    GROUP BY status
");
$stmt->execute();
$queue_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get history statistics
$stmt = $pdo->prepare("
    SELECT 
        type,
        status,
        COUNT(*) as count
    FROM notification_history
    GROUP BY type, status
");
$stmt->execute();
$history_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get queue items
if ($tab === 'queue') {
    $stmt = $pdo->prepare("
        SELECT nq.*, u.full_name, u.email, nt.subject
        FROM notification_queue nq
        JOIN users u ON nq.user_id = u.id
        JOIN notification_templates nt ON nq.template_id = nt.id
        ORDER BY nq.priority DESC, nq.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notification_queue");
    $stmt->execute();
    $total = $stmt->fetch()['total'];
} else {
    // Get history items
    $stmt = $pdo->prepare("
        SELECT nh.*, u.full_name, u.email
        FROM notification_history nh
        JOIN users u ON nh.user_id = u.id
        ORDER BY nh.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notification_history");
    $stmt->execute();
    $total = $stmt->fetch()['total'];
}

$total_pages = ceil($total / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Management – CyberAware Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #EE8E46 0%, #E67A2E 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .header {
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 28px;
            color: #1A1A2E;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
        }

        .stat-card h3 {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 8px;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: 700;
            color: #FF8C42;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
        }

        .tab-btn {
            padding: 12px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-size: 14px;
            font-weight: 600;
            color: #888;
            cursor: pointer;
            transition: all 0.3s;
        }

        .tab-btn.active {
            color: #FF8C42;
            border-bottom-color: #FF8C42;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
            margin-bottom: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: #f9f9f9;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #eee;
            font-size: 13px;
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-sent {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-high {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-normal {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-low {
            background: #e0e7ff;
            color: #3730a3;
        }

        .pagination {
            display: flex;
            gap: 5px;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination a, .pagination span {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            font-size: 13px;
        }

        .pagination a:hover {
            background: #f5f5f5;
        }

        .pagination .active {
            background: #FF8C42;
            color: white;
            border-color: #FF8C42;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #888;
        }

        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-bell"></i> Notification Management</h1>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <?php 
            $pending_count = 0;
            $sent_count = 0;
            $failed_count = 0;
            
            foreach ($queue_stats as $stat) {
                if ($stat['status'] === 'pending') $pending_count = $stat['count'];
                elseif ($stat['status'] === 'completed') $sent_count = $stat['count'];
                elseif ($stat['status'] === 'failed') $failed_count = $stat['count'];
            }
            ?>
            <div class="stat-card">
                <h3>Pending</h3>
                <div class="value"><?php echo $pending_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Sent</h3>
                <div class="value"><?php echo $sent_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Failed</h3>
                <div class="value"><?php echo $failed_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total History</h3>
                <div class="value"><?php echo $total; ?></div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn <?php echo $tab === 'queue' ? 'active' : ''; ?>" onclick="location.href='?tab=queue'">
                <i class="fas fa-hourglass-half"></i> Queue
            </button>
            <button class="tab-btn <?php echo $tab === 'history' ? 'active' : ''; ?>" onclick="location.href='?tab=history'">
                <i class="fas fa-history"></i> History
            </button>
        </div>

        <!-- Content -->
        <div class="card">
            <?php if (empty($items)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No <?php echo $tab; ?> items</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <?php if ($tab === 'queue'): ?>
                                <th>Priority</th>
                                <th>Attempts</th>
                            <?php else: ?>
                                <th>Type</th>
                            <?php endif; ?>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['email']); ?></td>
                            <td><?php echo htmlspecialchars(substr($item['subject'], 0, 40)); ?></td>
                            <?php if ($tab === 'queue'): ?>
                                <td>
                                    <span class="badge badge-<?php echo $item['priority']; ?>">
                                        <?php echo ucfirst($item['priority']); ?>
                                    </span>
                                </td>
                                <td><?php echo $item['attempts']; ?>/<?php echo $item['max_attempts']; ?></td>
                            <?php else: ?>
                                <td><?php echo ucfirst(str_replace('_', ' ', $item['type'])); ?></td>
                            <?php endif; ?>
                            <td>
                                <span class="badge badge-<?php echo $item['status']; ?>">
                                    <?php echo ucfirst($item['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($tab === 'queue' ? $item['created_at'] : $item['sent_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?tab=<?php echo $tab; ?>&page=1">First</a>
                        <a href="?tab=<?php echo $tab; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?tab=<?php echo $tab; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?tab=<?php echo $tab; ?>&page=<?php echo $page + 1; ?>">Next</a>
                        <a href="?tab=<?php echo $tab; ?>&page=<?php echo $total_pages; ?>">Last</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
