<?php
// privacy.php - Privacy Policy Page for CyberAware

require_once 'config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (hasRole('admin') || hasRole('manager')) {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: trainee/dashboard.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy – CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        /* Header */
        .header {
            background: backdrop-filter blur(10px);
            background-color: rgba(255, 255, 255, 0.8);
            border-bottom: 1px solid rgba(226, 232, 240, 0.5);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            padding: 1rem 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: auto;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            gap: 60px;
        }

        .logo {
            font-size: 1.375rem;
            font-weight: 800;
            color: var(--orange-600);
            display: flex;
            align-items: center;
            gap: 0.625rem;
            text-decoration: none;
            white-space: nowrap;
            flex-shrink: 0;
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
            align-items: center;
            flex: 1;
            justify-content: center;
        }

        .nav-menu a {
            color: var(--slate-700);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
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

        .nav-menu a:hover, .nav-menu a.active {
            color: var(--orange-500);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-shrink: 0;
        }

        .btn {
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            border: none;
            transition: .2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--orange-500), var(--orange-600));
            color: var(--white);
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(251, 146, 60, 0.3);
        }

        .btn-primary:hover {
            transform: scale(1.05) translateY(-3px);
            box-shadow: 0 8px 24px rgba(251, 146, 60, 0.4);
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 32px;
        }

        /* Hero Section */
        .hero {
            background: backdrop-filter blur(20px);
            background-color: linear-gradient(135deg, rgba(251, 146, 60, 0.15), rgba(251, 146, 60, 0.05));
            color: var(--slate-900);
            padding: 5rem 2.5rem;
            text-align: center;
            border-radius: 1rem;
            margin: 3.75rem auto;
            max-width: 62.5rem;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(226, 232, 240, 0.5);
            position: relative;
        }
        
        .hero::before {
            display: none;
        }

        .hero h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1.25rem;
            letter-spacing: 0;
            color: var(--slate-900);
        }

        .hero p {
            font-size: 1rem;
            max-width: 37.5rem;
            margin: 0 auto;
            color: var(--slate-600);
        }

        /* Privacy Content */
        .privacy-content {
            background: backdrop-filter blur(10px);
            background-color: rgba(255, 255, 255, 0.8);
            padding: 3.75rem;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 5rem;
            border: 1px solid rgba(226, 232, 240, 0.5);
        }

        .privacy-content h2 {
            font-size: 2.25rem;
            font-weight: 700;
            margin: 2.5rem 0 1.25rem;
            color: var(--slate-900);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .privacy-content h2:first-child {
            margin-top: 0;
        }

        .privacy-content h2 i {
            color: var(--orange-500);
            font-size: 2rem;
        }

        .privacy-content p {
            font-size: 1.125rem;
            line-height: 1.8;
            color: var(--slate-600);
            margin-bottom: 1.5rem;
        }

        .privacy-content ul {
            padding-left: 30px;
            margin: 20px 0;
        }

        .privacy-content li {
            font-size: 18px;
            line-height: 1.8;
            margin: 16px 0;
            color: var(--gray-700);
        }

        .privacy-content strong {
            color: var(--dark);
        }

        .last-updated {
            text-align: center;
            color: var(--gray-600);
            font-style: italic;
            margin-top: 40px;
            font-size: 16px;
        }

        .cta-section {
            background: linear-gradient(135deg, var(--orange-400), var(--orange-600));
            color: var(--white);
            padding: 3.125rem 2.5rem;
            border-radius: 1rem;
            text-align: center;
            box-shadow: 0 30px 80px rgba(251, 146, 60, 0.2);
            margin: 3.75rem auto;
            max-width: 56.25rem;
            border: none;
        }

        .cta-section h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--white);
        }

        .cta-section p {
            font-size: 1rem;
            margin-bottom: 2rem;
            opacity: 1;
            color: rgba(255, 255, 255, 0.95);
        }

        .cta-btn {
            padding: 0.875rem 2.5rem;
            font-size: 1rem;
            background: var(--white);
            color: var(--orange-600);
            border-radius: 0.5rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(251, 146, 60, 0.3);
        }

        .cta-btn:hover {
            transform: scale(1.05) translateY(-3px);
            box-shadow: 0 8px 24px rgba(251, 146, 60, 0.4);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .header-content {
                padding: 16px 20px;
            }

            .nav-menu {
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: var(--white);
                flex-direction: column;
                gap: 12px;
                align-items: center;
                padding: 32px 0;
                box-shadow: var(--shadow-lg);
                z-index: 999;
                display: none;
            }

            .nav-menu.active {
                display: flex;
            }

            .nav-menu a {
                font-size: 17px;
                padding: 14px 40px;
                border-radius: 8px;
                width: auto;
                text-align: center;
            }

            .nav-menu a:hover {
                background: var(--primary-lighter);
                color: var(--primary);
            }

            .menu-toggle {
                display: block;
            }

            .header-right {
                gap: 12px;
            }

            .hero h1 {
                font-size: 42px;
            }

            .hero p {
                font-size: 20px;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 40px 20px;
            }

            .hero {
                padding: 80px 20px;
                margin-bottom: 40px;
            }

            .hero h1 {
                font-size: 36px;
            }

            .hero p {
                font-size: 18px;
            }


        /* Orange + White + Platinum Grey refresh */
        body {
            font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: radial-gradient(1000px 520px at 12% -10%, #fff1e4 0%, transparent 60%),
                linear-gradient(135deg, #ffffff 0%, var(--platinum) 100%);
            color: var(--dark-navy);
        }

        h1, h2, h3 {
            font-family: 'Space Grotesk', 'Segoe UI', sans-serif;
        }

        .header {
            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid rgba(255, 140, 66, 0.2);
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
            backdrop-filter: blur(10px);
        }

        .nav-menu a {
            color: var(--dark-navy);
        }

        .hero {
            background: linear-gradient(135deg, #ff8c42 0%, #ffd2b3 100%);
            color: var(--dark-navy);
            border: 1px solid rgba(255, 140, 66, 0.25);
        }
            .privacy-content {
                padding: 40px 24px;
            }

            .privacy-content h2 {
                font-size: 30px;
            }

            .cta-section {
                padding: 60px 24px;
            }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-shield-alt"></i>
                CyberAware
            </a>

            <nav class="nav-menu">
                <a href="index.php">Home</a>
                <a href="index.php#features">Features</a>
                <a href="services.php">Services</a>
                <a href="about.php">About Us</a>
                <a href="compliance.php">Compliance</a>
                <a href="contact.php">Contact Us</a>
            </nav>

            <div class="header-right">
                <button class="menu-toggle" id="menu-toggle" aria-label="Toggle navigation menu">
                    <i class="fas fa-bars"></i>
                </button>

                <a href="pages/login.php" class="btn btn-primary">Login</a>
            </div>
        </div>
    </div>

    <!-- Hero -->
    <div class="hero">
        <h1>Privacy Policy</h1>
        <p>Your privacy and data security are our top priorities</p>
    </div>

    <div class="container">
        <div class="privacy-content">
            <h2>
                <i class="fas fa-lock"></i>
                Privacy Commitment
            </h2>
            <p>CyberAware, developed by Intellectual Technology, is committed to protecting your privacy. This Privacy Policy explains how we collect, use, and safeguard your information when you use our security awareness training platform.</p>
            <p><strong>Last updated: January 11, 2026</strong></p>

            <h2>
                <i class="fas fa-database"></i>
                Information We Collect
            </h2>
            <p>We collect only the information necessary to provide effective training and platform functionality:</p>
            <ul>
                <li><strong>Account Information:</strong> Username, full name, email address, and role (provided during registration)</li>
                <li><strong>Training Activity:</strong> Module completion status, scores, timestamps, and simulation decisions (used solely for progress tracking and reporting)</li>
                <li><strong>Technical Data:</strong> Login times, IP addresses, and browser information (for security and troubleshooting)</li>
            </ul>
            <p><strong>We do NOT collect:</strong> Real passwords, personal financial data, or any sensitive information entered during simulations. All training interactions are simulated and safe.</p>

            <h2>
                <i class="fas fa-shield-alt"></i>
                How We Use Your Information
            </h2>
            <p>Your data is used exclusively for:</p>
            <ul>
                <li>Delivering personalized training experiences</li>
                <li>Generating progress reports and compliance documentation</li>
                <li>Improving platform functionality and security</li>
                <li>Communicating important platform updates (if enabled)</li>
            </ul>

            <h2>
                <i class="fas fa-user-shield"></i>
                Data Protection & Security
            </h2>
            <p>We implement industry-standard security measures including:</p>
            <ul>
                <li>Encrypted database storage</li>
                <li>Secure session management</li>
                <li>Role-based access controls</li>
                <li>Regular security audits</li>
            </ul>
            <p>All data remains within your organization's controlled environment. No training data is shared with third parties.</p>

            <h2>
                <i class="fas fa-share-alt"></i>
                Data Sharing & Disclosure
            </h2>
            <p>We do not sell, trade, or share your personal information with third parties. Data may be disclosed only:</p>
            <ul>
                <li>To comply with legal requirements</li>
                <li>To protect the rights and safety of users and the platform</li>
                <li>With authorized administrators within your organization</li>
            </ul>

            <h2>
                <i class="fas fa-cookie"></i>
                Cookies & Tracking
            </h2>
            <p>CyberAware uses session cookies for authentication and functionality. No tracking cookies or analytics that monitor user behavior outside the platform are used.</p>

            <h2>
                <i class="fas fa-user-cog"></i>
                Your Rights
            </h2>
            <p>You have the right to:</p>
            <ul>
                <li>Access your personal data and training records</li>
                <li>Request correction of inaccurate information</li>
                <li>Request deletion of your account (subject to organizational policies)</li>
            </ul>
            <p>Contact your system administrator or Intellectual Technology for privacy-related requests.</p>

            <h2>
                <i class="fas fa-envelope"></i>
                Contact Us
            </h2>
            <p>For privacy questions or concerns, please contact:</p>
            <p><strong>Intellectual Technology</strong><br>
            Email: privacy@intellectualtechnology.com.na<br>
            Website: <a href="https://intellectualtechnology.com.na" style="color: var(--primary);">intellectualtechnology.com.na</a></p>

            <p class="last-updated">
                This Privacy Policy was last updated on January 11, 2026.<br>
                We may update this policy periodically. Continued use of the platform constitutes acceptance of changes.
            </p>
        </div>

        <div class="cta-section">
            <h2>Ready to Train Securely?</h2>
            <p>Start your privacy-protected security awareness journey today</p>
            <a href="pages/login.php" class="cta-btn">
                Login to CyberAware
            </a>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            if (menuToggle) {
                const navMenu = document.querySelector('.nav-menu');
                const icon = menuToggle.querySelector('i');

                menuToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');

                    if (navMenu.classList.contains('active')) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');
                        document.body.style.overflow = 'hidden';
                    } else {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                        document.body.style.overflow = '';
                    }
                });

                // Close menu when a link is clicked
                document.querySelectorAll('.nav-menu a').forEach(function(link) {
                    link.addEventListener('click', function() {
                        navMenu.classList.remove('active');
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                        document.body.style.overflow = '';
                    });
                });
            }
        });
    </script>

</body>
</html>