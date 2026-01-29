<?php
// includes/admin-sidebar.php - Reusable Admin Sidebar

// Get current page name for active highlight
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <h1>CyberAware</h1>
        <p>Admin Panel</p>
    </div>

    <ul class="sidebar-nav">
        <li>
            <a href="dashboard.php" <?= $current_page === 'dashboard' ? 'class="active"' : '' ?>>
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="campaigns.php" <?= $current_page === 'campaigns' ? 'class="active"' : '' ?>>
                <i class="fas fa-calendar-alt"></i>
                <span>Manage Campaigns</span>
            </a>
        </li>
        <li>
            <a href="manage-users.php" <?= in_array($current_page, ['manage_users', 'add_user', 'edit_user', 'view_user']) ? 'class="active"' : '' ?>>
                <i class="fas fa-users"></i>
                <span>Manage Users</span>
            </a>
        </li>
        <li>
            <a href="manage_modules.php" <?= in_array($current_page, ['manage_modules']) ? 'class="active"' : '' ?>>
                <i class="fas fa-users"></i>
                <span>Manage Modules</span>
            </a>
        </li>
        <li>
            <a href="department-scores.php" <?= $current_page === 'department-scores' ? 'class="active"' : '' ?>>
                <i class="fas fa-chart-bar"></i>
                <span>Department Scores</span>
            </a>
        </li>
        <li>
            <a href="export-report.php" <?= $current_page === 'export-report' ? 'class="active"' : '' ?>>
                <i class="fas fa-file-export"></i>
                <span>Export Report</span>
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

<style>
    :root {
        --cyber-yellow: #FFD60A;
        --cyber-gold: #FFC300;
        --dark-navy: #0F1419;
        --dark-slate: #1A1E2E;
        --white: #FFFFFF;
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
        background: var(--dark-navy);
        border-right: 5px solid var(--cyber-yellow);
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
        border-bottom: 1px solid rgba(255, 214, 10, 0.2);
        margin-bottom: 1.5rem;
    }

    .sidebar-header h1 {
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--cyber-yellow);
        margin: 0;
    }

    .sidebar-header p {
        color: rgba(255, 214, 10, 0.7);
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
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
        border-left: 3px solid transparent;
    }

    .sidebar-nav a:hover {
        background: rgba(255, 214, 10, 0.1);
        color: var(--cyber-yellow);
    }

    .sidebar-nav a.active {
        background: rgba(255, 214, 10, 0.15);
        color: var(--cyber-yellow);
        border-left-color: var(--cyber-yellow);
        font-weight: 600;
    }

    .sidebar-nav a i {
        margin-right: 1rem;
        font-size: 1.1rem;
        width: 20px;
        text-align: center;
    }

    /* Mobile Responsive */
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