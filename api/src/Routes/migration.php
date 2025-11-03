<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    $container = $app->getContainer();

    /**
     * Run Database Migration (Admin Only)
     *
     * This is a temporary endpoint to apply the 2FA database migration.
     * Should be removed or disabled after migration is complete.
     *
     * POST /api/v1/admin/migrate
     * Headers: Authorization: Bearer <admin_jwt_token>
     */
    $app->post('/api/v1/admin/migrate', function (Request $request, Response $response) use ($container) {
        $jwt = $container->get('jwt');

        // Require admin role
        if ($jwt['role'] !== 'admin') {
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json')->write(json_encode([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Admin access required'
                ]
            ]));
        }

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

            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($columns)) {
                return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->write(json_encode([
                    'success' => false,
                    'error' => [
                        'code' => 'VERIFICATION_FAILED',
                        'message' => 'Migration executed but columns not found. May have already been applied.'
                    ]
                ]));
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'message' => '2FA migration completed successfully',
                    'columns_added' => $columns,
                    'migration_file' => basename($migrationFile)
                ]
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (PDOException $e) {
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
        } catch (Exception $e) {
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
