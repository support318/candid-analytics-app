-- Create default admin user
-- Username: admin
-- Password: password

INSERT INTO users (username, email, password_hash, role, status)
VALUES (
    'admin',
    'admin@candidstudios.net',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    'active'
)
ON CONFLICT (username) DO NOTHING;
