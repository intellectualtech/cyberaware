<?php
require_once '../config/database.php';

// Redirect if already logged in - send to appropriate dashboard
// But don't redirect if there's an access_denied error (to avoid redirect loops)
if (isLoggedIn() && !isset($_GET['error'])) {
    if (hasRole('admin') || hasRole('manager') || hasRole('superadmin')) {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../trainee/dashboard.php');
    }
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberShield - Security Awareness Training Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        :root {
            --primary: #FF8C42;
            --primary-rgb: 255,140,66;
            --platinum: #E5E4E2;
            --ink: #1f2937;
            --muted: #6b7280;
            --white: #ffffff;
            --shadow-sm: 0 10px 24px rgba(15, 23, 42, 0.08);
            --shadow-md: 0 18px 40px rgba(15, 23, 42, 0.12);
        }

        body {
            font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: radial-gradient(1000px 520px at 15% -10%, #fff1e4 0%, transparent 60%),
                linear-gradient(135deg, #ffffff 0%, var(--platinum) 100%);
            color: var(--ink);
            margin: 0;
        }

        h1, h2, h3 {
            font-family: 'Space Grotesk', 'Segoe UI', sans-serif;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            border-bottom: 1px solid rgba(255, 140, 66, 0.2);
            box-shadow: var(--shadow-sm);
        }

        .hero {
            background: linear-gradient(135deg, #ff8c42 0%, #ffd2b3 100%);
            color: var(--ink);
            padding: 100px 20px;
            text-align: center;
            border-radius: 24px;
            margin-bottom: 50px;
            box-shadow: 0 18px 40px rgba(255, 140, 66, 0.22);
        }

        .hero h1 {
            font-size: 56px;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 22px;
            margin-bottom: 30px;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero-btn {
            padding: 15px 40px;
            font-size: 18px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
        }

        .hero-btn-primary {
            background: var(--white);
            color: var(--ink);
        }

        .hero-btn-secondary {
            background: transparent;
            color: var(--ink);
            border: 2px solid rgba(255, 255, 255, 0.9);
        }

        .purpose-statement {
            background: var(--white);
            padding: 40px;
            border-radius: 18px;
            margin: 50px 0;
            box-shadow: var(--shadow-sm);
            text-align: center;
        }

        .purpose-statement h2 {
            color: var(--primary);
            margin-bottom: 20px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 50px 0;
        }

        .feature-card {
            background: var(--white);
            padding: 40px;
            border-radius: 18px;
            border: 1px solid rgba(255, 140, 66, 0.12);
            box-shadow: var(--shadow-sm);
            transition: transform 0.3s;
        }

        .feature-card h3 {
            color: var(--primary);
        }

        .stats-section {
            background: linear-gradient(135deg, #ffffff 0%, #fff1e4 100%);
            color: var(--ink);
            padding: 60px 40px;
            border-radius: 18px;
            margin: 50px 0;
            border: 1px solid rgba(255, 140, 66, 0.2);
        }

        .stats-section h2 {
            text-align: center;
            font-size: 36px;
            margin-bottom: 40px;
        }

        .stats-grid-home {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
        }

        @media (max-width: 768px) {
            .hero h1 { font-size: 36px; }
            .hero p { font-size: 18px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">🛡️ CyberShield</div>
            <nav class="nav-menu">
                <a href="#features">Features</a>
                <a href="#about">About</a>
                <a href="compliance.php">Compliance</a>
                <a href="contact.php">Contact</a>
            </nav>
            <div>
                <a href="login.php" class="btn btn-primary">Login</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="hero">
            <div style="font-size: 80px; margin-bottom: 20px;">🛡️</div>
            <h1>CyberShield</h1>
            <p>Empower your team to recognize and defend against cyber threats</p>
            <div class="hero-buttons">
                <a href="login.php" class="hero-btn hero-btn-primary">Get Started</a>
                <a href="#features" class="hero-btn hero-btn-secondary">Learn More</a>
            </div>
        </div>
        
        <div class="purpose-statement">
            <h2>🎯 Our Mission</h2>
            <p>
                <strong>This platform is designed to simulate common cyberattack scenarios for employee awareness and training,
                helping users recognize, avoid, and report threats.</strong>
            </p>
            <p style="margin-top: 20px; font-size: 16px; color: #666;">
                Compliant with ISO 27001 & NIST security awareness frameworks | Training-Only Use
            </p>
        </div>
        
        <h2 style="text-align: center; font-size: 36px; margin: 60px 0 40px 0;" id="features">
            Why Choose CyberShield?
        </h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3>Realistic Simulations</h3>
                <p>Safe, controlled environment to practice identifying phishing emails, social engineering, and other threats without real-world risks.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Measurable Results</h3>
                <p>Track click rates, report rates, and user improvement with detailed metrics and compliance-ready reports.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🔐</div>
                <h3>Complete Privacy</h3>
                <p>No real credential capture, no external emails sent. Everything stays within your organization's controlled environment.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Immediate Feedback</h3>
                <p>Users learn from every decision with instant, detailed explanations of what they did right or wrong.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h3>Role-Based Access</h3>
                <p>Separate interfaces for trainees and administrators. Managers get powerful dashboards to track team progress.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">✅</div>
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
                    <div class="stat-number">15min</div>
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
                    <div class="module-icon">📧</div>
                    <h3>Phishing Email Recognition</h3>
                    <p>Learn to identify suspicious emails and avoid phishing attacks with realistic simulations.</p>
                </div>
                
                <div class="module-card">
                    <div class="module-icon">🔐</div>
                    <h3>Credential Harvesting Awareness</h3>
                    <p>Recognize fake login pages and protect your credentials from theft.</p>
                </div>
                
                <div class="module-card">
                    <div class="module-icon">📞</div>
                    <h3>Social Engineering Defense</h3>
                    <p>Identify manipulation tactics and respond appropriately to suspicious requests.</p>
                </div>
                
                <div class="module-card">
                    <div class="module-icon">🦠</div>
                    <h3>Malware & Attachment Safety</h3>
                    <p>Spot dangerous attachments before they infect your system.</p>
                </div>
                
                <div class="module-card">
                    <div class="module-icon">🔗</div>
                    <h3>Website & Link Safety</h3>
                    <p>Verify URLs and detect malicious websites before clicking.</p>
                </div>
            </div>
        </div>
        
        <div class="purpose-statement" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <h2 style="color: white;">Ready to Strengthen Your Security?</h2>
            <p style="color: white; margin-bottom: 30px;">Join organizations worldwide using CyberShield to protect their teams</p>
            <a href="login.php" class="hero-btn hero-btn-primary">Get Started Now</a>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>