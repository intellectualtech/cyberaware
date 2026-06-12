<?php
/**
 * Adaptive Learning Feature - Data Models
 * 
 * This file contains the core data model classes for the Adaptive Learning feature.
 * Each model represents a key entity in the system with validation and serialization methods.
 */

/**
 * TrainingSession Model
 * Represents a single training session with score and metadata
 */
class TrainingSession {
    public $id;
    public $user_id;
    public $module_id;
    public $final_score;
    public $completed_at;
    public $module_category;
    public $difficulty_level;
    public $is_remediation;
    public $recommendation_id;
    
    /**
     * Constructor
     */
    public function __construct($data = []) {
        $this->id = $data['id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
        $this->module_id = $data['module_id'] ?? null;
        $this->final_score = $data['final_score'] ?? null;
        $this->completed_at = $data['completed_at'] ?? null;
        $this->module_category = $data['module_category'] ?? null;
        $this->difficulty_level = $data['difficulty_level'] ?? 'beginner';
        $this->is_remediation = $data['is_remediation'] ?? 0;
        $this->recommendation_id = $data['recommendation_id'] ?? null;
    }
    
    /**
     * Validate the training session
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate() {
        $errors = [];
        
        // Validate score
        if ($this->final_score === null || $this->final_score === '') {
            $errors[] = 'Score is required';
        } elseif (!is_numeric($this->final_score)) {
            $errors[] = 'Score must be numeric';
        } elseif ($this->final_score < 0 || $this->final_score > 100) {
            $errors[] = 'Score must be between 0 and 100';
        }
        
        // Validate user_id
        if ($this->user_id === null || $this->user_id === '') {
            $errors[] = 'User ID is required';
        } elseif (!is_numeric($this->user_id)) {
            $errors[] = 'User ID must be numeric';
        }
        
        // Validate module_id
        if ($this->module_id === null || $this->module_id === '') {
            $errors[] = 'Module ID is required';
        } elseif (!is_numeric($this->module_id)) {
            $errors[] = 'Module ID must be numeric';
        }
        
        // Validate completed_at
        if ($this->completed_at === null || $this->completed_at === '') {
            $errors[] = 'Completion date is required';
        } elseif (!strtotime($this->completed_at)) {
            $errors[] = 'Completion date must be a valid date';
        }
        
        // Validate difficulty_level
        $validDifficulties = ['beginner', 'intermediate', 'advanced'];
        if (!in_array($this->difficulty_level, $validDifficulties)) {
            $errors[] = 'Invalid difficulty level';
        }
        
        return [
            'valid' => count($errors) === 0,
            'errors' => $errors
        ];
    }
    
    /**
     * Convert to array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'module_id' => $this->module_id,
            'final_score' => $this->final_score,
            'completed_at' => $this->completed_at,
            'module_category' => $this->module_category,
            'difficulty_level' => $this->difficulty_level,
            'is_remediation' => $this->is_remediation,
            'recommendation_id' => $this->recommendation_id
        ];
    }
}

/**
 * PerformanceAnalysis Model
 * Represents performance analysis for a user in a specific category
 */
class PerformanceAnalysis {
    public $user_id;
    public $category;
    public $category_average;
    public $weighted_average;
    public $session_count;
    public $recent_session_count;
    public $is_weak;
    public $is_mastered;
    public $difficulty_tier;
    public $last_updated;
    
    /**
     * Constructor
     */
    public function __construct($data = []) {
        $this->user_id = $data['user_id'] ?? null;
        $this->category = $data['category'] ?? null;
        $this->category_average = $data['category_average'] ?? 0;
        $this->weighted_average = $data['weighted_average'] ?? 0;
        $this->session_count = $data['session_count'] ?? 0;
        $this->recent_session_count = $data['recent_session_count'] ?? 0;
        $this->is_weak = $data['is_weak'] ?? 0;
        $this->is_mastered = $data['is_mastered'] ?? 0;
        $this->difficulty_tier = $data['difficulty_tier'] ?? 'beginner';
        $this->last_updated = $data['last_updated'] ?? date('Y-m-d H:i:s');
    }
    
    /**
     * Validate the performance analysis
     */
    public function validate() {
        $errors = [];
        
        if ($this->user_id === null) {
            $errors[] = 'User ID is required';
        }
        
        if ($this->category === null || $this->category === '') {
            $errors[] = 'Category is required';
        }
        
        if (!is_numeric($this->category_average) || $this->category_average < 0 || $this->category_average > 100) {
            $errors[] = 'Category average must be between 0 and 100';
        }
        
        if (!is_numeric($this->weighted_average) || $this->weighted_average < 0 || $this->weighted_average > 100) {
            $errors[] = 'Weighted average must be between 0 and 100';
        }
        
        $validDifficulties = ['beginner', 'intermediate', 'advanced'];
        if (!in_array($this->difficulty_tier, $validDifficulties)) {
            $errors[] = 'Invalid difficulty tier';
        }
        
        return [
            'valid' => count($errors) === 0,
            'errors' => $errors
        ];
    }
    
    /**
     * Convert to array
     */
    public function toArray() {
        return [
            'user_id' => $this->user_id,
            'category' => $this->category,
            'category_average' => round($this->category_average, 2),
            'weighted_average' => round($this->weighted_average, 2),
            'session_count' => $this->session_count,
            'recent_session_count' => $this->recent_session_count,
            'is_weak' => (bool)$this->is_weak,
            'is_mastered' => (bool)$this->is_mastered,
            'difficulty_tier' => $this->difficulty_tier,
            'last_updated' => $this->last_updated
        ];
    }
}

/**
 * Recommendation Model
 * Represents a personalized training recommendation
 */
class Recommendation {
    public $id;
    public $user_id;
    public $module_id;
    public $reason;
    public $difficulty_level;
    public $priority_score;
    public $created_at;
    public $accepted_at;
    public $declined_at;
    public $expires_at;
    public $status;
    
    /**
     * Constructor
     */
    public function __construct($data = []) {
        $this->id = $data['id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
        $this->module_id = $data['module_id'] ?? null;
        $this->reason = $data['reason'] ?? '';
        $this->difficulty_level = $data['difficulty_level'] ?? 'beginner';
        $this->priority_score = $data['priority_score'] ?? 0;
        $this->created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->accepted_at = $data['accepted_at'] ?? null;
        $this->declined_at = $data['declined_at'] ?? null;
        $this->expires_at = $data['expires_at'] ?? null;
        $this->status = $data['status'] ?? 'pending';
    }
    
    /**
     * Validate the recommendation
     */
    public function validate() {
        $errors = [];
        
        if ($this->user_id === null) {
            $errors[] = 'User ID is required';
        }
        
        if ($this->module_id === null) {
            $errors[] = 'Module ID is required';
        }
        
        if ($this->reason === null || $this->reason === '') {
            $errors[] = 'Reason is required';
        }
        
        $validDifficulties = ['beginner', 'intermediate', 'advanced'];
        if (!in_array($this->difficulty_level, $validDifficulties)) {
            $errors[] = 'Invalid difficulty level';
        }
        
        $validStatuses = ['pending', 'accepted', 'declined', 'expired'];
        if (!in_array($this->status, $validStatuses)) {
            $errors[] = 'Invalid status';
        }
        
        if (!is_numeric($this->priority_score) || $this->priority_score < 0 || $this->priority_score > 100) {
            $errors[] = 'Priority score must be between 0 and 100';
        }
        
        return [
            'valid' => count($errors) === 0,
            'errors' => $errors
        ];
    }
    
    /**
     * Check if recommendation is expired
     */
    public function isExpired() {
        if ($this->expires_at === null) {
            return false;
        }
        return strtotime($this->expires_at) < time();
    }
    
    /**
     * Convert to array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'module_id' => $this->module_id,
            'reason' => $this->reason,
            'difficulty_level' => $this->difficulty_level,
            'priority_score' => round($this->priority_score, 2),
            'created_at' => $this->created_at,
            'accepted_at' => $this->accepted_at,
            'declined_at' => $this->declined_at,
            'expires_at' => $this->expires_at,
            'status' => $this->status
        ];
    }
}

/**
 * RemediationTracking Model
 * Represents remediation tracking for a user in a specific category
 */
class RemediationTracking {
    public $id;
    public $user_id;
    public $category;
    public $remediation_level;
    public $consecutive_failures;
    public $last_failure_date;
    public $remediation_module_id;
    public $remediation_score;
    public $remediation_completed_at;
    public $status;
    public $created_at;
    public $updated_at;
    
    /**
     * Constructor
     */
    public function __construct($data = []) {
        $this->id = $data['id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
        $this->category = $data['category'] ?? null;
        $this->remediation_level = $data['remediation_level'] ?? 1;
        $this->consecutive_failures = $data['consecutive_failures'] ?? 0;
        $this->last_failure_date = $data['last_failure_date'] ?? null;
        $this->remediation_module_id = $data['remediation_module_id'] ?? null;
        $this->remediation_score = $data['remediation_score'] ?? null;
        $this->remediation_completed_at = $data['remediation_completed_at'] ?? null;
        $this->status = $data['status'] ?? 'active';
        $this->created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->updated_at = $data['updated_at'] ?? date('Y-m-d H:i:s');
    }
    
    /**
     * Validate the remediation tracking
     */
    public function validate() {
        $errors = [];
        
        if ($this->user_id === null) {
            $errors[] = 'User ID is required';
        }
        
        if ($this->category === null || $this->category === '') {
            $errors[] = 'Category is required';
        }
        
        $validStatuses = ['active', 'resolved', 'escalated'];
        if (!in_array($this->status, $validStatuses)) {
            $errors[] = 'Invalid status';
        }
        
        if (!is_numeric($this->consecutive_failures) || $this->consecutive_failures < 0) {
            $errors[] = 'Consecutive failures must be a non-negative number';
        }
        
        return [
            'valid' => count($errors) === 0,
            'errors' => $errors
        ];
    }
    
    /**
     * Check if remediation is active
     */
    public function isActive() {
        return $this->status === 'active';
    }
    
    /**
     * Convert to array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'category' => $this->category,
            'remediation_level' => $this->remediation_level,
            'consecutive_failures' => $this->consecutive_failures,
            'last_failure_date' => $this->last_failure_date,
            'remediation_module_id' => $this->remediation_module_id,
            'remediation_score' => $this->remediation_score,
            'remediation_completed_at' => $this->remediation_completed_at,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}

?>
