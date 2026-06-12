<?php
require_once 'config/database.php';

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $team_size = trim($_POST['team_size'] ?? '');
    $message_text = trim($_POST['message'] ?? '');
    $requested_modules = $_POST['modules'] ?? [];
    $phone = trim($_POST['phone'] ?? '');
    $contact_preference = trim($_POST['contact_preference'] ?? 'email');
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($company) || empty($message_text)) {
        $message = 'Please fill in all required fields.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } elseif ($contact_preference === 'phone' && empty($phone)) {
        $message = 'Please enter your phone number if you prefer phone contact.';
        $message_type = 'error';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Insert into database
            $stmt = $pdo->prepare("
                INSERT INTO contact_requests (name, email, company, team_size, message, requested_modules, phone, contact_preference, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $name,
                $email,
                $company,
                $team_size ?: 'Not specified',
                $message_text,
                !empty($requested_modules) ? implode(',', array_map('intval', $requested_modules)) : '',
                $phone,
                $contact_preference
            ]);
            
            // Send email to admin
            $to = 'info@intellectualtechnology.com.na';
            $subject = "New Demo Request from " . htmlspecialchars($name);
            $email_body = "
            <html>
            <head>
            <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #FF8C42; color: white; padding: 20px; border-radius: 8px; }
            .content { padding: 20px; background: #f9f9f9; margin: 20px 0; border-radius: 8px; }
            .field { margin: 15px 0; }
            .label { font-weight: bold; color: #333; }
            .value { color: #666; margin-top: 5px; }
            .contact-pref { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; border-radius: 4px; margin-top: 10px; }
            </style>
            </head>
            <body>
            <div class='container'>
            <div class='header'>
            <h1>New Demo Request</h1>
            </div>
            <div class='content'>
            <div class='field'>
            <div class='label'>Name:</div>
            <div class='value'>" . htmlspecialchars($name) . "</div>
            </div>
            <div class='field'>
            <div class='label'>Email:</div>
            <div class='value'>" . htmlspecialchars($email) . "</div>
            </div>
            <div class='field'>
            <div class='label'>Company:</div>
            <div class='value'>" . htmlspecialchars($company) . "</div>
            </div>
            <div class='field'>
            <div class='label'>Team Size:</div>
            <div class='value'>" . htmlspecialchars($team_size ?: 'Not specified') . "</div>
            </div>
            <div class='field'>
            <div class='label'>Message:</div>
            <div class='value'>" . nl2br(htmlspecialchars($message_text)) . "</div>
            </div>
            <div class='field'>
            <div class='label'>Contact Preference:</div>
            <div class='contact-pref'>
            <strong>" . ($contact_preference === 'phone' ? '📱 Phone' : '📧 Email') . "</strong><br>
            " . ($contact_preference === 'phone' ? 'Phone: ' . htmlspecialchars($phone) : 'Email: ' . htmlspecialchars($email)) . "
            </div>
            </div>
            </div>
            </div>
            </body>
            </html>
            ";
            
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: noreply@cyberaware.local\r\n";
            
            @mail($to, $subject, $email_body, $headers);
            
            // Get module names for the selected modules
            $module_names = [
                1 => 'Phishing Email Recognition',
                2 => 'Credential Harvesting Awareness',
                3 => 'Social Engineering Defense',
                4 => 'Malware & Attachment Safety',
                5 => 'Website & Link Safety',
                6 => 'Password Security',
                7 => 'Ransomware Awareness'
            ];
            
            $selected_modules = [];
            foreach ($requested_modules as $module_id) {
                $module_id = (int)$module_id;
                if (isset($module_names[$module_id])) {
                    $selected_modules[] = $module_names[$module_id];
                }
            }
            
            $modules_list = !empty($selected_modules) ? implode('<br>', array_map(function($m) { return '✓ ' . htmlspecialchars($m); }, $selected_modules)) : 'No modules selected';
            
            // Send approval email to user
            $user_subject = "✅ Your CyberAware Demo Request Has Been Approved!";
            $user_body = "
            <html>
            <head>
            <style>
            body { font-family: 'Arial', sans-serif; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #FF8C42 0%, #ffd2b3 100%); color: white; padding: 30px 20px; border-radius: 12px; text-align: center; margin-bottom: 20px; }
            .header h1 { margin: 0; font-size: 28px; }
            .header p { margin: 8px 0 0 0; font-size: 16px; opacity: 0.95; }
            .content { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            .section { margin: 20px 0; }
            .section-title { font-weight: bold; color: #FF8C42; font-size: 16px; margin-bottom: 12px; }
            .modules-box { background: #f9f9f9; border-left: 4px solid #FF8C42; padding: 15px; border-radius: 6px; margin: 15px 0; }
            .module-item { padding: 8px 0; color: #333; }
            .cta-button { display: inline-block; background: #FF8C42; color: white; padding: 14px 28px; border-radius: 8px; text-decoration: none; font-weight: bold; margin-top: 20px; }
            .cta-button:hover { background: #E67A2E; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; text-align: center; }
            .credentials-box { background: #f0fdf4; border: 1px solid #86efac; padding: 15px; border-radius: 8px; margin: 15px 0; }
            .credentials-box strong { color: #065f46; }
            </style>
            </head>
            <body>
            <div class='container'>
            <div class='header'>
            <h1>🎉 Demo Approved!</h1>
            <p>Your CyberAware demo request has been approved</p>
            </div>
            
            <div class='content'>
            <p>Hi <strong>" . htmlspecialchars($name) . "</strong>,</p>
            
            <p>Great news! Your demo request for <strong>" . htmlspecialchars($company) . "</strong> has been <strong style='color: #10b981;'>APPROVED</strong>! 🚀</p>
            
            <div class='section'>
            <div class='section-title'>📋 Your Selected Training Modules:</div>
            <div class='modules-box'>
            " . $modules_list . "
            </div>
            </div>
            
            <div class='section'>
            <div class='section-title'>📞 Next Steps:</div>
            <p>David from our team will contact you shortly to:</p>
            <ul>
            <li>Schedule your personalized demo</li>
            <li>Discuss your team's security needs</li>
            <li>Answer any questions you have</li>
            <li>Set up your training modules</li>
            </ul>
            </div>
            
            <div class='section'>
            <div class='section-title'>📧 Contact Information:</div>
            <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
            <p><strong>Company:</strong> " . htmlspecialchars($company) . "</p>
            <p><strong>Team Size:</strong> " . htmlspecialchars($team_size ?: 'Not specified') . "</p>
            </div>
            
            <div class='section'>
            <div class='section-title'>🔐 Your Demo Access:</div>
            <div class='credentials-box'>
            <p><strong>You'll receive your login credentials shortly!</strong></p>
            <p>Once you log in, you'll have immediate access to:</p>
            <ul>
            <li>All selected training modules</li>
            <li>Interactive lessons and simulations</li>
            <li>Real-world phishing scenarios</li>
            <li>Progress tracking and certificates</li>
            </ul>
            </div>
            </div>
            
            <p style='margin-top: 30px;'>If you have any questions in the meantime, feel free to reach out to us at <strong>info@intellectualtechnology.com.na</strong> or call <strong>+264 81 870 6257</strong>.</p>
            
            <a href='http://" . $_SERVER['HTTP_HOST'] . "/cyberaware/' class='cta-button'>Visit CyberAware Platform</a>
            
            <div class='footer'>
            <p>CyberAware - Cybersecurity Awareness Training Platform</p>
            <p>Windhoek, Namibia | info@intellectualtechnology.com.na</p>
            <p style='margin-top: 10px;'>© 2026 Intellectual Technology cc. All rights reserved.</p>
            </div>
            </div>
            </div>
            </body>
            </html>
            ";
            
            @mail($email, $user_subject, $user_body, $headers);
            
            // Send SMS if phone contact preference selected
            if ($contact_preference === 'phone' && !empty($phone)) {
                require_once '../includes/sms-notifications.php';
                $sms_modules = array_slice($selected_modules, 0, 3);
                sendDemoApprovalSMS($phone, $name, $company, $sms_modules);
            }
            
            $message = 'Thank you! Your demo request has been sent. We will contact you shortly.';
            $message_type = 'success';
            
            // Clear form
            $name = $email = $company = $team_size = $message_text = '';
            
        } catch (Exception $e) {
            $message = 'Error submitting request. Please try again.';
            $message_type = 'error';
            error_log("Contact form error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Contact – CyberAware</title>
	<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	<style>
		:root {
			--orange-400: #fb923c;
			--orange-500: #f97316;
			--orange-600: #ea580c;
			--slate-50: #f8fafc;
			--slate-100: #f1f5f9;
			--slate-200: #e2e8f0;
			--slate-500: #64748b;
			--slate-600: #475569;
			--slate-700: #334155;
			--slate-900: #0f172a;
			--white: #ffffff;
			
			/* Additional colors for styling */
			--primary: #f97316;
			--primary-dark: #ea580c;
		}

		* { margin: 0; padding: 0; box-sizing: border-box; }

		body {
			font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
			background: linear-gradient(135deg, var(--slate-50) 0%, var(--white) 50%, var(--slate-100) 100%);
			color: var(--slate-900);
			overflow-x: hidden;
		}

		body::before {
			content: '';
			position: fixed;
			top: -10%;
			left: -5%;
			width: 24rem;
			height: 24rem;
			background: radial-gradient(circle, rgba(251, 146, 60, 0.2), transparent 70%);
			border-radius: 50%;
			filter: blur(120px);
			opacity: 0.4;
			z-index: 1;
			pointer-events: none;
		}

		body::after {
			content: '';
			position: fixed;
			bottom: 10%;
			right: -10%;
			width: 32rem;
			height: 32rem;
			background: radial-gradient(circle, rgba(107, 114, 128, 0.3), transparent 70%);
			border-radius: 50%;
			filter: blur(120px);
			opacity: 0.3;
			z-index: 1;
			pointer-events: none;
		}

		.grid-overlay {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background-image: 
				linear-gradient(rgba(100, 100, 100, 0.03) 1px, transparent 1px),
				linear-gradient(90deg, rgba(100, 100, 100, 0.03) 1px, transparent 1px);
			background-size: 100px 100px;
			z-index: 1;
			pointer-events: none;
		}

		.header {
			background: backdrop-filter blur(10px);
			background-color: rgba(255, 255, 255, 0.8);
			border-bottom: 1px solid rgba(226, 232, 240, 0.5);
			box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
			position: sticky;
			top: 0;
			z-index: 1000;
		}

		.header-content {
			max-width: 1200px;
			margin: 0 auto;
			padding: 18px 28px;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.logo {
			text-decoration: none;
			font-weight: 800;
			color: var(--orange-600);
			font-size: 1.375rem;
			display: flex;
			gap: 0.625rem;
			align-items: center;
		}

		.logo i {
			width: 2.5rem;
			height: 2.5rem;
			background: linear-gradient(135deg, var(--orange-400), var(--orange-600));
			border-radius: 0.75rem;
			display: flex;
			align-items: center;
			justify-content: center;
			color: var(--white);
			font-size: 1.25rem;
			box-shadow: 0 4px 12px rgba(251, 146, 60, 0.3);
		}

		.nav-menu {
			display: flex;
			gap: 32px;
			flex: 1;
			margin-left: 64px;
			justify-content: center;
		}

		.nav-menu a {
			text-decoration: none;
			color: var(--slate-700);
			font-weight: 600;
			font-size: 0.95rem;
			transition: 0.3s ease;
			white-space: nowrap;
			position: relative;
		}

		.nav-menu a::after {
			content: '';
			position: absolute;
			bottom: -0.25rem;
			left: 0;
			width: 0;
			height: 0.15rem;
			background: linear-gradient(90deg, var(--orange-400), var(--orange-600));
			transition: width 0.3s ease;
		}

		.nav-menu a:hover::after {
			width: 100%;
		}

		.nav-menu a:hover,
		.nav-menu a.active {
			color: var(--orange-500);
		}

		.header-right {
			display: flex;
			gap: 16px;
			align-items: center;
		}

		.header-right a {
			padding: 0.625rem 1.25rem;
			background: linear-gradient(135deg, var(--orange-500), var(--orange-600));
			color: white;
			border-radius: 0.5rem;
			text-decoration: none;
			font-weight: 700;
			font-size: 0.875rem;
			transition: all 0.3s ease;
			box-shadow: 0 4px 12px rgba(251, 146, 60, 0.3);
			border: none;
		}

		.header-right a:hover {
			transform: scale(1.05) translateY(-3px);
			box-shadow: 0 8px 24px rgba(251, 146, 60, 0.4);
		}

		.container {
			max-width: 1100px;
			margin: 0 auto;
			padding: 50px 24px 80px;
		}

		.hero {
			background: linear-gradient(135deg, #ff8c42 0%, #ffd2b3 100%);
			border-radius: 1.625rem;
			padding: 3.125rem 2.5rem;
			box-shadow: 0 18px 40px rgba(255, 140, 66, 0.2);
			margin-bottom: 1.875rem;
			color: var(--dark-navy);
			position: relative;
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			border: 1px solid rgba(255, 140, 66, 0.25);
		}

		.hero-content {
			flex: 1;
		}

		.hero h1 {
			font-family: 'Space Grotesk', sans-serif;
			font-size: 2rem;
			margin-bottom: 0.625rem;
			color: var(--dark-navy);
			font-weight: 800;
		}

		.hero p {
			color: var(--dark-navy);
			font-size: 1rem;
		}

		.hero-actions {
			display: flex;
			gap: 10px;
			flex-shrink: 0;
		}

		.hero-actions a {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 10px 18px;
			border-radius: 999px;
			text-decoration: none;
			font-weight: 700;
			font-size: 13px;
			transition: all 0.2s;
			white-space: nowrap;
		}

		.hero-actions a.primary {
			background: rgba(255, 255, 255, 0.3);
			color: white;
			border: 2px solid white;
		}

		.hero-actions a.primary:hover {
			background: white;
			color: #FF8C42;
		}

		.hero-actions a.secondary {
			background: white;
			color: #FF8C42;
		}

		.hero-actions a.secondary:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
		}

		.contact-grid {
			display: grid;
			gap: 24px;
			grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
		}

		.contact-card {
			background: backdrop-filter blur(10px);
			background-color: rgba(255, 255, 255, 0.8);
			border-radius: 1.125rem;
			padding: 1.375rem;
			border: 1px solid rgba(226, 232, 240, 0.5);
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
			transition: all 0.3s ease;
		}

		.contact-card:hover {
			box-shadow: 0 12px 32px rgba(251, 146, 60, 0.15);
			border-color: var(--orange-500);
		}

		.contact-card h3 { margin-bottom: 10px; }

		.contact-form {
			display: grid;
			gap: 12px;
		}

		.contact-form input,
		.contact-form textarea,
		.contact-form select {
			width: 100%;
			padding: 0.75rem 0.875rem;
			border-radius: 0.75rem;
			border: 1px solid rgba(226, 232, 240, 0.5);
			font-family: inherit;
			font-size: 0.875rem;
			transition: all 0.3s ease;
			outline: none;
			background: rgba(255, 255, 255, 0.9);
		}

		.contact-form input:focus,
		.contact-form select:focus,
		.contact-form textarea:focus {
			border-color: var(--orange-500);
			background: var(--white);
			box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.1);
		}

		.contact-form button {
			padding: 0.75rem 1.25rem;
			border-radius: 9999px;
			border: none;
			background: linear-gradient(135deg, var(--orange-500), var(--orange-600));
			color: var(--white);
			font-weight: 700;
			cursor: pointer;
			transition: all 0.3s ease;
			box-shadow: 0 4px 12px rgba(251, 146, 60, 0.3);
		}

		.contact-form button:hover {
			transform: scale(1.05) translateY(-3px);
			box-shadow: 0 8px 24px rgba(251, 146, 60, 0.4);
		}

		.message {
			padding: 15px 20px;
			border-radius: 10px;
			margin-bottom: 20px;
			display: flex;
			align-items: center;
			gap: 10px;
		}

		.message.success {
			background: #d1fae5;
			color: #065f46;
			border-left: 4px solid #10b981;
		}

		.message.error {
			background: #fee2e2;
			color: #991b1b;
			border-left: 4px solid #ef4444;
		}

		.action-buttons {
			display: flex;
			gap: 12px;
			justify-content: center;
			margin-top: 20px;
			flex-wrap: wrap;
		}

		.action-buttons a {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 12px 24px;
			border-radius: 999px;
			text-decoration: none;
			font-weight: 700;
			transition: all 0.2s;
		}

		.action-buttons a.primary {
			background: var(--primary);
			color: white;
		}

		.action-buttons a.primary:hover {
			background: #E67A2E;
			transform: translateY(-2px);
		}

		.action-buttons a.secondary {
			background: var(--white);
			color: var(--ink);
			border: 2px solid var(--platinum);
		}

		.action-buttons a.secondary:hover {
			border-color: var(--primary);
			color: var(--primary);
		}

		/* Responsive navbar */
		@media (max-width: 768px) {
			.nav-menu {
				display: none;
			}

			.header-content {
				padding: 12px 16px;
			}

			.header-right {
				gap: 8px;
			}
		}
	</style>
</head>
<body>
	<header class="header">
		<div class="header-content">
			<a class="logo" href="index.php"><i class="fas fa-shield-alt"></i> CyberAware</a>

			<nav class="nav-menu">
				<a href="index.php">Home</a>
				<a href="index.php#features">Features</a>
				<a href="services.php">Services</a>
				<a href="about.php">About Us</a>
				<a href="compliance.php">Compliance</a>
				<a href="contact.php" class="active">Contact Us</a>
			</nav>

			<div class="header-right" style="display: flex; gap: 16px;">
				<a href="pages/login.php" style="padding: 10px 20px; background: linear-gradient(135deg, var(--orange-500), var(--orange-600)); color: white; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 14px;"><i class="fas fa-sign-in-alt"></i> Login</a>
			</div>
		</div>
	</header>

	<main class="container">
		<section class="hero">
			<div class="hero-content">
				<h1>Let's connect</h1>
				<p>Send a quick note and we will respond with next steps.</p>
			</div>
			<?php if ($message && $message_type === 'success'): ?>
				<div class="hero-actions">
					<a href="pages/login.php" class="primary">
						<i class="fas fa-sign-in-alt"></i> Login
					</a>
				</div>
			<?php endif; ?>
		</section>

		<?php if ($message): ?>
			<div class="message <?= htmlspecialchars($message_type) ?>">
				<i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
				<?= htmlspecialchars($message) ?>
			</div>
		<?php endif; ?>

		<section class="contact-grid">
			<div class="contact-card">
				<h3>Direct lines</h3>
				<p><strong>Email:</strong> info@intellectualtechnology.com.na</p>
				<p><strong>Phone:</strong> +264 81 870 6257</p>
				<p><strong>Office:</strong> Windhoek, Namibia</p>
			</div>
			<div class="contact-card">
				<h3>Request a demo</h3>
				<form class="contact-form" method="POST">
					<input type="text" name="name" placeholder="Full name" value="<?= htmlspecialchars($name ?? '') ?>" required>
					<input type="email" name="email" placeholder="Work email" value="<?= htmlspecialchars($email ?? '') ?>" required>
					<input type="text" name="company" placeholder="Company" value="<?= htmlspecialchars($company ?? '') ?>" required>
					<select name="team_size">
						<option value="">Select team size</option>
						<option value="1–10 employees" <?= ($team_size ?? '') === '1–10 employees' ? 'selected' : '' ?>>1–10 employees</option>
						<option value="11–50 employees" <?= ($team_size ?? '') === '11–50 employees' ? 'selected' : '' ?>>11–50 employees</option>
						<option value="51–200 employees" <?= ($team_size ?? '') === '51–200 employees' ? 'selected' : '' ?>>51–200 employees</option>
						<option value="200+ employees" <?= ($team_size ?? '') === '200+ employees' ? 'selected' : '' ?>>200+ employees</option>
					</select>
					<textarea name="message" rows="4" placeholder="Tell us what you need" required><?= htmlspecialchars($message_text ?? '') ?></textarea>
					
					<div style="margin-top: 20px; padding: 16px; background: rgba(255, 140, 66, 0.08); border-radius: 12px; border: 1px solid rgba(255, 140, 66, 0.2);">
						<label style="display: block; font-weight: 700; margin-bottom: 12px; font-size: 14px; color: var(--ink);">
							<i class="fas fa-phone" style="color: var(--primary); margin-right: 6px;"></i>How should we contact you?
						</label>
						<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
							<label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px 14px; border-radius: 10px; border: 2px solid rgba(255, 140, 66, 0.3); background: white; transition: all 0.2s;" onmouseover="this.style.borderColor='var(--primary)'; this.style.background='rgba(255, 140, 66, 0.05)';" onmouseout="this.style.borderColor='rgba(255, 140, 66, 0.3)'; this.style.background='white';">
								<input type="radio" name="contact_preference" value="email" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary);">
								<div style="flex: 1;">
									<div style="font-weight: 600; font-size: 13px; color: var(--ink);"><i class="fas fa-envelope" style="color: var(--primary); margin-right: 6px;"></i>Email</div>
									<div style="font-size: 11px; color: var(--muted);">Fastest response</div>
								</div>
							</label>
							<label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px 14px; border-radius: 10px; border: 2px solid rgba(59, 130, 246, 0.3); background: white; transition: all 0.2s;" onmouseover="this.style.borderColor='#3B82F6'; this.style.background='rgba(59, 130, 246, 0.05)';" onmouseout="this.style.borderColor='rgba(59, 130, 246, 0.3)'; this.style.background='white';">
								<input type="radio" name="contact_preference" value="phone" style="width: 18px; height: 18px; cursor: pointer; accent-color: #3B82F6;">
								<div style="flex: 1;">
									<div style="font-weight: 600; font-size: 13px; color: var(--ink);"><i class="fas fa-mobile-alt" style="color: #3B82F6; margin-right: 6px;"></i>Phone/SMS</div>
									<div style="font-size: 11px; color: var(--muted);">Direct contact</div>
								</div>
							</label>
						</div>
						<input type="tel" name="phone" placeholder="Your phone number (if phone contact preferred)" style="width: 100%; padding: 12px 14px; border-radius: 10px; border: 1px solid rgba(15, 23, 42, 0.1); font-family: inherit; font-size: 14px; display: none;" id="phone_input">
					</div>
					
					<button type="submit" style="margin-top: 16px;"><i class="fas fa-paper-plane"></i> Send request</button>
				</form>
			</div>
		</section>
	</main>

	<script>
		// Show/hide phone input based on contact preference
		const contactPreferenceRadios = document.querySelectorAll('input[name="contact_preference"]');
		const phoneInput = document.getElementById('phone_input');

		function updatePhoneInputVisibility() {
			const selectedPreference = document.querySelector('input[name="contact_preference"]:checked').value;
			if (selectedPreference === 'phone') {
				phoneInput.style.display = 'block';
				phoneInput.required = true;
			} else {
				phoneInput.style.display = 'none';
				phoneInput.required = false;
			}
		}

		contactPreferenceRadios.forEach(radio => {
			radio.addEventListener('change', updatePhoneInputVisibility);
		});

		// Initialize on page load
		updatePhoneInputVisibility();
	</script>
</body>
</html>
