<?php
// admin/export-report.php - Export Summary Report as CSV

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/theme-helper.php';

require_admin();

$pdo = getDBConnection();
$admin_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();
$admin_name = $admin['full_name'] ?? 'Admin';

// Check if export is requested
$export_requested = isset($_GET['export']) && $_GET['export'] === 'true';

if ($export_requested) {
    // Fetch comprehensive report data
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.username,
            u.full_name,
            u.email,
            d.name AS department,
            COUNT(DISTINCT ts.id) AS total_sessions,
            COUNT(DISTINCT CASE WHEN ts.completed_at IS NOT NULL THEN ts.id END) AS completed_sessions,
            SUM(CASE WHEN ts.module_id = 1 AND ta.action_type IN ('click', 'clicked') THEN 1 ELSE 0 END) AS phishing_clicks,
            SUM(CASE WHEN ts.module_id = 1 AND ta.action_type IN ('report', 'reported') THEN 1 ELSE 0 END) AS phishing_reports,
            CASE 
                WHEN AVG(ts.final_score) IS NULL THEN 100
                WHEN AVG(ts.final_score) < 60 THEN 80
                WHEN AVG(ts.final_score) < 70 THEN 60
                WHEN AVG(ts.final_score) < 80 THEN 40
                ELSE 20
            END AS risk_score,
            MAX(ts.completed_at) AS last_training_date
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN training_sessions ts ON u.id = ts.user_id
        LEFT JOIN training_actions ta ON ts.id = ta.session_id
        WHERE u.role = 'trainee' AND u.is_active = 1
        GROUP BY u.id, u.username, u.full_name, u.email, d.name
        ORDER BY d.name, u.username
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Try to use PhpSpreadsheet for better formatting, fallback to CSV if not available
    $use_excel = false;
    if (file_exists('../vendor/autoload.php')) {
        try {
            require_once '../vendor/autoload.php';
            $use_excel = true;
        } catch (Exception $e) {
            $use_excel = false;
        }
    }

    if ($use_excel) {
        // Use PhpSpreadsheet for Excel format
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Training Report');

        // Define headers
        $headers = [
            'User ID',
            'Username',
            'Full Name',
            'Email',
            'Department',
            'Total Sessions',
            'Completed Sessions',
            'Phishing Clicks',
            'Phishing Reports',
            'Risk Score',
            'Last Training Date'
        ];

        // Add headers with formatting
        $sheet->fromArray([$headers], null, 'A1');
        
        // Style header row
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FF8C42'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ];
        
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Add data rows
        $row = 2;
        foreach ($rows as $data) {
            $sheet->setCellValue('A' . $row, $data['id']);
            $sheet->setCellValue('B' . $row, $data['username']);
            $sheet->setCellValue('C' . $row, $data['full_name'] ?: '');
            $sheet->setCellValue('D' . $row, $data['email'] ?: '');
            $sheet->setCellValue('E' . $row, $data['department'] ?: 'No Department');
            $sheet->setCellValue('F' . $row, $data['total_sessions']);
            $sheet->setCellValue('G' . $row, $data['completed_sessions']);
            $sheet->setCellValue('H' . $row, $data['phishing_clicks']);
            $sheet->setCellValue('I' . $row, $data['phishing_reports']);
            $sheet->setCellValue('J' . $row, $data['risk_score']);
            $sheet->setCellValue('K' . $row, $data['last_training_date'] ? date('M j, Y', strtotime($data['last_training_date'])) : 'Never');
            
            // Style data rows
            $dataStyle = [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'EEEEEE'],
                    ],
                ],
                'font' => [
                    'size' => 11,
                ],
            ];
            
            $sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray($dataStyle);
            $sheet->getRowDimension($row)->setRowHeight(20);
            
            // Alternate row colors for better readability
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':K' . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F9FAFB');
            }
            
            $row++;
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(16);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(12);
        $sheet->getColumnDimension('K')->setWidth(18);

        // Freeze header row
        $sheet->freezePane('A2');

        // Generate Excel file
        $filename = "cyberaware-report-" . date('Y-m-d') . ".xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } else {
        // Fallback to CSV format
        $filename = "cyberaware-report-" . date('Y-m-d') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        $escape = '';

        fputcsv($output, [
            'User ID',
            'Username',
            'Full Name',
            'Email',
            'Department',
            'Total Sessions',
            'Completed Sessions',
            'Phishing Clicks',
            'Phishing Reports',
            'Risk Score (lower = better)',
            'Last Training Date'
        ], ',', '"', $escape);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['id'],
                $row['username'],
                $row['full_name'] ?: '',
                $row['email'] ?: '',
                $row['department'] ?: 'No Department',
                $row['total_sessions'],
                $row['completed_sessions'],
                $row['phishing_clicks'],
                $row['phishing_reports'],
                $row['risk_score'],
                $row['last_training_date'] ? date('M j, Y', strtotime($row['last_training_date'])) : 'Never'
            ], ',', '"', $escape);
        }

        fclose($output);
        exit;
    }
}

// Get preview data for the UI
$preview_stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT u.id) as total_users,
        COUNT(DISTINCT d.id) as total_departments,
        COUNT(DISTINCT ts.id) as total_sessions,
        COUNT(DISTINCT CASE WHEN ts.completed_at IS NOT NULL THEN ts.id END) as completed_sessions
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN training_sessions ts ON u.id = ts.user_id
    WHERE u.role = 'trainee' AND u.is_active = 1
");
$preview = $preview_stmt->fetch(PDO::FETCH_ASSOC);

$current_page = 'export-report';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Export Report — CyberAware</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../trainee/theme-styles.css">
<script>
(function() {
    const theme = localStorage.getItem('theme') || 'light';
    if (theme === 'dark') {
        document.documentElement.classList.add('dark-mode-init');
    }
})();
</script>
<style>
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
.tb-dropdown-item.logout i{color:var(--red);}
.layout{display:grid;grid-template-columns:280px 1fr;min-height:calc(100vh - 64px);}
.layout.sidebar-hidden{grid-template-columns:1fr;}
.sidebar{background:#fff;border-right:1px solid var(--grey2);padding:28px 20px;display:flex;flex-direction:column;gap:8px;}
.sidebar.hidden{display:none;}
.toggle-sidebar{position:absolute;top:20px;right:20px;background:var(--orange);color:#fff;border:none;width:36px;height:36px;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;transition:.2s;z-index:50;}
.toggle-sidebar:hover{background:#e67e2f;}
.show-sidebar{position:fixed;bottom:20px;left:20px;background:var(--orange);border:none;color:#fff;border-radius:50%;width:50px;height:50px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:20px;box-shadow:0 4px 12px rgba(255,140,66,.3);transition:.2s;z-index:99;opacity:0;visibility:hidden;pointer-events:none;}
.show-sidebar.visible{opacity:1;visibility:visible;pointer-events:auto;}
.show-sidebar:hover{background:#e67e2f;transform:scale(1.1);}
body.dark-mode .toggle-sidebar{background:var(--orange);}
body.dark-mode .toggle-sidebar:hover{background:#e67e2f;}
body.dark-mode .show-sidebar{background:var(--orange);}
body.dark-mode .show-sidebar:hover{background:#e67e2f;}
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
.section-hdr{margin-bottom:16px;}
.section-hdr h2{font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:700;color:var(--dark);}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:28px;}
.kpi-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.05);border-left:4px solid var(--orange);transition:.2s;text-decoration:none;color:inherit;display:block;}
.kpi-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.1);}
.kpi-label{font-size:13px;color:#888;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;}
.kpi-value{font-size:32px;font-weight:800;color:var(--dark);margin-bottom:6px;}
.kpi-desc{font-size:13px;color:#aaa;}
.card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.05);}
.card-header{margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--grey2);}
.card-title{font-family:'Space Grotesk',sans-serif;font-size:18px;font-weight:700;color:var(--dark);display:flex;align-items:center;gap:10px;}
.card-title i{color:var(--orange);}
.feature-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-top:16px;}
.feature-item{padding:16px;background:var(--grey);border-radius:10px;border-left:4px solid var(--orange);}
.feature-item i{color:var(--orange);font-size:20px;margin-bottom:8px;display:block;}
.feature-item strong{display:block;font-size:14px;color:var(--dark);margin-bottom:4px;}
.feature-item p{font-size:13px;color:#888;margin:0;}
.btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;border:none;cursor:pointer;transition:.2s;}
.btn-primary{background:var(--orange);color:#fff;}
.btn-primary:hover{background:#e67e2f;transform:translateY(-2px);box-shadow:0 8px 16px rgba(255,140,66,.3);}
.info-box{background:var(--ol);border-left:4px solid var(--orange);padding:16px 20px;border-radius:10px;margin-top:24px;}
.info-box p{font-size:14px;color:var(--dark);margin-bottom:8px;}
.info-box ul{margin-left:20px;color:var(--dark);font-size:14px;}
.info-box li{margin-bottom:6px;}
body.dark-mode{background:#1A1A2E;color:#fff;}
body.dark-mode .topbar{background:#0f0f1e;}
body.dark-mode .sidebar{background:#1f1f2e;border-right-color:#333;}
body.dark-mode .nav-item{color:#aaa;}
body.dark-mode .nav-item:hover,body.dark-mode .nav-item.active{background:rgba(255,140,66,.15);color:var(--orange);}
body.dark-mode .sb-profile{background:rgba(255,140,66,.1);}
body.dark-mode .sb-name{color:#fff;}
body.dark-mode .sb-role{color:#aaa;}
body.dark-mode .main{background:#1A1A2E;}
body.dark-mode .card{background:#2a2a3e;box-shadow:0 2px 8px rgba(0,0,0,.3);}
body.dark-mode .card-title{color:#fff;}
body.dark-mode .kpi-card{background:#2a2a3e;box-shadow:0 2px 8px rgba(0,0,0,.3);}
body.dark-mode .kpi-card:hover{box-shadow:0 12px 32px rgba(0,0,0,.5);}
body.dark-mode .kpi-value{color:#fff;}
body.dark-mode .kpi-label{color:#888;}
body.dark-mode .kpi-desc{color:#aaa;}
body.dark-mode .section-hdr h2{color:#fff;}
body.dark-mode .card-header{border-bottom-color:#333;}
body.dark-mode .tb-dropdown{background:#2a2a3e;}
body.dark-mode .tb-dropdown-header{border-bottom-color:#333;}
body.dark-mode .tb-dropdown-name{color:#fff;}
body.dark-mode .tb-dropdown-item{color:#fff;}
body.dark-mode .tb-dropdown-item:hover{background:#333;color:var(--orange);}
body.dark-mode .feature-item{background:#333;}
body.dark-mode .feature-item strong{color:#fff;}
body.dark-mode .info-box{background:rgba(255,140,66,.1);}
body.dark-mode .info-box p,body.dark-mode .info-box ul{color:#ddd;}
@media(max-width:900px){.layout{grid-template-columns:1fr;}.sidebar{display:none;}}
</style>
</head>
<body class="<?= getThemeClass() ?>">

<div class="topbar">
    <a href="dashboard.php" class="tb-logo">
        <div class="ic"><i class="fas fa-shield-alt"></i></div>
        <span>CyberAware</span>
    </a>
    <div class="tb-right">
        <div class="tb-avatar-wrapper">
            <div class="tb-avatar" onclick="toggleDropdown()"><?= strtoupper(substr($admin_name,0,1)) ?></div>
            <div class="tb-dropdown" id="profileDropdown">
                <div class="tb-dropdown-header">
                    <div class="tb-dropdown-user">
                        <div class="tb-dropdown-avatar"><?= strtoupper(substr($admin_name,0,1)) ?></div>
                        <div>
                            <div class="tb-dropdown-name"><?= htmlspecialchars($admin_name) ?></div>
                            <div class="tb-dropdown-email"><?= htmlspecialchars($admin['email'] ?? '') ?></div>
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

<button id="showSidebarBtn" class="show-sidebar" onclick="toggleSidebar()" title="Show Sidebar">
    <i class="fas fa-chevron-right"></i>
</button>

<div class="layout" id="mainLayout">
    <aside class="sidebar" id="sidebarPanel">
        <button class="toggle-sidebar" onclick="toggleSidebar()" title="Hide Sidebar">
            <i class="fas fa-chevron-left"></i>
        </button>
        <div class="sb-profile">
            <div class="sb-avatar"><?= strtoupper(substr($admin_name,0,1)) ?></div>
            <div class="sb-name"><?= htmlspecialchars($admin_name) ?></div>
            <div class="sb-role">Administrator</div>
            <div class="sb-level">Admin Panel</div>
        </div>
        <div class="sb-section">Management</div>
        <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
        <a href="campaigns.php" class="nav-item"><i class="fas fa-calendar"></i> Manage Campaigns</a>
        <a href="manage-users.php" class="nav-item"><i class="fas fa-users"></i> Manage Users</a>
        <a href="manage_modules.php" class="nav-item"><i class="fas fa-book"></i> Manage Modules</a>
        <a href="department-scores.php" class="nav-item"><i class="fas fa-chart-line"></i> Department Scores</a>
        <a href="risk-heatmap.php" class="nav-item"><i class="fas fa-fire"></i> Risk Heatmap</a>
        <a href="compliance-snapshot.php" class="nav-item"><i class="fas fa-check-circle"></i> Compliance Snapshot</a>
        <a href="export-report.php" class="nav-item active"><i class="fas fa-file-export"></i> Export Report</a>
        <a href="incident-reports.php" class="nav-item"><i class="fas fa-exclamation-circle"></i> Incident Reports</a>
        <div class="sb-section">Account</div>
        <a href="../logout.php" class="nav-item nav-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <main class="main">
        <div class="section-hdr">
            <h2>Export Training Report</h2>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Active Trainees</div>
                <div class="kpi-value"><?= $preview['total_users'] ?></div>
                <div class="kpi-desc">Users in system</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Departments</div>
                <div class="kpi-value"><?= $preview['total_departments'] ?></div>
                <div class="kpi-desc">Organization units</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Training Sessions</div>
                <div class="kpi-value"><?= $preview['total_sessions'] ?></div>
                <div class="kpi-desc">Total sessions</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Completed</div>
                <div class="kpi-value"><?= $preview['completed_sessions'] ?></div>
                <div class="kpi-desc">Finished trainings</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-box"></i> What's Included</h3>
            </div>
            <div class="feature-grid">
                <div class="feature-item">
                    <i class="fas fa-user"></i>
                    <strong>User Information</strong>
                    <p>ID, username, name, email, department</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-graduation-cap"></i>
                    <strong>Training Progress</strong>
                    <p>Sessions, completions, scores</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-fish"></i>
                    <strong>Phishing Metrics</strong>
                    <p>Clicks, reports, responses</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-chart-line"></i>
                    <strong>Risk Assessment</strong>
                    <p>Calculated risk scores</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-calendar"></i>
                    <strong>Activity Timeline</strong>
                    <p>Last training dates</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-file-csv"></i>
                    <strong>CSV Format</strong>
                    <p>Excel, Sheets, analytics</p>
                </div>
            </div>
        </div>

        <div class="info-box">
            <p><strong>Use Cases</strong></p>
            <ul>
                <li>Compliance audits and regulatory reporting</li>
                <li>Management reporting and dashboards</li>
                <li>Performance analysis by department</li>
                <li>Trend analysis and improvements</li>
                <li>Risk assessment and prioritization</li>
            </ul>
        </div>

        <div class="card" style="margin-top:28px;text-align:center;">
            <h3 style="margin-bottom:12px;color:var(--dark);">Ready to Export?</h3>
            <p style="color:#888;margin-bottom:20px;font-size:14px;">Download comprehensive training and security awareness data in CSV format.</p>
            <a href="?export=true" class="btn btn-primary">
                <i class="fas fa-download"></i> Download Report (CSV)
            </a>
        </div>
    </main>
</div>

</body>
</html>

<script>
function toggleDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('active');
}

document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('profileDropdown');
    const avatar = document.querySelector('.tb-avatar');
    
    if (!dropdown.contains(event.target) && !avatar.contains(event.target)) {
        dropdown.classList.remove('active');
    }
});

function toggleSidebar() {
    const sidebar = document.getElementById('sidebarPanel');
    const layout = document.getElementById('mainLayout');
    const showBtn = document.getElementById('showSidebarBtn');
    
    sidebar.classList.toggle('hidden');
    layout.classList.toggle('sidebar-hidden');
    showBtn.classList.toggle('visible');
    
    localStorage.setItem('sidebarHidden', sidebar.classList.contains('hidden') ? 'true' : 'false');
}

document.addEventListener('DOMContentLoaded', function() {
    const sidebarHidden = localStorage.getItem('sidebarHidden') === 'true';
    if (sidebarHidden) {
        const sidebar = document.getElementById('sidebarPanel');
        const layout = document.getElementById('mainLayout');
        const showBtn = document.getElementById('showSidebarBtn');
        
        sidebar.classList.add('hidden');
        layout.classList.add('sidebar-hidden');
        showBtn.classList.add('visible');
    }
});

document.querySelectorAll('.tb-dropdown-item').forEach(item => {
    item.addEventListener('click', function() {
        document.getElementById('profileDropdown').classList.remove('active');
    });
});
</script>