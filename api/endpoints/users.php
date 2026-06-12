<?php
/**
 * Users Endpoints
 * 
 * GET  /api/users/{id}           - Get user profile
 * GET  /api/users/{id}/progress  - Get user progress
 * GET  /api/users/{id}/modules   - Get user's modules
 * PUT  /api/users/{id}           - Update user profile
 */

function handleUsersRequest($method, $resource, $action, $api_key_data) {
    if ($method === 'GET') {
        if ($action === 'progress') {
            getUserProgress($resource);
        }
        elseif ($action === 'modules') {
            getUserModules($resource);
        }
        else {
            getUser($resource);
        }
    }
    elseif ($method === 'PUT') {
        APIAuth::checkPermission('write', $api_key_data);
        updateUser($resource);
    }
    else {
        die(APIResponse::error('Method not allowed', 405));
    }
}

/**
 * Get user profile
 */
function getUser($user_id) {
    try {
        $user_id = (int)$user_id;
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("
            SELECT id, username, full_name, email, role, department_id, is_active, created_at, last_login
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            die(APIResponse::error('User not found', 404));
        }

        echo APIResponse::success($user, 'User retrieved successfully');
    } catch (Exception $e) {
        error_log("Get user error: " . $e->getMessage());
        die(APIResponse::error('Failed to retrieve user', 500));
    }
}

/**
 * Get user progress
 */
function getUserProgress($user_id) {
    try {
        $user_id = (int)$user_id;
        $pdo = getDBConnection();

        // Get overall stats
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_modules,
                SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as completed_modules,
                ROUND(AVG(best_score)) as average_score,
                SUM(total_attempts) as total_attempts
            FROM user_module_progress
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get module-by-module progress
        $stmt = $pdo->prepare("
            SELECT 
                ump.module_id,
                tm.title,
                ump.total_attempts,
                ump.best_score,
                ump.average_score,
                ump.passed,
                ump.passed_date
            FROM user_module_progress ump
            JOIN training_modules tm ON ump.module_id = tm.id
            WHERE ump.user_id = ?
            ORDER BY ump.module_id
        ");
        $stmt->execute([$user_id]);
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [
            'overall' => $stats,
            'modules' => $modules
        ];

        echo APIResponse::success($response, 'User progress retrieved successfully');
    } catch (Exception $e) {
        error_log("Get user progress error: " . $e->getMessage());
        die(APIResponse::error('Failed to retrieve user progress', 500));
    }
}

/**
 * Get user's modules
 */
function getUserModules($user_id) {
    try {
        $user_id = (int)$user_id;
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("
            SELECT 
                umr.module_id,
                umr.status,
                umr.registration_order,
                tm.title,
                tm.code,
                tm.category,
                ump.best_score,
                ump.passed
            FROM user_module_registrations umr
            JOIN training_modules tm ON umr.module_id = tm.id
            LEFT JOIN user_module_progress ump ON umr.user_id = ump.user_id AND umr.module_id = ump.module_id
            WHERE umr.user_id = ?
            ORDER BY umr.registration_order
        ");
        $stmt->execute([$user_id]);
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo APIResponse::success($modules, 'User modules retrieved successfully');
    } catch (Exception $e) {
        error_log("Get user modules error: " . $e->getMessage());
        die(APIResponse::error('Failed to retrieve user modules', 500));
    }
}

/**
 * Update user profile
 */
function updateUser($user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = (int)$user_id;

    try {
        $pdo = getDBConnection();

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        if (!$stmt->fetch()) {
            die(APIResponse::error('User not found', 404));
        }

        // Update user
        $updates = [];
        $params = [];

        if (isset($input['full_name'])) {
            $updates[] = 'full_name = ?';
            $params[] = sanitize($input['full_name']);
        }
        if (isset($input['email'])) {
            if (!Validator::email($input['email'])) {
                die(APIResponse::error('Invalid email format', 400));
            }
            $updates[] = 'email = ?';
            $params[] = sanitize($input['email']);
        }

        if (empty($updates)) {
            die(APIResponse::error('No fields to update', 400));
        }

        $updates[] = 'updated_at = NOW()';
        $params[] = $user_id;

        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo APIResponse::success(['id' => $user_id], 'User updated successfully');
    } catch (Exception $e) {
        error_log("Update user error: " . $e->getMessage());
        die(APIResponse::error('Failed to update user', 500));
    }
}

?>
