<?php
// admin/add_user.php - Add New Employee Page

require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../index.php');
    exit();
}

$pdo = getDBConnection();

$departments = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Handle error
}

$success_message = '';
$error_messages = [];

$username = $full_name = $email = $role = '';
$department_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department_id = $_POST['department_id'] ?? null;
    if ($department_id === '') $department_id = null;
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($full_name) || empty($email) || empty($role) || empty($password)) {
        $error_messages[] = 'Please fill in all required fields.';
    }

    if ($password !== $confirm_password) {
        $error_messages[] = 'Passwords do not match.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_messages[] = 'Please enter a valid email address.';
    }

    $allowed_roles = ['trainee', 'manager', 'compliance', 'admin'];
    if (!in_array($role, $allowed_roles)) {
        $error_messages[] = 'Invalid role selected.';
    }

    if (empty($error_messages)) {
        // Check for duplicate username or email
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->execute([':username' => $username, ':email' => $email]);
            if ($stmt->rowCount() > 0) {
                $error_messages[] = 'Username or email address is already in use.';
            }
        } catch (Exception $e) {
            $error_messages[] = 'Database error while checking uniqueness.';
        }
    }

    if (empty($error_messages)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $sql = "INSERT INTO users (username, password_hash, full_name, email, role, department_id, is_active) 
                    VALUES (:username, :password_hash, :full_name, :email, :role, :department_id, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':username' => $username,
                ':password_hash' => $password_hash,
                ':full_name' => $full_name,
                ':email' => $email,
                ':role' => $role,
                ':department_id' => $department_id
            ]);

            $success_message = "Employee '{$full_name}' added successfully with username '{$username}'.";
            // Clear form
            $username = $full_name = $email = $role = '';
            $department_id = null;
        } catch (Exception $e) {
            $error_messages[] = 'Failed to add employee. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Employee - CyberAware Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF8C42;
            --primary-dark: #E67A2E;
            --primary-light: #FFF4ED;
            --success: #00A65A;
            --danger: #DD4B39;
            --dark: #2C2C2C;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #D4D4D4;
            --gray-600: #475569;
            --gray-700: #334155;
            --white: #FFFFFF;
            --shadow-md: 0 4px 16px rgba(0,0,0,0.1);
            --sidebar-width: 260px;
            --radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #EE8E46 0%, #E67A2E 100%);
            color: var(--dark);
            line-height: 1.6;
        }

        .main-content {
            margin-bottom: var(--sidebar-height);
            padding: 3rem 2rem;
            min-height: 100vh;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: var(--dark);
            text-align: center;
        }

        .card {
            background: var(--white);
            padding: 3rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            max-width: 900px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--gray-700);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
        }

        .form-success {
            background: #E8F5E9;
            color: var(--success);
            border: 1px solid var(--success);
        }

        .form-error {
            background: #FFEBEE;
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        .form-actions {
            display: flex;
            gap: 1.5rem;
            margin-top: 2.5rem;
            justify-content: center;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }

        /* Mobile */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 2rem 1rem 6rem 1rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .card {
                padding: 2rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .page-title {
                font-size: 1.8rem;
            }

            .card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <?php include 'admin-sidebar.php'; ?>

    <main class="main-content">
        <h2 class="page-title">Add New Employee</h2>

        <div class="card">
            <?php if ($success_message): ?>
                <div class="form-message form-success">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_messages)): ?>
                <div class="form-message form-error">
                    <ul style="margin: 0; padding-left: 20px; text-align: left;">
                        <?php foreach ($error_messages as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($full_name) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                </div>

                <div class="form-group">
                    <label for="department_id">Department</label>
                    <select id="department_id" name="department_id">
                        <option value="">None</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= ($department_id == $dept['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="role">Role *</label>
                    <select id="role" name="role" required>
                        <option value="trainee" <?= ($role === 'trainee') ? 'selected' : '' ?>>Trainee (Employee)</option>
                        <option value="manager" <?= ($role === 'manager') ? 'selected' : '' ?>>Manager</option>
                        <option value="compliance" <?= ($role === 'compliance') ? 'selected' : '' ?>>Compliance Officer</option>
                        <option value="admin" <?= ($role === 'admin') ? 'selected' : '' ?>>Administrator</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Employee</button>
                    <a href="manage-users.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>