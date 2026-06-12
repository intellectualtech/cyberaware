<?php
/**
 * JWT Helper Class
 * 
 * Handles JWT token generation, validation, and refresh
 * Implements RFC 7519 JWT standard
 */

class JWTHelper {
    private static $secret_key = null;
    private static $algorithm = 'HS256';
    private static $token_expiry = 3600; // 1 hour
    private static $refresh_expiry = 604800; // 7 days
    
    /**
     * Initialize JWT helper with secret key
     */
    public static function init($secret_key = null) {
        if ($secret_key === null) {
            // Generate from environment or use default (should be in .env in production)
            $secret_key = getenv('JWT_SECRET_KEY') ?: 'your-secret-key-change-in-production-' . DB_NAME;
        }
        self::$secret_key = $secret_key;
    }
    
    /**
     * Generate JWT token for user
     */
    public static function generateToken($user_id, $username, $role, $email = null) {
        self::init();
        
        $now = time();
        $expires = $now + self::$token_expiry;
        
        // Create payload
        $payload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'aud' => 'cyberaware-api',
            'iat' => $now,
            'exp' => $expires,
            'nbf' => $now,
            'sub' => $user_id,
            'user_id' => $user_id,
            'username' => $username,
            'role' => $role,
            'email' => $email,
            'jti' => bin2hex(random_bytes(16)) // JWT ID for tracking
        ];
        
        // Encode token
        $token = self::encode($payload);
        
        // Store token in database
        self::storeToken($user_id, $token, $expires);
        
        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => self::$token_expiry,
            'refresh_token' => self::generateRefreshToken($user_id)
        ];
    }
    
    /**
     * Generate refresh token
     */
    public static function generateRefreshToken($user_id) {
        self::init();
        
        $now = time();
        $expires = $now + self::$refresh_expiry;
        
        $payload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'aud' => 'cyberaware-api',
            'iat' => $now,
            'exp' => $expires,
            'sub' => $user_id,
            'user_id' => $user_id,
            'type' => 'refresh',
            'jti' => bin2hex(random_bytes(16))
        ];
        
        $token = self::encode($payload);
        self::storeToken($user_id, $token, $expires, 'refresh');
        
        return $token;
    }
    
    /**
     * Verify and decode JWT token
     */
    public static function verifyToken($token) {
        self::init();
        
        try {
            // Decode token
            $payload = self::decode($token);
            
            // Check expiration
            if ($payload['exp'] < time()) {
                return [
                    'valid' => false,
                    'error' => 'Token expired',
                    'code' => 'TOKEN_EXPIRED'
                ];
            }
            
            // Check if token is revoked in database
            if (self::isTokenRevoked($token)) {
                return [
                    'valid' => false,
                    'error' => 'Token revoked',
                    'code' => 'TOKEN_REVOKED'
                ];
            }
            
            return [
                'valid' => true,
                'payload' => $payload
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
                'code' => 'INVALID_TOKEN'
            ];
        }
    }
    
    /**
     * Refresh token
     */
    public static function refreshToken($refresh_token) {
        self::init();
        
        try {
            $payload = self::decode($refresh_token);
            
            // Verify it's a refresh token
            if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
                return [
                    'valid' => false,
                    'error' => 'Invalid refresh token'
                ];
            }
            
            // Check expiration
            if ($payload['exp'] < time()) {
                return [
                    'valid' => false,
                    'error' => 'Refresh token expired'
                ];
            }
            
            // Generate new access token
            $user_id = $payload['user_id'];
            
            // Get user details from database
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT username, role, email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'valid' => false,
                    'error' => 'User not found'
                ];
            }
            
            // Generate new token
            $new_tokens = self::generateToken($user_id, $user['username'], $user['role'], $user['email']);
            
            return [
                'valid' => true,
                'tokens' => $new_tokens
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Revoke token
     */
    public static function revokeToken($token) {
        try {
            $pdo = getDBConnection();
            $token_hash = hash('sha256', $token);
            
            $stmt = $pdo->prepare("UPDATE jwt_tokens SET is_revoked = 1 WHERE token_hash = ?");
            $stmt->execute([$token_hash]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error revoking token: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Encode JWT token
     */
    private static function encode($payload) {
        // Header
        $header = [
            'alg' => self::$algorithm,
            'typ' => 'JWT'
        ];
        
        $header_encoded = self::base64UrlEncode(json_encode($header));
        $payload_encoded = self::base64UrlEncode(json_encode($payload));
        
        // Signature
        $signature_input = $header_encoded . '.' . $payload_encoded;
        $signature = hash_hmac('sha256', $signature_input, self::$secret_key, true);
        $signature_encoded = self::base64UrlEncode($signature);
        
        return $signature_input . '.' . $signature_encoded;
    }
    
    /**
     * Decode JWT token
     */
    private static function decode($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }
        
        list($header_encoded, $payload_encoded, $signature_encoded) = $parts;
        
        // Verify signature
        $signature_input = $header_encoded . '.' . $payload_encoded;
        $signature = hash_hmac('sha256', $signature_input, self::$secret_key, true);
        $signature_expected = self::base64UrlEncode($signature);
        
        if (!hash_equals($signature_encoded, $signature_expected)) {
            throw new Exception('Invalid token signature');
        }
        
        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payload_encoded), true);
        
        if ($payload === null) {
            throw new Exception('Invalid token payload');
        }
        
        return $payload;
    }
    
    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 4 - strlen($data) % 4));
    }
    
    /**
     * Store token in database
     */
    private static function storeToken($user_id, $token, $expires, $type = 'access') {
        try {
            $pdo = getDBConnection();
            $token_hash = hash('sha256', $token);
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt = $pdo->prepare("
                INSERT INTO jwt_tokens (user_id, token_hash, ip_address, user_agent, expires_at)
                VALUES (?, ?, ?, ?, FROM_UNIXTIME(?))
            ");
            $stmt->execute([$user_id, $token_hash, $ip_address, $user_agent, $expires]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error storing token: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if token is revoked
     */
    private static function isTokenRevoked($token) {
        try {
            $pdo = getDBConnection();
            $token_hash = hash('sha256', $token);
            
            $stmt = $pdo->prepare("SELECT is_revoked FROM jwt_tokens WHERE token_hash = ? LIMIT 1");
            $stmt->execute([$token_hash]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['is_revoked'] == 1;
        } catch (Exception $e) {
            error_log("Error checking token revocation: " . $e->getMessage());
            return true; // Assume revoked on error
        }
    }
    
    /**
     * Get token from request
     */
    public static function getTokenFromRequest() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.+)/', $auth_header, $matches)) {
                return $matches[1];
            }
        }
        
        return $_GET['token'] ?? null;
    }
    
    /**
     * Validate token from request
     */
    public static function validateRequestToken() {
        $token = self::getTokenFromRequest();
        
        if (!$token) {
            return [
                'valid' => false,
                'error' => 'No token provided',
                'code' => 'NO_TOKEN'
            ];
        }
        
        return self::verifyToken($token);
    }
}

// Initialize JWT helper
JWTHelper::init();

?>
