-- Analytics V2 Schema Additions
-- Adds missing tables and fields for complete Candid Studios analytics
-- Run this migration on existing database

-- =====================================================
-- 1. STAFF ASSIGNMENTS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS staff (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    ghl_user_id VARCHAR(255) UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100),
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(50),
    role VARCHAR(50) CHECK (role IN ('photographer', 'videographer', 'hybrid', 'editor_photo', 'editor_video', 'editor_hybrid', 'project_manager', 'sales_agent', 'admin')),
    status VARCHAR(50) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'on_leave')),
    hire_date DATE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    metadata JSONB DEFAULT '{}'::jsonb
);

CREATE TABLE IF NOT EXISTS staff_assignments (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    project_id UUID NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    staff_id UUID REFERENCES staff(id),
    role VARCHAR(50) NOT NULL CHECK (role IN ('photographer', 'videographer', 'editor_photo', 'editor_video', 'project_manager')),
    staff_name VARCHAR(255), -- Fallback if no staff record
    assigned_date DATE DEFAULT CURRENT_DATE,
    status VARCHAR(50) DEFAULT 'assigned' CHECK (status IN ('assigned', 'confirmed', 'completed', 'cancelled')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    notes TEXT
);

-- =====================================================
-- 2. CLIENT REVIEWS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS client_reviews (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    project_id UUID REFERENCES projects(id) ON DELETE SET NULL,
    client_id UUID NOT NULL REFERENCES clients(id) ON DELETE CASCADE,

    -- Ratings (1-5 stars)
    overall_rating INTEGER CHECK (overall_rating >= 1 AND overall_rating <= 5),
    photographer_rating INTEGER CHECK (photographer_rating >= 1 AND photographer_rating <= 5),
    videographer_rating INTEGER CHECK (videographer_rating >= 1 AND videographer_rating <= 5),

    -- NPS Score (0-10)
    nps_score INTEGER CHECK (nps_score >= 0 AND nps_score <= 10),

    -- Would recommend?
    would_recommend BOOLEAN,

    -- Review content
    review_text TEXT,
    review_platform VARCHAR(100), -- Google, Yelp, TheKnot, WeddingWire, etc.
    review_link TEXT,

    -- Timestamps
    review_date DATE NOT NULL DEFAULT CURRENT_DATE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,

    -- GHL field sync
    ghl_synced BOOLEAN DEFAULT FALSE,
    ghl_sync_date TIMESTAMP WITH TIME ZONE,

    metadata JSONB DEFAULT '{}'::jsonb
);

-- =====================================================
-- 3. PROJECT DELIVERIES TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS project_deliveries (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    project_id UUID NOT NULL REFERENCES projects(id) ON DELETE CASCADE,

    -- Delivery status
    delivery_status VARCHAR(50) DEFAULT 'pending' CHECK (delivery_status IN (
        'pending',
        'raw_photos_delivered',
        'raw_video_delivered',
        'editing_in_progress',
        'qa_review',
        'final_photos_delivered',
        'final_video_delivered',
        'complete',
        'revisions_requested'
    )),

    -- Photo delivery tracking
    raw_photos_url TEXT,
    raw_photos_delivered_date DATE,
    final_photos_url TEXT,
    final_photos_delivered_date DATE,
    photo_revision_count INTEGER DEFAULT 0,

    -- Video delivery tracking
    raw_video_url TEXT,
    raw_video_delivered_date DATE,
    final_video_url TEXT,
    final_video_delivered_date DATE,
    video_revision_count INTEGER DEFAULT 0,
    additional_videos_url TEXT,

    -- Deadlines
    photo_delivery_deadline DATE,
    video_delivery_deadline DATE,

    -- Calculated fields (days to delivery)
    photo_delivery_days INTEGER GENERATED ALWAYS AS (
        CASE WHEN final_photos_delivered_date IS NOT NULL AND booking_date IS NOT NULL
        THEN (final_photos_delivered_date - booking_date)
        ELSE NULL END
    ) STORED,
    video_delivery_days INTEGER GENERATED ALWAYS AS (
        CASE WHEN final_video_delivered_date IS NOT NULL AND booking_date IS NOT NULL
        THEN (final_video_delivered_date - booking_date)
        ELSE NULL END
    ) STORED,

    -- Reference to project booking date for calculations
    booking_date DATE,

    -- Editor notes
    photographer_notes TEXT,
    videographer_notes TEXT,
    editor_notes TEXT,
    client_revision_notes TEXT,

    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    metadata JSONB DEFAULT '{}'::jsonb
);

-- =====================================================
-- 4. UPDATE REVENUE TABLE FOR STRIPE
-- =====================================================

-- Add Stripe-specific columns if they don't exist
DO $$
BEGIN
    -- Add stripe_payment_id
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'revenue' AND column_name = 'stripe_payment_id') THEN
        ALTER TABLE revenue ADD COLUMN stripe_payment_id VARCHAR(255) UNIQUE;
    END IF;

    -- Add stripe_charge_id
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'revenue' AND column_name = 'stripe_charge_id') THEN
        ALTER TABLE revenue ADD COLUMN stripe_charge_id VARCHAR(255);
    END IF;

    -- Add stripe_invoice_id
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'revenue' AND column_name = 'stripe_invoice_id') THEN
        ALTER TABLE revenue ADD COLUMN stripe_invoice_id VARCHAR(255);
    END IF;

    -- Add stripe_customer_id
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'revenue' AND column_name = 'stripe_customer_id') THEN
        ALTER TABLE revenue ADD COLUMN stripe_customer_id VARCHAR(255);
    END IF;

    -- Add refund tracking columns
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'revenue' AND column_name = 'stripe_refund_id') THEN
        ALTER TABLE revenue ADD COLUMN stripe_refund_id VARCHAR(255);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'revenue' AND column_name = 'refund_amount') THEN
        ALTER TABLE revenue ADD COLUMN refund_amount DECIMAL(10,2) DEFAULT 0;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'revenue' AND column_name = 'refund_date') THEN
        ALTER TABLE revenue ADD COLUMN refund_date DATE;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'revenue' AND column_name = 'refund_reason') THEN
        ALTER TABLE revenue ADD COLUMN refund_reason TEXT;
    END IF;
END $$;

-- =====================================================
-- 5. UPDATE PROJECTS TABLE
-- =====================================================

DO $$
BEGIN
    -- Add GHL opportunity_id
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'projects' AND column_name = 'ghl_opportunity_id') THEN
        ALTER TABLE projects ADD COLUMN ghl_opportunity_id VARCHAR(255) UNIQUE;
    END IF;

    -- Add pipeline tracking
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'projects' AND column_name = 'ghl_pipeline_id') THEN
        ALTER TABLE projects ADD COLUMN ghl_pipeline_id VARCHAR(255);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'projects' AND column_name = 'ghl_stage_id') THEN
        ALTER TABLE projects ADD COLUMN ghl_stage_id VARCHAR(255);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'projects' AND column_name = 'ghl_stage_name') THEN
        ALTER TABLE projects ADD COLUMN ghl_stage_name VARCHAR(255);
    END IF;

    -- Add photo/video specific fields
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'projects' AND column_name = 'has_photography') THEN
        ALTER TABLE projects ADD COLUMN has_photography BOOLEAN DEFAULT FALSE;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'projects' AND column_name = 'has_videography') THEN
        ALTER TABLE projects ADD COLUMN has_videography BOOLEAN DEFAULT FALSE;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'projects' AND column_name = 'photo_hours') THEN
        ALTER TABLE projects ADD COLUMN photo_hours DECIMAL(4,1) DEFAULT 0;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'projects' AND column_name = 'video_hours') THEN
        ALTER TABLE projects ADD COLUMN video_hours DECIMAL(4,1) DEFAULT 0;
    END IF;

    -- Add estimated vs actual revenue
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'projects' AND column_name = 'estimated_revenue') THEN
        ALTER TABLE projects ADD COLUMN estimated_revenue DECIMAL(10,2) DEFAULT 0;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'projects' AND column_name = 'actual_revenue') THEN
        ALTER TABLE projects ADD COLUMN actual_revenue DECIMAL(10,2) DEFAULT 0;
    END IF;
END $$;

-- =====================================================
-- 6. UPDATE CLIENTS TABLE
-- =====================================================

DO $$
BEGIN
    -- Add partner information
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'clients' AND column_name = 'partner_first_name') THEN
        ALTER TABLE clients ADD COLUMN partner_first_name VARCHAR(100);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'clients' AND column_name = 'partner_last_name') THEN
        ALTER TABLE clients ADD COLUMN partner_last_name VARCHAR(100);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'clients' AND column_name = 'partner_email') THEN
        ALTER TABLE clients ADD COLUMN partner_email VARCHAR(255);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'clients' AND column_name = 'partner_phone') THEN
        ALTER TABLE clients ADD COLUMN partner_phone VARCHAR(50);
    END IF;

    -- Add Stripe customer ID
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'clients' AND column_name = 'stripe_customer_id') THEN
        ALTER TABLE clients ADD COLUMN stripe_customer_id VARCHAR(255);
    END IF;

    -- Add engagement score
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                   WHERE table_name = 'clients' AND column_name = 'engagement_score') THEN
        ALTER TABLE clients ADD COLUMN engagement_score INTEGER DEFAULT 0;
    END IF;
END $$;

-- =====================================================
-- 7. CREATE INDEXES
-- =====================================================

CREATE INDEX IF NOT EXISTS idx_staff_assignments_project_id ON staff_assignments(project_id);
CREATE INDEX IF NOT EXISTS idx_staff_assignments_staff_id ON staff_assignments(staff_id);
CREATE INDEX IF NOT EXISTS idx_client_reviews_client_id ON client_reviews(client_id);
CREATE INDEX IF NOT EXISTS idx_client_reviews_project_id ON client_reviews(project_id);
CREATE INDEX IF NOT EXISTS idx_project_deliveries_project_id ON project_deliveries(project_id);
CREATE INDEX IF NOT EXISTS idx_revenue_stripe_payment_id ON revenue(stripe_payment_id);
CREATE INDEX IF NOT EXISTS idx_projects_ghl_opportunity_id ON projects(ghl_opportunity_id);
CREATE INDEX IF NOT EXISTS idx_projects_ghl_pipeline_id ON projects(ghl_pipeline_id);
CREATE INDEX IF NOT EXISTS idx_clients_stripe_customer_id ON clients(stripe_customer_id);

-- =====================================================
-- 8. CREATE TRIGGERS
-- =====================================================

CREATE TRIGGER IF NOT EXISTS update_staff_updated_at
    BEFORE UPDATE ON staff
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER IF NOT EXISTS update_staff_assignments_updated_at
    BEFORE UPDATE ON staff_assignments
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER IF NOT EXISTS update_client_reviews_updated_at
    BEFORE UPDATE ON client_reviews
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER IF NOT EXISTS update_project_deliveries_updated_at
    BEFORE UPDATE ON project_deliveries
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- =====================================================
-- 9. KEY PIPELINE/STAGE CONSTANTS
-- =====================================================

-- These are stored as reference - use in application code:
-- PLANNING Pipeline ID: L2s9gNWdWzCbutNTC4DE
-- PLANNING "Booked" Stage ID: bad101e4-ff48-4ab8-845a-1660f0c0c7da

COMMENT ON TABLE projects IS 'Projects are only created when opportunity moves to PLANNING pipeline (ID: L2s9gNWdWzCbutNTC4DE)';
COMMENT ON COLUMN projects.ghl_pipeline_id IS 'GHL Pipeline ID - "Booked" requires L2s9gNWdWzCbutNTC4DE';
COMMENT ON COLUMN projects.ghl_stage_id IS 'GHL Stage ID - "Booked" stage is bad101e4-ff48-4ab8-845a-1660f0c0c7da';
COMMENT ON COLUMN revenue.stripe_payment_id IS 'Stripe payment intent ID - only track ACTUAL payments, not estimates';

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================
