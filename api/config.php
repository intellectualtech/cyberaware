<?php
/**
 * API Configuration
 * Central configuration for all API endpoints
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// API Version
define('API_VERSION', '1.0.0');

// Rate limiting
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600); // 1 hour

// Database connection
require_once '../config/database.php';

// Load JWT and API Key managers
require_once 'jwt-helper.php';
require_once 'api-key-manager.php';

/**
 * API Response Helper
 */
class APIResponse {
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        return json_encode([
            'status' => 'success',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    public static function error($message = 'Error', $code = 400, $errors = null) {
        http_response_code($code);
        return json_encode([
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    public static function paginated($data, $total, $page, $per_page, $message = 'Success') {
        http_response_code(200);
        return json_encode([
            'status' => 'success',
            'code' => 200,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page)
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

/**
 * API Authentication Helper
 * Now supports both API Keys and JWT tokens
 */
class APIAuth {
    
    /**
     * Validate API key (from database)
     */
    public static function validateKey() {
        return APIKeyManager::validateKey();
    }

    /**
     * Check permission for API key
     */
    public static function checkPermission($required_permission, $api_key_data) {
        if (!APIKeyManager::checkPermission($required_permission, $api_key_data)) {
            die(APIResponse::error('Permission denied', 403));
        }
    }

    /**
     * Validate JWT token
     */
    public static function validateToken($token = null) {
        if ($token === null) {
            $token = JWTHelper::getTokenFromRequest();
        }
        
        if (!$token) {
            return [
                'valid' => false,
                'error' => 'No token provided'
            ];
        }
        
        return JWTHelper::verifyToken($token);
    }
    
    /**
     * Get current user from JWT token
     */
    public static function getCurrentUser() {
        $token_result = self::validateToken();
        
        if (!$token_result['valid']) {
            die(APIResponse::error('Unauthorized', 401));
        }
        
        return $token_result['payload'];
    }
}

/**
 * Rate Limiting Helper
 */
class RateLimiter {
    public static function check($identifier) {
        $cache_key = 'api_rate_' . md5($identifier);
        $current_count = apcu_fetch($cache_key);

        if ($current_count === false) {
            apcu_store($cache_key, 1, RATE_LIMIT_WINDOW);
            return true;
        }

        if ($current_count >= RATE_LIMIT_REQUESTS) {
            http_response_code(429);
            die(json_encode([
                'status' => 'error',
                'code' => 429,
                'message' => 'Rate limit exceeded',
                'retry_after' => RATE_LIMIT_WINDOW
            ]));
        }

        apcu_increment($cache_key);
        return true;
    }
}

/**
 * Input Validation Helper
 */
class Validator {
    public static function required($data, $fields) {
        $errors = [];
        foreach ($fields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }
        return $errors;
    }

    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function integer($value) {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    public static function string($value, $min = 1, $max = 255) {
        $length = strlen($value);
        return $length >= $min && $length <= $max;
    }
}

?>
