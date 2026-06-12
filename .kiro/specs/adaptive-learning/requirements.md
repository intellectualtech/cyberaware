# Adaptive Learning Requirements Document

## Introduction

The Adaptive Learning feature enables CyberApp to personalize the training experience for each employee by analyzing their performance history, identifying weak security topics, and dynamically assigning follow-up training with adjusted difficulty levels. This system transforms CyberApp from a static module-based platform into an intelligent learning system that responds to individual performance patterns, improving training effectiveness and employee security awareness.

The feature tracks performance across all training modules, analyzes performance trends by security category, and automatically recommends and assigns next training based on individual learning needs. Difficulty levels adjust dynamically—increasing for high performers and decreasing with remediation for struggling learners.

---

## Glossary

- **Adaptive_Learning_Engine**: The system component that analyzes performance data, identifies weak categories, and generates personalized training recommendations
- **Performance_History**: Complete record of all training sessions for a user, including scores, completion dates, and module categories
- **Weak_Category**: A security topic (phishing, credential, social, malware, link, password, ransomware) where an employee's average score falls below 70%
- **Performance_Trend**: The pattern of scores over time for a specific category or module
- **Remediation_Module**: A beginner-level training module assigned to address weak performance in a specific category
- **Difficulty_Level**: The complexity tier of a training module (Beginner, Intermediate, Advanced)
- **Personalized_Recommendation**: A training module suggested by the Adaptive_Learning_Engine based on performance analysis
- **Performance_Score**: The numerical score (0-100) achieved by a user on a training session
- **Category_Average**: The mean performance score across all sessions in a specific security category
- **Dashboard_Widget**: A UI component on the trainee dashboard displaying adaptive learning recommendations
- **Training_Session**: A single instance of a user completing a training module, including score and timestamp
- **Module_Category**: One of seven security topics: phishing, credential, social, malware, link, password, ransomware
- **Trainee**: An employee user role with access to training modules and personal dashboard
- **Admin**: A user role with access to system configuration and analytics
- **Manager**: A user role with access to team performance reports and training assignments

---

## Requirements

### Requirement 1: Track Performance History Across All Modules

**User Story:** As a trainee, I want my training performance to be automatically tracked across all modules, so that the system can analyze my learning patterns and provide personalized recommendations.

#### Acceptance Criteria

1. WHEN a trainee completes a training session, THE Adaptive_Learning_Engine SHALL record the session with user_id, module_id, final_score, completed_at, and module_category
2. WHEN querying performance history, THE Adaptive_Learning_Engine SHALL retrieve all training sessions for a user ordered by completed_at descending
3. WHEN a trainee has completed multiple sessions in the same category, THE Adaptive_Learning_Engine SHALL calculate the Category_Average as the mean of all scores in that category
4. THE Adaptive_Learning_Engine SHALL maintain historical data for at least 12 months to enable trend analysis
5. WHEN performance data is queried, THE Adaptive_Learning_Engine SHALL return results within 500ms for users with up to 100 training sessions

### Requirement 2: Analyze Weak Categories Based on Performance

**User Story:** As the system, I want to identify which security categories each trainee struggles with, so that I can recommend targeted remediation training.

#### Acceptance Criteria

1. WHEN analyzing a trainee's performance, THE Adaptive_Learning_Engine SHALL identify weak categories as those with Category_Average below 70%
2. WHEN a trainee has fewer than 2 sessions in a category, THE Adaptive_Learning_Engine SHALL not classify that category as weak (insufficient data)
3. WHEN calculating Category_Average, THE Adaptive_Learning_Engine SHALL weight recent sessions (last 30 days) at 1.5x to reflect current competency
4. WHEN a trainee has no sessions in a category, THE Adaptive_Learning_Engine SHALL mark that category as "not_attempted"
5. THE Adaptive_Learning_Engine SHALL update weak category analysis within 1 hour of a training session completion

### Requirement 3: Dynamically Assign Personalized Follow-Up Training

**User Story:** As a trainee, I want the system to automatically recommend my next training based on my performance, so that I focus on areas where I need improvement.

#### Acceptance Criteria

1. WHEN a trainee completes a training session with a score below 60%, THE Adaptive_Learning_Engine SHALL assign a beginner-level remediation module in the same category
2. WHEN a trainee completes a training session with a score between 60–80%, THE Adaptive_Learning_Engine SHALL assign an intermediate-level practice module in the same category
3. WHEN a trainee completes a training session with a score above 80%, THE Adaptive_Learning_Engine SHALL unlock an advanced-level simulation module in the same category
4. WHEN a trainee has multiple weak categories, THE Adaptive_Learning_Engine SHALL prioritize the category with the lowest Category_Average for the next recommendation
5. WHEN a trainee has no weak categories, THE Adaptive_Learning_Engine SHALL recommend the next module in their registered sequence

### Requirement 4: Increase Difficulty for Consistent High Performers

**User Story:** As a high-performing trainee, I want the system to challenge me with advanced content, so that I continue to develop expertise in security topics.

#### Acceptance Criteria

1. WHEN a trainee's Category_Average in a module category is above 80% AND they have completed at least 3 sessions in that category, THE Adaptive_Learning_Engine SHALL mark that category as "mastered"
2. WHEN a category is marked as "mastered", THE Adaptive_Learning_Engine SHALL unlock advanced-level simulation modules in that category
3. WHEN a trainee completes an advanced module with a score above 85%, THE Adaptive_Learning_Engine SHALL increase their difficulty tier for future modules in that category
4. WHEN difficulty is increased, THE Adaptive_Learning_Engine SHALL display a "Level Up" notification on the trainee dashboard
5. WHEN a trainee's score drops below 75% in an advanced module, THE Adaptive_Learning_Engine SHALL revert to intermediate difficulty for that category

### Requirement 5: Provide Remediation After Repeated Failures

**User Story:** As a trainee struggling with a security topic, I want the system to provide targeted remediation training, so that I can build foundational knowledge before advancing.

#### Acceptance Criteria

1. WHEN a trainee scores below 60% on two consecutive sessions in the same category, THE Adaptive_Learning_Engine SHALL assign a beginner-level remediation module with simplified content
2. WHEN a remediation module is assigned, THE Adaptive_Learning_Engine SHALL lock advanced modules in that category until the trainee scores above 70% on the remediation module
3. WHEN a trainee completes a remediation module with a score above 70%, THE Adaptive_Learning_Engine SHALL unlock intermediate modules in that category
4. WHEN a trainee completes a remediation module with a score below 70%, THE Adaptive_Learning_Engine SHALL assign an additional remediation module with different content
5. THE Adaptive_Learning_Engine SHALL track remediation attempts and display remediation history on the trainee dashboard

### Requirement 6: Update Employee Dashboard with Recommendations

**User Story:** As a trainee, I want to see my personalized training recommendations on my dashboard, so that I know what to study next.

#### Acceptance Criteria

1. WHEN a trainee views their dashboard, THE Dashboard_Widget SHALL display the top 3 personalized training recommendations based on performance analysis
2. WHEN a recommendation is displayed, THE Dashboard_Widget SHALL show the module title, category, recommended difficulty level, and reason for recommendation (e.g., "Improve weak area: Phishing")
3. WHEN a trainee has no weak categories, THE Dashboard_Widget SHALL display the next module in their registered sequence with a "Continue your journey" message
4. WHEN a trainee has completed all registered modules, THE Dashboard_Widget SHALL display advanced challenge modules with a "Master your skills" message
5. WHEN a trainee clicks a recommended module, THE Dashboard_Widget SHALL navigate to the training module and mark it as "recommended" in the session tracking

### Requirement 7: Implement Performance Scoring Logic

**User Story:** As the system, I want to apply consistent scoring logic to determine training assignments, so that all trainees receive appropriate difficulty levels.

#### Acceptance Criteria

1. WHEN a trainee's most recent session score is below 60%, THE Adaptive_Learning_Engine SHALL assign a beginner remediation module in the same category
2. WHEN a trainee's most recent session score is between 60–80%, THE Adaptive_Learning_Engine SHALL assign an intermediate practice module in the same category
3. WHEN a trainee's most recent session score is above 80%, THE Adaptive_Learning_Engine SHALL unlock an advanced simulation module in the same category
4. WHEN calculating assignment recommendations, THE Adaptive_Learning_Engine SHALL consider the last 5 sessions in a category to determine the appropriate difficulty
5. WHEN a trainee has not attempted a category, THE Adaptive_Learning_Engine SHALL assign the beginner module for that category

### Requirement 8: Track Performance Trends Over Time

**User Story:** As a manager, I want to see performance trends for my team, so that I can identify systemic training gaps and measure training effectiveness.

#### Acceptance Criteria

1. WHEN querying performance trends, THE Adaptive_Learning_Engine SHALL calculate Category_Average for each security category over the last 30, 60, and 90 days
2. WHEN a trainee's Category_Average improves by 10% or more over 30 days, THE Adaptive_Learning_Engine SHALL mark that category as "improving"
3. WHEN a trainee's Category_Average declines by 10% or more over 30 days, THE Adaptive_Learning_Engine SHALL mark that category as "declining"
4. WHEN generating trend reports, THE Adaptive_Learning_Engine SHALL include the number of sessions, average score, and trend direction for each category
5. THE Adaptive_Learning_Engine SHALL provide trend data via API endpoint for manager dashboard integration

### Requirement 9: Prevent Recommendation Loops

**User Story:** As the system, I want to avoid recommending the same module repeatedly, so that trainees experience variety in their learning path.

#### Acceptance Criteria

1. WHEN generating a recommendation, THE Adaptive_Learning_Engine SHALL not recommend a module the trainee has completed in the last 30 days
2. WHEN a trainee has completed all available modules in a category, THE Adaptive_Learning_Engine SHALL recommend modules from the next weak category
3. WHEN a trainee has completed all available modules across all categories, THE Adaptive_Learning_Engine SHALL recommend advanced challenge modules or suggest reviewing weak areas
4. WHEN a trainee declines a recommendation, THE Adaptive_Learning_Engine SHALL not recommend the same module again for 7 days
5. THE Adaptive_Learning_Engine SHALL maintain a recommendation history for each trainee to prevent duplicate suggestions

### Requirement 10: Ensure Data Consistency and Accuracy

**User Story:** As an admin, I want the adaptive learning system to maintain accurate performance data, so that recommendations are reliable and fair.

#### Acceptance Criteria

1. WHEN performance data is updated, THE Adaptive_Learning_Engine SHALL validate that scores are between 0 and 100
2. WHEN a training session is recorded, THE Adaptive_Learning_Engine SHALL verify that the user_id, module_id, and completed_at are present and valid
3. WHEN calculating Category_Average, THE Adaptive_Learning_Engine SHALL exclude sessions with invalid or missing data
4. WHEN a data inconsistency is detected, THE Adaptive_Learning_Engine SHALL log the error and alert an admin
5. THE Adaptive_Learning_Engine SHALL perform data integrity checks every 24 hours and generate a consistency report

---

## Acceptance Criteria Patterns

### Performance Analysis Properties

The following properties define the correctness of the adaptive learning system:

#### 1. **Invariant: Category Average Bounds**
- Property: For any trainee, the Category_Average for a security category must be between 0 and 100
- Rationale: Ensures scoring logic is mathematically sound
- Test: Generate random performance scores and verify Category_Average remains within bounds

#### 2. **Invariant: Weak Category Consistency**
- Property: If a category is marked as "weak", its Category_Average must be below 70%
- Rationale: Ensures weak category identification is accurate
- Test: Verify that all weak categories have Category_Average < 70% and all non-weak categories have Category_Average >= 70%

#### 3. **Invariant: Difficulty Assignment Correctness**
- Property: If a trainee's most recent score is below 60%, the assigned module must be beginner level
- Rationale: Ensures difficulty assignment matches performance
- Test: For each score range (0-60, 60-80, 80-100), verify the assigned difficulty level is correct

#### 4. **Round-Trip Property: Performance History Retrieval**
- Property: If a training session is recorded with score S, querying the performance history must return that session with score S
- Rationale: Ensures data persistence and retrieval accuracy
- Test: Record a session, retrieve it, and verify all fields match

#### 5. **Round-Trip Property: Recommendation Persistence**
- Property: If a recommendation is generated and stored, retrieving the recommendation must return the same module_id and reason
- Rationale: Ensures recommendations are stored and retrieved correctly
- Test: Generate a recommendation, store it, retrieve it, and verify it matches

#### 6. **Idempotence Property: Category Average Calculation**
- Property: Calculating Category_Average multiple times for the same set of sessions must produce the same result
- Rationale: Ensures calculation logic is deterministic
- Test: Calculate Category_Average twice and verify results are identical

#### 7. **Idempotence Property: Weak Category Identification**
- Property: Running weak category analysis multiple times on the same performance data must produce the same weak category list
- Rationale: Ensures analysis is deterministic
- Test: Identify weak categories twice and verify the lists are identical

#### 8. **Metamorphic Property: Recommendation Prioritization**
- Property: If trainee A has a lower Category_Average in category X than trainee B, trainee A should receive a recommendation for category X before trainee B
- Rationale: Ensures recommendations prioritize the weakest areas
- Test: Compare two trainees with different Category_Averages and verify recommendation order

#### 9. **Metamorphic Property: Difficulty Progression**
- Property: If a trainee's score increases from 65% to 85%, the assigned difficulty should increase or stay the same, never decrease
- Rationale: Ensures difficulty progression is monotonic with performance
- Test: Simulate score progression and verify difficulty never decreases

#### 10. **Error Condition: Invalid Score Handling**
- Property: If a training session has an invalid score (< 0 or > 100), the system must reject it and log an error
- Rationale: Ensures data integrity
- Test: Attempt to record sessions with invalid scores and verify rejection

#### 11. **Error Condition: Missing Data Handling**
- Property: If a training session is missing required fields (user_id, module_id, completed_at), the system must reject it and log an error
- Rationale: Ensures complete data collection
- Test: Attempt to record sessions with missing fields and verify rejection

#### 12. **Confluence Property: Recommendation Independence**
- Property: The order in which trainees complete sessions should not affect the recommendations generated for other trainees
- Rationale: Ensures recommendations are independent
- Test: Generate recommendations in different orders and verify results are the same

---

## Implementation Notes

### Database Schema Considerations

The following tables are required or should be extended:

- **training_sessions**: Already exists; ensure it includes `final_score`, `completed_at`, and `module_id`
- **training_modules**: Already exists; ensure it includes `category` and `difficulty_level`
- **user_module_progress**: Already exists; extend to track `recommended_at` and `recommendation_reason`
- **adaptive_learning_recommendations**: New table to store generated recommendations with fields: `id`, `user_id`, `module_id`, `reason`, `created_at`, `accepted_at`, `declined_at`
- **performance_analysis_cache**: New table to cache Category_Average calculations for performance optimization

### API Endpoints Required

- `GET /api/endpoints/adaptive-learning/recommendations` - Retrieve personalized recommendations for a trainee
- `GET /api/endpoints/adaptive-learning/performance-analysis` - Get performance analysis for a trainee
- `POST /api/endpoints/adaptive-learning/accept-recommendation` - Accept a recommendation
- `POST /api/endpoints/adaptive-learning/decline-recommendation` - Decline a recommendation
- `GET /api/endpoints/adaptive-learning/trends` - Get performance trends for a manager

### UI Components Required

- **Recommendation Widget**: Display on trainee dashboard showing top 3 recommendations
- **Performance Analytics Panel**: Show weak categories and improvement areas
- **Difficulty Indicator**: Display current difficulty level and progression
- **Remediation Status**: Show remediation attempts and progress

