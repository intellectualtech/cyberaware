<?php
/**
 * CyberAware API - Main Router
 * 
 * Base URL: /api/
 * 
 * Usage:
 * GET    /api/modules                    - Get all training modules
 * GET    /api/modules/{id}               - Get specific module
 * GET    /api/users/{id}/progress        - Get user progress
 * GET    /api/users/{id}/modules         - Get user's modules
 * POST   /api/auth/login                 - User login
 * POST   /api/auth/register              - User registration
 * GET    /api/leaderboard                - Get leaderboard
 * POST   /api/modules/{id}/progress      - Update module progress
 */

require_once 'config.php';

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove the base path from the URL
$path = str_replace('/cyberaware-new-Edits/api', '', $path);
$path = str_replace('/cyberaware/api', '', $path);
$path = trim($path, '/');

// Parse path into segments
$segments = explode('/', $path);
$endpoint = $segments[0] ?? '';
$resource = $segments[1] ?? '';
$action = $segments[2] ?? '';

// Route requests
try {
    // Public endpoints (no API key required)
    if ($endpoint === 'auth') {
        require_once 'endpoints/auth.php';
        handleAuthRequest($method, $resource);
    }
    // Protected endpoints (API key required)
    else {
        $api_key_data = APIAuth::validateKey();
        
        // Rate limiting
        $client_ip = $_SERVER['REMOTE_ADDR'];
        RateLimiter::check($client_ip);

        if ($endpoint === 'modules') {
            require_once 'endpoints/modules.php';
            handleModulesRequest($method, $resource, $action, $api_key_data);
        }
        elseif ($endpoint === 'users') {
            require_once 'endpoints/users.php';
            handleUsersRequest($method, $resource, $action, $api_key_data);
        }
        elseif ($endpoint === 'leaderboard') {
            require_once 'endpoints/leaderboard.php';
            handleLeaderboardRequest($method, $api_key_data);
        }
        elseif ($endpoint === 'progress') {
            require_once 'endpoints/progress.php';
            handleProgressRequest($method, $resource, $action, $api_key_data);
        }
        elseif ($endpoint === 'admin') {
            require_once 'endpoints/admin.php';
            handleAdminRequest($method, $resource, $action, $api_key_data);
        }
        elseif ($endpoint === 'api-keys') {
            require_once 'endpoints/api-keys.php';
            handleAPIKeysRequest($method, $resource, $action, $api_key_data);
        }
        elseif ($endpoint === 'incidents') {
            require_once 'endpoints/incidents.php';
            handleIncidentsRequest($method, $resource, $action, $api_key_data);
        }
        else {
            die(APIResponse::error('Endpoint not found', 404));
        }
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    die(APIResponse::error('Internal server error', 500));
}

?>
