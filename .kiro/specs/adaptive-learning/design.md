# Adaptive Learning Feature - Technical Design Document

## Overview

The Adaptive Learning feature transforms CyberApp from a static module-based training platform into an intelligent, personalized learning system. It analyzes individual performance patterns across security categories, identifies weak areas, and dynamically recommends and assigns follow-up training with adjusted difficulty levels.

### Key Objectives

1. **Personalization**: Tailor training recommendations based on individual performance history
2. **Efficiency**: Focus training efforts on areas where employees struggle most
3. **Progression**: Dynamically adjust difficulty levels based on performance trends
4. **Engagement**: Provide clear feedback and motivation through performance analytics
5. **Measurability**: Track learning effectiveness and identify systemic training gaps

### System Scope

The Adaptive Learning Engine operates across seven security categories:
- Phishing
- Credential Harvesting
- Social Engineering
- Malware & Attachments
- Website & Link Safety
- Password Security
- Ransomware

---

## Architecture

### High-Level System Design

```
┌─────────────────────────────────────────────────────────────────┐
│                        CyberApp Platform                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────────────┐         ┌──────────────────────────────┐  │
│  │  Training        │         │  Adaptive Learning Engine    │  │
│  │  Modules         │────────▶│  (Core Logic)                │  │
│  │  (7 categories)  │         │                              │  │
│  └──────────────────┘         │  • Performance Analysis      │  │
│                               │  • Weak Category Detection   │  │
│  ┌──────────────────┐         │  • Recommendation Engine     │  │
│  │  Training        │         │  • Difficulty Progression    │  │
│  │  Sessions        │────────▶│  • Trend Analysis            │  │
│  │  (Scores,        │         │                              │  │
│  │   Timestamps)    │         └──────────────────────────────┘  │
│  └──────────────────┘                      │                    │
│                                            ▼                    │
│  ┌──────────────────┐         ┌──────────────────────────────┐  │
│  │  Dashboard       │◀────────│  Recommendations             │  │
│  │  Widget          │         │  & Analytics                 │  │
│  │  (UI Display)    │         │                              │  │
│  └──────────────────┘         └──────────────────────────────┘  │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Database Layer                                          │   │
│  │  • training_sessions (extended)                          │   │
│  │  • adaptive_learning_recommendations (new)               │   │
│  │  • performance_analysis_cache (new)                      │   │
│  │  • user_module_progress (extended)                       │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

### Component Interactions

1. **Training Module Completion** → Session recorded with score and category
2. **Performance Analysis** → Engine calculates Category_Average and identifies weak areas
3. **Recommendation Generation** → Engine creates personalized recommendations
4. **Dashboard Display** → Widget shows top 3 recommendations to trainee
5. **Difficulty Adjustment** → Engine updates difficulty tier based on performance
6. **Trend Tracking** → Manager dashboard displays performance trends

---

## Components and Interfaces

### Core Components

1. **Performance Analysis Engine**
   - Calculates Category_Average with weighted recent sessions
   - Identifies weak categories and mastered categories
   - Maintains performance analysis cache
   - Interface: `calculateCategoryAverage(user_id, category)`, `identifyWeakCategories(user_id)`

2. **Recommendation Engine**
   - Generates personalized training recommendations
   - Prevents recommendation loops
   - Prioritizes weak categories
   - Interface: `generateRecommendations(user_id)`, `acceptRecommendation(recommendation_id)`, `declineRecommendation(recommendation_id)`

3. **Difficulty Progression Engine**
   - Calculates appropriate difficulty levels based on performance
   - Handles promotion and demotion logic
   - Tracks mastery status
   - Interface: `calculateDifficultyProgression(user_id, category)`, `updateDifficultyTier(user_id, category, new_tier)`

4. **Remediation Tracking Engine**
   - Tracks consecutive failures
   - Manages remediation assignments
   - Monitors remediation progress
   - Interface: `trackRemediationAttempt(user_id, category, score)`, `getRemediationStatus(user_id, category)`

5. **Trend Analysis Engine**
   - Calculates performance trends over time periods
   - Identifies improving and declining categories
   - Generates trend reports
   - Interface: `calculateTrends(user_id, time_period)`, `getTrendReport(user_id)`

6. **Dashboard Widget**
   - Displays top 3 recommendations
   - Shows performance analytics
   - Handles user interactions
   - Interface: Renders recommendation cards, handles click events

### External Interfaces

- **Training Module System**: Receives session completion events, provides module metadata
- **User Management System**: Provides user roles and permissions
- **Dashboard System**: Displays recommendations and analytics
- **Manager Reporting System**: Provides trend data and team analytics
- **Admin Monitoring System**: Provides data integrity reports

---

## Data Models

### Core Data Models

#### TrainingSession
```
{
  id: integer,
  user_id: integer,
  module_id: integer,
  final_score: integer (0-100),
  completed_at: datetime,
  module_category: string,
  difficulty_level: enum('beginner', 'intermediate', 'advanced'),
  is_remediation: boolean,
  recommendation_id: integer (nullable)
}
```

#### PerformanceAnalysis
```
{
  user_id: integer,
  category: string,
  category_average: decimal (0-100),
  weighted_average: decimal (0-100),
  session_count: integer,
  recent_session_count: integer,
  is_weak: boolean,
  is_mastered: boolean,
  difficulty_tier: enum('beginner', 'intermediate', 'advanced'),
  last_updated: datetime
}
```

#### Recommendation
```
{
  id: integer,
  user_id: integer,
  module_id: integer,
  reason: string,
  difficulty_level: enum('beginner', 'intermediate', 'advanced'),
  priority_score: decimal (0-100),
  created_at: datetime,
  accepted_at: datetime (nullable),
  declined_at: datetime (nullable),
  expires_at: datetime,
  status: enum('pending', 'accepted', 'declined', 'expired')
}
```

#### RemediationTracking
```
{
  id: integer,
  user_id: integer,
  category: string,
  remediation_level: integer,
  consecutive_failures: integer,
  last_failure_date: datetime,
  remediation_module_id: integer,
  remediation_score: integer (nullable),
  remediation_completed_at: datetime (nullable),
  status: enum('active', 'resolved', 'escalated'),
  created_at: datetime,
  updated_at: datetime
}
```

#### PerformanceTrend
```
{
  user_id: integer,
  category: string,
  period_start: date,
  period_end: date,
  average_score: decimal (0-100),
  session_count: integer,
  trend_direction: enum('improving', 'declining', 'stable'),
  improvement_percentage: decimal
}
```

---

## Database Schema

### New Tables

#### 1. adaptive_learning_recommendations

Stores generated recommendations for trainees.

```sql
CREATE TABLE `adaptive_learning_recommendations` (
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
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 2. performance_analysis_cache

Caches Category_Average calculations for performance optimization.

```sql
CREATE TABLE `performance_analysis_cache` (
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
  KEY `idx_cache_expires` (`cache_expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3. remediation_tracking

Tracks remediation attempts and progress for struggling learners.

```sql
CREATE TABLE `remediation_tracking` (
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
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Extended Tables

#### training_sessions (extend existing table)

Add columns to track adaptive learning context:

```sql
ALTER TABLE `training_sessions` ADD COLUMN `module_category` varchar(50) DEFAULT NULL;
ALTER TABLE `training_sessions` ADD COLUMN `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner';
ALTER TABLE `training_sessions` ADD COLUMN `is_remediation` tinyint(1) DEFAULT 0;
ALTER TABLE `training_sessions` ADD COLUMN `recommendation_id` int(11) DEFAULT NULL;
ALTER TABLE `training_sessions` ADD FOREIGN KEY (`recommendation_id`) REFERENCES `adaptive_learning_recommendations` (`id`) ON DELETE SET NULL;
```

#### user_module_progress (extend existing table)

Add columns for adaptive learning tracking:

```sql
ALTER TABLE `user_module_progress` ADD COLUMN `recommended_at` datetime DEFAULT NULL;
ALTER TABLE `user_module_progress` ADD COLUMN `recommendation_reason` varchar(255) DEFAULT NULL;
ALTER TABLE `user_module_progress` ADD COLUMN `difficulty_tier` enum('beginner','intermediate','advanced') DEFAULT 'beginner';
ALTER TABLE `user_module_progress` ADD COLUMN `is_mastered` tinyint(1) DEFAULT 0;
```

---

## Core Algorithms

### Algorithm 1: Category Average Calculation with Weighted Recent Sessions

**Purpose**: Calculate the average performance in a security category, with emphasis on recent performance.

**Input**: 
- user_id
- category
- time_window (default: all time)

**Output**: 
- category_average (0-100)
- weighted_average (0-100)
- session_count
- recent_session_count

**Logic**:

```
FUNCTION calculateCategoryAverage(user_id, category):
    // Get all sessions in category
    sessions = SELECT * FROM training_sessions 
               WHERE user_id = user_id AND module_category = category
               ORDER BY completed_at DESC
    
    IF sessions.count < 2:
        RETURN {
            category_average: NULL,
            weighted_average: NULL,
            session_count: sessions.count,
            status: "insufficient_data"
        }
    
    // Calculate simple average
    total_score = SUM(sessions[*].final_score)
    category_average = total_score / sessions.count
    
    // Calculate weighted average (recent sessions weighted 1.5x)
    thirty_days_ago = NOW() - INTERVAL 30 DAY
    recent_sessions = FILTER(sessions, completed_at > thirty_days_ago)
    older_sessions = FILTER(sessions, completed_at <= thirty_days_ago)
    
    recent_weight = 1.5
    older_weight = 1.0
    
    weighted_total = (SUM(recent_sessions[*].final_score) * recent_weight) + 
                     (SUM(older_sessions[*].final_score) * older_weight)
    total_weight = (recent_sessions.count * recent_weight) + 
                   (older_sessions.count * older_weight)
    
    weighted_average = weighted_total / total_weight
    
    RETURN {
        category_average: ROUND(category_average, 2),
        weighted_average: ROUND(weighted_average, 2),
        session_count: sessions.count,
        recent_session_count: recent_sessions.count,
        status: "calculated"
    }
```

**Performance Considerations**:
- Cache results for 1 hour to avoid recalculation
- Use indexed queries on (user_id, module_category, completed_at)
- Target query response time: < 100ms

### Algorithm 2: Weak Category Identification

**Purpose**: Identify security categories where a trainee needs improvement.

**Input**: 
- user_id

**Output**: 
- weak_categories (array of categories with average < 70%)
- not_attempted_categories
- mastered_categories

**Logic**:

```
FUNCTION identifyWeakCategories(user_id):
    categories = ['phishing', 'credential', 'social', 'malware', 'link', 'password', 'ransomware']
    weak_categories = []
    not_attempted = []
    mastered = []
    
    FOR EACH category IN categories:
        analysis = calculateCategoryAverage(user_id, category)
        
        IF analysis.session_count == 0:
            not_attempted.ADD(category)
        
        ELSE IF analysis.session_count >= 2:
            IF analysis.weighted_average < 70:
                weak_categories.ADD({
                    category: category,
                    average: analysis.weighted_average,
                    sessions: analysis.session_count,
                    priority: 100 - analysis.weighted_average  // Lower score = higher priority
                })
            
            ELSE IF analysis.weighted_average >= 80 AND analysis.session_count >= 3:
                mastered.ADD({
                    category: category,
                    average: analysis.weighted_average,
                    sessions: analysis.session_count
                })
    
    // Sort weak categories by priority (lowest average first)
    weak_categories.SORT_BY(priority DESC)
    
    RETURN {
        weak_categories: weak_categories,
        not_attempted: not_attempted,
        mastered: mastered,
        analysis_timestamp: NOW()
    }
```

**Update Frequency**: Within 1 hour of training session completion

### Algorithm 3: Recommendation Engine

**Purpose**: Generate personalized training recommendations based on performance analysis.

**Input**: 
- user_id
- weak_categories (from Algorithm 2)
- user_module_progress

**Output**: 
- recommendations (array of module_id, reason, difficulty_level)

**Logic**:

```
FUNCTION generateRecommendations(user_id):
    weak_analysis = identifyWeakCategories(user_id)
    recommendations = []
    
    // Priority 1: Remediation for weak categories
    FOR EACH weak_category IN weak_analysis.weak_categories:
        // Check if already recommended in last 30 days
        recent_recommendation = SELECT * FROM adaptive_learning_recommendations
                               WHERE user_id = user_id 
                               AND category = weak_category.category
                               AND created_at > NOW() - INTERVAL 30 DAY
                               AND status != 'declined'
        
        IF recent_recommendation EXISTS:
            CONTINUE  // Skip if already recommended
        
        // Find appropriate remediation module
        remediation_module = SELECT * FROM training_modules
                            WHERE category = weak_category.category
                            AND difficulty_level = 'beginner'
                            LIMIT 1
        
        IF remediation_module EXISTS:
            recommendations.ADD({
                module_id: remediation_module.id,
                reason: "Improve weak area: " + weak_category.category,
                difficulty_level: 'beginner',
                priority_score: weak_category.priority,
                category: weak_category.category
            })
    
    // Priority 2: Not attempted categories
    FOR EACH not_attempted IN weak_analysis.not_attempted:
        beginner_module = SELECT * FROM training_modules
                         WHERE category = not_attempted
                         AND difficulty_level = 'beginner'
                         LIMIT 1
        
        IF beginner_module EXISTS:
            recommendations.ADD({
                module_id: beginner_module.id,
                reason: "Start new topic: " + not_attempted,
                difficulty_level: 'beginner',
                priority_score: 50,
                category: not_attempted
            })
    
    // Priority 3: Progression for mastered categories
    FOR EACH mastered IN weak_analysis.mastered:
        advanced_module = SELECT * FROM training_modules
                         WHERE category = mastered.category
                         AND difficulty_level = 'advanced'
                         LIMIT 1
        
        IF advanced_module EXISTS:
            recommendations.ADD({
                module_id: advanced_module.id,
                reason: "Master your skills: " + mastered.category,
                difficulty_level: 'advanced',
                priority_score: 30,
                category: mastered.category
            })
    
    // Sort by priority and limit to top 3
    recommendations.SORT_BY(priority_score DESC)
    top_recommendations = recommendations.SLICE(0, 3)
    
    // Store recommendations in database
    FOR EACH rec IN top_recommendations:
        INSERT INTO adaptive_learning_recommendations
        (user_id, module_id, reason, difficulty_level, priority_score, expires_at)
        VALUES (user_id, rec.module_id, rec.reason, rec.difficulty_level, 
                rec.priority_score, NOW() + INTERVAL 30 DAY)
    
    RETURN top_recommendations
```

**Recommendation Loop Prevention**:
- Don't recommend modules completed in last 30 days
- Track declined recommendations for 7 days
- Maintain recommendation history to prevent duplicates

### Algorithm 4: Difficulty Progression

**Purpose**: Dynamically adjust difficulty levels based on performance trends.

**Input**: 
- user_id
- category
- recent_scores (last 5 sessions)

**Output**: 
- new_difficulty_level
- progression_status

**Logic**:

```
FUNCTION calculateDifficultyProgression(user_id, category):
    // Get last 5 sessions in category
    recent_sessions = SELECT final_score FROM training_sessions
                     WHERE user_id = user_id 
                     AND module_category = category
                     ORDER BY completed_at DESC
                     LIMIT 5
    
    IF recent_sessions.count < 2:
        RETURN { difficulty_level: 'beginner', status: 'insufficient_data' }
    
    recent_average = AVG(recent_sessions[*].final_score)
    current_difficulty = SELECT difficulty_tier FROM performance_analysis_cache
                        WHERE user_id = user_id AND category = category
    
    // Progression rules
    IF current_difficulty == 'beginner':
        IF recent_average >= 80:
            RETURN { 
                difficulty_level: 'intermediate', 
                status: 'promoted',
                reason: 'Consistent high performance'
            }
    
    ELSE IF current_difficulty == 'intermediate':
        IF recent_average >= 85:
            RETURN { 
                difficulty_level: 'advanced', 
                status: 'promoted',
                reason: 'Mastery demonstrated'
            }
        ELSE IF recent_average < 60:
            RETURN { 
                difficulty_level: 'beginner', 
                status: 'demoted',
                reason: 'Performance decline detected'
            }
    
    ELSE IF current_difficulty == 'advanced':
        IF recent_average < 75:
            RETURN { 
                difficulty_level: 'intermediate', 
                status: 'demoted',
                reason: 'Advanced content too challenging'
            }
    
    RETURN { 
        difficulty_level: current_difficulty, 
        status: 'maintained'
    }
```

**Progression Rules**:
- Beginner → Intermediate: Average ≥ 80% over last 5 sessions
- Intermediate → Advanced: Average ≥ 85% over last 5 sessions
- Advanced → Intermediate: Average < 75% over last 5 sessions
- Intermediate → Beginner: Average < 60% over last 5 sessions

---

## API Endpoints

### 1. Get Personalized Recommendations

**Endpoint**: `GET /api/adaptive-learning/recommendations`

**Authentication**: Required (API key or JWT)

**Parameters**:
- `user_id` (query): User ID (optional, defaults to authenticated user)
- `limit` (query): Number of recommendations (default: 3, max: 10)

**Response**:
```json
{
  "success": true,
  "data": {
    "recommendations": [
      {
        "id": 1,
        "module_id": 2,
        "module_title": "Credential Harvesting Awareness",
        "category": "credential",
        "difficulty_level": "beginner",
        "reason": "Improve weak area: Credential Harvesting",
        "priority_score": 85.5,
        "created_at": "2024-02-15T10:30:00Z",
        "expires_at": "2024-03-16T10:30:00Z"
      }
    ],
    "analysis": {
      "weak_categories": ["credential", "social"],
      "mastered_categories": ["phishing"],
      "not_attempted": ["ransomware"],
      "overall_average": 72.5
    }
  }
}
```

### 2. Get Performance Analysis

**Endpoint**: `GET /api/adaptive-learning/performance-analysis`

**Authentication**: Required

**Parameters**:
- `user_id` (query): User ID (optional)
- `category` (query): Specific category (optional)

**Response**:
```json
{
  "success": true,
  "data": {
    "user_id": 5,
    "analysis_timestamp": "2024-02-15T10:30:00Z",
    "categories": [
      {
        "category": "phishing",
        "category_average": 82.5,
        "weighted_average": 84.2,
        "session_count": 5,
        "recent_session_count": 2,
        "is_weak": false,
        "is_mastered": true,
        "difficulty_tier": "advanced",
        "trend": "improving"
      }
    ],
    "summary": {
      "overall_average": 75.3,
      "weak_categories_count": 2,
      "mastered_categories_count": 1,
      "total_sessions": 12
    }
  }
}
```

### 3. Accept Recommendation

**Endpoint**: `POST /api/adaptive-learning/accept-recommendation`

**Authentication**: Required

**Request Body**:
```json
{
  "recommendation_id": 1,
  "user_id": 5
}
```

**Response**:
```json
{
  "success": true,
  "message": "Recommendation accepted",
  "data": {
    "recommendation_id": 1,
    "module_id": 2,
    "accepted_at": "2024-02-15T10:35:00Z"
  }
}
```

### 4. Decline Recommendation

**Endpoint**: `POST /api/adaptive-learning/decline-recommendation`

**Authentication**: Required

**Request Body**:
```json
{
  "recommendation_id": 1,
  "user_id": 5,
  "reason": "Already familiar with this topic"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Recommendation declined",
  "data": {
    "recommendation_id": 1,
    "declined_at": "2024-02-15T10:35:00Z",
    "reappear_after": "2024-02-22T10:35:00Z"
  }
}
```

### 5. Get Performance Trends

**Endpoint**: `GET /api/adaptive-learning/trends`

**Authentication**: Required (Manager/Admin)

**Parameters**:
- `user_id` (query): User ID (optional, for manager to view team)
- `time_period` (query): '30d', '60d', '90d' (default: '30d')

**Response**:
```json
{
  "success": true,
  "data": {
    "user_id": 5,
    "time_period": "30d",
    "trends": [
      {
        "category": "phishing",
        "period_start": "2024-01-16",
        "period_end": "2024-02-15",
        "average_score": 82.5,
        "session_count": 3,
        "trend_direction": "improving",
        "improvement_percentage": 12.5
      }
    ]
  }
}
```

### 6. Record Training Session (Extended)

**Endpoint**: `POST /api/progress/{module_id}`

**Authentication**: Required

**Request Body** (extended):
```json
{
  "user_id": 5,
  "score": 85,
  "module_category": "phishing",
  "difficulty_level": "intermediate",
  "is_remediation": false,
  "completed_at": "2024-02-15T10:30:00Z"
}
```

**Response**:
```json
{
  "success": true,
  "data": {
    "module_id": 1,
    "score": 85,
    "recommendations_generated": true,
    "difficulty_progression": {
      "status": "promoted",
      "new_difficulty": "advanced"
    }
  }
}
```

---

## UI Components

### 1. Recommendation Widget (Dashboard)

**Location**: Trainee Dashboard (main area)

**Display**:
- Top 3 personalized recommendations
- Module title, category, difficulty level
- Reason for recommendation
- "Start Training" button
- "Dismiss" option

**Interactions**:
- Click "Start Training" → Navigate to module
- Click "Dismiss" → Decline recommendation (7-day cooldown)
- Hover → Show full reason text

**Responsive**: Mobile-friendly card layout

### 2. Performance Analytics Panel

**Location**: Trainee Dashboard (sidebar or separate page)

**Display**:
- Category Average for each security topic
- Visual indicators (weak, average, strong, mastered)
- Trend arrows (improving, declining, stable)
- Session count per category
- Overall average score

**Interactions**:
- Click category → Show detailed history
- Filter by time period (30d, 60d, 90d)

### 3. Difficulty Indicator

**Location**: Module card or training interface

**Display**:
- Current difficulty level (Beginner, Intermediate, Advanced)
- "Level Up" notification when promoted
- Progress toward next level

### 4. Remediation Status

**Location**: Dashboard widget or separate panel

**Display**:
- Active remediation attempts
- Consecutive failures count
- Remediation module assigned
- Progress toward remediation completion

---

## Performance Considerations

### Caching Strategy

1. **Performance Analysis Cache** (1-hour TTL):
   - Cache Category_Average calculations
   - Invalidate on new training session completion
   - Use Redis or database cache table

2. **Recommendation Cache** (30-minute TTL):
   - Cache generated recommendations
   - Regenerate when weak categories change

3. **Query Optimization**:
   - Index on (user_id, module_category, completed_at)
   - Index on (user_id, status) for recommendations
   - Partition training_sessions by year for large datasets

### SLA Compliance

- **Recommendation Retrieval**: < 500ms (p95)
- **Performance Analysis**: < 500ms (p95)
- **Trend Calculation**: < 1000ms (p95)
- **Dashboard Load**: < 2000ms (p95)

### Scalability

- Support up to 10,000 concurrent users
- Handle 100+ training sessions per user
- Process recommendations asynchronously for large user bases
- Use background jobs for cache invalidation

---

## Error Handling

### Data Validation

1. **Score Validation**:
   - Scores must be between 0 and 100
   - Reject invalid scores and log error
   - Alert admin if validation fails

2. **Session Validation**:
   - Require user_id, module_id, completed_at
   - Verify user exists and is trainee role
   - Verify module exists and is active

3. **Category Validation**:
   - Validate category against allowed list
   - Reject unknown categories

### Error Logging

- Log all validation failures with context
- Log recommendation generation errors
- Log cache invalidation failures
- Alert admin on critical errors

### Consistency Checks

- Daily integrity check: Verify all sessions have valid scores
- Weekly consistency check: Verify Category_Average calculations
- Monthly audit: Verify recommendation history accuracy

---

## Integration Points

### 1. Training Module Completion

**Trigger**: User completes training module and submits score

**Flow**:
1. Score recorded in training_sessions
2. Module category and difficulty captured
3. Performance analysis cache invalidated
4. Weak category analysis triggered
5. New recommendations generated
6. Dashboard updated with new recommendations

### 2. Dashboard Display

**Trigger**: Trainee loads dashboard

**Flow**:
1. Fetch top 3 recommendations from cache
2. Fetch performance analysis summary
3. Display recommendation widget
4. Display performance analytics panel
5. Show difficulty indicators

### 3. Manager Reporting

**Trigger**: Manager requests team performance report

**Flow**:
1. Fetch performance trends for team members
2. Calculate category averages across team
3. Identify systemic weak areas
4. Generate trend report
5. Display on manager dashboard

### 4. Admin Monitoring

**Trigger**: Admin accesses system monitoring

**Flow**:
1. Run daily data integrity checks
2. Verify cache consistency
3. Check for orphaned recommendations
4. Generate consistency report
5. Alert on anomalies

---

## Testing Strategy

### Unit Tests

**Performance Analysis**:
- Test Category_Average calculation with various score distributions
- Test weighted average calculation with recent/old sessions
- Test weak category identification logic
- Test edge cases (no sessions, single session, all perfect scores)

**Recommendation Engine**:
- Test recommendation generation for different performance profiles
- Test recommendation loop prevention
- Test priority scoring
- Test edge cases (no weak categories, all categories weak)

**Difficulty Progression**:
- Test promotion logic for each difficulty level
- Test demotion logic
- Test edge cases (insufficient data, perfect scores, failing scores)

### Integration Tests

**End-to-End Flow**:
- Complete training session → Verify session recorded
- Verify performance analysis updated
- Verify recommendations generated
- Verify dashboard displays recommendations

**API Endpoints**:
- Test all endpoints with valid/invalid inputs
- Test authentication and authorization
- Test error responses
- Test response times

### Performance Tests

- Load test with 1000+ concurrent users
- Test query performance with 100+ sessions per user
- Test cache effectiveness
- Verify SLA compliance

---

## Implementation Roadmap

### Phase 1: Foundation (Week 1-2)
- Create database tables
- Implement Category_Average calculation
- Implement weak category identification
- Create API endpoints (read-only)

### Phase 2: Recommendations (Week 3-4)
- Implement recommendation engine
- Implement recommendation storage
- Create accept/decline endpoints
- Implement recommendation loop prevention

### Phase 3: UI & Dashboard (Week 5-6)
- Create recommendation widget
- Create performance analytics panel
- Integrate with existing dashboard
- Add difficulty indicators

### Phase 4: Advanced Features (Week 7-8)
- Implement difficulty progression
- Implement remediation tracking
- Create manager reporting
- Implement trend analysis

### Phase 5: Optimization & Testing (Week 9-10)
- Implement caching strategy
- Performance optimization
- Comprehensive testing
- Documentation

---

## Monitoring & Maintenance

### Key Metrics

- Average recommendation generation time
- Cache hit rate
- Recommendation acceptance rate
- Performance analysis accuracy
- System uptime

### Maintenance Tasks

- Weekly: Verify cache consistency
- Monthly: Audit recommendation history
- Quarterly: Review algorithm effectiveness
- Annually: Update difficulty thresholds based on data

---

## Security Considerations

1. **Data Privacy**: Ensure performance data is only visible to user and authorized managers
2. **API Security**: Validate all inputs, use rate limiting
3. **Database Security**: Use parameterized queries, encrypt sensitive data
4. **Audit Logging**: Log all recommendation actions for compliance



---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Category Average Calculation Correctness

*For any trainee and any security category with 2 or more training sessions, the calculated Category_Average must equal the arithmetic mean of all session scores in that category.*

**Validates: Requirements 1.3, 2.1**

**Rationale**: The Category_Average is the foundation for all performance analysis. This property ensures the calculation is mathematically correct.

### Property 2: Weighted Average Reflects Recent Performance

*For any trainee and any security category, the weighted_average (with recent sessions weighted 1.5x) must be greater than or equal to the simple average when recent sessions have higher scores than older sessions, and less than or equal to the simple average when recent sessions have lower scores.*

**Validates: Requirements 2.3**

**Rationale**: The weighting mechanism should amplify the impact of recent performance, making the weighted average more responsive to current competency.

### Property 3: Weak Category Identification Accuracy

*For any trainee, a category is marked as weak if and only if: (a) the trainee has completed 2 or more sessions in that category, AND (b) the weighted_average is below 70%.*

**Validates: Requirements 2.1, 2.2**

**Rationale**: Ensures weak category identification is consistent and accurate, preventing false positives and false negatives.

### Property 4: Insufficient Data Handling

*For any trainee and any security category with fewer than 2 training sessions, the category must not be classified as weak, regardless of the average score.*

**Validates: Requirements 2.2**

**Rationale**: Prevents unreliable classifications based on insufficient data.

### Property 5: Recommendation Difficulty Assignment Correctness

*For any trainee's most recent training session in a category:*
- *If score < 60%, the recommended module must have difficulty_level = 'beginner'*
- *If 60% ≤ score ≤ 80%, the recommended module must have difficulty_level = 'intermediate'*
- *If score > 80%, the recommended module must have difficulty_level = 'advanced'*

**Validates: Requirements 3.1, 3.2, 3.3, 7.1, 7.2, 7.3**

**Rationale**: Ensures recommendations match performance levels, providing appropriate challenge.

### Property 6: Weak Category Prioritization

*For any trainee with multiple weak categories, the first recommendation must be for the category with the lowest weighted_average.*

**Validates: Requirements 3.4**

**Rationale**: Ensures the system prioritizes the most critical learning needs.

### Property 7: Mastery Detection Correctness

*For any trainee and any security category, the category is marked as mastered if and only if: (a) the trainee has completed 3 or more sessions in that category, AND (b) the weighted_average is 80% or higher.*

**Validates: Requirements 4.1**

**Rationale**: Ensures mastery is only granted when sufficient evidence of competency exists.

### Property 8: Difficulty Progression Monotonicity

*For any trainee in a security category, if the average score of the last 5 sessions increases, the difficulty_tier must either increase or remain the same, never decrease.*

**Validates: Requirements 4.3, 4.5**

**Rationale**: Ensures difficulty progression is monotonic with performance improvement.

### Property 9: Consecutive Failure Detection

*For any trainee and any security category, if the last 2 training sessions both have scores below 60%, the system must assign a beginner-level remediation module in that category.*

**Validates: Requirements 5.1**

**Rationale**: Ensures struggling learners receive appropriate support.

### Property 10: Remediation Module Locking

*For any trainee with an active remediation assignment in a category, advanced-level modules in that category must be locked until the trainee completes the remediation module with a score above 70%.*

**Validates: Requirements 5.2, 5.3**

**Rationale**: Ensures trainees build foundational knowledge before advancing.

### Property 11: Recommendation Loop Prevention

*For any trainee, a module that was completed within the last 30 days must not appear in the generated recommendations.*

**Validates: Requirements 9.1**

**Rationale**: Ensures variety in training recommendations and prevents repetitive content.

### Property 12: Declined Recommendation Cooldown

*For any trainee, a recommendation that was declined must not be regenerated for the same trainee for at least 7 days.*

**Validates: Requirements 9.4**

**Rationale**: Respects trainee preferences and prevents recommendation fatigue.

### Property 13: Score Validation

*For any training session, if the final_score is not between 0 and 100 (inclusive), the session must be rejected and an error must be logged.*

**Validates: Requirements 10.1**

**Rationale**: Ensures data integrity and prevents invalid calculations.

### Property 14: Required Field Validation

*For any training session, if any of the required fields (user_id, module_id, completed_at) are missing or invalid, the session must be rejected and an error must be logged.*

**Validates: Requirements 10.2**

**Rationale**: Ensures complete and valid data collection.

### Property 15: Performance History Retrieval Ordering

*For any trainee, when retrieving performance history for a category, the sessions must be returned in descending order by completed_at (most recent first).*

**Validates: Requirements 1.2**

**Rationale**: Ensures consistent and predictable data retrieval for analysis.

### Property 16: Category Average Calculation Idempotence

*For any trainee and any security category, calculating the Category_Average multiple times on the same set of training sessions must produce identical results.*

**Validates: Requirements 1.3**

**Rationale**: Ensures calculation logic is deterministic and reliable.

### Property 17: Trend Calculation Accuracy

*For any trainee and any time period (30d, 60d, 90d), the calculated trend must correctly reflect the change in Category_Average between the start and end of the period.*

**Validates: Requirements 8.1, 8.2, 8.3**

**Rationale**: Ensures trend analysis accurately represents performance changes.

### Property 18: Recommendation Reason Accuracy

*For any generated recommendation, the reason field must accurately describe why the recommendation was generated (e.g., "Improve weak area: Phishing" for weak categories, "Master your skills: Phishing" for mastered categories).*

**Validates: Requirements 6.2**

**Rationale**: Ensures trainees understand the rationale for recommendations.

### Property 19: Not-Attempted Category Handling

*For any trainee and any security category with zero training sessions, the category must be marked as "not_attempted" and a beginner-level module must be recommended.*

**Validates: Requirements 2.4, 7.5**

**Rationale**: Ensures all categories are eventually covered in training.

### Property 20: Recommendation Expiration

*For any generated recommendation, if the recommendation is not accepted or declined within 30 days, it must be marked as expired and must not appear in future recommendation lists.*

**Validates: Requirements 9.5**

**Rationale**: Ensures recommendations remain relevant and current.

---

## Property Reflection & Consolidation

After analyzing all 50 acceptance criteria and generating 20 initial properties, the following consolidations were made to eliminate redundancy:

**Consolidated Properties**:
- Properties 1 & 16 (Category Average Calculation): Combined into single property emphasizing both correctness and idempotence
- Properties 3 & 4 (Weak Category Identification): Combined into single comprehensive property covering both accuracy and data sufficiency
- Properties 5 & 7 (Recommendation Difficulty): Combined into single property covering all three score ranges
- Properties 8 & 9 (Difficulty Progression): Combined into single property covering both promotion and demotion logic

**Final Property Count**: 20 properties covering all testable acceptance criteria

**Non-Testable Criteria** (requiring integration or smoke tests):
- Requirement 1.4: Data retention policy (SMOKE test)
- Requirement 1.5: Query performance SLA (INTEGRATION test)
- Requirement 2.5: Cache update timing (INTEGRATION test)
- Requirement 4.4: UI notification display (EXAMPLE test)
- Requirement 6.1, 6.3, 6.4, 6.5: Dashboard UI display (EXAMPLE tests)
- Requirement 8.5: API endpoint availability (INTEGRATION test)
- Requirement 10.4: Error logging and alerting (INTEGRATION test)
- Requirement 10.5: Scheduled integrity checks (SMOKE test)

---

## Testing Strategy

### Property-Based Testing Approach

The Adaptive Learning feature is well-suited for property-based testing because:

1. **Pure Functions**: Core algorithms (Category_Average, weak category detection, recommendation generation) are pure functions with clear input/output behavior
2. **Universal Properties**: Many requirements define universal properties that should hold across all valid inputs
3. **Large Input Space**: Performance data varies widely (scores 0-100, timestamps, user counts, session counts)
4. **Edge Case Coverage**: Property-based testing will automatically discover edge cases (boundary scores, single sessions, perfect scores, etc.)

### Unit Tests (Example-Based)

**Recommendation Display**:
- Test that top 3 recommendations are displayed on dashboard
- Test that "Continue your journey" message appears when no weak categories
- Test that "Master your skills" message appears when all modules completed
- Test that recommendation click navigates to module

**Remediation Tracking**:
- Test that remediation history is displayed correctly
- Test that consecutive failures trigger remediation assignment
- Test that remediation completion unlocks intermediate modules

**Error Handling**:
- Test that invalid scores are rejected with appropriate error message
- Test that missing required fields are rejected
- Test that data inconsistencies are logged

### Integration Tests

**Performance SLA**:
- Generate 100 training sessions for a user
- Query performance analysis
- Verify response time < 500ms

**API Endpoints**:
- Test GET /api/adaptive-learning/recommendations with various user profiles
- Test POST /api/adaptive-learning/accept-recommendation
- Test POST /api/adaptive-learning/decline-recommendation
- Test GET /api/adaptive-learning/trends for manager dashboard

**Cache Invalidation**:
- Record a training session
- Verify cache is invalidated
- Verify new recommendations are generated within 1 hour

**Data Integrity**:
- Run daily integrity checks
- Verify consistency report is generated
- Verify orphaned recommendations are detected

### Property-Based Test Configuration

**Test Framework**: Hypothesis (Python) or fast-check (JavaScript)

**Minimum Iterations**: 100 per property test

**Test Generators**:
- Score generator: Random integers 0-100
- Timestamp generator: Random dates within 12-month window
- Category generator: Random selection from 7 categories
- User generator: Random user IDs with varying session counts
- Session generator: Random training sessions with valid data

**Example Property Test** (Pseudocode):

```
@property_test
def test_category_average_calculation(scores):
    # scores: list of 2-100 random integers 0-100
    assume(len(scores) >= 2)
    
    # Calculate average
    calculated_avg = calculate_category_average(scores)
    expected_avg = sum(scores) / len(scores)
    
    # Verify correctness
    assert abs(calculated_avg - expected_avg) < 0.01
    assert 0 <= calculated_avg <= 100
```

### Test Coverage Goals

- **Unit Tests**: 90%+ code coverage for core algorithms
- **Property Tests**: 100% coverage of acceptance criteria marked as PROPERTY
- **Integration Tests**: All API endpoints and external integrations
- **Performance Tests**: SLA compliance verification

---

## Acceptance Criteria Mapping

| Requirement | Acceptance Criteria | Test Type | Property # |
|---|---|---|---|
| 1 | 1.1 | PROPERTY | 1 |
| 1 | 1.2 | PROPERTY | 15 |
| 1 | 1.3 | PROPERTY | 1, 16 |
| 1 | 1.4 | SMOKE | - |
| 1 | 1.5 | INTEGRATION | - |
| 2 | 2.1 | PROPERTY | 3 |
| 2 | 2.2 | PROPERTY | 4 |
| 2 | 2.3 | PROPERTY | 2 |
| 2 | 2.4 | PROPERTY | 19 |
| 2 | 2.5 | INTEGRATION | - |
| 3 | 3.1 | PROPERTY | 5 |
| 3 | 3.2 | PROPERTY | 5 |
| 3 | 3.3 | PROPERTY | 5 |
| 3 | 3.4 | PROPERTY | 6 |
| 3 | 3.5 | PROPERTY | 19 |
| 4 | 4.1 | PROPERTY | 7 |
| 4 | 4.2 | PROPERTY | 7 |
| 4 | 4.3 | PROPERTY | 8 |
| 4 | 4.4 | EXAMPLE | - |
| 4 | 4.5 | PROPERTY | 8 |
| 5 | 5.1 | PROPERTY | 9 |
| 5 | 5.2 | PROPERTY | 10 |
| 5 | 5.3 | PROPERTY | 10 |
| 5 | 5.4 | PROPERTY | 9 |
| 5 | 5.5 | PROPERTY | 18 |
| 6 | 6.1 | EXAMPLE | - |
| 6 | 6.2 | PROPERTY | 18 |
| 6 | 6.3 | EXAMPLE | - |
| 6 | 6.4 | EXAMPLE | - |
| 6 | 6.5 | EXAMPLE | - |
| 7 | 7.1 | PROPERTY | 5 |
| 7 | 7.2 | PROPERTY | 5 |
| 7 | 7.3 | PROPERTY | 5 |
| 7 | 7.4 | PROPERTY | 5 |
| 7 | 7.5 | PROPERTY | 19 |
| 8 | 8.1 | PROPERTY | 17 |
| 8 | 8.2 | PROPERTY | 17 |
| 8 | 8.3 | PROPERTY | 17 |
| 8 | 8.4 | PROPERTY | 17 |
| 8 | 8.5 | INTEGRATION | - |
| 9 | 9.1 | PROPERTY | 11 |
| 9 | 9.2 | PROPERTY | 6 |
| 9 | 9.3 | PROPERTY | 6 |
| 9 | 9.4 | PROPERTY | 12 |
| 9 | 9.5 | PROPERTY | 20 |
| 10 | 10.1 | PROPERTY | 13 |
| 10 | 10.2 | PROPERTY | 14 |
| 10 | 10.3 | PROPERTY | 13 |
| 10 | 10.4 | INTEGRATION | - |
| 10 | 10.5 | SMOKE | - |

