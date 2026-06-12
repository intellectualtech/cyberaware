<?php
/**
 * Adaptive Learning Widget - Recommendation Display
 * 
 * This widget displays personalized training recommendations on the trainee dashboard.
 * Safely handles missing dependencies.
 */

// Suppress warnings for this widget
$old_error_reporting = error_reporting(E_ERROR | E_PARSE);

try {
    // Check if required files exist before including
    $config_path = '../../config/database.php';
    $rec_engine_path = '../../includes/adaptive-learning/RecommendationEngine.php';
    $perf_engine_path = '../../includes/adaptive-learning/PerformanceAnalysisEngine.php';
    
    if (!file_exists($config_path) || !file_exists($rec_engine_path) || !file_exists($perf_engine_path)) {
        throw new Exception('Required files not found');
    }
    
    require_once $config_path;
    require_once $rec_engine_path;
    require_once $perf_engine_path;

    // Get database connection
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$user_id) {
        throw new Exception('User not logged in');
    }

    // Initialize engines
    $recommendationEngine = new RecommendationEngine($pdo);
    $performanceEngine = new PerformanceAnalysisEngine($pdo);

    // Get recommendations
    $result = $recommendationEngine->generateRecommendations($user_id, 3);
    $recommendations = $result['recommendations'] ?? [];
    $analysis = $result['analysis'] ?? [];
    
} catch (Exception $e) {
    // If there's any error, just skip the widget
    error_log("Adaptive learning widget error: " . $e->getMessage());
    $recommendations = [];
    $analysis = [];
}

// Restore error reporting
error_reporting($old_error_reporting);

// Color map for categories
$colorMap = [
    'phishing' => '#FF8C42',
    'credential' => '#8B5CF6',
    'social' => '#3B82F6',
    'malware' => '#EF4444',
    'link' => '#10B981',
    'password' => '#6366F1',
    'ransomware' => '#DC2626'
];

// Icon map for categories
$iconMap = [
    'phishing' => 'fa-envelope',
    'credential' => 'fa-key',
    'social' => 'fa-phone-alt',
    'malware' => 'fa-paperclip',
    'link' => 'fa-link',
    'password' => 'fa-lock',
    'ransomware' => 'fa-virus'
];
?>

<div class="adaptive-learning-widget">
    <div class="alw-header">
        <h3><i class="fas fa-lightbulb"></i> Recommended for You</h3>
        <p class="alw-subtitle">Personalized training based on your performance</p>
    </div>
    
    <?php if (empty($recommendations)): ?>
        <div class="alw-empty">
            <i class="fas fa-check-circle"></i>
            <p>No recommendations yet</p>
            <small>Complete more modules to get personalized recommendations</small>
        </div>
    <?php else: ?>
        <div class="alw-recommendations">
            <?php foreach ($recommendations as $idx => $rec): ?>
                <?php 
                    $category = $rec['category'] ?? 'phishing';
                    $color = $colorMap[$category] ?? '#FF8C42';
                    $icon = $iconMap[$category] ?? 'fa-book';
                ?>
                <div class="alw-card" style="border-left: 4px solid <?= $color ?>;">
                    <div class="alwc-top">
                        <div class="alwc-icon" style="background: <?= $color ?>20;">
                            <i class="fas <?= $icon ?>" style="color: <?= $color ?>;"></i>
                        </div>
                        <div class="alwc-meta">
                            <span class="alwc-category"><?= ucfirst($category) ?></span>
                            <span class="alwc-difficulty" style="background: <?= $color ?>20; color: <?= $color ?>;">
                                <?= ucfirst($rec['difficulty_level'] ?? 'beginner') ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="alwc-content">
                        <h4><?= htmlspecialchars($rec['module_title'] ?? 'Training Module') ?></h4>
                        <p class="alwc-reason"><?= htmlspecialchars($rec['reason'] ?? '') ?></p>
                    </div>
                    
                    <div class="alwc-actions">
                        <a href="../../pages/training_module.php?module=<?= $rec['module_id'] ?>&recommended=<?= $rec['id'] ?? '' ?>" 
                           class="alwc-btn-start" style="background: <?= $color ?>;">
                            <i class="fas fa-play"></i> Start Training
                        </a>
                        <button class="alwc-btn-dismiss" onclick="dismissRecommendation(<?= $rec['id'] ?? 0 ?>, this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="alw-footer">
        <a href="progress.php" class="alw-link">
            <i class="fas fa-chart-line"></i> View Full Analytics
        </a>
    </div>
</div>

<style>
.adaptive-learning-widget {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
    margin-bottom: 24px;
}

.alw-header {
    margin-bottom: 20px;
}

.alw-header h3 {
    font-size: 18px;
    font-weight: 700;
    color: #1A1A2E;
    margin: 0 0 4px 0;
}

.alw-subtitle {
    font-size: 13px;
    color: #888;
    margin: 0;
}

.alw-empty {
    text-align: center;
    padding: 40px 20px;
    color: #aaa;
}

.alw-empty i {
    font-size: 48px;
    color: #ddd;
    display: block;
    margin-bottom: 12px;
}

.alw-empty p {
    font-size: 15px;
    font-weight: 600;
    margin: 0 0 6px 0;
}

.alw-empty small {
    font-size: 12px;
    color: #bbb;
}

.alw-recommendations {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 16px;
}

.alw-card {
    background: #f9f9f9;
    border-radius: 12px;
    padding: 16px;
    transition: all .2s;
}

.alw-card:hover {
    background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,.08);
}

.alwc-top {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.alwc-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.alwc-meta {
    display: flex;
    align-items: center;
    gap: 8px;
}

.alwc-category {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    color: #666;
}

.alwc-difficulty {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 4px;
}

.alwc-content {
    margin-bottom: 12px;
}

.alwc-content h4 {
    font-size: 14px;
    font-weight: 700;
    color: #1A1A2E;
    margin: 0 0 4px 0;
}

.alwc-reason {
    font-size: 12px;
    color: #888;
    margin: 0;
}

.alwc-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.alwc-btn-start {
    flex: 1;
    padding: 8px 12px;
    border: none;
    border-radius: 8px;
    color: white;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all .2s;
}

.alwc-btn-start:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,.15);
}

.alwc-btn-dismiss {
    width: 32px;
    height: 32px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    color: #888;
    font-size: 14px;
    transition: all .2s;
}

.alwc-btn-dismiss:hover {
    background: #f5f5f5;
    color: #1A1A2E;
}

.alw-footer {
    text-align: center;
    padding-top: 12px;
    border-top: 1px solid #eee;
}

.alw-link {
    font-size: 13px;
    color: #FF8C42;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all .2s;
}

.alw-link:hover {
    color: #e67e2f;
}

.alw-error {
    background: #fff3cd;
    color: #856404;
    padding: 12px;
    border-radius: 8px;
    font-size: 13px;
}

@media (max-width: 768px) {
    .adaptive-learning-widget {
        padding: 16px;
    }
    
    .alwc-actions {
        flex-direction: column;
    }
    
    .alwc-btn-start {
        width: 100%;
    }
}
</style>

<script>
function dismissRecommendation(recommendationId, button) {
    if (!recommendationId) return;
    
    // Send decline request
    fetch('/api/endpoints/adaptive-learning.php?endpoint=decline-recommendation', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            recommendation_id: recommendationId,
            reason: 'dismissed'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove card with animation
            const card = button.closest('.alw-card');
            card.style.opacity = '0';
            card.style.transform = 'translateX(-20px)';
            setTimeout(() => card.remove(), 300);
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
