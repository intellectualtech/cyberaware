<?php
require_once '../config/database.php';


// Redirect if already logged in

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberShield - Security Awareness Training Platform</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 20px;
            text-align: center;
            border-radius: 15px;
            margin-bottom: 50px;
        }
        
        .hero h1 {
            font-size: 56px;
            margin-bottom: 20px;
            color: white;
        }
        
        .hero p {
            font-size: 24px;
            margin-bottom: 30px;
            opacity: 0.95;
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
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .hero-btn-primary {
            background: white;
            color: #667eea;
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
            color: #667eea;
            transform: translateY(-3px);
        }
        
        .purpose-statement {
            background: white;
            padding: 40px;
            border-radius: 15px;
            margin: 50px 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .purpose-statement h2 {
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .purpose-statement p {
            font-size: 20px;
            line-height: 1.8;
            color: #333;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 50px 0;
        }
        
        .feature-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .feature-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.6;
        }
        
        .stats-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 40px;
            border-radius: 15px;
            margin: 50px 0;
        }
        
        .stats-section h2 {
            text-align: center;
            font-size: 36px;
            margin-bottom: 40px;
            color: white;
        }
        
        .stats-grid-home {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 16px;
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 36px;
            }
            
            .hero p {
                font-size: 18px;
            }
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