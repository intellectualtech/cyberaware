<?php
/**
 * Notifications API Endpoint
 * Handles notification-related API requests
 */

require_once '../config.php';
require_once '../../includes/NotificationService.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Verify JWT token
$headers = getallheaders();
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

if (!$token || !verifyJWT($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$payload = decodeJWT($token);
$user_id = $payload['user_id'];

$pdo = getDBConnection();
$notificationService = new NotificationService($pdo);

switch ($action) {
    case 'get_preferences':
        getPreferences($user_id, $pdo);
        break;

    case 'update_preferences':
        updatePreferences($user_id, $pdo, $notificationService);
        break;

    case 'get_history':
        getHistory($user_id, $pdo);
        break;

    case 'get_stats':
        getStats($user_id, $pdo);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

function getPreferences($user_id, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_notification_preferences WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $prefs = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prefs) {
            // Create default preferences
            $stmt = $pdo->prepare("INSERT INTO user_notification_preferences (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
            $prefs = [
                'user_id' => $user_id,
                'training_assigned' => 1,
                'campaign_reminder' => 1,
                'deadline_approaching' => 1,
                'incident_update' => 1,
                'certificate_completion' => 1,
                'email_frequency' => 'immediate'
            ];
        }

        echo json_encode(['success' => true, 'data' => $prefs]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function updatePreferences($user_id, $pdo, $notificationService) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$notificationService->updatePreferences($user_id, $data)) {
            throw new Exception('Failed to update preferences');
        }

        echo json_encode(['success' => true, 'message' => 'Preferences updated']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getHistory($user_id, $pdo) {
    try {
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT * FROM notification_history
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $limit, $offset]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notification_history WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $total = $stmt->fetch()['total'];

        echo json_encode([
            'success' => true,
            'data' => $history,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getStats($user_id, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                type,
                status,
                COUNT(*) as count
            FROM notification_history
            WHERE user_id = ?
            GROUP BY type, status
        ");
        $stmt->execute([$user_id]);
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $stats]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getallheaders() {
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
    return $headers;
}
?>
