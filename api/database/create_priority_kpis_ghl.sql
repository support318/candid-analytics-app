-- Create Priority KPIs Materialized View (For GHL Data)
-- Drop if exists
DROP MATERIALIZED VIEW IF EXISTS mv_priority_kpis CASCADE;

-- Create the view using projects table populated from GHL
CREATE MATERIALIZED VIEW mv_priority_kpis AS
WITH today_stats AS (
    SELECT
        COALESCE(SUM(total_revenue), 0) as today_revenue,
        COUNT(*) FILTER (WHERE status = 'booked') as today_bookings
    FROM projects
    WHERE DATE(booking_date) = CURRENT_DATE
),
month_stats AS (
    SELECT
        COALESCE(SUM(total_revenue), 0) as month_revenue,
        COUNT(*) FILTER (WHERE status = 'booked') as month_bookings
    FROM projects
    WHERE DATE(booking_date) >= DATE_TRUNC('month', CURRENT_DATE)
),
conversion_stats AS (
    SELECT
        COUNT(*) FILTER (WHERE status IN ('booked', 'won')) as converted,
        COUNT(*) as total_leads
    FROM projects
    WHERE created_at >= CURRENT_DATE - INTERVAL '30 days'
),
pipeline_stats AS (
    SELECT
        COUNT(*) as leads_in_pipeline
    FROM projects
    WHERE status IN ('lead', 'open', 'new')
),
project_stats AS (
    SELECT
        COUNT(*) as projects_in_progress
    FROM projects
    WHERE status IN ('in-progress', 'active', 'booked', 'won')
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
        WHEN c.total_leads > 0 THEN
            ROUND((c.converted::NUMERIC / c.total_leads * 100), 2)
        ELSE 0
    END as conversion_rate,

    -- Average booking value
    COALESCE(ROUND(abv.avg_value, 2), 0) as avg_booking_value,

    -- Pipeline metrics
    COALESCE(p.leads_in_pipeline, 0) as leads_in_pipeline,
    COALESCE(ps.projects_in_progress, 0) as projects_in_progress,

    -- Placeholder metrics for features not yet implemented
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

-- Create index for faster access
CREATE UNIQUE INDEX IF NOT EXISTS idx_mv_priority_kpis ON mv_priority_kpis (last_updated);

-- Show the result
SELECT
    'Priority KPIs View Created for GHL Data!' as status,
    leads_in_pipeline,
    projects_in_progress,
    today_revenue,
    month_revenue,
    month_bookings,
    conversion_rate,
    avg_booking_value
FROM mv_priority_kpis;
