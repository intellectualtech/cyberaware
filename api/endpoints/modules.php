<?php
/**
 * Modules Endpoints
 * 
 * GET  /api/modules              - Get all modules
 * GET  /api/modules/{id}         - Get specific module
 * POST /api/modules              - Create module (admin only)
 * PUT  /api/modules/{id}         - Update module (admin only)
 */

function handleModulesRequest($method, $resource, $action, $api_key_data) {
    if ($method === 'GET') {
        if (empty($resource)) {
            getModules();
        } else {
            getModule($resource);
        }
    }
    elseif ($method === 'POST') {
        APIAuth::checkPermission('write', $api_key_data);
        createModule();
    }
    elseif ($method === 'PUT') {
        APIAuth::checkPermission('write', $api_key_data);
        updateModule($resource);
    }
    else {
        die(APIResponse::error('Method not allowed', 405));
    }
}

/**
 * Get all modules
 */
function getModules() {
    try {
        $pdo = getDBConnection();
        
        // Pagination
        $page = (int)($_GET['page'] ?? 1);
        $per_page = (int)($_GET['per_page'] ?? 10);
        $offset = ($page - 1) * $per_page;

        // Get total count
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM training_modules WHERE is_active = 1");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get modules
        $stmt = $pdo->prepare("
            SELECT id, code, title, description, category, difficulty, estimated_minutes, is_active
            FROM training_modules
            WHERE is_active = 1
            ORDER BY id ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo APIResponse::paginated($modules, $total, $page, $per_page, 'Modules retrieved successfully');
    } catch (Exception $e) {
        error_log("Get modules error: " . $e->getMessage());
        die(APIResponse::error('Failed to retrieve modules', 500));
    }
}

/**
 * Get specific module
 */
function getModule($module_id) {
    try {
        $module_id = (int)$module_id;
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("
            SELECT id, code, title, description, category, difficulty, estimated_minutes, is_active
            FROM training_modules
            WHERE id = ? AND is_active = 1
        ");
        $stmt->execute([$module_id]);
        $module = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$module) {
            die(APIResponse::error('Module not found', 404));
        }

        echo APIResponse::success($module, 'Module retrieved successfully');
    } catch (Exception $e) {
        error_log("Get module error: " . $e->getMessage());
        die(APIResponse::error('Failed to retrieve module', 500));
    }
}

/**
 * Create module (admin only)
 */
function createModule() {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input
    $errors = Validator::required($input, ['code', 'title', 'category']);
    if (!empty($errors)) {
        die(APIResponse::error('Validation failed', 400, $errors));
    }

    try {
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("
            INSERT INTO training_modules (code, title, description, category, difficulty, estimated_minutes, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ");

        $stmt->execute([
            sanitize($input['code']),
            sanitize($input['title']),
            sanitize($input['description'] ?? ''),
            sanitize($input['category']),
            (int)($input['difficulty'] ?? 3),
            (int)($input['estimated_minutes'] ?? 30)
        ]);

        $module_id = $pdo->lastInsertId();

        $response = [
            'id' => $module_id,
            'code' => $input['code'],
            'title' => $input['title'],
            'category' => $input['category']
        ];

        echo APIResponse::success($response, 'Module created successfully', 201);
    } catch (Exception $e) {
        error_log("Create module error: " . $e->getMessage());
        die(APIResponse::error('Failed to create module', 500));
    }
}

/**
 * Update module (admin only)
 */
function updateModule($module_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $module_id = (int)$module_id;

    try {
        $pdo = getDBConnection();

        // Check if module exists
        $stmt = $pdo->prepare("SELECT id FROM training_modules WHERE id = ?");
        $stmt->execute([$module_id]);
        if (!$stmt->fetch()) {
            die(APIResponse::error('Module not found', 404));
        }

        // Update module
        $updates = [];
        $params = [];

        if (isset($input['title'])) {
            $updates[] = 'title = ?';
            $params[] = sanitize($input['title']);
        }
        if (isset($input['description'])) {
            $updates[] = 'description = ?';
            $params[] = sanitize($input['description']);
        }
        if (isset($input['difficulty'])) {
            $updates[] = 'difficulty = ?';
            $params[] = (int)$input['difficulty'];
        }

        if (empty($updates)) {
            die(APIResponse::error('No fields to update', 400));
        }

        $updates[] = 'updated_at = NOW()';
        $params[] = $module_id;

        $sql = "UPDATE training_modules SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo APIResponse::success(['id' => $module_id], 'Module updated successfully');
    } catch (Exception $e) {
        error_log("Update module error: " . $e->getMessage());
        die(APIResponse::error('Failed to update module', 500));
    }
}

?>
