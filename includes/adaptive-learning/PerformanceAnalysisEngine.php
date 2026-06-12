<?php
/**
 * Adaptive Learning Feature - Performance Analysis Engine
 * 
 * This engine calculates performance metrics, identifies weak categories,
 * and maintains performance analysis cache for optimization.
 */

require_once 'DataModels.php';

class PerformanceAnalysisEngine {
    private $pdo;
    private $cache = [];
    private $cacheExpiry = 3600; // 1 hour in seconds
    
    // Valid security categories
    private $validCategories = [
        'phishing',
        'credential',
        'social',
        'malware',
        'link',
        'password',
        'ransomware'
    ];
    
    /**
     * Constructor
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Calculate Category Average with weighted recent sessions
     * 
     * @param int $user_id
     * @param string $category
     * @return array ['category_average' => float, 'weighted_average' => float, 'session_count' => int, 'recent_session_count' => int, 'status' => string]
     */
    public function calculateCategoryAverage($user_id, $category) {
        // Validate inputs
        if (!is_numeric($user_id) || !in_array($category, $this->validCategories)) {
            return [
                'category_average' => null,
                'weighted_average' => null,
                'session_count' => 0,
                'recent_session_count' => 0,
                'status' => 'invalid_input'
            ];
        }
        
        // Check cache first
        $cacheKey = "category_avg_{$user_id}_{$category}";
        if (isset($this->cache[$cacheKey])) {
            $cached = $this->cache[$cacheKey];
            if (time() - $cached['timestamp'] < $this->cacheExpiry) {
                return $cached['data'];
            }
        }
        
        try {
            // Get all sessions in category
            $stmt = $this->pdo->prepare("
                SELECT final_score, completed_at
                FROM training_sessions
                WHERE user_id = ? AND module_category = ? AND completed_at IS NOT NULL
                ORDER BY completed_at DESC
            ");
            $stmt->execute([$user_id, $category]);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Check if we have enough data
            if (count($sessions) < 2) {
                $result = [
                    'category_average' => null,
                    'weighted_average' => null,
                    'session_count' => count($sessions),
                    'recent_session_count' => 0,
                    'status' => 'insufficient_data'
                ];
                $this->cacheResult($cacheKey, $result);
                return $result;
            }
            
            // Calculate simple average
            $totalScore = array_sum(array_column($sessions, 'final_score'));
            $categoryAverage = $totalScore / count($sessions);
            
            // Separate recent and older sessions (last 30 days)
            $thirtyDaysAgo = strtotime('-30 days');
            $recentSessions = [];
            $olderSessions = [];
            
            foreach ($sessions as $session) {
                $sessionTime = strtotime($session['completed_at']);
                if ($sessionTime > $thirtyDaysAgo) {
                    $recentSessions[] = $session;
                } else {
                    $olderSessions[] = $session;
                }
            }
            
            // Calculate weighted average (recent sessions weighted 1.5x)
            $recentWeight = 1.5;
            $olderWeight = 1.0;
            
            $recentTotal = array_sum(array_column($recentSessions, 'final_score')) * $recentWeight;
            $olderTotal = array_sum(array_column($olderSessions, 'final_score')) * $olderWeight;
            
            $totalWeight = (count($recentSessions) * $recentWeight) + (count($olderSessions) * $olderWeight);
            $weightedAverage = ($recentTotal + $olderTotal) / $totalWeight;
            
            // Ensure values are within bounds
            $categoryAverage = max(0, min(100, $categoryAverage));
            $weightedAverage = max(0, min(100, $weightedAverage));
            
            $result = [
                'category_average' => round($categoryAverage, 2),
                'weighted_average' => round($weightedAverage, 2),
                'session_count' => count($sessions),
                'recent_session_count' => count($recentSessions),
                'status' => 'calculated'
            ];
            
            $this->cacheResult($cacheKey, $result);
            return $result;
            
        } catch (Exception $e) {
            error_log("Error calculating category average: " . $e->getMessage());
            return [
                'category_average' => null,
                'weighted_average' => null,
                'session_count' => 0,
                'recent_session_count' => 0,
                'status' => 'error'
            ];
        }
    }
    
    /**
     * Identify weak, mastered, and not-attempted categories
     * 
     * @param int $user_id
     * @return array ['weak_categories' => array, 'not_attempted' => array, 'mastered' => array, 'analysis_timestamp' => string]
     */
    public function identifyWeakCategories($user_id) {
        if (!is_numeric($user_id)) {
            return [
                'weak_categories' => [],
                'not_attempted' => [],
                'mastered' => [],
                'analysis_timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        $weakCategories = [];
        $notAttempted = [];
        $mastered = [];
        
        foreach ($this->validCategories as $category) {
            $analysis = $this->calculateCategoryAverage($user_id, $category);
            
            if ($analysis['status'] === 'insufficient_data' && $analysis['session_count'] === 0) {
                // Not attempted
                $notAttempted[] = $category;
            } elseif ($analysis['status'] === 'calculated' || $analysis['status'] === 'insufficient_data') {
                $average = $analysis['weighted_average'] ?? $analysis['category_average'];
                
                if ($analysis['session_count'] >= 2) {
                    if ($average < 70) {
                        // Weak category
                        $weakCategories[] = [
                            'category' => $category,
                            'average' => $average,
                            'sessions' => $analysis['session_count'],
                            'priority' => 100 - $average // Lower score = higher priority
                        ];
                    } elseif ($average >= 80 && $analysis['session_count'] >= 3) {
                        // Mastered category
                        $mastered[] = [
                            'category' => $category,
                            'average' => $average,
                            'sessions' => $analysis['session_count']
                        ];
                    }
                }
            }
        }
        
        // Sort weak categories by priority (highest priority first)
        usort($weakCategories, function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
        
        return [
            'weak_categories' => $weakCategories,
            'not_attempted' => $notAttempted,
            'mastered' => $mastered,
            'analysis_timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get performance analysis for all categories
     * 
     * @param int $user_id
     * @return array
     */
    public function getFullPerformanceAnalysis($user_id) {
        if (!is_numeric($user_id)) {
            return [];
        }
        
        $analysis = [];
        $weakAnalysis = $this->identifyWeakCategories($user_id);
        
        foreach ($this->validCategories as $category) {
            $categoryAnalysis = $this->calculateCategoryAverage($user_id, $category);
            
            // Determine performance level
            $average = $categoryAnalysis['weighted_average'] ?? $categoryAnalysis['category_average'];
            $performanceLevel = 'not_attempted';
            
            if ($categoryAnalysis['session_count'] >= 2) {
                if ($average < 70) {
                    $performanceLevel = 'weak';
                } elseif ($average >= 80) {
                    $performanceLevel = 'mastered';
                } else {
                    $performanceLevel = 'proficient';
                }
            }
            
            // Determine difficulty tier
            $difficultyTier = 'beginner';
            if ($performanceLevel === 'proficient') {
                $difficultyTier = 'intermediate';
            } elseif ($performanceLevel === 'mastered') {
                $difficultyTier = 'advanced';
            }
            
            $analysis[] = [
                'category' => $category,
                'category_average' => $categoryAnalysis['category_average'],
                'weighted_average' => $categoryAnalysis['weighted_average'],
                'session_count' => $categoryAnalysis['session_count'],
                'recent_session_count' => $categoryAnalysis['recent_session_count'],
                'is_weak' => $performanceLevel === 'weak',
                'is_mastered' => $performanceLevel === 'mastered',
                'difficulty_tier' => $difficultyTier,
                'performance_level' => $performanceLevel,
                'status' => $categoryAnalysis['status']
            ];
        }
        
        return $analysis;
    }
    
    /**
     * Update performance analysis cache in database
     * 
     * @param int $user_id
     * @param string $category
     * @param array $analysis
     */
    public function updateCache($user_id, $category, $analysis) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO performance_analysis_cache 
                (user_id, category, category_average, weighted_average, session_count, recent_session_count, 
                 is_weak, is_mastered, difficulty_tier, cache_expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))
                ON DUPLICATE KEY UPDATE
                category_average = VALUES(category_average),
                weighted_average = VALUES(weighted_average),
                session_count = VALUES(session_count),
                recent_session_count = VALUES(recent_session_count),
                is_weak = VALUES(is_weak),
                is_mastered = VALUES(is_mastered),
                difficulty_tier = VALUES(difficulty_tier),
                cache_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR),
                last_updated = NOW()
            ");
            
            $stmt->execute([
                $user_id,
                $category,
                $analysis['category_average'],
                $analysis['weighted_average'],
                $analysis['session_count'],
                $analysis['recent_session_count'],
                $analysis['is_weak'] ? 1 : 0,
                $analysis['is_mastered'] ? 1 : 0,
                $analysis['difficulty_tier']
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error updating cache: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get cached analysis from database
     * 
     * @param int $user_id
     * @param string $category
     * @return array|null
     */
    public function getCachedAnalysis($user_id, $category) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM performance_analysis_cache
                WHERE user_id = ? AND category = ? AND cache_expires_at > NOW()
            ");
            $stmt->execute([$user_id, $category]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Error getting cached analysis: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Invalidate cache for a user
     * 
     * @param int $user_id
     * @param string|null $category (optional, if null invalidates all categories)
     */
    public function invalidateCache($user_id, $category = null) {
        try {
            if ($category) {
                $stmt = $this->pdo->prepare("
                    DELETE FROM performance_analysis_cache
                    WHERE user_id = ? AND category = ?
                ");
                $stmt->execute([$user_id, $category]);
            } else {
                $stmt = $this->pdo->prepare("
                    DELETE FROM performance_analysis_cache
                    WHERE user_id = ?
                ");
                $stmt->execute([$user_id]);
            }
            
            // Also clear in-memory cache
            if ($category) {
                $cacheKey = "category_avg_{$user_id}_{$category}";
                unset($this->cache[$cacheKey]);
            } else {
                $this->cache = [];
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error invalidating cache: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cache a result in memory
     */
    private function cacheResult($key, $data) {
        $this->cache[$key] = [
            'data' => $data,
            'timestamp' => time()
        ];
    }
    
    /**
     * Get valid categories
     */
    public function getValidCategories() {
        return $this->validCategories;
    }
}

?>
