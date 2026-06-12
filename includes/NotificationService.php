<?php
/**
 * Notification Service
 * Handles email notifications, templates, and history tracking
 */

class NotificationService {
    private $pdo;
    private $from_email;
    private $from_name;
    private $base_url;

    public function __construct($pdo, $from_email = 'noreply@cyberaware.local', $from_name = 'CyberAware', $base_url = 'http://localhost/cyberaware-new-Edits') {
        $this->pdo = $pdo;
        $this->from_email = $from_email;
        $this->from_name = $from_name;
        $this->base_url = $base_url;
    }

    /**
     * Send training assigned notification
     */
    public function notifyTrainingAssigned($user_id, $module_id, $module_title, $module_category, $module_duration = '10 min') {
        $user = $this->getUser($user_id);
        if (!$user) return false;

        $data = [
            'user_name' => $user['full_name'],
            'module_title' => $module_title,
            'module_category' => $module_category,
            'module_duration' => $module_duration,
            'dashboard_url' => $this->base_url . '/trainee/dashboard.php'
        ];

        return $this->queueNotification($user_id, 'training_assigned', $data, 'high', $module_id, 'module');
    }

    /**
     * Send campaign reminder notification
     */
    public function notifyCampaignReminder($user_id, $campaign_id, $campaign_name, $campaign_description, $campaign_progress = 0) {
        $user = $this->getUser($user_id);
        if (!$user) return false;

        $data = [
            'user_name' => $user['full_name'],
            'campaign_name' => $campaign_name,
            'campaign_description' => $campaign_description,
            'campaign_progress' => $campaign_progress,
            'campaign_url' => $this->base_url . '/trainee/dashboard.php'
        ];

        return $this->queueNotification($user_id, 'campaign_reminder', $data, 'normal', $campaign_id, 'campaign');
    }

    /**
     * Send deadline approaching notification
     */
    public function notifyDeadlineApproaching($user_id, $item_id, $item_name, $deadline_date, $item_type = 'module') {
        $user = $this->getUser($user_id);
        if (!$user) return false;

        $days_remaining = $this->calculateDaysRemaining($deadline_date);

        $data = [
            'user_name' => $user['full_name'],
            'item_name' => $item_name,
            'deadline_date' => date('F j, Y', strtotime($deadline_date)),
            'days_remaining' => $days_remaining,
            'item_url' => $this->base_url . '/trainee/dashboard.php'
        ];

        return $this->queueNotification($user_id, 'deadline_approaching', $data, 'high', $item_id, $item_type);
    }

    /**
     * Send incident status update notification
     */
    public function notifyIncidentUpdate($user_id, $incident_id, $incident_title, $incident_status, $incident_description) {
        $user = $this->getUser($user_id);
        if (!$user) return false;

        $data = [
            'user_name' => $user['full_name'],
            'incident_title' => $incident_title,
            'incident_status' => $incident_status,
            'incident_description' => $incident_description,
            'incident_url' => $this->base_url . '/trainee/report-incident.php'
        ];

        return $this->queueNotification($user_id, 'incident_update', $data, 'high', $incident_id, 'incident');
    }

    /**
     * Send certificate completion notification
     */
    public function notifyCertificateCompletion($user_id, $module_id, $module_title, $final_score, $completion_date = null) {
        $user = $this->getUser($user_id);
        if (!$user) return false;

        if (!$completion_date) {
            $completion_date = date('Y-m-d H:i:s');
        }

        $data = [
            'user_name' => $user['full_name'],
            'module_title' => $module_title,
            'final_score' => $final_score,
            'completion_date' => date('F j, Y', strtotime($completion_date)),
            'certificate_url' => $this->base_url . '/trainee/certificate.php?module=' . $module_id
        ];

        return $this->queueNotification($user_id, 'certificate_completion', $data, 'normal', $module_id, 'certificate');
    }

    /**
     * Queue a notification for sending
     */
    private function queueNotification($user_id, $type, $data, $priority = 'normal', $entity_id = null, $entity_type = null) {
        try {
            // Check user notification preferences
            if (!$this->isNotificationEnabled($user_id, $type)) {
                return false;
            }

            // Get template
            $template = $this->getTemplate($type);
            if (!$template) {
                return false;
            }

            // Queue the notification
            $stmt = $this->pdo->prepare("
                INSERT INTO notification_queue (user_id, template_id, template_data, priority)
                VALUES (?, ?, ?, ?)
            ");

            $result = $stmt->execute([
                $user_id,
                $template['id'],
                json_encode($data),
                $priority
            ]);

            return $result;
        } catch (Exception $e) {
            error_log("Error queueing notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process notification queue (call via cron job)
     */
    public function processQueue($limit = 50) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT nq.*, nt.subject, nt.body_html, nt.body_text, u.email, u.full_name
                FROM notification_queue nq
                JOIN notification_templates nt ON nq.template_id = nt.id
                JOIN users u ON nq.user_id = u.id
                WHERE nq.status = 'pending' 
                AND (nq.scheduled_for IS NULL OR nq.scheduled_for <= NOW())
                AND nq.attempts < nq.max_attempts
                ORDER BY nq.priority DESC, nq.created_at ASC
                LIMIT ?
            ");

            $stmt->execute([$limit]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $sent_count = 0;
            foreach ($notifications as $notification) {
                if ($this->sendNotification($notification)) {
                    $sent_count++;
                }
            }

            return $sent_count;
        } catch (Exception $e) {
            error_log("Error processing notification queue: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Send individual notification
     */
    private function sendNotification($notification) {
        try {
            $template_data = json_decode($notification['template_data'], true);

            // Replace placeholders in subject and body
            $subject = $this->replacePlaceholders($notification['subject'], $template_data);
            $body_html = $this->replacePlaceholders($notification['body_html'], $template_data);
            $body_text = $this->replacePlaceholders($notification['body_text'], $template_data);

            // Send email
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: {$this->from_name} <{$this->from_email}>\r\n";
            $headers .= "Reply-To: {$this->from_email}\r\n";

            $mail_sent = mail(
                $notification['email'],
                $subject,
                $body_html,
                $headers
            );

            if ($mail_sent) {
                // Update queue status
                $stmt = $this->pdo->prepare("
                    UPDATE notification_queue
                    SET status = 'completed', updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$notification['id']]);

                // Log to history
                $this->logNotificationHistory(
                    $notification['user_id'],
                    $notification['template_id'],
                    $notification['email'],
                    $subject,
                    $body_html,
                    $body_text,
                    $notification['type'],
                    'sent',
                    null,
                    $notification['related_entity_type'],
                    $notification['related_entity_id']
                );

                return true;
            } else {
                // Update attempts
                $stmt = $this->pdo->prepare("
                    UPDATE notification_queue
                    SET attempts = attempts + 1, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$notification['id']]);

                return false;
            }
        } catch (Exception $e) {
            error_log("Error sending notification: " . $e->getMessage());

            // Update queue with error
            $stmt = $this->pdo->prepare("
                UPDATE notification_queue
                SET attempts = attempts + 1, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$notification['id']]);

            return false;
        }
    }

    /**
     * Log notification to history
     */
    private function logNotificationHistory($user_id, $template_id, $email, $subject, $body_html, $body_text, $type, $status, $error_message = null, $entity_type = null, $entity_id = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notification_history 
                (user_id, template_id, recipient_email, subject, body_html, body_text, type, status, error_message, related_entity_type, related_entity_id, sent_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            return $stmt->execute([
                $user_id,
                $template_id,
                $email,
                $subject,
                $body_html,
                $body_text,
                $type,
                $status,
                $error_message,
                $entity_type,
                $entity_id
            ]);
        } catch (Exception $e) {
            error_log("Error logging notification history: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by ID
     */
    private function getUser($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, full_name, email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get notification template
     */
    private function getTemplate($type) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM notification_templates WHERE type = ?");
            $stmt->execute([$type]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Check if notification type is enabled for user
     */
    private function isNotificationEnabled($user_id, $type) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM user_notification_preferences WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $prefs = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$prefs) {
                // Create default preferences
                $this->createDefaultPreferences($user_id);
                return true;
            }

            return (bool)$prefs[$type];
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Create default notification preferences for user
     */
    private function createDefaultPreferences($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO user_notification_preferences (user_id)
                VALUES (?)
            ");
            return $stmt->execute([$user_id]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Replace placeholders in text
     */
    private function replacePlaceholders($text, $data) {
        foreach ($data as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }

    /**
     * Calculate days remaining until deadline
     */
    private function calculateDaysRemaining($deadline_date) {
        $deadline = new DateTime($deadline_date);
        $today = new DateTime();
        $interval = $today->diff($deadline);
        return $interval->days;
    }

    /**
     * Get notification history for user
     */
    public function getNotificationHistory($user_id, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM notification_history
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$user_id, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    type,
                    status,
                    COUNT(*) as count
                FROM notification_history
                WHERE user_id = ?
                GROUP BY type, status
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Update user notification preferences
     */
    public function updatePreferences($user_id, $preferences) {
        try {
            $updates = [];
            $values = [];

            foreach ($preferences as $key => $value) {
                if (in_array($key, ['training_assigned', 'campaign_reminder', 'deadline_approaching', 'incident_update', 'certificate_completion', 'email_frequency'])) {
                    $updates[] = "$key = ?";
                    $values[] = $value;
                }
            }

            if (empty($updates)) {
                return false;
            }

            $values[] = $user_id;
            $sql = "UPDATE user_notification_preferences SET " . implode(', ', $updates) . " WHERE user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
        } catch (Exception $e) {
            error_log("Error updating preferences: " . $e->getMessage());
            return false;
        }
    }
}
?>
