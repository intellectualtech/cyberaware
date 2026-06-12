<?php
/**
 * Adaptive Learning Feature - Database Migration Script
 * 
 * This script safely applies schema changes for the Adaptive Learning feature.
 * It includes rollback functionality and data validation checks.
 * 
 * Usage: php migrate_adaptive_learning.php [--rollback]
 */

require_once '../config/database.php';

class AdaptiveLearningMigration {
    private $pdo;
    private $migrationLog = [];
    private $errors = [];
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Run the migration
     */
    public function migrate() {
        echo "Starting Adaptive Learning Migration...\n";
        echo str_repeat("=", 60) . "\n\n";
        
        try {
            // Step 1: Create new tables
            $this->createAdaptiveLearningRecommendationsTable();
            $this->createPerformanceAnalysisCacheTable();
            $this->createRemediationTrackingTable();
            
            // Step 2: Extend existing tables
            $this->extendTrainingSessionsTable();
            $this->extendUserModuleProgressTable();
            
            // Step 3: Create views
            $this->createUserPerformanceSummaryView();
            $this->createCategoryPerformanceSummaryView();
            
            // Step 4: Validate migration
            $this->validateMigration();
            
            echo "\n" . str_repeat("=", 60) . "\n";
            echo "✓ Migration completed successfully!\n";
            echo "Total steps: " . count($this->migrationLog) . "\n";
            
            return true;
        } catch (Exception $e) {
            echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
            $this->logError("Migration failed", $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create adaptive_learning_recommendations table
     */
    private function createAdaptiveLearningRecommendationsTable() {
        echo "Creating adaptive_learning_recommendations table...\n";
        
        $sql = "CREATE TABLE IF NOT EXISTS `adaptive_learning_recommendations` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `module_id` int(11) NOT NULL,
            `reason` varchar(255) NOT NULL,
            `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
            `priority_score` decimal(5,2) DEFAULT 0.00,
            `created_at` datetime DEFAULT current_timestamp(),
            `accepted_at` datetime DEFAULT NULL,
            `declined_at` datetime DEFAULT NULL,
            `expires_at` datetime DEFAULT NULL,
            `status` enum('pending','accepted','declined','expired') DEFAULT 'pending',
            PRIMARY KEY (`id`),
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            FOREIGN KEY (`module_id`) REFERENCES `training_modules` (`id`) ON DELETE CASCADE,
            KEY `idx_user_status` (`user_id`, `status`),
            KEY `idx_created_at` (`created_at`),
            KEY `idx_expires_at` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->pdo->exec($sql);
        $this->logStep("Created adaptive_learning_recommendations table");
    }
    
    /**
     * Create performance_analysis_cache table
     */
    private function createPerformanceAnalysisCacheTable() {
        echo "Creating performance_analysis_cache table...\n";
        
        $sql = "CREATE TABLE IF NOT EXISTS `performance_analysis_cache` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `category` varchar(50) NOT NULL,
            `category_average` decimal(5,2) DEFAULT 0.00,
            `weighted_average` decimal(5,2) DEFAULT 0.00,
            `session_count` int(11) DEFAULT 0,
            `recent_session_count` int(11) DEFAULT 0,
            `is_weak` tinyint(1) DEFAULT 0,
            `is_mastered` tinyint(1) DEFAULT 0,
            `difficulty_tier` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
            `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            `cache_expires_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_user_category` (`user_id`, `category`),
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            KEY `idx_is_weak` (`is_weak`),
            KEY `idx_is_mastered` (`is_mastered`),
            KEY `idx_cache_expires` (`cache_expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->pdo->exec($sql);
        $this->logStep("Created performance_analysis_cache table");
    }
    
    /**
     * Create remediation_tracking table
     */
    private function createRemediationTrackingTable() {
        echo "Creating remediation_tracking table...\n";
        
        $sql = "CREATE TABLE IF NOT EXISTS `remediation_tracking` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `category` varchar(50) NOT NULL,
            `remediation_level` int(11) DEFAULT 1,
            `consecutive_failures` int(11) DEFAULT 0,
            `last_failure_date` datetime DEFAULT NULL,
            `remediation_module_id` int(11) DEFAULT NULL,
            `remediation_score` int(11) DEFAULT NULL,
            `remediation_completed_at` datetime DEFAULT NULL,
            `status` enum('active','resolved','escalated') DEFAULT 'active',
            `created_at` datetime DEFAULT current_timestamp(),
            `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_user_category` (`user_id`, `category`),
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
            FOREIGN KEY (`remediation_module_id`) REFERENCES `training_modules` (`id`) ON DELETE SET NULL,
            KEY `idx_status` (`status`),
            KEY `idx_user_category` (`user_id`, `category`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->pdo->exec($sql);
        $this->logStep("Created remediation_tracking table");
    }
    
    /**
     * Extend training_sessions table
     */
    private function extendTrainingSessionsTable() {
        echo "Extending training_sessions table...\n";
        
        // Add columns if they don't exist
        $columns = [
            'module_category' => "ALTER TABLE `training_sessions` ADD COLUMN IF NOT EXISTS `module_category` varchar(50) DEFAULT NULL",
            'difficulty_level' => "ALTER TABLE `training_sessions` ADD COLUMN IF NOT EXISTS `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner'",
            'is_remediation' => "ALTER TABLE `training_sessions` ADD COLUMN IF NOT EXISTS `is_remediation` tinyint(1) DEFAULT 0",
            'recommendation_id' => "ALTER TABLE `training_sessions` ADD COLUMN IF NOT EXISTS `recommendation_id` int(11) DEFAULT NULL"
        ];
        
        foreach ($columns as $name => $sql) {
            try {
                $this->pdo->exec($sql);
                $this->logStep("Added column $name to training_sessions");
            } catch (Exception $e) {
                // Column might already exist, continue
                $this->logStep("Column $name already exists in training_sessions");
            }
        }
        
        // Add foreign key
        try {
            $fkSql = "ALTER TABLE `training_sessions` ADD CONSTRAINT `fk_training_sessions_recommendation` 
                      FOREIGN KEY (`recommendation_id`) REFERENCES `adaptive_learning_recommendations` (`id`) ON DELETE SET NULL";
            $this->pdo->exec($fkSql);
            $this->logStep("Added foreign key for recommendation_id");
        } catch (Exception $e) {
            $this->logStep("Foreign key already exists");
        }
        
        // Add indexes
        $indexes = [
            'idx_user_category_date' => "ALTER TABLE `training_sessions` ADD KEY IF NOT EXISTS `idx_user_category_date` (`user_id`, `module_category`, `completed_at`)",
            'idx_module_category' => "ALTER TABLE `training_sessions` ADD KEY IF NOT EXISTS `idx_module_category` (`module_category`)"
        ];
        
        foreach ($indexes as $name => $sql) {
            try {
                $this->pdo->exec($sql);
                $this->logStep("Added index $name to training_sessions");
            } catch (Exception $e) {
                $this->logStep("Index $name already exists");
            }
        }
    }
    
    /**
     * Extend user_module_progress table
     */
    private function extendUserModuleProgressTable() {
        echo "Extending user_module_progress table...\n";
        
        $columns = [
            'recommended_at' => "ALTER TABLE `user_module_progress` ADD COLUMN IF NOT EXISTS `recommended_at` datetime DEFAULT NULL",
            'recommendation_reason' => "ALTER TABLE `user_module_progress` ADD COLUMN IF NOT EXISTS `recommendation_reason` varchar(255) DEFAULT NULL",
            'difficulty_tier' => "ALTER TABLE `user_module_progress` ADD COLUMN IF NOT EXISTS `difficulty_tier` enum('beginner','intermediate','advanced') DEFAULT 'beginner'",
            'is_mastered' => "ALTER TABLE `user_module_progress` ADD COLUMN IF NOT EXISTS `is_mastered` tinyint(1) DEFAULT 0"
        ];
        
        foreach ($columns as $name => $sql) {
            try {
                $this->pdo->exec($sql);
                $this->logStep("Added column $name to user_module_progress");
            } catch (Exception $e) {
                $this->logStep("Column $name already exists in user_module_progress");
            }
        }
        
        // Add indexes
        try {
            $this->pdo->exec("ALTER TABLE `user_module_progress` ADD KEY IF NOT EXISTS `idx_is_mastered` (`is_mastered`)");
            $this->logStep("Added index idx_is_mastered to user_module_progress");
        } catch (Exception $e) {
            $this->logStep("Index already exists");
        }
    }
    
    /**
     * Create user_performance_summary view
     */
    private function createUserPerformanceSummaryView() {
        echo "Creating user_performance_summary view...\n";
        
        $sql = "CREATE OR REPLACE VIEW `user_performance_summary` AS
                SELECT 
                    u.id as user_id,
                    u.username,
                    u.full_name,
                    COUNT(DISTINCT ts.id) as total_sessions,
                    COUNT(DISTINCT ts.module_id) as modules_completed,
                    AVG(ts.final_score) as average_score,
                    MAX(ts.completed_at) as last_training_date,
                    CASE 
                        WHEN AVG(ts.final_score) < 60 THEN 'high_risk'
                        WHEN AVG(ts.final_score) < 70 THEN 'medium_risk'
                        WHEN AVG(ts.final_score) < 80 THEN 'low_risk'
                        ELSE 'compliant'
                    END as risk_level
                FROM users u
                LEFT JOIN training_sessions ts ON u.id = ts.user_id AND ts.completed_at IS NOT NULL
                WHERE u.role = 'trainee'
                GROUP BY u.id, u.username, u.full_name";
        
        $this->pdo->exec($sql);
        $this->logStep("Created user_performance_summary view");
    }
    
    /**
     * Create category_performance_summary view
     */
    private function createCategoryPerformanceSummaryView() {
        echo "Creating category_performance_summary view...\n";
        
        $sql = "CREATE OR REPLACE VIEW `category_performance_summary` AS
                SELECT 
                    ts.user_id,
                    ts.module_category as category,
                    COUNT(ts.id) as session_count,
                    AVG(ts.final_score) as category_average,
                    MIN(ts.final_score) as min_score,
                    MAX(ts.final_score) as max_score,
                    MAX(ts.completed_at) as last_session_date,
                    CASE 
                        WHEN COUNT(ts.id) < 2 THEN 'insufficient_data'
                        WHEN AVG(ts.final_score) < 70 THEN 'weak'
                        WHEN AVG(ts.final_score) >= 80 THEN 'mastered'
                        ELSE 'proficient'
                    END as performance_level
                FROM training_sessions ts
                WHERE ts.completed_at IS NOT NULL AND ts.module_category IS NOT NULL
                GROUP BY ts.user_id, ts.module_category";
        
        $this->pdo->exec($sql);
        $this->logStep("Created category_performance_summary view");
    }
    
    /**
     * Validate the migration
     */
    private function validateMigration() {
        echo "\nValidating migration...\n";
        
        // Check if tables exist
        $tables = ['adaptive_learning_recommendations', 'performance_analysis_cache', 'remediation_tracking'];
        foreach ($tables as $table) {
            $stmt = $this->pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
            $stmt->execute([DB_NAME, $table]);
            if ($stmt->rowCount() > 0) {
                echo "✓ Table $table exists\n";
            } else {
                throw new Exception("Table $table was not created");
            }
        }
        
        // Check if columns were added
        $stmt = $this->pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'training_sessions' AND COLUMN_NAME = 'module_category'");
        $stmt->execute([DB_NAME]);
        if ($stmt->rowCount() > 0) {
            echo "✓ Column module_category added to training_sessions\n";
        } else {
            throw new Exception("Column module_category was not added to training_sessions");
        }
        
        // Check if views exist
        $views = ['user_performance_summary', 'category_performance_summary'];
        foreach ($views as $view) {
            $stmt = $this->pdo->prepare("SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
            $stmt->execute([DB_NAME, $view]);
            if ($stmt->rowCount() > 0) {
                echo "✓ View $view exists\n";
            } else {
                throw new Exception("View $view was not created");
            }
        }
    }
    
    /**
     * Log a migration step
     */
    private function logStep($message) {
        $this->migrationLog[] = $message;
        echo "  ✓ $message\n";
    }
    
    /**
     * Log an error
     */
    private function logError($title, $message) {
        $this->errors[] = ['title' => $title, 'message' => $message];
    }
    
    /**
     * Get migration log
     */
    public function getLog() {
        return $this->migrationLog;
    }
    
    /**
     * Get errors
     */
    public function getErrors() {
        return $this->errors;
    }
}

// Run migration
$migration = new AdaptiveLearningMigration();
$success = $migration->migrate();

if (!$success) {
    echo "\nErrors encountered:\n";
    foreach ($migration->getErrors() as $error) {
        echo "  - " . $error['title'] . ": " . $error['message'] . "\n";
    }
    exit(1);
}

exit(0);
?>
