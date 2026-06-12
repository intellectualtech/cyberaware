<?php
/**
 * Admin Endpoints
 * 
 * GET  /api/admin/users          - Get all users
 * GET  /api/admin/stats          - Get platform statistics
 * POST /api/admin/users          - Create user
 */

function handleAdminRequest($method, $resource, $action, $api_key_data) {
    APIAuth::checkPermission('write', $api_key_data);

    if ($method === 'GET') {
        if ($resource === 'users') {
            getAdminUsers();
        }
        elseif ($resource === 'stats') {
            getAdminStats();
        }
        else {
            die(APIResponse::error('Endpoint not found', 404));
        }
    }
    elseif ($method === 'POST') {
        if ($resource === 'users') {
            createAdminUser();
        }
        else {
            die(APIResponse::error('Endpoint not found', 404));
        }
    }
    else {
        die(APIResponse::error('Method not allowed', 405));
    }
}

/**
 * Get all users (admin)
 */
function getAdminUsers() {
    try {
        $pdo = getDBConnection();

        $page = (int)($_GET['page'] ?? 1);
        $per_page = (int)($_GET['per_page'] ?? 20);
        $offset = ($page - 1) * $per_page;

        // Get total count
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get users
        $stmt = $pdo->prepare("
            SELECT id, username, full_name, email, role, is_active, created_at, last_login
            FROM users
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo APIResponse::paginated($users, $total, $page, $per_page, 'Users retrieved successfully');
    } catch (Exception $e) {
        error_log("Get admin users error: " . $e->getMessage());
        die(APIResponse::error('Failed to retrieve users', 500));
    }
}

/**
 * Get platform statistics (admin)
 */
function getAdminStats() {
    try {
        $pdo = getDBConnection();

        $stats = [
            'total_users' => 0,
            'total_trainees' => 0,
            'total_modules' => 0,
            'total_sessions' => 0,
            'average_score' => 0,
            'completion_rate' => 0
        ];

        // Total users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Total trainees
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'trainee'");
        $stats['total_trainees'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Total modules
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM training_modules WHERE is_active = 1");
        $stats['total_modules'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Total sessions
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM training_sessions");
        $stats['total_sessions'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Average score
        $stmt = $pdo->query("SELECT AVG(final_score) as avg FROM training_sessions");
        $stats['average_score'] = round((float)$stmt->fetch(PDO::FETCH_ASSOC)['avg'], 2);

        // Completion rate
        $stmt = $pdo->query("
            SELECT 
                COUNT(CASE WHEN passed = 1 THEN 1 END) as completed,
                COUNT(*) as total
            FROM user_module_progress
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['completion_rate'] = $result['total'] > 0 ? round(($result['completed'] / $result['total']) * 100, 2) : 0;

        echo APIResponse::success($stats, 'Statistics retrieved successfully');
    } catch (Exception $e) {
        error_log("Get admin stats error: " . $e->getMessage());
        die(APIResponse::error('Failed to retrieve statistics', 500));
    }
}

/**
 * Create user (admin)
 */
function createAdminUser() {
    $input = json_decode(file_get_contents('php://input'), true);

    $errors = Validator::required($input, ['username', 'email', 'password', 'full_name']);
    if (!empty($errors)) {
        die(APIResponse::error('Validation failed', 400, $errors));
    }

    if (!Validator::email($input['email'])) {
        die(APIResponse::error('Invalid email format', 400));
    }

    try {
        $pdo = getDBConnection();

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$input['username'], $input['email']]);
        if ($stmt->fetch()) {
            die(APIResponse::error('Username or email already exists', 409));
        }

        // Create user
        $password_hash = password_hash($input['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, full_name, role, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([
            sanitize($input['username']),
            sanitize($input['email']),
            $password_hash,
            sanitize($input['full_name']),
            sanitize($input['role'] ?? 'trainee')
        ]);

        $user_id = $pdo->lastInsertId();

        $response = [
            'id' => $user_id,
            'username' => $input['username'],
            'email' => $input['email'],
            'full_name' => $input['full_name'],
            'role' => $input['role'] ?? 'trainee'
        ];

        echo APIResponse::success($response, 'User created successfully', 201);
    } catch (Exception $e) {
        error_log("Create admin user error: " . $e->getMessage());
        die(APIResponse::error('Failed to create user', 500));
    }
}

?>
