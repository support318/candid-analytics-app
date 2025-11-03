<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Temporary endpoint to check and fix users table
$app->get('/api/database/check-users-table', function (Request $request, Response $response) {
    $db = $this->get('db');

    try {
        $output = [];
        $output[] = "Checking users table schema...";

        // Check if full_name and is_active columns exist
        $stmt = $db->getConnection()->query("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_name = 'users'
            AND column_name IN ('full_name', 'is_active')
        ");
        $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $output[] = "Existing special columns: " . implode(', ', $existingColumns);

        $needsMigration = false;
        $changes = [];

        if (!in_array('full_name', $existingColumns)) {
            $output[] = "❌ Missing: full_name column";
            $needsMigration = true;
        } else {
            $output[] = "✓ Found: full_name column";
        }

        if (!in_array('is_active', $existingColumns)) {
            $output[] = "❌ Missing: is_active column";
            $needsMigration = true;
        } else {
            $output[] = "✓ Found: is_active column";
        }

        if ($needsMigration) {
            $output[] = "🔧 Applying migration...";

            // Add full_name column if missing
            if (!in_array('full_name', $existingColumns)) {
                $db->getConnection()->exec("ALTER TABLE users ADD COLUMN full_name VARCHAR(255)");
                $changes[] = "Added full_name column";
            }

            // Add is_active column if missing
            if (!in_array('is_active', $existingColumns)) {
                $db->getConnection()->exec("ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE");
                $changes[] = "Added is_active column";

                // Update existing users
                $db->getConnection()->exec("UPDATE users SET is_active = (status = 'active') WHERE is_active IS NULL");
                $changes[] = "Updated is_active values based on status";
            }

            $output[] = "✅ Migration completed!";
            $output[] = "Changes: " . implode(', ', $changes);
        } else {
            $output[] = "✅ All columns exist! No migration needed.";
        }

        // Test query
        $output[] = "🧪 Testing SELECT query with full_name...";
        $stmt = $db->getConnection()->query("
            SELECT id, username, email, full_name, role, created_at, last_login
            FROM users
            LIMIT 1
        ");
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $output[] = "✓ Query successful!";
            $output[] = "Sample user: {$user['username']} (full_name: " . ($user['full_name'] ?? 'NULL') . ")";
        } else {
            $output[] = "⚠️  No users found in database";
        }

        $data = [
            'success' => true,
            'data' => [
                'migration_needed' => $needsMigration,
                'changes_applied' => $changes,
                'output' => $output
            ]
        ];

        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');

    } catch (Exception $e) {
        $data = [
            'success' => false,
            'error' => [
                'code' => 'DATABASE_ERROR',
                'message' => $e->getMessage()
            ]
        ];
        $response->getBody()->write(json_encode($data));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});
