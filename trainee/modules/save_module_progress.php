<?php
/**
 * Save Module Progress
 * Handles saving module completion data to the database
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn() || $_SESSION['role'] !== 'trainee') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    $module_id = isset($_POST['module_id']) ? (int)$_POST['module_id'] : 0;
    $score = isset($_POST['score']) ? (int)$_POST['score'] : 0;
    $xp = isset($_POST['xp']) ? (int)$_POST['xp'] : 0;

    if (!$module_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Module ID is required']);
        exit;
    }

    // Check if module exists
    $stmt = $pdo->prepare("SELECT id FROM training_modules WHERE id = ?");
    $stmt->execute([$module_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Module not found']);
        exit;
    }

    // Check if user has a progress record for this module
    $stmt = $pdo->prepare("
        SELECT id, total_attempts, best_score, average_score 
        FROM user_module_progress 
        WHERE user_id = ? AND module_id = ?
    ");
    $stmt->execute([$user_id, $module_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing record
        $total_attempts = (int)$existing['total_attempts'] + 1;
        $best_score = max((int)$existing['best_score'], $score);
        $previous_avg = (float)$existing['average_score'];
        $new_avg = (($previous_avg * ((int)$existing['total_attempts'])) + $score) / $total_attempts;

        $stmt = $pdo->prepare("
            UPDATE user_module_progress 
            SET 
                total_attempts = ?,
                best_score = ?,
                average_score = ?,
                last_attempt_date = NOW(),
                passed = ?,
                passed_date = ?
            WHERE user_id = ? AND module_id = ?
        ");

        $passed = $score >= 70 ? 1 : 0;
        $passed_date = $passed ? date('Y-m-d H:i:s') : null;

        $stmt->execute([
            $total_attempts,
            $best_score,
            round($new_avg, 2),
            $passed,
            $passed_date,
            $user_id,
            $module_id
        ]);
    } else {
        // Create new record
        $passed = $score >= 70 ? 1 : 0;
        $passed_date = $passed ? date('Y-m-d H:i:s') : null;

        $stmt = $pdo->prepare("
            INSERT INTO user_module_progress 
            (user_id, module_id, total_attempts, best_score, average_score, passed, passed_date, last_attempt_date)
            VALUES (?, ?, 1, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $user_id,
            $module_id,
            $score,
            $score,
            $passed,
            $passed_date
        ]);
    }

    // Update registration status to passed when module is completed
    if ($passed) {
        $stmt = $pdo->prepare("
            UPDATE user_module_registrations 
            SET status = 'passed', completed_at = NOW()
            WHERE user_id = ? AND module_id = ?
        ");
        $stmt->execute([$user_id, $module_id]);
    }

    // Award XP if applicable
    if ($xp > 0) {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET xp_points = xp_points + ? 
            WHERE id = ?
        ");
        $stmt->execute([$xp, $user_id]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Progress saved successfully',
        'score' => $score,
        'xp' => $xp,
        'passed' => $passed ?? false
    ]);

} catch (Exception $e) {
    error_log("Error saving module progress: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error saving progress: ' . $e->getMessage()
    ]);
}
?>
