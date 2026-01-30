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
        --cyber-yellow: #FF8C42;
        --primary: #FF8C42;
        --cyber-gold: #FF8C42;
        --white: #FFFFFF;
        --cyber-light: #F3F4F6;
        --dark-navy: #111827;
        --dark-slate: #374151;
        --grey-50: #F3F4F6;
        --grey-100: #E5E7EB;
        --grey-200: #D1D5DB;
        --grey-600: #475569;
        --grey-700: #374151;
        --grey-800: #1e293b;
        --sidebar-width: 260px;
        --shadow-md: 0 6px 18px rgba(0,0,0,0.09);
        --radius: 12px;
    }

    .sidebar {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        height: var(--sidebar-height);
        background: var(--grey-800);
        border-top: 4px solid var(--primary);
        box-shadow: 0 -8px 30px rgba(0,0,0,0.12);
        display: flex;
        align-items: center;
        justify-content: space-around;
        z-index: 1000;
        padding: 0 12px;
    }

    .sidebar-header {
        display: none;
    }

    .sidebar a {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        color: var(--white);
        text-decoration: none;
        font-size: 13px;
        padding: 8px 10px;
        border-radius: 8px;
    }

    .sidebar a i { font-size: 18px; }

    .sidebar a.active { color: var(--primary); }

    .sidebar .footer-note { display:none; }

    .sidebar-header h1 {
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--cyber-yellow);
        margin: 0;
    }

    .sidebar-header p {
        color: rgba(var(--primary-rgb),0.9);
        font-size: 0.95rem;
        margin: 0.3rem 0 0 0;
    }

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
        color: rgba(255, 255, 255, 0.85);
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
        border-top: 3px solid transparent;
        background: transparent;
    }

    .sidebar-nav a:hover {
        background: rgba(var(--primary-rgb),0.12);
        color: var(--white);
    }

    .sidebar-nav a.active {
        background: rgba(var(--primary-rgb),0.18);
        color: var(--primary);
        border-left-color: var(--primary);
        font-weight: 600;
    }

    .sidebar-nav a i {
        margin: 0 0 0.3rem 0;
        font-size: 1.2rem;
        width: auto;
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