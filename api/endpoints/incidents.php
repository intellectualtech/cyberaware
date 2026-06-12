<?php
/**
 * Incident Reporting Endpoints
 * 
 * POST   /api/incidents                    - Create incident report
 * GET    /api/incidents                    - Get all incidents (admin only)
 * GET    /api/incidents/{id}               - Get incident details
 * PUT    /api/incidents/{id}               - Update incident status (admin only)
 * POST   /api/incidents/{id}/comments      - Add comment to incident
 * GET    /api/incidents/{id}/comments      - Get incident comments
 */

function handleIncidentsRequest($method, $resource, $action, $api_key_data) {
    if ($method === 'GET') {
        if (empty($resource)) {
            getIncidents($api_key_data);
        } else {
            if ($action === 'comments') {
                getIncidentComments($resource);
            } else {
                getIncident($resource);
            }
        }
    }
    elseif ($method === 'POST') {
        if (empty($resource)) {
            createIncident();
        } elseif ($action === 'comments') {
            addIncidentComment($resource);
        }
        else {
            die(APIResponse::error('Invalid endpoint', 400));
        }
    }
    elseif ($method === 'PUT') {
        APIAuth::checkPermission('write', $api_key_data);
        updateIncident($resource);
    }
    else {
        die(APIResponse::error('Method not allowed', 405));
    }
}

/**
 * Create incident report
 */
function createIncident() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $errors = Validator::required($input, ['incident_type', 'subject', 'description']);
        if (!empty($errors)) {
            die(APIResponse::error('Validation failed', 400, $errors));
        }

        $pdo = getDBConnection();
        $user_id = $_SESSION['user_id'] ?? null;
        
        if (!$user_id) {
            die(APIResponse::error('User not authenticated', 401));
        }

        $incident_type = sanitize($input['incident_type']);
        $subject = sanitize($input['subject']);
        $description = sanitize($input['description']);
        $email_sender = sanitize($input['email_sender'] ?? '');
        $email_subject = sanitize($input['email_subject'] ?? '');
        $email_content = sanitize($input['email_content'] ?? '');

        // Validate incident type
        $valid_types = ['phishing', 'malware', 'suspicious_link', 'credential_theft', 'social_engineering', 'other'];
        if (!in_array($incident_type, $valid_types)) {
            die(APIResponse::error('Invalid incident type', 400));
        }

        // Determine severity
        $severity = 'medium';
        if (in_array($incident_type, ['phishing', 'credential_theft', 'malware'])) {
            $severity = 'high';
        }

        // Insert incident
        $stmt = $pdo->prepare("
            INSERT INTO incident_reports (
                user_id, incident_type, subject, description,
                email_sender, email_subject, email_content,
                severity, status, reported_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new', NOW())
        ");

        $stmt->execute([
            $user_id,
            $incident_type,
            $subject,
            $description,
            $email_sender ?: null,
            $email_subject ?: null,
            $email_content ?: null,
            $severity
        ]);

        $incident_id = $pdo->lastInsertId();

        // Get created incident
        $stmt = $pdo->prepare("
            SELECT id, user_id, incident_type, subject, status, severity, reported_at
            FROM incident_reports
            WHERE id = ?
        ");
        $stmt->execute([$incident_id]);
        $incident = $stmt->fetch(PDO::FETCH_ASSOC);

        die(APIResponse::success('Incident report created successfully', 201, $incident));

    } catch (Exception $e) {
        error_log("Create Incident Error: " . $e->getMessage());
        die(APIResponse::error('Failed to create incident report', 500));
    }
}

/**
 * Get all incidents (admin only)
 */
function getIncidents($api_key_data) {
    try {
        APIAuth::checkPermission('read', $api_key_data);
        
        $pdo = getDBConnection();
        
        // Check if user is admin
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'] ?? 0]);
        $user = $stmt->fetch();
        
        if (!$user || !in_array($user['role'], ['admin', 'manager'])) {
            die(APIResponse::error('Unauthorized', 403));
        }

        // Pagination
        $page = (int)($_GET['page'] ?? 1);
        $per_page = (int)($_GET['per_page'] ?? 10);
        $offset = ($page - 1) * $per_page;

        // Filters
        $where_clauses = [];
        $params = [];

        if (!empty($_GET['status'])) {
            $where_clauses[] = "status = ?";
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['severity'])) {
            $where_clauses[] = "severity = ?";
            $params[] = $_GET['severity'];
        }
        if (!empty($_GET['type'])) {
            $where_clauses[] = "incident_type = ?";
            $params[] = $_GET['type'];
        }

        $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

        // Get total count
        $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM incident_reports $where_sql");
        $count_stmt->execute($params);
        $total = $count_stmt->fetch()['total'];

        // Get incidents
        $stmt = $pdo->prepare("
            SELECT 
                ir.id, ir.user_id, ir.incident_type, ir.subject, ir.status, ir.severity,
                ir.reported_at, ir.reviewed_by, ir.reviewed_at,
                u.full_name, u.email
            FROM incident_reports ir
            JOIN users u ON ir.user_id = u.id
            $where_sql
            ORDER BY ir.reported_at DESC
            LIMIT ? OFFSET ?
        ");

        $params[] = $per_page;
        $params[] = $offset;
        $stmt->execute($params);
        $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [
            'incidents' => $incidents,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page)
            ]
        ];

        die(APIResponse::success('Incidents retrieved successfully', 200, $response));

    } catch (Exception $e) {
        error_log("Get Incidents Error: " . $e->getMessage());
        die(APIResponse::error('Failed to retrieve incidents', 500));
    }
}

/**
 * Get single incident
 */
function getIncident($incident_id) {
    try {
        $pdo = getDBConnection();
        $user_id = $_SESSION['user_id'] ?? 0;

        $stmt = $pdo->prepare("
            SELECT 
                ir.*,
                u.full_name, u.email, u.department_id,
                d.name as department
            FROM incident_reports ir
            JOIN users u ON ir.user_id = u.id
            LEFT JOIN departments d ON u.department_id = d.id
            WHERE ir.id = ?
        ");
        $stmt->execute([$incident_id]);
        $incident = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$incident) {
            die(APIResponse::error('Incident not found', 404));
        }

        // Check authorization (own report or admin)
        $user_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch();

        if ($incident['user_id'] !== $user_id && !in_array($user['role'] ?? '', ['admin', 'manager'])) {
            die(APIResponse::error('Unauthorized', 403));
        }

        die(APIResponse::success('Incident retrieved successfully', 200, $incident));

    } catch (Exception $e) {
        error_log("Get Incident Error: " . $e->getMessage());
        die(APIResponse::error('Failed to retrieve incident', 500));
    }
}

/**
 * Update incident status (admin only)
 */
function updateIncident($incident_id) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $pdo = getDBConnection();
        $admin_id = $_SESSION['user_id'] ?? 0;

        // Check admin role
        $user_stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $user_stmt->execute([$admin_id]);
        $user = $user_stmt->fetch();

        if (!in_array($user['role'] ?? '', ['admin', 'manager'])) {
            die(APIResponse::error('Unauthorized', 403));
        }

        $status = sanitize($input['status'] ?? '');
        $resolution_notes = sanitize($input['resolution_notes'] ?? '');

        if (!in_array($status, ['new', 'under_review', 'resolved', 'false_positive'])) {
            die(APIResponse::error('Invalid status', 400));
        }

        $stmt = $pdo->prepare("
            UPDATE incident_reports
            SET status = ?, reviewed_by = ?, reviewed_at = NOW(), resolution_notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $admin_id, $resolution_notes, $incident_id]);

        if ($stmt->rowCount() === 0) {
            die(APIResponse::error('Incident not found', 404));
        }

        die(APIResponse::success('Incident updated successfully', 200));

    } catch (Exception $e) {
        error_log("Update Incident Error: " . $e->getMessage());
        die(APIResponse::error('Failed to update incident', 500));
    }
}

/**
 * Add comment to incident
 */
function addIncidentComment($incident_id) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $pdo = getDBConnection();
        $user_id = $_SESSION['user_id'] ?? 0;

        if (empty($input['comment_text'])) {
            die(APIResponse::error('Comment text is required', 400));
        }

        $comment_text = sanitize($input['comment_text']);

        $stmt = $pdo->prepare("
            INSERT INTO incident_report_comments (incident_id, user_id, comment_text, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$incident_id, $user_id, $comment_text]);

        $comment_id = $pdo->lastInsertId();

        die(APIResponse::success('Comment added successfully', 201, ['comment_id' => $comment_id]));

    } catch (Exception $e) {
        error_log("Add Comment Error: " . $e->getMessage());
        die(APIResponse::error('Failed to add comment', 500));
    }
}

/**
 * Get incident comments
 */
function getIncidentComments($incident_id) {
    try {
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("
            SELECT 
                irc.id, irc.incident_id, irc.user_id, irc.comment_text, irc.created_at,
                u.full_name, u.email
            FROM incident_report_comments irc
            JOIN users u ON irc.user_id = u.id
            WHERE irc.incident_id = ?
            ORDER BY irc.created_at DESC
        ");
        $stmt->execute([$incident_id]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        die(APIResponse::success('Comments retrieved successfully', 200, ['comments' => $comments]));

    } catch (Exception $e) {
        error_log("Get Comments Error: " . $e->getMessage());
        die(APIResponse::error('Failed to retrieve comments', 500));
    }
}
