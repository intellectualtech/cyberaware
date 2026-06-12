# Implementation Plan: Adaptive Learning Feature

## Overview

This implementation plan transforms CyberApp into an intelligent, personalized learning system by building the Adaptive Learning Engine. The feature analyzes individual performance patterns across seven security categories, identifies weak areas, and dynamically recommends follow-up training with adjusted difficulty levels. Implementation follows a foundation-first approach: database schema → core algorithms → API endpoints → UI integration → optimization.

---

## Tasks

### Phase 1: Foundation & Database Schema

- [x] 1. Set up database schema and migrations
  - Create `adaptive_learning_recommendations` table for storing personalized recommendations
  - Create `performance_analysis_cache` table for caching Category_Average calculations
  - Create `remediation_tracking` table for tracking remediation attempts and progress
  - Extend `training_sessions` table with `module_category`, `difficulty_level`, `is_remediation`, `recommendation_id` columns
  - Extend `user_module_progress` table with `recommended_at`, `recommendation_reason`, `difficulty_tier`, `is_mastered` columns
  - Create database indexes on (user_id, category), (user_id, status), (created_at) for query optimization
  - _Requirements: 1.1, 10.1, 10.2_
  - **Effort: Large**

- [x] 2. Create database migration script
  - Write PHP migration script to safely apply schema changes
  - Include rollback functionality for each migration step
  - Add data validation checks before and after migration
  - Test migration on development database
  - _Requirements: 1.1, 10.1_
  - **Effort: Medium**

- [ ] 3. Create core data model classes
  - Create `TrainingSession` class with properties: id, user_id, module_id, final_score, completed_at, module_category, difficulty_level, is_remediation, recommendation_id
  - Create `PerformanceAnalysis` class with properties: user_id, category, category_average, weighted_average, session_count, recent_session_count, is_weak, is_mastered, difficulty_tier, last_updated
  - Create `Recommendation` class with properties: id, user_id, module_id, reason, difficulty_level, priority_score, created_at, accepted_at, declined_at, expires_at, status
  - Create `RemediationTracking` class with properties: id, user_id, category, remediation_level, consecutive_failures, last_failure_date, remediation_module_id, remediation_score, remediation_completed_at, status, created_at, updated_at
  - Add validation methods to each class
  - _Requirements: 1.1, 10.1, 10.2_
  - **Effort: Medium**

- [ ]* 3.1 Write unit tests for data model classes
  - Test object instantiation and property assignment
  - Test validation methods with valid and invalid data
  - Test serialization/deserialization
  - _Requirements: 10.1, 10.2_
  - **Effort: Small**

### Phase 2: Core Algorithms - Performance Analysis

- [ ] 4. Implement Category Average calculation algorithm
  - Create `PerformanceAnalysisEngine` class with `calculateCategoryAverage(user_id, category)` method
  - Implement logic to retrieve all training sessions for a user in a specific category
  - Calculate simple average (sum of scores / count)
  - Calculate weighted average with recent sessions (last 30 days) weighted at 1.5x
  - Return object with category_average, weighted_average, session_count, recent_session_count
  - Handle edge case: fewer than 2 sessions returns insufficient_data status
  - _Requirements: 1.1, 1.3, 2.1, 2.3_
  - **Effort: Medium**

- [ ]* 4.1 Write property test for Category Average calculation
  - **Property 1: Category Average Bounds** - Verify result is always between 0-100
  - **Property 6: Idempotence** - Verify multiple calculations produce identical results
  - **Property 4: Round-Trip Consistency** - Verify recorded sessions are retrieved correctly
  - _Requirements: 1.3, 2.1_
  - **Effort: Medium**

- [ ] 5. Implement weak category identification algorithm
  - Create `identifyWeakCategories(user_id)` method in PerformanceAnalysisEngine
  - Iterate through all seven security categories (phishing, credential, social, malware, link, password, ransomware)
  - For each category, call calculateCategoryAverage
  - Classify categories: weak (< 70%), not_attempted (0 sessions), mastered (>= 80% with 3+ sessions)
  - Sort weak categories by priority (lowest average first)
  - Return object with weak_categories, not_attempted, mastered arrays
  - _Requirements: 2.1, 2.2, 2.3, 2.4_
  - **Effort: Medium**

- [ ]* 5.1 Write property test for weak category identification
  - **Property 2: Weak Category Consistency** - Verify all weak categories have average < 70%
  - **Property 7: Idempotence** - Verify multiple analyses produce identical results
  - **Property 8: Metamorphic** - Verify lower averages are prioritized higher
  - _Requirements: 2.1, 2.2_
  - **Effort: Medium**

- [ ] 6. Implement performance analysis cache layer
  - Create `PerformanceAnalysisCache` class with methods: get(user_id, category), set(user_id, category, analysis), invalidate(user_id, category), invalidateUser(user_id)
  - Implement 1-hour TTL for cached results
  - Store cache in database table `performance_analysis_cache`
  - Add cache expiration check on retrieval
  - Implement automatic cache invalidation on new training session completion
  - _Requirements: 1.5, 2.5_
  - **Effort: Medium**

- [ ]* 6.1 Write unit tests for cache layer
  - Test cache hit and miss scenarios
  - Test TTL expiration
  - Test cache invalidation
  - Test concurrent access patterns
  - _Requirements: 1.5_
  - **Effort: Small**

### Phase 3: Core Algorithms - Recommendations & Difficulty

- [ ] 7. Implement recommendation engine
  - Create `RecommendationEngine` class with `generateRecommendations(user_id)` method
  - Call identifyWeakCategories to get weak, not_attempted, and mastered categories
  - Priority 1: Generate recommendations for weak categories (beginner level remediation)
  - Priority 2: Generate recommendations for not_attempted categories (beginner level)
  - Priority 3: Generate recommendations for mastered categories (advanced level progression)
  - Check for recent recommendations (last 30 days) to prevent loops
  - Sort by priority_score and limit to top 3 recommendations
  - Store recommendations in database with 30-day expiration
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 9.1, 9.2_
  - **Effort: Large**

- [ ]* 7.1 Write property test for recommendation engine
  - **Property 5: Round-Trip Persistence** - Verify stored recommendations are retrieved correctly
  - **Property 8: Metamorphic Prioritization** - Verify weak categories are recommended before mastered
  - **Property 12: Confluence Independence** - Verify recommendation order doesn't affect other users
  - _Requirements: 3.1, 3.4, 9.1_
  - **Effort: Medium**

- [ ] 8. Implement difficulty progression algorithm
  - Create `DifficultyProgressionEngine` class with `calculateDifficultyProgression(user_id, category)` method
  - Retrieve last 5 training sessions in category
  - Calculate average of recent sessions
  - Implement progression rules:
    - Beginner → Intermediate: average >= 80%
    - Intermediate → Advanced: average >= 85%
    - Advanced → Intermediate: average < 75%
    - Intermediate → Beginner: average < 60%
  - Return object with new_difficulty_level, status (promoted/demoted/maintained), reason
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 7.1, 7.2, 7.3, 7.4_
  - **Effort: Medium**

- [ ]* 8.1 Write property test for difficulty progression
  - **Property 3: Difficulty Assignment Correctness** - Verify difficulty matches score ranges
  - **Property 9: Metamorphic Monotonicity** - Verify difficulty increases with score improvement
  - _Requirements: 4.1, 4.3, 7.1, 7.3_
  - **Effort: Medium**

- [ ] 9. Implement remediation tracking engine
  - Create `RemediationTrackingEngine` class with methods: trackRemediationAttempt(user_id, category, score), getRemediationStatus(user_id, category)
  - Track consecutive failures in a category
  - When 2 consecutive failures detected: assign beginner remediation module
  - Lock advanced modules in category until remediation score > 70%
  - Update remediation status: active, resolved, escalated
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_
  - **Effort: Medium**

- [ ]* 9.1 Write unit tests for remediation tracking
  - Test consecutive failure detection
  - Test remediation module assignment
  - Test module locking/unlocking logic
  - Test status transitions
  - _Requirements: 5.1, 5.2, 5.3_
  - **Effort: Small**

### Phase 4: Core Algorithms - Trends & Analysis

- [ ] 10. Implement trend analysis engine
  - Create `TrendAnalysisEngine` class with methods: calculateTrends(user_id, time_period), getTrendReport(user_id)
  - Support time periods: 30d, 60d, 90d
  - For each category, calculate average score over period
  - Determine trend direction: improving (10%+ increase), declining (10%+ decrease), stable
  - Calculate improvement_percentage
  - Return trend data with category, period_start, period_end, average_score, session_count, trend_direction, improvement_percentage
  - _Requirements: 8.1, 8.2, 8.3, 8.4_
  - **Effort: Medium**

- [ ]* 10.1 Write unit tests for trend analysis
  - Test trend calculation for different time periods
  - Test trend direction classification
  - Test improvement percentage calculation
  - _Requirements: 8.1, 8.2, 8.3_
  - **Effort: Small**

### Phase 5: API Endpoints

- [ ] 11. Create adaptive learning API endpoints file
  - Create `/api/endpoints/adaptive-learning.php` with routing logic
  - Implement request validation and authentication checks
  - Add error handling and logging
  - _Requirements: 1.1, 3.1, 6.1, 8.1_
  - **Effort: Small**

- [ ] 12. Implement GET /api/adaptive-learning/recommendations endpoint
  - Accept parameters: user_id (optional), limit (default: 3, max: 10)
  - Call RecommendationEngine.generateRecommendations(user_id)
  - Call identifyWeakCategories for analysis data
  - Return JSON with recommendations array and analysis object
  - Include module metadata (title, category, difficulty_level)
  - Handle errors: invalid user_id, insufficient data
  - _Requirements: 3.1, 6.1, 6.2_
  - **Effort: Medium**

- [ ]* 12.1 Write integration test for recommendations endpoint
  - Test with user having weak categories
  - Test with user having no weak categories
  - Test with user having no training history
  - Test limit parameter
  - _Requirements: 3.1, 6.1_
  - **Effort: Small**

- [ ] 13. Implement GET /api/adaptive-learning/performance-analysis endpoint
  - Accept parameters: user_id (optional), category (optional)
  - Call identifyWeakCategories and calculateCategoryAverage for each category
  - Return JSON with categories array and summary object
  - Include: category_average, weighted_average, session_count, is_weak, is_mastered, difficulty_tier, trend
  - Handle errors: invalid user_id, invalid category
  - _Requirements: 2.1, 2.2, 2.3, 6.1_
  - **Effort: Medium**

- [ ]* 13.1 Write integration test for performance analysis endpoint
  - Test with single category filter
  - Test without category filter (all categories)
  - Test with user having no training history
  - _Requirements: 2.1, 6.1_
  - **Effort: Small**

- [ ] 14. Implement POST /api/adaptive-learning/accept-recommendation endpoint
  - Accept parameters: recommendation_id, user_id
  - Validate recommendation exists and belongs to user
  - Update recommendation status to 'accepted'
  - Set accepted_at timestamp
  - Return JSON with recommendation_id, module_id, accepted_at
  - Handle errors: invalid recommendation_id, recommendation already accepted/declined
  - _Requirements: 3.1, 6.1_
  - **Effort: Small**

- [ ]* 14.1 Write integration test for accept recommendation endpoint
  - Test accepting valid recommendation
  - Test accepting already-accepted recommendation
  - Test accepting non-existent recommendation
  - _Requirements: 3.1_
  - **Effort: Small**

- [ ] 15. Implement POST /api/adaptive-learning/decline-recommendation endpoint
  - Accept parameters: recommendation_id, user_id, reason (optional)
  - Validate recommendation exists and belongs to user
  - Update recommendation status to 'declined'
  - Set declined_at timestamp
  - Calculate reappear_after (7 days from now)
  - Return JSON with recommendation_id, declined_at, reappear_after
  - Handle errors: invalid recommendation_id, recommendation already accepted/declined
  - _Requirements: 9.4_
  - **Effort: Small**

- [ ]* 15.1 Write integration test for decline recommendation endpoint
  - Test declining valid recommendation
  - Test declining already-declined recommendation
  - Test declining non-existent recommendation
  - _Requirements: 9.4_
  - **Effort: Small**

- [ ] 16. Implement GET /api/adaptive-learning/trends endpoint
  - Accept parameters: user_id (optional), time_period (default: '30d')
  - Validate time_period is one of: 30d, 60d, 90d
  - Call TrendAnalysisEngine.calculateTrends(user_id, time_period)
  - Return JSON with trends array containing category, period_start, period_end, average_score, session_count, trend_direction, improvement_percentage
  - Handle errors: invalid user_id, invalid time_period
  - _Requirements: 8.1, 8.4, 8.5_
  - **Effort: Medium**

- [ ]* 16.1 Write integration test for trends endpoint
  - Test with different time periods
  - Test with user having no training history
  - Test trend direction classification
  - _Requirements: 8.1, 8.4_
  - **Effort: Small**

- [ ] 17. Extend POST /api/progress/{module_id} endpoint
  - Modify existing progress endpoint to capture adaptive learning context
  - Accept additional parameters: module_category, difficulty_level, is_remediation, recommendation_id
  - After recording session, call PerformanceAnalysisEngine to update cache
  - Call DifficultyProgressionEngine to check for difficulty changes
  - Call RecommendationEngine to generate new recommendations if needed
  - Return extended JSON including recommendations_generated, difficulty_progression status
  - _Requirements: 1.1, 3.1, 4.1, 4.3_
  - **Effort: Medium**

- [ ]* 17.1 Write integration test for extended progress endpoint
  - Test recording session with all adaptive learning fields
  - Test difficulty progression trigger
  - Test recommendation generation trigger
  - _Requirements: 1.1, 3.1, 4.1_
  - **Effort: Small**

### Phase 6: Dashboard Widget Integration

- [ ] 18. Create recommendation widget component
  - Create `/trainee/widgets/adaptive-learning-widget.php` component
  - Fetch top 3 recommendations via API endpoint
  - Display recommendation cards with: module title, category, difficulty level, reason
  - Add "Start Training" button linking to module
  - Add "Dismiss" button for declining recommendation
  - Handle empty state: "No recommendations yet"
  - Add loading state and error handling
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_
  - **Effort: Medium**

- [ ] 19. Create performance analytics panel
  - Create `/trainee/widgets/performance-analytics.php` component
  - Fetch performance analysis via API endpoint
  - Display category cards with: category name, average score, visual indicator (weak/average/strong/mastered)
  - Add trend arrows (improving/declining/stable)
  - Show session count per category
  - Display overall average score
  - Add time period filter (30d, 60d, 90d)
  - _Requirements: 6.1, 6.2_
  - **Effort: Medium**

- [ ] 20. Integrate widgets into trainee dashboard
  - Modify `/trainee/dashboard.php` to include adaptive learning widgets
  - Position recommendation widget in main content area
  - Position performance analytics in sidebar or separate section
  - Add responsive layout for mobile devices
  - Test widget loading and error states
  - _Requirements: 6.1, 6.2, 6.3_
  - **Effort: Small**

- [ ]* 20.1 Write unit tests for dashboard widgets
  - Test widget rendering with sample data
  - Test empty state rendering
  - Test error state rendering
  - Test responsive layout
  - _Requirements: 6.1, 6.2_
  - **Effort: Small**

- [ ] 21. Create difficulty indicator component
  - Create `/trainee/widgets/difficulty-indicator.php` component
  - Display current difficulty level (Beginner, Intermediate, Advanced)
  - Show progress toward next level
  - Display "Level Up" notification when promoted
  - Add visual progress bar
  - _Requirements: 4.3, 4.4_
  - **Effort: Small**

- [ ] 22. Create remediation status widget
  - Create `/trainee/widgets/remediation-status.php` component
  - Display active remediation attempts
  - Show consecutive failures count
  - Display assigned remediation module
  - Show progress toward remediation completion
  - _Requirements: 5.5_
  - **Effort: Small**

### Phase 7: Data Validation & Error Handling

- [ ] 23. Implement comprehensive data validation
  - Create `DataValidator` class with methods: validateScore(score), validateSession(session), validateCategory(category)
  - Validate scores are between 0 and 100
  - Validate required fields: user_id, module_id, completed_at
  - Validate user exists and is trainee role
  - Validate module exists and is active
  - Validate category against allowed list
  - Log all validation failures with context
  - _Requirements: 10.1, 10.2, 10.3, 10.4_
  - **Effort: Medium**

- [ ]* 23.1 Write unit tests for data validation
  - Test valid and invalid scores
  - Test valid and invalid sessions
  - Test valid and invalid categories
  - _Requirements: 10.1, 10.2_
  - **Effort: Small**

- [ ] 24. Implement error logging and alerting
  - Create `ErrorLogger` class with methods: logValidationError(context), logCalculationError(context), logCacheError(context)
  - Log all validation failures with user_id, module_id, error details
  - Log recommendation generation errors
  - Log cache invalidation failures
  - Alert admin on critical errors (via email or dashboard)
  - _Requirements: 10.4, 10.5_
  - **Effort: Medium**

- [ ] 25. Implement data consistency checks
  - Create `ConsistencyChecker` class with methods: dailyIntegrityCheck(), weeklyConsistencyCheck(), monthlyAudit()
  - Daily check: Verify all sessions have valid scores (0-100)
  - Weekly check: Verify Category_Average calculations match stored values
  - Monthly audit: Verify recommendation history accuracy
  - Generate consistency report
  - Alert admin if inconsistencies found
  - _Requirements: 10.5_
  - **Effort: Medium**

- [ ]* 25.1 Write unit tests for consistency checks
  - Test integrity check detection of invalid scores
  - Test consistency check detection of calculation errors
  - Test audit report generation
  - _Requirements: 10.5_
  - **Effort: Small**

### Phase 8: Performance Optimization & Caching

- [ ] 26. Optimize database queries
  - Add indexes on (user_id, module_category, completed_at) for training_sessions
  - Add indexes on (user_id, status) for adaptive_learning_recommendations
  - Add indexes on (created_at) for time-based queries
  - Partition training_sessions table by year for large datasets
  - Test query performance: target < 100ms for Category_Average calculation
  - _Requirements: 1.5_
  - **Effort: Medium**

- [ ] 27. Implement Redis caching layer (optional)
  - Create `RedisCache` class as alternative to database cache
  - Implement cache methods: get(key), set(key, value, ttl), delete(key), flush()
  - Set 1-hour TTL for performance analysis cache
  - Set 30-minute TTL for recommendation cache
  - Implement cache invalidation on training session completion
  - Fall back to database cache if Redis unavailable
  - _Requirements: 1.5, 2.5_
  - **Effort: Large**

- [ ] 28. Implement asynchronous recommendation generation
  - Create background job queue for recommendation generation
  - Trigger recommendation generation asynchronously after training session completion
  - Implement job retry logic with exponential backoff
  - Log job execution and failures
  - _Requirements: 1.5_
  - **Effort: Large**

- [ ] 29. Implement batch processing for trend analysis
  - Create batch job for calculating trends for all users
  - Run daily at off-peak hours
  - Cache trend results for 24 hours
  - Implement incremental updates for new sessions
  - _Requirements: 8.1, 8.4_
  - **Effort: Medium**

### Phase 9: Testing & Quality Assurance

- [ ] 30. Write comprehensive unit tests
  - Test all core algorithm classes: PerformanceAnalysisEngine, RecommendationEngine, DifficultyProgressionEngine, RemediationTrackingEngine, TrendAnalysisEngine
  - Test edge cases: empty data, single session, boundary values
  - Test error conditions: invalid inputs, missing data
  - Achieve 80%+ code coverage
  - _Requirements: 10.1, 10.2_
  - **Effort: Large**

- [ ] 31. Write integration tests
  - Test end-to-end flows: session completion → analysis → recommendation generation
  - Test API endpoints with various user scenarios
  - Test database transactions and rollbacks
  - Test cache invalidation and refresh
  - _Requirements: 1.1, 3.1, 6.1_
  - **Effort: Large**

- [ ]* 31.1 Write property-based tests for core algorithms
  - **Property 1: Category Average Bounds** - Generate random scores and verify bounds
  - **Property 2: Weak Category Consistency** - Verify weak category classification
  - **Property 3: Difficulty Assignment Correctness** - Verify difficulty matches score ranges
  - **Property 4: Round-Trip Consistency** - Verify data persistence
  - **Property 5: Round-Trip Persistence** - Verify recommendation storage
  - **Property 6: Idempotence** - Verify calculation determinism
  - **Property 7: Idempotence** - Verify analysis determinism
  - **Property 8: Metamorphic Prioritization** - Verify recommendation ordering
  - **Property 9: Metamorphic Monotonicity** - Verify difficulty progression
  - **Property 10: Invalid Score Handling** - Verify rejection of invalid scores
  - **Property 11: Missing Data Handling** - Verify rejection of incomplete data
  - **Property 12: Confluence Independence** - Verify user independence
  - _Requirements: 1.3, 2.1, 3.1, 4.1, 7.1, 10.1_
  - **Effort: Large**

- [ ] 32. Performance testing
  - Test API endpoints with 1000+ concurrent users
  - Measure response times: target < 500ms for recommendations, < 500ms for analysis, < 1000ms for trends
  - Test with users having 100+ training sessions
  - Identify and optimize bottlenecks
  - _Requirements: 1.5_
  - **Effort: Medium**

- [ ] 33. Security testing
  - Test API authentication and authorization
  - Verify users can only access their own data
  - Test SQL injection prevention
  - Test XSS prevention in UI components
  - Test CSRF protection
  - _Requirements: 10.1, 10.2_
  - **Effort: Medium**

### Phase 10: Documentation & Deployment

- [ ] 34. Create API documentation
  - Document all endpoints: parameters, responses, error codes
  - Include example requests and responses
  - Document authentication requirements
  - Document rate limiting
  - Create API reference guide
  - _Requirements: 8.5_
  - **Effort: Small**

- [ ] 35. Create developer documentation
  - Document core algorithm classes and methods
  - Document database schema and relationships
  - Document caching strategy
  - Document error handling approach
  - Create architecture diagram
  - _Requirements: 1.1, 3.1_
  - **Effort: Small**

- [ ] 36. Create user documentation
  - Document adaptive learning feature for trainees
  - Explain recommendations and how they're generated
  - Explain difficulty levels and progression
  - Create FAQ
  - _Requirements: 6.1, 6.2_
  - **Effort: Small**

- [ ] 37. Prepare deployment package
  - Create migration scripts for production database
  - Create rollback scripts
  - Document deployment steps
  - Create pre-deployment checklist
  - _Requirements: 1.1_
  - **Effort: Small**

- [ ] 38. Deploy to staging environment
  - Run all migrations on staging database
  - Run full test suite
  - Verify all endpoints work correctly
  - Test with staging data
  - Get stakeholder approval
  - _Requirements: 1.1, 3.1, 6.1_
  - **Effort: Medium**

- [ ] 39. Deploy to production
  - Run migrations on production database
  - Monitor system performance and errors
  - Verify all features working correctly
  - Collect user feedback
  - _Requirements: 1.1, 3.1, 6.1_
  - **Effort: Medium**

### Phase 11: Post-Launch Monitoring & Optimization

- [ ] 40. Monitor system performance
  - Track API response times
  - Monitor database query performance
  - Track cache hit rates
  - Monitor error rates and types
  - Create performance dashboard
  - _Requirements: 1.5_
  - **Effort: Small**

- [ ] 41. Gather user feedback
  - Collect trainee feedback on recommendations
  - Collect manager feedback on trends
  - Identify feature gaps or issues
  - Create feedback log
  - _Requirements: 6.1, 6.2_
  - **Effort: Small**

- [ ] 42. Optimize based on feedback
  - Adjust recommendation algorithm based on user feedback
  - Optimize slow queries
  - Improve cache strategy
  - Fix reported bugs
  - _Requirements: 1.5, 3.1_
  - **Effort: Medium**

---

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP delivery, but are strongly recommended for production quality
- Each task references specific requirements for traceability
- Checkpoints are included at phase boundaries to validate progress
- Property-based tests validate universal correctness properties defined in the design document
- Unit tests validate specific examples and edge cases
- Integration tests validate end-to-end workflows
- Database indexes are critical for performance at scale
- Caching strategy is essential for meeting SLA targets (< 500ms response time)
- All API endpoints require authentication and authorization checks
- Error handling and logging are built into each component
- Data validation occurs at multiple layers: API, business logic, database

---

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1", "2", "3"] },
    { "id": 1, "tasks": ["3.1", "4", "5", "6"] },
    { "id": 2, "tasks": ["4.1", "5.1", "6.1", "7", "8", "9"] },
    { "id": 3, "tasks": ["7.1", "8.1", "9.1", "10"] },
    { "id": 4, "tasks": ["10.1", "11", "12", "13", "14", "15", "16", "17"] },
    { "id": 5, "tasks": ["12.1", "13.1", "14.1", "15.1", "16.1", "17.1", "18", "19"] },
    { "id": 6, "tasks": ["20", "21", "22", "23", "24", "25"] },
    { "id": 7, "tasks": ["20.1", "23.1", "25.1", "26", "27", "28", "29"] },
    { "id": 8, "tasks": ["30", "31", "31.1", "32", "33"] },
    { "id": 9, "tasks": ["34", "35", "36", "37"] },
    { "id": 10, "tasks": ["38", "39"] },
    { "id": 11, "tasks": ["40", "41", "42"] }
  ]
}
```

---

## Implementation Approach

This task list follows a **foundation-first, algorithm-centric** approach:

1. **Foundation (Waves 0-1)**: Database schema and data models establish the data layer
2. **Core Algorithms (Waves 2-4)**: Performance analysis, recommendations, difficulty progression, and remediation tracking form the intelligent engine
3. **API Layer (Waves 4-5)**: RESTful endpoints expose algorithms to frontend and external systems
4. **UI Integration (Waves 5-6)**: Dashboard widgets display recommendations and analytics to trainees
5. **Quality & Optimization (Waves 7-8)**: Testing, validation, and performance optimization ensure production readiness
6. **Deployment (Waves 9-11)**: Documentation, staging, production deployment, and post-launch monitoring

Each wave builds on previous waves, with clear dependencies preventing premature implementation. Property-based tests are co-located with implementations to catch correctness issues early.

