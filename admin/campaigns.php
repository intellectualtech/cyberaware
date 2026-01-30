<?php
// admin/campaigns.php - Campaign Management
// Updated: January 2026 - Working delete with custom modal

require_once '../config/database.php';
require_once '../includes/functions.php';
require_admin();

$pdo = getDBConnection();

// Handle POST actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Status change ───────────────────────────────────────
    if ($action === 'update_status') {
        $campaign_id = (int)($_POST['campaign_id'] ?? 0);
        $new_status  = $_POST['status'] ?? 'draft';

        if ($campaign_id > 0 && in_array($new_status, ['draft','active','completed','archived'])) {
            $stmt = $pdo->prepare("UPDATE campaigns SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $campaign_id]);
            $message = "Campaign status updated successfully";
            $message_type = "success";
        }
    }

    // ── Delete campaign ─────────────────────────────────────
    elseif ($action === 'delete') {
        $campaign_id = (int)($_POST['campaign_id'] ?? 0);
        if ($campaign_id > 0) {
            try {
                $pdo->beginTransaction();

                // Delete related department assignments
                $pdo->prepare("DELETE FROM campaign_departments WHERE campaign_id = ?")
                    ->execute([$campaign_id]);

                // Delete the campaign itself
                $pdo->prepare("DELETE FROM campaigns WHERE id = ?")
                    ->execute([$campaign_id]);

                $pdo->commit();

                $message = "Campaign deleted successfully";
                $message_type = "success";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Cannot delete this campaign.<br>Reason: " . htmlspecialchars($e->getMessage());
                $message_type = "danger";
            }
        }
    }
}

// Success message from create/edit pages
if (isset($_GET['success'])) {
    $message = htmlspecialchars($_GET['msg'] ?? "Operation completed successfully");
    $message_type = "success";
}

// Get all campaigns with target info
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        u.username AS creator_name,
        GROUP_CONCAT(d.name ORDER BY d.name SEPARATOR ', ') AS target_departments
    FROM campaigns c
    JOIN users u ON c.created_by = u.id
    LEFT JOIN campaign_departments cd ON cd.campaign_id = c.id
    LEFT JOIN departments d ON d.id = cd.department_id
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaigns • CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF8C42;
            --primary-dark: #E67A2E;
            --success: #10b981;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --sidebar-width: 260px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--gray-100);
            color: var(--gray-700);
        }
        .main-content { margin-bottom: var(--sidebar-height); min-height: 100vh; }
        .header {
            background: white;
            border-bottom: 1px solid var(--gray-200);
            padding: 1.25rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .page-title { font-size: 1.75rem; font-weight: 600; margin: 0; }
        .container { padding: 1.5rem 2rem; max-width: 1400px; margin: 0 auto; }
        .message {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .message-success { background: #ecfdf5; color: #065f46; border-left: 4px solid var(--success); }
        .message-danger  { background: #fef2f2; color: #991b1b; border-left: 4px solid var(--danger); }

        .card { background: white; border-radius: 12px; border: 1px solid var(--gray-200); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
        .card-header {
            padding: 1.25rem 1.75rem;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-primary    { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-edit       { background: #3b82f6; color: white; padding: 0.5rem 1rem; font-size: 0.9rem; }
        .btn-edit:hover { background: #2563eb; }
        .btn-delete     { background: var(--danger); color: white; padding: 0.5rem 1rem; font-size: 0.9rem; }
        .btn-delete:hover { background: #dc2626; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.25rem; text-align: left; }
        th { background: var(--gray-50); font-weight: 600; font-size: 0.85rem; color: #475569; text-transform: uppercase; letter-spacing: 0.4px; }
        tr:hover { background: #f8fafc; }

        .status-badge {
            padding: 0.35em 0.85em;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-draft     { background:#e5e7eb; color:#4b5563; }
        .status-active    { background:#d1fae5; color:#065f46; }
        .status-completed { background:#dbeafe; color:#1d4ed8; }
        .status-archived  { background:#fef3c7; color:#92400e; }

        /* ── Custom Delete Modal ────────────────────────────────────── */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.65);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 440px;
            padding: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);
        }
        .modal-header {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .modal-body {
            color: #4b5563;
            margin-bottom: 1.75rem;
            line-height: 1.5;
        }
        .modal-body strong { color: #1f2937; }
        .modal-footer {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        .btn-modal {
            padding: 0.7rem 1.4rem;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            border: none;
        }
        .btn-cancel { background: #e5e7eb; color: #374151; }
        .btn-delete-confirm { background: var(--danger); color: white; }
        .btn-delete-confirm:hover { background: #dc2626; }
    </style>
</head>
<body>

<?php include 'admin-sidebar.php'; ?>

<main class="main-content">
    <header class="header">
        <h1 class="page-title">Campaign Management</h1>
    </header>

    <div class="container">

        <?php if ($message): ?>
        <div class="message message-<?= $message_type ?>">
            <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= $message ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 style="margin:0; font-size:1.35rem;">
                    <i class="fas fa-list-ul" style="color:var(--primary); margin-right:0.5rem;"></i>
                    All Campaigns
                </h3>
                <a href="campaign-create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Campaign
                </a>
            </div>

            <?php if (empty($campaigns)): ?>
                <div style="padding:5rem 1rem; text-align:center; color:#6b7280;">
                    <i class="far fa-folder-open" style="font-size:3.8rem; color:#d1d5db; margin-bottom:1.5rem;"></i>
                    <p style="margin:1.25rem 0 0.75rem; font-size:1.15rem;">No campaigns have been created yet</p>
                    <p><a href="campaign-create.php" style="color:var(--primary)">Create your first campaign →</a></p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th>Target</th>
                                <th>Date Range</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th style="width:160px; text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($campaigns as $c): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600;"><?= htmlspecialchars($c['name']) ?></div>
                                    <?php if ($c['description']): ?>
                                    <div style="color:#6b7280; font-size:0.9rem; margin-top:0.25rem;">
                                        <?= htmlspecialchars(substr($c['description'],0,85)) . (strlen($c['description'])>85 ? '...' : '') ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($c['target_all']): ?>
                                        <strong style="color:#059669;">All Employees</strong>
                                    <?php elseif ($c['target_departments']): ?>
                                        <?= htmlspecialchars($c['target_departments']) ?>
                                    <?php else: ?>
                                        <em style="color:#9ca3af;">Specific users only</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    if ($c['start_date'] && $c['end_date']) {
                                        echo date('M j, Y', strtotime($c['start_date'])) . ' – ' .
                                             date('M j, Y', strtotime($c['end_date']));
                                    } elseif ($c['start_date']) {
                                        echo 'From ' . date('M j, Y', strtotime($c['start_date']));
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $c['status'] ?>">
                                        <?= ucfirst($c['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($c['creator_name']) ?></td>
                                <td style="text-align:center; white-space:nowrap;">
                                    <a href="campaign-edit.php?id=<?= $c['id'] ?>" class="btn btn-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <button type="button" class="btn btn-delete"
                                            onclick="showDeleteModal(<?= $c['id'] ?>, <?= htmlspecialchars(json_encode($c['name']), ENT_QUOTES) ?>)"
                                            title="Delete Campaign">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                Delete Campaign
            </div>
            <div class="modal-body">
                Are you sure you want to permanently delete<br>
                <strong id="campaignName" style="color:#1f2937;"></strong>?<br><br>
                <span style="color:var(--danger); font-weight:500;">This action cannot be undone.</span>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancel" onclick="closeDeleteModal()">Cancel</button>

                <form id="deleteForm" method="POST" action="">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="campaign_id" id="deleteCampaignId">
                    <button type="submit" class="btn-modal btn-delete-confirm">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
// ── Delete Modal Controls ────────────────────────────────
function showDeleteModal(id, name) {
    document.getElementById('campaignName').textContent = name;
    document.getElementById('deleteCampaignId').value = id;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>

</body>
</html>