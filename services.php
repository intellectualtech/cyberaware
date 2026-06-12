<?php
// services.php - Services Page for CyberAware

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
    <title>Services – CyberAware by Intellectual Technology</title>
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
            background: radial-gradient(1000px 520px at 10% -10%, #fff1e4 0%, transparent 60%),
                linear-gradient(135deg, #ffffff 0%, var(--slate-100) 100%);
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
            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid rgba(255, 140, 66, 0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.08);
            border-radius: 0 0 1.5rem 1.5rem;
            padding: 1rem 5%;
            backdrop-filter: blur(10px);
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
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--orange-500), var(--orange-600));
            color: var(--white);
            border: none;
            box-shadow: 0 4px 12px rgba(251, 146, 60, 0.3);
        }

        .btn-primary:hover {
            transform: scale(1.05) translateY(-3px);
            box-shadow: 0 8px 24px rgba(251, 146, 60, 0.4);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #ff8c42 0%, #ffd2b3 100%);
            color: var(--slate-900);
            padding: 80px 40px;
            text-align: center;
            border-radius: 16px;
            margin: 60px auto;
            max-width: 1000px;
            box-shadow: 0 18px 40px rgba(255, 140, 66, 0.2);
            border: 1px solid rgba(255, 140, 66, 0.25);
        }

        .hero-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .hero h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 48px;
            font-weight: 800;
            margin: 0;
            color: var(--slate-900);
        }

        .hero p {
            font-size: 18px;
            margin: 0;
            color: var(--slate-900);
            max-width: 600px;
        }

        /* Services Grid */
        .services-section {
            padding: 80px 32px;
        }

        .section-title {
            text-align: center;
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 60px;
            color: var(--slate-900);
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 40px;
            margin-bottom: 80px;
        }

        .service-card {
            background: var(--white);
            padding: 40px;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(226, 232, 240, 0.5);
            transition: all 0.3s ease;
            text-align: center;
        }

        .service-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 48px rgba(251, 146, 60, 0.15);
            border-color: var(--orange-500);
        }

        .service-icon {
            width: 5rem;
            height: 5rem;
            background: linear-gradient(135deg, var(--orange-400), var(--orange-600));
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            color: var(--white);
            box-shadow: 0 8px 24px rgba(251, 146, 60, 0.3);
        }

        .service-card h3 {
            font-size: 24px;
            color: var(--slate-900);
            margin-bottom: 16px;
            font-weight: 700;
        }

        .service-card p {
            font-size: 15px;
            color: var(--slate-600);
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .service-features {
            text-align: left;
            margin: 20px 0;
        }

        .service-features li {
            list-style: none;
            padding: 8px 0;
            padding-left: 24px;
            position: relative;
            color: var(--slate-600);
            font-size: 14px;
        }

        .service-features li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--orange-500);
            font-weight: bold;
            font-size: 18px;
        }

        /* Features Overview */
        .features-overview {
            background: linear-gradient(135deg, rgba(251, 146, 60, 0.1), rgba(251, 146, 60, 0.05));
            padding: 80px 40px;
            border-radius: 16px;
            margin: 80px 0;
            border: 1px solid rgba(251, 146, 60, 0.2);
        }

        .features-overview h2 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 50px;
            text-align: center;
            color: var(--slate-900);
        }

        .features-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .feature-item {
            background: var(--white);
            padding: 30px;
            border-radius: 12px;
            border-left: 4px solid var(--orange-500);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .feature-item h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--slate-900);
        }

        .feature-item p {
            font-size: 15px;
            color: var(--slate-600);
            line-height: 1.6;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #ff8c42 0%, #ffb884 100%);
            color: var(--white);
            padding: 80px 40px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 18px 40px rgba(255, 140, 66, 0.2);
            margin: 80px 0;
        }

        .cta-section h2 {
            font-size: 42px;
            margin-bottom: 20px;
            font-weight: 800;
        }

        .cta-section p {
            font-size: 18px;
            margin-bottom: 40px;
            opacity: 0.95;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-btn {
            padding: 16px 44px;
            font-size: 16px;
            background: var(--white);
            color: var(--orange-500);
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .cta-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.3);
        }

        /* Footer */
        .footer {
            background: var(--slate-900);
            padding: 40px 20px;
            margin-top: 60px;
            border-top: 4px solid var(--orange-500);
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }

        .footer a {
            color: var(--orange-400);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer a:hover {
            text-decoration: underline;
            color: var(--white);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .header-content {
                padding: 16px 20px;
                gap: 30px;
            }

            .nav-menu {
                gap: 20px;
            }

            .hero h1 {
                font-size: 36px;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 40px 20px;
            }

            .hero {
                padding: 60px 20px;
                margin: 40px auto;
            }

            .hero h1 {
                font-size: 32px;
            }

            .hero p {
                font-size: 16px;
            }

            .section-title {
                font-size: 32px;
                margin-bottom: 40px;
            }

            .services-grid {
                gap: 20px;
            }

            .service-card {
                padding: 30px;
            }

            .features-overview {
                padding: 50px 20px;
            }

            .cta-section {
                padding: 60px 20px;
            }

            .cta-section h2 {
                font-size: 32px;
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
                <a href="services.php" class="active">Services</a>
                <a href="about.php">About Us</a>
                <a href="compliance.php">Compliance</a>
                <a href="contact.php">Contact Us</a>
            </nav>

            <div class="header-right">
                <a href="pages/login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login</a>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="hero">
        <div class="hero-content">
            <h1>Our Services</h1>
            <p>Comprehensive cybersecurity awareness training solutions tailored to your organization's needs</p>
        </div>
    </div>

    <div class="container">
        <!-- Services Grid -->
        <div class="services-section">
            <h2 class="section-title">What We Offer</h2>
            
            <div class="services-grid">
                <!-- Service 1 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Interactive Training Modules</h3>
                    <p>Engaging, bite-sized courses covering all aspects of cybersecurity awareness</p>
                    <ul class="service-features">
                        <li>Phishing Email Recognition</li>
                        <li>Malware & Ransomware Defense</li>
                        <li>Password Security Best Practices</li>
                        <li>Data Protection & Privacy</li>
                        <li>Social Engineering Awareness</li>
                    </ul>
                </div>

                <!-- Service 2 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Simulated Phishing Campaigns</h3>
                    <p>Safe, realistic phishing simulations to test and reinforce employee awareness</p>
                    <ul class="service-features">
                        <li>Customizable Email Templates</li>
                        <li>Multi-Wave Campaign Support</li>
                        <li>Real-time Reporting</li>
                        <li>Auto-enrollment Features</li>
                        <li>Detailed Analytics & Metrics</li>
                    </ul>
                </div>

                <!-- Service 3 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Advanced Analytics & Reporting</h3>
                    <p>Comprehensive insights into your organization's security posture and awareness levels</p>
                    <ul class="service-features">
                        <li>Department-level Performance</li>
                        <li>Individual User Dashboards</li>
                        <li>Risk Heat Maps</li>
                        <li>Compliance Reports</li>
                        <li>Exportable Data & Insights</li>
                    </ul>
                </div>

                <!-- Service 4 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3>Adaptive Learning Engine</h3>
                    <p>Personalized training paths that adjust based on individual performance and needs</p>
                    <ul class="service-features">
                        <li>Performance-Based Progression</li>
                        <li>Personalized Recommendations</li>
                        <li>Difficulty Adaptation</li>
                        <li>Continuous Improvement</li>
                        <li>Progress Tracking</li>
                    </ul>
                </div>

                <!-- Service 5 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Incident Reporting System</h3>
                    <p>Streamlined platform for employees to report suspicious activity and security incidents</p>
                    <ul class="service-features">
                        <li>Easy Report Submission</li>
                        <li>Secure Communication Channel</li>
                        <li>Incident Tracking</li>
                        <li>Admin Dashboard</li>
                        <li>Response Management</li>
                    </ul>
                </div>

                <!-- Service 6 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h3>Administration & Management</h3>
                    <p>Powerful tools for managing users, campaigns, and compliance across your organization</p>
                    <ul class="service-features">
                        <li>Bulk User Management</li>
                        <li>Role-Based Access Control</li>
                        <li>Campaign Scheduling</li>
                        <li>Department Management</li>
                        <li>API Integration Support</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Features Overview -->
        <div class="features-overview">
            <h2>Platform Features That Set Us Apart</h2>
            
            <div class="features-list">
                <div class="feature-item">
                    <h3><i class="fas fa-lock"></i> 100% Safe & Secure</h3>
                    <p>All simulations run in a controlled environment. No real data is collected, and employees remain completely safe during training.</p>
                </div>

                <div class="feature-item">
                    <h3><i class="fas fa-chart-bar"></i> Real-Time Insights</h3>
                    <p>Track engagement, performance, and compliance metrics in real-time with intuitive dashboards and detailed reports.</p>
                </div>

                <div class="feature-item">
                    <h3><i class="fas fa-cogs"></i> Easy Integration</h3>
                    <p>Seamlessly integrate with your existing systems via our REST API and user provisioning features.</p>
                </div>

                <div class="feature-item">
                    <h3><i class="fas fa-check-double"></i> Compliance Ready</h3>
                    <p>Meet ISO 27001, GDPR, NIST, and other regulatory requirements with built-in compliance features.</p>
                </div>

                <div class="feature-item">
                    <h3><i class="fas fa-headset"></i> Expert Support</h3>
                    <p>24/7 customer support from cybersecurity experts to help you maximize your security awareness program.</p>
                </div>

                <div class="feature-item">
                    <h3><i class="fas fa-globe"></i> Scalable Platform</h3>
                    <p>From small teams to enterprises, CyberAware scales with your organization's growth and needs.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="container">
        <div class="cta-section">
            <h2>Ready to Strengthen Your Security Awareness?</h2>
            <p>Join organizations across Namibia and beyond in building a resilient, security-conscious workforce. Get started with CyberAware today.</p>
            <a href="contact.php" class="cta-btn">Request a Demo</a>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

</body>
</html>
