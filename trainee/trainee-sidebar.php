<?php
// includes/trainee-sidebar.php - Reusable Trainee Sidebar (Fully Fixed & Updated)

$script_path = $_SERVER['PHP_SELF'];
$is_in_modules = (strpos($script_path, '/modules/') !== false);

// Correct relative paths
$dashboard_link = $is_in_modules ? '../dashboard.php' : 'dashboard.php';
$progress_link  = $is_in_modules ? '../progress.php' : 'progress.php';
$modules_prefix = $is_in_modules ? '' : 'modules/';
$logout_link    = $is_in_modules ? '../../logout.php' : '../logout.php';

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <h1>CyberAware</h1>
        <p>Training Portal</p>
    </div>

    <ul class="sidebar-nav">
        <li>
            <a href="<?= $dashboard_link ?>" <?= $current_page === 'dashboard' ? 'class="active"' : '' ?>>
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="<?= $modules_prefix ?>phishing.php" <?= $current_page === 'phishing' ? 'class="active"' : '' ?>>
                <i class="fas fa-envelope"></i>
                <span>Phishing Recognition</span>
            </a>
        </li>
        <li>
            <a href="<?= $modules_prefix ?>credentials.php" <?= $current_page === 'credentials' ? 'class="active"' : '' ?>>
                <i class="fas fa-key"></i>
                <span>Fake Login Pages</span>
            </a>
        </li>
        <li>
            <a href="<?= $modules_prefix ?>social.php" <?= $current_page === 'social' ? 'class="active"' : '' ?>>
                <i class="fas fa-phone-alt"></i>
                <span>Social Engineering</span>
            </a>
        </li>
        <li>
            <a href="<?= $modules_prefix ?>attachments.php" <?= $current_page === 'attachments' ? 'class="active"' : '' ?>>
                <i class="fas fa-paperclip"></i>
                <span>Dangerous Attachments</span>
            </a>
        </li>
        <li>
            <a href="<?= $modules_prefix ?>links.php" <?= $current_page === 'links' ? 'class="active"' : '' ?>>
                <i class="fas fa-link"></i>
                <span>Suspicious Links</span>
            </a>
        </li>
        <li>
            <a href="<?= $progress_link ?>" <?= $current_page === 'progress' ? 'class="active"' : '' ?>>
                <i class="fas fa-chart-line"></i>
                <span>My Progress</span>
            </a>
        </li>
        <li>
            <a href="<?= $logout_link ?>">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>

<!-- Font Awesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
      integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ=="
      crossorigin="anonymous" referrerpolicy="no-referrer" />

<style>
    :root {
        --primary: #FF8C42;
        --primary-dark: #E67A2E;
        --primary-light: #FFF4ED;
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-600: #475569;
        --gray-700: #334155;
        --gray-800: #1e293b;
        --sidebar-width: 260px;
        --shadow-md: 0 4px 16px rgba(0,0,0,0.1);
        --radius: 12px;
    }

    .sidebar {
        width: var(--sidebar-width);
        background: white;
        border-right: 1px solid var(--gray-200);
        box-shadow: var(--shadow-md);
        position: fixed;
        height: 100vh;
        overflow-y: auto;
        padding: 2rem 0;
        z-index: 100;
        left: 0;
        top: 0;
    }

    .sidebar-header {
        padding: 0 1.8rem 2rem;
        border-bottom: 1px solid var(--gray-200);
        margin-bottom: 1.5rem;
    }

    .sidebar-header h1 {
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--primary-dark);
        margin: 0;
    }

    .sidebar-header p {
        color: var(--gray-600);
        font-size: 0.95rem;
        margin: 0.3rem 0 0 0;
    }

    .sidebar-nav {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar-nav li {
        margin: 0.4rem 0;
    }

    .sidebar-nav a {
        display: flex;
        align-items: center;
        padding: 0.9rem 1.8rem;
        color: var(--gray-700);
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
        border-left: 3px solid transparent;
    }

    .sidebar-nav a:hover {
        background: var(--gray-100);
        color: var(--primary-dark);
    }

    .sidebar-nav a.active {
        background: var(--primary-light);
        color: var(--primary);
        border-left-color: var(--primary);
        font-weight: 600;
    }

    .sidebar-nav a i {
        margin-right: 1rem;
        font-size: 1.1rem;
        width: 20px;
        text-align: center;
    }

    /* Mobile Responsive - Bottom bar */
    @media (max-width: 992px) {
        .sidebar {
            position: fixed;
            width: 100%;
            height: auto;
            border-right: none;
            border-bottom: 1px solid var(--gray-200);
            padding: 1rem 0;
            bottom: 0;
            top: auto;
            box-shadow: 0 -4px 16px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            display: none;
        }

        .sidebar-nav {
            display: flex;
            justify-content: space-around;
            align-items: center;
            overflow-x: auto;
        }

        .sidebar-nav li {
            margin: 0;
            flex: 1;
        }

        .sidebar-nav a {
            flex-direction: column;
            padding: 0.6rem 0.5rem;
            border-left: none;
            border-top: 3px solid transparent;
            text-align: center;
            font-size: 0.75rem;
        }

        .sidebar-nav a i {
            margin-right: 0;
            margin-bottom: 0.3rem;
            font-size: 1.2rem;
        }

        .sidebar-nav a span {
            display: block;
            white-space: nowrap;
        }

        .sidebar-nav a.active {
            border-left-color: transparent;
            border-top-color: var(--primary);
        }
    }

    @media (max-width: 576px) {
        .sidebar-nav a {
            font-size: 0.7rem;
            padding: 0.5rem 0.3rem;
        }

        .sidebar-nav a i {
            font-size: 1rem;
        }

        .sidebar-nav a span {
            font-size: 0.65rem;
        }
    }
</style>