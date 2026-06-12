<?php
// trainee/trainee-sidebar.php - Bottom Navigation Sidebar for Trainee Portal

$script_path = $_SERVER['PHP_SELF'];
$is_in_modules = (strpos($script_path, '/modules/') !== false);

// Correct relative paths
$dashboard_link = $is_in_modules ? '../dashboard.php' : 'dashboard.php';
$progress_link  = $is_in_modules ? '../progress.php' : 'progress.php';
$inbox_link     = $is_in_modules ? '../phish-inbox.php' : 'phish-inbox.php';
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
            <a href="<?= $is_in_modules ? '../register-modules.php' : 'register-modules.php' ?>" <?= $current_page === 'register-modules' ? 'class="active"' : '' ?>>
                <i class="fas fa-plus-circle"></i>
                <span>Register</span>
            </a>
        </li>
        <li>
            <a href="<?= $is_in_modules ? '../learning-modules.php' : 'learning-modules.php' ?>" <?= $current_page === 'learning-modules' ? 'class="active"' : '' ?>>
                <i class="fas fa-book"></i>
                <span>Modules</span>
            </a>
        </li>
        <li>
            <a href="<?= $modules_prefix ?>phishing.php" <?= $current_page === 'phishing' ? 'class="active"' : '' ?>>
                <i class="fas fa-envelope"></i>
                <span>Phishing</span>
            </a>
        </li>
        <li>
            <a href="<?= $modules_prefix ?>credentials.php" <?= $current_page === 'credentials' ? 'class="active"' : '' ?>>
                <i class="fas fa-key"></i>
                <span>Credentials</span>
            </a>
        </li>
        <li>
            <a href="<?= $modules_prefix ?>social.php" <?= $current_page === 'social' ? 'class="active"' : '' ?>>
                <i class="fas fa-phone-alt"></i>
                <span>Social Eng.</span>
            </a>
        </li>
        <li>
            <a href="<?= $modules_prefix ?>attachments.php" <?= $current_page === 'attachments' ? 'class="active"' : '' ?>>
                <i class="fas fa-paperclip"></i>
                <span>Attachments</span>
            </a>
        </li>
        <li>
            <a href="<?= $modules_prefix ?>links.php" <?= $current_page === 'links' ? 'class="active"' : '' ?>>
                <i class="fas fa-link"></i>
                <span>Links</span>
            </a>
        </li>
        <li>
            <a href="<?= $inbox_link ?>" <?= $current_page === 'phish-inbox' ? 'class="active"' : '' ?>>
                <i class="fas fa-inbox"></i>
                <span>Inbox</span>
            </a>
        </li>
        <li>
            <a href="<?= $progress_link ?>" <?= $current_page === 'progress' ? 'class="active"' : '' ?>>
                <i class="fas fa-chart-line"></i>
                <span>Progress</span>
            </a>
        </li>
        <li>
            <a href="<?= $is_in_modules ? '../leaderboard.php' : 'leaderboard.php' ?>" <?= $current_page === 'leaderboard' ? 'class="active"' : '' ?>>
                <i class="fas fa-trophy"></i>
                <span>Leaderboard</span>
            </a>
        </li>
        <li>
            <a href="<?= $is_in_modules ? '../profile.php' : 'profile.php' ?>" <?= $current_page === 'profile' ? 'class="active"' : '' ?>>
                <i class="fas fa-user"></i>
                <span>Profile</span>
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
        --white: #FFFFFF;
        --grey-50: #F3F4F6;
        --grey-100: #E5E7EB;
        --grey-300: #D1D5DB;
        --grey-500: #6B7280;
        --grey-700: #374151;
        --grey-800: #1f2937;
        --text-dark: #111827;

        --sidebar-height: 72px;
        --shadow-md: 0 10px 24px rgba(15,23,42,0.12);
        --radius: 12px;
    }

    .sidebar {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        height: var(--sidebar-height);
        background: rgba(255,255,255,0.96);
        border-top: 1px solid rgba(15,23,42,0.08);
        box-shadow: 0 -10px 24px rgba(15,23,42,0.08);
        display: flex;
        align-items: center;
        justify-content: space-around;
        z-index: 100;
        padding: 0 12px;
        backdrop-filter: blur(16px);
        pointer-events: auto;
    }

    .sidebar-header { 
        display: none; 
    }

    .sidebar-nav {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        gap: 0.5rem;
        align-items: center;
        justify-content: center;
        width: 100%;
    }

    .sidebar-nav li {
        margin: 0;
    }

    .sidebar-nav a {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0.6rem 0.9rem;
        color: rgba(15, 23, 42, 0.75);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.75rem;
        transition: all 0.2s ease;
        border-top: 3px solid transparent;
        background: transparent;
        border-radius: 8px;
    }

    .sidebar-nav a:hover {
        background: rgba(255,140,66,0.12);
        color: var(--text-dark);
    }

    .sidebar-nav a.active {
        background: rgba(255,140,66,0.18);
        color: var(--primary);
        border-top-color: var(--primary);
        font-weight: 700;
    }

    .sidebar-nav a i {
        margin: 0 0 0.3rem 0;
        font-size: 1.2rem;
        width: auto;
        text-align: center;
    }

    .sidebar-nav a span {
        display: block;
        white-space: nowrap;
    }

    /* Ensure main content doesn't get hidden behind sidebar */
    body {
        padding-bottom: var(--sidebar-height);
    }

    @media (max-width: 576px) {
        .sidebar-nav a {
            font-size: 0.65rem;
            padding: 0.5rem 0.3rem;
        }

        .sidebar-nav a i {
            font-size: 1rem;
        }

        .sidebar-nav a span {
            font-size: 0.6rem;
        }
    }
</style>
