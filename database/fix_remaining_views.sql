-- Fix the 3 views that had errors
-- Issue: consultations table doesn't have inquiry_id, only client_id
-- Fix: Join via client_id instead

DROP MATERIALIZED VIEW IF EXISTS mv_sales_funnel CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_lead_source_performance CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_client_retention CASCADE;

-- ============================================================================
-- 1. SALES FUNNEL (Fixed to use client_id)
-- ============================================================================

CREATE MATERIALIZED VIEW mv_sales_funnel AS
WITH monthly_funnel AS (
    SELECT
        DATE_TRUNC('month', i.inquiry_date) as month,
        COUNT(*) as total_inquiries,
        COUNT(DISTINCT c.id) FILTER (WHERE c.client_id = i.client_id) as consultations_booked,
        COUNT(*) FILTER (WHERE i.outcome = 'won') as projects_booked,
        COUNT(*) FILTER (WHERE i.outcome = 'lost') as lost_leads,
        COUNT(*) FILTER (WHERE i.status NOT IN ('booked', 'lost', 'abandoned')) as active_leads,
        AVG(
            CASE
                WHEN p.booking_date IS NOT NULL AND i.inquiry_date IS NOT NULL THEN
                    EXTRACT(EPOCH FROM (p.booking_date::timestamp - i.inquiry_date::timestamp)) / 86400
                ELSE NULL
            END
        )::NUMERIC as avg_days_to_booking
    FROM inquiries i
    LEFT JOIN consultations c ON i.client_id = c.client_id
        AND c.consultation_date >= i.inquiry_date
        AND c.consultation_date <= i.inquiry_date + INTERVAL '90 days'
    LEFT JOIN projects p ON i.client_id = p.client_id
        AND p.booking_date >= i.inquiry_date::date
        AND p.booking_date <= (i.inquiry_date::date + INTERVAL '90 days')
    WHERE i.inquiry_date >= CURRENT_DATE - INTERVAL '24 months'
    GROUP BY DATE_TRUNC('month', i.inquiry_date)
)
SELECT
    month,
    total_inquiries,
    consultations_booked,
    projects_booked,
    lost_leads,
    active_leads,
    COALESCE(ROUND(avg_days_to_booking, 1), 0) as avg_days_to_booking,
    -- Conversion rates
    CASE
        WHEN total_inquiries > 0 THEN
            ROUND((consultations_booked::NUMERIC / total_inquiries * 100), 2)
        ELSE 0
    END as consultation_booking_rate,
    CASE
        WHEN consultations_booked > 0 THEN
            ROUND((projects_booked::NUMERIC / consultations_booked * 100), 2)
        ELSE 0
    END as consultation_conversion_rate,
    CASE
        WHEN total_inquiries > 0 THEN
            ROUND((projects_booked::NUMERIC / total_inquiries * 100), 2)
        ELSE 0
    END as overall_conversion_rate
FROM monthly_funnel
ORDER BY month DESC;

CREATE INDEX idx_mv_sales_funnel_month ON mv_sales_funnel (month);

-- ============================================================================
-- 2. LEAD SOURCE PERFORMANCE (Fixed to use client_id)
-- ============================================================================

CREATE MATERIALIZED VIEW mv_lead_source_performance AS
WITH lead_sources AS (
    SELECT
        COALESCE(i.source, 'Unknown') as lead_source,
        COUNT(*) as total_leads,
        COUNT(*) FILTER (WHERE i.outcome = 'won') as converted_leads,
        COUNT(DISTINCT c.id) FILTER (WHERE c.client_id = i.client_id) as consultations_booked,
        SUM(COALESCE(p.total_revenue, 0)) as total_revenue
    FROM inquiries i
    LEFT JOIN consultations c ON i.client_id = c.client_id
        AND c.consultation_date >= i.inquiry_date
        AND c.consultation_date <= i.inquiry_date + INTERVAL '90 days'
    LEFT JOIN projects p ON i.client_id = p.client_id
        AND p.booking_date >= i.inquiry_date::date
        AND p.booking_date <= (i.inquiry_date::date + INTERVAL '90 days')
    WHERE i.inquiry_date >= CURRENT_DATE - INTERVAL '12 months'
    GROUP BY COALESCE(i.source, 'Unknown')
)
SELECT
    lead_source,
    total_leads,
    converted_leads,
    consultations_booked,
    COALESCE(total_revenue, 0) as total_revenue,
    CASE
        WHEN total_leads > 0 THEN
            ROUND((converted_leads::NUMERIC / total_leads * 100), 2)
        ELSE 0
    END as conversion_rate,
    CASE
        WHEN converted_leads > 0 THEN
            ROUND((total_revenue / converted_leads), 2)
        ELSE 0
    END as revenue_per_conversion,
    -- Placeholder for CAC (will calculate when marketing_campaigns table exists)
    0.00 as cost_per_acquisition
FROM lead_sources
ORDER BY total_revenue DESC;

CREATE INDEX idx_mv_lead_source_performance ON mv_lead_source_performance (lead_source);

-- ============================================================================
-- 3. CLIENT RETENTION (Fixed EXTRACT function)
-- ============================================================================

CREATE MATERIALIZED VIEW mv_client_retention AS
WITH client_booking_counts AS (
    SELECT
        client_id,
        COUNT(*) as booking_count,
        MIN(booking_date) as first_booking,
        MAX(booking_date) as last_booking
    FROM projects
    GROUP BY client_id
),
retention_metrics AS (
    SELECT
        COUNT(*) as total_clients,
        COUNT(*) FILTER (WHERE booking_count > 1) as repeat_clients,
        AVG(booking_count) as avg_bookings_per_client,
        AVG(
            CASE
                WHEN last_booking IS NOT NULL AND first_booking IS NOT NULL THEN
                    EXTRACT(EPOCH FROM (last_booking::timestamp - first_booking::timestamp)) / 86400
                ELSE 0
            END
        ) as avg_client_lifetime_days
    FROM client_booking_counts
)
SELECT
    total_clients,
    repeat_clients,
    ROUND(avg_bookings_per_client, 2) as avg_bookings_per_client,
    ROUND(avg_client_lifetime_days::NUMERIC, 0) as avg_client_lifetime_days,
    CASE
        WHEN total_clients > 0 THEN
            ROUND((repeat_clients::NUMERIC / total_clients * 100), 2)
        ELSE 0
    END as repeat_client_percentage,
    NOW() as last_updated
FROM retention_metrics;

CREATE UNIQUE INDEX idx_mv_client_retention ON mv_client_retention (last_updated);

-- Refresh the new views
REFRESH MATERIALIZED VIEW mv_sales_funnel;
REFRESH MATERIALIZED VIEW mv_lead_source_performance;
REFRESH MATERIALIZED VIEW mv_client_retention;

-- Verify
SELECT 'Fixed Views Created Successfully!' as status;

SELECT
    matviewname,
    hasindexes,
    ispopulated
FROM pg_matviews
WHERE schemaname = 'public'
ORDER BY matviewname;
