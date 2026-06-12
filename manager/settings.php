<?php
/**
 * Manager Settings
 * Settings page for managers
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// Require login and manager role
if (!isLoggedIn()) {
    header('Location: ../pages/login.php');
    exit;
}

$role = strtolower(trim($_SESSION['role'] ?? ''));
if ($role !== 'manager') {
    header('Location: ../pages/access-denied.php');
    exit;
}

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];

    // Get manager info
    $stmt = $pdo->prepare("SELECT u.*, d.name AS dept FROM users u LEFT JOIN departments d ON d.id=u.department_id WHERE u.id=?");
    $stmt->execute([$user_id]);
    $manager = $stmt->fetch();
    $manager_name = $manager['full_name'] ?? $manager['username'] ?? 'Manager';
    $manager_dept = $manager['dept'] ?? 'General';
    $manager_email = $manager['email'] ?? '';

} catch(Exception $e) {
    $manager_name = 'Manager';
    $manager_dept = 'General';
    $manager_email = '';
}

$current_page = 'settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Settings — CyberAware Manager</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--orange:#FF8C42;--ol:#FFF4EC;--grey:#F5F5F5;--grey2:#E8E8E8;--dark:#1A1A2E;--green:#10B981;--red:#EF4444;}
body{font-family:'Manrope',sans-serif;background:var(--grey);min-height:100vh;padding-bottom:100px;}
.topbar{background:var(--dark);padding:0 24px;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
.tb-logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.tb-logo .ic{width:36px;height:36px;background:var(--orange);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;}
.tb-logo span{font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;color:#fff;}
.tb-right{display:flex;align-items:center;gap:16px;}
.tb-avatar-wrapper{position:relative;}
.tb-avatar{width:36px;height:36px;background:var(--orange);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:15px;cursor:pointer;transition:all 0.2s;}
.tb-avatar:hover{background:#e07030;transform:scale(1.05);}
.dropdown-menu{position:absolute;top:100%;right:0;background:#fff;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,0.15);min-width:180px;margin-top:8px;opacity:0;visibility:hidden;transform:translateY(-10px);transition:all 0.2s;z-index:1001;}
.dropdown-menu.active{opacity:1;visibility:visible;transform:translateY(0);}
.dropdown-item{display:flex;align-items:center;gap:10px;padding:12px 16px;color:var(--dark);text-decoration:none;font-size:14px;font-weight:600;transition:all 0.2s;border-bottom:1px solid var(--grey2);}
.dropdown-item:last-child{border-bottom:none;}
.dropdown-item:hover{background:var(--ol);color:var(--orange);}
.dropdown-item i{width:16px;text-align:center;}
.main{padding:32px;}
.container{max-width:1200px;margin:0 auto;}
.page-header{margin-bottom:32px;}
.page-header h1{font-family:'Space Grotesk',sans-serif;font-size:32px;font-weight:700;color:var(--dark);margin-bottom:6px;}
.page-header p{color:#888;font-size:15px;}
/* SETTINGS GRID */
.settings-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(500px,1fr));gap:24px;}
.settings-card{background:#fff;border-radius:16px;padding:28px;box-shadow:0 2px 8px rgba(0,0,0,.05);}
.card-header{display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--grey2);}
.card-icon{width:40px;height:40px;background:var(--ol);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--orange);font-size:18px;}
.card-title{font-size:18px;font-weight:700;color:var(--dark);}
.card-desc{font-size:13px;color:#888;margin-top:2px;}
/* FORM ELEMENTS */
.form-group{margin-bottom:20px;}
.form-group:last-child{margin-bottom:0;}
.form-label{display:block;font-size:14px;font-weight:700;color:var(--dark);margin-bottom:8px;}
.form-input,.form-select{width:100%;padding:12px 14px;border:1px solid var(--grey2);border-radius:10px;font-family:'Manrope',sans-serif;font-size:14px;color:var(--dark);transition:.2s;}
.form-input:focus,.form-select:focus{outline:none;border-color:var(--orange);box-shadow:0 0 0 3px rgba(255,140,66,.1);}
.form-input::placeholder{color:#aaa;}
.form-help{font-size:12px;color:#888;margin-top:6px;}
/* TOGGLES */
.toggle-group{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--grey2);}
.toggle-group:last-child{border-bottom:none;}
.toggle-label{font-size:14px;font-weight:600;color:var(--dark);}
.toggle-desc{font-size:12px;color:#888;margin-top:2px;}
.toggle-switch{position:relative;width:50px;height:28px;background:var(--grey2);border-radius:14px;cursor:pointer;transition:.2s;}
.toggle-switch.active{background:var(--green);}
.toggle-switch::after{content:'';position:absolute;width:24px;height:24px;background:#fff;border-radius:50%;top:2px;left:2px;transition:.2s;}
.toggle-switch.active::after{left:24px;}
/* BADGE */
.badge{display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:700;}
.badge.enabled{background:#d1fae5;color:#065f46;}
.badge.disabled{background:var(--grey2);color:#888;}
/* BUTTONS */
.btn{padding:12px 24px;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:'Manrope',sans-serif;transition:.2s;display:inline-flex;align-items:center;gap:8px;}
.btn-primary{background:var(--orange);color:#fff;}
.btn-primary:hover{background:#e67e2f;transform:translateY(-2px);}
.btn-danger{background:var(--red);color:#fff;}
.btn-danger:hover{background:#dc2626;transform:translateY(-2px);}
.btn-secondary{background:var(--grey2);color:var(--dark);}
.btn-secondary:hover{background:#ddd;}
.btn-block{width:100%;justify-content:center;}
/* 2FA BADGE */
.twofa-badge{display:flex;align-items:center;gap:8px;padding:12px 16px;background:#f0fdf4;border:1px solid #d1fae5;border-radius:10px;margin-top:12px;}
.twofa-badge.disabled{background:var(--grey);border-color:var(--grey2);}
.twofa-badge i{font-size:18px;color:#10b981;}
.twofa-badge.disabled i{color:#aaa;}
.twofa-text{font-size:13px;font-weight:600;}
.twofa-text.disabled{color:#888;}
/* SIDEBAR */
.sidebar{position:fixed;left:0;right:0;bottom:0;height:72px;background:rgba(255,255,255,0.96);border-top:1px solid rgba(15,23,42,0.08);box-shadow:0 -10px 24px rgba(15,23,42,0.08);display:flex;align-items:center;justify-content:space-around;z-index:1000;padding:0 12px;backdrop-filter:blur(16px);}
.sidebar-header{display:none;}
.sidebar a{display:flex;flex-direction:column;align-items:center;gap:6px;color:var(--dark);text-decoration:none;font-size:12px;padding:8px 10px;border-radius:999px;transition:all 0.2s ease;}
.sidebar a i{font-size:18px;}
.sidebar a.active{color:var(--orange);background:rgba(255,140,66,0.15);}
.sidebar-nav{list-style:none;padding:0;margin:0;display:flex;gap:0.5rem;align-items:center;justify-content:center;}
.sidebar-nav li{margin:0;}
.sidebar-nav a{display:flex;flex-direction:column;align-items:center;padding:0.6rem 0.9rem;color:rgba(15,23,42,0.75);text-decoration:none;font-weight:600;transition:all 0.2s ease;border-top:3px solid transparent;background:transparent;border-radius:999px;}
.sidebar-nav a:hover{background:rgba(255,140,66,0.12);color:var(--dark);}
.sidebar-nav a.active{background:rgba(255,140,66,0.18);color:var(--orange);border-left-color:var(--orange);font-weight:700;}
.sidebar-nav a i{margin:0 0 0.3rem 0;font-size:1.2rem;width:auto;text-align:center;}
@media(max-width:768px){.settings-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <a href="dashboard.php" class="tb-logo">
        <div class="ic"><i class="fas fa-users"></i></div>
        <span>CyberAware Manager</span>
    </a>
    <div class="tb-right">
        <div class="tb-avatar-wrapper">
            <div class="tb-avatar" id="avatarBtn" title="<?= htmlspecialchars($manager_name) ?>"><?= strtoupper(substr($manager_name,0,1)) ?></div>
            <div class="dropdown-menu" id="dropdownMenu">
                <a href="profile.php" class="dropdown-item">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="../logout.php" class="dropdown-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>

<!-- MAIN -->
<main class="main">
    <div class="container">
        <!-- PAGE HEADER -->
        <div class="page-header">
            <h1><i class="fas fa-cog"></i> Settings</h1>
            <p>Customize your account and preferences</p>
        </div>

        <!-- SETTINGS GRID -->
        <div class="settings-grid">
            <!-- PROFILE CARD -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-user"></i></div>
                    <div>
                        <div class="card-title">Profile</div>
                        <div class="card-desc">Update your personal information</div>
                    </div>
                </div>
                <form>
                    <div class="form-group">
                        <label class="form-label">Display Name</label>
                        <input type="text" class="form-input" value="<?= htmlspecialchars($manager_name) ?>" placeholder="Your name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-input" value="<?= htmlspecialchars($manager_email) ?>" placeholder="your@email.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Change Password</label>
                        <input type="password" class="form-input" placeholder="Enter new password">
                        <div class="form-help">Leave blank to keep current password</div>
                    </div>
                </form>
            </div>

            <!-- NOTIFICATIONS CARD -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-bell"></i></div>
                    <div>
                        <div class="card-title">Notifications</div>
                        <div class="card-desc">Manage your notification preferences</div>
                    </div>
                </div>
                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">Team Activity Alerts</div>
                        <div class="toggle-desc">Notify when team members complete modules</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                </div>
                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">Incident Reports</div>
                        <div class="toggle-desc">Notify when incidents are reported</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                </div>
                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">Weekly Summary</div>
                        <div class="toggle-desc">Receive weekly team performance summary</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                </div>
                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">Low Completion Alerts</div>
                        <div class="toggle-desc">Alert when team members fall behind</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                </div>
                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">Email Notifications</div>
                        <div class="toggle-desc">Receive updates via email</div>
                    </div>
                    <div class="toggle-switch" onclick="toggleSwitch(this)"></div>
                </div>
            </div>

            <!-- TEAM PREFERENCES CARD -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="card-title">Team Preferences</div>
                        <div class="card-desc">Customize team management settings</div>
                    </div>
                </div>
                <form>
                    <div class="form-group">
                        <label class="form-label">Completion Target (%)</label>
                        <select class="form-select">
                            <option value="50">50% - Minimum</option>
                            <option value="75" selected>75% - Standard</option>
                            <option value="90">90% - Ambitious</option>
                            <option value="100">100% - Strict</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Report Frequency</label>
                        <select class="form-select">
                            <option value="daily">Daily</option>
                            <option value="weekly" selected>Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="toggle-group" style="border:none;padding:0;margin-top:16px;">
                        <div>
                            <div class="toggle-label">Auto-Assign Modules</div>
                            <div class="toggle-desc">Automatically assign new modules to team</div>
                        </div>
                        <div class="toggle-switch" onclick="toggleSwitch(this)"></div>
                    </div>
                </form>
            </div>

            <!-- PRIVACY & SECURITY CARD -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-shield-alt"></i></div>
                    <div>
                        <div class="card-title">Privacy & Security</div>
                        <div class="card-desc">Control your privacy settings</div>
                    </div>
                </div>
                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">View Team Progress</div>
                        <div class="toggle-desc">Allow viewing detailed team progress</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                </div>
                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">Export Reports</div>
                        <div class="toggle-desc">Allow exporting team reports</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSwitch(this)"></div>
                </div>
                <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--grey2);">
                    <div class="toggle-label" style="margin-bottom:12px;">Two-Factor Authentication</div>
                    <div class="twofa-badge disabled">
                        <i class="fas fa-lock"></i>
                        <div class="twofa-text disabled">Not enabled</div>
                    </div>
                </div>
            </div>

            <!-- ACCOUNT CARD -->
            <div class="settings-card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-trash"></i></div>
                    <div>
                        <div class="card-title">Account</div>
                        <div class="card-desc">Manage your account</div>
                    </div>
                </div>
                <p style="font-size:14px;color:#666;margin-bottom:20px;">Deleting your account is permanent and cannot be undone. All your data will be removed.</p>
                <button class="btn btn-danger btn-block" onclick="if(confirm('Are you sure? This cannot be undone.')) { alert('Account deletion would be processed here'); }">
                    <i class="fas fa-trash"></i> Delete Account
                </button>
            </div>
        </div>

        <!-- SAVE BUTTON -->
        <div style="margin-top:32px;display:flex;justify-content:flex-end;gap:12px;">
            <button class="btn btn-secondary" onclick="window.history.back()">Cancel</button>
            <button class="btn btn-primary" onclick="alert('Settings saved successfully!')">
                <i class="fas fa-check"></i> Save Changes
            </button>
        </div>
    </div>
</main>

<!-- SIDEBAR -->
<?php include 'manager-sidebar.php'; ?>

</body>
</html>

<script>
// Avatar dropdown toggle
const avatarBtn = document.getElementById('avatarBtn');
const dropdownMenu = document.getElementById('dropdownMenu');

if (avatarBtn && dropdownMenu) {
    avatarBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdownMenu.classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.tb-avatar-wrapper')) {
            dropdownMenu.classList.remove('active');
        }
    });

    // Close dropdown when clicking on a menu item
    const dropdownItems = dropdownMenu.querySelectorAll('.dropdown-item');
    dropdownItems.forEach(item => {
        item.addEventListener('click', function() {
            dropdownMenu.classList.remove('active');
        });
    });
}

function toggleSwitch(element) {
    element.classList.toggle('active');
}
</script>
