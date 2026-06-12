#!/bin/bash

# CyberAware API - Testing Script
# This script tests all API endpoints using cURL

BASE_URL="http://localhost/cyberaware/api"
DEMO_KEY="demo_key_12345"
ADMIN_KEY="admin_key_67890"

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Test counters
TOTAL=0
PASSED=0
FAILED=0

# Function to print header
print_header() {
    echo -e "\n${CYAN}════════════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${CYAN}════════════════════════════════════════════════════════════════════${NC}\n"
}

# Function to test endpoint
test_endpoint() {
    local name=$1
    local method=$2
    local endpoint=$3
    local data=$4
    local api_key=$5
    local expected_code=$6
    
    TOTAL=$((TOTAL + 1))
    
    # Build curl command
    local cmd="curl -s -w '\n%{http_code}' -X $method"
    
    if [ ! -z "$api_key" ]; then
        cmd="$cmd -H 'X-API-Key: $api_key'"
    fi
    
    cmd="$cmd -H 'Content-Type: application/json'"
    
    if [ ! -z "$data" ]; then
        cmd="$cmd -d '$data'"
    fi
    
    cmd="$cmd '$BASE_URL$endpoint'"
    
    # Execute request
    response=$(eval $cmd)
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    
    # Check result
    if [ "$http_code" = "$expected_code" ]; then
        echo -e "${GREEN}✓ PASS${NC} | $method $endpoint | Expected: $expected_code, Got: $http_code"
        PASSED=$((PASSED + 1))
    else
        echo -e "${RED}✗ FAIL${NC} | $method $endpoint | Expected: $expected_code, Got: $http_code"
        FAILED=$((FAILED + 1))
        echo "  Response: $body"
    fi
}

# Print title
echo -e "${CYAN}"
echo "╔════════════════════════════════════════════════════════════════════╗"
echo "║         CyberAware API - Testing Suite                             ║"
echo "║         Date: $(date '+%Y-%m-%d %H:%M:%S')                                    ║"
echo "╚════════════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

# ============================================================================
# 1. AUTHENTICATION ENDPOINTS
# ============================================================================

print_header "1. AUTHENTICATION ENDPOINTS"

test_endpoint "Login - Valid Credentials" "POST" "/auth/login" \
    '{"username":"trainee","password":"password123"}' "" "200"

test_endpoint "Login - Invalid Credentials" "POST" "/auth/login" \
    '{"username":"trainee","password":"wrongpassword"}' "" "401"

test_endpoint "Register - New User" "POST" "/auth/register" \
    "{\"username\":\"testuser_$(date +%s)\",\"email\":\"test_$(date +%s)@example.com\",\"password\":\"SecurePass123\",\"full_name\":\"Test User\"}" "" "201"

test_endpoint "Register - Duplicate Username" "POST" "/auth/register" \
    '{"username":"trainee","email":"another@example.com","password":"SecurePass123","full_name":"Another User"}' "" "409"

test_endpoint "Logout" "POST" "/auth/logout" '{}' "" "200"

# ============================================================================
# 2. MODULES ENDPOINTS
# ============================================================================

print_header "2. MODULES ENDPOINTS"

test_endpoint "Get All Modules" "GET" "/modules?page=1&per_page=10" "" "$DEMO_KEY" "200"

test_endpoint "Get Specific Module" "GET" "/modules/1" "" "$DEMO_KEY" "200"

test_endpoint "Get Non-existent Module" "GET" "/modules/999" "" "$DEMO_KEY" "404"

test_endpoint "Create Module (Admin)" "POST" "/modules" \
    "{\"code\":\"TEST_$(date +%s)\",\"title\":\"Test Module\",\"description\":\"Test\",\"category\":\"Testing\",\"difficulty\":2,\"estimated_minutes\":45}" "$ADMIN_KEY" "201"

test_endpoint "Create Module - Insufficient Permissions" "POST" "/modules" \
    "{\"code\":\"TEST_$(date +%s)\",\"title\":\"Test Module\",\"description\":\"Test\",\"category\":\"Testing\",\"difficulty\":2,\"estimated_minutes\":45}" "$DEMO_KEY" "403"

test_endpoint "Update Module (Admin)" "PUT" "/modules/1" \
    '{"title":"Updated Title","difficulty":3}' "$ADMIN_KEY" "200"

# ============================================================================
# 3. USERS ENDPOINTS
# ============================================================================

print_header "3. USERS ENDPOINTS"

test_endpoint "Get User Profile" "GET" "/users/2" "" "$DEMO_KEY" "200"

test_endpoint "Get Non-existent User" "GET" "/users/999" "" "$DEMO_KEY" "404"

test_endpoint "Get User Progress" "GET" "/users/2/progress" "" "$DEMO_KEY" "200"

test_endpoint "Get User Modules" "GET" "/users/2/modules" "" "$DEMO_KEY" "200"

test_endpoint "Update User Profile (Admin)" "PUT" "/users/2" \
    '{"full_name":"Updated Name","email":"updated@example.com"}' "$ADMIN_KEY" "200"

# ============================================================================
# 4. LEADERBOARD ENDPOINTS
# ============================================================================

print_header "4. LEADERBOARD ENDPOINTS"

test_endpoint "Get Leaderboard (All Time)" "GET" "/leaderboard?filter=all_time&sort=xp&limit=10" "" "$DEMO_KEY" "200"

test_endpoint "Get Leaderboard (This Week)" "GET" "/leaderboard?filter=this_week&sort=accuracy&limit=10" "" "$DEMO_KEY" "200"

test_endpoint "Get Leaderboard (This Month)" "GET" "/leaderboard?filter=this_month&sort=modules&limit=10" "" "$DEMO_KEY" "200"

# ============================================================================
# 5. PROGRESS ENDPOINTS
# ============================================================================

print_header "5. PROGRESS ENDPOINTS"

test_endpoint "Update Module Progress" "POST" "/progress/1" \
    '{"user_id":2,"score":85}' "$ADMIN_KEY" "200"

test_endpoint "Get User Progress" "GET" "/progress/2" "" "$DEMO_KEY" "200"

# ============================================================================
# 6. ADMIN ENDPOINTS
# ============================================================================

print_header "6. ADMIN ENDPOINTS"

test_endpoint "Get All Users (Admin)" "GET" "/admin/users?page=1&per_page=20" "" "$ADMIN_KEY" "200"

test_endpoint "Get All Users - Insufficient Permissions" "GET" "/admin/users?page=1&per_page=20" "" "$DEMO_KEY" "403"

test_endpoint "Get Platform Statistics" "GET" "/admin/stats" "" "$ADMIN_KEY" "200"

test_endpoint "Create User (Admin)" "POST" "/admin/users" \
    "{\"username\":\"admintest_$(date +%s)\",\"email\":\"admintest_$(date +%s)@example.com\",\"password\":\"SecurePass123\",\"full_name\":\"Admin Test\",\"role\":\"trainee\"}" "$ADMIN_KEY" "201"

# ============================================================================
# 7. ERROR HANDLING TESTS
# ============================================================================

print_header "7. ERROR HANDLING TESTS"

test_endpoint "Missing API Key" "GET" "/modules" "" "" "401"

test_endpoint "Invalid API Key" "GET" "/modules" "" "invalid_key_12345" "401"

# ============================================================================
# SUMMARY
# ============================================================================

print_header "TEST SUMMARY"

echo -e "Total Tests: ${CYAN}$TOTAL${NC}"
echo -e "Passed: ${GREEN}$PASSED${NC}"
echo -e "Failed: ${RED}$FAILED${NC}"

if [ $TOTAL -gt 0 ]; then
    PASS_RATE=$((PASSED * 100 / TOTAL))
    echo -e "Pass Rate: ${BLUE}$PASS_RATE%${NC}"
fi

echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ ALL TESTS PASSED!${NC}"
else
    echo -e "${RED}✗ SOME TESTS FAILED${NC}"
fi

echo -e "\n${CYAN}════════════════════════════════════════════════════════════════════${NC}\n"

exit $FAILED
