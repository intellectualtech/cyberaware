<?php
/**
 * Admin Theme Wrapper Helper
 * Provides consistent theme initialization for all admin pages
 */

// Initialize theme and admin info
function initAdminTheme() {
    global $admin_name, $admin_email;
    
    require_once '../config/database.php';
    require_once '../includes/functions.php';
    require_once '../includes/theme-helper.php';
    
    require_admin();
    
    $pdo = getDBConnection();
    $admin_id = $_SESSION['user_id'];
    $admin_name = 'Admin';
    $admin_email = '';
    
    try {
        $stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch();
        if ($admin) {
            $admin_name = $admin['full_name'] ?? 'Admin';
            $admin_email = $admin['email'] ?? '';
        }
    } catch (Exception $e) {
        $admin_name = $_SESSION['full_name'] ?? 'Admin';
    }
    
    return $pdo;
}

// CSS template for all admin pages
function getAdminCSS() {
    return <<<'CSS'
*{margin:0;padding:0;box-sizing:border-box;}
:root{--orange:#FF8C42;--ol:#FFF4EC;--grey:#F5F5F5;--grey2:#E8E8E8;--dark:#1A1A2E;--green:#10B981;--purple:#8B5CF6;--red:#EF4444;}
body{font-family:'Manrope',sans-serif;background:var(--grey);min-height:100vh;}
.topbar{background:var(--dark);padding:0 24px;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
.tb-logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.tb-logo .ic{width:36px;height:36px;background:var(--orange);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;}
.tb-logo span{font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;color:#fff;}
.tb-right{display:flex;align-items:center;gap:16px;}
.tb-avatar-wrapper{position:relative;}
.tb-avatar{width:36px;height:36px;background:var(--orange);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;cursor:pointer;transition:.2s;}
.tb-avatar:hover{background:#e67e2f;transform:scale(1.05);}
.tb-dropdown{position:absolute;top:calc(100% + 8px);right:0;background:#fff;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.15);min-width:280px;opacity:0;visibility:hidden;transform:translateY(-10px);transition:.2s;z-index:1000;}
.tb-dropdown.active{opacity:1;visibility:visible;transform:translateY(0);}
.tb-dropdown-header{padding:16px;border-bottom:1px solid #f0f0f0;}
.tb-dropdown-user{display:flex;align-items:center;gap:12px;margin-bottom:8px;}
.tb-dropdown-avatar{width:48px;height:48px;background:var(--orange);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:18px;}
.tb-dropdown-name{font-weight:700;color:var(--dark);font-size:14px;}
.tb-dropdown-email{font-size:12px;color:#888;}
.tb-dropdown-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#bbb;margin-top:8px;}
.tb-dropdown-menu{padding:8px 0;}
.tb-dropdown-item{display:flex;align-items:center;gap:12px;padding:12px 16px;color:#333;text-decoration:none;font-size:14px;font-weight:600;transition:.2s;cursor:pointer;}
.tb-dropdown-item:hover{background:#f5f5f5;color:var(--orange);}
.tb-dropdown-item i{width:18px;text-align:center;color:var(--orange);}
.tb-dropdown-item.logout{color:var(--red);}
.tb-dropdown-item.logout:hover{background:#fff0f0;}
.layout{display:grid;grid-template-columns:280px 1fr;min-height:calc(100vh - 64px);}
.sidebar{background:#fff;border-right:1px solid var(--grey2);padding:28px 20px;display:flex;flex-direction:column;gap:8px;}
.sb-profile{background:linear-gradient(135deg,var(--ol),#fff);border-radius:14px;padding:20px;margin-bottom:16px;text-align:center;}
.sb-avatar{width:60px;height:60px;background:var(--orange);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:24px;font-weight:700;margin:0 auto 12px;}
.sb-name{font-size:16px;font-weight:700;color:var(--dark);margin-bottom:2px;}
.sb-role{font-size:13px;color:#888;}
.sb-level{display:inline-block;background:var(--orange);color:#fff;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;margin-top:8px;}
.nav-item{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;text-decoration:none;color:#555;font-weight:600;font-size:15px;transition:.2s;}
.nav-item:hover,.nav-item.active{background:var(--ol);color:var(--orange);}
.nav-item i{width:20px;text-align:center;font-size:16px;}
.sb-section{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#bbb;padding:12px 16px 4px;}
.nav-logout{color:var(--red);}
.nav-logout:hover{background:#fff0f0;color:var(--red);}
.main{padding:32px;padding-bottom:100px;}
.page-hdr{margin-bottom:28px;}
.page-hdr h1{font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;color:var(--dark);margin-bottom:6px;}
.page-hdr p{color:#888;font-size:15px;}
.card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.05);border-left:4px solid var(--orange);margin-bottom:24px;transition:.2s;}
.card:hover{box-shadow:0 12px 32px rgba(0,0,0,.1);}
.card-title{font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;color:var(--dark);margin-bottom:16px;}
table{width:100%;border-collapse:collapse;margin-top:16px;}
th,td{padding:12px 14px;text-align:left;border-bottom:1px solid var(--grey2);font-size:14px;}
th{background:var(--grey);font-weight:700;color:var(--dark);}
tbody tr:hover{background:var(--ol);}
body.dark-mode{background:#1A1A2E;color:#fff;}
body.dark-mode .topbar{background:#0f0f1e;}
body.dark-mode .sidebar{background:#1f1f2e;border-right-color:#333;}
body.dark-mode .nav-item{color:#aaa;}
body.dark-mode .nav-item:hover,body.dark-mode .nav-item.active{background:rgba(255,140,66,.15);color:var(--orange);}
body.dark-mode .sb-profile{background:rgba(255,140,66,.1);}
body.dark-mode .sb-name{color:#fff;}
body.dark-mode .main{background:#1A1A2E;}
body.dark-mode .page-hdr h1{color:#fff;}
body.dark-mode .page-hdr p{color:#aaa;}
body.dark-mode .card{background:#2a2a3e;box-shadow:0 2px 8px rgba(0,0,0,.3);}
body.dark-mode .card:hover{box-shadow:0 12px 32px rgba(0,0,0,.5);}
body.dark-mode .card-title{color:#fff;}
body.dark-mode table{background:#2a2a3e;color:#fff;}
body.dark-mode th{background:#1f1f2e;color:#fff;}
body.dark-mode th,body.dark-mode td{border-bottom-color:#333;}
body.dark-mode tbody tr:hover{background:#333;}
body.dark-mode .tb-dropdown{background:#2a2a3e;}
body.dark-mode .tb-dropdown-header{border-bottom-color:#333;}
body.dark-mode .tb-dropdown-name{color:#fff;}
body.dark-mode .tb-dropdown-item{color:#fff;}
body.dark-mode .tb-dropdown-item:hover{background:#333;color:var(--orange);}
@media(max-width:900px){.layout{grid-template-columns:1fr;}.sidebar{display:none;}}
CSS;
}

// Admin layout header
function adminLayoutHeader($admin_name, $admin_email) {
    echo <<<HTML
<div class="topbar">
    <a href="dashboard.php" class="tb-logo">
        <div class="ic"><i class="fas fa-shield-alt"></i></div>
        <span>CyberAware</span>
    </a>
    <div class="tb-right">
        <div class="tb-avatar-wrapper">
            <div class="tb-avatar" onclick="toggleDropdown()">${ strtoupper(substr($admin_name,0,1)) }</div>
            <div class="tb-dropdown" id="profileDropdown">
                <div class="tb-dropdown-header">
                    <div class="tb-dropdown-user">
                        <div class="tb-dropdown-avatar">${ strtoupper(substr($admin_name,0,1)) }</div>
                        <div>
                            <div class="tb-dropdown-name">${ htmlspecialchars($admin_name) }</div>
                            <div class="tb-dropdown-email">${ htmlspecialchars($admin_email ?? '') }</div>
                        </div>
                    </div>
                    <div class="tb-dropdown-label">Signed in as</div>
                </div>
                <div class="tb-dropdown-menu">
                    <a href="../logout.php" class="tb-dropdown-item logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;
}
