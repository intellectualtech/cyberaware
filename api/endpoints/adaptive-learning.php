<?php
/**
 * Adaptive Learning Feature - API Endpoints
 * 
 * This file provides RESTful endpoints for the Adaptive Learning feature:
 * - GET /api/adaptive-learning/recommendations
 * - GET /api/adaptive-learning/performance-analysis
 * - POST /api/adaptive-learning/accept-recommendation
 * - POST /api/adaptive-learning/decline-recommendation
 * - GET /api/adaptive-learning/trends
 */

require_once '../config.php';
require_once '../../includes/adaptive-learning/PerformanceAnalysisEngine.php';
require_once '../../includes/adaptive-learning/RecommendationEngine.php';
require_once '../../includes/adaptive-learning/DifficultyProgressionEngine.php';
require_once '../../includes/adaptive-learning/RemediationTrackingEngine.php';
require_once '../../includes/adaptive-learning/TrendAnalysisEngine.php';

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Extract endpoint
$endpoint = end($pathParts);

// Get database connection
try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Initialize engines
$performanceEngine = new PerformanceAnalysisEngine($pdo);
$recommendationEngine = new RecommendationEngine($pdo);
$difficultyEngine = new DifficultyProgressionEngine($pdo);
$remediationEngine = new RemediationTrackingEngine($pdo);
$trendEngine = new TrendAnalysisEngine($pdo);

// Route requests
switch ($endpoint) {
    case 'recommendations':
        handleRecommendations($method, $recommendationEngine, $performanceEngine);
        break;
        
    case 'performance-analysis':
        handlePerformanceAnalysis($method, $performanceEngine);
        break;
        
    case 'accept-recommendation':
        handleAcceptRecommendation($method, $recommendationEngine);
        break;
        
    case 'decline-recommendation':
        handleDeclineRecommendation($method, $recommendationEngine);
        break;
        
    case 'trends':
        handleTrends($method, $trendEngine);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
        break;
}

/**
 * Handle GET /api/adaptive-learning/recommendations
 */
function handleRecommendations($method, $recommendationEngine, $performanceEngine) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    try {
        // Get parameters
        $userId = $_GET['user_id'] ?? $_SESSION['user_id'] ?? null;
        $limit = min((int)($_GET['limit'] ?? 3), 10);
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'User ID required']);
            return;
        }
        
        // Generate recommendations
        $result = $recommendationEngine->generateRecommendations($userId, $limit);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Handle GET /api/adaptive-learning/performance-analysis
 */
function handlePerformanceAnalysis($method, $performanceEngine) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    try {
        $userId = $_GET['user_id'] ?? $_SESSION['user_id'] ?? null;
        $category = $_GET['category'] ?? null;
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'User ID required']);
            return;
        }
        
        if ($category) {
            // Single category analysis
            $analysis = $performanceEngine->calculateCategoryAverage($userId, $category);
            $data = ['category' => $category, 'analysis' => $analysis];
        } else {
            // Full analysis for all categories
            $analysis = $performanceEngine->getFullPerformanceAnalysis($userId);
            $overallAverage = array_sum(array_column($analysis, 'weighted_average')) / count($analysis);
            
            $data = [
                'categories' => $analysis,
                'summary' => [
                    'overall_average' => round($overallAverage, 2),
                    'weak_categories_count' => count(array_filter($analysis, function($a) { return $a['is_weak']; })),
                    'mastered_categories_count' => count(array_filter($analysis, function($a) { return $a['is_mastered']; })),
                    'total_sessions' => 0 // Would need to calculate from sessions
                ]
            ];
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Handle POST /api/adaptive-learning/accept-recommendation
 */
function handleAcceptRecommendation($method, $recommendationEngine) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $recommendationId = $data['recommendation_id'] ?? null;
        $userId = $data['user_id'] ?? $_SESSION['user_id'] ?? null;
        
        if (!$recommendationId || !$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Recommendation ID and User ID required']);
            return;
        }
        
        $success = $recommendationEngine->acceptRecommendation($recommendationId, $userId);
        
        if ($success) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Recommendation accepted',
                'data' => [
                    'recommendation_id' => $recommendationId,
                    'accepted_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Failed to accept recommendation']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Handle POST /api/adaptive-learning/decline-recommendation
 */
function handleDeclineRecommendation($method, $recommendationEngine) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $recommendationId = $data['recommendation_id'] ?? null;
        $userId = $data['user_id'] ?? $_SESSION['user_id'] ?? null;
        $reason = $data['reason'] ?? null;
        
        if (!$recommendationId || !$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Recommendation ID and User ID required']);
            return;
        }
        
        $success = $recommendationEngine->declineRecommendation($recommendationId, $userId, $reason);
        
        if ($success) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Recommendation declined',
                'data' => [
                    'recommendation_id' => $recommendationId,
                    'declined_at' => date('Y-m-d H:i:s'),
                    'reappear_after' => date('Y-m-d H:i:s', strtotime('+7 days'))
                ]
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Failed to decline recommendation']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Handle GET /api/adaptive-learning/trends
 */
function handleTrends($method, $trendEngine) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    try {
        $userId = $_GET['user_id'] ?? $_SESSION['user_id'] ?? null;
        $timePeriod = $_GET['time_period'] ?? '30d';
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'User ID required']);
            return;
        }
        
        // Validate time period
        if (!in_array($timePeriod, ['30d', '60d', '90d'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid time period. Use 30d, 60d, or 90d']);
            return;
        }
        
        $report = $trendEngine->getTrendReport($userId, $timePeriod);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $report
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

?>
