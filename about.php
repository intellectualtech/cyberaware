<?php
// about.php - About Page for CyberAware

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
    <title>About CyberAware – Intellectual Technology</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Primary Brand Colors (Orange / White / Grey theme) */
            --cyber-yellow: #FF8C42;
            --primary: #FF8C42;
            --cyber-gold: #FF8C42;
            --white: #FFFFFF;
            --cyber-light: #F3F4F6;
            --primary-rgb: 255,140,66;
            --platinum: #E5E4E2;

            /* Grey Tones */
            --grey-50: #F3F4F6;
            --grey-100: #E5E7EB;
            --grey-300: #D1D5DB;
            --grey-500: #6B7280;
            --grey-700: #374151;
            --text-dark: #111827;

            /* Legacy/dark variable for existing usage */
            --dark-navy: #111827;
            --dark-slate: #374151;
            --charcoal: #6B7280;

            /* Accent Colors */
            --shield-green: #FF8C42;
            --alert-red: #EF4444;
            --info-blue: #3B82F6;
            
            /* Shadows & Effects */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.2);
            --shadow-yellow: 0 0 20px rgba(var(--primary-rgb),0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #FFF8DC 0%, #FFFFFF 50%, #FFF8DC 100%);
            color: var(--dark-navy);
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: var(--dark-navy);
            border-bottom: 4px solid var(--cyber-yellow);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-md);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            color: var(--cyber-yellow);
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
            text-shadow: 0 0 10px rgba(var(--primary-rgb),0.5);
        }

        .logo i {
            font-size: 32px;
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
            padding: 10px 15px;
            border-radius: 6px;
            position: relative;
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

        .nav-menu a:hover {
            background: rgba(var(--primary-rgb),0.1);
            color: var(--cyber-yellow);
        }

        .nav-menu a:hover::after {
            width: 100%;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn {
            padding: 11px 28px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
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
            box-shadow: 0 8px 20px rgba(var(--primary-rgb),0.3);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px;
        }

        .section {
            padding: 80px 32px;
            text-align: center;
        }

        .section h2 {
            font-size: 48px;
            margin-bottom: 30px;
            font-weight: 800;
            color: var(--dark-navy);
        }

        .section h2::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: var(--cyber-yellow);
            margin: 20px auto 0;
            border-radius: 2px;
        }

        .section p {
            font-size: 18px;
            line-height: 1.8;
            color: var(--charcoal);
            max-width: 900px;
            margin: 0 auto;
        }

        .card {
            background: var(--white);
            padding: 35px;
            border-radius: 14px;
            box-shadow: var(--shadow-md);
            margin: 30px 0;
            border-left: 5px solid var(--cyber-yellow);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            border-left-color: var(--cyber-gold);
        }

        .card h3 {
            color: var(--dark-navy);
            margin-bottom: 20px;
            font-size: 26px;
            font-weight: 700;
        }

        .card p {
            color: var(--charcoal);
            line-height: 1.8;
            font-size: 16px;
        }

        /* Footer */
        .footer {
            background: var(--dark-navy);
            padding: 40px 20px;
            margin-top: 60px;
            border-top: 4px solid var(--cyber-yellow);
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }

        .footer a {
            color: var(--cyber-yellow);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer a:hover {
            text-decoration: underline;
            color: var(--white);
        }

        @media (max-width: 768px) {
            .section h2 {
                font-size: 36px;
            }

            .section {
                padding: 50px 24px;
            }

            .card {
                padding: 24px;
            }
        }
    </style>
<style>
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
            box-shadow: 0 4px 12px rgba(var(--primary-rgb),0.3);
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
            margin: 0 auto 40px;
            opacity: 0.95;
        }

        /* About Content */
        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            margin-bottom: 100px;
        }

        .about-text h2 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--dark);
        }

        .about-text p {
            font-size: 18px;
            line-height: 1.8;
            color: var(--gray-700);
            margin-bottom: 20px;
        }

        .about-image {
            text-align: center;
        }

        .about-image img {
            max-width: 100%;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
        }

        .company-info {
            background: var(--white);
            padding: 60px;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            text-align: center;
            margin: 80px 0;
        }

        /* Organization Structure Section */
        .org-structure {
            background: linear-gradient(135deg, #FFF8DC 0%, #FFFFFF 100%);
            padding: 100px 50px;
            border-radius: 16px;
            margin: 80px 0;
            border-left: 5px solid var(--cyber-yellow);
            box-shadow: var(--shadow-lg);
        }

        .org-structure h2 {
            text-align: center;
            font-size: 42px;
            color: var(--dark-navy);
            margin-bottom: 60px;
            font-weight: 800;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }

        .team-card {
            background: var(--white);
            padding: 40px;
            border-radius: 14px;
            border: 2px solid rgba(var(--primary-rgb),0.2);
            box-shadow: var(--shadow-md);
            text-align: center;
            transition: all 0.3s ease;
        }

        .team-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--cyber-yellow);
        }

        .team-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(var(--primary-rgb),0.2) 0%, rgba(var(--primary-rgb),0.1) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 40px;
            color: var(--cyber-yellow);
            border: 2px solid var(--cyber-yellow);
        }

        .team-card h3 {
            font-size: 24px;
            color: var(--dark-navy);
            margin-bottom: 12px;
            font-weight: 700;
        }

        .team-title {
            font-size: 14px;
            color: var(--cyber-yellow);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 16px;
        }

        .team-card p {
            font-size: 15px;
            color: #5A6B7C;
            line-height: 1.7;
        }

        /* Certifications Section */
        .certifications-section {
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--dark-slate) 100%);
            color: var(--white);
            padding: 100px 50px;
            border-radius: 16px;
            margin: 80px 0;
            box-shadow: var(--shadow-lg), var(--shadow-yellow);
            border-left: 5px solid var(--cyber-yellow);
            position: relative;
            overflow: hidden;
        }

        .certifications-section::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(var(--primary-rgb),0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .certifications-section h2 {
            text-align: center;
            font-size: 42px;
            margin-bottom: 60px;
            font-weight: 800;
            position: relative;
            z-index: 1;
        }

        .cert-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            position: relative;
            z-index: 1;
        }

        .cert-badge {
            background: rgba(var(--primary-rgb),0.1);
            padding: 40px;
            border-radius: 14px;
            text-align: center;
            border: 2px solid rgba(var(--primary-rgb),0.2);
            transition: all 0.3s ease;
        }

        .cert-badge:hover {
            background: rgba(var(--primary-rgb),0.15);
            border-color: var(--cyber-yellow);
            transform: translateY(-8px);
        }

        .cert-icon {
            font-size: 48px;
            color: var(--cyber-yellow);
            margin-bottom: 16px;
        }

        .cert-badge h3 {
            font-size: 22px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .cert-badge p {
            font-size: 14px;
            opacity: 0.9;
        }

        .company-info h2 {
            font-size: 36px;
            margin-bottom: 30px;
            color: var(--primary-dark);
        }

        .company-info p {
            font-size: 18px;
            line-height: 1.8;
            color: var(--gray-700);
            max-width: 900px;
            margin: 0 auto 40px;
        }

        .company-logo {
            width: 200px;
            margin: 40px auto;
        }

        .company-logo img {
            max-width: 100%;
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

            .about-content {
                grid-template-columns: 1fr;
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

            .about-content {
                gap: 40px;
            }

            .company-info {
                padding: 40px 24px;
            }

            .cta-section {
                padding: 60px 24px;
            }
        }

        /* Orange + White + Platinum Grey refresh */
        body {
            font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: radial-gradient(1000px 520px at 10% -10%, #fff1e4 0%, transparent 60%),
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

        .nav-menu a:hover, .nav-menu a.active {
            color: var(--primary);
        }

        .hero {
            background: linear-gradient(135deg, #ff8c42 0%, #ffd2b3 100%);
            color: var(--dark-navy);
            border: 1px solid rgba(255, 140, 66, 0.25);
            box-shadow: 0 18px 40px rgba(255, 140, 66, 0.2);
        }

        .org-structure,
        .company-info {
            background: var(--white);
            border: 1px solid rgba(15, 23, 42, 0.06);
        }

        .certifications-section {
            background: linear-gradient(135deg, #ffffff 0%, #fff1e4 100%);
            color: var(--dark-navy);
        }

        .cta-section {
            background: linear-gradient(135deg, #ff8c42 0%, #ffb884 100%);
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
                <a href="about.php" class="active">About</a>
                <a href="compliance.php">Compliance</a>
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
        <h1>About CyberAware</h1>
        <p>The flagship cybersecurity awareness training platform from Intellectual Technology</p>
    </div>

    <div class="container">
        <!-- About CyberAware -->
        <div class="about-content">
            <div class="about-text">
                <h2>Empowering Organizations Against Cyber Threats</h2>
                <p>CyberAware is a comprehensive security awareness training platform designed to educate employees on recognizing and responding to modern cyber threats. Through realistic, interactive simulations, users learn to identify phishing emails, fake login pages, social engineering attempts, dangerous attachments, and malicious links.</p>
                <p>Built with compliance in mind, CyberAware helps organizations meet ISO 27001, NIST, and other regulatory requirements while fostering a strong security culture.</p>
                <p>All simulations are 100% safe — no real data is collected, and training occurs in a controlled environment.</p>
            </div>
            <div class="about-image">
                <img src="https://cyberawereness.intellectualtechnology.com.na/cyberaware.png" alt="CyberAware Platform">
            </div>
        </div>

        <!-- Company Info -->
        <div class="company-info">
            <h2>Intellectual Technology</h2>
            <p>CyberAware is the flagship product of <strong>Intellectual Technology</strong>, a leading Namibian technology solutions provider specializing in innovative software and cybersecurity services.</p>
            <p>With years of experience in digital transformation and security, we created CyberAware to address the growing need for effective, engaging employee training in the face of increasingly sophisticated cyber attacks.</p>
            <p>We are proud to serve organizations across Namibia and beyond, helping them build resilient teams in an ever-evolving threat landscape.</p>
            
            <div class="company-logo">
                <img src="https://cyberawereness.intellectualtechnology.com.na/intellectual-logo.jpeg" alt="Intellectual Technology Logo">
            </div>
            
            <p style="margin-top: 40px;">
                <a href="https://intellectualtechnology.com.na" target="_blank" class="btn btn-primary" style="font-size: 16px; padding: 14px 32px;">
                    Visit Intellectual Technology Website →
                </a>
            </p>
        </div>

        <!-- Organization Structure -->
        <div class="org-structure">
            <h2>Our Leadership & Team</h2>
            <div class="team-grid">
                <div class="team-card">
                    <div class="team-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3>Leadership Vision</h3>
                    <div class="team-title">Executive Direction</div>
                    <p>Guided by cybersecurity experts with decades of combined experience in information security, risk management, and organizational defense.</p>
                </div>
                <div class="team-card">
                    <div class="team-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Security Experts</h3>
                    <div class="team-title">Technical Foundation</div>
                    <p>Team of certified security professionals (CISSP, CEH, OSCP) ensuring platform effectiveness and compliance standards.</p>
                </div>
                <div class="team-card">
                    <div class="team-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Customer Success</h3>
                    <div class="team-title">24/7 Support</div>
                    <p>Dedicated team providing round-the-clock support, training, and consulting to ensure your organization's success.</p>
                </div>
                <div class="team-card">
                    <div class="team-icon">
                        <i class="fas fa-flask"></i>
                    </div>
                    <h3>Research & Development</h3>
                    <div class="team-title">Innovation</div>
                    <p>Continuous research team monitoring emerging threats and developing cutting-edge training simulations.</p>
                </div>
                <div class="team-card">
                    <div class="team-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Learning Specialists</h3>
                    <div class="team-title">Pedagogy</div>
                    <p>Instructional designers ensuring training content is engaging, effective, and scientifically-backed.</p>
                </div>
                <div class="team-card">
                    <div class="team-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3>Technical Operations</h3>
                    <div class="team-title">Infrastructure</div>
                    <p>Reliability engineers maintaining 99.9% uptime and ensuring secure, scalable platform operations.</p>
                </div>
            </div>
        </div>

        <!-- Certifications & Compliance -->
        <div class="certifications-section">
            <h2>Compliance & Certifications</h2>
            <div class="cert-grid">
                <div class="cert-badge">
                    <div class="cert-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3>ISO 27001</h3>
                    <p>Information Security Management certified for industry-standard practices</p>
                </div>
                <div class="cert-badge">
                    <div class="cert-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>GDPR Compliant</h3>
                    <p>Full European data protection and privacy regulation compliance</p>
                </div>
                <div class="cert-badge">
                    <div class="cert-icon">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <h3>NIST Framework</h3>
                    <p>Aligned with NIST cybersecurity and awareness training guidelines</p>
                </div>
                <div class="cert-badge">
                    <div class="cert-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3>SOC 2 Type II</h3>
                    <p>Service Organization Control certified for security and availability</p>
                </div>
                <div class="cert-badge">
                    <div class="cert-icon">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <h3>POPIA Compliant</h3>
                    <p>Protection of Personal Information Act compliant for South African standards</p>
                </div>
                <div class="cert-badge">
                    <div class="cert-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3>ISO 9001</h3>
                    <p>Quality Management System certified for continuous improvement</p>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="cta-section">
            <h2>Ready to Protect Your Organization?</h2>
            <p>Join hundreds of users already training with CyberAware</p>
            <a href="pages/login.php" class="cta-btn">
                Start Training Now
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