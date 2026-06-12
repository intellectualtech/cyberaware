@echo off
REM CyberAware API Testing Script

setlocal enabledelayedexpansion

set BASE_URL=http://localhost/cyberaware-new-Edits/api
set DEMO_KEY=demo_key_12345
set ADMIN_KEY=admin_key_67890

set TOTAL=0
set PASSED=0
set FAILED=0

echo.
echo ========================================================================
echo         CyberAware API - Testing Suite
echo         Date: %date% %time%
echo ========================================================================
echo.

REM ========================================================================
REM 1. AUTHENTICATION ENDPOINTS
REM ========================================================================

echo.
echo 1. AUTHENTICATION ENDPOINTS
echo.

REM Test 1.1: Login - Valid Credentials
set /a TOTAL+=1
for /f "tokens=*" %%A in ('curl -s -w "%%{http_code}" -X POST "%BASE_URL%/auth/login" -H "Content-Type: application/json" -d "{\"username\":\"trainee\",\"password\":\"password123\"}" 2^>nul') do (
    set RESPONSE=%%A
)
set HTTP_CODE=!RESPONSE:~-3!
if "!HTTP_CODE!"=="200" (
    echo PASS ^| POST /auth/login ^| Expected: 200, Got: !HTTP_CODE!
    set /a PASSED+=1
) else (
    echo FAIL ^| POST /auth/login ^| Expected: 200, Got: !HTTP_CODE!
    set /a FAILED+=1
)

REM Test 1.2: Login - Invalid Credentials
set /a TOTAL+=1
for /f "tokens=*" %%A in ('curl -s -w "%%{http_code}" -X POST "%BASE_URL%/auth/login" -H "Content-Type: application/json" -d "{\"username\":\"trainee\",\"password\":\"wrongpassword\"}" 2^>nul') do (
    set RESPONSE=%%A
)
set HTTP_CODE=!RESPONSE:~-3!
if "!HTTP_CODE!"=="401" (
    echo PASS ^| POST /auth/login ^| Expected: 401, Got: !HTTP_CODE!
    set /a PASSED+=1
) else (
    echo FAIL ^| POST /auth/login ^| Expected: 401, Got: !HTTP_CODE!
    set /a FAILED+=1
)

REM ========================================================================
REM 2. MODULES ENDPOINTS
REM ========================================================================

echo.
echo 2. MODULES ENDPOINTS
echo.

REM Test 2.1: Get All Modules
set /a TOTAL+=1
for /f "tokens=*" %%A in ('curl -s -w "%%{http_code}" -X GET "%BASE_URL%/modules?page=1^&per_page=10" -H "X-API-Key: %DEMO_KEY%" 2^>nul') do (
    set RESPONSE=%%A
)
set HTTP_CODE=!RESPONSE:~-3!
if "!HTTP_CODE!"=="200" (
    echo PASS ^| GET /modules ^| Expected: 200, Got: !HTTP_CODE!
    set /a PASSED+=1
) else (
    echo FAIL ^| GET /modules ^| Expected: 200, Got: !HTTP_CODE!
    set /a FAILED+=1
)

REM Test 2.2: Get Specific Module
set /a TOTAL+=1
for /f "tokens=*" %%A in ('curl -s -w "%%{http_code}" -X GET "%BASE_URL%/modules/1" -H "X-API-Key: %DEMO_KEY%" 2^>nul') do (
    set RESPONSE=%%A
)
set HTTP_CODE=!RESPONSE:~-3!
if "!HTTP_CODE!"=="200" (
    echo PASS ^| GET /modules/1 ^| Expected: 200, Got: !HTTP_CODE!
    set /a PASSED+=1
) else (
    echo FAIL ^| GET /modules/1 ^| Expected: 200, Got: !HTTP_CODE!
    set /a FAILED+=1
)

REM Test 2.3: Get Non-existent Module
set /a TOTAL+=1
for /f "tokens=*" %%A in ('curl -s -w "%%{http_code}" -X GET "%BASE_URL%/modules/999" -H "X-API-Key: %DEMO_KEY%" 2^>nul') do (
    set RESPONSE=%%A
)
set HTTP_CODE=!RESPONSE:~-3!
if "!HTTP_CODE!"=="404" (
    echo PASS ^| GET /modules/999 ^| Expected: 404, Got: !HTTP_CODE!
    set /a PASSED+=1
) else (
    echo FAIL ^| GET /modules/999 ^| Expected: 404, Got: !HTTP_CODE!
    set /a FAILED+=1
)

REM ========================================================================
REM 3. USERS ENDPOINTS
REM ========================================================================

echo.
echo 3. USERS ENDPOINTS
echo.

REM Test 3.1: Get User Profile
set /a TOTAL+=1
for /f "tokens=*" %%A in ('curl -s -w "%%{http_code}" -X GET "%BASE_URL%/users/2" -H "X-API-Key: %DEMO_KEY%" 2^>nul') do (
    set RESPONSE=%%A
)
set HTTP_CODE=!RESPONSE:~-3!
if "!HTTP_CODE!"=="200" (
    echo PASS ^| GET /users/2 ^| Expected: 200, Got: !HTTP_CODE!
    set /a PASSED+=1
) else (
    echo FAIL ^| GET /users/2 ^| Expected: 200, Got: !HTTP_CODE!
    set /a FAILED+=1
)

REM Test 3.2: Get Non-existent User
set /a TOTAL+=1
for /f "tokens=*" %%A in ('curl -s -w "%%{http_code}" -X GET "%BASE_URL%/users/999" -H "X-API-Key: %DEMO_KEY%" 2^>nul') do (
    set RESPONSE=%%A
)
set HTTP_CODE=!RESPONSE:~-3!
if "!HTTP_CODE!"=="404" (
    echo PASS ^| GET /users/999 ^| Expected: 404, Got: !HTTP_CODE!
    set /a PASSED+=1
) else (
    echo FAIL ^| GET /users/999 ^| Expected: 404, Got: !HTTP_CODE!
    set /a FAILED+=1
)

REM ========================================================================
REM 4. LEADERBOARD ENDPOINTS
REM ========================================================================

echo.
echo 4. LEADERBOARD ENDPOINTS
echo.

REM Test 4.1: Get Leaderboard
set /a TOTAL+=1
for /f "tokens=*" %%A in ('curl -s -w "%%{http_code}" -X GET "%BASE_URL%/leaderboard?filter=all_time^&sort=xp^&limit=10" -H "X-API-Key: %DEMO_KEY%" 2^>nul') do (
    set RESPONSE=%%A
)
set HTTP_CODE=!RESPONSE:~-3!
if "!HTTP_CODE!"=="200" (
    echo PASS ^| GET /leaderboard ^| Expected: 200, Got: !HTTP_CODE!
    set /a PASSED+=1
) else (
    echo FAIL ^| GET /leaderboard ^| Expected: 200, Got: !HTTP_CODE!
    set /a FAILED+=1
)

REM ========================================================================
REM 5. ADMIN ENDPOINTS
REM ========================================================================

echo.
echo 5. ADMIN ENDPOINTS
echo.

REM Test 5.1: Get All Users (Admin)
set /a TOTAL+=1
for /f "tokens=*" %%A in ('curl -s -w "%%{http_code}" -X GET "%BASE_URL%/admin/users?page=1^&per_page=20" -H "X-API-Key: %ADMIN_KEY%" 2^>nul') do (
    set RESPONSE=%%A
)
set HTTP_CODE=!RESPONSE:~-3!
if "!HTTP_CODE!"=="200" (
    echo PASS ^| GET /admin/users ^| Expected: 200, Got: !HTTP_CODE!
    set /a PASSED+=1
) else (
    echo FAIL ^| GET /admin/users ^| Expected: 200, Got: !HTTP_CODE!
    set /a FAILED+=1
)

REM Test 5.2: Get All Users - Insufficient Permissions
set /a TOTAL+=1
for /f "tokens=*" %%A in ('curl -s -w "%%{http_code}" -X GET "%BASE_URL%/admin/users?page=1^&per_page=20" -H "X-API-Key: %DEMO_KEY%" 2^>nul') do (
    set RESPONSE=%%A
)
set HTTP_CODE=!RESPONSE:~-3!
if "!HTTP_CODE!"=="403" (
    echo PASS ^| GET /admin/users ^| Expected: 403, Got: !HTTP_CODE!
    set /a PASSED+=1
) else (
    echo FAIL ^| GET /admin/users ^| Expected: 403, Got: !HTTP_CODE!
    set /a FAILED+=1
)

REM Test 5.3: Get Platform Statistics
set /a TOTAL+=1
for /f "tokens=*" %%A in ('curl -s -w "%%{http_code}" -X GET "%BASE_URL%/admin/stats" -H "X-API-Key: %ADMIN_KEY%" 2^>nul') do (
    set RESPONSE=%%A
)
set HTTP_CODE=!RESPONSE:~-3!
if "!HTTP_CODE!"=="200" (
    echo PASS ^| GET /admin/stats ^| Expected: 200, Got: !HTTP_CODE!
    set /a PASSED+=1
) else (
    echo FAIL ^| GET /admin/stats ^| Expected: 200, Got: !HTTP_CODE!
    set /a FAILED+=1
)

REM ========================================================================
REM 6. ERROR HANDLING TESTS
REM ========================================================================

echo.
echo 6. ERROR HANDLING TESTS
echo.

REM Test 6.1: Missing API Key
set /a TOTAL+=1
for /f "tokens=*" %%A in ('curl -s -w "%%{http_code}" -X GET "%BASE_URL%/modules" 2^>nul') do (
    set RESPONSE=%%A
)
set HTTP_CODE=!RESPONSE:~-3!
if "!HTTP_CODE!"=="401" (
    echo PASS ^| GET /modules ^| Expected: 401, Got: !HTTP_CODE!
    set /a PASSED+=1
) else (
    echo FAIL ^| GET /modules ^| Expected: 401, Got: !HTTP_CODE!
    set /a FAILED+=1
)

REM Test 6.2: Invalid API Key
set /a TOTAL+=1
for /f "tokens=*" %%A in ('curl -s -w "%%{http_code}" -X GET "%BASE_URL%/modules" -H "X-API-Key: invalid_key" 2^>nul') do (
    set RESPONSE=%%A
)
set HTTP_CODE=!RESPONSE:~-3!
if "!HTTP_CODE!"=="401" (
    echo PASS ^| GET /modules ^| Expected: 401, Got: !HTTP_CODE!
    set /a PASSED+=1
) else (
    echo FAIL ^| GET /modules ^| Expected: 401, Got: !HTTP_CODE!
    set /a FAILED+=1
)

REM ========================================================================
REM SUMMARY
REM ========================================================================

echo.
echo ========================================================================
echo TEST SUMMARY
echo ========================================================================
echo.
echo Total Tests: %TOTAL%
echo Passed: %PASSED%
echo Failed: %FAILED%

if %TOTAL% gtr 0 (
    set /a PASS_RATE=(%PASSED% * 100) / %TOTAL%
    echo Pass Rate: !PASS_RATE!%%
)

echo.

if %FAILED% equ 0 (
    echo ALL TESTS PASSED!
) else (
    echo SOME TESTS FAILED
)

echo.
echo ========================================================================
echo.

endlocal
