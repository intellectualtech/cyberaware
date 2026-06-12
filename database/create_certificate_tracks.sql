-- Certificate Tracks System
-- Coursera-style enrollment and sequential progression

-- 1. Certificate Tracks (Learning Paths)
CREATE TABLE IF NOT EXISTS certificate_tracks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    color VARCHAR(7),
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    estimated_hours INT,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Certificate Modules (Ordered modules within tracks)
CREATE TABLE IF NOT EXISTS certificate_modules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    track_id INT NOT NULL,
    module_id INT NOT NULL,
    sequence_order INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    estimated_duration INT,
    requires_quiz BOOLEAN DEFAULT 1,
    passing_score INT DEFAULT 70,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (track_id) REFERENCES certificate_tracks(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES training_modules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_track_module (track_id, module_id),
    INDEX idx_track_sequence (track_id, sequence_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Trainee Enrollments (Track enrollment records)
CREATE TABLE IF NOT EXISTS trainee_enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    track_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    current_module_id INT,
    progress_percentage INT DEFAULT 0,
    status ENUM('enrolled', 'in_progress', 'completed', 'abandoned') DEFAULT 'enrolled',
    completion_date DATETIME,
    certificate_issued BOOLEAN DEFAULT 0,
    certificate_issued_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (track_id) REFERENCES certificate_tracks(id) ON DELETE CASCADE,
    FOREIGN KEY (current_module_id) REFERENCES certificate_modules(id),
    UNIQUE KEY unique_enrollment (user_id, track_id),
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Module Progress (Track completion per module)
CREATE TABLE IF NOT EXISTS module_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enrollment_id INT NOT NULL,
    module_id INT NOT NULL,
    certificate_module_id INT NOT NULL,
    status ENUM('locked', 'unlocked', 'in_progress', 'completed', 'failed') DEFAULT 'locked',
    lessons_viewed INT DEFAULT 0,
    total_lessons INT DEFAULT 0,
    quiz_attempts INT DEFAULT 0,
    best_quiz_score INT,
    last_attempt_date DATETIME,
    completion_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES trainee_enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES training_modules(id) ON DELETE CASCADE,
    FOREIGN KEY (certificate_module_id) REFERENCES certificate_modules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progress (enrollment_id, certificate_module_id),
    INDEX idx_enrollment_status (enrollment_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Certificate Completions (Issued certificates)
CREATE TABLE IF NOT EXISTS certificate_completions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    track_id INT NOT NULL,
    enrollment_id INT NOT NULL,
    certificate_number VARCHAR(50) UNIQUE,
    issued_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date DATE,
    xp_awarded INT DEFAULT 100,
    badge_awarded VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (track_id) REFERENCES certificate_tracks(id) ON DELETE CASCADE,
    FOREIGN KEY (enrollment_id) REFERENCES trainee_enrollments(id) ON DELETE CASCADE,
    INDEX idx_user_track (user_id, track_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
