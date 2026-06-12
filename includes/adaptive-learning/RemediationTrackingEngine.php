<?php
/**
 * Adaptive Learning Feature - Remediation Tracking Engine
 * 
 * This engine tracks remediation attempts, manages remediation assignments,
 * and monitors progress for struggling learners.
 */

require_once 'DataModels.php';

class RemediationTrackingEngine {
    private $pdo;
    
    /**
     * Constructor
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Track a remediation attempt
     * 
     * @param int $user_id
     * @param string $category
     * @param int $score
     * @return bool
     */
    public function trackRemediationAttempt($user_id, $category, $score) {
        if (!is_numeric($user_id) || !$category || !is_numeric($score)) {
            return false;
        }
        
        try {
            // Get or create remediation tracking record
            $tracking = $this->getRemediationTracking($user_id, $category);
            
            if (!$tracking) {
                // Create new tracking record
                $stmt = $this->pdo->prepare("
                    INSERT INTO remediation_tracking
                    (user_id, category, consecutive_failures, status)
                    VALUES (?, ?, 0, 'active')
                ");
                $stmt->execute([$user_id, $category]);
                $tracking = $this->getRemediationTracking($user_id, $category);
            }
            
            // Update based on score
            if ($score < 60) {
                // Failure - increment consecutive failures
                $newFailures = $tracking['consecutive_failures'] + 1;
                $stmt = $this->pdo->prepare("
                    UPDATE remediation_tracking
                    SET consecutive_failures = ?, last_failure_date = NOW()
                    WHERE user_id = ? AND category = ?
                ");
                $stmt->execute([$newFailures, $user_id, $category]);
                
                // Check if we need to assign remediation
                if ($newFailures >= 2) {
                    $this->assignRemediation($user_id, $category);
                }
            } else {
                // Success - reset consecutive failures and mark as resolved
                $stmt = $this->pdo->prepare("
                    UPDATE remediation_tracking
                    SET consecutive_failures = 0, 
                        remediation_score = ?,
                        remediation_completed_at = NOW(),
                        status = 'resolved'
                    WHERE user_id = ? AND category = ?
                ");
                $stmt->execute([$score, $user_id, $category]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error tracking remediation attempt: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Assign remediation module to a user
     * 
     * @param int $user_id
     * @param string $category
     * @return bool
     */
    private function assignRemediation($user_id, $category) {
        try {
            // Find beginner remediation module
            $stmt = $this->pdo->prepare("
                SELECT id FROM training_modules
                WHERE category = ? AND difficulty_level = 'beginner' AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$category]);
            $module = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($module) {
                $stmt = $this->pdo->prepare("
                    UPDATE remediation_tracking
                    SET remediation_module_id = ?, remediation_level = remediation_level + 1
                    WHERE user_id = ? AND category = ?
                ");
                $stmt->execute([$module['id'], $user_id, $category]);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error assigning remediation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get remediation status for a user in a category
     * 
     * @param int $user_id
     * @param string $category
     * @return array|null
     */
    public function getRemediationStatus($user_id, $category) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM remediation_tracking
                WHERE user_id = ? AND category = ?
            ");
            $stmt->execute([$user_id, $category]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting remediation status: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get remediation tracking record
     * 
     * @param int $user_id
     * @param string $category
     * @return array|null
     */
    private function getRemediationTracking($user_id, $category) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM remediation_tracking
                WHERE user_id = ? AND category = ?
            ");
            $stmt->execute([$user_id, $category]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting remediation tracking: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if user is in active remediation for a category
     * 
     * @param int $user_id
     * @param string $category
     * @return bool
     */
    public function isInRemediation($user_id, $category) {
        $tracking = $this->getRemediationStatus($user_id, $category);
        return $tracking && $tracking['status'] === 'active';
    }
    
    /**
     * Check if advanced modules are locked for a category
     * 
     * @param int $user_id
     * @param string $category
     * @return bool
     */
    public function areAdvancedModulesLocked($user_id, $category) {
        $tracking = $this->getRemediationStatus($user_id, $category);
        
        if (!$tracking) {
            return false;
        }
        
        // Locked if in active remediation and haven't completed it with score > 70
        if ($tracking['status'] === 'active') {
            return true;
        }
        
        // Locked if remediation was completed but score was below 70
        if ($tracking['remediation_score'] && $tracking['remediation_score'] < 70) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get all active remediations for a user
     * 
     * @param int $user_id
     * @return array
     */
    public function getActiveRemediations($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT rt.*, tm.title as module_title
                FROM remediation_tracking rt
                LEFT JOIN training_modules tm ON rt.remediation_module_id = tm.id
                WHERE rt.user_id = ? AND rt.status = 'active'
                ORDER BY rt.created_at DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting active remediations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get remediation history for a user
     * 
     * @param int $user_id
     * @return array
     */
    public function getRemediationHistory($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT rt.*, tm.title as module_title
                FROM remediation_tracking rt
                LEFT JOIN training_modules tm ON rt.remediation_module_id = tm.id
                WHERE rt.user_id = ?
                ORDER BY rt.updated_at DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting remediation history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Resolve remediation (mark as resolved)
     * 
     * @param int $user_id
     * @param string $category
     * @return bool
     */
    public function resolveRemediation($user_id, $category) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE remediation_tracking
                SET status = 'resolved', consecutive_failures = 0
                WHERE user_id = ? AND category = ?
            ");
            return $stmt->execute([$user_id, $category]);
        } catch (Exception $e) {
            error_log("Error resolving remediation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Escalate remediation (mark as escalated for admin review)
     * 
     * @param int $user_id
     * @param string $category
     * @return bool
     */
    public function escalateRemediation($user_id, $category) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE remediation_tracking
                SET status = 'escalated'
                WHERE user_id = ? AND category = ?
            ");
            return $stmt->execute([$user_id, $category]);
        } catch (Exception $e) {
            error_log("Error escalating remediation: " . $e->getMessage());
            return false;
        }
    }
}

?>
