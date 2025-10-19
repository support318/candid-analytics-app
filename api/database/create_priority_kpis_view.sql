-- Create Priority KPIs Materialized View
-- This view aggregates key business metrics for the dashboard

CREATE MATERIALIZED VIEW IF NOT EXISTS mv_priority_kpis AS
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
    WHERE status IN ('in-progress', 'booked')
),
delivery_stats AS (
    SELECT
        AVG(
            CASE
                WHEN deliverable_type = 'photos' THEN
                    EXTRACT(EPOCH FROM (actual_delivery_date - expected_delivery_date))/86400
                ELSE NULL
            END
        ) as avg_photo_delivery_days,
        AVG(
            CASE
                WHEN deliverable_type = 'video' THEN
                    EXTRACT(EPOCH FROM (actual_delivery_date - expected_delivery_date))/86400
                ELSE NULL
            END
        ) as avg_video_delivery_days
    FROM deliverables
    WHERE actual_delivery_date IS NOT NULL
    AND actual_delivery_date >= CURRENT_DATE - INTERVAL '90 days'
),
satisfaction_stats AS (
    SELECT
        AVG(rating) as avg_rating,
        COUNT(*) FILTER (WHERE would_recommend = true)::FLOAT / NULLIF(COUNT(*), 0) * 100 as nps_score
    FROM reviews
    WHERE review_date >= CURRENT_DATE - INTERVAL '90 days'
),
avg_booking_value AS (
    SELECT
        AVG(total_revenue) as avg_value
    FROM projects
    WHERE booking_date >= CURRENT_DATE - INTERVAL '30 days'
)
SELECT
    -- Today's metrics
    t.today_revenue,
    t.today_bookings,

    -- Month metrics
    m.month_revenue,
    m.month_bookings,

    -- Conversion rate
    CASE
        WHEN c.total_inquiries > 0 THEN
            ROUND((c.converted::NUMERIC / c.total_inquiries * 100), 2)
        ELSE 0
    END as conversion_rate,

    -- Average booking value
    COALESCE(ROUND(abv.avg_value, 2), 0) as avg_booking_value,

    -- Pipeline metrics
    p.leads_in_pipeline,
    ps.projects_in_progress,

    -- Delivery metrics (in days)
    COALESCE(ROUND(d.avg_photo_delivery_days, 1), 0) as avg_photo_delivery_days,
    COALESCE(ROUND(d.avg_video_delivery_days, 1), 0) as avg_video_delivery_days,

    -- Satisfaction metrics
    COALESCE(ROUND(s.avg_rating, 2), 0) as avg_client_rating,
    COALESCE(ROUND(s.nps_score, 1), 0) as nps_score,

    -- Timestamp
    NOW() as last_updated
FROM today_stats t
CROSS JOIN month_stats m
CROSS JOIN conversion_stats c
CROSS JOIN pipeline_stats p
CROSS JOIN project_stats ps
CROSS JOIN delivery_stats d
CROSS JOIN satisfaction_stats s
CROSS JOIN avg_booking_value abv;

-- Create index for faster access
CREATE UNIQUE INDEX IF NOT EXISTS idx_mv_priority_kpis ON mv_priority_kpis (last_updated);

-- Refresh the view with data
REFRESH MATERIALIZED VIEW mv_priority_kpis;

-- Verify it worked
SELECT * FROM mv_priority_kpis;
