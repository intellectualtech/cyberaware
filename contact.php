<?php
// contact.php - Contact & Request Demo Page for CyberAware

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

// Handle form submission
$success_message = '';
$error_message = '';
$name = $email = $company = $phone = $message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($company) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // In a real implementation, send email or save to DB
        // Here: just show success
        $success_message = 'Thank you for your request! We will contact you shortly.';
        
        // Clear form fields after success
        $name = $email = $company = $phone = $message = '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us & Request Demo – CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --cyber-yellow: #FF8C42;
            --primary: #FF8C42;
            --primary-rgb: 255,140,66;
            --cyber-gold: #FF8C42;
            --white: #FFFFFF;
            --cyber-light: #F3F4F6;
            --dark-navy: #111827;
            --dark-slate: #374151;
            --dark: #1f2937;
            --charcoal: #6B7280;
            --shield-green: #FF8C42;
            --alert-red: #EF4444;
            --info-blue: #3B82F6;
            --platinum: #E5E4E2;
            --grey-50: #F3F4F6;
            --grey-100: #E5E7EB;
            --grey-200: #D1D5DB;
            --grey-300: #C8CBD1;
            --grey-400: #B8B8B8;
            --grey-500: #9E9E9E;
            --grey-600: #475569;
            --grey-700: #374151;
            --grey-800: #1e293b;
            --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05);
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
    /* Orange + White + Platinum Grey refresh */
    body {
        font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: radial-gradient(1000px 520px at 12% -10%, #fff1e4 0%, transparent 60%),
            linear-gradient(135deg, #ffffff 0%, var(--platinum) 100%);
        color: var(--dark-navy);
    }
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
            text-shadow: 0 0 15px rgba(var(--primary-rgb),0.4);
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

        /* Contact Content */
        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: start;
            margin-bottom: 80px;
        }

        .contact-info {
            background: var(--white);
            padding: 50px;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
        }

        .contact-info h2 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 30px;
            color: var(--dark);
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 40px;
        }

        .contact-item i {
            font-size: 28px;
            color: var(--primary);
            margin-top: 4px;
        }

        .contact-item div h3 {
            font-size: 20px;
            margin-bottom: 8px;
            color: var(--dark);
        }

        .contact-item div p {
            color: var(--gray-700);
            line-height: 1.6;
        }

        .contact-item div a {
            color: var(--primary);
            text-decoration: none;
        }

        .contact-item div a:hover {
            text-decoration: underline;
        }

        .demo-form {
            background: var(--white);
            padding: 50px;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
        }

        .demo-form h2 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 30px;
            color: var(--dark);
            text-align: center;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--gray-700);
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-message {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            text-align: center;
            font-weight: 500;
        }

        .form-success {
            background: #E8F5E9;
            color: var(--success);
            border: 1px solid var(--success);
        }

        .form-error {
            background: #FFEBEE;
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }

        .submit-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(var(--primary-rgb),0.3);
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

            .contact-content {
                grid-template-columns: 1fr;
                gap: 40px;
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

            .contact-info,
            .demo-form {
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
                <a href="compliance.php">Compliance</a>
                <a href="privacy.php">Privacy</a>
                <a href="contact.php" class="active">Contact</a>
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
        <h1>Contact Us</h1>
        <p>Get in touch or request a free demo of CyberAware</p>
    </div>

    <div class="container">
        <div class="contact-content">
            <!-- Contact Info -->
            <div class="contact-info">
                <h2>Get in Touch</h2>
                
                <div class="contact-item">
                    <i class="fas fa-building"></i>
                    <div>
                        <h3>Intellectual Technology</h3>
                        <p>Windhoek, Namibia<br>
                        Leading provider of innovative technology solutions</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h3>Email</h3>
                        <p><a href="mailto:info@intellectualtechnology.com.na">info@intellectualtechnology.com.na</a></p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-globe"></i>
                    <div>
                        <h3>Website</h3>
                        <p><a href="https://intellectualtechnology.com.na" target="_blank">intellectualtechnology.com.na</a></p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-shield-alt"></i>
                    <div>
                        <h3>CyberAware Demo</h3>
                        <p>Fill out the form to request a personalized demo</p>
                    </div>
                </div>
            </div>

            <!-- Demo Request Form -->
            <div class="demo-form">
                <h2>Request a Free Demo</h2>

                <?php if ($success_message): ?>
                <div class="form-message form-success">
                    <?= htmlspecialchars($success_message) ?>
                </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                <div class="form-message form-error">
                    <?= htmlspecialchars($error_message) ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="name">Your Name *</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="company">Company / Organization *</label>
                        <input type="text" id="company" name="company" value="<?= htmlspecialchars($company) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number (optional)</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>">
                    </div>

                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" required><?= htmlspecialchars($message) ?></textarea>
                    </div>

                    <button type="submit" class="submit-btn">
                        Submit Request
                    </button>
                </form>
            </div>
        </div>

        <div class="cta-section">
            <h2>Ready to Get Started?</h2>
            <p>Contact us today for a personalized demonstration of CyberAware</p>
            <a href="pages/login.php" class="cta-btn">
                Login to Platform
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