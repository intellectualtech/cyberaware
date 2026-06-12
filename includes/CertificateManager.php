<?php
/**
 * Certificate Manager
 * Handles enrollment, progression, and completion logic
 */

class CertificateManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all available certificate tracks
     */
    public function getAllTracks() {
        $stmt = $this->pdo->query("
            SELECT * FROM certificate_tracks 
            WHERE is_active = 1 
            ORDER BY name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get track with modules
     */
    public function getTrackWithModules($track_id) {
        $stmt = $this->pdo->prepare("
            SELECT ct.*, COUNT(cm.id) as total_modules
            FROM certificate_tracks ct
            LEFT JOIN certificate_modules cm ON ct.id = cm.track_id
            WHERE ct.id = ? AND ct.is_active = 1
            GROUP BY ct.id
        ");
        $stmt->execute([$track_id]);
        $track = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($track) {
            $stmt = $this->pdo->prepare("
                SELECT cm.*, tm.title as module_title, tm.description as module_description
                FROM certificate_modules cm
                JOIN training_modules tm ON cm.module_id = tm.id
                WHERE cm.track_id = ?
                ORDER BY cm.sequence_order
            ");
            $stmt->execute([$track_id]);
            $track['modules'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $track;
    }

    /**
     * Enroll trainee in certificate track
     */
    public function enrollTrainee($user_id, $track_id) {
        try {
            $this->pdo->beginTransaction();

            // Check if already enrolled
            $stmt = $this->pdo->prepare("
                SELECT id FROM trainee_enrollments 
                WHERE user_id = ? AND track_id = ?
            ");
            $stmt->execute([$user_id, $track_id]);
            if ($stmt->fetch()) {
                throw new Exception("Already enrolled in this track");
            }

            // Create enrollment
            $stmt = $this->pdo->prepare("
                INSERT INTO trainee_enrollments (user_id, track_id, status)
                VALUES (?, ?, 'enrolled')
            ");
            $stmt->execute([$user_id, $track_id]);
            $enrollment_id = $this->pdo->lastInsertId();

            // Get first module
            $stmt = $this->pdo->prepare("
                SELECT id FROM certificate_modules 
                WHERE track_id = ? 
                ORDER BY sequence_order 
                LIMIT 1
            ");
            $stmt->execute([$track_id]);
            $first_module = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($first_module) {
                // Unlock first module
                $stmt = $this->pdo->prepare("
                    INSERT INTO module_progress 
                    (enrollment_id, module_id, certificate_module_id, status)
                    VALUES (?, ?, ?, 'unlocked')
                ");
                $stmt->execute([$enrollment_id, $first_module['id'], $first_module['id']]);

                // Update enrollment with current module
                $stmt = $this->pdo->prepare("
                    UPDATE trainee_enrollments 
                    SET current_module_id = ?, status = 'in_progress'
                    WHERE id = ?
                ");
                $stmt->execute([$first_module['id'], $enrollment_id]);
            }

            $this->pdo->commit();
            return $enrollment_id;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Get trainee's enrollments
     */
    public function getTraineeEnrollments($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT te.*, ct.name as track_name, ct.icon, ct.color,
                   cm.title as current_module_title
            FROM trainee_enrollments te
            JOIN certificate_tracks ct ON te.track_id = ct.id
            LEFT JOIN certificate_modules cm ON te.current_module_id = cm.id
            WHERE te.user_id = ?
            ORDER BY te.enrollment_date DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get enrollment details with progress
     */
    public function getEnrollmentDetails($enrollment_id) {
        $stmt = $this->pdo->prepare("
            SELECT te.*, ct.name as track_name, ct.description as track_description,
                   ct.icon, ct.color
            FROM trainee_enrollments te
            JOIN certificate_tracks ct ON te.track_id = ct.id
            WHERE te.id = ?
        ");
        $stmt->execute([$enrollment_id]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($enrollment) {
            // Get all modules with progress
            $stmt = $this->pdo->prepare("
                SELECT cm.*, tm.title as module_title, tm.description as module_description,
                       mp.status as progress_status, mp.best_quiz_score, mp.completion_date
                FROM certificate_modules cm
                JOIN training_modules tm ON cm.module_id = tm.id
                LEFT JOIN module_progress mp ON cm.id = mp.certificate_module_id 
                    AND mp.enrollment_id = ?
                WHERE cm.track_id = ?
                ORDER BY cm.sequence_order
            ");
            $stmt->execute([$enrollment_id, $enrollment['track_id']]);
            $enrollment['modules'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate progress
            $completed = count(array_filter($enrollment['modules'], fn($m) => $m['progress_status'] === 'completed'));
            $enrollment['progress_percentage'] = round(($completed / count($enrollment['modules'])) * 100);
        }

        return $enrollment;
    }

    /**
     * Complete module and unlock next
     */
    public function completeModule($enrollment_id, $certificate_module_id, $quiz_score) {
        try {
            $this->pdo->beginTransaction();

            // Get module details
            $stmt = $this->pdo->prepare("
                SELECT cm.*, te.track_id
                FROM certificate_modules cm
                JOIN module_progress mp ON cm.id = mp.certificate_module_id
                JOIN trainee_enrollments te ON mp.enrollment_id = te.id
                WHERE cm.id = ? AND mp.enrollment_id = ?
            ");
            $stmt->execute([$certificate_module_id, $enrollment_id]);
            $module = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$module) {
                throw new Exception("Module not found");
            }

            // Check if passing score
            if ($quiz_score < $module['passing_score']) {
                // Mark as failed
                $stmt = $this->pdo->prepare("
                    UPDATE module_progress 
                    SET status = 'failed', best_quiz_score = ?, quiz_attempts = quiz_attempts + 1
                    WHERE enrollment_id = ? AND certificate_module_id = ?
                ");
                $stmt->execute([$quiz_score, $enrollment_id, $certificate_module_id]);
                $this->pdo->commit();
                return ['success' => false, 'message' => 'Score below passing threshold. Please retry.'];
            }

            // Mark as completed
            $stmt = $this->pdo->prepare("
                UPDATE module_progress 
                SET status = 'completed', best_quiz_score = ?, completion_date = NOW(), quiz_attempts = quiz_attempts + 1
                WHERE enrollment_id = ? AND certificate_module_id = ?
            ");
            $stmt->execute([$quiz_score, $enrollment_id, $certificate_module_id]);

            // Get next module
            $stmt = $this->pdo->prepare("
                SELECT cm.id FROM certificate_modules cm
                WHERE cm.track_id = ? AND cm.sequence_order > (
                    SELECT sequence_order FROM certificate_modules WHERE id = ?
                )
                ORDER BY cm.sequence_order
                LIMIT 1
            ");
            $stmt->execute([$module['track_id'], $certificate_module_id]);
            $next_module = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($next_module) {
                // Unlock next module
                $stmt = $this->pdo->prepare("
                    INSERT INTO module_progress 
                    (enrollment_id, module_id, certificate_module_id, status)
                    SELECT ?, module_id, ?, 'unlocked'
                    FROM certificate_modules WHERE id = ?
                ");
                $stmt->execute([$enrollment_id, $next_module['id'], $next_module['id']]);

                // Update current module
                $stmt = $this->pdo->prepare("
                    UPDATE trainee_enrollments 
                    SET current_module_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([$next_module['id'], $enrollment_id]);
            } else {
                // All modules completed - issue certificate
                $this->issueCertificate($enrollment_id);
            }

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Module completed successfully!'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Issue certificate upon completion
     */
    public function issueCertificate($enrollment_id) {
        $stmt = $this->pdo->prepare("
            SELECT te.user_id, te.track_id FROM trainee_enrollments WHERE id = ?
        ");
        $stmt->execute([$enrollment_id]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$enrollment) return false;

        $certificate_number = 'CERT-' . strtoupper(uniqid());

        $stmt = $this->pdo->prepare("
            INSERT INTO certificate_completions 
            (user_id, track_id, enrollment_id, certificate_number, xp_awarded)
            VALUES (?, ?, ?, ?, 100)
        ");
        $stmt->execute([$enrollment['user_id'], $enrollment['track_id'], $enrollment_id, $certificate_number]);

        // Update enrollment
        $stmt = $this->pdo->prepare("
            UPDATE trainee_enrollments 
            SET status = 'completed', completion_date = NOW(), certificate_issued = 1, certificate_issued_date = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$enrollment_id]);

        return $certificate_number;
    }

    /**
     * Check if module is unlocked
     */
    public function isModuleUnlocked($enrollment_id, $certificate_module_id) {
        $stmt = $this->pdo->prepare("
            SELECT status FROM module_progress 
            WHERE enrollment_id = ? AND certificate_module_id = ?
        ");
        $stmt->execute([$enrollment_id, $certificate_module_id]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);

        return $progress && in_array($progress['status'], ['unlocked', 'in_progress', 'completed']);
    }

    /**
     * Get trainee's certificate count
     */
    public function getCertificateCount($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count FROM certificate_completions WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}
