<?php
/**
 * Adaptive Learning Feature - Trend Analysis Engine
 * 
 * This engine calculates performance trends over time periods,
 * identifies improving and declining categories, and generates trend reports.
 */

class TrendAnalysisEngine {
    private $pdo;
    
    /**
     * Constructor
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Calculate trends for a user over a time period
     * 
     * @param int $user_id
     * @param string $timePeriod ('30d', '60d', '90d')
     * @return array
     */
    public function calculateTrends($user_id, $timePeriod = '30d') {
        if (!is_numeric($user_id) || !in_array($timePeriod, ['30d', '60d', '90d'])) {
            return [];
        }
        
        try {
            // Parse time period
            $days = (int)$timePeriod;
            $startDate = date('Y-m-d', strtotime("-{$days} days"));
            $endDate = date('Y-m-d');
            
            // Get all categories
            $categories = $this->getValidCategories();
            $trends = [];
            
            foreach ($categories as $category) {
                $trend = $this->calculateCategoryTrend($user_id, $category, $startDate, $endDate);
                if ($trend) {
                    $trends[] = $trend;
                }
            }
            
            return $trends;
        } catch (Exception $e) {
            error_log("Error calculating trends: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate trend for a specific category
     * 
     * @param int $user_id
     * @param string $category
     * @param string $startDate (Y-m-d format)
     * @param string $endDate (Y-m-d format)
     * @return array|null
     */
    private function calculateCategoryTrend($user_id, $category, $startDate, $endDate) {
        try {
            // Get sessions in period
            $stmt = $this->pdo->prepare("
                SELECT final_score, completed_at
                FROM training_sessions
                WHERE user_id = ? 
                AND module_category = ? 
                AND DATE(completed_at) >= ? 
                AND DATE(completed_at) <= ?
                AND completed_at IS NOT NULL
                ORDER BY completed_at ASC
            ");
            $stmt->execute([$user_id, $category, $startDate, $endDate]);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($sessions) === 0) {
                return null;
            }
            
            // Calculate average score
            $averageScore = array_sum(array_column($sessions, 'final_score')) / count($sessions);
            
            // Calculate trend direction
            $trendDirection = $this->calculateTrendDirection($sessions);
            
            // Calculate improvement percentage
            $improvementPercentage = $this->calculateImprovement($sessions);
            
            return [
                'category' => $category,
                'period_start' => $startDate,
                'period_end' => $endDate,
                'average_score' => round($averageScore, 2),
                'session_count' => count($sessions),
                'trend_direction' => $trendDirection,
                'improvement_percentage' => round($improvementPercentage, 2)
            ];
        } catch (Exception $e) {
            error_log("Error calculating category trend: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Calculate trend direction based on sessions
     * 
     * @param array $sessions
     * @return string ('improving', 'declining', 'stable')
     */
    private function calculateTrendDirection($sessions) {
        if (count($sessions) < 2) {
            return 'stable';
        }
        
        // Split into first half and second half
        $midpoint = (int)(count($sessions) / 2);
        $firstHalf = array_slice($sessions, 0, $midpoint);
        $secondHalf = array_slice($sessions, $midpoint);
        
        $firstAvg = array_sum(array_column($firstHalf, 'final_score')) / count($firstHalf);
        $secondAvg = array_sum(array_column($secondHalf, 'final_score')) / count($secondHalf);
        
        $difference = $secondAvg - $firstAvg;
        
        // 10% threshold for trend classification
        if ($difference >= 10) {
            return 'improving';
        } elseif ($difference <= -10) {
            return 'declining';
        } else {
            return 'stable';
        }
    }
    
    /**
     * Calculate improvement percentage
     * 
     * @param array $sessions
     * @return float
     */
    private function calculateImprovement($sessions) {
        if (count($sessions) < 2) {
            return 0;
        }
        
        $firstScore = $sessions[0]['final_score'];
        $lastScore = $sessions[count($sessions) - 1]['final_score'];
        
        if ($firstScore === 0) {
            return 0;
        }
        
        return (($lastScore - $firstScore) / $firstScore) * 100;
    }
    
    /**
     * Get trend report for a user
     * 
     * @param int $user_id
     * @param string $timePeriod
     * @return array
     */
    public function getTrendReport($user_id, $timePeriod = '30d') {
        try {
            $trends = $this->calculateTrends($user_id, $timePeriod);
            
            // Categorize trends
            $improving = array_filter($trends, function($t) {
                return $t['trend_direction'] === 'improving';
            });
            
            $declining = array_filter($trends, function($t) {
                return $t['trend_direction'] === 'declining';
            });
            
            $stable = array_filter($trends, function($t) {
                return $t['trend_direction'] === 'stable';
            });
            
            // Calculate overall average
            $allScores = array_column($trends, 'average_score');
            $overallAverage = count($allScores) > 0 ? array_sum($allScores) / count($allScores) : 0;
            
            return [
                'user_id' => $user_id,
                'time_period' => $timePeriod,
                'overall_average' => round($overallAverage, 2),
                'total_categories' => count($trends),
                'improving_categories' => count($improving),
                'declining_categories' => count($declining),
                'stable_categories' => count($stable),
                'trends' => $trends,
                'improving_details' => array_values($improving),
                'declining_details' => array_values($declining),
                'generated_at' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log("Error generating trend report: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Compare trends between two time periods
     * 
     * @param int $user_id
     * @param string $period1 ('30d', '60d', '90d')
     * @param string $period2
     * @return array
     */
    public function compareTrends($user_id, $period1 = '30d', $period2 = '60d') {
        try {
            $trends1 = $this->calculateTrends($user_id, $period1);
            $trends2 = $this->calculateTrends($user_id, $period2);
            
            $comparison = [];
            
            foreach ($trends1 as $trend1) {
                $category = $trend1['category'];
                $trend2 = array_values(array_filter($trends2, function($t) use ($category) {
                    return $t['category'] === $category;
                }));
                
                if (count($trend2) > 0) {
                    $trend2 = $trend2[0];
                    $scoreDifference = $trend1['average_score'] - $trend2['average_score'];
                    
                    $comparison[] = [
                        'category' => $category,
                        'score_period1' => $trend1['average_score'],
                        'score_period2' => $trend2['average_score'],
                        'score_difference' => round($scoreDifference, 2),
                        'trend_period1' => $trend1['trend_direction'],
                        'trend_period2' => $trend2['trend_direction']
                    ];
                }
            }
            
            return $comparison;
        } catch (Exception $e) {
            error_log("Error comparing trends: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get categories with most improvement
     * 
     * @param int $user_id
     * @param string $timePeriod
     * @param int $limit
     * @return array
     */
    public function getMostImprovedCategories($user_id, $timePeriod = '30d', $limit = 3) {
        try {
            $trends = $this->calculateTrends($user_id, $timePeriod);
            
            // Sort by improvement percentage
            usort($trends, function($a, $b) {
                return $b['improvement_percentage'] <=> $a['improvement_percentage'];
            });
            
            return array_slice($trends, 0, $limit);
        } catch (Exception $e) {
            error_log("Error getting most improved categories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get categories with most decline
     * 
     * @param int $user_id
     * @param string $timePeriod
     * @param int $limit
     * @return array
     */
    public function getMostDeclinedCategories($user_id, $timePeriod = '30d', $limit = 3) {
        try {
            $trends = $this->calculateTrends($user_id, $timePeriod);
            
            // Sort by improvement percentage (descending, so negative values come first)
            usort($trends, function($a, $b) {
                return $a['improvement_percentage'] <=> $b['improvement_percentage'];
            });
            
            return array_slice($trends, 0, $limit);
        } catch (Exception $e) {
            error_log("Error getting most declined categories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get valid categories
     */
    private function getValidCategories() {
        return [
            'phishing',
            'credential',
            'social',
            'malware',
            'link',
            'password',
            'ransomware'
        ];
    }
}

?>
