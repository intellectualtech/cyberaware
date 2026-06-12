<?php
/**
 * Leaderboard Endpoints
 * 
 * GET /api/leaderboard           - Get leaderboard
 * GET /api/leaderboard?filter=   - Filter by time period
 * GET /api/leaderboard?sort=     - Sort by metric
 */

function handleLeaderboardRequest($method, $api_key_data) {
    if ($method !== 'GET') {
        die(APIResponse::error('Method not allowed', 405));
    }

    getLeaderboard();
}

/**
 * Get leaderboard
 */
function getLeaderboard() {
    try {
        $pdo = getDBConnection();

        // Get filter and sort parameters
        $filter = $_GET['filter'] ?? 'all_time'; // all_time, this_week, this_month
        $sort = $_GET['sort'] ?? 'xp'; // xp, accuracy, modules, streak
        $limit = (int)($_GET['limit'] ?? 100);

        // Determine date filter
        $date_filter = '';
        switch ($filter) {
            case 'this_week':
                $date_filter = "AND ts.completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'this_month':
                $date_filter = "AND ts.completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }

        // Determine sort order
        $sort_clause = '';
        switch ($sort) {
            case 'accuracy':
                $sort_clause = "ORDER BY avg_accuracy DESC, total_xp DESC";
                break;
            case 'modules':
                $sort_clause = "ORDER BY modules_completed DESC, avg_accuracy DESC";
                break;
            case 'streak':
                $sort_clause = "ORDER BY current_streak DESC, total_xp DESC";
                break;
            default: // xp
                $sort_clause = "ORDER BY total_xp DESC, avg_accuracy DESC";
        }

        // Get leaderboard data
        $query = "
            SELECT 
                u.id,
                u.full_name,
                u.username,
                d.name AS department,
                COUNT(DISTINCT ts.id) as total_sessions,
                COUNT(DISTINCT CASE WHEN ts.final_score >= 70 THEN ts.id END) as modules_completed,
                COALESCE(AVG(ts.final_score), 0) as avg_accuracy,
                COALESCE(SUM(CASE WHEN ts.final_score >= 70 THEN 140 ELSE 0 END) + 
                         COALESCE(SUM(ts.final_score * 0.04), 0), 0) as total_xp,
                MAX(ts.completed_at) as last_activity,
                COALESCE(COUNT(DISTINCT CASE WHEN ia.action = 'phish' AND ia.is_correct = 1 THEN ia.id END), 0) as phishing_correct,
                COALESCE(COUNT(DISTINCT ia.id), 0) as phishing_total
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.id
            LEFT JOIN training_sessions ts ON u.id = ts.user_id $date_filter
            LEFT JOIN inbox_actions ia ON u.id = ia.user_id
            WHERE u.role = 'trainee' AND u.is_active = 1
            GROUP BY u.id
            $sort_clause
            LIMIT $limit
        ";

        $leaderboard = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

        // Add rank
        foreach ($leaderboard as $key => $entry) {
            $leaderboard[$key]['rank'] = $key + 1;
        }

        $response = [
            'filter' => $filter,
            'sort' => $sort,
            'leaderboard' => $leaderboard
        ];

        echo APIResponse::success($response, 'Leaderboard retrieved successfully');
    } catch (Exception $e) {
        error_log("Get leaderboard error: " . $e->getMessage());
        die(APIResponse::error('Failed to retrieve leaderboard', 500));
    }
}

?>
