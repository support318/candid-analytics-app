-- ============================================================================
-- Candid Analytics - Updated Materialized Views
-- ============================================================================
-- This file recreates all materialized views with real data from the new tables
-- Now that staff_assignments, deliverables, reviews, lead_sources, and
-- marketing_campaigns tables exist, we can use real data instead of placeholders
-- ============================================================================

-- Drop all existing materialized views
DROP MATERIALIZED VIEW IF EXISTS mv_staff_productivity CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_marketing_performance CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_client_retention CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_client_satisfaction CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_operational_efficiency CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_lead_source_performance CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_sales_funnel CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_revenue_by_location CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_revenue_analytics CASCADE;
DROP MATERIALIZED VIEW IF EXISTS mv_priority_kpis CASCADE;

-- ============================================================================
-- 1. PRIORITY KPIS (Dashboard Overview)
-- ============================================================================
-- Now includes REAL delivery times and client ratings from new tables

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
),
delivery_times AS (
    SELECT
        AVG(EXTRACT(EPOCH FROM (actual_delivery_date - expected_delivery_date)) / 86400)::NUMERIC as avg_photo_days,
        AVG(EXTRACT(EPOCH FROM (actual_delivery_date - expected_delivery_date)) / 86400) FILTER (WHERE deliverable_type = 'video')::NUMERIC as avg_video_days
    FROM deliverables
    WHERE actual_delivery_date IS NOT NULL
    AND expected_delivery_date IS NOT NULL
    AND actual_delivery_date >= CURRENT_DATE - INTERVAL '30 days'
),
client_ratings AS (
    SELECT
        AVG(overall_rating)::NUMERIC as avg_rating,
        AVG(nps_score)::NUMERIC as avg_nps
    FROM reviews
    WHERE review_date >= CURRENT_DATE - INTERVAL '30 days'
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

    -- REAL delivery metrics from deliverables table
    COALESCE(ROUND(dt.avg_photo_days, 1), 0) as avg_photo_delivery_days,
    COALESCE(ROUND(dt.avg_video_days, 1), 0) as avg_video_delivery_days,

    -- REAL client satisfaction from reviews table
    COALESCE(ROUND(cr.avg_rating, 2), 0) as avg_client_rating,
    COALESCE(ROUND(cr.avg_nps, 1), 0) as nps_score,

    -- Timestamp
    NOW() as last_updated
FROM today_stats t
CROSS JOIN month_stats m
CROSS JOIN conversion_stats c
CROSS JOIN pipeline_stats p
CROSS JOIN project_stats ps
CROSS JOIN avg_booking_value abv
CROSS JOIN delivery_times dt
CROSS JOIN client_ratings cr;

CREATE UNIQUE INDEX idx_mv_priority_kpis ON mv_priority_kpis (last_updated);

-- ============================================================================
-- 2. OPERATIONS EFFICIENCY (Now uses REAL deliverables data)
-- ============================================================================

CREATE MATERIALIZED VIEW mv_operational_efficiency AS
WITH monthly_deliverables AS (
    SELECT
        DATE_TRUNC('month', d.expected_delivery_date) as month,
        d.deliverable_type,
        COUNT(*) as total_deliverables,
        COUNT(*) FILTER (WHERE d.is_delivered = TRUE) as completed_deliverables,
        COUNT(*) FILTER (WHERE d.is_on_time = TRUE) as on_time_deliveries,
        AVG(EXTRACT(EPOCH FROM (d.actual_delivery_date - d.expected_delivery_date)) / 86400)::NUMERIC as avg_delivery_days,
        AVG(d.revision_count)::NUMERIC as avg_revisions
    FROM deliverables d
    WHERE d.expected_delivery_date >= CURRENT_DATE - INTERVAL '12 months'
    GROUP BY DATE_TRUNC('month', d.expected_delivery_date), d.deliverable_type
),
staff_utilization AS (
    SELECT
        DATE_TRUNC('month', sa.assignment_date) as month,
        SUM(sa.hours_worked) as total_hours,
        COUNT(DISTINCT sa.user_id) as staff_count
    FROM staff_assignments sa
    WHERE sa.assignment_date >= CURRENT_DATE - INTERVAL '12 months'
    GROUP BY DATE_TRUNC('month', sa.assignment_date)
)
SELECT
    COALESCE(md.month, su.month) as month,
    COALESCE(md.deliverable_type, 'all') as deliverable_type,
    COALESCE(md.total_deliverables, 0) as total_deliverables,
    COALESCE(md.completed_deliverables, 0) as completed_deliverables,
    COALESCE(md.on_time_deliveries, 0) as on_time_deliveries,
    COALESCE(ROUND(md.avg_delivery_days, 1), 0) as avg_delivery_days,
    COALESCE(ROUND(md.avg_revisions, 1), 0) as avg_revisions,
    COALESCE(su.total_hours, 0) as total_staff_hours,
    COALESCE(su.staff_count, 0) as active_staff_count,
    CASE
        WHEN md.total_deliverables > 0 THEN
            ROUND((md.completed_deliverables::NUMERIC / md.total_deliverables * 100), 2)
        ELSE 0
    END as completion_rate,
    CASE
        WHEN md.completed_deliverables > 0 THEN
            ROUND((md.on_time_deliveries::NUMERIC / md.completed_deliverables * 100), 2)
        ELSE 0
    END as on_time_percentage
FROM monthly_deliverables md
FULL OUTER JOIN staff_utilization su ON md.month = su.month
WHERE COALESCE(md.month, su.month) IS NOT NULL
ORDER BY COALESCE(md.month, su.month) DESC, md.deliverable_type;

CREATE INDEX idx_mv_operational_efficiency ON mv_operational_efficiency (month, deliverable_type);

-- ============================================================================
-- 3. CLIENT SATISFACTION (Now uses REAL reviews data)
-- ============================================================================

CREATE MATERIALIZED VIEW mv_client_satisfaction AS
WITH monthly_reviews AS (
    SELECT
        DATE_TRUNC('month', r.review_date) as month,
        COUNT(*) as total_reviews,
        AVG(r.overall_rating)::NUMERIC as avg_overall_rating,
        AVG(r.photographer_rating)::NUMERIC as avg_photographer_rating,
        AVG(r.videographer_rating)::NUMERIC as avg_videographer_rating,
        AVG(r.communication_rating)::NUMERIC as avg_communication_rating,
        AVG(r.value_rating)::NUMERIC as avg_value_rating,
        AVG(r.nps_score)::NUMERIC as avg_nps,
        COUNT(*) FILTER (WHERE r.nps_score >= 9) as promoters,
        COUNT(*) FILTER (WHERE r.nps_score BETWEEN 7 AND 8) as passives,
        COUNT(*) FILTER (WHERE r.nps_score <= 6) as detractors,
        COUNT(*) FILTER (WHERE r.would_recommend = TRUE) as would_recommend_count,
        COUNT(*) FILTER (WHERE r.sentiment = 'positive') as positive_reviews,
        COUNT(*) FILTER (WHERE r.sentiment = 'neutral') as neutral_reviews,
        COUNT(*) FILTER (WHERE r.sentiment = 'negative') as negative_reviews
    FROM reviews r
    WHERE r.review_date >= CURRENT_DATE - INTERVAL '12 months'
    GROUP BY DATE_TRUNC('month', r.review_date)
)
SELECT
    month,
    total_reviews,
    COALESCE(ROUND(avg_overall_rating, 2), 0) as avg_overall_rating,
    COALESCE(ROUND(avg_photographer_rating, 2), 0) as avg_photographer_rating,
    COALESCE(ROUND(avg_videographer_rating, 2), 0) as avg_videographer_rating,
    COALESCE(ROUND(avg_communication_rating, 2), 0) as avg_communication_rating,
    COALESCE(ROUND(avg_value_rating, 2), 0) as avg_value_rating,
    COALESCE(ROUND(avg_nps, 1), 0) as avg_nps,
    promoters,
    passives,
    detractors,
    -- Calculate NPS Score: (% promoters - % detractors)
    CASE
        WHEN total_reviews > 0 THEN
            ROUND(((promoters::NUMERIC - detractors::NUMERIC) / total_reviews * 100), 1)
        ELSE 0
    END as nps_score,
    would_recommend_count,
    positive_reviews,
    neutral_reviews,
    negative_reviews,
    CASE
        WHEN total_reviews > 0 THEN
            ROUND((positive_reviews::NUMERIC / total_reviews * 100), 1)
        ELSE 0
    END as positive_percentage
FROM monthly_reviews
ORDER BY month DESC;

CREATE INDEX idx_mv_client_satisfaction_month ON mv_client_satisfaction (month);

-- ============================================================================
-- 4. MARKETING PERFORMANCE (Now uses REAL lead_sources and marketing_campaigns)
-- ============================================================================

CREATE MATERIALIZED VIEW mv_marketing_performance AS
WITH campaign_metrics AS (
    SELECT
        DATE_TRUNC('month', mc.start_date) as month,
        ls.source_name as campaign_source,
        mc.campaign_type,
        SUM(mc.budget) as total_budget,
        SUM(mc.actual_spend) as total_spend,
        SUM(mc.leads_generated) as leads_generated,
        SUM(mc.conversions) as conversions,
        SUM(mc.revenue_generated) as revenue_generated,
        SUM(mc.impressions) as impressions,
        SUM(mc.clicks) as clicks,
        SUM(mc.email_opens) as email_opens,
        SUM(mc.email_clicks) as email_clicks,
        SUM(mc.social_engagement) as social_engagement
    FROM marketing_campaigns mc
    LEFT JOIN lead_sources ls ON mc.lead_source_id = ls.id
    WHERE mc.start_date >= CURRENT_DATE - INTERVAL '12 months'
    GROUP BY DATE_TRUNC('month', mc.start_date), ls.source_name, mc.campaign_type
),
inquiry_metrics AS (
    SELECT
        DATE_TRUNC('month', i.inquiry_date) as month,
        i.source as inquiry_source,
        COUNT(*) as total_inquiries,
        COUNT(*) FILTER (WHERE i.outcome = 'won') as won_inquiries
    FROM inquiries i
    WHERE i.inquiry_date >= CURRENT_DATE - INTERVAL '12 months'
    GROUP BY DATE_TRUNC('month', i.inquiry_date), i.source
)
SELECT
    COALESCE(cm.month, im.month) as month,
    COALESCE(cm.campaign_source, im.inquiry_source, 'organic') as campaign_source,
    cm.campaign_type,
    COALESCE(cm.total_budget, 0) as total_budget,
    COALESCE(cm.total_spend, 0) as total_spend,
    COALESCE(cm.leads_generated, im.total_inquiries, 0) as leads_generated,
    COALESCE(cm.conversions, im.won_inquiries, 0) as conversions,
    COALESCE(cm.revenue_generated, 0) as revenue_generated,
    COALESCE(cm.impressions, 0) as impressions,
    COALESCE(cm.clicks, 0) as clicks,
    COALESCE(cm.email_opens, 0) as email_opens,
    COALESCE(cm.email_clicks, 0) as email_clicks,
    COALESCE(cm.social_engagement, 0) as social_engagement,
    -- Calculated metrics
    CASE
        WHEN COALESCE(cm.leads_generated, im.total_inquiries, 0) > 0 THEN
            ROUND((COALESCE(cm.conversions, im.won_inquiries, 0)::NUMERIC /
                   COALESCE(cm.leads_generated, im.total_inquiries, 0) * 100), 2)
        ELSE 0
    END as conversion_rate,
    CASE
        WHEN cm.total_spend > 0 THEN
            ROUND((cm.revenue_generated / cm.total_spend), 2)
        ELSE 0
    END as roi,
    CASE
        WHEN cm.total_spend > 0 AND COALESCE(cm.leads_generated, 0) > 0 THEN
            ROUND((cm.total_spend / cm.leads_generated), 2)
        ELSE 0
    END as cost_per_lead,
    CASE
        WHEN cm.total_spend > 0 AND COALESCE(cm.conversions, 0) > 0 THEN
            ROUND((cm.total_spend / cm.conversions), 2)
        ELSE 0
    END as cost_per_conversion
FROM campaign_metrics cm
FULL OUTER JOIN inquiry_metrics im ON cm.month = im.month AND cm.campaign_source = im.inquiry_source
WHERE COALESCE(cm.month, im.month) IS NOT NULL
ORDER BY COALESCE(cm.month, im.month) DESC, campaign_source;

CREATE INDEX idx_mv_marketing_performance ON mv_marketing_performance (month, campaign_source);

-- ============================================================================
-- 5. STAFF PRODUCTIVITY (Now uses REAL staff_assignments data)
-- ============================================================================

CREATE MATERIALIZED VIEW mv_staff_productivity AS
WITH staff_metrics AS (
    SELECT
        DATE_TRUNC('month', sa.assignment_date) as month,
        u.username as staff_member,
        sa.role as staff_role,
        COUNT(DISTINCT sa.project_id) as projects_assigned,
        SUM(sa.hours_worked) as hours_worked,
        SUM(p.total_revenue) as revenue_generated
    FROM staff_assignments sa
    LEFT JOIN users u ON sa.user_id = u.id
    LEFT JOIN projects p ON sa.project_id = p.id
    WHERE sa.assignment_date >= CURRENT_DATE - INTERVAL '12 months'
    GROUP BY DATE_TRUNC('month', sa.assignment_date), u.username, sa.role
),
staff_reviews AS (
    SELECT
        DATE_TRUNC('month', r.review_date) as month,
        sa.user_id,
        AVG(CASE
            WHEN sa.role = 'photographer' THEN r.photographer_rating
            WHEN sa.role = 'videographer' THEN r.videographer_rating
            ELSE r.overall_rating
        END)::NUMERIC as avg_rating
    FROM reviews r
    JOIN staff_assignments sa ON r.project_id = sa.project_id
    WHERE r.review_date >= CURRENT_DATE - INTERVAL '12 months'
    GROUP BY DATE_TRUNC('month', r.review_date), sa.user_id
),
deliverable_performance AS (
    SELECT
        DATE_TRUNC('month', d.expected_delivery_date) as month,
        sa.user_id,
        COUNT(*) FILTER (WHERE d.is_on_time = TRUE) as on_time_deliveries,
        COUNT(*) as total_deliveries
    FROM deliverables d
    JOIN staff_assignments sa ON d.project_id = sa.project_id
    WHERE d.expected_delivery_date >= CURRENT_DATE - INTERVAL '12 months'
    GROUP BY DATE_TRUNC('month', d.expected_delivery_date), sa.user_id
)
SELECT
    sm.month,
    sm.staff_member,
    sm.staff_role,
    sm.projects_assigned,
    COALESCE(ROUND(sm.hours_worked, 1), 0) as hours_worked,
    COALESCE(ROUND(sm.revenue_generated, 2), 0) as revenue_generated,
    COALESCE(ROUND(sr.avg_rating, 2), 0) as avg_client_rating,
    COALESCE(dp.on_time_deliveries, 0) as on_time_deliveries,
    COALESCE(dp.total_deliveries, 0) as total_deliveries,
    -- Calculated metrics
    CASE
        WHEN sm.hours_worked > 0 THEN
            ROUND((sm.revenue_generated / sm.hours_worked), 2)
        ELSE 0
    END as revenue_per_hour,
    CASE
        WHEN sm.hours_worked > 0 THEN
            ROUND((sm.projects_assigned / sm.hours_worked), 2)
        ELSE 0
    END as projects_per_hour,
    CASE
        WHEN dp.total_deliveries > 0 THEN
            ROUND((dp.on_time_deliveries::NUMERIC / dp.total_deliveries * 100), 1)
        ELSE 0
    END as on_time_percentage
FROM staff_metrics sm
LEFT JOIN staff_reviews sr ON sm.month = sr.month
LEFT JOIN deliverable_performance dp ON sm.month = dp.month
ORDER BY sm.month DESC, sm.revenue_generated DESC;

CREATE INDEX idx_mv_staff_productivity ON mv_staff_productivity (month, staff_member);

-- ============================================================================
-- 6. REVENUE ANALYTICS (Already good, just recreating for consistency)
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
        SUM(CASE WHEN p.event_type = 'corporate' THEN r.amount ELSE 0 END) as corporate_revenue,
        SUM(CASE WHEN p.event_type = 'event' THEN r.amount ELSE 0 END) as event_revenue
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
    COALESCE(event_revenue, 0) as event_revenue,
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
-- 7. SALES FUNNEL (Already good, just recreating for consistency)
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
        AVG(EXTRACT(EPOCH FROM (p.booking_date - i.inquiry_date)) / 86400)::NUMERIC as avg_days_to_booking
    FROM inquiries i
    LEFT JOIN consultations c ON i.id = c.inquiry_id
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
-- 8. REVENUE BY LOCATION (Already good, just recreating for consistency)
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
-- 9. LEAD SOURCE PERFORMANCE (Enhanced with marketing_campaigns data)
-- ============================================================================

CREATE MATERIALIZED VIEW mv_lead_source_performance AS
WITH lead_sources_data AS (
    SELECT
        COALESCE(ls.source_name, i.source, 'Unknown') as lead_source,
        ls.source_type,
        COUNT(DISTINCT i.id) as total_leads,
        COUNT(DISTINCT i.id) FILTER (WHERE i.outcome = 'won') as converted_leads,
        COUNT(DISTINCT i.id) FILTER (WHERE c.id IS NOT NULL) as consultations_booked,
        SUM(COALESCE(p.total_revenue, 0)) as total_revenue
    FROM inquiries i
    LEFT JOIN lead_sources ls ON i.source = ls.source_name
    LEFT JOIN consultations c ON i.id = c.inquiry_id
    LEFT JOIN projects p ON i.client_id = p.client_id
        AND p.booking_date >= i.inquiry_date
        AND p.booking_date <= i.inquiry_date + INTERVAL '90 days'
    WHERE i.inquiry_date >= CURRENT_DATE - INTERVAL '12 months'
    GROUP BY COALESCE(ls.source_name, i.source, 'Unknown'), ls.source_type
),
campaign_costs AS (
    SELECT
        ls.source_name,
        SUM(mc.actual_spend) as total_spend
    FROM marketing_campaigns mc
    JOIN lead_sources ls ON mc.lead_source_id = ls.id
    WHERE mc.start_date >= CURRENT_DATE - INTERVAL '12 months'
    GROUP BY ls.source_name
)
SELECT
    lsd.lead_source,
    lsd.source_type,
    lsd.total_leads,
    lsd.converted_leads,
    lsd.consultations_booked,
    COALESCE(lsd.total_revenue, 0) as total_revenue,
    COALESCE(cc.total_spend, 0) as total_spend,
    CASE
        WHEN lsd.total_leads > 0 THEN
            ROUND((lsd.converted_leads::NUMERIC / lsd.total_leads * 100), 2)
        ELSE 0
    END as conversion_rate,
    CASE
        WHEN lsd.converted_leads > 0 THEN
            ROUND((lsd.total_revenue / lsd.converted_leads), 2)
        ELSE 0
    END as revenue_per_conversion,
    CASE
        WHEN COALESCE(cc.total_spend, 0) > 0 AND lsd.total_leads > 0 THEN
            ROUND((cc.total_spend / lsd.total_leads), 2)
        ELSE 0
    END as cost_per_lead,
    CASE
        WHEN COALESCE(cc.total_spend, 0) > 0 AND lsd.converted_leads > 0 THEN
            ROUND((cc.total_spend / lsd.converted_leads), 2)
        ELSE 0
    END as cost_per_acquisition,
    CASE
        WHEN COALESCE(cc.total_spend, 0) > 0 THEN
            ROUND((lsd.total_revenue / cc.total_spend), 2)
        ELSE 0
    END as roi
FROM lead_sources_data lsd
LEFT JOIN campaign_costs cc ON lsd.lead_source = cc.source_name
ORDER BY lsd.total_revenue DESC;

CREATE INDEX idx_mv_lead_source_performance ON mv_lead_source_performance (lead_source);

-- ============================================================================
-- 10. CLIENT RETENTION (Already good, just recreating for consistency)
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
        AVG(EXTRACT(EPOCH FROM (last_booking - first_booking)) / 86400) as avg_client_lifetime_days
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

SELECT 'All Materialized Views Updated Successfully!' as status;

SELECT
    schemaname,
    matviewname,
    hasindexes,
    ispopulated
FROM pg_matviews
WHERE schemaname = 'public'
ORDER BY matviewname;
