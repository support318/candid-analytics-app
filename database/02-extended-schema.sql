-- ============================================================================
-- Candid Analytics - Extended Schema
-- ============================================================================
-- Creates 5 additional tables needed for comprehensive KPI tracking:
-- 1. staff_assignments - Tracks photographer, videographer, PM, sales agent
-- 2. deliverables - Tracks photo/video delivery with on-time metrics
-- 3. reviews - Tracks client ratings, NPS, feedback
-- 4. lead_sources - Tracks marketing channel performance with ROI
-- 5. marketing_campaigns - Tracks campaign metrics and spend
-- ============================================================================

-- ============================================================================
-- 1. STAFF ASSIGNMENTS
-- ============================================================================
-- Tracks which staff members are assigned to each project and their roles

CREATE TABLE IF NOT EXISTS staff_assignments (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    project_id UUID NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    staff_name VARCHAR(255),
    role VARCHAR(100) NOT NULL CHECK (role IN ('photographer', 'videographer', 'project_manager', 'sales_agent', 'assistant')),
    hours_worked DECIMAL(10, 2) DEFAULT 0,
    assignment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_staff_assignments_project ON staff_assignments(project_id);
CREATE INDEX idx_staff_assignments_user ON staff_assignments(user_id);
CREATE INDEX idx_staff_assignments_role ON staff_assignments(role);

-- Trigger to update updated_at timestamp
CREATE TRIGGER update_staff_assignments_updated_at
    BEFORE UPDATE ON staff_assignments
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

COMMENT ON TABLE staff_assignments IS 'Tracks staff member assignments to projects with hours worked';
COMMENT ON COLUMN staff_assignments.role IS 'photographer, videographer, project_manager, sales_agent, assistant';

-- ============================================================================
-- 2. DELIVERABLES
-- ============================================================================
-- Tracks photo and video deliverables with delivery dates and on-time status

CREATE TABLE IF NOT EXISTS deliverables (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    project_id UUID NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    deliverable_type VARCHAR(50) NOT NULL CHECK (deliverable_type IN ('photos', 'video', 'album', 'prints', 'other')),
    expected_delivery_date DATE,
    actual_delivery_date DATE,
    is_delivered BOOLEAN DEFAULT FALSE,
    is_on_time BOOLEAN,
    revision_count INTEGER DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_deliverables_project ON deliverables(project_id);
CREATE INDEX idx_deliverables_type ON deliverables(deliverable_type);
CREATE INDEX idx_deliverables_expected_date ON deliverables(expected_delivery_date);
CREATE INDEX idx_deliverables_actual_date ON deliverables(actual_delivery_date);
CREATE INDEX idx_deliverables_is_delivered ON deliverables(is_delivered);

-- Trigger to automatically calculate is_on_time when actual_delivery_date is set
CREATE OR REPLACE FUNCTION calculate_on_time_delivery()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.actual_delivery_date IS NOT NULL AND NEW.expected_delivery_date IS NOT NULL THEN
        NEW.is_on_time := NEW.actual_delivery_date <= NEW.expected_delivery_date;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_calculate_on_time_delivery
    BEFORE INSERT OR UPDATE ON deliverables
    FOR EACH ROW
    EXECUTE FUNCTION calculate_on_time_delivery();

-- Trigger to update updated_at timestamp
CREATE TRIGGER update_deliverables_updated_at
    BEFORE UPDATE ON deliverables
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

COMMENT ON TABLE deliverables IS 'Tracks delivery of photos, videos, and other project deliverables';
COMMENT ON COLUMN deliverables.is_on_time IS 'Automatically calculated: actual_delivery_date <= expected_delivery_date';

-- ============================================================================
-- 3. REVIEWS
-- ============================================================================
-- Tracks client reviews, ratings, NPS scores, and feedback

CREATE TABLE IF NOT EXISTS reviews (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    project_id UUID NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    client_id UUID NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
    overall_rating INTEGER CHECK (overall_rating BETWEEN 1 AND 5),
    photographer_rating INTEGER CHECK (photographer_rating BETWEEN 1 AND 5),
    videographer_rating INTEGER CHECK (videographer_rating BETWEEN 1 AND 5),
    communication_rating INTEGER CHECK (communication_rating BETWEEN 1 AND 5),
    value_rating INTEGER CHECK (value_rating BETWEEN 1 AND 5),
    nps_score INTEGER CHECK (nps_score BETWEEN 0 AND 10),
    would_recommend BOOLEAN,
    feedback_text TEXT,
    sentiment VARCHAR(50) CHECK (sentiment IN ('positive', 'neutral', 'negative')),
    review_date DATE DEFAULT CURRENT_DATE,
    source VARCHAR(100),
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_reviews_project ON reviews(project_id);
CREATE INDEX idx_reviews_client ON reviews(client_id);
CREATE INDEX idx_reviews_overall_rating ON reviews(overall_rating);
CREATE INDEX idx_reviews_nps_score ON reviews(nps_score);
CREATE INDEX idx_reviews_review_date ON reviews(review_date);
CREATE INDEX idx_reviews_sentiment ON reviews(sentiment);

-- Trigger to automatically calculate sentiment based on ratings
CREATE OR REPLACE FUNCTION calculate_review_sentiment()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.overall_rating IS NOT NULL THEN
        IF NEW.overall_rating >= 4 THEN
            NEW.sentiment := 'positive';
        ELSIF NEW.overall_rating = 3 THEN
            NEW.sentiment := 'neutral';
        ELSE
            NEW.sentiment := 'negative';
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_calculate_review_sentiment
    BEFORE INSERT OR UPDATE ON reviews
    FOR EACH ROW
    EXECUTE FUNCTION calculate_review_sentiment();

-- Trigger to update updated_at timestamp
CREATE TRIGGER update_reviews_updated_at
    BEFORE UPDATE ON reviews
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

COMMENT ON TABLE reviews IS 'Client reviews, ratings, NPS scores, and feedback for projects';
COMMENT ON COLUMN reviews.nps_score IS 'Net Promoter Score: 0-6 = detractor, 7-8 = passive, 9-10 = promoter';
COMMENT ON COLUMN reviews.sentiment IS 'Automatically calculated from overall_rating';

-- ============================================================================
-- 4. LEAD SOURCES
-- ============================================================================
-- Master list of all marketing channels and lead sources

CREATE TABLE IF NOT EXISTS lead_sources (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    source_name VARCHAR(255) NOT NULL UNIQUE,
    source_type VARCHAR(100) CHECK (source_type IN ('organic', 'paid', 'referral', 'direct', 'social', 'email', 'event', 'other')),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_lead_sources_name ON lead_sources(source_name);
CREATE INDEX idx_lead_sources_type ON lead_sources(source_type);
CREATE INDEX idx_lead_sources_active ON lead_sources(is_active);

-- Trigger to update updated_at timestamp
CREATE TRIGGER update_lead_sources_updated_at
    BEFORE UPDATE ON lead_sources
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

COMMENT ON TABLE lead_sources IS 'Master list of all marketing channels and lead sources';

-- Seed data for common lead sources
INSERT INTO lead_sources (source_name, source_type) VALUES
    ('Google Search', 'organic'),
    ('Google Ads', 'paid'),
    ('Facebook Ads', 'paid'),
    ('Instagram', 'social'),
    ('Referral', 'referral'),
    ('Direct Website', 'direct'),
    ('Indeed', 'paid'),
    ('LinkedIn', 'social'),
    ('Yelp', 'organic'),
    ('Wedding Wire', 'paid'),
    ('The Knot', 'paid')
ON CONFLICT (source_name) DO NOTHING;

-- ============================================================================
-- 5. MARKETING CAMPAIGNS
-- ============================================================================
-- Tracks marketing campaign performance, spend, and ROI

CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    campaign_name VARCHAR(255) NOT NULL,
    lead_source_id UUID REFERENCES lead_sources(id) ON DELETE SET NULL,
    campaign_type VARCHAR(100) CHECK (campaign_type IN ('google_ads', 'facebook_ads', 'instagram_ads', 'email', 'event', 'seo', 'content', 'other')),
    start_date DATE,
    end_date DATE,
    budget DECIMAL(10, 2),
    actual_spend DECIMAL(10, 2),
    leads_generated INTEGER DEFAULT 0,
    conversions INTEGER DEFAULT 0,
    revenue_generated DECIMAL(10, 2) DEFAULT 0,
    impressions INTEGER DEFAULT 0,
    clicks INTEGER DEFAULT 0,
    email_opens INTEGER DEFAULT 0,
    email_clicks INTEGER DEFAULT 0,
    social_engagement INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_marketing_campaigns_lead_source ON marketing_campaigns(lead_source_id);
CREATE INDEX idx_marketing_campaigns_type ON marketing_campaigns(campaign_type);
CREATE INDEX idx_marketing_campaigns_start_date ON marketing_campaigns(start_date);
CREATE INDEX idx_marketing_campaigns_end_date ON marketing_campaigns(end_date);
CREATE INDEX idx_marketing_campaigns_active ON marketing_campaigns(is_active);

-- Trigger to update updated_at timestamp
CREATE TRIGGER update_marketing_campaigns_updated_at
    BEFORE UPDATE ON marketing_campaigns
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

COMMENT ON TABLE marketing_campaigns IS 'Tracks marketing campaign performance, spend, and ROI';
COMMENT ON COLUMN marketing_campaigns.actual_spend IS 'Total amount spent on this campaign';
COMMENT ON COLUMN marketing_campaigns.revenue_generated IS 'Total revenue attributed to this campaign';

-- ============================================================================
-- VERIFICATION
-- ============================================================================

SELECT 'Extended schema created successfully!' as status;

-- Show all new tables
SELECT
    tablename,
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE schemaname = 'public'
AND tablename IN ('staff_assignments', 'deliverables', 'reviews', 'lead_sources', 'marketing_campaigns')
ORDER BY tablename;
