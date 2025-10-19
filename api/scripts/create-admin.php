<?php

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Create database connection
$db = new \CandidAnalytics\Services\Database(
    $_ENV['DB_HOST'],
    $_ENV['DB_PORT'],
    $_ENV['DB_NAME'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASSWORD']
);

// Insert admin user
try {
    $db->execute(
        "INSERT INTO users (username, email, password_hash, role, status)
         VALUES (:username, :email, :password_hash, :role, :status)
         ON CONFLICT (username) DO NOTHING",
        [
            'username' => 'admin',
            'email' => 'admin@candidstudios.net',
            'password_hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'role' => 'admin',
            'status' => 'active'
        ]
    );

    echo "Admin user created successfully!\n";
    echo "Username: admin\n";
    echo "Password: password\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
