<?php
// admin/campaign-edit.php
// Edit existing security awareness campaign

require_once '../config/database.php';
require_once '../includes/functions.php';
require_admin(); // Only admins/managers

$pdo = getDBConnection();

$campaign_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($campaign_id <= 0) {
    header("Location: campaigns.php?error=invalid");
    exit;
}

// Fetch campaign data
$stmt = $pdo->prepare("
    SELECT * FROM campaigns 
    WHERE id = ?
");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campaign) {
    header("Location: campaigns.php?error=notfound");
    exit;
}

// Fetch all departments
$dept_stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
$all_departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch currently selected departments for this campaign
$selected_depts_stmt = $pdo->prepare("
    SELECT department_id 
    FROM campaign_departments 
    WHERE campaign_id = ?
");
$selected_depts_stmt->execute([$campaign_id]);
$selected_department_ids = $selected_depts_stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_date  = $_POST['start_date'] ?: null;
    $end_date    = $_POST['end_date'] ?: null;
    $target_all  = isset($_POST['target_all']) ? 1 : 0;
    $status      = $_POST['status'] ?? $campaign['status'];

    $errors = [];

    if (empty($name)) {
        $errors[] = "Campaign name is required.";
    }
    if ($start_date && $end_date && strtotime($start_date) > strtotime($end_date)) {
        $errors[] = "End date cannot be before start date.";
    }

    if (!empty($errors)) {
        $message = implode("<br>", $errors);
        $message_type = 'danger';
    } else {
        try {
            $pdo->beginTransaction();

            // Update main campaign record
            $update_stmt = $pdo->prepare("
                UPDATE campaigns 
                SET name = ?, description = ?, start_date = ?, end_date = ?, 
                    target_all = ?, status = ?, 
                    updated_at = NOW()
                WHERE id = ?
            ");
            $update_stmt->execute([
                $name, $description, $start_date, $end_date,
                $target_all, $status, $campaign_id
            ]);

            // Handle department assignments (only if not targeting all)
            $pdo->prepare("DELETE FROM campaign_departments WHERE campaign_id = ?")
                ->execute([$campaign_id]);

            if (!$target_all && !empty($_POST['departments'])) {
                $insert_dept = $pdo->prepare("
                    INSERT INTO campaign_departments (campaign_id, department_id) 
                    VALUES (?, ?)
                ");
                foreach ($_POST['departments'] as $dept_id) {
                    $insert_dept->execute([$campaign_id, (int)$dept_id]);
                }
            }

            $pdo->commit();

            header("Location: campaigns.php?success=1&msg=Campaign+updated+successfully!");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error updating campaign. Please try again.";
            $message_type = 'danger';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Campaign – <?= htmlspecialchars($campaign['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF8C42;
            --primary-dark: #E67A2E;
            --success: #00A65A;
            --danger: #DD4B39;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-700: #334155;
            --white: #FFFFFF;
            --sidebar-width: 260px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--gray-100);
            color: #1e293b;
        }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .header {
            background: white;
            border-bottom: 1px solid var(--gray-200);
            padding: 1.5rem 2rem;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .container { max-width: 900px; margin: 2rem auto; padding: 0 1.5rem; }
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .message {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .message-success { background: #ecfdf5; color: #065f46; }
        .message-danger  { background: #fef2f2; color: #991b1b; }
        h1 { font-size: 1.8rem; margin: 0 0 0.5rem 0; }
        .form-group { margin-bottom: 1.5rem; }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--gray-700);
        }
        input[type="text"],
        input[type="date"],
        textarea,
        select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255,140,66,0.15);
        }
        textarea { min-height: 100px; resize: vertical; }
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            background: var(--gray-50);
            border-radius: 6px;
            cursor: pointer;
        }
        .checkbox-label:hover { background: #f1f5f9; }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary    { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-secondary  { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #cbd5e1; }
        .status-select {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
        }
        @media (max-width: 992px) {
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<?php include 'admin-sidebar.php'; ?>

<main class="main-content">
    <header class="header">
        <h1>Edit Campaign</h1>
        <div style="color:#64748b;"><?= htmlspecialchars($campaign['name']) ?></div>
    </header>

    <div class="container">
        <div class="card">

            <?php if ($message): ?>
            <div class="message message-<?= $message_type ?>">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                <?= $message ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="name">Campaign Name *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?= htmlspecialchars($campaign['name']) ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description / Objective</label>
                    <textarea name="description" id="description" 
                              placeholder="Campaign goals and details..."><?= htmlspecialchars($campaign['description'] ?? '') ?></textarea>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:2rem;">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" 
                               value="<?= htmlspecialchars($campaign['start_date'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" 
                               value="<?= htmlspecialchars($campaign['end_date'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label" style="background:none; padding:0;">
                        <input type="checkbox" name="target_all" id="target_all" value="1"
                               <?= $campaign['target_all'] ? 'checked' : '' ?>>
                        <span>Target All Employees</span>
                    </label>
                </div>

                <div class="form-group" id="departments-section" 
                     style="display: <?= $campaign['target_all'] ? 'none' : 'block' ?>;">
                    <label>Select Departments</label>
                    <div class="checkbox-group">
                        <?php foreach($all_departments as $dept): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="departments[]" value="<?= $dept['id'] ?>"
                                <?= in_array($dept['id'], $selected_department_ids) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Campaign Status</label>
                    <select name="status" id="status" class="status-select">
                        <option value="draft"     <?= $campaign['status']==='draft'     ? 'selected' : '' ?>>Draft</option>
                        <option value="active"    <?= $campaign['status']==='active'    ? 'selected' : '' ?>>Active</option>
                        <option value="completed" <?= $campaign['status']==='completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="archived"  <?= $campaign['status']==='archived'  ? 'selected' : '' ?>>Archived</option>
                    </select>
                </div>

                <div style="margin-top: 2rem; display:flex; gap:1rem; flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="campaigns.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancel / Back to List
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    const targetAllCheckbox = document.getElementById('target_all');
    const departmentsSection = document.getElementById('departments-section');

    function toggleDepartments() {
        departmentsSection.style.display = targetAllCheckbox.checked ? 'none' : 'block';
    }

    targetAllCheckbox.addEventListener('change', toggleDepartments);
    // Initial state
    toggleDepartments();
</script>

</body>
</html>