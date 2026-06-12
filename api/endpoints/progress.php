<?php
/**
 * Progress Endpoints
 * 
 * POST /api/progress/{module_id}  - Update module progress
 * GET  /api/progress/{user_id}    - Get user progress
 */

function handleProgressRequest($method, $resource, $action, $api_key_data) {
    if ($method === 'POST') {
        APIAuth::checkPermission('write', $api_key_data);
        updateProgress($resource);
    }
    elseif ($method === 'GET') {
        getProgress($resource);
    }
    else {
        die(APIResponse::error('Method not allowed', 405));
    }
}

/**
 * Update module progress
 */
function updateProgress($module_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $module_id = (int)$module_id;

    $errors = Validator::required($input, ['user_id', 'score']);
    if (!empty($errors)) {
        die(APIResponse::error('Validation failed', 400, $errors));
    }

    try {
        $pdo = getDBConnection();
        $user_id = (int)$input['user_id'];
        $score = (int)$input['score'];

        // Validate score
        if ($score < 0 || $score > 100) {
            die(APIResponse::error('Score must be between 0 and 100', 400));
        }

        // Update progress
        $stmt = $pdo->prepare("
            INSERT INTO user_module_progress (user_id, module_id, total_attempts, best_score, average_score, passed, passed_date, last_attempt_date)
            VALUES (?, ?, 1, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                total_attempts = total_attempts + 1,
                best_score = GREATEST(best_score, ?),
                average_score = (average_score * (total_attempts - 1) + ?) / total_attempts,
                passed = CASE WHEN ? >= 70 THEN 1 ELSE passed END,
                passed_date = CASE WHEN ? >= 70 THEN NOW() ELSE passed_date END,
                last_attempt_date = NOW()
        ");

        $stmt->execute([
            $user_id,
            $module_id,
            $score,
            $score >= 70 ? 1 : 0,
            $score,
            $score,
            $score,
            $score
        ]);

        echo APIResponse::success(['module_id' => $module_id, 'score' => $score], 'Progress updated successfully');
    } catch (Exception $e) {
        error_log("Update progress error: " . $e->getMessage());
        die(APIResponse::error('Failed to update progress', 500));
    }
}

/**
 * Get progress
 */
function getProgress($user_id) {
    try {
        $user_id = (int)$user_id;
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("
            SELECT 
                module_id,
                total_attempts,
                best_score,
                average_score,
                passed,
                passed_date,
                last_attempt_date
            FROM user_module_progress
            WHERE user_id = ?
            ORDER BY module_id
        ");
        $stmt->execute([$user_id]);
        $progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo APIResponse::success($progress, 'Progress retrieved successfully');
    } catch (Exception $e) {
        error_log("Get progress error: " . $e->getMessage());
        die(APIResponse::error('Failed to retrieve progress', 500));
    }
}

?>
