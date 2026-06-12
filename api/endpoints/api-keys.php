<?php
/**
 * API Keys Management Endpoints
 * 
 * GET    /api/api-keys              - List API keys (admin)
 * POST   /api/api-keys              - Create API key (admin)
 * DELETE /api/api-keys/{id}         - Revoke API key (admin)
 * GET    /api/api-keys/{id}/stats   - Get API key usage stats (admin)
 */

function handleAPIKeysRequest($method, $resource, $action, $api_key_data) {
    // Check admin permission
    APIAuth::checkPermission('write', $api_key_data);
    
    if ($method === 'GET') {
        if ($resource === '' || $resource === null) {
            listAPIKeys();
        } elseif ($action === 'stats') {
            getAPIKeyStats($resource);
        } else {
            die(APIResponse::error('Endpoint not found', 404));
        }
    }
    elseif ($method === 'POST') {
        if ($resource === '' || $resource === null) {
            createAPIKey();
        } else {
            die(APIResponse::error('Endpoint not found', 404));
        }
    }
    elseif ($method === 'DELETE') {
        if ($resource !== '' && $resource !== null) {
            revokeAPIKey($resource);
        } else {
            die(APIResponse::error('Endpoint not found', 404));
        }
    }
    else {
        die(APIResponse::error('Method not allowed', 405));
    }
}

/**
 * List all API keys
 */
function listAPIKeys() {
    try {
        $keys = APIKeyManager::listKeys();
        
        echo APIResponse::success($keys, 'API keys retrieved successfully', 200);
    } catch (Exception $e) {
        error_log("Error listing API keys: " . $e->getMessage());
        die(APIResponse::error('Failed to list API keys', 500));
    }
}

/**
 * Create new API key
 */
function createAPIKey() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    $errors = Validator::required($input, ['key_name']);
    if (!empty($errors)) {
        die(APIResponse::error('Validation failed', 400, $errors));
    }
    
    $key_name = sanitize($input['key_name']);
    $permissions = $input['permissions'] ?? ['read'];
    $user_id = $input['user_id'] ?? null;
    $expires_days = $input['expires_days'] ?? null;
    
    // Validate permissions
    $valid_permissions = ['read', 'write', 'delete'];
    foreach ($permissions as $perm) {
        if (!in_array($perm, $valid_permissions)) {
            die(APIResponse::error('Invalid permission: ' . $perm, 400));
        }
    }
    
    $result = APIKeyManager::createKey($key_name, $permissions, $user_id, $expires_days);
    
    if ($result['success']) {
        echo APIResponse::success($result, 'API key created successfully', 201);
    } else {
        die(APIResponse::error('Failed to create API key: ' . $result['error'], 500));
    }
}

/**
 * Revoke API key
 */
function revokeAPIKey($key_id) {
    // Validate key_id
    if (!is_numeric($key_id)) {
        die(APIResponse::error('Invalid key ID', 400));
    }
    
    $result = APIKeyManager::revokeKey($key_id);
    
    if ($result['success']) {
        echo APIResponse::success(null, $result['message'], 200);
    } else {
        die(APIResponse::error('Failed to revoke API key: ' . $result['error'], 500));
    }
}

/**
 * Get API key usage statistics
 */
function getAPIKeyStats($key_id) {
    // Validate key_id
    if (!is_numeric($key_id)) {
        die(APIResponse::error('Invalid key ID', 400));
    }
    
    $days = $_GET['days'] ?? 7;
    
    if (!is_numeric($days) || $days < 1 || $days > 365) {
        die(APIResponse::error('Invalid days parameter (must be 1-365)', 400));
    }
    
    $stats = APIKeyManager::getUsageStats($key_id, $days);
    
    if ($stats === null) {
        die(APIResponse::error('Failed to get usage statistics', 500));
    }
    
    echo APIResponse::success($stats, 'Usage statistics retrieved successfully', 200);
}

?>
