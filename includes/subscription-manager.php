<?php
/**
 * Subscription Manager
 * Handles subscription checks, access control, and expiry reminders
 */

class SubscriptionManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Check if organization subscription is active
     */
    public function isSubscriptionActive($organization_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT status, end_date FROM subscriptions 
                WHERE organization_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$organization_id]);
            $subscription = $stmt->fetch();
            
            if (!$subscription) {
                return false;
            }
            
            // Check if subscription is active and not expired
            if ($subscription['status'] === 'active' && strtotime($subscription['end_date']) > time()) {
                return true;
            }
            
            // Check if trial is still valid
            if ($subscription['status'] === 'trial' && strtotime($subscription['end_date']) > time()) {
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Subscription check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get subscription status
     */
    public function getSubscriptionStatus($organization_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id, status, plan_type, start_date, end_date,
                    DATEDIFF(end_date, NOW()) as days_remaining
                FROM subscriptions 
                WHERE organization_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$organization_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get subscription status error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if subscription is expired
     */
    public function isSubscriptionExpired($organization_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT end_date FROM subscriptions 
                WHERE organization_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$organization_id]);
            $subscription = $stmt->fetch();
            
            if (!$subscription) {
                return true; // No subscription = expired
            }
            
            return strtotime($subscription['end_date']) < time();
        } catch (Exception $e) {
            error_log("Subscription expiry check error: " . $e->getMessage());
            return true;
        }
    }
    
    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiry($organization_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT DATEDIFF(end_date, NOW()) as days_remaining
                FROM subscriptions 
                WHERE organization_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$organization_id]);
            $result = $stmt->fetch();
            
            return $result ? (int)$result['days_remaining'] : -1;
        } catch (Exception $e) {
            error_log("Days until expiry error: " . $e->getMessage());
            return -1;
        }
    }
    
    /**
     * Check if feature is enabled for subscription
     */
    public function isFeatureEnabled($organization_id, $feature_name) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.plan_type FROM subscriptions s
                WHERE s.organization_id = ? 
                ORDER BY s.created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$organization_id]);
            $subscription = $stmt->fetch();
            
            if (!$subscription) {
                return false;
            }
            
            $feature_stmt = $this->pdo->prepare("
                SELECT is_enabled FROM subscription_features 
                WHERE plan_type = ? AND feature_name = ?
            ");
            $feature_stmt->execute([$subscription['plan_type'], $feature_name]);
            $feature = $feature_stmt->fetch();
            
            return $feature && $feature['is_enabled'];
        } catch (Exception $e) {
            error_log("Feature check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get subscription features
     */
    public function getSubscriptionFeatures($organization_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.plan_type FROM subscriptions s
                WHERE s.organization_id = ? 
                ORDER BY s.created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$organization_id]);
            $subscription = $stmt->fetch();
            
            if (!$subscription) {
                return [];
            }
            
            $features_stmt = $this->pdo->prepare("
                SELECT feature_name, feature_description FROM subscription_features 
                WHERE plan_type = ? AND is_enabled = 1
                ORDER BY feature_name
            ");
            $features_stmt->execute([$subscription['plan_type']]);
            return $features_stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get features error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Send expiry reminders
     */
    public function sendExpiryReminders() {
        try {
            // Get subscriptions expiring in 7, 3, and 1 days
            $reminders = [
                '7_days' => 7,
                '3_days' => 3,
                '1_day' => 1
            ];
            
            foreach ($reminders as $reminder_type => $days) {
                $stmt = $this->pdo->prepare("
                    SELECT 
                        s.id, s.organization_id, s.end_date,
                        o.name, o.contact_email
                    FROM subscriptions s
                    JOIN organizations o ON s.organization_id = o.id
                    WHERE s.status IN ('trial', 'active')
                    AND DATEDIFF(s.end_date, NOW()) = ?
                    AND NOT EXISTS (
                        SELECT 1 FROM expiry_reminders 
                        WHERE subscription_id = s.id 
                        AND reminder_type = ?
                        AND status = 'sent'
                    )
                ");
                $stmt->execute([$days, $reminder_type]);
                $subscriptions = $stmt->fetchAll();
                
                foreach ($subscriptions as $sub) {
                    $this->sendReminderEmail($sub, $reminder_type, $days);
                }
            }
            
            // Handle expired subscriptions
            $this->handleExpiredSubscriptions();
            
            return true;
        } catch (Exception $e) {
            error_log("Send reminders error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send reminder email
     */
    private function sendReminderEmail($subscription, $reminder_type, $days) {
        try {
            $email = $subscription['contact_email'];
            $subject = "Subscription Expiring in $days Days - " . $subscription['name'];
            
            $body = "Dear " . $subscription['name'] . ",\n\n";
            $body .= "Your CyberAware subscription will expire in $days days on " . date('F j, Y', strtotime($subscription['end_date'])) . ".\n\n";
            $body .= "Please renew your subscription to maintain uninterrupted access to our platform.\n\n";
            $body .= "If you have any questions, please contact our support team.\n\n";
            $body .= "Best regards,\nCyberAware Team";
            
            // Send email
            mail($email, $subject, $body);
            
            // Log reminder
            $stmt = $this->pdo->prepare("
                INSERT INTO expiry_reminders (
                    subscription_id, reminder_type, email_sent_to, status, sent_at
                ) VALUES (?, ?, ?, 'sent', NOW())
            ");
            $stmt->execute([$subscription['id'], $reminder_type, $email]);
            
            return true;
        } catch (Exception $e) {
            error_log("Send reminder email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle expired subscriptions
     */
    private function handleExpiredSubscriptions() {
        try {
            // Update expired subscriptions
            $stmt = $this->pdo->prepare("
                UPDATE subscriptions 
                SET status = 'expired'
                WHERE status IN ('trial', 'active')
                AND end_date < NOW()
            ");
            $stmt->execute();
            
            // Disable access for expired organizations
            $stmt = $this->pdo->prepare("
                UPDATE organizations o
                SET o.is_active = 0
                WHERE EXISTS (
                    SELECT 1 FROM subscriptions s
                    WHERE s.organization_id = o.id
                    AND s.status = 'expired'
                )
            ");
            $stmt->execute();
            
            return true;
        } catch (Exception $e) {
            error_log("Handle expired subscriptions error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reactivate subscription
     */
    public function reactivateSubscription($subscription_id, $days = 30) {
        try {
            $new_end_date = date('Y-m-d H:i:s', strtotime("+$days days"));
            
            $stmt = $this->pdo->prepare("
                UPDATE subscriptions 
                SET status = 'active', end_date = ?
                WHERE id = ?
            ");
            $stmt->execute([$new_end_date, $subscription_id]);
            
            // Reactivate organization
            $org_stmt = $this->pdo->prepare("
                UPDATE organizations o
                SET o.is_active = 1
                WHERE o.id = (
                    SELECT organization_id FROM subscriptions WHERE id = ?
                )
            ");
            $org_stmt->execute([$subscription_id]);
            
            return true;
        } catch (Exception $e) {
            error_log("Reactivate subscription error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get subscription limits
     */
    public function getSubscriptionLimits($organization_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT max_users, max_modules FROM subscriptions 
                WHERE organization_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$organization_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get limits error: " . $e->getMessage());
            return null;
        }
    }
}

/**
 * Check subscription access middleware
 * Call this at the beginning of protected pages
 */
function checkSubscriptionAccess() {
    if (!isLoggedIn()) {
        header('Location: ../pages/login.php');
        exit;
    }
    
    try {
        $pdo = getDBConnection();
        
        // Get user's organization
        $stmt = $pdo->prepare("SELECT organization_id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user || !$user['organization_id']) {
            // No organization assigned
            return true; // Allow access for users without organization
        }
        
        $manager = new SubscriptionManager($pdo);
        
        // Check if subscription is active
        if (!$manager->isSubscriptionActive($user['organization_id'])) {
            // Subscription expired or not active
            $_SESSION['subscription_expired'] = true;
            
            // Redirect to expiry page
            header('Location: ../pages/subscription-expired.php');
            exit;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Subscription access check error: " . $e->getMessage());
        return true; // Allow access on error
    }
}
