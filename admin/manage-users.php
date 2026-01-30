<?php
// admin/manage_users.php - Manage Users (with Excel/CSV Bulk Import)

require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../index.php');
    exit();
}

$pdo = getDBConnection();

// Handle file upload
$import_message = '';
$import_type = '';
$import_details = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['user_file']) && $_FILES['user_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['user_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, ['csv', 'xlsx'])) {
        $import_message = "Only .csv and .xlsx files are allowed.";
        $import_type = 'danger';
    } else {
        try {
            $pdo->beginTransaction();

            if ($ext === 'csv') {
                // ── CSV Import ───────────────────────────────────────────────
                $handle = fopen($file['tmp_name'], 'r');
                if (!$handle) throw new Exception("Cannot open CSV file");

                $header = fgetcsv($handle); // first row = headers
                if (!$header) throw new Exception("Empty CSV file");

                // Normalize headers (lowercase, trim)
                $header_map = array_map('trim', array_map('strtolower', $header));

                $col = [
                    'username' => array_search('username', $header_map),
                    'full_name' => array_search('full_name', $header_map) ?: array_search('name', $header_map),
                    'email' => array_search('email', $header_map),
                    'role' => array_search('role', $header_map),
                    'department' => array_search('department', $header_map) ?: array_search('department_code', $header_map),
                    'password' => array_search('password', $header_map),
                ];

                if ($col['username'] === false || $col['email'] === false || $col['role'] === false) {
                    throw new Exception("CSV must contain at least: username, email, role columns");
                }

                $row_num = 2; // starting from 2 because 1 = header
                while (($data = fgetcsv($handle)) !== false) {
                    $row_num++;

                    $username   = trim($data[$col['username']] ?? '');
                    $full_name  = trim($data[$col['full_name']] ?? '');
                    $email      = trim($data[$col['email']] ?? '');
                    $role       = strtolower(trim($data[$col['role']] ?? 'trainee'));
                    $dept_code  = trim($data[$col['department']] ?? '');
                    $plain_pass = trim($data[$col['password']] ?? ''); // optional

                    if (empty($username) || empty($email)) continue; // skip empty rows

                    // Validate role
                    if (!in_array($role, ['trainee','manager','compliance','admin'])) {
                        $import_details[] = "Row $row_num: Invalid role '$role' → skipped";
                        continue;
                    }

                    // Find department id
                    $dept_id = null;
                    if ($dept_code) {
                        $dstmt = $pdo->prepare("SELECT id FROM departments WHERE code = ? OR name = ? LIMIT 1");
                        $dstmt->execute([$dept_code, $dept_code]);
                        $dept = $dstmt->fetch();
                        if ($dept) $dept_id = $dept['id'];
                    }

                    // Check if user already exists (by email or username)
                    $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $check->execute([$username, $email]);
                    if ($check->fetch()) {
                        $import_details[] = "Row $row_num: User $username / $email already exists → skipped";
                        continue;
                    }

                    // Insert new user
                    $pass_hash = $plain_pass ? password_hash($plain_pass, PASSWORD_DEFAULT) : password_hash('ChangeMe123!', PASSWORD_DEFAULT);

                    $insert = $pdo->prepare("
                        INSERT INTO users (username, password_hash, full_name, email, role, department_id, is_active, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                    ");
                    $insert->execute([$username, $pass_hash, $full_name ?: $username, $email, $role, $dept_id]);

                    $import_details[] = "Row $row_num: User <strong>$username</strong> created successfully";
                }

                fclose($handle);
            } 
            else {
                // ── XLSX Import (using PhpSpreadsheet) ───────────────────────
                // Note: You need to install phpoffice/phpspreadsheet via composer first!
                // composer require phpoffice/phpspreadsheet
                require_once '../vendor/autoload.php'; // adjust path if needed

                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                $spreadsheet = $reader->load($file['tmp_name']);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();

                if (count($rows) < 2) throw new Exception("Excel file is empty or has no data");

                $header = array_map('trim', array_map('strtolower', $rows[0]));

                $col = [
                    'username'   => array_search('username', $header),
                    'full_name'  => array_search('full_name', $header) ?: array_search('name', $header),
                    'email'      => array_search('email', $header),
                    'role'       => array_search('role', $header),
                    'department' => array_search('department', $header) ?: array_search('department_code', $header),
                    'password'   => array_search('password', $header),
                ];

                if ($col['username'] === false || $col['email'] === false || $col['role'] === false) {
                    throw new Exception("Excel must contain at least: username, email, role columns");
                }

                for ($i = 1; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    $row_num = $i + 1;

                    $username   = trim($row[$col['username']] ?? '');
                    $full_name  = trim($row[$col['full_name']] ?? '');
                    $email      = trim($row[$col['email']] ?? '');
                    $role       = strtolower(trim($row[$col['role']] ?? 'trainee'));
                    $dept_code  = trim($row[$col['department']] ?? '');
                    $plain_pass = trim($row[$col['password']] ?? '');

                    if (empty($username) || empty($email)) continue;

                    if (!in_array($role, ['trainee','manager','compliance','admin'])) {
                        $import_details[] = "Row $row_num: Invalid role → skipped";
                        continue;
                    }

                    $dept_id = null;
                    if ($dept_code) {
                        $dstmt = $pdo->prepare("SELECT id FROM departments WHERE code = ? OR name = ? LIMIT 1");
                        $dstmt->execute([$dept_code, $dept_code]);
                        $dept = $dstmt->fetch();
                        if ($dept) $dept_id = $dept['id'];
                    }

                    $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $check->execute([$username, $email]);
                    if ($check->fetch()) {
                        $import_details[] = "Row $row_num: User already exists → skipped";
                        continue;
                    }

                    $pass_hash = $plain_pass ? password_hash($plain_pass, PASSWORD_DEFAULT) : password_hash('ChangeMe123!', PASSWORD_DEFAULT);

                    $insert = $pdo->prepare("
                        INSERT INTO users (username, password_hash, full_name, email, role, department_id, is_active, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                    ");
                    $insert->execute([$username, $pass_hash, $full_name ?: $username, $email, $role, $dept_id]);

                    $import_details[] = "Row $row_num: User <strong>$username</strong> created";
                }
            }

            $pdo->commit();

            $import_message = "Import completed! " . count($import_details) . " rows processed.";
            $import_type = 'success';

        } catch (Exception $e) {
            $pdo->rollBack();
            $import_message = "Import failed: " . $e->getMessage();
            $import_type = 'danger';
        }
    }
}

// Fetch existing users
$users = $pdo->query("
    SELECT u.id, u.username, u.full_name, u.email, u.role, u.is_active, d.name AS department_name
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    ORDER BY u.full_name
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - CyberAware Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
        body { font-family: 'Inter', sans-serif; background: var(--gray-100); margin:0; }
        .main-content { margin-bottom: var(--sidebar-height); padding: 2rem; }
        .page-title { font-size: 2.2rem; font-weight: 700; margin-bottom: 1.5rem; }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .import-section { margin-bottom: 2.5rem; padding: 1.5rem; background: #f9fafb; border-radius: 10px; }
        .message { padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .message-success { background: #ecfdf5; color: #065f46; }
        .message-danger  { background: #fef2f2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f3f4f6; font-weight: 600; }
        .btn { padding: 0.7rem 1.4rem; border-radius: 6px; color: white; text-decoration: none; font-weight: 500; }
        .btn-primary { background: var(--primary); }
        .btn-import { background: #8b5cf6; }
        .status-active { color: var(--success); font-weight: bold; }
        .status-inactive { color: var(--danger); font-weight: bold; }
        @media (max-width: 992px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <?php include 'admin-sidebar.php'; ?>

    <main class="main-content">
        <h1 class="page-title">Manage Users</h1>

        <!-- Bulk Import Section -->
        <div class="card import-section">
            <h3><i class="fas fa-file-import"></i> Bulk Import Users (CSV or Excel)</h3>
            <p style="margin:0.75rem 0 1.25rem; color:#4b5563;">
                Supported columns: <code>username</code>, <code>full_name</code>, <code>email</code>, <code>role</code>, 
                <code>department</code> or <code>department_code</code>, <code>password</code> (optional)
            </p>

            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="user_file" accept=".csv,.xlsx" required style="margin-bottom:1rem;">
                <button type="submit" class="btn btn-import">
                    <i class="fas fa-upload"></i> Upload & Import
                </button>
            </form>

            <?php if ($import_message): ?>
            <div class="message message-<?= $import_type ?>">
                <?= $import_message ?>
            </div>
            <?php endif; ?>

            <?php if ($import_details): ?>
            <div style="margin-top:1.5rem; max-height:220px; overflow-y:auto; background:#f8fafc; padding:1rem; border-radius:8px;">
                <ul style="margin:0; padding-left:1.2rem;">
                    <?php foreach ($import_details as $detail): ?>
                        <li><?= $detail ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="margin:0;">All Users</h2>
                <a href="add_user.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add New User
                </a>
            </div>

            <?php if (empty($users)): ?>
                <p>No users found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['full_name'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['department_name'] ?: '—') ?></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($user['role']) ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </td>
                            <td class="status-<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                            </td>
                            <td>
                                <a href="edit_user.php?id=<?= $user['id'] ?>">Edit</a>
                                <a href="#" class="delete" onclick="return confirm('Delete user <?= htmlspecialchars($user['username']) ?>?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>