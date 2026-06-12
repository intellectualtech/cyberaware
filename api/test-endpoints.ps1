# CyberAware API - Testing Script (PowerShell)
# This script tests all API endpoints using Invoke-WebRequest

$BASE_URL = "http://localhost/cyberaware-new-Edits/api"
$DEMO_KEY = "demo_key_12345"
$ADMIN_KEY = "admin_key_67890"

# Test counters
$TOTAL = 0
$PASSED = 0
$FAILED = 0
$results = @()

# Function to test endpoint
function Test-Endpoint {
    param(
        [string]$name,
        [string]$method,
        [string]$endpoint,
        [object]$data,
        [string]$api_key,
        [int]$expected_code
    )
    
    $script:TOTAL++
    
    try {
        $headers = @{
            "Content-Type" = "application/json"
        }
        
        if ($api_key) {
            $headers["X-API-Key"] = $api_key
        }
        
        $url = "$BASE_URL$endpoint"
        
        $params = @{
            Uri = $url
            Method = $method
            Headers = $headers
            ErrorAction = "SilentlyContinue"
            UseBasicParsing = $true
        }
        
        if ($data) {
            $params["Body"] = $data | ConvertTo-Json -Depth 10
        }
        
        $response = Invoke-WebRequest @params
        $http_code = $response.StatusCode
        $body = $response.Content
    }
    catch {
        if ($_.Exception.Response) {
            $http_code = $_.Exception.Response.StatusCode.Value__
            $body = $_.Exception.Response.Content
        }
        else {
            $http_code = 0
            $body = $_.Exception.Message
        }
    }
    
    $passed = $http_code -eq $expected_code
    
    if ($passed) {
        Write-Host "PASS" -ForegroundColor Green -NoNewline
        $script:PASSED++
    }
    else {
        Write-Host "FAIL" -ForegroundColor Red -NoNewline
        $script:FAILED++
    }
    
    $output = " | $method $endpoint | Expected: $expected_code, Got: $http_code"
    Write-Host $output
    
    $results += @{
        name = $name
        method = $method
        endpoint = $endpoint
        expected = $expected_code
        actual = $http_code
        passed = $passed
    }
}

# Print title
Write-Host ""
Write-Host "CyberAware API - Testing Suite" -ForegroundColor Cyan
Write-Host "Date: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Cyan
Write-Host ""

# ============================================================================
# 1. AUTHENTICATION ENDPOINTS
# ============================================================================

Write-Host ""
Write-Host "1. AUTHENTICATION ENDPOINTS" -ForegroundColor Blue
Write-Host ""

Test-Endpoint "Login - Valid Credentials" "POST" "/auth/login" `
    @{username="trainee"; password="password123"} "" 200

Test-Endpoint "Login - Invalid Credentials" "POST" "/auth/login" `
    @{username="trainee"; password="wrongpassword"} "" 401

$timestamp = [int][double]::Parse((Get-Date -UFormat %s))
Test-Endpoint "Register - New User" "POST" "/auth/register" `
    @{username="testuser_$timestamp"; email="test_$timestamp@example.com"; password="SecurePass123"; full_name="Test User"} "" 201

Test-Endpoint "Register - Duplicate Username" "POST" "/auth/register" `
    @{username="trainee"; email="another@example.com"; password="SecurePass123"; full_name="Another User"} "" 409

Test-Endpoint "Logout" "POST" "/auth/logout" @{} "" 200

# ============================================================================
# 2. MODULES ENDPOINTS
# ============================================================================

Write-Host ""
Write-Host "2. MODULES ENDPOINTS" -ForegroundColor Blue
Write-Host ""

Test-Endpoint "Get All Modules" "GET" "/modules?page=1&per_page=10" $null $DEMO_KEY 200

Test-Endpoint "Get Specific Module" "GET" "/modules/1" $null $DEMO_KEY 200

Test-Endpoint "Get Non-existent Module" "GET" "/modules/999" $null $DEMO_KEY 404

$timestamp = [int][double]::Parse((Get-Date -UFormat %s))
Test-Endpoint "Create Module (Admin)" "POST" "/modules" `
    @{code="TEST_$timestamp"; title="Test Module"; description="Test"; category="Testing"; difficulty=2; estimated_minutes=45} $ADMIN_KEY 201

$timestamp = [int][double]::Parse((Get-Date -UFormat %s))
Test-Endpoint "Create Module - Insufficient Permissions" "POST" "/modules" `
    @{code="TEST_$timestamp"; title="Test Module"; description="Test"; category="Testing"; difficulty=2; estimated_minutes=45} $DEMO_KEY 403

Test-Endpoint "Update Module (Admin)" "PUT" "/modules/1" `
    @{title="Updated Title"; difficulty=3} $ADMIN_KEY 200

# ============================================================================
# 3. USERS ENDPOINTS
# ============================================================================

Write-Host ""
Write-Host "3. USERS ENDPOINTS" -ForegroundColor Blue
Write-Host ""

Test-Endpoint "Get User Profile" "GET" "/users/2" $null $DEMO_KEY 200

Test-Endpoint "Get Non-existent User" "GET" "/users/999" $null $DEMO_KEY 404

Test-Endpoint "Get User Progress" "GET" "/users/2/progress" $null $DEMO_KEY 200

Test-Endpoint "Get User Modules" "GET" "/users/2/modules" $null $DEMO_KEY 200

Test-Endpoint "Update User Profile (Admin)" "PUT" "/users/2" `
    @{full_name="Updated Name"; email="updated@example.com"} $ADMIN_KEY 200

# ============================================================================
# 4. LEADERBOARD ENDPOINTS
# ============================================================================

Write-Host ""
Write-Host "4. LEADERBOARD ENDPOINTS" -ForegroundColor Blue
Write-Host ""

Test-Endpoint "Get Leaderboard (All Time)" "GET" "/leaderboard?filter=all_time&sort=xp&limit=10" $null $DEMO_KEY 200

Test-Endpoint "Get Leaderboard (This Week)" "GET" "/leaderboard?filter=this_week&sort=accuracy&limit=10" $null $DEMO_KEY 200

Test-Endpoint "Get Leaderboard (This Month)" "GET" "/leaderboard?filter=this_month&sort=modules&limit=10" $null $DEMO_KEY 200

# ============================================================================
# 5. PROGRESS ENDPOINTS
# ============================================================================

Write-Host ""
Write-Host "5. PROGRESS ENDPOINTS" -ForegroundColor Blue
Write-Host ""

Test-Endpoint "Update Module Progress" "POST" "/progress/1" `
    @{user_id=2; score=85} $ADMIN_KEY 200

Test-Endpoint "Get User Progress" "GET" "/progress/2" $null $DEMO_KEY 200

# ============================================================================
# 6. ADMIN ENDPOINTS
# ============================================================================

Write-Host ""
Write-Host "6. ADMIN ENDPOINTS" -ForegroundColor Blue
Write-Host ""

Test-Endpoint "Get All Users (Admin)" "GET" "/admin/users?page=1&per_page=20" $null $ADMIN_KEY 200

Test-Endpoint "Get All Users - Insufficient Permissions" "GET" "/admin/users?page=1&per_page=20" $null $DEMO_KEY 403

Test-Endpoint "Get Platform Statistics" "GET" "/admin/stats" $null $ADMIN_KEY 200

$timestamp = [int][double]::Parse((Get-Date -UFormat %s))
Test-Endpoint "Create User (Admin)" "POST" "/admin/users" `
    @{username="admintest_$timestamp"; email="admintest_$timestamp@example.com"; password="SecurePass123"; full_name="Admin Test"; role="trainee"} $ADMIN_KEY 201

# ============================================================================
# 7. ERROR HANDLING TESTS
# ============================================================================

Write-Host ""
Write-Host "7. ERROR HANDLING TESTS" -ForegroundColor Blue
Write-Host ""

Test-Endpoint "Missing API Key" "GET" "/modules" $null "" 401

Test-Endpoint "Invalid API Key" "GET" "/modules" $null "invalid_key_12345" 401

# ============================================================================
# SUMMARY
# ============================================================================

Write-Host ""
Write-Host "TEST SUMMARY" -ForegroundColor Blue
Write-Host ""

Write-Host "Total Tests: $TOTAL" -ForegroundColor Cyan
Write-Host "Passed: $PASSED" -ForegroundColor Green
Write-Host "Failed: $FAILED" -ForegroundColor Red

if ($TOTAL -gt 0) {
    $PASS_RATE = [math]::Round(($PASSED / $TOTAL) * 100, 2)
    Write-Host "Pass Rate: $PASS_RATE%" -ForegroundColor Blue
}

Write-Host ""

if ($FAILED -eq 0) {
    Write-Host "ALL TESTS PASSED!" -ForegroundColor Green
}
else {
    Write-Host "SOME TESTS FAILED" -ForegroundColor Red
    Write-Host ""
    Write-Host "Failed Tests:" -ForegroundColor Yellow
    foreach ($result in $results) {
        if (-not $result.passed) {
            Write-Host "  - $($result.name)" -ForegroundColor Yellow
        }
    }
}

Write-Host ""

# Export results to JSON
$results | ConvertTo-Json | Out-File -FilePath "api/test-results.json" -Encoding UTF8
Write-Host "Test results saved to: api/test-results.json" -ForegroundColor Green

exit $FAILED
