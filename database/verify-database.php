<?php
/**
 * Database Verification Script
 * 
 * This script verifies:
 * 1. Database connection
 * 2. Required tables exist
 * 3. Table structure
 * 4. Sample data
 */

require_once '../config/database.php';

// Color codes for CLI output
$colors = [
    'reset' => "\033[0m",
    'green' => "\033[32m",
    'red' => "\033[31m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'cyan' => "\033[36m"
];

echo $colors['cyan'];
echo "\n╔════════════════════════════════════════════════════════════════════╗\n";
echo "║         CyberAware Database Verification Script                    ║\n";
echo "║         Date: " . date('Y-m-d H:i:s') . "                                    ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo $colors['reset'];

// ============================================================================
// 1. TEST DATABASE CONNECTION
// ============================================================================

echo "\n" . $colors['blue'] . "1. DATABASE CONNECTION TEST" . $colors['reset'] . "\n";
echo str_repeat("-", 70) . "\n";

try {
    $pdo = getDBConnection();
    echo $colors['green'] . "✓ PASS" . $colors['reset'] . " | Database connection successful\n";
    echo "  Host: " . DB_HOST . "\n";
    echo "  Database: " . DB_NAME . "\n";
    echo "  User: " . DB_USER . "\n";
} catch (Exception $e) {
    echo $colors['red'] . "✗ FAIL" . $colors['reset'] . " | Database connection failed\n";
    echo "  Error: " . $e->getMessage() . "\n";
    exit(1);
}

// ============================================================================
// 2. CHECK REQUIRED TABLES
// ============================================================================

echo "\n" . $colors['blue'] . "2. REQUIRED TABLES CHECK" . $colors['reset'] . "\n";
echo str_repeat("-", 70) . "\n";

$required_tables = [
    'users',
    'training_modules',
    'user_module_progress',
    'user_module_registration',
    'phishing_inbox',
    'phishing_emails',
    'contact_requests',
    'mfa_attempts'
];

$existing_tables = [];
$missing_tables = [];

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($required_tables as $table) {
        if (in_array($table, $tables)) {
            $existing_tables[] = $table;
            echo $colors['green'] . "✓" . $colors['reset'] . " $table\n";
        } else {
            $missing_tables[] = $table;
            echo $colors['yellow'] . "⚠" . $colors['reset'] . " $table (missing)\n";
        }
    }
} catch (Exception $e) {
    echo $colors['red'] . "✗ Error checking tables: " . $e->getMessage() . $colors['reset'] . "\n";
    exit(1);
}

echo "\nSummary: " . count($existing_tables) . "/" . count($required_tables) . " tables found\n";

// ============================================================================
// 3. CHECK TABLE STRUCTURE
// ============================================================================

echo "\n" . $colors['blue'] . "3. TABLE STRUCTURE CHECK" . $colors['reset'] . "\n";
echo str_repeat("-", 70) . "\n";

$table_checks = [
    'users' => ['id', 'username', 'email', 'password_hash', 'full_name', 'role'],
    'training_modules' => ['id', 'code', 'title', 'description', 'category'],
    'user_module_progress' => ['id', 'user_id', 'module_id', 'score', 'passed'],
    'user_module_registration' => ['id', 'user_id', 'module_id', 'registration_order'],
    'contact_requests' => ['id', 'email', 'phone', 'contact_preference', 'message']
];

foreach ($table_checks as $table => $required_columns) {
    if (!in_array($table, $existing_tables)) {
        echo $colors['yellow'] . "⚠ $table" . $colors['reset'] . " (table missing, skipping structure check)\n";
        continue;
    }
    
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $missing_cols = [];
        foreach ($required_columns as $col) {
            if (!in_array($col, $columns)) {
                $missing_cols[] = $col;
            }
        }
        
        if (empty($missing_cols)) {
            echo $colors['green'] . "✓" . $colors['reset'] . " $table (all required columns present)\n";
        } else {
            echo $colors['yellow'] . "⚠" . $colors['reset'] . " $table (missing columns: " . implode(', ', $missing_cols) . ")\n";
        }
    } catch (Exception $e) {
        echo $colors['red'] . "✗" . $colors['reset'] . " $table (error: " . $e->getMessage() . ")\n";
    }
}

// ============================================================================
// 4. CHECK SAMPLE DATA
// ============================================================================

echo "\n" . $colors['blue'] . "4. SAMPLE DATA CHECK" . $colors['reset'] . "\n";
echo str_repeat("-", 70) . "\n";

$data_checks = [
    'users' => 'SELECT COUNT(*) as count FROM users',
    'training_modules' => 'SELECT COUNT(*) as count FROM training_modules',
    'user_module_progress' => 'SELECT COUNT(*) as count FROM user_module_progress',
    'contact_requests' => 'SELECT COUNT(*) as count FROM contact_requests'
];

foreach ($data_checks as $table => $query) {
    if (!in_array($table, $existing_tables)) {
        echo $colors['yellow'] . "⚠ $table" . $colors['reset'] . " (table missing, skipping data check)\n";
        continue;
    }
    
    try {
        $stmt = $pdo->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $result['count'] ?? 0;
        
        if ($count > 0) {
            echo $colors['green'] . "✓" . $colors['reset'] . " $table ($count records)\n";
        } else {
            echo $colors['yellow'] . "⚠" . $colors['reset'] . " $table (no data)\n";
        }
    } catch (Exception $e) {
        echo $colors['red'] . "✗" . $colors['reset'] . " $table (error: " . $e->getMessage() . ")\n";
    }
}

// ============================================================================
// 5. CHECK SPECIFIC USERS
// ============================================================================

echo "\n" . $colors['blue'] . "5. TEST USER CHECK" . $colors['reset'] . "\n";
echo str_repeat("-", 70) . "\n";

if (in_array('users', $existing_tables)) {
    try {
        $stmt = $pdo->query("SELECT id, username, email, role FROM users LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($users) > 0) {
            echo $colors['green'] . "✓" . $colors['reset'] . " Found " . count($users) . " users:\n";
            foreach ($users as $user) {
                echo "  - ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}, Role: {$user['role']}\n";
            }
        } else {
            echo $colors['yellow'] . "⚠" . $colors['reset'] . " No users found in database\n";
        }
    } catch (Exception $e) {
        echo $colors['red'] . "✗" . $colors['reset'] . " Error: " . $e->getMessage() . "\n";
    }
} else {
    echo $colors['yellow'] . "⚠" . $colors['reset'] . " Users table missing\n";
}

// ============================================================================
// 6. CHECK TRAINING MODULES
// ============================================================================

echo "\n" . $colors['blue'] . "6. TRAINING MODULES CHECK" . $colors['reset'] . "\n";
echo str_repeat("-", 70) . "\n";

if (in_array('training_modules', $existing_tables)) {
    try {
        $stmt = $pdo->query("SELECT id, code, title, category FROM training_modules LIMIT 7");
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($modules) > 0) {
            echo $colors['green'] . "✓" . $colors['reset'] . " Found " . count($modules) . " modules:\n";
            foreach ($modules as $module) {
                echo "  - ID: {$module['id']}, Code: {$module['code']}, Title: {$module['title']}, Category: {$module['category']}\n";
            }
        } else {
            echo $colors['yellow'] . "⚠" . $colors['reset'] . " No modules found in database\n";
        }
    } catch (Exception $e) {
        echo $colors['red'] . "✗" . $colors['reset'] . " Error: " . $e->getMessage() . "\n";
    }
} else {
    echo $colors['yellow'] . "⚠" . $colors['reset'] . " Training modules table missing\n";
}

// ============================================================================
// 7. SUMMARY
// ============================================================================

echo "\n" . $colors['cyan'] . str_repeat("=", 70) . $colors['reset'] . "\n";
echo $colors['blue'] . "VERIFICATION SUMMARY" . $colors['reset'] . "\n";
echo $colors['cyan'] . str_repeat("=", 70) . $colors['reset'] . "\n\n";

$status = "✓ READY";
$status_color = $colors['green'];

if (count($missing_tables) > 0) {
    $status = "⚠ INCOMPLETE";
    $status_color = $colors['yellow'];
}

echo "Database Connection: " . $colors['green'] . "✓ OK" . $colors['reset'] . "\n";
echo "Tables Found: " . count($existing_tables) . "/" . count($required_tables) . "\n";
echo "Overall Status: " . $status_color . $status . $colors['reset'] . "\n";

if (count($missing_tables) > 0) {
    echo "\n" . $colors['yellow'] . "Missing Tables:" . $colors['reset'] . "\n";
    foreach ($missing_tables as $table) {
        echo "  - $table\n";
    }
    echo "\n" . $colors['yellow'] . "To create missing tables, run:" . $colors['reset'] . "\n";
    echo "  php database/create_missing_tables.php\n";
}

echo "\n" . $colors['cyan'] . str_repeat("=", 70) . $colors['reset'] . "\n\n";

?>
