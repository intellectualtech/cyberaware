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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --cyber-yellow: #FFD60A;
            --cyber-gold: #FFC300;
            --cyber-light: #FFF8DC;
            --white: #FFFFFF;
            --dark-navy: #0F1419;
            --dark-slate: #1A1E2E;
            --charcoal: #2D3142;
            --shield-green: #10B981;
            --alert-red: #EF4444;
            --info-blue: #3B82F6;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #D4D4D4;
            --gray-400: #B8B8B8;
            --gray-500: #9E9E9E;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 10px 32px rgba(0, 0, 0, 0.2);
            --shadow-yellow: 0 0 20px rgba(255, 214, 10, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--gray-50);
            color: var(--dark);
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: var(--dark-navy);
            border-bottom: 5px solid var(--cyber-yellow);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--cyber-yellow);
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .logo:hover {
            color: var(--cyber-gold);
            text-shadow: 0 0 15px rgba(255, 214, 10, 0.4);
        }

        .logo i {
            font-size: 28px;
        }

        .nav-menu {
            display: flex;
            gap: 32px;
            align-items: center;
        }

        .nav-menu a {
            color: var(--white);
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: all 0.3s ease;
            position: relative;
            padding-bottom: 4px;
        }

        .nav-menu a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--cyber-yellow);
            transition: width 0.3s ease;
        }

        .nav-menu a:hover::after, .nav-menu a.active::after {
            width: 100%;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 28px;
            color: var(--gray-700);
            cursor: pointer;
            padding: 8px;
        }

        .btn {
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--dark-navy);
            color: var(--cyber-yellow);
            border: 2px solid var(--cyber-yellow);
        }

        .btn-primary:hover {
            background: var(--cyber-yellow);
            color: var(--dark-navy);
            transform: translateY(-3px);
            box-shadow: var(--shadow-yellow);
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 32px;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--dark-slate) 100%);
            color: white;
            padding: 120px 40px;
            text-align: center;
            border-radius: 16px;
            margin-bottom: 80px;
            box-shadow: var(--shadow-lg);
            border-left: 8px solid var(--cyber-yellow);
            position: relative;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255, 214, 10, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero h1 {
            font-size: 56px;
            font-weight: 800;
            margin-bottom: 24px;
            letter-spacing: -1px;
        }

        .hero p {
            font-size: 22px;
            max-width: 900px;
            margin: 0 auto;
            opacity: 0.95;
        }

        /* Privacy Content */
        .privacy-content {
            background: var(--white);
            padding: 60px;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            margin-bottom: 80px;
        }

        .privacy-content h2 {
            font-size: 36px;
            font-weight: 700;
            margin: 40px 0 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .privacy-content h2:first-child {
            margin-top: 0;
        }

        .privacy-content h2 i {
            color: var(--cyber-yellow);
            font-size: 32px;
        }

        .privacy-content p {
            font-size: 18px;
            line-height: 1.8;
            color: var(--gray-700);
            margin-bottom: 24px;
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
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--dark-slate) 100%);
            color: white;
            padding: 80px 40px;
            border-radius: 16px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            border-left: 8px solid var(--cyber-yellow);
        }

        .cta-section h2 {
            font-size: 42px;
            margin-bottom: 20px;
        }

        .cta-section p {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.95;
        }

        .cta-btn {
            padding: 18px 48px;
            font-size: 18px;
            background: white;
            color: var(--primary);
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .cta-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.3);
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
                <a href="about.php">About</a>
                <a href="compliance.php">Compliance</a>
                <a href="privacy.php" class="active">Privacy</a>
                <a href="contact.php">Contact</a>
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