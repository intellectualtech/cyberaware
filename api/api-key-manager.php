<?php
/**
 * API Key Manager Class
 * 
 * Manages API keys stored in database
 * Replaces hardcoded API keys with database-managed keys
 */

class APIKeyManager {
    
    /**
     * Validate API key from request
     */
    public static function validateKey() {
        $headers = getallheaders();
        $api_key = $headers['X-API-Key'] ?? $_GET['api_key'] ?? null;

        if (!$api_key) {
            die(APIResponse::error('API key required', 401));
        }

        $key_data = self::getKeyData($api_key);
        
        if (!$key_data) {
            die(APIResponse::error('Invalid API key', 401));
        }
        
        // Check if key is active
        if (!$key_data['is_active']) {
            die(APIResponse::error('API key is inactive', 401));
        }
        
        // Check if key has expired
        if ($key_data['expires_at'] && strtotime($key_data['expires_at']) < time()) {
            die(APIResponse::error('API key has expired', 401));
        }
        
        // Update last used time
        self::updateLastUsed($key_data['id']);
        
        return $key_data;
    }
    
    /**
     * Get API key data from database
     */
    public static function getKeyData($api_key) {
        try {
            $pdo = getDBConnection();
            $key_hash = hash('sha256', $api_key);
            
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    key_name,
                    description,
                    user_id,
                    permissions,
                    rate_limit,
                    rate_limit_window,
                    is_active,
                    last_used_at,
                    expires_at
                FROM api_keys
                WHERE key_hash = ?
                LIMIT 1
            ");
            $stmt->execute([$key_hash]);
            $key = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($key) {
                $key['permissions'] = json_decode($key['permissions'], true);
            }
            
            return $key;
        } catch (Exception $e) {
            error_log("Error getting API key: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if key has permission
     */
    public static function checkPermission($required_permission, $key_data) {
        if (!isset($key_data['permissions']) || !is_array($key_data['permissions'])) {
            return false;
        }
        
        return in_array($required_permission, $key_data['permissions']);
    }
    
    /**
     * Create new API key
     */
    public static function createKey($key_name, $permissions = ['read'], $user_id = null, $expires_days = null) {
        try {
            $pdo = getDBConnection();
            
            // Generate random key
            $api_key = 'sk_' . bin2hex(random_bytes(32));
            $key_hash = hash('sha256', $api_key);
            
            $expires_at = null;
            if ($expires_days) {
                $expires_at = date('Y-m-d H:i:s', strtotime("+$expires_days days"));
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO api_keys (key_hash, key_name, user_id, permissions, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $key_hash,
                $key_name,
                $user_id,
                json_encode($permissions),
                $expires_at
            ]);
            
            return [
                'success' => true,
                'api_key' => $api_key,
                'key_name' => $key_name,
                'message' => 'API key created successfully. Save this key securely - you won\'t be able to see it again.'
            ];
        } catch (Exception $e) {
            error_log("Error creating API key: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Revoke API key
     */
    public static function revokeKey($key_id) {
        try {
            $pdo = getDBConnection();
            
            $stmt = $pdo->prepare("UPDATE api_keys SET is_active = 0 WHERE id = ?");
            $stmt->execute([$key_id]);
            
            return [
                'success' => true,
                'message' => 'API key revoked successfully'
            ];
        } catch (Exception $e) {
            error_log("Error revoking API key: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update last used time
     */
    private static function updateLastUsed($key_id) {
        try {
            $pdo = getDBConnection();
            
            $stmt = $pdo->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?");
            $stmt->execute([$key_id]);
        } catch (Exception $e) {
            error_log("Error updating last used time: " . $e->getMessage());
        }
    }
    
    /**
     * Log API usage
     */
    public static function logUsage($key_id, $endpoint, $method, $status_code, $response_time_ms) {
        try {
            $pdo = getDBConnection();
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt = $pdo->prepare("
                INSERT INTO api_key_usage_log (api_key_id, endpoint, method, status_code, response_time_ms, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $key_id,
                $endpoint,
                $method,
                $status_code,
                $response_time_ms,
                $ip_address,
                $user_agent
            ]);
        } catch (Exception $e) {
            error_log("Error logging API usage: " . $e->getMessage());
        }
    }
    
    /**
     * Get API key usage statistics
     */
    public static function getUsageStats($key_id, $days = 7) {
        try {
            $pdo = getDBConnection();
            
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_requests,
                    AVG(response_time_ms) as avg_response_time,
                    MIN(response_time_ms) as min_response_time,
                    MAX(response_time_ms) as max_response_time,
                    SUM(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 ELSE 0 END) as successful_requests,
                    SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as failed_requests
                FROM api_key_usage_log
                WHERE api_key_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$key_id, $days]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting usage stats: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * List all API keys (admin only)
     */
    public static function listKeys($user_id = null) {
        try {
            $pdo = getDBConnection();
            
            if ($user_id) {
                $stmt = $pdo->prepare("
                    SELECT 
                        id,
                        key_name,
                        description,
                        permissions,
                        is_active,
                        last_used_at,
                        expires_at,
                        created_at
                    FROM api_keys
                    WHERE user_id = ?
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$user_id]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT 
                        id,
                        key_name,
                        description,
                        user_id,
                        permissions,
                        is_active,
                        last_used_at,
                        expires_at,
                        created_at
                    FROM api_keys
                    ORDER BY created_at DESC
                ");
                $stmt->execute();
            }
            
            $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($keys as &$key) {
                $key['permissions'] = json_decode($key['permissions'], true);
                // Don't return the actual key hash
                unset($key['key_hash']);
            }
            
            return $keys;
        } catch (Exception $e) {
            error_log("Error listing API keys: " . $e->getMessage());
            return [];
        }
    }
}

?>
