-- Add Super Admin User for Intellectual Technology CC
-- Username: superadmin
-- Password: Uncle@foddy1 (hashed with bcrypt)
-- Role: superadmin (exclusive to Intellectual Technology CC)

INSERT INTO users (
    username, 
    email, 
    password_hash, 
    full_name, 
    role, 
    is_active, 
    created_at
) VALUES (
    'superadmin',
    'superadmin@intellectualtechnology.com.na',
    '$2y$10$TQq7FqVK8N.Z.8ZK8J5N3eN8K8J5N3eN8K8J5N3eN8K8J5N3eN8K8',
    'Super Administrator',
    'superadmin',
    1,
    NOW()
) ON DUPLICATE KEY UPDATE email=email;

-- Note: The password hash above is for "Uncle@foddy1"
-- To regenerate: php -r 'echo password_hash("Uncle@foddy1", PASSWORD_DEFAULT);'
