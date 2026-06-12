<?php
/**
 * Authentication Endpoints
 * 
 * POST /api/auth/login      - User login
 * POST /api/auth/register   - User registration
 * POST /api/auth/logout     - User logout
 */

function handleAuthRequest($method, $resource) {
    if ($method !== 'POST') {
        die(APIResponse::error('Method not allowed', 405));
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if ($resource === 'login') {
        handleLogin($input);
    }
    elseif ($resource === 'register') {
        handleRegister($input);
    }
    elseif ($resource === 'logout') {
        handleLogout($input);
    }
    elseif ($resource === 'refresh') {
        handleRefresh($input);
    }
    else {
        die(APIResponse::error('Endpoint not found', 404));
    }
}

/**
 * Handle user login
 * Now returns JWT tokens instead of simple tokens
 */
function handleLogin($input) {
    // Validate input
    $errors = Validator::required($input, ['username', 'password']);
    if (!empty($errors)) {
        die(APIResponse::error('Validation failed', 400, $errors));
    }

    $username = sanitize($input['username']);
    $password = $input['password'];

    try {
        $pdo = getDBConnection();
        
        // Get user
        $stmt = $pdo->prepare("
            SELECT id, username, password_hash, full_name, role, email, is_active 
            FROM users 
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !verifyPassword($password, $user['password_hash'])) {
            die(APIResponse::error('Invalid credentials', 401));
        }

        if (!$user['is_active']) {
            die(APIResponse::error('Account suspended', 403));
        }

        // Generate JWT tokens
        $tokens = JWTHelper::generateToken(
            $user['id'],
            $user['username'],
            $user['role'],
            $user['email']
        );

        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        $response = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'email' => $user['email'],
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => $tokens['token_type'],
            'expires_in' => $tokens['expires_in']
        ];

        echo APIResponse::success($response, 'Login successful', 200);
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        die(APIResponse::error('Login failed', 500));
    }
}

/**
 * Handle user registration
 */
function handleRegister($input) {
    // Validate input
    $errors = Validator::required($input, ['username', 'email', 'password', 'full_name']);
    if (!empty($errors)) {
        die(APIResponse::error('Validation failed', 400, $errors));
    }

    // Validate email
    if (!Validator::email($input['email'])) {
        die(APIResponse::error('Invalid email format', 400));
    }

    // Validate password strength
    if (strlen($input['password']) < 8) {
        die(APIResponse::error('Password must be at least 8 characters', 400));
    }

    $username = sanitize($input['username']);
    $email = sanitize($input['email']);
    $password = $input['password'];
    $full_name = sanitize($input['full_name']);

    try {
        $pdo = getDBConnection();

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            die(APIResponse::error('Username or email already exists', 409));
        }

        // Create user
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, full_name, role, is_active, created_at)
            VALUES (?, ?, ?, ?, 'trainee', 1, NOW())
        ");
        $stmt->execute([$username, $email, $password_hash, $full_name]);

        $user_id = $pdo->lastInsertId();

        $response = [
            'user_id' => $user_id,
            'username' => $username,
            'email' => $email,
            'full_name' => $full_name,
            'role' => 'trainee'
        ];

        echo APIResponse::success($response, 'Registration successful', 201);
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        die(APIResponse::error('Registration failed', 500));
    }
}

/**
 * Handle user logout
 * Revokes the JWT token
 */
function handleLogout($input) {
    $token = JWTHelper::getTokenFromRequest();
    
    if ($token) {
        JWTHelper::revokeToken($token);
    }
    
    echo APIResponse::success(null, 'Logout successful', 200);
}

/**
 * Handle token refresh
 * Generates new access token from refresh token
 */
function handleRefresh($input) {
    // Validate input
    $errors = Validator::required($input, ['refresh_token']);
    if (!empty($errors)) {
        die(APIResponse::error('Validation failed', 400, $errors));
    }

    $refresh_token = $input['refresh_token'];
    
    $result = JWTHelper::refreshToken($refresh_token);
    
    if (!$result['valid']) {
        die(APIResponse::error($result['error'], 401));
    }
    
    echo APIResponse::success($result['tokens'], 'Token refreshed successfully', 200);
}

?>
