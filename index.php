<?php
require_once 'config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (hasRole('admin') || hasRole('manager')) {
        header('Location: admin/dashboard.php');
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
    <title>CyberAware - Security Awareness Training Platform</title>
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

        .nav-menu a:hover {
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-danger {
            background: var(--danger);
            color: white;
        }

        .badge-warning {
            background: var(--warning);
            color: white;
        }

        .badge-info {
            background: #3498db;
            color: white;
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

        .btn-secondary {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }

        .btn-large {
            padding: 16px 40px;
            font-size: 16px;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 100px 32px;
            text-align: center;
            border-radius: 16px;
            margin: 40px 32px;
            box-shadow: var(--shadow-lg);
        }

        .hero-icon {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }

        .hero-icon i {
            font-size: 50px;
            color: white;
        }

        .hero h1 {
            font-size: 56px;
            margin-bottom: 20px;
            color: white;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .hero p {
            font-size: 24px;
            margin-bottom: 40px;
            opacity: 0.95;
            font-weight: 400;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero-btn {
            padding: 18px 48px;
            font-size: 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .hero-btn-primary {
            background: white;
            color: var(--primary);
        }

        .hero-btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.3);
        }

        .hero-btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .hero-btn-secondary:hover {
            background: white;
            color: var(--primary);
            transform: translateY(-3px);
        }

        /* Purpose Statement */
        .purpose-statement {
            background: var(--white);
            padding: 60px 40px;
            border-radius: 16px;
            margin: 60px 0;
            box-shadow: var(--shadow-md);
            text-align: center;
        }

        .purpose-statement h2 {
            color: var(--primary);
            font-size: 36px;
            margin-bottom: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .purpose-statement h2 i {
            font-size: 40px;
        }

        .purpose-statement p {
            font-size: 20px;
            line-height: 1.8;
            color: var(--gray-700);
            max-width: 900px;
            margin: 0 auto;
        }

        .purpose-statement .subtitle {
            margin-top: 20px;
            font-size: 16px;
            color: var(--gray-600);
            font-weight: 500;
        }

        /* Section Title */
        .section-title {
            text-align: center;
            font-size: 42px;
            margin: 80px 0 50px 0;
            font-weight: 700;
            color: var(--dark);
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            margin: 50px 0;
        }

        .feature-card {
            background: var(--white);
            padding: 40px;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            transition: all 0.3s;
            border: 1px solid var(--gray-200);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-lighter);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: var(--primary-lighter);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }

        .feature-icon i {
            font-size: 36px;
            color: var(--primary);
        }

        .feature-card h3 {
            color: var(--dark);
            margin-bottom: 16px;
            font-size: 22px;
            font-weight: 600;
        }

        .feature-card p {
            color: var(--gray-600);
            line-height: 1.7;
            font-size: 15px;
        }

        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 80px 40px;
            border-radius: 16px;
            margin: 80px 0;
            box-shadow: var(--shadow-lg);
        }

        .stats-section h2 {
            text-align: center;
            font-size: 42px;
            margin-bottom: 50px;
            color: white;
            font-weight: 700;
        }

        .stats-grid-home {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 40px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 56px;
            font-weight: 800;
            margin-bottom: 10px;
            color: white;
        }

        .stat-label {
            font-size: 18px;
            opacity: 0.95;
            font-weight: 500;
        }

        /* Modules Grid */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 30px;
        }

        .module-card {
            background: var(--gray-50);
            padding: 32px;
            border-radius: 12px;
            border: 1px solid var(--gray-200);
            transition: all 0.3s;
        }

        .module-card:hover {
            background: var(--white);
            box-shadow: var(--shadow-md);
            transform: translateY(-5px);
        }

        .module-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-lighter);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .module-icon i {
            font-size: 30px;
            color: var(--primary);
        }

        .module-card h3 {
            color: var(--dark);
            margin-bottom: 12px;
            font-size: 19px;
            font-weight: 600;
        }

        .module-card p {
            color: var(--gray-600);
            line-height: 1.6;
            font-size: 14px;
        }

        /* Card */
        .card {
            background: var(--white);
            padding: 60px 40px;
            border-radius: 16px;
            margin: 60px 0;
            box-shadow: var(--shadow-md);
        }

        .card h2 {
            font-size: 36px;
            margin-bottom: 40px;
            color: var(--dark);
            font-weight: 700;
            text-align: center;
        }

        /* CTA disappearing */
        .cta-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 80px 40px;
            border-radius: 16px;
            margin: 80px 0;
            text-align: center;
            box-shadow: var(--shadow-lg);
        }

        .cta-section h2 {
            font-size: 42px;
            color: white;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .cta-section p {
            font-size: 20px;
            color: white;
            margin-bottom: 40px;
            opacity: 0.95;
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

            .user-info {
                gap: 12px;
                font-size: 14px;
            }

            .user-info .btn {
                padding: 8px 16px;
                font-size: 13px;
            }

            .hero h1 {
                font-size: 42px;
            }

            .hero p {
                font-size: 20px;
            }

            .section-title {
                font-size: 32px;
            }

            .features-grid,
            .modules-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .hero {
                padding: 60px 24px;
                margin: 20px 16px;
            }

            .hero h1 {
                font-size: 32px;
            }

            .hero p {
                font-size: 18px;
            }

            .hero-btn {
                padding: 14px 32px;
                font-size: 16px;
            }

            .container {
                padding: 0 16px;
            }

            .purpose-statement,
            .card,
            .stats-section,
            .cta-section {
                padding: 40px 24px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo (hasRole('admin') || hasRole('manager')) ? 'admin_dashboard.php' : 'trainee_dashboard.php'; ?>" class="logo">
                    <i class="fas fa-shield-alt"></i> CyberAware
                </a>
            <?php else: ?>
                <a href="index.php" class="logo">
                    <i class="fas fa-shield-alt"></i> CyberAware
                </a>
            <?php endif; ?>
            
            <nav class="nav-menu">
                <?php if (isLoggedIn()): ?>
                    <?php if (hasRole('admin') || hasRole('manager')): ?>
                        <a href="admin_dashboard.php">Dashboard</a>
                        <a href="create_campaign.php">Campaigns</a>
                        <a href="manage_users.php">Users</a>
                        <a href="phishing_templates.php">Templates</a>
                        <a href="reports.php">Reports</a>
                    <?php else: ?>
                        <a href="trainee_dashboard.php">Dashboard</a>
                        <a href="my_progress.php">My Progress</a>
                        <a href="modules.php">Training Modules</a>
                    <?php endif; ?>
                    <a href="compliance.php">Compliance</a>
                    <a href="help.php">Help</a>
                <?php else: ?>
                    <a href="#features">Features</a>
                    <a href="about.php">About</a>
                    <a href="compliance.php">Compliance</a>
                    <a href="contact.php">Contact</a>
                <?php endif; ?>
            </nav>

            <div class="header-right">
                <button class="menu-toggle" id="menu-toggle" aria-label="Toggle navigation menu">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="user-info">
                    <?php if (isLoggedIn()): ?>
                        <span>👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <span class="badge <?php 
                            echo hasRole('admin') ? 'badge-danger' : 
                                (hasRole('manager') ? 'badge-warning' : 'badge-info'); 
                        ?>">
                            <?php echo strtoupper($_SESSION['role']); ?>
                        </span>
                        <a href="logout.php" class="btn btn-secondary">Logout</a>
                    <?php else: ?>
                        <a href="pages/login.php" class="btn btn-primary">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="hero">
        <div class="hero-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        <h1>CyberAware</h1>
        <p>Empower your team to recognize and defend against cyber threats</p>
        <div class="hero-buttons">
            <a href="login.php" class="hero-btn hero-btn-primary">
                <i class="fas fa-rocket"></i>
                Get Started
            </a>
            <a href="#features" class="hero-btn hero-btn-secondary">
                <i class="fas fa-arrow-down"></i>
                Learn More
            </a>
        </div>
    </div>
    
    <div class="container">
        <div class="purpose-statement">
            <h2>
                <i class="fas fa-bullseye"></i>
                Our Mission
            </h2>
            <p>
                <strong>This platform is designed to simulate cyberattack scenarios for employee awareness and training,
                helping users recognize, avoid, and report threats.</strong>
            </p>
            <p class="subtitle">
                <i class="fas fa-check-circle"></i> Compliant with ISO 27001 & NIST security awareness frameworks | Training-Only Use
            </p>
        </div>
        
        <h2 class="section-title" id="features">
            Why Choose CyberAware?
        </h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-crosshairs"></i>
                </div>
                <h3>Realistic Simulations</h3>
                <p>Safe, controlled environment to practice identifying phishing emails, social engineering, and other threats without real-world risks.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Measurable Results</h3>
                <p>Track click rates, report rates, and user improvement with detailed metrics and compliance-ready reports.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h3>Complete Privacy</h3>
                <p>No real credential capture, no external emails sent. Everything stays within your organization's controlled environment.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3>Immediate Feedback</h3>
                <p>Users learn from every decision with instant, detailed explanations of what they did right or wrong.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h3>Role-Based Access</h3>
                <p>Separate interfaces for trainees and administrators. Managers get powerful dashboards to track team progress.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <h3>Compliance Ready</h3>
                <p>Aligned with ISO 27001 and NIST frameworks. Generate audit-ready reports for regulatory compliance.</p>
            </div>
        </div>
        
        <div class="stats-section">
            <h2>Platform Impact</h2>
            <div class="stats-grid-home">
                <div class="stat-item">
                    <div class="stat-number">5</div>
                    <div class="stat-label">Training Modules</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">2h15min</div>
                    <div class="stat-label">Avg Completion Time</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">85%</div>
                    <div class="stat-label">Threat Awareness Increase</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">72%</div>
                    <div class="stat-label">Incident Reduction</div>
                </div>
            </div>
        </div>
        
        <div class="card" id="about">
            <h2>Training Modules</h2>
            <div class="modules-grid">
                <div class="module-card">
                    <div class="module-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Phishing Email Recognition</h3>
                    <p>Learn to identify suspicious emails and avoid phishing attacks with realistic simulations.</p>
                </div>
                
                <div class="module-card">
                    <div class="module-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <h3>Credential Harvesting Awareness</h3>
                    <p>Recognize fake login pages and protect your credentials from theft.</p>
                </div>
                
                <div class="module-card">
                    <div class="module-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <h3>Social Engineering Defense</h3>
                    <p>Identify manipulation tactics and respond appropriately to suspicious requests.</p>
                </div>
                
                <div class="module-card">
                    <div class="module-icon">
                        <i class="fas fa-virus"></i>
                    </div>
                    <h3>Malware & Attachment Safety</h3>
                    <p>Spot dangerous attachments before they infect your system.</p>
                </div>
                
                <div class="module-card">
                    <div class="module-icon">
                        <i class="fas fa-link"></i>
                    </div>
                    <h3>Website & Link Safety</h3>
                    <p>Verify URLs and detect malicious websites before clicking.</p>
                </div>
            </div>
        </div>
        
        <div class="cta-section">
            <h2>Ready to Strengthen Your Security?</h2>
            <p>Join organizations worldwide using CyberAware to protect their teams</p>
            <a href="login.php" class="hero-btn hero-btn-primary">
                <i class="fas fa-arrow-right"></i>
                Get Started Now
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