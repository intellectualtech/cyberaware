<div class="header">
    <div class="header-content">
        <a href="<?php echo hasRole('admin') || hasRole('manager') ? 'admin_dashboard.php' : 'trainee_dashboard.php'; ?>" class="logo">
            🛡️ CyberShield
        </a>
        
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
                <a href="index.php">Home</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact</a>
            <?php endif; ?>
        </nav>
        
        <div class="user-info">
            <?php if (isLoggedIn()): ?>
                <span>👤 <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <span class="badge <?php 
                    echo hasRole('admin') ? 'badge-danger' : 
                        (hasRole('manager') ? 'badge-warning' : 'badge-info'); 
                ?>">
                    <?php echo strtoupper($_SESSION['role']); ?>
                </span>
                <a href="logout.php" class="btn btn-secondary" style="padding: 8px 16px;">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary">Login</a>
            <?php endif; ?>
        </div>
    </div>
</div>