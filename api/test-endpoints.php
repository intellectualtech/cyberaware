<?php
/**
 * CyberAware API - Automated Testing Script
 * 
 * This script tests all API endpoints and generates a comprehensive report
 * 
 * Usage: php api/test-endpoints.php
 */

// Configuration
$BASE_URL = 'http://localhost/cyberaware/api';
$DEMO_KEY = 'demo_key_12345';
$ADMIN_KEY = 'admin_key_67890';

// Test results storage
$results = [
    'total_tests' => 0,
    'passed' => 0,
    'failed' => 0,
    'tests' => []
];

// Color codes for CLI output
$colors = [
    'reset' => "\033[0m",
    'green' => "\033[32m",
    'red' => "\033[31m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'cyan' => "\033[36m"
];

/**
 * Make HTTP request to API
 */
function makeRequest($method, $endpoint, $data = null, $headers = []) {
    global $BASE_URL, $DEMO_KEY;
    
    $url = $BASE_URL . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // Set default headers
    $default_headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    // Merge with provided headers
    $all_headers = array_merge($default_headers, $headers);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $all_headers);
    
    // Add request body if provided
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'status_code' => $http_code,
        'body' => $response,
        'error' => $error,
        'data' => json_decode($response, true)
    ];
}

/**
 * Record test result
 */
function recordTest($name, $method, $endpoint, $expected_code, $actual_code, $passed, $response = null) {
    global $results, $colors;
    
    $results['total_tests']++;
    if ($passed) {
        $results['passed']++;
        $status = $colors['green'] . '✓ PASS' . $colors['reset'];
    } else {
        $results['failed']++;
        $status = $colors['red'] . '✗ FAIL' . $colors['reset'];
    }
    
    $results['tests'][] = [
        'name' => $name,
        'method' => $method,
        'endpoint' => $endpoint,
        'expected_code' => $expected_code,
        'actual_code' => $actual_code,
        'passed' => $passed,
        'response' => $response
    ];
    
    echo sprintf(
        "%s | %s %s | Expected: %d, Got: %d\n",
        $status,
        $method,
        $endpoint,
        $expected_code,
        $actual_code
    );
}

/**
 * Print header
 */
function printHeader($title) {
    global $colors;
    echo "\n" . $colors['cyan'] . str_repeat('=', 70) . $colors['reset'] . "\n";
    echo $colors['blue'] . $title . $colors['reset'] . "\n";
    echo $colors['cyan'] . str_repeat('=', 70) . $colors['reset'] . "\n";
}

// ============================================================================
// START TESTING
// ============================================================================

echo $colors['cyan'] . "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║         CyberAware API - Automated Testing Suite                   ║\n";
echo "║         Date: " . date('Y-m-d H:i:s') . "                                    ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo $colors['reset'];

// ============================================================================
// 1. AUTHENTICATION ENDPOINTS
// ============================================================================

printHeader('1. AUTHENTICATION ENDPOINTS');

// Test 1.1: Login - Valid Credentials
$response = makeRequest('POST', '/auth/login', [
    'username' => 'trainee',
    'password' => 'password123'
]);
recordTest(
    'Login - Valid Credentials',
    'POST',
    '/auth/login',
    200,
    $response['status_code'],
    $response['status_code'] == 200 && isset($response['data']['data']['user_id']),
    $response['data']
);

// Test 1.2: Login - Invalid Credentials
$response = makeRequest('POST', '/auth/login', [
    'username' => 'trainee',
    'password' => 'wrongpassword'
]);
recordTest(
    'Login - Invalid Credentials',
    'POST',
    '/auth/login',
    401,
    $response['status_code'],
    $response['status_code'] == 401,
    $response['data']
);

// Test 1.3: Login - Missing Fields
$response = makeRequest('POST', '/auth/login', [
    'username' => 'trainee'
]);
recordTest(
    'Login - Missing Password',
    'POST',
    '/auth/login',
    400,
    $response['status_code'],
    $response['status_code'] == 400,
    $response['data']
);

// Test 1.4: Register - New User
$unique_username = 'testuser_' . time();
$response = makeRequest('POST', '/auth/register', [
    'username' => $unique_username,
    'email' => $unique_username . '@example.com',
    'password' => 'SecurePass123',
    'full_name' => 'Test User'
]);
recordTest(
    'Register - New User',
    'POST',
    '/auth/register',
    201,
    $response['status_code'],
    $response['status_code'] == 201 && isset($response['data']['data']['user_id']),
    $response['data']
);

// Test 1.5: Register - Duplicate Username
$response = makeRequest('POST', '/auth/register', [
    'username' => 'trainee',
    'email' => 'another@example.com',
    'password' => 'SecurePass123',
    'full_name' => 'Another User'
]);
recordTest(
    'Register - Duplicate Username',
    'POST',
    '/auth/register',
    409,
    $response['status_code'],
    $response['status_code'] == 409,
    $response['data']
);

// Test 1.6: Logout
$response = makeRequest('POST', '/auth/logout', []);
recordTest(
    'Logout',
    'POST',
    '/auth/logout',
    200,
    $response['status_code'],
    $response['status_code'] == 200,
    $response['data']
);

// ============================================================================
// 2. MODULES ENDPOINTS
// ============================================================================

printHeader('2. MODULES ENDPOINTS');

// Test 2.1: Get All Modules
$response = makeRequest('GET', '/modules?page=1&per_page=10', null, [
    'X-API-Key: ' . $DEMO_KEY
]);
recordTest(
    'Get All Modules',
    'GET',
    '/modules',
    200,
    $response['status_code'],
    $response['status_code'] == 200 && isset($response['data']['pagination']),
    $response['data']
);

// Test 2.2: Get Specific Module
$response = makeRequest('GET', '/modules/1', null, [
    'X-API-Key: ' . $DEMO_KEY
]);
recordTest(
    'Get Specific Module',
    'GET',
    '/modules/1',
    200,
    $response['status_code'],
    $response['status_code'] == 200 && isset($response['data']['data']['id']),
    $response['data']
);

// Test 2.3: Get Non-existent Module
$response = makeRequest('GET', '/modules/999', null, [
    'X-API-Key: ' . $DEMO_KEY
]);
recordTest(
    'Get Non-existent Module',
    'GET',
    '/modules/999',
    404,
    $response['status_code'],
    $response['status_code'] == 404,
    $response['data']
);

// Test 2.4: Create Module (Admin)
$response = makeRequest('POST', '/modules', [
    'code' => 'TEST_' . time(),
    'title' => 'Test Module ' . time(),
    'description' => 'This is a test module',
    'category' => 'Testing',
    'difficulty' => 2,
    'estimated_minutes' => 45
], [
    'X-API-Key: ' . $ADMIN_KEY
]);
recordTest(
    'Create Module (Admin)',
    'POST',
    '/modules',
    201,
    $response['status_code'],
    $response['status_code'] == 201 && isset($response['data']['data']['id']),
    $response['data']
);

// Test 2.5: Create Module - Insufficient Permissions
$response = makeRequest('POST', '/modules', [
    'code' => 'TEST_' . time(),
    'title' => 'Test Module',
    'description' => 'This is a test module',
    'category' => 'Testing',
    'difficulty' => 2,
    'estimated_minutes' => 45
], [
    'X-API-Key: ' . $DEMO_KEY
]);
recordTest(
    'Create Module - Insufficient Permissions',
    'POST',
    '/modules',
    403,
    $response['status_code'],
    $response['status_code'] == 403,
    $response['data']
);

// Test 2.6: Update Module (Admin)
$response = makeRequest('PUT', '/modules/1', [
    'title' => 'Updated Module Title',
    'difficulty' => 3
], [
    'X-API-Key: ' . $ADMIN_KEY
]);
recordTest(
    'Update Module (Admin)',
    'PUT',
    '/modules/1',
    200,
    $response['status_code'],
    $response['status_code'] == 200,
    $response['data']
);

// ============================================================================
// 3. USERS ENDPOINTS
// ============================================================================

printHeader('3. USERS ENDPOINTS');

// Test 3.1: Get User Profile
$response = makeRequest('GET', '/users/2', null, [
    'X-API-Key: ' . $DEMO_KEY
]);
recordTest(
    'Get User Profile',
    'GET',
    '/users/2',
    200,
    $response['status_code'],
    $response['status_code'] == 200 && isset($response['data']['data']['id']),
    $response['data']
);

// Test 3.2: Get Non-existent User
$response = makeRequest('GET', '/users/999', null, [
    'X-API-Key: ' . $DEMO_KEY
]);
recordTest(
    'Get Non-existent User',
    'GET',
    '/users/999',
    404,
    $response['status_code'],
    $response['status_code'] == 404,
    $response['data']
);

// Test 3.3: Get User Progress
$response = makeRequest('GET', '/users/2/progress', null, [
    'X-API-Key: ' . $DEMO_KEY
]);
recordTest(
    'Get User Progress',
    'GET',
    '/users/2/progress',
    200,
    $response['status_code'],
    $response['status_code'] == 200 && isset($response['data']['data']),
    $response['data']
);

// Test 3.4: Get User Modules
$response = makeRequest('GET', '/users/2/modules', null, [
    'X-API-Key: ' . $DEMO_KEY
]);
recordTest(
    'Get User Modules',
    'GET',
    '/users/2/modules',
    200,
    $response['status_code'],
    $response['status_code'] == 200 && is_array($response['data']['data']),
    $response['data']
);

// Test 3.5: Update User Profile (Admin)
$response = makeRequest('PUT', '/users/2', [
    'full_name' => 'Updated Name',
    'email' => 'updated@example.com'
], [
    'X-API-Key: ' . $ADMIN_KEY
]);
recordTest(
    'Update User Profile (Admin)',
    'PUT',
    '/users/2',
    200,
    $response['status_code'],
    $response['status_code'] == 200,
    $response['data']
);

// ============================================================================
// 4. LEADERBOARD ENDPOINTS
// ============================================================================

printHeader('4. LEADERBOARD ENDPOINTS');

// Test 4.1: Get Leaderboard (All Time)
$response = makeRequest('GET', '/leaderboard?filter=all_time&sort=xp&limit=10', null, [
    'X-API-Key: ' . $DEMO_KEY
]);
recordTest(
    'Get Leaderboard (All Time)',
    'GET',
    '/leaderboard',
    200,
    $response['status_code'],
    $response['status_code'] == 200 && isset($response['data']['data']),
    $response['data']
);

// Test 4.2: Get Leaderboard (This Week)
$response = makeRequest('GET', '/leaderboard?filter=this_week&sort=accuracy&limit=10', null, [
    'X-API-Key: ' . $DEMO_KEY
]);
recordTest(
    'Get Leaderboard (This Week)',
    'GET',
    '/leaderboard?filter=this_week',
    200,
    $response['status_code'],
    $response['status_code'] == 200,
    $response['data']
);

// Test 4.3: Get Leaderboard (This Month)
$response = makeRequest('GET', '/leaderboard?filter=this_month&sort=modules&limit=10', null, [
    'X-API-Key: ' . $DEMO_KEY
]);
recordTest(
    'Get Leaderboard (This Month)',
    'GET',
    '/leaderboard?filter=this_month',
    200,
    $response['status_code'],
    $response['status_code'] == 200,
    $response['data']
);

// ============================================================================
// 5. PROGRESS ENDPOINTS
// ============================================================================

printHeader('5. PROGRESS ENDPOINTS');

// Test 5.1: Update Module Progress
$response = makeRequest('POST', '/progress/1', [
    'user_id' => 2,
    'score' => 85
], [
    'X-API-Key: ' . $ADMIN_KEY
]);
recordTest(
    'Update Module Progress',
    'POST',
    '/progress/1',
    200,
    $response['status_code'],
    $response['status_code'] == 200,
    $response['data']
);

// Test 5.2: Get User Progress
$response = makeRequest('GET', '/progress/2', null, [
    'X-API-Key: ' . $DEMO_KEY
]);
recordTest(
    'Get User Progress',
    'GET',
    '/progress/2',
    200,
    $response['status_code'],
    $response['status_code'] == 200 && is_array($response['data']['data']),
    $response['data']
);

// ============================================================================
// 6. ADMIN ENDPOINTS
// ============================================================================

printHeader('6. ADMIN ENDPOINTS');

// Test 6.1: Get All Users (Admin)
$response = makeRequest('GET', '/admin/users?page=1&per_page=20', null, [
    'X-API-Key: ' . $ADMIN_KEY
]);
recordTest(
    'Get All Users (Admin)',
    'GET',
    '/admin/users',
    200,
    $response['status_code'],
    $response['status_code'] == 200 && isset($response['data']['pagination']),
    $response['data']
);

// Test 6.2: Get All Users - Insufficient Permissions
$response = makeRequest('GET', '/admin/users?page=1&per_page=20', null, [
    'X-API-Key: ' . $DEMO_KEY
]);
recordTest(
    'Get All Users - Insufficient Permissions',
    'GET',
    '/admin/users',
    403,
    $response['status_code'],
    $response['status_code'] == 403,
    $response['data']
);

// Test 6.3: Get Platform Statistics
$response = makeRequest('GET', '/admin/stats', null, [
    'X-API-Key: ' . $ADMIN_KEY
]);
recordTest(
    'Get Platform Statistics',
    'GET',
    '/admin/stats',
    200,
    $response['status_code'],
    $response['status_code'] == 200 && isset($response['data']['data']),
    $response['data']
);

// Test 6.4: Create User (Admin)
$response = makeRequest('POST', '/admin/users', [
    'username' => 'admintest_' . time(),
    'email' => 'admintest_' . time() . '@example.com',
    'password' => 'SecurePass123',
    'full_name' => 'Admin Test User',
    'role' => 'trainee'
], [
    'X-API-Key: ' . $ADMIN_KEY
]);
recordTest(
    'Create User (Admin)',
    'POST',
    '/admin/users',
    201,
    $response['status_code'],
    $response['status_code'] == 201 && isset($response['data']['data']['user_id']),
    $response['data']
);

// ============================================================================
// 7. ERROR HANDLING TESTS
// ============================================================================

printHeader('7. ERROR HANDLING TESTS');

// Test 7.1: Missing API Key
$response = makeRequest('GET', '/modules', null, []);
recordTest(
    'Missing API Key',
    'GET',
    '/modules',
    401,
    $response['status_code'],
    $response['status_code'] == 401,
    $response['data']
);

// Test 7.2: Invalid API Key
$response = makeRequest('GET', '/modules', null, [
    'X-API-Key: invalid_key_12345'
]);
recordTest(
    'Invalid API Key',
    'GET',
    '/modules',
    401,
    $response['status_code'],
    $response['status_code'] == 401,
    $response['data']
);

// Test 7.3: Invalid JSON
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $BASE_URL . '/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'invalid json');
$response_body = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

recordTest(
    'Invalid JSON',
    'POST',
    '/auth/login',
    400,
    $http_code,
    $http_code == 400,
    json_decode($response_body, true)
);

// ============================================================================
// SUMMARY
// ============================================================================

printHeader('TEST SUMMARY');

echo "\n";
echo $colors['cyan'] . "Total Tests: " . $colors['reset'] . $results['total_tests'] . "\n";
echo $colors['green'] . "Passed: " . $colors['reset'] . $results['passed'] . "\n";
echo $colors['red'] . "Failed: " . $colors['reset'] . $results['failed'] . "\n";

$pass_rate = ($results['total_tests'] > 0) ? round(($results['passed'] / $results['total_tests']) * 100, 2) : 0;
echo $colors['blue'] . "Pass Rate: " . $colors['reset'] . $pass_rate . "%\n";

echo "\n";

if ($results['failed'] == 0) {
    echo $colors['green'] . "✓ ALL TESTS PASSED!" . $colors['reset'] . "\n";
} else {
    echo $colors['red'] . "✗ SOME TESTS FAILED" . $colors['reset'] . "\n";
    echo "\nFailed Tests:\n";
    foreach ($results['tests'] as $test) {
        if (!$test['passed']) {
            echo "  - " . $test['name'] . " (" . $test['method'] . " " . $test['endpoint'] . ")\n";
        }
    }
}

echo "\n" . $colors['cyan'] . str_repeat('=', 70) . $colors['reset'] . "\n";

// Generate JSON report
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_tests' => $results['total_tests'],
    'passed' => $results['passed'],
    'failed' => $results['failed'],
    'pass_rate' => $pass_rate,
    'tests' => $results['tests']
];

file_put_contents('api/test-results.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "\nTest results saved to: api/test-results.json\n";

?>
