<?php
// admin/export-report.php - Export Summary Report as CSV (Fixed for PHP Deprecation)

require_once '../config/database.php';
require_once '../includes/functions.php';

require_admin(); // Only admins/managers

$pdo = getDBConnection();

// Fetch comprehensive report data
$stmt = $pdo->query("
    SELECT 
        u.id,
        u.username,
        u.full_name,
        u.email,
        d.name AS department,
        COALESCE(uts.total_sessions, 0) AS total_sessions,
        COALESCE(uts.completed_sessions, 0) AS completed_sessions,
        COALESCE(uts.phishing_clicks, 0) AS phishing_clicks,
        COALESCE(uts.phishing_reports, 0) AS phishing_reports,
        COALESCE(uts.risk_score, 100) AS risk_score,
        uts.last_training_date
    FROM users u
    LEFT JOIN departments d ON u.department_id = d.id
    LEFT JOIN user_training_summary uts ON u.id = uts.user_id
    WHERE u.role = 'trainee' AND u.is_active = 1
    ORDER BY d.name, u.username
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for clean CSV download (no notices in output)
$filename = "cyberaware-report-" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Explicitly provide $escape parameter to avoid deprecation warning
$escape = ''; // matches current default behavior

// CSV Header
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

// CSV Rows
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