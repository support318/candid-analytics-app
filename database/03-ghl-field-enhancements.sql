-- ============================================================================
-- Candid Analytics - GHL Field Enhancements
-- ============================================================================
-- Adds columns to existing tables based on newly discovered GHL custom fields
--
-- Updates:
-- - projects: Add GHL opportunity ID, discount tracking, video flag, raw/final links
-- - clients: Add engagement score, mailing address
-- - deliverables: Add raw/final file links
-- - reviews: Add photographer/videographer feedback text fields, review link
-- ============================================================================

-- ============================================================================
-- PROJECTS TABLE ENHANCEMENTS
-- ============================================================================

-- Add GHL Opportunity ID for linking back to GoHighLevel
ALTER TABLE projects
ADD COLUMN IF NOT EXISTS ghl_opportunity_id VARCHAR(255) UNIQUE;

COMMENT ON COLUMN projects.ghl_opportunity_id IS 'GoHighLevel opportunity ID for API linking';

-- Add discount tracking
ALTER TABLE projects
ADD COLUMN IF NOT EXISTS discount_type VARCHAR(50) CHECK (discount_type IN ('referral', 'promo', 'none', NULL));

ALTER TABLE projects
ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0.00;

COMMENT ON COLUMN projects.discount_type IS 'Type of discount applied: referral, promo, or none';
COMMENT ON COLUMN projects.discount_amount IS 'Dollar amount of discount applied';

-- Add video flag
ALTER TABLE projects
ADD COLUMN IF NOT EXISTS has_video BOOLEAN DEFAULT FALSE;

COMMENT ON COLUMN projects.has_video IS 'Whether this project includes videography services';

-- Add travel distance for cost tracking
ALTER TABLE projects
ADD COLUMN IF NOT EXISTS travel_distance VARCHAR(100);

COMMENT ON COLUMN projects.travel_distance IS 'Round trip distance for travel cost calculations (from GHL)';

-- Add calendar/appointment linking
ALTER TABLE projects
ADD COLUMN IF NOT EXISTS calendar_event_id VARCHAR(255);

COMMENT ON COLUMN projects.calendar_event_id IS 'GHL calendar event ID for appointment tracking';

-- Create indexes for new fields
CREATE INDEX IF NOT EXISTS idx_projects_ghl_opportunity_id ON projects(ghl_opportunity_id);
CREATE INDEX IF NOT EXISTS idx_projects_discount_type ON projects(discount_type);
CREATE INDEX IF NOT EXISTS idx_projects_has_video ON projects(has_video);

-- ============================================================================
-- CLIENTS TABLE ENHANCEMENTS
-- ============================================================================

-- Add engagement score from GHL
ALTER TABLE clients
ADD COLUMN IF NOT EXISTS engagement_score INTEGER;

COMMENT ON COLUMN clients.engagement_score IS 'Lead engagement score from GoHighLevel (0-100)';

-- Add mailing address
ALTER TABLE clients
ADD COLUMN IF NOT EXISTS mailing_address TEXT;

COMMENT ON COLUMN clients.mailing_address IS 'Physical mailing address for thank you cards, gifts, etc.';

-- Add partner information for couples (weddings, etc.)
ALTER TABLE clients
ADD COLUMN IF NOT EXISTS partner_first_name VARCHAR(100);

ALTER TABLE clients
ADD COLUMN IF NOT EXISTS partner_last_name VARCHAR(100);

ALTER TABLE clients
ADD COLUMN IF NOT EXISTS partner_email VARCHAR(255);

ALTER TABLE clients
ADD COLUMN IF NOT EXISTS partner_phone VARCHAR(50);

COMMENT ON COLUMN clients.partner_first_name IS 'Partner/spouse first name (for couples)';
COMMENT ON COLUMN clients.partner_last_name IS 'Partner/spouse last name (for couples)';
COMMENT ON COLUMN clients.partner_email IS 'Partner/spouse email (for couples)';
COMMENT ON COLUMN clients.partner_phone IS 'Partner/spouse phone (for couples)';

-- Create indexes for new fields
CREATE INDEX IF NOT EXISTS idx_clients_engagement_score ON clients(engagement_score);
CREATE INDEX IF NOT EXISTS idx_clients_partner_email ON clients(partner_email);

-- ============================================================================
-- DELIVERABLES TABLE ENHANCEMENTS
-- ============================================================================

-- Add file storage links
ALTER TABLE deliverables
ADD COLUMN IF NOT EXISTS raw_images_link TEXT;

ALTER TABLE deliverables
ADD COLUMN IF NOT EXISTS raw_video_link TEXT;

ALTER TABLE deliverables
ADD COLUMN IF NOT EXISTS final_images_link TEXT;

ALTER TABLE deliverables
ADD COLUMN IF NOT EXISTS final_video_link TEXT;

ALTER TABLE deliverables
ADD COLUMN IF NOT EXISTS additional_videos_link TEXT;

COMMENT ON COLUMN deliverables.raw_images_link IS 'Link to raw/unedited photo files';
COMMENT ON COLUMN deliverables.raw_video_link IS 'Link to raw/unedited video files';
COMMENT ON COLUMN deliverables.final_images_link IS 'Link to final edited photo gallery';
COMMENT ON COLUMN deliverables.final_video_link IS 'Link to final edited video';
COMMENT ON COLUMN deliverables.additional_videos_link IS 'Link to additional video content (highlights, etc.)';

-- ============================================================================
-- REVIEWS TABLE ENHANCEMENTS
-- ============================================================================

-- Add detailed feedback text fields from GHL
ALTER TABLE reviews
ADD COLUMN IF NOT EXISTS photographer_feedback TEXT;

ALTER TABLE reviews
ADD COLUMN IF NOT EXISTS videographer_feedback TEXT;

ALTER TABLE reviews
ADD COLUMN IF NOT EXISTS review_link TEXT;

COMMENT ON COLUMN reviews.photographer_feedback IS 'Detailed text feedback about photographer performance (from GHL)';
COMMENT ON COLUMN reviews.videographer_feedback IS 'Detailed text feedback about videographer performance (from GHL)';
COMMENT ON COLUMN reviews.review_link IS 'Link to public review (Google, Wedding Wire, etc.)';

-- ============================================================================
-- STAFF_ASSIGNMENTS TABLE ENHANCEMENTS
-- ============================================================================

-- Add GHL staff ID for syncing
ALTER TABLE staff_assignments
ADD COLUMN IF NOT EXISTS ghl_staff_id VARCHAR(255);

COMMENT ON COLUMN staff_assignments.ghl_staff_id IS 'GoHighLevel user/staff ID for API syncing';

CREATE INDEX IF NOT EXISTS idx_staff_assignments_ghl_staff_id ON staff_assignments(ghl_staff_id);

-- ============================================================================
-- VERIFICATION
-- ============================================================================

SELECT 'GHL field enhancements applied successfully!' as status;

-- Show updated table structures
SELECT
    c.table_name,
    c.column_name,
    c.data_type,
    c.is_nullable,
    pgd.description
FROM information_schema.columns c
LEFT JOIN pg_catalog.pg_statio_all_tables st ON c.table_schema = st.schemaname AND c.table_name = st.relname
LEFT JOIN pg_catalog.pg_description pgd ON pgd.objoid = st.relid AND pgd.objsubid = c.ordinal_position
WHERE c.table_schema = 'public'
AND c.table_name IN ('projects', 'clients', 'deliverables', 'reviews', 'staff_assignments')
AND c.column_name IN (
    'ghl_opportunity_id', 'discount_type', 'discount_amount', 'has_video', 'travel_distance', 'calendar_event_id',
    'engagement_score', 'mailing_address', 'partner_first_name', 'partner_last_name', 'partner_email', 'partner_phone',
    'raw_images_link', 'raw_video_link', 'final_images_link', 'final_video_link', 'additional_videos_link',
    'photographer_feedback', 'videographer_feedback', 'review_link',
    'ghl_staff_id'
)
ORDER BY c.table_name, c.ordinal_position;
