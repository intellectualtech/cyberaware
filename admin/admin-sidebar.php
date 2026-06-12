<?php
// includes/admin-sidebar.php - Reusable Admin Sidebar

// Get current page name for active highlight
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<button id="toggleSidebarBtn" class="sidebar-toggle-btn" onclick="toggleAdminSidebar()" title="Hide Sidebar">
    <i class="fas fa-chevron-left"></i>
</button>

<aside class="sidebar" id="adminSidebar">
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
            <a href="risk-heatmap.php" <?= $current_page === 'risk-heatmap' ? 'class="active"' : '' ?>>
                <i class="fas fa-fire"></i>
                <span>Risk Heatmap</span>
            </a>
        </li>
        <li>
            <a href="compliance-snapshot.php" <?= $current_page === 'compliance-snapshot' ? 'class="active"' : '' ?>>
                <i class="fas fa-clipboard-check"></i>
                <span>Compliance Snapshot</span>
            </a>
        </li>
        <li>
            <a href="export-report.php" <?= $current_page === 'export-report' ? 'class="active"' : '' ?>>
                <i class="fas fa-file-export"></i>
                <span>Export Report</span>
            </a>
        </li>
        <li>
            <a href="incident-reports.php" <?= $current_page === 'incident-reports' || $current_page === 'incident-detail' ? 'class="active"' : '' ?>>
                <i class="fas fa-exclamation-circle"></i>
                <span>Incident Reports</span>
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

<button id="showSidebarBtn" class="sidebar-show-btn" onclick="toggleAdminSidebar()" title="Show Sidebar">
    <i class="fas fa-chevron-right"></i>
</button>

<style>
    :root {
        --admin-bg: #0b1120;
        --admin-panel: #111827;
        --admin-card: #ffffff;
        --admin-accent: #FF8C42;
        --admin-muted: #94a3b8;
        --admin-line: rgba(148, 163, 184, 0.18);
        --sidebar-width: 260px;
        --sidebar-height: 72px;
        --radius: 14px;
        --shadow-sm: 0 8px 18px rgba(15, 23, 42, 0.18);
        --shadow-md: 0 18px 40px rgba(15, 23, 42, 0.28);
    }

    body {
        font-family: 'Manrope', 'Segoe UI', sans-serif;
        background: radial-gradient(900px 520px at 15% -10%, #1f2937 0%, transparent 60%),
            linear-gradient(140deg, #0b1120 0%, #111827 100%);
        color: #e2e8f0;
    }

    .main-content {
        margin-left: var(--sidebar-width);
        margin-bottom: 0;
        padding: 32px 36px 80px;
        min-height: 100vh;
    }

    .header {
        background: rgba(17, 24, 39, 0.9);
        border: 1px solid var(--admin-line);
        border-radius: 18px;
        padding: 24px 28px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-sm);
    }

    .container {
        padding: 0;
    }

    .card,
    .stat-card {
        background: var(--admin-card);
        color: #111827;
        border-radius: 18px;
        border: 1px solid rgba(148, 163, 184, 0.2);
        box-shadow: var(--shadow-sm);
    }

    .btn,
    .btn-primary {
        border-radius: 999px;
        font-weight: 700;
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        width: var(--sidebar-width);
        background: rgba(15, 23, 42, 0.98);
        border-right: 1px solid var(--admin-line);
        box-shadow: 12px 0 24px rgba(0, 0, 0, 0.35);
        display: flex;
        flex-direction: column;
        align-items: stretch;
        padding: 28px 18px;
        z-index: 1000;
        transition: transform 0.3s ease;
    }

    .sidebar.hidden {
        transform: translateX(-100%);
    }

    .sidebar-toggle-btn {
        position: absolute;
        top: 20px;
        right: 15px;
        background: var(--admin-accent);
        color: white;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        z-index: 1001;
        transition: 0.2s;
    }

    .sidebar-toggle-btn:hover {
        background: #e67e2f;
    }

    .sidebar-show-btn {
        position: fixed;
        left: 20px;
        bottom: 20px;
        background: var(--admin-accent);
        color: white;
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 4px 12px rgba(255, 140, 66, 0.3);
        z-index: 999;
        transition: 0.2s;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .sidebar-show-btn.visible {
        display: flex;
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .sidebar-show-btn:hover {
        background: #e67e2f;
        transform: scale(1.1);
    }

    .sidebar-header {
        display: block;
        margin-bottom: 28px;
    }

    .sidebar-header h1 {
        font-size: 1.4rem;
        font-weight: 800;
        color: #f8fafc;
        margin: 0;
    }

    .sidebar-header p {
        color: var(--admin-muted);
        margin: 0.4rem 0 0;
        font-size: 0.9rem;
    }

    .sidebar-nav {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .sidebar-nav a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 10px;
        text-decoration: none;
        color: #e2e8f0;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .sidebar-nav a i {
        font-size: 1rem;
        width: 20px;
        text-align: center;
    }

    .sidebar-nav a:hover {
        background: rgba(255, 140, 66, 0.12);
        color: #ffffff;
    }

    .sidebar-nav a.active {
        background: rgba(255, 140, 66, 0.2);
        color: #ffffff;
        box-shadow: inset 0 0 0 1px rgba(255, 140, 66, 0.35);
    }

    @media (max-width: 992px) {
        .sidebar {
            top: auto;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            height: var(--sidebar-height);
            flex-direction: row;
            align-items: center;
            justify-content: space-around;
            padding: 0 10px;
        }

        .sidebar-header {
            display: none;
        }

        .sidebar-nav {
            flex-direction: row;
            gap: 8px;
            overflow-x: auto;
            width: 100%;
            justify-content: space-around;
        }

        .sidebar-nav a {
            flex-direction: column;
            font-size: 0.7rem;
            padding: 6px 8px;
        }

        .main-content {
            margin-left: 0;
            margin-bottom: var(--sidebar-height);
            padding: 24px 20px 80px;
        }
    }
</style>

<script>
function toggleAdminSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const showBtn = document.getElementById('showSidebarBtn');
    const toggleBtn = document.getElementById('toggleSidebarBtn');
    
    sidebar.classList.toggle('hidden');
    showBtn.classList.toggle('visible');
    
    // Save state to localStorage
    const isHidden = sidebar.classList.contains('hidden');
    localStorage.setItem('adminSidebarHidden', isHidden ? 'true' : 'false');
}

// Restore sidebar state on page load
document.addEventListener('DOMContentLoaded', function() {
    const sidebarHidden = localStorage.getItem('adminSidebarHidden') === 'true';
    if (sidebarHidden) {
        const sidebar = document.getElementById('adminSidebar');
        const showBtn = document.getElementById('showSidebarBtn');
        
        sidebar.classList.add('hidden');
        showBtn.classList.add('visible');
    }
});
</script>