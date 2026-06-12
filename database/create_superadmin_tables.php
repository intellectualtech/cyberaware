<?php
// Create tables needed for superadmin portal
require_once('c:/xampp/htdocs/cyberaware-new-Edits/config/database.php');

try {
    $conn = getDBConnection();
    
    echo "Creating superadmin portal tables...\n\n";
    
    // 1. Organizations table
    echo "1. Creating organizations table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS organizations (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(200) NOT NULL,
        email VARCHAR(150),
        phone VARCHAR(20),
        address TEXT,
        contact_person VARCHAR(100),
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_org_name (name),
        KEY active_index (is_active)
    )";
    $conn->exec($sql);
    echo "   ✓ Organizations table created\n";
    
    // 2. Subscriptions table
    echo "2. Creating subscriptions table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS subscriptions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        organization_id INT NOT NULL,
        plan VARCHAR(50) NOT NULL,
        status VARCHAR(50) DEFAULT 'active',
        start_date DATE,
        end_date DATE,
        price DECIMAL(10,2),
        renewal_date DATE,
        auto_renew TINYINT(1) DEFAULT 1,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
        KEY status_index (status),
        KEY renewal_index (renewal_date)
    )";
    $conn->exec($sql);
    echo "   ✓ Subscriptions table created\n";
    
    // 3. Training modules table (if not exists)
    echo "3. Checking training_modules table...\n";
    $stmt = $conn->prepare("SHOW TABLES LIKE 'training_modules'");
    $stmt->execute();
    if ($stmt->fetch()) {
        echo "   ✓ Training modules table already exists\n";
    } else {
        echo "   Creating training_modules table...\n";
        $sql = "CREATE TABLE IF NOT EXISTS training_modules (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            category VARCHAR(100),
            video_url VARCHAR(255),
            content TEXT,
            duration_minutes INT,
            difficulty_level VARCHAR(50),
            is_active TINYINT(1) DEFAULT 1,
            order_index INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_title (title),
            KEY active_index (is_active)
        )";
        $conn->exec($sql);
        echo "   ✓ Training modules table created\n";
    }
    
    echo "\n✓ All superadmin tables created successfully!\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
