-- Create Priority KPIs Materialized View (Final - Matches Actual Schema)
DROP MATERIALIZED VIEW IF EXISTS mv_priority_kpis CASCADE;

CREATE MATERIALIZED VIEW mv_priority_kpis AS
SELECT
    -- Today's metrics
    COALESCE((SELECT SUM(amount) FROM revenue WHERE DATE(payment_date) = CURRENT_DATE), 0) as today_revenue,
    COALESCE((SELECT COUNT(DISTINCT project_id) FROM revenue WHERE DATE(payment_date) = CURRENT_DATE), 0) as today_bookings,

    -- Month metrics
    COALESCE((SELECT SUM(amount) FROM revenue WHERE DATE(payment_date) >= DATE_TRUNC('month', CURRENT_DATE)), 0) as month_revenue,
    COALESCE((SELECT COUNT(DISTINCT project_id) FROM revenue WHERE DATE(payment_date) >= DATE_TRUNC('month', CURRENT_DATE)), 0) as month_bookings,

    -- Conversion rate (last 30 days)
    COALESCE(
        CASE
            WHEN (SELECT COUNT(*) FROM inquiries WHERE inquiry_date >= CURRENT_DATE - INTERVAL '30 days') > 0 THEN
                ROUND((SELECT COUNT(*) FROM inquiries WHERE outcome = 'won' AND inquiry_date >= CURRENT_DATE - INTERVAL '30 days')::NUMERIC /
                     (SELECT COUNT(*) FROM inquiries WHERE inquiry_date >= CURRENT_DATE - INTERVAL '30 days') * 100, 2)
            ELSE 0
        END, 0
    ) as conversion_rate,

    -- Average booking value (last 30 days)
    COALESCE(ROUND((SELECT AVG(total_revenue) FROM projects WHERE booking_date >= CURRENT_DATE - INTERVAL '30 days'), 2), 0) as avg_booking_value,

    -- Pipeline metrics
    COALESCE((SELECT COUNT(*) FROM inquiries WHERE status NOT IN ('booked', 'lost', 'abandoned')), 0) as leads_in_pipeline,
    COALESCE((SELECT COUNT(*) FROM projects WHERE status IN ('in-progress', 'booked')), 0) as projects_in_progress,

    -- Delivery metrics (avg days - last 90 days)
    COALESCE(ROUND((SELECT AVG(actual_delivery_date::date - expected_delivery_date::date) FROM deliverables WHERE actual_delivery_date IS NOT NULL AND deliverable_type = 'photos' AND actual_delivery_date >= CURRENT_DATE - INTERVAL '90 days')::NUMERIC, 1), 0) as avg_photo_delivery_days,
    COALESCE(ROUND((SELECT AVG(actual_delivery_date::date - expected_delivery_date::date) FROM deliverables WHERE actual_delivery_date IS NOT NULL AND deliverable_type = 'video' AND actual_delivery_date >= CURRENT_DATE - INTERVAL '90 days')::NUMERIC, 1), 0) as avg_video_delivery_days,

    -- Satisfaction metrics (last 90 days)
    COALESCE(ROUND((SELECT AVG(rating) FROM reviews WHERE review_date >= CURRENT_DATE - INTERVAL '90 days')::NUMERIC, 2), 0) as avg_client_rating,
    COALESCE(ROUND((SELECT AVG(nps_score) FROM reviews WHERE review_date >= CURRENT_DATE - INTERVAL '90 days' AND nps_score IS NOT NULL)::NUMERIC, 1), 0) as nps_score,

    -- Timestamp
    NOW() as last_updated;

-- Create index
CREATE UNIQUE INDEX IF NOT EXISTS idx_mv_priority_kpis ON mv_priority_kpis (last_updated);

-- Show result
SELECT
    'SUCCESS: Priority KPIs View Created!' as status,
    *
FROM mv_priority_kpis;
