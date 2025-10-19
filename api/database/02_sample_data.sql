-- Sample Data for Candid Studios Analytics Dashboard
-- Realistic photography/videography business data for 2024-2025

-- Insert sample clients
INSERT INTO clients (id, name, email, phone, business_type, created_at) VALUES
(gen_random_uuid(), 'Sarah & Michael Johnson', 'sarah.johnson@email.com', '214-555-0101', 'Wedding', '2024-01-15 10:30:00'),
(gen_random_uuid(), 'Tech Innovations Inc', 'marketing@techinnovations.com', '214-555-0102', 'Corporate', '2024-02-01 14:20:00'),
(gen_random_uuid(), 'The Martinez Family', 'maria.martinez@email.com', '214-555-0103', 'Portrait', '2024-02-20 11:45:00'),
(gen_random_uuid(), 'Green Valley Real Estate', 'info@greenvalleyrealty.com', '214-555-0104', 'Commercial', '2024-03-05 09:15:00'),
(gen_random_uuid(), 'Jennifer & David Kim', 'jen.kim@email.com', '214-555-0105', 'Wedding', '2024-03-12 16:00:00'),
(gen_random_uuid(), 'Summit Conference Center', 'events@summitcc.com', '214-555-0106', 'Event', '2024-04-08 13:30:00'),
(gen_random_uuid(), 'The Thompson Family', 'robert.thompson@email.com', '214-555-0107', 'Portrait', '2024-04-22 10:00:00'),
(gen_random_uuid(), 'Bloom Fashion Boutique', 'contact@bloomfashion.com', '214-555-0108', 'Commercial', '2024-05-10 15:45:00'),
(gen_random_uuid(), 'Emily & James Rodriguez', 'emily.rodriguez@email.com', '214-555-0109', 'Wedding', '2024-05-28 12:20:00'),
(gen_random_uuid(), 'City Sports Academy', 'admin@citysports.com', '214-555-0110', 'Event', '2024-06-15 11:00:00'),
(gen_random_uuid(), 'The Anderson Family', 'lisa.anderson@email.com', '214-555-0111', 'Portrait', '2024-07-03 14:30:00'),
(gen_random_uuid(), 'Horizon Tech Solutions', 'pr@horizontech.com', '214-555-0112', 'Corporate', '2024-07-18 10:15:00'),
(gen_random_uuid(), 'Amanda & Chris Taylor', 'amanda.taylor@email.com', '214-555-0113', 'Wedding', '2024-08-05 16:45:00'),
(gen_random_uuid(), 'Downtown Art Gallery', 'curator@downtownart.com', '214-555-0114', 'Event', '2024-08-20 13:00:00'),
(gen_random_uuid(), 'Luxury Homes Realty', 'contact@luxuryhomes.com', '214-555-0115', 'Commercial', '2024-09-02 09:30:00');

-- Insert sample photographers/videographers
INSERT INTO team_members (id, name, role, email, phone, hourly_rate, is_active, created_at) VALUES
(gen_random_uuid(), 'Ryan Mitchell', 'Lead Photographer', 'ryan@candidstudios.net', '214-555-0201', 150.00, true, '2023-01-01'),
(gen_random_uuid(), 'Jessica Chen', 'Senior Photographer', 'jessica@candidstudios.net', '214-555-0202', 125.00, true, '2023-03-15'),
(gen_random_uuid(), 'Marcus Williams', 'Lead Videographer', 'marcus@candidstudios.net', '214-555-0203', 175.00, true, '2023-01-01'),
(gen_random_uuid(), 'Sarah Lopez', 'Videographer', 'sarah@candidstudios.net', '214-555-0204', 135.00, true, '2023-06-01'),
(gen_random_uuid(), 'Alex Turner', 'Photographer', 'alex@candidstudios.net', '214-555-0205', 100.00, true, '2024-01-15'),
(gen_random_uuid(), 'Emma Davis', 'Drone Operator', 'emma@candidstudios.net', '214-555-0206', 150.00, true, '2023-09-01');

-- Insert sample services
INSERT INTO services (id, name, description, base_price, category, is_active, created_at) VALUES
(gen_random_uuid(), 'Wedding Photography - Full Day', '10 hours of coverage, 2 photographers, all edited photos', 3500.00, 'Photography', true, '2023-01-01'),
(gen_random_uuid(), 'Wedding Photography - Half Day', '6 hours of coverage, 1 photographer, all edited photos', 2200.00, 'Photography', true, '2023-01-01'),
(gen_random_uuid(), 'Wedding Videography - Full Day', '10 hours of coverage, cinematic edit, highlight reel', 4500.00, 'Videography', true, '2023-01-01'),
(gen_random_uuid(), 'Wedding Videography - Highlight Reel', '8 hours of coverage, 5-minute highlight video', 2800.00, 'Videography', true, '2023-01-01'),
(gen_random_uuid(), 'Corporate Event Coverage', 'Full event photography coverage, same-day editing', 1800.00, 'Photography', true, '2023-01-01'),
(gen_random_uuid(), 'Corporate Video Production', 'Professional video production for corporate events', 2500.00, 'Videography', true, '2023-01-01'),
(gen_random_uuid(), 'Family Portrait Session', '2-hour session, multiple locations, all edited photos', 650.00, 'Photography', true, '2023-01-01'),
(gen_random_uuid(), 'Real Estate Photography', 'Interior and exterior photos, HDR processing, drone shots', 450.00, 'Photography', true, '2023-01-01'),
(gen_random_uuid(), 'Drone Aerial Photography', 'Aerial photography and video, up to 2 hours', 800.00, 'Drone', true, '2023-01-01'),
(gen_random_uuid(), 'Commercial Product Photography', 'Professional product shots for marketing', 1200.00, 'Photography', true, '2023-01-01');

-- Get client IDs for project creation
DO $$
DECLARE
    client_sarah_michael UUID;
    client_tech_innovations UUID;
    client_martinez UUID;
    client_green_valley UUID;
    client_jennifer_david UUID;
    client_summit UUID;
    client_thompson UUID;
    client_bloom UUID;
    client_emily_james UUID;
    client_city_sports UUID;
    client_anderson UUID;
    client_horizon UUID;
    client_amanda_chris UUID;
    client_downtown_art UUID;
    client_luxury_homes UUID;

    service_wedding_photo_full UUID;
    service_wedding_video_full UUID;
    service_wedding_photo_half UUID;
    service_wedding_video_highlight UUID;
    service_corporate_photo UUID;
    service_corporate_video UUID;
    service_portrait UUID;
    service_real_estate UUID;
    service_drone UUID;
    service_product UUID;

    team_ryan UUID;
    team_jessica UUID;
    team_marcus UUID;
    team_sarah UUID;
    team_alex UUID;
    team_emma UUID;
BEGIN
    -- Get client IDs
    SELECT id INTO client_sarah_michael FROM clients WHERE name = 'Sarah & Michael Johnson';
    SELECT id INTO client_tech_innovations FROM clients WHERE name = 'Tech Innovations Inc';
    SELECT id INTO client_martinez FROM clients WHERE name = 'The Martinez Family';
    SELECT id INTO client_green_valley FROM clients WHERE name = 'Green Valley Real Estate';
    SELECT id INTO client_jennifer_david FROM clients WHERE name = 'Jennifer & David Kim';
    SELECT id INTO client_summit FROM clients WHERE name = 'Summit Conference Center';
    SELECT id INTO client_thompson FROM clients WHERE name = 'The Thompson Family';
    SELECT id INTO client_bloom FROM clients WHERE name = 'Bloom Fashion Boutique';
    SELECT id INTO client_emily_james FROM clients WHERE name = 'Emily & James Rodriguez';
    SELECT id INTO client_city_sports FROM clients WHERE name = 'City Sports Academy';
    SELECT id INTO client_anderson FROM clients WHERE name = 'The Anderson Family';
    SELECT id INTO client_horizon FROM clients WHERE name = 'Horizon Tech Solutions';
    SELECT id INTO client_amanda_chris FROM clients WHERE name = 'Amanda & Chris Taylor';
    SELECT id INTO client_downtown_art FROM clients WHERE name = 'Downtown Art Gallery';
    SELECT id INTO client_luxury_homes FROM clients WHERE name = 'Luxury Homes Realty';

    -- Get service IDs
    SELECT id INTO service_wedding_photo_full FROM services WHERE name = 'Wedding Photography - Full Day';
    SELECT id INTO service_wedding_video_full FROM services WHERE name = 'Wedding Videography - Full Day';
    SELECT id INTO service_wedding_photo_half FROM services WHERE name = 'Wedding Photography - Half Day';
    SELECT id INTO service_wedding_video_highlight FROM services WHERE name = 'Wedding Videography - Highlight Reel';
    SELECT id INTO service_corporate_photo FROM services WHERE name = 'Corporate Event Coverage';
    SELECT id INTO service_corporate_video FROM services WHERE name = 'Corporate Video Production';
    SELECT id INTO service_portrait FROM services WHERE name = 'Family Portrait Session';
    SELECT id INTO service_real_estate FROM services WHERE name = 'Real Estate Photography';
    SELECT id INTO service_drone FROM services WHERE name = 'Drone Aerial Photography';
    SELECT id INTO service_product FROM services WHERE name = 'Commercial Product Photography';

    -- Get team IDs
    SELECT id INTO team_ryan FROM team_members WHERE name = 'Ryan Mitchell';
    SELECT id INTO team_jessica FROM team_members WHERE name = 'Jessica Chen';
    SELECT id INTO team_marcus FROM team_members WHERE name = 'Marcus Williams';
    SELECT id INTO team_sarah FROM team_members WHERE name = 'Sarah Lopez';
    SELECT id INTO team_alex FROM team_members WHERE name = 'Alex Turner';
    SELECT id INTO team_emma FROM team_members WHERE name = 'Emma Davis';

    -- Insert projects with realistic timeline

    -- Project 1: Sarah & Michael Wedding (Completed)
    INSERT INTO projects (id, client_id, name, event_date, event_type, status, location, total_amount, deposit_amount, balance_due, created_at, completed_at)
    VALUES (gen_random_uuid(), client_sarah_michael, 'Sarah & Michael''s Wedding', '2024-06-15 15:00:00', 'Wedding', 'completed', 'The Mansion at Turtle Creek, Dallas, TX', 8800.00, 3000.00, 0.00, '2024-01-15', '2024-07-20');

    -- Project 2: Tech Innovations Corporate Event (Completed)
    INSERT INTO projects (id, client_id, name, event_date, event_type, status, location, total_amount, deposit_amount, balance_due, created_at, completed_at)
    VALUES (gen_random_uuid(), client_tech_innovations, 'Tech Innovations Annual Summit', '2024-03-20 09:00:00', 'Corporate Event', 'completed', 'Dallas Convention Center', 4300.00, 1500.00, 0.00, '2024-02-01', '2024-04-10');

    -- Project 3: Martinez Family Portraits (Completed)
    INSERT INTO projects (id, client_id, name, event_date, event_type, status, location, total_amount, deposit_amount, balance_due, created_at, completed_at)
    VALUES (gen_random_uuid(), client_martinez, 'Martinez Family Spring Portraits', '2024-04-05 10:00:00', 'Family Portrait', 'completed', 'White Rock Lake Park, Dallas, TX', 650.00, 200.00, 0.00, '2024-02-20', '2024-04-15');

    -- Project 4: Green Valley Real Estate (Completed)
    INSERT INTO projects (id, client_id, name, event_date, event_type, status, location, total_amount, deposit_amount, balance_due, created_at, completed_at)
    VALUES (gen_random_uuid(), client_green_valley, 'Luxury Home Listing - 5 Properties', '2024-04-10 13:00:00', 'Real Estate', 'completed', 'Highland Park, Dallas, TX', 2250.00, 0.00, 0.00, '2024-03-05', '2024-04-12');

    -- Project 5: Jennifer & David Wedding (Completed)
    INSERT INTO projects (id, client_id, name, event_date, event_type, status, location, total_amount, deposit_amount, balance_due, created_at, completed_at)
    VALUES (gen_random_uuid(), client_jennifer_david, 'Jennifer & David''s Wedding', '2024-08-10 16:00:00', 'Wedding', 'completed', 'Four Seasons Resort, Irving, TX', 8000.00, 2500.00, 0.00, '2024-03-12', '2024-09-05');

    -- Project 6: Summit Conference (Completed)
    INSERT INTO projects (id, client_id, name, event_date, event_type, status, location, total_amount, deposit_amount, balance_due, created_at, completed_at)
    VALUES (gen_random_uuid(), client_summit, 'Leadership Summit 2024', '2024-05-18 08:00:00', 'Conference', 'completed', 'Summit Conference Center, Plano, TX', 4300.00, 1500.00, 0.00, '2024-04-08', '2024-05-25');

    -- Project 7: Thompson Family Portraits (Completed)
    INSERT INTO projects (id, client_id, name, event_date, event_type, status, location, total_amount, deposit_amount, balance_due, created_at, completed_at)
    VALUES (gen_random_uuid(), client_thompson, 'Thompson Family Summer Portraits', '2024-06-30 17:00:00', 'Family Portrait', 'completed', 'Klyde Warren Park, Dallas, TX', 650.00, 200.00, 0.00, '2024-04-22', '2024-07-05');

    -- Project 8: Bloom Fashion (Completed)
    INSERT INTO projects (id, client_id, name, event_date, event_type, status, location, total_amount, deposit_amount, balance_due, created_at, completed_at)
    VALUES (gen_random_uuid(), client_bloom, 'Summer Collection Product Shoot', '2024-06-12 10:00:00', 'Commercial', 'completed', 'Bloom Fashion Studio, Dallas, TX', 1200.00, 0.00, 0.00, '2024-05-10', '2024-06-20');

    -- Project 9: Emily & James Wedding (Active - upcoming)
    INSERT INTO projects (id, client_id, name, event_date, event_type, status, location, total_amount, deposit_amount, balance_due, created_at)
    VALUES (gen_random_uuid(), client_emily_james, 'Emily & James'' Garden Wedding', '2025-05-24 14:00:00', 'Wedding', 'active', 'Dallas Arboretum and Botanical Garden', 9500.00, 3500.00, 6000.00, '2024-05-28');

    -- Project 10: City Sports Academy (Completed)
    INSERT INTO projects (id, client_id, name, event_date, event_type, status, location, total_amount, deposit_amount, balance_due, created_at, completed_at)
    VALUES (gen_random_uuid(), client_city_sports, 'Youth Championship Tournament', '2024-07-28 09:00:00', 'Sports Event', 'completed', 'City Sports Complex, Dallas, TX', 1800.00, 500.00, 0.00, '2024-06-15', '2024-08-05');

    -- Project 11: Anderson Family (Completed)
    INSERT INTO projects (id, client_id, name, event_date, event_type, status, location, total_amount, deposit_amount, balance_due, created_at, completed_at)
    VALUES (gen_random_uuid(), client_anderson, 'Anderson Family Fall Portraits', '2024-09-15 16:00:00', 'Family Portrait', 'completed', 'Reverchon Park, Dallas, TX', 650.00, 200.00, 0.00, '2024-07-03', '2024-09-20');

    -- Project 12: Horizon Tech (Active - upcoming)
    INSERT INTO projects (id, client_id, name, event_date, event_type, status, location, total_amount, deposit_amount, balance_due, created_at)
    VALUES (gen_random_uuid(), client_horizon, 'Horizon Product Launch Event', '2025-02-15 18:00:00', 'Corporate Event', 'active', 'The Joule Hotel, Dallas, TX', 5200.00, 2000.00, 3200.00, '2024-07-18');

    -- Project 13: Amanda & Chris Wedding (Active - upcoming)
    INSERT INTO projects (id, client_id, name, event_date, event_type, status, location, total_amount, deposit_amount, balance_due, created_at)
    VALUES (gen_random_uuid(), client_amanda_chris, 'Amanda & Chris'' Fall Wedding', '2025-10-18 17:00:00', 'Wedding', 'active', 'Rosewood Mansion on Turtle Creek', 9200.00, 3000.00, 6200.00, '2024-08-05');

    -- Project 14: Downtown Art Gallery (Completed)
    INSERT INTO projects (id, client_id, name, event_date, event_type, status, location, total_amount, deposit_amount, balance_due, created_at, completed_at)
    VALUES (gen_random_uuid(), client_downtown_art, 'Contemporary Art Exhibition Opening', '2024-09-28 19:00:00', 'Gallery Event', 'completed', 'Downtown Art Gallery, Dallas, TX', 2500.00, 800.00, 0.00, '2024-08-20', '2024-10-05');

    -- Project 15: Luxury Homes (Active - recurring)
    INSERT INTO projects (id, client_id, name, event_date, event_type, status, location, total_amount, deposit_amount, balance_due, created_at)
    VALUES (gen_random_uuid(), client_luxury_homes, 'Monthly Luxury Listing Photography', '2025-01-15 10:00:00', 'Real Estate', 'active', 'Various Locations, Dallas, TX', 1800.00, 0.00, 1800.00, '2024-09-02');

END $$;

-- Refresh materialized views to show KPIs
REFRESH MATERIALIZED VIEW monthly_revenue_kpi;
REFRESH MATERIALIZED VIEW quarterly_bookings_kpi;
REFRESH MATERIALIZED VIEW client_retention_kpi;
REFRESH MATERIALIZED VIEW average_project_value_kpi;
REFRESH MATERIALIZED VIEW photographer_utilization_kpi;
REFRESH MATERIALIZED VIEW service_popularity_kpi;
REFRESH MATERIALIZED VIEW revenue_by_service_type_kpi;
REFRESH MATERIALIZED VIEW outstanding_balance_kpi;
REFRESH MATERIALIZED VIEW booking_lead_time_kpi;
REFRESH MATERIALIZED VIEW repeat_client_rate_kpi;
REFRESH MATERIALIZED VIEW seasonal_trends_kpi;
