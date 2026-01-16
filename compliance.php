<?php
// compliance.php - Compliance Page for CyberAware

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
    <title>Compliance & Standards – CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF8C42;
            --primary-dark: #E67A2E;
            --primary-light: #FFF4ED;
            --primary-lighter: #FFEAD9;
            --success: #00A65A;
            --warning: #F39C12;
            --danger: #DD4B39;
            --dark: #2C2C2C;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #D4D4D4;
            --gray-400: #B8B8B8;
            --gray-500: #9E9E9E;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --white: #FFFFFF;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.1);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.15);
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
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
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
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
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
            color: var(--gray-700);
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: color 0.2s;
        }

        .nav-menu a:hover, .nav-menu a.active {
            color: var(--primary);
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
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 140, 66, 0.3);
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 32px;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 120px 40px;
            text-align: center;
            border-radius: 16px;
            margin-bottom: 80px;
            box-shadow: var(--shadow-lg);
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

        /* Compliance Content */
        .compliance-content {
            background: var(--white);
            padding: 60px;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            margin-bottom: 80px;
        }

        .compliance-content h2 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 30px;
            color: var(--dark);
            text-align: center;
        }

        .compliance-content p {
            font-size: 18px;
            line-height: 1.8;
            color: var(--gray-700);
            margin-bottom: 24px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        .standards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 50px 0;
        }

        .standard-card {
            background: var(--gray-50);
            padding: 32px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid var(--gray-200);
            transition: all 0.3s;
        }

        .standard-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-md);
        }

        .standard-icon {
            width: 80px;
            height: 80px;
            background: var(--primary-lighter);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .standard-icon i {
            font-size: 40px;
            color: var(--primary);
        }

        .standard-card h3 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--dark);
        }

        .standard-card p {
            font-size: 15px;
            color: var(--gray-600);
            line-height: 1.6;
        }

        .features-list {
            max-width: 900px;
            margin: 50px auto;
        }

        .features-list li {
            font-size: 18px;
            line-height: 1.8;
            margin: 20px 0;
            padding-left: 40px;
            position: relative;
            color: var(--gray-700);
        }

        .features-list li i {
            position: absolute;
            left: 0;
            top: 4px;
            color: var(--success);
            font-size: 24px;
        }

        .cta-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 80px 40px;
            border-radius: 16px;
            text-align: center;
            box-shadow: var(--shadow-lg);
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

            .standards-grid {
                grid-template-columns: 1fr;
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

            .compliance-content {
                padding: 40px 24px;
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
                <a href="compliance.php" class="active">Compliance</a>
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
        <h1>Compliance & Regulatory Standards</h1>
        <p>CyberAware is designed to help your organization meet global security awareness training requirements</p>
    </div>

    <div class="container">
        <div class="compliance-content">
            <h2>Built for Regulatory Compliance</h2>
            <p>CyberAware aligns with leading international cybersecurity frameworks and regulations, making it easier for organizations to demonstrate compliance through employee training programs.</p>
            <p>Our platform provides measurable evidence of training delivery, user participation, and knowledge retention — essential for audits and regulatory reporting.</p>

            <div class="standards-grid">
                <div class="standard-card">
                    <div class="standard-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3>ISO 27001</h3>
                    <p>Supports Annex A.7.2.2 requirements for information security awareness, education, and training.</p>
                </div>

                <div class="standard-card">
                    <div class="standard-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>NIST Cybersecurity Framework</h3>
                    <p>Addresses PR.AT (Awareness and Training) controls including phishing recognition and incident response training.</p>
                </div>

                <div class="standard-card">
                    <div class="standard-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3>PCI DSS</h3>
                    <p>Meets Requirement 12.6 for security awareness training on cardholder data protection and phishing threats.</p>
                </div>
            </div>

            <h2 style="text-align:center; margin:50px 0 30px;">Key Compliance Features</h2>
            <ul class="features-list">
                <li><i class="fas fa-check-circle"></i> Detailed training records with timestamps and completion status</li>
                <li><i class="fas fa-check-circle"></i> Measurable assessment scores and improvement tracking</li>
                <li><i class="fas fa-check-circle"></i> Exportable reports for audit and compliance documentation</li>
                <li><i class="fas fa-check-circle"></i> Role-based training assignment and progress monitoring</li>
                <li><i class="fas fa-check-circle"></i> Regular scenario updates to reflect current threats</li>
                <li><i class="fas fa-check-circle"></i> Secure, internal-only simulations with no external data transmission</li>
            </ul>

            <p style="text-align:center; font-size:18px; margin-top:50px;">
                CyberAware helps you not just meet, but exceed security awareness training requirements.
            </p>
        </div>

        <div class="cta-section">
            <h2>Start Your Compliance Journey Today</h2>
            <p>Implement effective security awareness training that satisfies auditors and protects your organization</p>
            <a href="pages/login.php" class="cta-btn">
                Begin Training Now
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