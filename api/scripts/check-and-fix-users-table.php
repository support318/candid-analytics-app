<?php

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $db = new PDO(getenv('DATABASE_URL'));
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Checking users table schema...\n\n";

    // Check if full_name column exists
    $stmt = $db->query("
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = 'users'
        AND column_name IN ('full_name', 'is_active')
        ORDER BY column_name
    ");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Existing special columns: " . implode(', ', $existingColumns) . "\n\n";

    $needsMigration = false;

    if (!in_array('full_name', $existingColumns)) {
        echo "❌ Missing: full_name column\n";
        $needsMigration = true;
    } else {
        echo "✓ Found: full_name column\n";
    }

    if (!in_array('is_active', $existingColumns)) {
        echo "❌ Missing: is_active column\n";
        $needsMigration = true;
    } else {
        echo "✓ Found: is_active column\n";
    }

    if ($needsMigration) {
        echo "\n🔧 Applying migration...\n";

        // Add full_name column if missing
        if (!in_array('full_name', $existingColumns)) {
            $db->exec("ALTER TABLE users ADD COLUMN full_name VARCHAR(255)");
            echo "✓ Added full_name column\n";
        }

        // Add is_active column if missing
        if (!in_array('is_active', $existingColumns)) {
            $db->exec("ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE");
            echo "✓ Added is_active column\n";

            // Update existing users
            $db->exec("UPDATE users SET is_active = (status = 'active') WHERE is_active IS NULL");
            echo "✓ Updated is_active values based on status\n";
        }

        echo "\n✅ Migration completed!\n";
    } else {
        echo "\n✅ All columns exist! No migration needed.\n";
    }

    // Show current users table structure
    echo "\n📋 Current users table structure:\n";
    $stmt = $db->query("
        SELECT column_name, data_type, is_nullable
        FROM information_schema.columns
        WHERE table_name = 'users'
        ORDER BY ordinal_position
    ");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        $nullable = $col['is_nullable'] === 'YES' ? 'NULL' : 'NOT NULL';
        echo "  - {$col['column_name']}: {$col['data_type']} ({$nullable})\n";
    }

    // Test query
    echo "\n🧪 Testing SELECT query with full_name...\n";
    $stmt = $db->query("
        SELECT id, username, email, full_name, role, created_at, last_login
        FROM users
        LIMIT 1
    ");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo "✓ Query successful!\n";
        echo "Sample user: {$user['username']} (full_name: " . ($user['full_name'] ?? 'NULL') . ")\n";
    } else {
        echo "⚠️  No users found in database\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
