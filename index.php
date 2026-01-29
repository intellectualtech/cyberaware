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
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            /* Primary Brand Colors */
            --cyber-yellow: #FFD60A;
            --cyber-gold: #FFC300;
            --cyber-light: #FFF8DC;
            --white: #FFFFFF;
            
            /* Security Dark Tones */
            --dark-navy: #0F1419;
            --dark-slate: #1A1E2E;
            --charcoal: #2D3142;
            
            /* Accent Colors */
            --shield-green: #10B981;
            --alert-red: #EF4444;
            --info-blue: #3B82F6;
            
            /* Shadows & Effects */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.2);
            --shadow-yellow: 0 0 20px rgba(255, 214, 10, 0.3);
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
            text-shadow: 0 0 10px rgba(255, 214, 10, 0.5);
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
            background: rgba(255, 214, 10, 0.1);
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

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 28px;
            color: var(--white);
            cursor: pointer;
            padding: 8px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--white);
        }

        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.2);
            color: var(--alert-red);
            border: 1px solid var(--alert-red);
        }

        .badge-warning {
            background: rgba(255, 214, 10, 0.2);
            color: var(--cyber-gold);
            border: 1px solid var(--cyber-gold);
        }

        .badge-info {
            background: rgba(59, 130, 246, 0.2);
            color: var(--info-blue);
            border: 1px solid var(--info-blue);
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
            box-shadow: 0 8px 20px rgba(255, 214, 10, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: var(--cyber-yellow);
            border: 2px solid var(--cyber-yellow);
        }

        .btn-secondary:hover {
            background: var(--cyber-yellow);
            color: var(--dark-navy);
            transform: translateY(-3px);
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--dark-slate) 100%);
            color: var(--white);
            padding: 120px 32px;
            text-align: center;
            border-radius: 0;
            margin: 0;
            box-shadow: var(--shadow-lg), var(--shadow-yellow);
            position: relative;
            overflow: hidden;
            border-left: 5px solid var(--cyber-yellow);
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            right: -100px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 214, 10, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: -50px;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(255, 214, 10, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-icon {
            width: 120px;
            height: 120px;
            background: rgba(255, 214, 10, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            position: relative;
            z-index: 1;
            border: 3px solid var(--cyber-yellow);
        }

        .hero-icon i {
            font-size: 60px;
            color: var(--cyber-yellow);
        }

        .hero h1 {
            font-size: 64px;
            margin-bottom: 20px;
            color: var(--white);
            font-weight: 800;
            letter-spacing: -1px;
            position: relative;
            z-index: 1;
        }

        .hero p {
            font-size: 24px;
            margin-bottom: 50px;
            opacity: 0.95;
            font-weight: 400;
            position: relative;
            z-index: 1;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }

        .hero-btn {
            padding: 18px 48px;
            font-size: 18px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.5px;
        }

        .hero-btn-primary {
            background: var(--cyber-yellow);
            color: var(--dark-navy);
            border: 2px solid var(--cyber-yellow);
        }

        .hero-btn-primary:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(255, 214, 10, 0.4);
        }

        .hero-btn-secondary {
            background: transparent;
            color: var(--white);
            border: 2px solid var(--white);
        }

        .hero-btn-secondary:hover {
            background: var(--white);
            color: var(--dark-navy);
            transform: translateY(-5px);
        }

        /* Purpose Statement */
        .purpose-section {
            padding: 100px 50px;
            margin: 80px 20px;
            border-radius: 16px;
        }

        .mission-vision-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 40px;
            margin-bottom: 60px;
        }

        .mission-card {
            background: var(--white);
            padding: 50px;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            border-left: 5px solid var(--cyber-yellow);
            position: relative;
            overflow: hidden;
        }

        .mission-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(255, 214, 10, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .mission-card h3 {
            font-size: 28px;
            color: var(--dark-navy);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .mission-card h3 i {
            font-size: 32px;
            color: var(--cyber-yellow);
        }

        .mission-card p {
            font-size: 16px;
            line-height: 1.8;
            color: var(--charcoal);
            position: relative;
            z-index: 1;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
            margin-top: 50px;
        }

        .value-badge {
            background: linear-gradient(135deg, rgba(255, 214, 10, 0.1) 0%, rgba(255, 195, 0, 0.05) 100%);
            padding: 28px 24px;
            border-radius: 12px;
            text-align: center;
            border: 2px solid rgba(255, 214, 10, 0.2);
            transition: all 0.3s ease;
        }

        .value-badge:hover {
            transform: translateY(-8px);
            border-color: var(--cyber-yellow);
            box-shadow: 0 12px 30px rgba(255, 214, 10, 0.15);
        }

        .value-badge i {
            font-size: 36px;
            color: var(--cyber-yellow);
            margin-bottom: 14px;
            display: block;
        }

        .value-badge h4 {
            font-size: 18px;
            color: var(--dark-navy);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .value-badge p {
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Organization Stats Section */
        .org-stats-section {
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--dark-slate) 100%);
            color: var(--white);
            padding: 100px 50px;
            border-radius: 16px;
            margin: 80px 20px;
            box-shadow: var(--shadow-lg), var(--shadow-yellow);
            border-left: 5px solid var(--cyber-yellow);
            position: relative;
            overflow: hidden;
        }

        .org-stats-section h2 {
            text-align: center;
            font-size: 48px;
            margin-bottom: 60px;
            color: var(--white);
            font-weight: 800;
            position: relative;
            z-index: 1;
        }

        .org-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 50px;
            position: relative;
            z-index: 1;
        }

        .org-stat-item {
            text-align: center;
            padding: 30px;
            background: rgba(255, 214, 10, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(255, 214, 10, 0.15);
            transition: all 0.3s ease;
        }

        .org-stat-item:hover {
            background: rgba(255, 214, 10, 0.1);
            transform: translateY(-8px);
            border-color: var(--cyber-yellow);
        }

        .org-stat-number {
            font-size: 56px;
            font-weight: 800;
            margin-bottom: 12px;
            color: var(--cyber-yellow);
            text-shadow: 0 4px 10px rgba(255, 214, 10, 0.3);
        }

        .org-stat-label {
            font-size: 16px;
            opacity: 0.95;
            font-weight: 600;
        }

        .org-stat-description {
            font-size: 12px;
            opacity: 0.7;
            margin-top: 10px;
            font-weight: 400;
        }

        /* Purpose Statement */
        .purpose-statement {
            background: var(--white);
            padding: 70px 50px;
            border-radius: 16px;
            margin: 60px 20px;
            box-shadow: var(--shadow-lg);
            text-align: center;
            border-left: 5px solid var(--cyber-yellow);
            position: relative;
        }

        .purpose-statement::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255, 214, 10, 0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .purpose-statement h2 {
            color: var(--dark-navy);
            font-size: 40px;
            margin-bottom: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .purpose-statement h2 i {
            font-size: 44px;
            color: var(--cyber-yellow);
        }

        .purpose-statement p {
            font-size: 20px;
            line-height: 1.8;
            color: var(--charcoal);
            max-width: 900px;
            margin: 0 auto;
        }

        .purpose-statement .subtitle {
            margin-top: 25px;
            font-size: 16px;
            color: var(--dark-navy);
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .purpose-statement .subtitle i {
            color: var(--shield-green);
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--cyber-yellow) 0%, var(--cyber-gold) 100%);
            color: var(--dark-navy);
            padding: 80px 50px;
            border-radius: 16px;
            margin: 80px 20px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .cta-section h2 {
            font-size: 48px;
            margin-bottom: 20px;
            font-weight: 800;
            position: relative;
            z-index: 1;
        }

        .cta-section p {
            font-size: 18px;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
            opacity: 0.95;
            font-weight: 500;
        }

        .cta-btn {
            background: var(--dark-navy);
            color: var(--cyber-yellow);
            padding: 16px 48px;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
            border: 2px solid var(--dark-navy);
            font-size: 16px;
            letter-spacing: 0.5px;
        }

        .cta-btn:hover {
            background: var(--white);
            color: var(--dark-navy);
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        /* Achievement Timeline */
        .achievements-section {
            padding: 100px 50px;
            margin: 80px 20px;
        }

        .achievements-section h2 {
            text-align: center;
            font-size: 48px;
            margin-bottom: 60px;
            color: var(--dark-navy);
            font-weight: 800;
        }

        .timeline {
            position: relative;
            max-width: 900px;
            margin: 0 auto;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--cyber-yellow), transparent);
        }

        .timeline-item {
            margin-bottom: 50px;
            position: relative;
        }

        .timeline-item:nth-child(odd) .timeline-content {
            margin-left: 0;
            margin-right: auto;
            width: calc(50% - 30px);
            text-align: right;
        }

        .timeline-item:nth-child(even) .timeline-content {
            margin-left: auto;
            margin-right: 0;
            width: calc(50% - 30px);
            text-align: left;
        }

        .timeline-dot {
            position: absolute;
            left: 50%;
            top: 0;
            transform: translateX(-50%);
            width: 20px;
            height: 20px;
            background: var(--cyber-yellow);
            border-radius: 50%;
            border: 4px solid var(--white);
            box-shadow: 0 0 0 4px var(--cyber-yellow);
            z-index: 10;
        }

        .timeline-content {
            background: var(--white);
            padding: 28px;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            border-left: 4px solid var(--cyber-yellow);
            transition: all 0.3s ease;
        }

        .timeline-content:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .timeline-content h4 {
            font-size: 18px;
            color: var(--dark-navy);
            margin-bottom: 8px;
            font-weight: 700;
        }

        .timeline-content p {
            font-size: 14px;
            color: var(--text-muted);
            margin: 0;
        }

        .timeline-year {
            font-size: 12px;
            color: var(--cyber-yellow);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .section-title {
            text-align: center;
            font-size: 48px;
            margin: 80px 20px 50px 20px;
            font-weight: 800;
            color: var(--dark-navy);
        }

        .section-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: var(--cyber-yellow);
            margin: 20px auto 0;
            border-radius: 2px;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 30px;
            margin: 50px 20px;
        }

        .feature-card {
            background: var(--white);
            padding: 45px;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            border-left: 5px solid var(--cyber-yellow);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255, 214, 10, 0.1) 0%, transparent 70%);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-12px);
            box-shadow: var(--shadow-lg);
            border-color: var(--cyber-gold);
        }

        .feature-card:hover::before {
            top: -25%;
            right: -25%;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(255, 214, 10, 0.2) 0%, rgba(255, 195, 0, 0.1) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
            border: 2px solid var(--cyber-yellow);
        }

        .feature-icon i {
            font-size: 40px;
            color: var(--dark-navy);
        }

        .feature-card h3 {
            color: var(--dark-navy);
            margin-bottom: 18px;
            font-size: 24px;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .feature-card p {
            color: var(--charcoal);
            line-height: 1.8;
            font-size: 16px;
            position: relative;
            z-index: 1;
        }

        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--dark-slate) 100%);
            color: var(--white);
            padding: 100px 50px;
            border-radius: 16px;
            margin: 80px 20px;
            box-shadow: var(--shadow-lg), var(--shadow-yellow);
            border-left: 5px solid var(--cyber-yellow);
            position: relative;
            overflow: hidden;
        }

        .stats-section::before {
            content: '';
            position: absolute;
            top: -100px;
            left: -100px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 214, 10, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .stats-section h2 {
            text-align: center;
            font-size: 48px;
            margin-bottom: 60px;
            color: var(--white);
            font-weight: 800;
            position: relative;
            z-index: 1;
        }

        .stats-grid-home {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 50px;
            position: relative;
            z-index: 1;
        }

        .stat-item {
            text-align: center;
            padding: 30px;
            background: rgba(255, 214, 10, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(255, 214, 10, 0.15);
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            background: rgba(255, 214, 10, 0.1);
            transform: translateY(-8px);
            border-color: var(--cyber-yellow);
        }

        .stat-number {
            font-size: 52px;
            font-weight: 800;
            margin-bottom: 12px;
            color: var(--cyber-yellow);
            text-shadow: 0 4px 10px rgba(255, 214, 10, 0.3);
        }

        .stat-label {
            font-size: 16px;
            opacity: 0.95;
            font-weight: 600;
        }

        /* Card class */
        .card {
            background: var(--white);
            padding: 50px;
            border-radius: 16px;
            margin: 80px 20px;
            box-shadow: var(--shadow-lg);
            border-left: 5px solid var(--cyber-yellow);
            position: relative;
        }

        .card h2 {
            color: var(--dark-navy);
            font-size: 40px;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 800;
        }

        .card h2 i {
            color: var(--cyber-yellow);
            font-size: 44px;
        }

        /* Modules Grid */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 28px;
            margin-top: 40px;
        }

        .module-card {
            background: var(--white);
            padding: 36px;
            border-radius: 16px;
            border: 2px solid transparent;
            border-left: 5px solid var(--cyber-yellow);
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .module-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255, 214, 10, 0.1) 0%, transparent 70%);
            transition: all 0.3s ease;
        }

        .module-card:hover {
            transform: translateY(-12px);
            box-shadow: var(--shadow-lg);
            border-color: var(--cyber-gold);
        }

        .module-card:hover::before {
            top: -25%;
            right: -25%;
        }

        .module-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, rgba(255, 214, 10, 0.2) 0%, rgba(255, 195, 0, 0.1) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 36px;
            color: var(--dark-navy);
            border: 2px solid var(--cyber-yellow);
        }

        .module-card h3 {
            color: var(--dark-navy);
            margin-bottom: 14px;
            font-size: 22px;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .module-card p {
            color: var(--charcoal);
            line-height: 1.7;
            font-size: 15px;
            position: relative;
            z-index: 1;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero {
                padding: 80px 24px;
            }

            .hero h1 {
                font-size: 42px;
            }

            .hero p {
                font-size: 18px;
            }

            .section-title {
                font-size: 36px;
                margin: 60px 20px 40px;
            }

            .features-grid,
            .modules-grid,
            .org-stats-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid-home {
                grid-template-columns: repeat(2, 1fr);
                gap: 30px;
            }

            .mission-vision-grid {
                grid-template-columns: 1fr;
            }

            .values-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .timeline::before {
                display: none;
            }

            .timeline-item:nth-child(odd) .timeline-content,
            .timeline-item:nth-child(even) .timeline-content {
                width: 100%;
                text-align: left;
                margin-left: 0;
                margin-right: 0;
            }

            .timeline-dot {
                display: none;
            }

            .container {
                padding: 0 20px;
            }

            .card {
                padding: 30px;
                margin: 60px 15px;
            }

            .purpose-statement {
                padding: 40px 30px;
                margin: 40px 15px;
            }

            .cta-section {
                padding: 60px 30px;
                margin: 60px 15px;
            }

            .cta-section h2 {
                font-size: 32px;
            }

            .org-stats-section,
            .stats-section {
                padding: 60px 30px;
                margin: 60px 15px;
            }

            .org-stats-section h2,
            .stats-section h2 {
                font-size: 32px;
                margin-bottom: 40px;
            }

            .stat-number,
            .org-stat-number {
                font-size: 40px;
            }

            .stat-label,
            .org-stat-label {
                font-size: 14px;
            }
        }

        @media (max-width: 576px) {
            .nav-menu {
                position: absolute;
                top: 70px;
                left: 0;
                right: 0;
                background: var(--dark-navy);
                flex-direction: column;
                gap: 0;
                display: none;
                border-top: 2px solid var(--cyber-yellow);
                padding: 20px 0;
                border-radius: 0;
            }

            .nav-menu.active {
                display: flex;
            }

            .nav-menu a {
                padding: 15px 32px;
                border-radius: 0;
            }

            .menu-toggle {
                display: block;
            }

            .hero {
                padding: 60px 20px;
            }

            .hero h1 {
                font-size: 32px;
            }

            .hero p {
                font-size: 16px;
            }

            .hero-icon {
                width: 90px;
                height: 90px;
            }

            .hero-icon i {
                font-size: 48px;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .hero-btn {
                width: 100%;
                justify-content: center;
            }

            .stats-grid-home {
                grid-template-columns: 1fr;
            }

            .stat-number,
            .org-stat-number {
                font-size: 36px;
            }

            .values-grid {
                grid-template-columns: 1fr;
            }

            .card h2 {
                font-size: 28px;
            }

            .purpose-statement h2 {
                font-size: 28px;
            }

            .cta-section h2 {
                font-size: 28px;
            }

            .feature-icon {
                width: 60px;
                height: 60px;
            }

            .feature-icon i {
                font-size: 32px;
            }
        }
            background: var(--white);
            box-shadow: var(--shadow-lg);
            transform: translateY(-10px);
            border-color: var(--cyber-gold);
        }

        .module-card:hover::before {
            top: -25%;
            right: -25%;
        }

        .module-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, rgba(255, 214, 10, 0.2) 0%, rgba(255, 195, 0, 0.1) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            position: relative;
            z-index: 1;
            border: 2px solid var(--cyber-yellow);
        }

        .module-icon i {
            font-size: 36px;
            color: var(--dark-navy);
        }

        .module-card h3 {
            color: var(--dark-navy);
            margin-bottom: 16px;
            font-size: 22px;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .module-card p {
            color: var(--charcoal);
            line-height: 1.8;
            font-size: 16px;
            position: relative;
            z-index: 1;
        }

        /* Card */
        .card {
            background: var(--white);
            padding: 70px 50px;
            border-radius: 16px;
            margin: 60px 20px;
            box-shadow: var(--shadow-md);
            border-left: 5px solid var(--cyber-yellow);
        }

        .card h2 {
            font-size: 42px;
            margin-bottom: 50px;
            color: var(--dark-navy);
            font-weight: 800;
            text-align: center;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--dark-navy) 0%, var(--dark-slate) 100%);
            color: var(--white);
            padding: 100px 50px;
            border-radius: 16px;
            margin: 80px 20px;
            text-align: center;
            box-shadow: var(--shadow-lg), var(--shadow-yellow);
            border-left: 5px solid var(--cyber-yellow);
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            bottom: -100px;
            right: -100px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 214, 10, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .cta-section h2 {
            font-size: 48px;
            color: var(--white);
            margin-bottom: 20px;
            font-weight: 800;
            position: relative;
            z-index: 1;
        }

        .cta-section p {
            font-size: 22px;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 50px;
            position: relative;
            z-index: 1;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .header-content {
                padding: 12px 20px;
            }

            .nav-menu {
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: var(--dark-navy);
                flex-direction: column;
                gap: 8px;
                align-items: center;
                padding: 20px 0;
                box-shadow: var(--shadow-lg);
                z-index: 999;
                display: none;
                border-bottom: 4px solid var(--cyber-yellow);
            }

            .nav-menu.active {
                display: flex;
            }

            .nav-menu a {
                font-size: 15px;
                padding: 12px 32px;
                width: auto;
                text-align: center;
            }

            .menu-toggle {
                display: block;
            }

            .hero h1 {
                font-size: 48px;
            }

            .hero p {
                font-size: 20px;
            }

            .section-title {
                font-size: 36px;
            }

            .features-grid,
            .modules-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .hero {
                padding: 80px 24px;
            }

            .hero h1 {
                font-size: 38px;
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
                padding: 50px 24px;
                margin: 40px 10px;
            }

            .features-grid,
            .modules-grid {
                grid-template-columns: 1fr;
                gap: 20px;
                margin: 30px 10px;
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
        <div class="purpose-section">
            <div class="mission-vision-grid">
                <div class="mission-card">
                    <h3>
                        <i class="fas fa-bullseye"></i>
                        Our Mission
                    </h3>
                    <p>Empower organizations to build a security-aware culture by providing engaging, effective cybersecurity training that transforms employees into the first line of defense against evolving threats.</p>
                </div>
                <div class="mission-card">
                    <h3>
                        <i class="fas fa-lightbulb"></i>
                        Our Vision
                    </h3>
                    <p>A world where every employee recognizes security threats, understands their role in protecting organizational assets, and confidently responds to emerging cyber risks with intelligence and caution.</p>
                </div>
            </div>

            <h2 style="text-align: center; font-size: 32px; color: var(--dark-navy); margin: 60px 0 40px; font-weight: 700;">Core Values</h2>
            <div class="values-grid">
                <div class="value-badge">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Security First</h4>
                    <p>Protection and compliance guide every decision</p>
                </div>
                <div class="value-badge">
                    <i class="fas fa-graduation-cap"></i>
                    <h4>Continuous Learning</h4>
                    <p>Adapt to evolving threats and techniques</p>
                </div>
                <div class="value-badge">
                    <i class="fas fa-users"></i>
                    <h4>Empowerment</h4>
                    <p>Enable teams with knowledge and tools</p>
                </div>
                <div class="value-badge">
                    <i class="fas fa-rocket"></i>
                    <h4>Innovation</h4>
                    <p>Modern solutions for emerging challenges</p>
                </div>
            </div>
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

        <div class="org-stats-section">
            <h2>Organization Overview</h2>
            <div class="org-stats-grid">
                <div class="org-stat-item">
                    <div class="org-stat-number">1000+</div>
                    <div class="org-stat-label">Users Trained</div>
                    <div class="org-stat-description">Across multiple organizations</div>
                </div>
                <div class="org-stat-item">
                    <div class="org-stat-number">50+</div>
                    <div class="org-stat-label">Organizations</div>
                    <div class="org-stat-description">Trust our platform</div>
                </div>
                <div class="stat-item">
                    <div class="org-stat-number">15K+</div>
                    <div class="org-stat-label">Training Hours</div>
                    <div class="org-stat-description">Completed worldwide</div>
                </div>
                <div class="org-stat-item">
                    <div class="org-stat-number">24/7</div>
                    <div class="org-stat-label">Support</div>
                    <div class="org-stat-description">Always available</div>
                </div>
            </div>
        </div>

        <div class="achievements-section">
            <h2>Our Journey</h2>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2022</div>
                        <h4>Platform Launch</h4>
                        <p>CyberAware officially launches with 5 comprehensive training modules</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2023 Q1</div>
                        <h4>ISO 27001 Certification</h4>
                        <p>Achieved industry-leading information security certification</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2023 Q3</div>
                        <h4>50+ Organizations</h4>
                        <p>Reached milestone of training 50 organizations worldwide</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2024</div>
                        <h4>Advanced Analytics</h4>
                        <p>Released real-time analytics and enhanced reporting dashboard</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2024 Q3</div>
                        <h4>Mobile Training App</h4>
                        <p>Launched mobile-friendly training platform for on-the-go learning</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2025+</div>
                        <h4>AI-Powered Training</h4>
                        <p>Developing intelligent, adaptive learning experiences using AI</p>
                    </div>
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
            <a href="pages/login.php" class="cta-btn">
                <i class="fas fa-rocket"></i>
                Start Your Training Journey
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