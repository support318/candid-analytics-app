-- Sample Data for Candid Studios Analytics Dashboard
-- Corrected to match actual database schema

-- Insert Sample Clients
INSERT INTO clients (ghl_contact_id, first_name, last_name, email, phone, lead_source, status, lifecycle_stage, tags, created_at, first_inquiry_date) VALUES
('ghl_001', 'Sarah', 'Johnson', 'sarah.johnson@email.com', '+1-555-0101', 'website', 'active', 'customer', ARRAY['wedding', 'vip'], NOW() - INTERVAL '11 months', NOW() - INTERVAL '11 months'),
('ghl_002', 'Emily', 'Chen', 'emily.chen@email.com', '+1-555-0102', 'instagram', 'active', 'customer', ARRAY['portrait', 'family'], NOW() - INTERVAL '10 months', NOW() - INTERVAL '10 months'),
('ghl_003', 'David', 'Rodriguez', 'david.r@business.com', '+1-555-0103', 'referral', 'active', 'customer', ARRAY['corporate'], NOW() - INTERVAL '9 months', NOW() - INTERVAL '9 months'),
('ghl_004', 'Jessica', 'Smith', 'jessica.smith@email.com', '+1-555-0104', 'google', 'active', 'customer', ARRAY['wedding'], NOW() - INTERVAL '8 months', NOW() - INTERVAL '8 months'),
('ghl_005', 'Tech', 'Startup', 'events@techstartup.com', '+1-555-0105', 'referral', 'active', 'customer', ARRAY['corporate'], NOW() - INTERVAL '7 months', NOW() - INTERVAL '7 months'),
('ghl_006', 'Amanda', 'Martinez', 'amanda.m@email.com', '+1-555-0106', 'facebook', 'active', 'customer', ARRAY['maternity'], NOW() - INTERVAL '6 months', NOW() - INTERVAL '6 months'),
('ghl_007', 'Robert', 'Thompson', 'robert.thompson@email.com', '+1-555-0107', 'website', 'active', 'customer', ARRAY['wedding'], NOW() - INTERVAL '5 months', NOW() - INTERVAL '5 months'),
('ghl_008', 'Creative', 'Agency', 'hello@creativeagency.com', '+1-555-0108', 'google', 'active', 'customer', ARRAY['commercial'], NOW() - INTERVAL '4 months', NOW() - INTERVAL '4 months'),
('ghl_009', 'Jennifer', 'Davis', 'jennifer.davis@email.com', '+1-555-0109', 'instagram', 'active', 'customer', ARRAY['engagement'], NOW() - INTERVAL '3 months', NOW() - INTERVAL '3 months'),
('ghl_010', 'Williams', 'Family', 'williams.family@email.com', '+1-555-0110', 'referral', 'active', 'customer', ARRAY['family'], NOW() - INTERVAL '2 months', NOW() - INTERVAL '2 months'),
('ghl_011', 'Nicole', 'Lee', 'nicole.lee@email.com', '+1-555-0111', 'website', 'booked', 'prospect', ARRAY['wedding'], NOW() - INTERVAL '60 days', NOW() - INTERVAL '60 days'),
('ghl_012', 'Corporate', 'Events', 'bookings@corpevents.com', '+1-555-0112', 'referral', 'booked', 'prospect', ARRAY['corporate'], NOW() - INTERVAL '50 days', NOW() - INTERVAL '50 days'),
('ghl_013', 'Sophia', 'Anderson', 'sophia.a@email.com', '+1-555-0113', 'active', 'customer', ARRAY['senior'], NOW() - INTERVAL '40 days', NOW() - INTERVAL '40 days'),
('ghl_014', 'James', 'Brown', 'james.brown@email.com', '+1-555-0114', 'instagram', 'active', 'customer', ARRAY['anniversary'], NOW() - INTERVAL '30 days', NOW() - INTERVAL '30 days'),
('ghl_015', 'Real', 'Estate', 'photos@realestategroup.com', '+1-555-0115', 'website', 'active', 'customer', ARRAY['real-estate'], NOW() - INTERVAL '25 days', NOW() - INTERVAL '25 days'),
('ghl_016', 'Maria', 'Garcia', 'maria.garcia@email.com', '+1-555-0116', 'facebook', 'lead', 'lead', ARRAY['wedding'], NOW() - INTERVAL '20 days', NOW() - INTERVAL '20 days'),
('ghl_017', 'Peterson', 'Family', 'peterson.fam@email.com', '+1-555-0117', 'referral', 'lead', 'lead', ARRAY['newborn'], NOW() - INTERVAL '15 days', NOW() - INTERVAL '15 days'),
('ghl_018', 'Tech', 'Conference', 'organizer@techconf.com', '+1-555-0118', 'google', 'lead', 'lead', ARRAY['event'], NOW() - INTERVAL '10 days', NOW() - INTERVAL '10 days'),
('ghl_019', 'Ashley', 'White', 'ashley.white@email.com', '+1-555-0119', 'instagram', 'lead', 'lead', ARRAY['engagement'], NOW() - INTERVAL '5 days', NOW() - INTERVAL '5 days'),
('ghl_020', 'Downtown', 'Restaurant', 'manager@restaurant.com', '+1-555-0120', 'website', 'lead', 'lead', ARRAY['commercial'], NOW() - INTERVAL '2 days', NOW() - INTERVAL '2 days')
ON CONFLICT (ghl_contact_id) DO NOTHING;

-- Get client IDs for FK relationships
DO $$
DECLARE
    v_client1 uuid; v_client2 uuid; v_client3 uuid; v_client4 uuid; v_client5 uuid;
    v_client6 uuid; v_client7 uuid; v_client8 uuid; v_client9 uuid; v_client10 uuid;
    v_client11 uuid; v_client12 uuid; v_client13 uuid; v_client14 uuid; v_client15 uuid;
    v_inq1 uuid; v_inq2 uuid; v_inq3 uuid; v_inq4 uuid; v_inq5 uuid;
    v_inq6 uuid; v_inq7 uuid; v_inq8 uuid; v_inq9 uuid; v_inq10 uuid;
    v_inq11 uuid; v_inq12 uuid; v_inq13 uuid; v_inq14 uuid; v_inq15 uuid;
BEGIN
    -- Get client IDs
    SELECT id INTO v_client1 FROM clients WHERE ghl_contact_id = 'ghl_001';
    SELECT id INTO v_client2 FROM clients WHERE ghl_contact_id = 'ghl_002';
    SELECT id INTO v_client3 FROM clients WHERE ghl_contact_id = 'ghl_003';
    SELECT id INTO v_client4 FROM clients WHERE ghl_contact_id = 'ghl_004';
    SELECT id INTO v_client5 FROM clients WHERE ghl_contact_id = 'ghl_005';
    SELECT id INTO v_client6 FROM clients WHERE ghl_contact_id = 'ghl_006';
    SELECT id INTO v_client7 FROM clients WHERE ghl_contact_id = 'ghl_007';
    SELECT id INTO v_client8 FROM clients WHERE ghl_contact_id = 'ghl_008';
    SELECT id INTO v_client9 FROM clients WHERE ghl_contact_id = 'ghl_009';
    SELECT id INTO v_client10 FROM clients WHERE ghl_contact_id = 'ghl_010';
    SELECT id INTO v_client11 FROM clients WHERE ghl_contact_id = 'ghl_011';
    SELECT id INTO v_client12 FROM clients WHERE ghl_contact_id = 'ghl_012';
    SELECT id INTO v_client13 FROM clients WHERE ghl_contact_id = 'ghl_013';
    SELECT id INTO v_client14 FROM clients WHERE ghl_contact_id = 'ghl_014';
    SELECT id INTO v_client15 FROM clients WHERE ghl_contact_id = 'ghl_015';

    -- Insert Inquiries
    INSERT INTO inquiries (client_id, ghl_opportunity_id, inquiry_date, inquiry_source, event_type, desired_event_date,
                          interested_in_photography, interested_in_videography, interested_in_drone,
                          estimated_budget, status, outcome) VALUES
    (v_client1, 'opp_001', NOW() - INTERVAL '11 months', 'website', 'wedding', NOW() - INTERVAL '10 months', true, true, true, 4500, 'booked', 'won'),
    (v_client2, 'opp_002', NOW() - INTERVAL '10 months', 'instagram', 'portrait', NOW() - INTERVAL '9 months', true, false, false, 850, 'booked', 'won'),
    (v_client3, 'opp_003', NOW() - INTERVAL '9 months', 'referral', 'headshots', NOW() - INTERVAL '8 months', true, false, false, 1500, 'booked', 'won'),
    (v_client4, 'opp_004', NOW() - INTERVAL '8 months', 'google', 'wedding', NOW() - INTERVAL '7 months', true, true, false, 5200, 'booked', 'won'),
    (v_client5, 'opp_005', NOW() - INTERVAL '7 months', 'referral', 'corporate-event', NOW() - INTERVAL '6 months', true, true, false, 2800, 'booked', 'won'),
    (v_client6, 'opp_006', NOW() - INTERVAL '6 months', 'facebook', 'maternity', NOW() - INTERVAL '5 months', true, false, false, 950, 'booked', 'won'),
    (v_client7, 'opp_007', NOW() - INTERVAL '5 months', 'website', 'wedding', NOW() - INTERVAL '4 months', true, true, true, 6500, 'booked', 'won'),
    (v_client8, 'opp_008', NOW() - INTERVAL '4 months', 'google', 'commercial', NOW() - INTERVAL '3 months', true, false, false, 2200, 'booked', 'won'),
    (v_client9, 'opp_009', NOW() - INTERVAL '3 months', 'instagram', 'engagement', NOW() - INTERVAL '2 months', true, false, false, 750, 'booked', 'won'),
    (v_client10, 'opp_010', NOW() - INTERVAL '2 months', 'referral', 'family', NOW() - INTERVAL '1 month', true, false, false, 900, 'booked', 'won'),
    (v_client11, 'opp_011', NOW() - INTERVAL '60 days', 'website', 'wedding', NOW() + INTERVAL '90 days', true, true, true, 5800, 'booked', 'won'),
    (v_client12, 'opp_012', NOW() - INTERVAL '50 days', 'referral', 'corporate-event', NOW() + INTERVAL '30 days', true, true, false, 3200, 'booked', 'won'),
    (v_client13, 'opp_013', NOW() - INTERVAL '40 days', 'google', 'senior', NOW() - INTERVAL '35 days', true, false, false, 650, 'booked', 'won'),
    (v_client14, 'opp_014', NOW() - INTERVAL '30 days', 'instagram', 'anniversary', NOW() - INTERVAL '25 days', true, true, false, 1000, 'booked', 'won'),
    (v_client15, 'opp_015', NOW() - INTERVAL '25 days', 'website', 'real-estate', NOW() - INTERVAL '20 days', true, false, true, 1600, 'booked', 'won')
    ON CONFLICT (ghl_opportunity_id) DO NOTHING
    RETURNING id INTO v_inq1, v_inq2, v_inq3, v_inq4, v_inq5, v_inq6, v_inq7, v_inq8, v_inq9, v_inq10, v_inq11, v_inq12, v_inq13, v_inq14, v_inq15;

    -- Get inquiry IDs
    SELECT id INTO v_inq1 FROM inquiries WHERE ghl_opportunity_id = 'opp_001';
    SELECT id INTO v_inq2 FROM inquiries WHERE ghl_opportunity_id = 'opp_002';
    SELECT id INTO v_inq3 FROM inquiries WHERE ghl_opportunity_id = 'opp_003';
    SELECT id INTO v_inq4 FROM inquiries WHERE ghl_opportunity_id = 'opp_004';
    SELECT id INTO v_inq5 FROM inquiries WHERE ghl_opportunity_id = 'opp_005';
    SELECT id INTO v_inq6 FROM inquiries WHERE ghl_opportunity_id = 'opp_006';
    SELECT id INTO v_inq7 FROM inquiries WHERE ghl_opportunity_id = 'opp_007';
    SELECT id INTO v_inq8 FROM inquiries WHERE ghl_opportunity_id = 'opp_008';
    SELECT id INTO v_inq9 FROM inquiries WHERE ghl_opportunity_id = 'opp_009';
    SELECT id INTO v_inq10 FROM inquiries WHERE ghl_opportunity_id = 'opp_010';
    SELECT id INTO v_inq11 FROM inquiries WHERE ghl_opportunity_id = 'opp_011';
    SELECT id INTO v_inq12 FROM inquiries WHERE ghl_opportunity_id = 'opp_012';
    SELECT id INTO v_inq13 FROM inquiries WHERE ghl_opportunity_id = 'opp_013';
    SELECT id INTO v_inq14 FROM inquiries WHERE ghl_opportunity_id = 'opp_014';
    SELECT id INTO v_inq15 FROM inquiries WHERE ghl_opportunity_id = 'opp_015';

    -- Insert Projects
    INSERT INTO projects (client_id, inquiry_id, ghl_opportunity_id, project_name, event_type, event_date, booking_date,
                         venue_city, venue_state, photography_hours, videography_hours, drone_services,
                         total_revenue, assigned_photographer, assigned_videographer, status, services) VALUES
    (v_client1, v_inq1, 'opp_001', 'Johnson Wedding', 'wedding', NOW() - INTERVAL '10 months', NOW() - INTERVAL '11 months', 'Dallas', 'TX', 8, 8, true, 4500, 'Alex Thompson', 'Sam Rivera', 'completed', ARRAY['photography', 'videography', 'drone']),
    (v_client2, v_inq2, 'opp_002', 'Chen Family Portrait', 'portrait', NOW() - INTERVAL '9 months', NOW() - INTERVAL '10 months', 'Austin', 'TX', 2, NULL, false, 850, 'Jordan Lee', NULL, 'completed', ARRAY['photography']),
    (v_client3, v_inq3, 'opp_003', 'Rodriguez Headshots', 'headshots', NOW() - INTERVAL '8 months', NOW() - INTERVAL '9 months', 'Houston', 'TX', 3, NULL, false, 1500, 'Alex Thompson', NULL, 'completed', ARRAY['photography']),
    (v_client4, v_inq4, 'opp_004', 'Smith Wedding', 'wedding', NOW() - INTERVAL '7 months', NOW() - INTERVAL '8 months', 'San Antonio', 'TX', 8, 8, false, 5200, 'Alex Thompson', 'Sam Rivera', 'completed', ARRAY['photography', 'videography']),
    (v_client5, v_inq5, 'opp_005', 'Tech Startup Event', 'corporate-event', NOW() - INTERVAL '6 months', NOW() - INTERVAL '7 months', 'Dallas', 'TX', 6, 6, false, 2800, 'Jordan Lee', 'Sam Rivera', 'completed', ARRAY['photography', 'videography']),
    (v_client6, v_inq6, 'opp_006', 'Martinez Maternity', 'maternity', NOW() - INTERVAL '5 months', NOW() - INTERVAL '6 months', 'Fort Worth', 'TX', 2, NULL, false, 950, 'Jordan Lee', NULL, 'completed', ARRAY['photography']),
    (v_client7, v_inq7, 'opp_007', 'Thompson Wedding', 'wedding', NOW() - INTERVAL '4 months', NOW() - INTERVAL '5 months', 'Austin', 'TX', 10, 10, true, 6500, 'Alex Thompson', 'Sam Rivera', 'completed', ARRAY['photography', 'videography', 'drone']),
    (v_client8, v_inq8, 'opp_008', 'Creative Agency Commercial', 'commercial', NOW() - INTERVAL '3 months', NOW() - INTERVAL '4 months', 'Dallas', 'TX', 4, NULL, false, 2200, 'Jordan Lee', NULL, 'completed', ARRAY['photography']),
    (v_client9, v_inq9, 'opp_009', 'Davis Engagement', 'engagement', NOW() - INTERVAL '2 months', NOW() - INTERVAL '3 months', 'Houston', 'TX', 2, NULL, false, 750, 'Alex Thompson', NULL, 'completed', ARRAY['photography']),
    (v_client10, v_inq10, 'opp_010', 'Williams Family Session', 'family', NOW() - INTERVAL '1 month', NOW() - INTERVAL '2 months', 'Dallas', 'TX', 2, NULL, false, 900, 'Jordan Lee', NULL, 'completed', ARRAY['photography']),
    (v_client11, v_inq11, 'opp_011', 'Lee Wedding', 'wedding', NOW() + INTERVAL '90 days', NOW() - INTERVAL '60 days', 'Austin', 'TX', 10, 10, true, 5800, 'Alex Thompson', 'Sam Rivera', 'in-progress', ARRAY['photography', 'videography', 'drone']),
    (v_client12, v_inq12, 'opp_012', 'Corporate Events Co Event', 'corporate-event', NOW() + INTERVAL '30 days', NOW() - INTERVAL '50 days', 'Dallas', 'TX', 8, 8, false, 3200, 'Jordan Lee', 'Sam Rivera', 'in-progress', ARRAY['photography', 'videography']),
    (v_client13, v_inq13, 'opp_013', 'Anderson Senior Portrait', 'senior', NOW() - INTERVAL '35 days', NOW() - INTERVAL '40 days', 'San Antonio', 'TX', 2, NULL, false, 650, 'Alex Thompson', NULL, 'completed', ARRAY['photography']),
    (v_client14, v_inq14, 'opp_014', 'Brown Anniversary', 'anniversary', NOW() - INTERVAL '25 days', NOW() - INTERVAL '30 days', 'Houston', 'TX', 3, 3, false, 1000, 'Sam Rivera', NULL, 'completed', ARRAY['photography', 'videography']),
    (v_client15, v_inq15, 'opp_015', 'Real Estate Luxury Home', 'real-estate', NOW() - INTERVAL '20 days', NOW() - INTERVAL '25 days', 'Dallas', 'TX', 3, NULL, true, 1600, 'Jordan Lee', NULL, 'completed', ARRAY['photography', 'drone'])
    ON CONFLICT (ghl_opportunity_id) DO NOTHING;

    -- Insert Revenue (payments)
    INSERT INTO revenue (client_id, project_id, amount, payment_method, payment_date, payment_type)
    SELECT client_id, id, total_revenue, 'credit_card', event_date + INTERVAL '7 days', 'full_payment'
    FROM projects WHERE status = 'completed' AND event_date < NOW()
    ON CONFLICT DO NOTHING;

    -- Insert Reviews (for completed projects)
    INSERT INTO reviews (client_id, project_id, rating, review_text, review_date, would_recommend)
    SELECT
        p.client_id,
        p.id,
        CASE WHEN random() > 0.3 THEN 5 ELSE 4 END,  -- Most 5 stars, some 4 stars
        'Great experience! Highly recommend!',
        p.event_date + INTERVAL '14 days',
        true
    FROM projects p
    WHERE p.status = 'completed' AND p.event_date < NOW() - INTERVAL '14 days'
    ON CONFLICT DO NOTHING;

END $$;

-- Summary
SELECT
    'Sample Data Inserted!' as status,
    (SELECT COUNT(*) FROM clients) as total_clients,
    (SELECT COUNT(*) FROM inquiries) as total_inquiries,
    (SELECT COUNT(*) FROM projects) as total_projects,
    (SELECT COUNT(*) FROM revenue) as total_revenue_records,
    (SELECT COALESCE(SUM(amount), 0) FROM revenue) as total_revenue_amount,
    (SELECT COUNT(*) FROM reviews) as total_reviews;
