-- ============================================================================
-- Candid Analytics - All Materialized Views
-- ============================================================================
-- This file creates all materialized views needed for dashboard tabs
-- Uses existing tables: clients, projects, inquiries, revenue, consultations
-- Creates placeholder metrics for tables that don't exist yet (deliverables, reviews, etc.)
-- ============================================================================

-- Drop existing views if they exist
DROP MATERIALIZED VIEW IF EXISTS mv_priority_kpis CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_revenue_analytics CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_revenue_by_location CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_sales_funnel CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_lead_source_performance CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_operational_efficiency CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_client_satisfaction CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_client_retention CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_marketing_performance CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_staff_productivity CASCADE;

-- ============================================================================
-- 1. PRIORITY KPIS (Dashboard Overview)
-- ============================================================================

CREATE MATERIALIZED VIEW mv_priority_kpis AS
WITH today_stats AS (
    SELECT
        COALESCE(SUM(amount), 0) as today_revenue,
        COUNT(DISTINCT project_id) as today_bookings
    FROM revenue
    WHERE DATE(payment_date) = CURRENT_DATE
),
month_stats AS (
    SELECT
        COALESCE(SUM(amount), 0) as month_revenue,
        COUNT(DISTINCT project_id) as month_bookings
    FROM revenue
    WHERE DATE(payment_date) >= DATE_TRUNC('month', CURRENT_DATE)
),
conversion_stats AS (
    SELECT
        COUNT(*) FILTER (WHERE outcome = 'won') as converted,
        COUNT(*) as total_inquiries
    FROM inquiries
    WHERE inquiry_date >= CURRENT_DATE - INTERVAL '30 days'
),
pipeline_stats AS (
    SELECT
        COUNT(*) as leads_in_pipeline
    FROM inquiries
    WHERE status NOT IN ('booked', 'lost', 'abandoned')
),
project_stats AS (
    SELECT
        COUNT(*) as projects_in_progress
    FROM projects
    WHERE status IN ('in-progress', 'booked', 'confirmed')
),
avg_booking_value AS (
    SELECT
        AVG(total_revenue) as avg_value
    FROM projects
    WHERE booking_date >= CURRENT_DATE - INTERVAL '30 days'
    AND total_revenue > 0
)
SELECT
    -- Today's metrics
    COALESCE(t.today_revenue, 0) as today_revenue,
    COALESCE(t.today_bookings, 0) as today_bookings,

    -- Month metrics
    COALESCE(m.month_revenue, 0) as month_revenue,
    COALESCE(m.month_bookings, 0) as month_bookings,

    -- Conversion rate
    CASE
        WHEN c.total_inquiries > 0 THEN
            ROUND((c.converted::NUMERIC / c.total_inquiries * 100), 2)
        ELSE 0
    END as conversion_rate,

    -- Average booking value
    COALESCE(ROUND(abv.avg_value, 2), 0) as avg_booking_value,

    -- Pipeline metrics
    COALESCE(p.leads_in_pipeline, 0) as leads_in_pipeline,
    COALESCE(ps.projects_in_progress, 0) as projects_in_progress,

    -- Placeholder metrics (will be real once deliverables/reviews tables exist)
    0 as avg_photo_delivery_days,
    0 as avg_video_delivery_days,
    0 as avg_client_rating,
    0 as nps_score,

    -- Timestamp
    NOW() as last_updated
FROM today_stats t
CROSS JOIN month_stats m
CROSS JOIN conversion_stats c
CROSS JOIN pipeline_stats p
CROSS JOIN project_stats ps
CROSS JOIN avg_booking_value abv;

CREATE UNIQUE INDEX idx_mv_priority_kpis ON mv_priority_kpis (last_updated);

-- ============================================================================
-- 2. REVENUE ANALYTICS (Monthly Trends)
-- ============================================================================

CREATE MATERIALIZED VIEW mv_revenue_analytics AS
WITH monthly_data AS (
    SELECT
        DATE_TRUNC('month', r.payment_date) as month,
        SUM(r.amount) as total_revenue,
        COUNT(DISTINCT r.project_id) as booking_count,
        AVG(r.amount) as avg_booking_value,
        SUM(CASE WHEN p.event_type = 'wedding' THEN r.amount ELSE 0 END) as wedding_revenue,
        SUM(CASE WHEN p.event_type = 'portrait' THEN r.amount ELSE 0 END) as portrait_revenue,
        SUM(CASE WHEN p.event_type = 'corporate' THEN r.amount ELSE 0 END) as corporate_revenue
    FROM revenue r
    LEFT JOIN projects p ON r.project_id = p.id
    WHERE r.payment_date >= CURRENT_DATE - INTERVAL '24 months'
    GROUP BY DATE_TRUNC('month', r.payment_date)
)
SELECT
    month,
    COALESCE(total_revenue, 0) as total_revenue,
    COALESCE(booking_count, 0) as booking_count,
    COALESCE(ROUND(avg_booking_value, 2), 0) as avg_booking_value,
    COALESCE(wedding_revenue, 0) as wedding_revenue,
    COALESCE(portrait_revenue, 0) as portrait_revenue,
    COALESCE(corporate_revenue, 0) as corporate_revenue,
    -- Year-over-year growth
    COALESCE(
        ROUND(((total_revenue - LAG(total_revenue, 12) OVER (ORDER BY month)) /
        NULLIF(LAG(total_revenue, 12) OVER (ORDER BY month), 0) * 100), 2),
        0
    ) as yoy_growth_percentage
FROM monthly_data
ORDER BY month DESC;

CREATE INDEX idx_mv_revenue_analytics_month ON mv_revenue_analytics (month);

-- ============================================================================
-- 3. REVENUE BY LOCATION
-- ============================================================================

CREATE MATERIALIZED VIEW mv_revenue_by_location AS
WITH location_totals AS (
    SELECT
        COALESCE(p.venue_name, 'Unknown') as location,
        SUM(p.total_revenue) as total_revenue,
        COUNT(*) as booking_count,
        AVG(p.total_revenue) as avg_booking_value
    FROM projects p
    WHERE p.total_revenue > 0
    GROUP BY COALESCE(p.venue_name, 'Unknown')
),
total_revenue AS (
    SELECT SUM(total_revenue) as grand_total FROM location_totals
)
SELECT
    lt.location,
    COALESCE(lt.total_revenue, 0) as revenue,
    COALESCE(lt.booking_count, 0) as booking_count,
    COALESCE(ROUND(lt.avg_booking_value, 2), 0) as avg_booking_value,
    COALESCE(ROUND((lt.total_revenue / NULLIF(tr.grand_total, 0) * 100), 2), 0) as percentage
FROM location_totals lt
CROSS JOIN total_revenue tr
ORDER BY lt.total_revenue DESC;

CREATE INDEX idx_mv_revenue_by_location ON mv_revenue_by_location (location);

-- ============================================================================
-- 4. SALES FUNNEL (Lead Conversion Metrics)
-- ============================================================================

CREATE MATERIALIZED VIEW mv_sales_funnel AS
WITH monthly_funnel AS (
    SELECT
        DATE_TRUNC('month', i.inquiry_date) as month,
        COUNT(*) as total_inquiries,
        COUNT(*) FILTER (WHERE c.id IS NOT NULL) as consultations_booked,
        COUNT(*) FILTER (WHERE i.outcome = 'won') as projects_booked,
        COUNT(*) FILTER (WHERE i.outcome = 'lost') as lost_leads,
        COUNT(*) FILTER (WHERE i.status NOT IN ('booked', 'lost', 'abandoned')) as active_leads,
        AVG((p.booking_date - i.inquiry_date))::NUMERIC as avg_days_to_booking
    FROM inquiries i
    LEFT JOIN consultations c ON i.client_id = c.client_id
    LEFT JOIN projects p ON i.client_id = p.client_id
        AND p.booking_date >= i.inquiry_date
        AND p.booking_date <= i.inquiry_date + INTERVAL '90 days'
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
-- 5. LEAD SOURCE PERFORMANCE
-- ============================================================================

CREATE MATERIALIZED VIEW mv_lead_source_performance AS
WITH lead_sources AS (
    SELECT
        COALESCE(i.source, 'Unknown') as lead_source,
        COUNT(*) as total_leads,
        COUNT(*) FILTER (WHERE i.outcome = 'won') as converted_leads,
        COUNT(*) FILTER (WHERE c.id IS NOT NULL) as consultations_booked,
        SUM(COALESCE(p.total_revenue, 0)) as total_revenue
    FROM inquiries i
    LEFT JOIN consultations c ON i.client_id = c.client_id
    LEFT JOIN projects p ON i.client_id = p.client_id
        AND p.booking_date >= i.inquiry_date
        AND p.booking_date <= i.inquiry_date + INTERVAL '90 days'
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
    0 as cost_per_acquisition
FROM lead_sources
ORDER BY total_revenue DESC;

CREATE INDEX idx_mv_lead_source_performance ON mv_lead_source_performance (lead_source);

-- ============================================================================
-- 6. OPERATIONAL EFFICIENCY (Placeholder - will be real with deliverables table)
-- ============================================================================

CREATE MATERIALIZED VIEW mv_operational_efficiency AS
WITH monthly_operations AS (
    SELECT
        DATE_TRUNC('month', p.event_date) as month,
        p.event_type as deliverable_type,
        COUNT(*) as total_projects,
        COUNT(*) FILTER (WHERE p.status = 'completed') as completed_projects,
        -- Placeholder delivery times (will use actual delivery dates when deliverables table exists)
        14.0 as avg_delivery_days,
        0 as projects_delivered_on_time,
        0 as avg_revision_count
    FROM projects p
    WHERE p.event_date >= CURRENT_DATE - INTERVAL '12 months'
    GROUP BY DATE_TRUNC('month', p.event_date), p.event_type
)
SELECT
    month,
    deliverable_type,
    total_projects,
    completed_projects,
    avg_delivery_days,
    projects_delivered_on_time,
    avg_revision_count,
    CASE
        WHEN total_projects > 0 THEN
            ROUND((completed_projects::NUMERIC / total_projects * 100), 2)
        ELSE 0
    END as completion_rate
FROM monthly_operations
ORDER BY month DESC, deliverable_type;

CREATE INDEX idx_mv_operational_efficiency ON mv_operational_efficiency (month, deliverable_type);

-- ============================================================================
-- 7. CLIENT SATISFACTION (Placeholder - will be real with reviews table)
-- ============================================================================

CREATE MATERIALIZED VIEW mv_client_satisfaction AS
WITH monthly_satisfaction AS (
    SELECT
        DATE_TRUNC('month', p.event_date) as month,
        COUNT(DISTINCT p.client_id) as total_clients,
        -- Placeholder metrics (will use actual review data when reviews table exists)
        4.8 as avg_rating,
        0 as total_reviews,
        85.0 as nps_score,
        0 as would_recommend_count
    FROM projects p
    WHERE p.event_date >= CURRENT_DATE - INTERVAL '12 months'
    AND p.status = 'completed'
    GROUP BY DATE_TRUNC('month', p.event_date)
)
SELECT
    month,
    total_clients,
    avg_rating,
    total_reviews,
    nps_score,
    would_recommend_count
FROM monthly_satisfaction
ORDER BY month DESC;

CREATE INDEX idx_mv_client_satisfaction_month ON mv_client_satisfaction (month);

-- ============================================================================
-- 8. CLIENT RETENTION
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
        AVG((last_booking - first_booking)) as avg_client_lifetime_days
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

-- ============================================================================
-- 9. MARKETING PERFORMANCE (Placeholder - will be real with marketing_campaigns table)
-- ============================================================================

CREATE MATERIALIZED VIEW mv_marketing_performance AS
WITH monthly_marketing AS (
    SELECT
        DATE_TRUNC('month', i.inquiry_date) as month,
        COALESCE(i.source, 'organic') as campaign_type,
        COUNT(*) as leads_generated,
        COUNT(*) FILTER (WHERE i.outcome = 'won') as conversions,
        -- Placeholder metrics
        0.00 as campaign_cost,
        0.00 as revenue_generated,
        0 as email_opens,
        0 as email_clicks,
        0 as social_engagement
    FROM inquiries i
    WHERE i.inquiry_date >= CURRENT_DATE - INTERVAL '12 months'
    GROUP BY DATE_TRUNC('month', i.inquiry_date), COALESCE(i.source, 'organic')
)
SELECT
    month,
    campaign_type,
    leads_generated,
    conversions,
    campaign_cost,
    revenue_generated,
    email_opens,
    email_clicks,
    social_engagement,
    CASE
        WHEN leads_generated > 0 THEN
            ROUND((conversions::NUMERIC / leads_generated * 100), 2)
        ELSE 0
    END as conversion_rate,
    CASE
        WHEN campaign_cost > 0 THEN
            ROUND((revenue_generated / campaign_cost), 2)
        ELSE 0
    END as roi
FROM monthly_marketing
ORDER BY month DESC, campaign_type;

CREATE INDEX idx_mv_marketing_performance ON mv_marketing_performance (month, campaign_type);

-- ============================================================================
-- 10. STAFF PRODUCTIVITY (Placeholder - will be real with staff_assignments table)
-- ============================================================================

CREATE MATERIALIZED VIEW mv_staff_productivity AS
WITH monthly_staff AS (
    SELECT
        DATE_TRUNC('month', CURRENT_DATE) as month,
        u.username as staff_member,
        -- Placeholder metrics (will use actual time logs when staff_assignments exists)
        0 as projects_completed,
        0.0 as hours_worked,
        0.00 as revenue_generated,
        0 as client_satisfaction_score
    FROM users u
    WHERE u.role IN ('admin', 'staff')
)
SELECT
    month,
    staff_member,
    projects_completed,
    hours_worked,
    revenue_generated,
    client_satisfaction_score,
    CASE
        WHEN hours_worked > 0 THEN
            ROUND((revenue_generated / hours_worked), 2)
        ELSE 0
    END as revenue_per_hour,
    CASE
        WHEN hours_worked > 0 THEN
            ROUND((projects_completed / hours_worked), 2)
        ELSE 0
    END as projects_per_hour
FROM monthly_staff
ORDER BY month DESC, revenue_generated DESC;

CREATE INDEX idx_mv_staff_productivity ON mv_staff_productivity (month, staff_member);

-- ============================================================================
-- REFRESH ALL VIEWS
-- ============================================================================

REFRESH MATERIALIZED VIEW mv_priority_kpis;
REFRESH MATERIALIZED VIEW mv_revenue_analytics;
REFRESH MATERIALIZED VIEW mv_revenue_by_location;
REFRESH MATERIALIZED VIEW mv_sales_funnel;
REFRESH MATERIALIZED VIEW mv_lead_source_performance;
REFRESH MATERIALIZED VIEW mv_operational_efficiency;
REFRESH MATERIALIZED VIEW mv_client_satisfaction;
REFRESH MATERIALIZED VIEW mv_client_retention;
REFRESH MATERIALIZED VIEW mv_marketing_performance;
REFRESH MATERIALIZED VIEW mv_staff_productivity;

-- ============================================================================
-- VERIFICATION
-- ============================================================================

-- Show summary
SELECT 'Materialized Views Created Successfully!' as status;

SELECT
    schemaname,
    matviewname,
    hasindexes,
    ispopulated
FROM pg_matviews
WHERE schemaname = 'public'
ORDER BY matviewname;
