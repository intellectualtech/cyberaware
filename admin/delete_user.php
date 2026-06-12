<?php
// admin/delete_user.php - Delete User Page

require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: ../index.php');
    exit();
}

$pdo = getDBConnection();

// Get user ID
$user_id = $_GET['id'] ?? null;
if (!$user_id || !is_numeric($user_id)) {
    header('Location: manage-users.php');
    exit();
}

// Prevent deleting self
if ($user_id == $_SESSION['user_id']) {
    header('Location: manage-users.php?error=cannot_delete_self');
    exit();
}

// Load user data for confirmation
$user = null;
try {
    $stmt = $pdo->prepare("
        SELECT id, username, full_name, email, role 
        FROM users 
        WHERE id = :id
    ");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: manage-users.php?error=user_not_found');
        exit();
    }
} catch (Exception $e) {
    header('Location: manage-users.php?error=load_failed');
    exit();
}

// Check if this is the last admin
if ($user['role'] === 'admin') {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1");
        $admin_count = $stmt->fetchColumn();
        if ($admin_count <= 1) {
            header('Location: manage-users.php?error=cannot_delete_last_admin');
            exit();
        }
    } catch (Exception $e) {
        // Proceed but log if needed
    }
}

$success_message = '';
$error_message = '';

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_delete'])) {
        try {
            // Delete related records first (due to foreign keys)
            $pdo->beginTransaction();
            
            // Delete from user_training_summary (has ON DELETE CASCADE, but safe)
            $pdo->prepare("DELETE FROM user_training_summary WHERE user_id = :id")->execute([':id' => $user_id]);
            
            // Delete training sessions/actions (cascades)
            $pdo->prepare("DELETE FROM training_sessions WHERE user_id = :id")->execute([':id' => $user_id]);
            
            // Delete from campaign assignments if any
            $pdo->prepare("DELETE FROM campaign_user_assignments WHERE user_id = :id")->execute([':id' => $user_id]);
            
            // Finally delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $user_id]);
            
            $pdo->commit();
            
            header('Location: manage-users.php?success=user_deleted');
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = 'Failed to delete user. They may have associated records.';
        }
    } else {
        header('Location: manage_users.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User - CyberAware Admin</title>
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
            background: var(--gray-50);
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
            color: var(--danger);
            text-align: center;
        }

        .card {
            background: var(--white);
            padding: 3rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            max-width: 700px;
            margin: 0 auto;
            border: 1px solid var(--gray-200);
        }

        .warning-box {
            background: #FFEBEE;
            border: 1px solid var(--danger);
            color: var(--danger);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 1.1rem;
        }

        .user-details {
            background: var(--gray-100);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .user-details p {
            margin: 0.8rem 0;
            font-size: 1.1rem;
        }

        .user-details strong {
            color: var(--dark);
        }

        .form-actions {
            display: flex;
            gap: 1.5rem;
            margin-top: 2rem;
            justify-content: center;
        }

        .btn {
            padding: 1rem 2.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c43e2c;
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
    </style>
</head>
<body>

    <?php include 'admin-sidebar.php'; ?>

    <main class="main-content">
        <h2 class="page-title">Delete User Confirmation</h2>

        <div class="card">
            <div class="warning-box">
                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                <p><strong>Warning:</strong> This action cannot be undone!</p>
                <p>All user data, training records, and assignments will be permanently deleted.</p>
            </div>

            <?php if ($error_message): ?>
                <div style="padding: 1rem; background: #FFEBEE; color: var(--danger); border-radius: 8px; margin-bottom: 1.5rem; text-align: center;">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <div class="user-details">
                <p><strong>Full Name:</strong> <?= htmlspecialchars($user['full_name'] ?? 'N/A') ?></p>
                <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Role:</strong> <?= ucfirst(htmlspecialchars($user['role'])) ?></p>
            </div>

            <form method="POST">
                <input type="hidden" name="confirm_delete" value="1">
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Yes, Delete This User
                    </button>
                    <a href="manage-users.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>

</body>
</html>