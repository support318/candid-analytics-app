<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    $container = $app->getContainer();

    /**
     * TEMPORARY Emergency Migration Endpoint (NO AUTH)
     *
     * This endpoint runs the 2FA migration without authentication.
     * DELETE THIS FILE after migration is complete for security.
     *
     * POST /emergency-migrate (outside /api scope to avoid JWT middleware)
     */
    $app->post('/emergency-migrate', function (Request $request, Response $response) use ($container) {
        try {
            $db = $container->get('db');

            // Read migration file
            $migrationFile = __DIR__ . '/../../database/migrations/add_2fa_support.sql';

            if (!file_exists($migrationFile)) {
                return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->write(json_encode([
                    'success' => false,
                    'error' => [
                        'code' => 'MIGRATION_FILE_NOT_FOUND',
                        'message' => 'Migration file not found: ' . $migrationFile
                    ]
                ]));
            }

            $sql = file_get_contents($migrationFile);

            // Execute migration
            $db->getConnection()->exec($sql);

            // Verify columns were added
            $stmt = $db->getConnection()->query("
                SELECT column_name, data_type, is_nullable, column_default
                FROM information_schema.columns
                WHERE table_name = 'users'
                AND column_name LIKE 'two_factor%'
                ORDER BY column_name
            ");

            $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($columns)) {
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->write(json_encode([
                    'success' => true,
                    'data' => [
                        'message' => 'Migration already applied (columns already exist)',
                        'note' => 'Database is already up to date'
                    ]
                ]));
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'message' => '2FA migration completed successfully',
                    'columns_added' => $columns,
                    'migration_file' => basename($migrationFile),
                    'note' => 'DELETE emergency-migrate.php file for security'
                ]
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\PDOException $e) {
            // Check if columns already exist
            if (strpos($e->getMessage(), 'already exists') !== false) {
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->write(json_encode([
                    'success' => true,
                    'data' => [
                        'message' => 'Migration already applied (columns already exist)',
                        'note' => 'This is not an error - the database is already up to date'
                    ]
                ]));
            }

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->write(json_encode([
                'success' => false,
                'error' => [
                    'code' => 'DATABASE_ERROR',
                    'message' => $e->getMessage()
                ]
            ]));
        } catch (\Exception $e) {
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->write(json_encode([
                'success' => false,
                'error' => [
                    'code' => 'MIGRATION_ERROR',
                    'message' => $e->getMessage()
                ]
            ]));
        }
    });
};
