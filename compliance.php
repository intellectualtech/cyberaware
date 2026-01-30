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
            --cyber-yellow: #FF8C42;
            --primary: #FF8C42;
            --cyber-gold: #FF8C42;
            --white: #FFFFFF;
            --cyber-light: #F3F4F6;
            --dark-navy: #111827;
            --dark-slate: #374151;
            --charcoal: #6B7280;
            --shield-green: #10B981;
            --alert-red: #EF4444;
            --info-blue: #3B82F6;
            --grey-50: #F3F4F6;
            --grey-100: #E5E7EB;
            --grey-200: #D1D5DB;
            --grey-300: #9CA3AF;
            --grey-400: #6B7280;
            --grey-500: #4B5563;
            --grey-700: #374151;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
            --shadow-md: 0 6px 18px rgba(0,0,0,0.09);
            --shadow-lg: 0 12px 40px rgba(0,0,0,0.12);
            --shadow-accent: 0 6px 24px rgba(var(--primary-rgb),0.12);
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

        /* Compliance Standards Cards */
        .compliance-standards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            margin: 80px 0;
        }

        .standard-card {
            background: var(--white);
            padding: 40px;
            border-radius: 14px;
            border: 2px solid rgba(var(--primary-rgb),0.15);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border-left: 5px solid var(--cyber-yellow);
        }

        .standard-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--cyber-yellow);
        }

        .standard-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, rgba(var(--primary-rgb),0.2) 0%, rgba(var(--primary-rgb),0.1) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: var(--cyber-yellow);
            border: 2px solid var(--cyber-yellow);
            margin-bottom: 20px;
        }

        .standard-card h3 {
            font-size: 22px;
            color: var(--dark-navy);
            margin-bottom: 12px;
            font-weight: 700;
        }

        .standard-code {
            font-size: 12px;
            color: var(--cyber-yellow);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 12px;
        }

        .standard-card p {
            font-size: 15px;
            color: #5A6B7C;
            line-height: 1.7;
            margin-bottom: 16px;
        }

        .compliance-features {
            list-style: none;
            font-size: 14px;
            color: #5A6B7C;
        }

        .compliance-features li {
            padding: 6px 0;
            padding-left: 24px;
            position: relative;
        }

        .compliance-features li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--shield-green);
            font-weight: 700;
            font-size: 16px;
        }

        /* Data Protection Section */
        .data-protection {
            background: linear-gradient(135deg, #F8F9FF 0%, #F3F5FF 100%);
            padding: 80px 50px;
            border-radius: 16px;
            margin: 80px 0;
            border-left: 5px solid var(--cyber-yellow);
            box-shadow: var(--shadow-lg);
        }

        .data-protection h2 {
            text-align: center;
            font-size: 42px;
            color: var(--dark-navy);
            margin-bottom: 60px;
            font-weight: 800;
        }

        .data-features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }

        .data-feature {
            background: var(--white);
            padding: 30px;
            border-radius: 12px;
            border: 2px solid rgba(var(--primary-rgb),0.1);
            text-align: center;
            transition: all 0.3s ease;
        }

        .data-feature:hover {
            transform: translateY(-4px);
            border-color: var(--cyber-yellow);
        }

        .data-feature i {
            font-size: 40px;
            color: var(--cyber-yellow);
            margin-bottom: 16px;
        }

        .data-feature h3 {
            font-size: 18px;
            color: var(--dark-navy);
            margin-bottom: 12px;
            font-weight: 700;
        }

        .data-feature p {
            font-size: 14px;
            color: #5A6B7C;
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
            background: radial-gradient(circle, rgba(var(--primary-rgb),0.15) 0%, transparent 70%);
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
            background: var(--white);
            padding: 32px;
            border-radius: 12px;
            text-align: center;
            border-left: 5px solid var(--cyber-yellow);
            transition: all 0.3s;
            box-shadow: var(--shadow-md);
        }

        .standard-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-left-color: var(--cyber-gold);
        }

        .standard-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--cyber-yellow) 0%, var(--cyber-gold) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .standard-icon i {
            font-size: 40px;
            color: var(--dark-navy);
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

            <div class="compliance-standards">
                <div class="standard-card">
                    <div class="standard-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3>ISO 27001</h3>
                    <div class="standard-code">Information Security Management</div>
                    <p>Supports Annex A.7.2.2 requirements for information security awareness, education, and training across the organization.</p>
                    <ul class="compliance-features">
                        <li>Employee awareness program documentation</li>
                        <li>Training completion tracking and records</li>
                        <li>Annual refresher training schedules</li>
                        <li>Audit-ready compliance reports</li>
                    </ul>
                </div>

                <div class="standard-card">
                    <div class="standard-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>NIST Framework</h3>
                    <div class="standard-code">Cybersecurity Standards</div>
                    <p>Addresses PR.AT (Awareness and Training) controls including phishing recognition and incident response training protocols.</p>
                    <ul class="compliance-features">
                        <li>Phishing simulation training</li>
                        <li>Social engineering awareness</li>
                        <li>Incident reporting procedures</li>
                        <li>Security culture development</li>
                    </ul>
                </div>

                <div class="standard-card">
                    <div class="standard-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3>PCI DSS</h3>
                    <div class="standard-code">Payment Card Industry</div>
                    <p>Meets Requirement 12.6 for security awareness training on cardholder data protection and threat response.</p>
                    <ul class="compliance-features">
                        <li>Cardholder data protection training</li>
                        <li>Phishing and malware awareness</li>
                        <li>Incident handling procedures</li>
                        <li>Compliance documentation</li>
                    </ul>
                </div>

                <div class="standard-card">
                    <div class="standard-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3>GDPR</h3>
                    <div class="standard-code">Data Protection Regulations</div>
                    <p>Supports Article 32 requirements for security awareness among staff responsible for processing personal data.</p>
                    <ul class="compliance-features">
                        <li>Data protection training</li>
                        <li>Privacy awareness programs</li>
                        <li>Incident response training</li>
                        <li>Documentation and audit trails</li>
                    </ul>
                </div>

                <div class="standard-card">
                    <div class="standard-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3>SOC 2</h3>
                    <div class="standard-code">Service Organization Control</div>
                    <p>Demonstrates security controls and training procedures for service organizations handling sensitive data.</p>
                    <ul class="compliance-features">
                        <li>Security control evidence</li>
                        <li>Training program documentation</li>
                        <li>Audit trail maintenance</li>
                        <li>Compliance verification reports</li>
                    </ul>
                </div>

                <div class="standard-card">
                    <div class="standard-icon">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <h3>HIPAA</h3>
                    <div class="standard-code">Healthcare Compliance</div>
                    <p>Addresses 45 CFR § 164.308(a)(5) for security awareness and training in healthcare organizations.</p>
                    <ul class="compliance-features">
                        <li>Healthcare data protection training</li>
                        <li>PHI handling awareness</li>
                        <li>HIPAA violation prevention</li>
                        <li>Compliance reporting</li>
                    </ul>
                </div>
            </div>

            <div class="data-protection">
                <h2>Data Protection & Security</h2>
                <div class="data-features-grid">
                    <div class="data-feature">
                        <i class="fas fa-lock"></i>
                        <h3>100% Internal</h3>
                        <p>All training simulations run within your organization — no external emails or data transmission</p>
                    </div>
                    <div class="data-feature">
                        <i class="fas fa-shield-alt"></i>
                        <h3>No Real Data Capture</h3>
                        <p>Simulations never capture actual credentials or sensitive information</p>
                    </div>
                    <div class="data-feature">
                        <i class="fas fa-user-secret"></i>
                        <h3>Privacy Focused</h3>
                        <p>Employee data protected with encryption and access controls</p>
                    </div>
                    <div class="data-feature">
                        <i class="fas fa-file-contract"></i>
                        <h3>Audit Trail</h3>
                        <p>Complete logging and documentation for compliance verification</p>
                    </div>
                    <div class="data-feature">
                        <i class="fas fa-key"></i>
                        <h3>Secure Architecture</h3>
                        <p>Enterprise-grade security with regular penetration testing</p>
                    </div>
                    <div class="data-feature">
                        <i class="fas fa-sync"></i>
                        <h3>Regular Updates</h3>
                        <p>Security patches and threat scenario updates on demand</p>
                    </div>
                </div>
            </div>

            <h2 style="text-align:center; margin:80px 0 30px; font-size: 36px; color: var(--dark-navy); font-weight: 800;">Key Compliance Features</h2>
            <ul class="features-list">
                <li><i class="fas fa-check-circle"></i> Detailed training records with timestamps and completion status</li>
                <li><i class="fas fa-check-circle"></i> Measurable assessment scores and improvement tracking</li>
                <li><i class="fas fa-check-circle"></i> Exportable reports for audit and compliance documentation</li>
                <li><i class="fas fa-check-circle"></i> Role-based training assignment and progress monitoring</li>
                <li><i class="fas fa-check-circle"></i> Regular scenario updates to reflect current threats</li>
                <li><i class="fas fa-check-circle"></i> Secure, internal-only simulations with no external data transmission</li>
            </ul>

            <p style="text-align:center; font-size:18px; margin-top:80px; color: var(--dark-navy); font-weight: 600;">
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