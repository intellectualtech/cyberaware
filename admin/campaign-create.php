<?php
// admin/campaign-create.php - Create New Campaign (Separate Page)
require_once '../config/database.php';
require_once '../includes/functions.php';
require_admin(); // Only admins/managers

$pdo = getDBConnection();

$message = '';
$message_type = 'info';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $start_date  = $_POST['start_date'] ?: null;
    $end_date    = $_POST['end_date'] ?: null;
    $target_all  = isset($_POST['target_all']) ? 1 : 0;

    $errors = [];

    if (empty($name)) {
        $errors[] = "Campaign name is required.";
    }
    if ($start_date && $end_date && strtotime($start_date) > strtotime($end_date)) {
        $errors[] = "End date cannot be earlier than start date.";
    }

    if (!empty($errors)) {
        $message = implode("<br>", $errors);
        $message_type = 'danger';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO campaigns 
                (name, description, start_date, end_date, created_by, status, target_all, created_at)
                VALUES (?, ?, ?, ?, ?, 'draft', ?, NOW())
            ");
            $stmt->execute([$name, $description, $start_date, $end_date, $_SESSION['user_id'], $target_all]);
            $campaign_id = $pdo->lastInsertId();

            // Save departments only if NOT targeting all
            if (!$target_all && !empty($_POST['departments'])) {
                $dept_stmt = $pdo->prepare("
                    INSERT INTO campaign_departments (campaign_id, department_id) 
                    VALUES (?, ?)
                ");
                foreach ($_POST['departments'] as $dept_id) {
                    $dept_stmt->execute([$campaign_id, (int)$dept_id]);
                }
            }

            $pdo->commit();

            // Success → redirect to main list with message
            header("Location: campaigns.php?success=1&msg=Campaign+created+successfully!");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error creating campaign. Please try again.";
            $message_type = 'danger';
        }
    }
}

// Fetch departments for selection
$dept_stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
$departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Campaign – CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF8C42;
            --primary-dark: #E67A2E;
            --primary-light: #FFF4ED;
            --primary-lighter: #FFEAD9;
            --success: #00A65A;
            --warning: #F39C12;
            --danger: #DD4B39;
            --dark: #2C2C2C;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #D4D4D4;
            --gray-400: #B8B8B8;
            --gray-500: #9E9E9E;
            --gray-600: #475569;
            --gray-700: #334155;
            --white: #FFFFFF;
            --sidebar-width: 260px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--gray-100);
            color: var(--dark);
            line-height: 1.6;
        }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .header {
            background: white;
            border-bottom: 1px solid var(--gray-200);
            padding: 20px 32px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .page-title { font-size: 24px; font-weight: 600; }
        .container { padding: 32px; max-width: 900px; margin: 0 auto; }
        .card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        }
        .message {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .message-success { background: #E8F5E9; color: var(--success); }
        .message-danger  { background: #FFEBEE; color: var(--danger); }
        .form-group { margin-bottom: 24px; }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--gray-700);
        }
        input[type="text"],
        input[type="date"],
        textarea,
        select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            font-size: 15px;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-lighter);
        }
        textarea { min-height: 100px; resize: vertical; }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: var(--gray-50);
            border-radius: 8px;
            cursor: pointer;
            margin-bottom: 8px;
        }
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }
        .btn {
            padding: 14px 32px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }
        .btn-back {
            background: var(--gray-200);
            color: var(--gray-700);
            margin-right: 12px;
        }
        .btn-back:hover { background: #e2e8f0; }
        @media (max-width: 992px) {
            .main-content { margin-left: 0; }
            .container { padding: 20px; }
        }
    </style>
</head>
<body>

    <?php include 'admin-sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1 class="page-title">Create New Campaign</h1>
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
                               placeholder="e.g. Q2 2026 Phishing Awareness" 
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Description / Objective</label>
                        <textarea name="description" id="description" 
                                  placeholder="Brief description of the campaign goals..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:32px;">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" 
                                   value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" 
                                   value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label" style="background:transparent; padding:0;">
                            <input type="checkbox" name="target_all" id="target_all" value="1" <?= !isset($_POST['target_all']) ? 'checked' : '' ?>>
                            <span>Target All Employees</span>
                        </label>
                    </div>

                    <div class="form-group" id="departments-section" style="display:none;">
                        <label>Select Target Departments</label>
                        <div class="checkbox-group">
                            <?php foreach($departments as $dept): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="departments[]" value="<?= $dept['id'] ?>"
                                    <?= in_array($dept['id'], $_POST['departments'] ?? []) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div style="margin-top:32px;">
                        <a href="campaigns.php" class="btn btn-back">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        <button type="submit" class="btn">
                            <i class="fas fa-save"></i> Create Campaign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        const targetAll = document.getElementById('target_all');
        const deptSection = document.getElementById('departments-section');

        function toggleDepartments() {
            deptSection.style.display = targetAll.checked ? 'none' : 'block';
        }

        targetAll.addEventListener('change', toggleDepartments);
        // Initial state
        toggleDepartments();
    </script>

</body>
</html>