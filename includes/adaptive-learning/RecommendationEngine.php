<?php
/**
 * Adaptive Learning Feature - Recommendation Engine
 * 
 * This engine generates personalized training recommendations based on
 * performance analysis, prevents recommendation loops, and prioritizes weak areas.
 */

require_once 'DataModels.php';
require_once 'PerformanceAnalysisEngine.php';

class RecommendationEngine {
    private $pdo;
    private $performanceEngine;
    
    /**
     * Constructor
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->performanceEngine = new PerformanceAnalysisEngine($pdo);
    }
    
    /**
     * Generate personalized recommendations for a user
     * 
     * @param int $user_id
     * @param int $limit (default: 3, max: 10)
     * @return array ['recommendations' => array, 'analysis' => array]
     */
    public function generateRecommendations($user_id, $limit = 3) {
        if (!is_numeric($user_id) || $limit < 1 || $limit > 10) {
            return ['recommendations' => [], 'analysis' => []];
        }
        
        try {
            // Get weak category analysis
            $weakAnalysis = $this->performanceEngine->identifyWeakCategories($user_id);
            $recommendations = [];
            
            // Priority 1: Remediation for weak categories
            foreach ($weakAnalysis['weak_categories'] as $weakCategory) {
                // Check if already recommended in last 30 days
                $recentRec = $this->getRecentRecommendation($user_id, $weakCategory['category']);
                if ($recentRec) {
                    continue; // Skip if already recommended
                }
                
                // Find beginner remediation module
                $module = $this->findModuleByCategory($weakCategory['category'], 'beginner');
                if ($module) {
                    $recommendations[] = [
                        'module_id' => $module['id'],
                        'module_title' => $module['title'],
                        'category' => $weakCategory['category'],
                        'reason' => "Improve weak area: " . ucfirst($weakCategory['category']),
                        'difficulty_level' => 'beginner',
                        'priority_score' => $weakCategory['priority'],
                        'type' => 'remediation'
                    ];
                }
            }
            
            // Priority 2: Not attempted categories
            foreach ($weakAnalysis['not_attempted'] as $category) {
                if (count($recommendations) >= $limit) {
                    break;
                }
                
                $module = $this->findModuleByCategory($category, 'beginner');
                if ($module) {
                    $recommendations[] = [
                        'module_id' => $module['id'],
                        'module_title' => $module['title'],
                        'category' => $category,
                        'reason' => "Start new topic: " . ucfirst($category),
                        'difficulty_level' => 'beginner',
                        'priority_score' => 50,
                        'type' => 'new_topic'
                    ];
                }
            }
            
            // Priority 3: Progression for mastered categories
            foreach ($weakAnalysis['mastered'] as $masteredCategory) {
                if (count($recommendations) >= $limit) {
                    break;
                }
                
                $module = $this->findModuleByCategory($masteredCategory['category'], 'advanced');
                if ($module) {
                    $recommendations[] = [
                        'module_id' => $module['id'],
                        'module_title' => $module['title'],
                        'category' => $masteredCategory['category'],
                        'reason' => "Master your skills: " . ucfirst($masteredCategory['category']),
                        'difficulty_level' => 'advanced',
                        'priority_score' => 30,
                        'type' => 'progression'
                    ];
                }
            }
            
            // Sort by priority and limit
            usort($recommendations, function($a, $b) {
                return $b['priority_score'] <=> $a['priority_score'];
            });
            $recommendations = array_slice($recommendations, 0, $limit);
            
            // Store recommendations in database
            $storedRecommendations = [];
            foreach ($recommendations as $rec) {
                $recId = $this->storeRecommendation($user_id, $rec);
                if ($recId) {
                    $rec['id'] = $recId;
                    $rec['created_at'] = date('Y-m-d H:i:s');
                    $rec['expires_at'] = date('Y-m-d H:i:s', strtotime('+30 days'));
                    $rec['status'] = 'pending';
                    $storedRecommendations[] = $rec;
                }
            }
            
            return [
                'recommendations' => $storedRecommendations,
                'analysis' => [
                    'weak_categories' => array_column($weakAnalysis['weak_categories'], 'category'),
                    'mastered_categories' => array_column($weakAnalysis['mastered'], 'category'),
                    'not_attempted' => $weakAnalysis['not_attempted'],
                    'overall_average' => $this->calculateOverallAverage($user_id)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error generating recommendations: " . $e->getMessage());
            return ['recommendations' => [], 'analysis' => []];
        }
    }
    
    /**
     * Accept a recommendation
     * 
     * @param int $recommendation_id
     * @param int $user_id
     * @return bool
     */
    public function acceptRecommendation($recommendation_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE adaptive_learning_recommendations
                SET status = 'accepted', accepted_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$recommendation_id, $user_id]);
        } catch (Exception $e) {
            error_log("Error accepting recommendation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Decline a recommendation
     * 
     * @param int $recommendation_id
     * @param int $user_id
     * @param string $reason (optional)
     * @return bool
     */
    public function declineRecommendation($recommendation_id, $user_id, $reason = null) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE adaptive_learning_recommendations
                SET status = 'declined', declined_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$recommendation_id, $user_id]);
        } catch (Exception $e) {
            error_log("Error declining recommendation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get pending recommendations for a user
     * 
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public function getPendingRecommendations($user_id, $limit = 3) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT alr.*, tm.title as module_title, tm.category
                FROM adaptive_learning_recommendations alr
                JOIN training_modules tm ON alr.module_id = tm.id
                WHERE alr.user_id = ? 
                AND alr.status = 'pending'
                AND (alr.expires_at IS NULL OR alr.expires_at > NOW())
                ORDER BY alr.priority_score DESC
                LIMIT ?
            ");
            $stmt->execute([$user_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting pending recommendations: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent recommendation for a category
     * 
     * @param int $user_id
     * @param string $category
     * @return array|null
     */
    private function getRecentRecommendation($user_id, $category) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT alr.* FROM adaptive_learning_recommendations alr
                JOIN training_modules tm ON alr.module_id = tm.id
                WHERE alr.user_id = ? 
                AND tm.category = ?
                AND alr.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND alr.status != 'declined'
                LIMIT 1
            ");
            $stmt->execute([$user_id, $category]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting recent recommendation: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find module by category and difficulty
     * 
     * @param string $category
     * @param string $difficulty
     * @return array|null
     */
    private function findModuleByCategory($category, $difficulty) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, title, category, difficulty_level
                FROM training_modules
                WHERE category = ? AND difficulty_level = ? AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$category, $difficulty]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error finding module: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Store recommendation in database
     * 
     * @param int $user_id
     * @param array $recommendation
     * @return int|null (recommendation ID or null on error)
     */
    private function storeRecommendation($user_id, $recommendation) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO adaptive_learning_recommendations
                (user_id, module_id, reason, difficulty_level, priority_score, expires_at)
                VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
            ");
            
            $stmt->execute([
                $user_id,
                $recommendation['module_id'],
                $recommendation['reason'],
                $recommendation['difficulty_level'],
                $recommendation['priority_score']
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Error storing recommendation: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Calculate overall average score for a user
     * 
     * @param int $user_id
     * @return float
     */
    private function calculateOverallAverage($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT AVG(final_score) as avg_score
                FROM training_sessions
                WHERE user_id = ? AND completed_at IS NOT NULL
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return round($result['avg_score'] ?? 0, 2);
        } catch (Exception $e) {
            error_log("Error calculating overall average: " . $e->getMessage());
            return 0;
        }
    }
}

?>
