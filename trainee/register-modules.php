<?php
// trainee/register-modules.php - Register for modules

require_once '../config/database.php';
require_once '../includes/functions.php';
require_login();

if ($_SESSION['role'] !== 'trainee') {
    header('Location: ../pages/login.php');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modules'])) {
    $selected_modules = array_map('intval', $_POST['modules'] ?? []);
    
    try {
        $pdo->beginTransaction();
        
        // Clear existing registrations
        $pdo->prepare("DELETE FROM user_module_registrations WHERE user_id = ?")->execute([$user_id]);
        
        // Insert new registrations in order
        $stmt = $pdo->prepare("
            INSERT INTO user_module_registrations (user_id, module_id, registration_order, status)
            VALUES (?, ?, ?, 'pending')
        ");
        
        foreach ($selected_modules as $order => $module_id) {
            $stmt->execute([$user_id, $module_id, $order + 1]);
        }
        
        // Mark first module as in_progress
        if (!empty($selected_modules)) {
            $pdo->prepare("
                UPDATE user_module_registrations 
                SET status = 'in_progress', started_at = NOW()
                WHERE user_id = ? AND registration_order = 1
            ")->execute([$user_id]);
        }
        
        $pdo->commit();
        $success = "Modules registered successfully! Redirecting to your learning path...";
        // Redirect to learning modules page after 1 second
        header("refresh:1;url=learning-modules.php");
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error registering modules: " . $e->getMessage();
    }
}

// Get all available modules
$stmt = $pdo->query("SELECT id, title, description, category, difficulty, estimated_minutes FROM training_modules WHERE is_active = 1 ORDER BY id");
$all_modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's requested modules from contact_requests
$stmt = $pdo->prepare("
    SELECT requested_modules FROM contact_requests 
    WHERE email = (SELECT email FROM users WHERE id = ?)
    ORDER BY created_at DESC LIMIT 1
");
$stmt->execute([$user_id]);
$contact_request = $stmt->fetch(PDO::FETCH_ASSOC);
$requested_module_ids = [];

if ($contact_request && !empty($contact_request['requested_modules'])) {
    $requested_module_ids = array_map('intval', explode(',', $contact_request['requested_modules']));
}

// Filter modules to only show requested ones
$available_modules = array_filter($all_modules, function($module) use ($requested_module_ids) {
    return empty($requested_module_ids) || in_array($module['id'], $requested_module_ids);
});

// Get user's current registrations
$stmt = $pdo->prepare("
    SELECT module_id, registration_order 
    FROM user_module_registrations 
    WHERE user_id = ? 
    ORDER BY registration_order
");
$stmt->execute([$user_id]);
$registered = $stmt->fetchAll(PDO::FETCH_ASSOC);
$registered_ids = array_column($registered, 'module_id');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for Modules – CyberAware</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --orange: #FF8C42;
            --dark: #1A1A2E;
            --grey: #F5F5F5;
            --text: #333;
        }
        body {
            font-family: 'Manrope', sans-serif;
            background: linear-gradient(135deg, #FFF4EC 0%, #fff 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding-bottom: 100px;
        }
        }
        .header {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .header h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            font-size: 15px;
        }
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .module-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 2px solid transparent;
            transition: all 0.3s;
            cursor: pointer;
            display: block;
        }
        .module-card:hover {
            border-color: var(--orange);
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        .module-card input[type="checkbox"] {
            display: none;
        }
        .module-card input[type="checkbox"]:checked ~ .card-content {
            background: var(--orange);
            color: white;
        }
        .module-card input[type="checkbox"]:checked ~ .card-content .module-title {
            color: white;
        }
        .module-card input[type="checkbox"]:checked ~ .card-content .module-meta {
            color: rgba(255, 255, 255, 0.8);
        }
        .module-card input[type="checkbox"]:checked {
            border-color: var(--orange);
        }
        .module-card.selected {
            border-color: var(--orange);
        }
        .module-card.selected .card-content {
            background: var(--orange);
            color: white;
        }
        .module-card.selected .card-content .module-title {
            color: white;
        }
        .module-card.selected .card-content .module-meta {
            color: rgba(255, 255, 255, 0.8);
        }
        .card-content {
            padding: 10px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .module-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .module-title {
            font-weight: 700;
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 8px;
        }
        .module-meta {
            font-size: 12px;
            color: #888;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .module-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .selected-order {
            background: white;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .selected-order h3 {
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 15px;
        }
        .order-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .order-item {
            background: var(--grey);
            padding: 12px 16px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .order-number {
            background: var(--orange);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }
        .order-item-title {
            flex: 1;
            font-weight: 600;
            color: var(--dark);
        }
        .order-item-remove {
            background: #fee2e2;
            color: #991b1b;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 999px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-primary {
            background: var(--orange);
            color: white;
        }
        .btn-primary:hover {
            background: #E67A2E;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: var(--grey);
            color: var(--text);
        }
        .btn-secondary:hover {
            background: #e8e8e8;
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

<?php include 'trainee-sidebar.php'; ?>

<div class="container">
    <div class="header">
        <h1>Register for Training Modules</h1>
        <p>Select the modules you want to complete. You'll complete them in order, and each module must be passed to unlock the next one.</p>
    </div>

    <?php if (isset($success)): ?>
        <div class="message success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="message error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($available_modules)): ?>
        <div class="message error">
            <i class="fas fa-info-circle"></i>
            No modules available. Please submit a demo request first to select your training modules.
        </div>
        <div class="actions" style="margin-top: 20px;">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="../contact.php" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Request Demo
            </a>
        </div>
    <?php else: ?>

    <form method="POST" id="registrationForm">
        <div class="modules-grid">
            <?php foreach ($available_modules as $module): ?>
                <label class="module-card" onclick="toggleModule(event, <?= $module['id'] ?>)">
                    <input type="checkbox" name="modules[]" value="<?= $module['id'] ?>" class="module-checkbox"
                        <?= in_array($module['id'], $registered_ids) ? 'checked' : '' ?>>
                    <div class="card-content">
                        <div class="module-icon">
                            <?php
                                $icons = [
                                    'phishing' => 'fa-envelope',
                                    'credential' => 'fa-key',
                                    'social' => 'fa-phone-alt',
                                    'malware' => 'fa-virus',
                                    'link' => 'fa-link',
                                    'password' => 'fa-lock',
                                    'ransomware' => 'fa-exclamation-triangle'
                                ];
                                $icon_class = $icons[$module['category']] ?? 'fa-book';
                            ?>
                            <i class="fas <?= $icon_class ?>"></i>
                        </div>
                        <div class="module-title"><?= htmlspecialchars($module['title']) ?></div>
                        <div class="module-meta">
                            <span><i class="fas fa-clock"></i> <?= $module['estimated_minutes'] ?> min</span>
                            <span><i class="fas fa-signal"></i> Level <?= $module['difficulty'] ?></span>
                        </div>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="selected-order" id="selectedOrder" style="display: none;">
            <h3>Your Learning Path (in order)</h3>
            <div class="order-list" id="orderList">
            </div>
        </div>

        <div class="actions">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <button type="submit" class="btn btn-primary" id="submitBtn" <?= empty($available_modules) ? 'disabled' : '' ?>>
                <i class="fas fa-save"></i> Save Registration
            </button>
        </div>
    </form>

    <script>
        function toggleModule(event, moduleId) {
            // Prevent default label behavior
            event.preventDefault();
            
            // Find the checkbox in this label
            const checkbox = event.currentTarget.querySelector('.module-checkbox');
            checkbox.checked = !checkbox.checked;
            
            // Update visual state
            if (checkbox.checked) {
                event.currentTarget.classList.add('selected');
            } else {
                event.currentTarget.classList.remove('selected');
            }
            
            // Update the learning path display
            updateLearningPath();
        }

        function updateLearningPath() {
            const form = document.getElementById('registrationForm');
            const checkboxes = form.querySelectorAll('.module-checkbox:checked');
            const orderList = document.getElementById('orderList');
            const selectedOrder = document.getElementById('selectedOrder');
            
            // Clear the order list
            orderList.innerHTML = '';
            
            if (checkboxes.length === 0) {
                selectedOrder.style.display = 'none';
                return;
            }
            
            // Show the selected order section
            selectedOrder.style.display = 'block';
            
            // Add each selected module to the order list
            checkboxes.forEach((checkbox, index) => {
                const label = checkbox.closest('.module-card');
                const title = label.querySelector('.module-title').textContent;
                
                const orderItem = document.createElement('div');
                orderItem.className = 'order-item';
                orderItem.innerHTML = `
                    <div class="order-number">${index + 1}</div>
                    <div class="order-item-title">${title}</div>
                `;
                orderList.appendChild(orderItem);
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set initial visual state for already checked modules
            const form = document.getElementById('registrationForm');
            const checkboxes = form.querySelectorAll('.module-checkbox');
            checkboxes.forEach(checkbox => {
                const label = checkbox.closest('.module-card');
                if (checkbox.checked) {
                    label.classList.add('selected');
                }
            });
            updateLearningPath();
        });
    </script>
    <?php endif; ?>
</div>

</body>
</html>
