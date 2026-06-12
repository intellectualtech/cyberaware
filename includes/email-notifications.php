<?php
// includes/email-notifications.php - Email notification system

if (!function_exists('send_email')) {
    /**
     * Send email notification
     * @param string $to - Recipient email
     * @param string $subject - Email subject
     * @param string $template - Template name (welcome, module_unlocked, etc.)
     * @param array $data - Template variables
     * @return bool - Success/failure
     */
    function send_email($to, $subject, $template, $data = []) {
        try {
            // Build email body based on template
            $body = build_email_template($template, $data);
            
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: noreply@cyberaware.local\r\n";
            $headers .= "Reply-To: info@intellectualtechnology.com.na\r\n";
            
            // Send email
            $result = @mail($to, $subject, $body, $headers);
            
            // Log email
            error_log("Email sent to $to: $subject - " . ($result ? "Success" : "Failed"));
            
            return $result;
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('build_email_template')) {
    /**
     * Build email HTML from template
     */
    function build_email_template($template, $data = []) {
        $name = $data['name'] ?? 'User';
        $company = $data['company'] ?? 'CyberAware';
        
        $header = "
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #FF8C42, #FFB070); color: white; padding: 30px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .footer { text-align: center; padding: 20px; color: #999; font-size: 12px; }
                .btn { display: inline-block; padding: 12px 24px; background: #FF8C42; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .highlight { background: #fff3e0; padding: 15px; border-left: 4px solid #FF8C42; margin: 15px 0; }
                h2 { color: #1a1a2e; }
                p { line-height: 1.6; }
            </style>
        </head>
        <body>
        <div class='container'>
            <div class='header'>
                <h1>CyberAware</h1>
            </div>
            <div class='content'>
        ";
        
        $footer = "
            </div>
            <div class='footer'>
                <p>© 2026 Intellectual Technology cc. All rights reserved.</p>
                <p>Windhoek, Namibia 🇳🇦</p>
            </div>
        </div>
        </body>
        </html>
        ";
        
        $body = '';
        
        switch ($template) {
            case 'welcome':
                $body = "
                    <h2>Welcome to CyberAware, $name! 👋</h2>
                    <p>Your account has been created successfully.</p>
                    <div class='highlight'>
                        <strong>Your Login Details:</strong><br>
                        Username: {$data['username']}<br>
                        Password: {$data['password']}<br>
                        <em style='color: #999;'>Please change your password after first login.</em>
                    </div>
                    <p><a href='{$data['login_url']}' class='btn'>Login to CyberAware</a></p>
                    <p>If you have any questions, contact us at info@intellectualtechnology.com.na</p>
                ";
                break;
                
            case 'module_unlocked':
                $body = "
                    <h2>New Module Unlocked! 🎉</h2>
                    <p>Hi $name,</p>
                    <p>Great job! You've passed the <strong>{$data['module_name']}</strong> module with a score of <strong>{$data['score']}%</strong>.</p>
                    <div class='highlight'>
                        <strong>Next Module Unlocked:</strong><br>
                        {$data['next_module']}
                    </div>
                    <p>Keep up the momentum and continue your security training journey!</p>
                    <p><a href='{$data['dashboard_url']}' class='btn'>Continue Training</a></p>
                ";
                break;
                
            case 'module_completed':
                $body = "
                    <h2>Module Completed! 🏆</h2>
                    <p>Hi $name,</p>
                    <p>Congratulations! You've successfully completed the <strong>{$data['module_name']}</strong> module.</p>
                    <div class='highlight'>
                        <strong>Your Score:</strong> {$data['score']}%<br>
                        <strong>Attempts:</strong> {$data['attempts']}<br>
                        <strong>XP Earned:</strong> {$data['xp']} XP
                    </div>
                    <p>You're making excellent progress in your cybersecurity awareness training!</p>
                    <p><a href='{$data['dashboard_url']}' class='btn'>View Progress</a></p>
                ";
                break;
                
            case 'all_modules_completed':
                $body = "
                    <h2>All Modules Completed! 🎓</h2>
                    <p>Hi $name,</p>
                    <p>Fantastic! You've successfully completed all your assigned training modules.</p>
                    <div class='highlight'>
                        <strong>Your Final Stats:</strong><br>
                        Total Modules: {$data['total_modules']}<br>
                        Average Score: {$data['avg_score']}%<br>
                        Total XP: {$data['total_xp']} XP
                    </div>
                    <p>Your certificate is now available for download.</p>
                    <p><a href='{$data['certificate_url']}' class='btn'>Download Certificate</a></p>
                ";
                break;
                
            case 'password_reset':
                $body = "
                    <h2>Password Reset Request</h2>
                    <p>Hi $name,</p>
                    <p>Your password has been reset by an administrator.</p>
                    <div class='highlight'>
                        <strong>Your New Password:</strong><br>
                        {$data['password']}<br>
                        <em style='color: #999;'>Please change this password after logging in.</em>
                    </div>
                    <p><a href='{$data['login_url']}' class='btn'>Login</a></p>
                ";
                break;
                
            case 'account_activated':
                $body = "
                    <h2>Account Activated ✓</h2>
                    <p>Hi $name,</p>
                    <p>Your account has been activated and is ready to use.</p>
                    <p><a href='{$data['login_url']}' class='btn'>Login Now</a></p>
                ";
                break;
                
            case 'account_deactivated':
                $body = "
                    <h2>Account Deactivated</h2>
                    <p>Hi $name,</p>
                    <p>Your account has been deactivated. If you believe this is an error, please contact your administrator.</p>
                ";
                break;
                
            case 'phishing_simulation':
                $body = "
                    <h2>Weekly Phishing Simulation 🎣</h2>
                    <p>Hi $name,</p>
                    <p>This week's phishing inbox is ready! You have <strong>{$data['email_count']}</strong> emails to review.</p>
                    <div class='highlight'>
                        <strong>Your Mission:</strong><br>
                        Tag at least 5 emails as safe or phishing to complete this week's challenge.
                    </div>
                    <p><a href='{$data['inbox_url']}' class='btn'>Start Phishing Inbox</a></p>
                ";
                break;
                
            case 'weekly_reminder':
                $body = "
                    <h2>Weekly Training Reminder 📚</h2>
                    <p>Hi $name,</p>
                    <p>Don't forget to continue your cybersecurity training this week!</p>
                    <div class='highlight'>
                        <strong>Your Progress:</strong><br>
                        Modules Completed: {$data['completed']}/{$data['total']}<br>
                        Current Streak: {$data['streak']} days
                    </div>
                    <p><a href='{$data['dashboard_url']}' class='btn'>Continue Training</a></p>
                ";
                break;
                
            default:
                $body = "<p>Hi $name,</p><p>{$data['message']}</p>";
        }
        
        return $header . $body . $footer;
    }
}

if (!function_exists('notify_user_created')) {
    /**
     * Send welcome email to newly created user
     */
    function notify_user_created($email, $full_name, $username, $password) {
        $login_url = 'http://localhost/cyberaware-new-Edits/pages/login.php';
        
        return send_email($email, 'Welcome to CyberAware', 'welcome', [
            'name' => $full_name,
            'username' => $username,
            'password' => $password,
            'login_url' => $login_url
        ]);
    }
}

if (!function_exists('notify_module_unlocked')) {
    /**
     * Send notification when module is unlocked
     */
    function notify_module_unlocked($pdo, $user_id, $module_id, $score) {
        $stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) return false;
        
        $stmt = $pdo->prepare("SELECT title FROM training_modules WHERE id = ?");
        $stmt->execute([$module_id]);
        $module = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT title FROM training_modules WHERE id = (SELECT id FROM training_modules WHERE id > ? ORDER BY id LIMIT 1)");
        $stmt->execute([$module_id]);
        $next_module = $stmt->fetch();
        
        $dashboard_url = 'http://localhost/cyberaware-new-Edits/trainee/dashboard.php';
        
        return send_email($user['email'], 'Module Unlocked! 🎉', 'module_unlocked', [
            'name' => $user['full_name'],
            'module_name' => $module['title'] ?? 'Module',
            'score' => $score,
            'next_module' => $next_module['title'] ?? 'Next Module',
            'dashboard_url' => $dashboard_url
        ]);
    }
}

if (!function_exists('notify_module_completed')) {
    /**
     * Send notification when module is completed
     */
    function notify_module_completed($pdo, $user_id, $module_id, $score, $attempts, $xp) {
        $stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) return false;
        
        $stmt = $pdo->prepare("SELECT title FROM training_modules WHERE id = ?");
        $stmt->execute([$module_id]);
        $module = $stmt->fetch();
        
        $dashboard_url = 'http://localhost/cyberaware-new-Edits/trainee/dashboard.php';
        
        return send_email($user['email'], 'Module Completed! 🏆', 'module_completed', [
            'name' => $user['full_name'],
            'module_name' => $module['title'] ?? 'Module',
            'score' => $score,
            'attempts' => $attempts,
            'xp' => $xp,
            'dashboard_url' => $dashboard_url
        ]);
    }
}

if (!function_exists('notify_phishing_simulation')) {
    /**
     * Send phishing simulation reminder
     */
    function notify_phishing_simulation($email, $full_name, $email_count = 6) {
        $inbox_url = 'http://localhost/cyberaware-new-Edits/trainee/phish-inbox.php';
        
        return send_email($email, 'Weekly Phishing Simulation 🎣', 'phishing_simulation', [
            'name' => $full_name,
            'email_count' => $email_count,
            'inbox_url' => $inbox_url
        ]);
    }
}

if (!function_exists('notify_weekly_reminder')) {
    /**
     * Send weekly training reminder
     */
    function notify_weekly_reminder($pdo, $user_id) {
        $stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) return false;
        
        // Get progress stats
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM user_module_registrations WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $total = $stmt->fetch()['total'] ?? 0;
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as completed FROM user_module_progress WHERE user_id = ? AND passed = 1
        ");
        $stmt->execute([$user_id]);
        $completed = $stmt->fetch()['completed'] ?? 0;
        
        $dashboard_url = 'http://localhost/cyberaware-new-Edits/trainee/dashboard.php';
        
        return send_email($user['email'], 'Weekly Training Reminder 📚', 'weekly_reminder', [
            'name' => $user['full_name'],
            'completed' => $completed,
            'total' => $total,
            'streak' => 0, // TODO: Calculate actual streak
            'dashboard_url' => $dashboard_url
        ]);
    }
}

?>
