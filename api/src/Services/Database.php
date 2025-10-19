<?php

declare(strict_types=1);

namespace CandidAnalytics\Services;

use PDO;
use PDOException;

/**
 * Database Service
 * Handles PostgreSQL connections and queries with connection pooling
 */
class Database
{
    private ?PDO $connection = null;
    private string $host;
    private string $port;
    private string $database;
    private string $username;
    private string $password;

    public function __construct(
        string $host,
        string $port,
        string $database,
        string $username,
        string $password
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Get PDO connection
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    /**
     * Connect to PostgreSQL database
     */
    private function connect(): void
    {
        try {
            $dsn = sprintf(
                "pgsql:host=%s;port=%s;dbname=%s",
                $this->host,
                $this->port,
                $this->database
            );

            $this->connection = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false, // Disable persistent connections to avoid stale data
                ]
            );
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Database connection failed: " . $e->getMessage()
            );
        }
    }

    /**
     * Execute query and return all results
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute query and return single row
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Execute query and return single value
     */
    public function queryScalar(string $sql, array $params = [])
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Execute INSERT/UPDATE/DELETE
     */
    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Insert record and return ID
     */
    public function insert(string $table, array $data): string
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s) RETURNING id",
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($data);
        return $stmt->fetchColumn();
    }

    /**
     * Update record
     */
    public function update(string $table, array $data, array $where): bool
    {
        $setClause = [];
        foreach (array_keys($data) as $col) {
            $setClause[] = "$col = :$col";
        }

        $whereClause = [];
        foreach (array_keys($where) as $col) {
            $whereClause[] = "$col = :where_$col";
        }

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $setClause),
            implode(' AND ', $whereClause)
        );

        // Merge data and where params with prefixed where keys
        $params = $data;
        foreach ($where as $key => $value) {
            $params["where_$key"] = $value;
        }

        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute($params);
    }

    // =========================================================================
    // KPI Query Methods
    // =========================================================================

    /**
     * Get priority KPIs from materialized view
     */
    public function getPriorityKpis(): ?array
    {
        return $this->queryOne("SELECT * FROM mv_priority_kpis LIMIT 1");
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics(int $months = 12): array
    {
        $sql = "SELECT * FROM mv_revenue_analytics
                WHERE month >= CURRENT_DATE - INTERVAL '$months months'
                ORDER BY month DESC";
        return $this->query($sql);
    }

    /**
     * Get revenue by location
     */
    public function getRevenueByLocation(int $limit = 20): array
    {
        $sql = "SELECT * FROM mv_revenue_by_location
                ORDER BY revenue DESC
                LIMIT $limit";
        return $this->query($sql);
    }

    /**
     * Get sales funnel metrics
     */
    public function getSalesFunnel(int $months = 12): array
    {
        // Aggregate monthly data into funnel stages
        $sql = "WITH funnel_totals AS (
                    SELECT
                        COALESCE(SUM(total_inquiries), 0) as total_inquiries,
                        COALESCE(SUM(consultations_booked), 0) as consultations_booked,
                        COALESCE(SUM(projects_booked), 0) as projects_booked
                    FROM mv_sales_funnel
                    WHERE month >= CURRENT_DATE - INTERVAL '$months months'
                )
                SELECT 'Inquiries' as stage, total_inquiries as count, total_inquiries * 1500 as value, 100.0 as conversion_rate FROM funnel_totals
                UNION ALL
                SELECT 'Consultations', consultations_booked, consultations_booked * 2000,
                       CASE WHEN total_inquiries > 0 THEN (consultations_booked::numeric / total_inquiries * 100) ELSE 0 END
                FROM funnel_totals
                UNION ALL
                SELECT 'Bookings', projects_booked, projects_booked * 2500,
                       CASE WHEN consultations_booked > 0 THEN (projects_booked::numeric / consultations_booked * 100) ELSE 0 END
                FROM funnel_totals
                ORDER BY count DESC";
        return $this->query($sql);
    }

    /**
     * Get lead source performance
     */
    public function getLeadSourcePerformance(): array
    {
        return $this->query(
            "SELECT
                lead_source as source,
                total_leads as leads,
                converted_leads as conversions,
                conversion_rate,
                total_revenue as revenue
             FROM mv_lead_source_performance
             ORDER BY total_revenue DESC"
        );
    }

    /**
     * Get operational efficiency metrics
     */
    public function getOperationalEfficiency(int $months = 12): array
    {
        $sql = "SELECT * FROM mv_operational_efficiency
                WHERE month >= CURRENT_DATE - INTERVAL '$months months'
                ORDER BY month DESC, deliverable_type";
        return $this->query($sql);
    }

    /**
     * Get staff productivity
     */
    public function getStaffProductivity(int $months = 6): array
    {
        $sql = "SELECT * FROM mv_staff_productivity
                WHERE month >= CURRENT_DATE - INTERVAL '$months months'
                ORDER BY month DESC, revenue_generated DESC";
        return $this->query($sql);
    }

    /**
     * Get client satisfaction metrics
     */
    public function getClientSatisfaction(int $months = 12): array
    {
        $sql = "SELECT * FROM mv_client_satisfaction
                WHERE month >= CURRENT_DATE - INTERVAL '$months months'
                ORDER BY month DESC";
        return $this->query($sql);
    }

    /**
     * Get client retention metrics
     */
    public function getClientRetention(): ?array
    {
        return $this->queryOne("SELECT * FROM mv_client_retention LIMIT 1");
    }

    /**
     * Get marketing performance
     */
    public function getMarketingPerformance(int $months = 12): array
    {
        $sql = "SELECT * FROM mv_marketing_performance
                WHERE month >= CURRENT_DATE - INTERVAL '$months months'
                ORDER BY month DESC, campaign_type";
        return $this->query($sql);
    }

    /**
     * Get venue performance
     */
    public function getVenuePerformance(int $limit = 20): array
    {
        $sql = "SELECT * FROM mv_venue_performance
                ORDER BY events_shot DESC
                LIMIT :limit";
        return $this->query($sql, ['limit' => $limit]);
    }

    /**
     * Get time allocation
     */
    public function getTimeAllocation(int $months = 6): array
    {
        $sql = "SELECT * FROM mv_time_allocation
                WHERE month >= CURRENT_DATE - INTERVAL ':months months'
                ORDER BY month DESC, total_hours DESC";
        return $this->query($sql, ['months' => $months]);
    }

    /**
     * Get seasonal patterns
     */
    public function getSeasonalPatterns(): array
    {
        return $this->query(
            "SELECT * FROM mv_seasonal_patterns ORDER BY month_number"
        );
    }

    // =========================================================================
    // AI Query Methods
    // =========================================================================

    /**
     * Find similar inquiries
     */
    public function findSimilarInquiries(
        string $inquiryId,
        float $threshold = 0.7,
        int $limit = 10
    ): array {
        return $this->query(
            "SELECT * FROM find_similar_inquiries(:inquiry_id, :threshold, :limit)",
            [
                'inquiry_id' => $inquiryId,
                'threshold' => $threshold,
                'limit' => $limit
            ]
        );
    }

    /**
     * Find similar clients
     */
    public function findSimilarClients(string $clientId, int $limit = 10): array
    {
        return $this->query(
            "SELECT * FROM find_similar_clients(:client_id, :limit)",
            ['client_id' => $clientId, 'limit' => $limit]
        );
    }

    /**
     * Get high-value leads (predicted conversion > threshold)
     */
    public function getHighValueLeads(float $threshold = 75.0, int $limit = 20): array
    {
        $sql = "SELECT
                    ie.inquiry_id,
                    ie.predicted_conversion_score,
                    ie.recommended_services,
                    i.inquiry_text,
                    i.event_type,
                    c.first_name || ' ' || c.last_name as client_name,
                    c.email,
                    c.phone
                FROM inquiry_embeddings ie
                JOIN inquiries i ON ie.inquiry_id = i.id
                JOIN clients c ON i.client_id = c.id
                WHERE ie.predicted_conversion_score > :threshold
                AND i.status NOT IN ('booked', 'lost')
                ORDER BY ie.predicted_conversion_score DESC
                LIMIT :limit";

        return $this->query($sql, ['threshold' => $threshold, 'limit' => $limit]);
    }

    /**
     * Get client segments
     */
    public function getClientSegments(): array
    {
        $sql = "SELECT
                    client_segment,
                    COUNT(*) as client_count,
                    AVG(c.lifetime_value) as avg_ltv,
                    SUM(c.lifetime_value) as total_ltv
                FROM client_preference_vectors cpv
                JOIN clients c ON cpv.client_id = c.id
                GROUP BY client_segment
                ORDER BY total_ltv DESC";

        return $this->query($sql);
    }

    /**
     * Get urgent communications (high urgency level)
     */
    public function getUrgentCommunications(int $urgencyLevel = 4, int $limit = 20): array
    {
        $sql = "SELECT
                    ca.*,
                    c.first_name || ' ' || c.last_name as client_name,
                    c.email,
                    c.phone
                FROM communication_analysis ca
                JOIN clients c ON ca.client_id = c.id
                WHERE ca.urgency_level >= :urgency_level
                ORDER BY ca.analyzed_at DESC
                LIMIT :limit";

        return $this->query($sql, [
            'urgency_level' => $urgencyLevel,
            'limit' => $limit
        ]);
    }

    /**
     * Close connection
     */
    public function close(): void
    {
        $this->connection = null;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->close();
    }
}
