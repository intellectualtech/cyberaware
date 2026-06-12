<?php
// manager/manager-sidebar.php - Manager Sidebar with Profile and Logout

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <h1>CyberAware</h1>
        <p>Manager Portal</p>
    </div>

    <ul class="sidebar-nav">
        <li>
            <a href="dashboard.php" <?= $current_page === 'dashboard' ? 'class="active"' : '' ?>>
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="profile.php" <?= $current_page === 'profile' ? 'class="active"' : '' ?>>
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </li>
        <li>
            <a href="settings.php" <?= $current_page === 'settings' ? 'class="active"' : '' ?>>
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
        <li>
            <a href="../logout.php">
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
        /* Primary Brand Colors (Orange / White) */
        --cyber-yellow: #FF8C42;
        --primary: #FF8C42;
        --cyber-gold: #FF8C42;
        --white: #FFFFFF;
        --cyber-light: #F7F7FB;

        /* Grey Tones */
        --grey-50: #F3F4F6;
        --grey-100: #E5E7EB;
        --grey-300: #D1D5DB;
        --grey-500: #6B7280;
        --grey-700: #374151;
        --grey-800: #1f2937;
        --text-dark: #111827;

        --sidebar-width: 260px;
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
        z-index: 1000;
        padding: 0 12px;
        backdrop-filter: blur(16px);
    }

    .sidebar-header { display:none; }

    .sidebar a {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        color: var(--grey-800);
        text-decoration: none;
        font-size: 12px;
        padding: 8px 10px;
        border-radius: 999px;
        transition: all 0.2s ease;
    }

    .sidebar a i { font-size: 18px; }
    .sidebar a.active { color: var(--primary); background: rgba(255,140,66,0.15); }

    .sidebar-nav {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex; /* horizontal layout by default */
        gap: 0.5rem;
        align-items: center;
        justify-content: center;
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
        transition: all 0.2s ease;
        border-top: 3px solid transparent;
        background: transparent;
        border-radius: 999px;
    }

    .sidebar-nav a:hover {
        background: rgba(255,140,66,0.12);
        color: var(--text-dark);
    }

    .sidebar-nav a.active {
        background: rgba(255,140,66,0.18);
        color: var(--primary);
        border-left-color: var(--primary);
        font-weight: 700;
    }

    .sidebar-nav a i {
        margin: 0 0 0.3rem 0;
        font-size: 1.2rem;
        width: auto;
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
