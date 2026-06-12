<?php
/**
 * Adaptive Learning Feature - Difficulty Progression Engine
 * 
 * This engine dynamically adjusts difficulty levels based on performance trends,
 * handling promotion and demotion logic for trainees.
 */

class DifficultyProgressionEngine {
    private $pdo;
    
    /**
     * Constructor
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Calculate difficulty progression for a user in a category
     * 
     * @param int $user_id
     * @param string $category
     * @return array ['difficulty_level' => string, 'status' => string, 'reason' => string]
     */
    public function calculateDifficultyProgression($user_id, $category) {
        if (!is_numeric($user_id) || !$category) {
            return [
                'difficulty_level' => 'beginner',
                'status' => 'invalid_input',
                'reason' => 'Invalid input parameters'
            ];
        }
        
        try {
            // Get last 5 sessions in category
            $stmt = $this->pdo->prepare("
                SELECT final_score, completed_at
                FROM training_sessions
                WHERE user_id = ? AND module_category = ? AND completed_at IS NOT NULL
                ORDER BY completed_at DESC
                LIMIT 5
            ");
            $stmt->execute([$user_id, $category]);
            $recentSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Check if we have enough data
            if (count($recentSessions) < 2) {
                return [
                    'difficulty_level' => 'beginner',
                    'status' => 'insufficient_data',
                    'reason' => 'Not enough sessions to determine progression'
                ];
            }
            
            // Calculate average of recent sessions
            $recentAverage = array_sum(array_column($recentSessions, 'final_score')) / count($recentSessions);
            
            // Get current difficulty tier
            $currentDifficulty = $this->getCurrentDifficultyTier($user_id, $category);
            
            // Apply progression rules
            return $this->applyProgressionRules($currentDifficulty, $recentAverage);
            
        } catch (Exception $e) {
            error_log("Error calculating difficulty progression: " . $e->getMessage());
            return [
                'difficulty_level' => 'beginner',
                'status' => 'error',
                'reason' => 'Error calculating progression'
            ];
        }
    }
    
    /**
     * Apply progression rules based on current difficulty and recent average
     * 
     * @param string $currentDifficulty
     * @param float $recentAverage
     * @return array
     */
    private function applyProgressionRules($currentDifficulty, $recentAverage) {
        switch ($currentDifficulty) {
            case 'beginner':
                if ($recentAverage >= 80) {
                    return [
                        'difficulty_level' => 'intermediate',
                        'status' => 'promoted',
                        'reason' => 'Consistent high performance (≥80%)'
                    ];
                }
                break;
                
            case 'intermediate':
                if ($recentAverage >= 85) {
                    return [
                        'difficulty_level' => 'advanced',
                        'status' => 'promoted',
                        'reason' => 'Mastery demonstrated (≥85%)'
                    ];
                } elseif ($recentAverage < 60) {
                    return [
                        'difficulty_level' => 'beginner',
                        'status' => 'demoted',
                        'reason' => 'Performance decline detected (<60%)'
                    ];
                }
                break;
                
            case 'advanced':
                if ($recentAverage < 75) {
                    return [
                        'difficulty_level' => 'intermediate',
                        'status' => 'demoted',
                        'reason' => 'Advanced content too challenging (<75%)'
                    ];
                }
                break;
        }
        
        return [
            'difficulty_level' => $currentDifficulty,
            'status' => 'maintained',
            'reason' => 'Performance stable'
        ];
    }
    
    /**
     * Get current difficulty tier for a user in a category
     * 
     * @param int $user_id
     * @param string $category
     * @return string
     */
    private function getCurrentDifficultyTier($user_id, $category) {
        try {
            // Check cache first
            $stmt = $this->pdo->prepare("
                SELECT difficulty_tier FROM performance_analysis_cache
                WHERE user_id = ? AND category = ?
            ");
            $stmt->execute([$user_id, $category]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['difficulty_tier'];
            }
            
            // Default to beginner if not found
            return 'beginner';
        } catch (Exception $e) {
            error_log("Error getting current difficulty tier: " . $e->getMessage());
            return 'beginner';
        }
    }
    
    /**
     * Update difficulty tier for a user in a category
     * 
     * @param int $user_id
     * @param string $category
     * @param string $newDifficulty
     * @return bool
     */
    public function updateDifficultyTier($user_id, $category, $newDifficulty) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE performance_analysis_cache
                SET difficulty_tier = ?
                WHERE user_id = ? AND category = ?
            ");
            return $stmt->execute([$newDifficulty, $user_id, $category]);
        } catch (Exception $e) {
            error_log("Error updating difficulty tier: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user has mastered a category
     * 
     * @param int $user_id
     * @param string $category
     * @return bool
     */
    public function isMastered($user_id, $category) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT is_mastered FROM performance_analysis_cache
                WHERE user_id = ? AND category = ?
            ");
            $stmt->execute([$user_id, $category]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['is_mastered'] == 1;
        } catch (Exception $e) {
            error_log("Error checking mastery: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark category as mastered
     * 
     * @param int $user_id
     * @param string $category
     * @return bool
     */
    public function markAsMastered($user_id, $category) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE performance_analysis_cache
                SET is_mastered = 1, difficulty_tier = 'advanced'
                WHERE user_id = ? AND category = ?
            ");
            return $stmt->execute([$user_id, $category]);
        } catch (Exception $e) {
            error_log("Error marking as mastered: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get difficulty progression history for a user
     * 
     * @param int $user_id
     * @return array
     */
    public function getProgressionHistory($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT category, difficulty_tier, is_mastered, last_updated
                FROM performance_analysis_cache
                WHERE user_id = ?
                ORDER BY category
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting progression history: " . $e->getMessage());
            return [];
        }
    }
}

?>
