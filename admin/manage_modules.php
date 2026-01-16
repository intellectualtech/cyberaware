<?php
// admin/manage_modules.php - Manage Training Module Content (Videos + Rich Content)

require_once '../config/database.php';
require_once '../includes/functions.php';
require_admin();

$pdo = getDBConnection();

// Ensure upload directory exists
$upload_dir = '../assets/videos/modules/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $module_id = (int)($_POST['module_id'] ?? 0);
    $content_html = $_POST['content_html'] ?? '';
    $video_file = $_FILES['module_video'] ?? null;

    if ($module_id <= 0) {
        $message = "Invalid module selected.";
        $message_type = 'danger';
    } else {
        try {
            $pdo->beginTransaction();

            // Update content_html
            $stmt = $pdo->prepare("UPDATE training_modules SET content_html = ? WHERE id = ?");
            $stmt->execute([$content_html, $module_id]);

            // Handle video upload
            if ($video_file && $video_file['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['video/mp4', 'video/webm', 'video/ogg'];
                $max_size = 500 * 1024 * 1024; // 500MB max

                if (!in_array($video_file['type'], $allowed_types)) {
                    throw new Exception("Only MP4, WebM, or OGG videos allowed.");
                }
                if ($video_file['size'] > $max_size) {
                    throw new Exception("Video file too large (max 500MB).");
                }

                // Get current video path to delete old one
                $stmt = $pdo->prepare("SELECT video_path FROM training_modules WHERE id = ?");
                $stmt->execute([$module_id]);
                $old_path = $stmt->fetchColumn();

                // Generate unique filename
                $ext = pathinfo($video_file['name'], PATHINFO_EXTENSION);
                $filename = 'module_' . $module_id . '_' . time() . '.' . $ext;
                $target_path = $upload_dir . $filename;

                if (move_uploaded_file($video_file['tmp_name'], $target_path)) {
                    // Update DB with new path (relative for web access)
                    $web_path = 'assets/videos/modules/' . $filename;
                    $stmt = $pdo->prepare("UPDATE training_modules SET video_path = ? WHERE id = ?");
                    $stmt->execute([$web_path, $module_id]);

                    // Delete old video if exists
                    if ($old_path && file_exists('../' . $old_path)) {
                        unlink('../' . $old_path);
                    }
                } else {
                    throw new Exception("Failed to upload video.");
                }
            }

            $pdo->commit();
            $message = "Module content updated successfully!";
            $message_type = 'success';

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Fetch all modules
$stmt = $pdo->query("SELECT id, code, title, description, video_path, content_html FROM training_modules WHERE is_active = 1 ORDER BY id");
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// For editing, pre-select first module or from POST
$selected_module = null;
if (!empty($_POST['module_id'])) {
    foreach ($modules as $m) {
        if ($m['id'] == $_POST['module_id']) {
            $selected_module = $m;
            break;
        }
    }
}
if (!$selected_module && !empty($modules)) {
    $selected_module = $modules[0]; // default to first
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Module Content – CyberAware Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF8C42;
            --primary-dark: #E67A2E;
            --success: #10b981;
            --danger: #ef4444;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --sidebar-width: 260px;
        }
        body { font-family:'Inter',sans-serif; background:var(--gray-100); margin:0; }
        .main-content { margin-left: var(--sidebar-width); padding:2rem; }
        .container { max-width:1200px; margin:0 auto; }
        .card { background:white; padding:2rem; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1); margin-bottom:2rem; }
        .message { padding:1rem; border-radius:8px; margin-bottom:1.5rem; }
        .message-success { background:#ecfdf5; color:#065f46; border-left:4px solid var(--success); }
        .message-danger { background:#fef2f2; color:#991b1b; border-left:4px solid var(--danger); }
        .form-group { margin-bottom:1.5rem; }
        label { display:block; margin-bottom:0.5rem; font-weight:600; }
        select, textarea, input[type="file"] {
            width:100%; padding:0.75rem; border:1px solid #d1d5db; border-radius:6px;
        }
        textarea { min-height:300px; font-family:monospace; }
        .btn { padding:0.8rem 1.5rem; background:var(--primary); color:white; border:none; border-radius:6px; cursor:pointer; }
        .btn:hover { background:var(--primary-dark); }
        .video-preview {
            margin-top:1rem;
            max-width:100%;
            border-radius:8px;
            box-shadow:0 4px 12px rgba(0,0,0,0.15);
        }
        .module-selector {
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(200px,1fr));
            gap:1rem;
            margin-bottom:2rem;
        }
        .module-option {
            padding:1rem;
            background:#f9fafb;
            border-radius:8px;
            text-align:center;
            cursor:pointer;
            border:2px solid transparent;
            transition:all 0.3s;
        }
        .module-option:hover, .module-option.selected {
            border-color:var(--primary);
            background:#fff4ed;
        }
        @media (max-width:992px) { .main-content { margin-left:0; } }
    </style>
</head>
<body>

<?php include 'admin-sidebar.php'; ?>

<main class="main-content">
    <div class="container">
        <h1 style="font-size:2.2rem; margin-bottom:1.5rem;">Manage Training Module Content</h1>

        <?php if ($message): ?>
        <div class="message message-<?= $message_type ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-bottom:1.5rem;">Select Module to Edit</h3>
            <div class="module-selector">
                <?php foreach ($modules as $m): ?>
                <div class="module-option <?= $selected_module && $selected_module['id'] == $m['id'] ? 'selected' : '' ?>"
                     onclick="document.getElementById('module_<?= $m['id'] ?>').submit()">
                    <form id="module_<?= $m['id'] ?>" method="POST">
                        <input type="hidden" name="module_id" value="<?= $m['id'] ?>">
                        <strong><?= htmlspecialchars($m['title']) ?></strong><br>
                        <small><?= htmlspecialchars($m['code']) ?></small>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($selected_module): ?>
        <div class="card">
            <h3>Editing: <?= htmlspecialchars($selected_module['title']) ?></h3>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="module_id" value="<?= $selected_module['id'] ?>">

                <div class="form-group">
                    <label>Module Description (shown in list)</label>
                    <textarea name="content_html" placeholder="Optional short description..."><?= htmlspecialchars($selected_module['description'] ?? '') ?></textarea>
                    <small>This is the short description shown in module lists.</small>
                </div>

                <div class="form-group">
                    <label>Full Training Content (HTML)</label>
                    <textarea name="content_html" placeholder="&lt;h2&gt;Welcome&lt;/h2&gt;&lt;p&gt;Your training content here...&lt;/p&gt;"><?= htmlspecialchars($selected_module['content_html'] ?? '') ?></textarea>
                    <small>Use HTML for formatting, headings, lists, images, etc.</small>
                </div>

                <div class="form-group">
                    <label>Training Video (MP4/WebM/OGG - max 500MB)</label>
                    <input type="file" name="module_video" accept="video/*">
                    <?php if ($selected_module['video_path']): ?>
                    <div style="margin-top:1rem;">
                        <strong>Current Video:</strong>
                        <video class="video-preview" controls>
                            <source src="../<?= htmlspecialchars($selected_module['video_path']) ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                        <p><small>Upload a new video to replace this one.</small></p>
                    </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</main>

</body>
</html>