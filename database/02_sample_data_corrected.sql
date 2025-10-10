-- Sample Data for Candid Studios Analytics Dashboard
-- Realistic photography/videography business data for 2024-2025
-- Matches actual database schema

-- 1. Insert sample clients
INSERT INTO clients (id, ghl_contact_id, first_name, last_name, email, phone, lead_source, lead_source_detail, status, lifecycle_stage, lifetime_value, total_projects, first_inquiry_date, tags) VALUES
(gen_random_uuid(), 'GHL001', 'Sarah', 'Johnson', 'sarah.johnson@email.com', '214-555-0101', 'Website', 'Wedding inquiry form', 'completed', 'client', 8800.00, 1, '2024-01-10', ARRAY['wedding', 'summer-2024']),
(gen_random_uuid(), 'GHL002', 'Michael', 'Chen', 'michael.chen@techinnovations.com', '214-555-0102', 'Referral', 'Past client recommendation', 'completed', 'client', 4300.00, 1, '2024-01-28', ARRAY['corporate', 'repeat-client']),
(gen_random_uuid(), 'GHL003', 'Maria', 'Martinez', 'maria.martinez@email.com', '214-555-0103', 'Instagram', 'DM inquiry', 'completed', 'client', 650.00, 1, '2024-02-15', ARRAY['portrait', 'family']),
(gen_random_uuid(), 'GHL004', 'Robert', 'Green', 'contact@greenvalleyrealty.com', '214-555-0104', 'Google Ads', 'Real estate photography search', 'completed', 'client', 2250.00, 1, '2024-03-01', ARRAY['real-estate', 'commercial']),
(gen_random_uuid(), 'GHL005', 'Jennifer', 'Kim', 'jen.kim@email.com', '214-555-0105', 'Wedding Wire', 'Profile inquiry', 'completed', 'client', 8000.00, 1, '2024-03-05', ARRAY['wedding', 'fall-2024']),
(gen_random_uuid(), 'GHL006', 'David', 'Summit', 'events@summitcc.com', '214-555-0106', 'LinkedIn', 'Professional connection', 'completed', 'client', 4300.00, 1, '2024-04-01', ARRAY['corporate', 'event']),
(gen_random_uuid(), 'GHL007', 'Lisa', 'Thompson', 'lisa.thompson@email.com', '214-555-0107', 'Facebook', 'Facebook ad campaign', 'completed', 'client', 650.00, 1, '2024-04-18', ARRAY['portrait', 'family']),
(gen_random_uuid(), 'GHL008', 'Amanda', 'Bloom', 'contact@bloomfashion.com', '214-555-0108', 'Referral', 'Business network', 'completed', 'client', 1200.00, 1, '2024-05-05', ARRAY['commercial', 'fashion']),
(gen_random_uuid(), 'GHL009', 'Emily', 'Rodriguez', 'emily.rodriguez@email.com', '214-555-0109', 'The Knot', 'Vendor directory', 'booked', 'prospect', 9500.00, 1, '2024-05-20', ARRAY['wedding', 'spring-2025']),
(gen_random_uuid(), 'GHL010', 'James', 'Patterson', 'admin@citysports.com', '214-555-0110', 'Email Campaign', 'Sports photography offer', 'completed', 'client', 1800.00, 1, '2024-06-10', ARRAY['sports', 'event']),
(gen_random_uuid(), 'GHL011', 'Rachel', 'Anderson', 'rachel.anderson@email.com', '214-555-0111', 'Website', 'Family portrait inquiry', 'completed', 'client', 650.00, 1, '2024-06-28', ARRAY['portrait', 'family']),
(gen_random_uuid(), 'GHL012', 'Steven', 'Horizon', 'steven@horizontech.com', '214-555-0112', 'Trade Show', 'Dallas Tech Expo booth', 'booked', 'prospect', 5200.00, 1, '2024-07-10', ARRAY['corporate', 'product-launch']),
(gen_random_uuid(), 'GHL013', 'Amanda', 'Taylor', 'amanda.taylor@email.com', '214-555-0113', 'Instagram', 'Story swipe-up', 'booked', 'prospect', 9200.00, 1, '2024-07-28', ARRAY['wedding', 'fall-2025']),
(gen_random_uuid(), 'GHL014', 'Marcus', 'Downtown', 'curator@downtownart.com', '214-555-0114', 'Google Search', 'Event photography Dallas', 'completed', 'client', 2500.00, 1, '2024-08-12', ARRAY['gallery', 'event']),
(gen_random_uuid(), 'GHL015', 'Victoria', 'Luxury', 'victoria@luxuryhomes.com', '214-555-0115', 'Zillow Partnership', 'Premium listing service', 'booked', 'client', 1800.00, 1, '2024-08-28', ARRAY['real-estate', 'luxury']);

-- 2. Insert staff members
INSERT INTO staff (id, first_name, last_name, email, phone, role, employment_type, status, total_projects_assigned, total_projects_completed, average_client_rating, on_time_delivery_rate, timezone, hire_date) VALUES
(gen_random_uuid(), 'Ryan', 'Mitchell', 'ryan@candidstudios.net', '214-555-0201', 'photographer', 'full-time', 'active', 45, 42, 4.9, 98.5, 'America/Chicago', '2023-01-01'),
(gen_random_uuid(), 'Jessica', 'Chen', 'jessica@candidstudios.net', '214-555-0202', 'photographer', 'full-time', 'active', 38, 36, 4.8, 97.0, 'America/Chicago', '2023-03-15'),
(gen_random_uuid(), 'Marcus', 'Williams', 'marcus@candidstudios.net', '214-555-0203', 'videographer', 'full-time', 'active', 52, 50, 4.9, 99.0, 'America/Chicago', '2023-01-01'),
(gen_random_uuid(), 'Sarah', 'Lopez', 'sarah@candidstudios.net', '214-555-0204', 'videographer', 'full-time', 'active', 41, 39, 4.7, 96.5, 'America/Chicago', '2023-06-01'),
(gen_random_uuid(), 'Alex', 'Turner', 'alex@candidstudios.net', '214-555-0205', 'photographer', 'part-time', 'active', 22, 21, 4.6, 95.0, 'America/Chicago', '2024-01-15'),
(gen_random_uuid(), 'Emma', 'Davis', 'emma@candidstudios.net', '214-555-0206', 'videographer', 'contractor', 'active', 18, 17, 4.8, 98.0, 'America/Chicago', '2023-09-01');

-- 3. Insert venues
INSERT INTO venues (id, venue_name, venue_type, address_line1, city, state, zipcode, capacity, indoor_outdoor, parking_available, lighting_quality, total_events_shot, average_client_rating, photography_notes, videography_notes) VALUES
(gen_random_uuid(), 'The Mansion at Turtle Creek', 'Wedding Venue', '2821 Turtle Creek Blvd', 'Dallas', 'TX', '75219', 250, 'both', true, 'excellent', 15, 4.9, 'Beautiful natural light in ballroom, outdoor garden perfect for golden hour', 'Great acoustics, stable for gimbal work'),
(gen_random_uuid(), 'Dallas Convention Center', 'Conference Center', '650 S Griffin St', 'Dallas', 'TX', '75202', 5000, 'indoor', true, 'good', 8, 4.7, 'Bring additional lighting for large spaces', 'Echo can be challenging, use directional mics'),
(gen_random_uuid(), 'White Rock Lake Park', 'Outdoor Location', '8300 E Lawther Dr', 'Dallas', 'TX', '75218', null, 'outdoor', true, 'excellent', 42, 4.8, 'Best light 1 hour before sunset, watch for wind', 'Drone-friendly area with stunning aerial shots'),
(gen_random_uuid(), 'Four Seasons Resort', 'Hotel', '4150 N MacArthur Blvd', 'Irving', 'TX', '75038', 300, 'both', true, 'excellent', 22, 4.9, 'Ballroom has great architectural features', 'Elevator noise isolated, good for audio'),
(gen_random_uuid(), 'Summit Conference Center', 'Conference Facility', '5500 Granite Pkwy', 'Plano', 'TX', '75024', 1000, 'indoor', true, 'good', 12, 4.6, 'Multiple rooms require equipment planning', 'Stage area has good lighting setup'),
(gen_random_uuid(), 'Klyde Warren Park', 'Urban Park', '2012 Woodall Rodgers Fwy', 'Dallas', 'TX', '75201', null, 'outdoor', true, 'excellent', 28, 4.7, 'Urban backdrop with skyline views', 'Watch for ambient noise from nearby traffic'),
(gen_random_uuid(), 'Dallas Arboretum', 'Botanical Garden', '8525 Garland Rd', 'Dallas', 'TX', '75218', 400, 'outdoor', true, 'excellent', 35, 4.9, 'Stunning natural backgrounds year-round', 'Multiple ceremony sites, plan backup for rain'),
(gen_random_uuid(), 'Rosewood Mansion', 'Historic Mansion', '3200 Turtle Creek Blvd', 'Dallas', 'TX', '75219', 180, 'both', true, 'excellent', 18, 4.8, 'Elegant interior with dramatic staircases', 'Small spaces require wide-angle lenses');

-- Now insert projects and revenue using a DO block to reference the inserted data
DO $$
DECLARE
    -- Client IDs
    client_sarah UUID;
    client_michael UUID;
    client_maria UUID;
    client_robert UUID;
    client_jennifer UUID;
    client_david UUID;
    client_lisa UUID;
    client_amanda_bloom UUID;
    client_emily UUID;
    client_james UUID;
    client_rachel UUID;
    client_steven UUID;
    client_amanda_taylor UUID;
    client_marcus UUID;
    client_victoria UUID;

    -- Staff IDs
    staff_ryan UUID;
    staff_jessica UUID;
    staff_marcus_w UUID;
    staff_sarah UUID;
    staff_alex UUID;
    staff_emma UUID;

    -- Venue IDs
    venue_mansion UUID;
    venue_convention UUID;
    venue_white_rock UUID;
    venue_four_seasons UUID;
    venue_summit UUID;
    venue_klyde UUID;
    venue_arboretum UUID;
    venue_rosewood UUID;

    -- Project IDs for revenue linkage
    project_id_temp UUID;
BEGIN
    -- Get client IDs
    SELECT id INTO client_sarah FROM clients WHERE email = 'sarah.johnson@email.com';
    SELECT id INTO client_michael FROM clients WHERE email = 'michael.chen@techinnovations.com';
    SELECT id INTO client_maria FROM clients WHERE email = 'maria.martinez@email.com';
    SELECT id INTO client_robert FROM clients WHERE email = 'contact@greenvalleyrealty.com';
    SELECT id INTO client_jennifer FROM clients WHERE email = 'jen.kim@email.com';
    SELECT id INTO client_david FROM clients WHERE email = 'events@summitcc.com';
    SELECT id INTO client_lisa FROM clients WHERE email = 'lisa.thompson@email.com';
    SELECT id INTO client_amanda_bloom FROM clients WHERE email = 'contact@bloomfashion.com';
    SELECT id INTO client_emily FROM clients WHERE email = 'emily.rodriguez@email.com';
    SELECT id INTO client_james FROM clients WHERE email = 'admin@citysports.com';
    SELECT id INTO client_rachel FROM clients WHERE email = 'rachel.anderson@email.com';
    SELECT id INTO client_steven FROM clients WHERE email = 'steven@horizontech.com';
    SELECT id INTO client_amanda_taylor FROM clients WHERE email = 'amanda.taylor@email.com';
    SELECT id INTO client_marcus FROM clients WHERE email = 'curator@downtownart.com';
    SELECT id INTO client_victoria FROM clients WHERE email = 'victoria@luxuryhomes.com';

    -- Get staff IDs
    SELECT id INTO staff_ryan FROM staff WHERE email = 'ryan@candidstudios.net';
    SELECT id INTO staff_jessica FROM staff WHERE email = 'jessica@candidstudios.net';
    SELECT id INTO staff_marcus_w FROM staff WHERE email = 'marcus@candidstudios.net';
    SELECT id INTO staff_sarah FROM staff WHERE email = 'sarah@candidstudios.net';
    SELECT id INTO staff_alex FROM staff WHERE email = 'alex@candidstudios.net';
    SELECT id INTO staff_emma FROM staff WHERE email = 'emma@candidstudios.net';

    -- Get venue IDs
    SELECT id INTO venue_mansion FROM venues WHERE venue_name = 'The Mansion at Turtle Creek';
    SELECT id INTO venue_convention FROM venues WHERE venue_name = 'Dallas Convention Center';
    SELECT id INTO venue_white_rock FROM venues WHERE venue_name = 'White Rock Lake Park';
    SELECT id INTO venue_four_seasons FROM venues WHERE venue_name = 'Four Seasons Resort';
    SELECT id INTO venue_summit FROM venues WHERE venue_name = 'Summit Conference Center';
    SELECT id INTO venue_klyde FROM venues WHERE venue_name = 'Klyde Warren Park';
    SELECT id INTO venue_arboretum FROM venues WHERE venue_name = 'Dallas Arboretum';
    SELECT id INTO venue_rosewood FROM venues WHERE venue_name = 'Rosewood Mansion';

    -- Project 1: Sarah's Wedding (Completed)
    project_id_temp := gen_random_uuid();
    INSERT INTO projects (
        id, client_id, ghl_opportunity_id, project_name, project_number, event_type, event_date, booking_date,
        venue_id, venue_name, venue_address, venue_city, venue_state,
        photography_hours, photography_start_time, assigned_photographer, photographer_staff_id,
        videography_hours, videography_start_time, assigned_videographer, videographer_staff_id,
        drone_services, services,
        total_revenue, photography_revenue, videography_revenue, drone_revenue,
        status, pipeline_stage, created_at, completed_at
    ) VALUES (
        project_id_temp, client_sarah, 'OPP001', 'Sarah & Michael''s Wedding', 'PRJ-2024-001', 'Wedding', '2024-06-15', '2024-02-10',
        venue_mansion, 'The Mansion at Turtle Creek', '2821 Turtle Creek Blvd', 'Dallas', 'TX',
        10.0, '15:00', 'Ryan Mitchell', staff_ryan,
        10.0, '15:00', 'Marcus Williams', staff_marcus_w,
        true, ARRAY['photography', 'videography', 'drone'],
        8800.00, 3500.00, 4500.00, 800.00,
        'completed', 'delivered', '2024-01-15', '2024-07-20'
    );

    -- Revenue for Project 1
    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_sarah, '2024-02-10', 3000.00, 'credit_card', 'deposit', 'wedding', 'completed');
    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_sarah, '2024-05-15', 2900.00, 'credit_card', 'partial', 'wedding', 'completed');
    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_sarah, '2024-07-20', 2900.00, 'credit_card', 'final', 'wedding', 'completed');

    -- Project 2: Tech Innovations Event (Completed)
    project_id_temp := gen_random_uuid();
    INSERT INTO projects (
        id, client_id, ghl_opportunity_id, project_name, project_number, event_type, event_date, booking_date,
        venue_id, venue_name, venue_address, venue_city, venue_state,
        photography_hours, photography_start_time, assigned_photographer, photographer_staff_id,
        videography_hours, videography_start_time, assigned_videographer, videographer_staff_id,
        services, total_revenue, photography_revenue, videography_revenue,
        status, pipeline_stage, created_at, completed_at
    ) VALUES (
        project_id_temp, client_michael, 'OPP002', 'Tech Innovations Annual Summit', 'PRJ-2024-002', 'Corporate Event', '2024-03-20', '2024-02-05',
        venue_convention, 'Dallas Convention Center', '650 S Griffin St', 'Dallas', 'TX',
        8.0, '09:00', 'Jessica Chen', staff_jessica,
        8.0, '09:00', 'Sarah Lopez', staff_sarah,
        ARRAY['photography', 'videography'],
        4300.00, 1800.00, 2500.00,
        'completed', 'delivered', '2024-02-01', '2024-04-10'
    );

    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_michael, '2024-02-05', 1500.00, 'ach', 'deposit', 'corporate', 'completed');
    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_michael, '2024-04-10', 2800.00, 'ach', 'final', 'corporate', 'completed');

    -- Project 3: Martinez Family Portraits (Completed)
    project_id_temp := gen_random_uuid();
    INSERT INTO projects (
        id, client_id, ghl_opportunity_id, project_name, project_number, event_type, event_date, booking_date,
        venue_id, venue_name, venue_city, venue_state,
        photography_hours, photography_start_time, assigned_photographer, photographer_staff_id,
        services, total_revenue, photography_revenue,
        status, pipeline_stage, created_at, completed_at
    ) VALUES (
        project_id_temp, client_maria, 'OPP003', 'Martinez Family Spring Portraits', 'PRJ-2024-003', 'Family Portrait', '2024-04-05', '2024-02-25',
        venue_white_rock, 'White Rock Lake Park', 'Dallas', 'TX',
        2.0, '10:00', 'Ryan Mitchell', staff_ryan,
        ARRAY['photography'],
        650.00, 650.00,
        'completed', 'delivered', '2024-02-20', '2024-04-15'
    );

    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_maria, '2024-02-25', 200.00, 'credit_card', 'deposit', 'portrait', 'completed');
    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_maria, '2024-04-15', 450.00, 'credit_card', 'final', 'portrait', 'completed');

    -- Project 4: Green Valley Real Estate (Completed)
    project_id_temp := gen_random_uuid();
    INSERT INTO projects (
        id, client_id, ghl_opportunity_id, project_name, project_number, event_type, event_date, booking_date,
        venue_city, venue_state,
        photography_hours, photography_start_time, assigned_photographer, photographer_staff_id,
        drone_services, services, total_revenue, photography_revenue, drone_revenue,
        status, pipeline_stage, created_at, completed_at
    ) VALUES (
        project_id_temp, client_robert, 'OPP004', 'Luxury Home Listing - 5 Properties', 'PRJ-2024-004', 'Real Estate', '2024-04-10', '2024-03-08',
        'Dallas', 'TX',
        5.0, '13:00', 'Alex Turner', staff_alex,
        true, ARRAY['photography', 'drone'],
        2250.00, 1800.00, 450.00,
        'completed', 'delivered', '2024-03-05', '2024-04-12'
    );

    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_robert, '2024-04-12', 2250.00, 'ach', 'final', 'real_estate', 'completed');

    -- Project 5: Jennifer & David Wedding (Completed)
    project_id_temp := gen_random_uuid();
    INSERT INTO projects (
        id, client_id, ghl_opportunity_id, project_name, project_number, event_type, event_date, booking_date,
        venue_id, venue_name, venue_city, venue_state,
        photography_hours, photography_start_time, assigned_photographer, photographer_staff_id,
        videography_hours, videography_start_time, assigned_videographer, videographer_staff_id,
        services, total_revenue, photography_revenue, videography_revenue,
        status, pipeline_stage, created_at, completed_at
    ) VALUES (
        project_id_temp, client_jennifer, 'OPP005', 'Jennifer & David''s Wedding', 'PRJ-2024-005', 'Wedding', '2024-08-10', '2024-03-20',
        venue_four_seasons, 'Four Seasons Resort', 'Irving', 'TX',
        10.0, '16:00', 'Jessica Chen', staff_jessica,
        8.0, '16:00', 'Emma Davis', staff_emma,
        ARRAY['photography', 'videography'],
        8000.00, 3500.00, 4500.00,
        'completed', 'delivered', '2024-03-12', '2024-09-05'
    );

    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_jennifer, '2024-03-20', 2500.00, 'credit_card', 'deposit', 'wedding', 'completed');
    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_jennifer, '2024-09-05', 5500.00, 'credit_card', 'final', 'wedding', 'completed');

    -- Project 6: Summit Conference (Completed)
    project_id_temp := gen_random_uuid();
    INSERT INTO projects (
        id, client_id, ghl_opportunity_id, project_name, project_number, event_type, event_date, booking_date,
        venue_id, venue_name, venue_city, venue_state,
        photography_hours, photography_start_time, assigned_photographer, photographer_staff_id,
        videography_hours, videography_start_time, assigned_videographer, videographer_staff_id,
        services, total_revenue, photography_revenue, videography_revenue,
        status, pipeline_stage, created_at, completed_at
    ) VALUES (
        project_id_temp, client_david, 'OPP006', 'Leadership Summit 2024', 'PRJ-2024-006', 'Conference', '2024-05-18', '2024-04-10',
        venue_summit, 'Summit Conference Center', 'Plano', 'TX',
        8.0, '08:00', 'Ryan Mitchell', staff_ryan,
        8.0, '08:00', 'Marcus Williams', staff_marcus_w,
        ARRAY['photography', 'videography'],
        4300.00, 1800.00, 2500.00,
        'completed', 'delivered', '2024-04-08', '2024-05-25'
    );

    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_david, '2024-04-10', 1500.00, 'ach', 'deposit', 'corporate', 'completed');
    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_david, '2024-05-25', 2800.00, 'ach', 'final', 'corporate', 'completed');

    -- Project 7: Thompson Family Portraits (Completed)
    project_id_temp := gen_random_uuid();
    INSERT INTO projects (
        id, client_id, ghl_opportunity_id, project_name, project_number, event_type, event_date, booking_date,
        venue_id, venue_name, venue_city, venue_state,
        photography_hours, photography_start_time, assigned_photographer, photographer_staff_id,
        services, total_revenue, photography_revenue,
        status, pipeline_stage, created_at, completed_at
    ) VALUES (
        project_id_temp, client_lisa, 'OPP007', 'Thompson Family Summer Portraits', 'PRJ-2024-007', 'Family Portrait', '2024-06-30', '2024-04-25',
        venue_klyde, 'Klyde Warren Park', 'Dallas', 'TX',
        2.0, '17:00', 'Jessica Chen', staff_jessica,
        ARRAY['photography'],
        650.00, 650.00,
        'completed', 'delivered', '2024-04-22', '2024-07-05'
    );

    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_lisa, '2024-04-25', 200.00, 'credit_card', 'deposit', 'portrait', 'completed');
    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_lisa, '2024-07-05', 450.00, 'credit_card', 'final', 'portrait', 'completed');

    -- Project 8: Bloom Fashion Product Shoot (Completed)
    project_id_temp := gen_random_uuid();
    INSERT INTO projects (
        id, client_id, ghl_opportunity_id, project_name, project_number, event_type, event_date, booking_date,
        venue_city, venue_state,
        photography_hours, photography_start_time, assigned_photographer, photographer_staff_id,
        services, total_revenue, photography_revenue,
        status, pipeline_stage, created_at, completed_at
    ) VALUES (
        project_id_temp, client_amanda_bloom, 'OPP008', 'Summer Collection Product Shoot', 'PRJ-2024-008', 'Commercial', '2024-06-12', '2024-05-12',
        'Dallas', 'TX',
        4.0, '10:00', 'Alex Turner', staff_alex,
        ARRAY['photography'],
        1200.00, 1200.00,
        'completed', 'delivered', '2024-05-10', '2024-06-20'
    );

    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_amanda_bloom, '2024-06-20', 1200.00, 'ach', 'final', 'commercial', 'completed');

    -- Project 9: Emily & James Wedding (Active - Future)
    project_id_temp := gen_random_uuid();
    INSERT INTO projects (
        id, client_id, ghl_opportunity_id, project_name, project_number, event_type, event_date, booking_date,
        venue_id, venue_name, venue_city, venue_state,
        photography_hours, photography_start_time, assigned_photographer, photographer_staff_id,
        videography_hours, videography_start_time, assigned_videographer, videographer_staff_id,
        drone_services, services,
        total_revenue, photography_revenue, videography_revenue, drone_revenue,
        status, pipeline_stage, created_at
    ) VALUES (
        project_id_temp, client_emily, 'OPP009', 'Emily & James'' Garden Wedding', 'PRJ-2025-001', 'Wedding', '2025-05-24', '2024-06-01',
        venue_arboretum, 'Dallas Arboretum and Botanical Garden', 'Dallas', 'TX',
        10.0, '14:00', 'Ryan Mitchell', staff_ryan,
        10.0, '14:00', 'Marcus Williams', staff_marcus_w,
        true, ARRAY['photography', 'videography', 'drone'],
        9500.00, 3500.00, 5000.00, 1000.00,
        'confirmed', 'booked', '2024-05-28'
    );

    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_emily, '2024-06-01', 3500.00, 'credit_card', 'deposit', 'wedding', 'completed');

    -- Project 10: City Sports Tournament (Completed)
    project_id_temp := gen_random_uuid();
    INSERT INTO projects (
        id, client_id, ghl_opportunity_id, project_name, project_number, event_type, event_date, booking_date,
        venue_city, venue_state,
        photography_hours, photography_start_time, assigned_photographer, photographer_staff_id,
        services, total_revenue, photography_revenue,
        status, pipeline_stage, created_at, completed_at
    ) VALUES (
        project_id_temp, client_james, 'OPP010', 'Youth Championship Tournament', 'PRJ-2024-010', 'Sports Event', '2024-07-28', '2024-06-18',
        'Dallas', 'TX',
        6.0, '09:00', 'Alex Turner', staff_alex,
        ARRAY['photography'],
        1800.00, 1800.00,
        'completed', 'delivered', '2024-06-15', '2024-08-05'
    );

    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_james, '2024-06-18', 500.00, 'credit_card', 'deposit', 'event', 'completed');
    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_james, '2024-08-05', 1300.00, 'credit_card', 'final', 'event', 'completed');

    -- Project 11: Anderson Family Fall Portraits (Completed)
    project_id_temp := gen_random_uuid();
    INSERT INTO projects (
        id, client_id, ghl_opportunity_id, project_name, project_number, event_type, event_date, booking_date,
        venue_city, venue_state,
        photography_hours, photography_start_time, assigned_photographer, photographer_staff_id,
        services, total_revenue, photography_revenue,
        status, pipeline_stage, created_at, completed_at
    ) VALUES (
        project_id_temp, client_rachel, 'OPP011', 'Anderson Family Fall Portraits', 'PRJ-2024-011', 'Family Portrait', '2024-09-15', '2024-07-08',
        'Dallas', 'TX',
        2.0, '16:00', 'Jessica Chen', staff_jessica,
        ARRAY['photography'],
        650.00, 650.00,
        'completed', 'delivered', '2024-07-03', '2024-09-20'
    );

    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_rachel, '2024-07-08', 200.00, 'credit_card', 'deposit', 'portrait', 'completed');
    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_rachel, '2024-09-20', 450.00, 'credit_card', 'final', 'portrait', 'completed');

    -- Project 12: Horizon Product Launch (Active - Future)
    project_id_temp := gen_random_uuid();
    INSERT INTO projects (
        id, client_id, ghl_opportunity_id, project_name, project_number, event_type, event_date, booking_date,
        venue_city, venue_state,
        photography_hours, photography_start_time, assigned_photographer, photographer_staff_id,
        videography_hours, videography_start_time, assigned_videographer, videographer_staff_id,
        services, total_revenue, photography_revenue, videography_revenue,
        status, pipeline_stage, created_at
    ) VALUES (
        project_id_temp, client_steven, 'OPP012', 'Horizon Product Launch Event', 'PRJ-2025-002', 'Corporate Event', '2025-02-15', '2024-07-20',
        'Dallas', 'TX',
        6.0, '18:00', 'Jessica Chen', staff_jessica,
        6.0, '18:00', 'Sarah Lopez', staff_sarah,
        ARRAY['photography', 'videography'],
        5200.00, 2200.00, 3000.00,
        'confirmed', 'booked', '2024-07-18'
    );

    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_steven, '2024-07-20', 2000.00, 'ach', 'deposit', 'corporate', 'completed');

    -- Project 13: Amanda & Chris Fall Wedding (Active - Future)
    project_id_temp := gen_random_uuid();
    INSERT INTO projects (
        id, client_id, ghl_opportunity_id, project_name, project_number, event_type, event_date, booking_date,
        venue_id, venue_name, venue_city, venue_state,
        photography_hours, photography_start_time, assigned_photographer, photographer_staff_id,
        videography_hours, videography_start_time, assigned_videographer, videographer_staff_id,
        drone_services, services,
        total_revenue, photography_revenue, videography_revenue, drone_revenue,
        status, pipeline_stage, created_at
    ) VALUES (
        project_id_temp, client_amanda_taylor, 'OPP013', 'Amanda & Chris'' Fall Wedding', 'PRJ-2025-003', 'Wedding', '2025-10-18', '2024-08-08',
        venue_rosewood, 'Rosewood Mansion on Turtle Creek', 'Dallas', 'TX',
        10.0, '17:00', 'Ryan Mitchell', staff_ryan,
        8.0, '17:00', 'Emma Davis', staff_emma,
        true, ARRAY['photography', 'videography', 'drone'],
        9200.00, 3500.00, 4900.00, 800.00,
        'confirmed', 'booked', '2024-08-05'
    );

    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_amanda_taylor, '2024-08-08', 3000.00, 'credit_card', 'deposit', 'wedding', 'completed');

    -- Project 14: Downtown Art Gallery Event (Completed)
    project_id_temp := gen_random_uuid();
    INSERT INTO projects (
        id, client_id, ghl_opportunity_id, project_name, project_number, event_type, event_date, booking_date,
        venue_city, venue_state,
        photography_hours, photography_start_time, assigned_photographer, photographer_staff_id,
        videography_hours, videography_start_time, assigned_videographer, videographer_staff_id,
        services, total_revenue, photography_revenue, videography_revenue,
        status, pipeline_stage, created_at, completed_at
    ) VALUES (
        project_id_temp, client_marcus, 'OPP014', 'Contemporary Art Exhibition Opening', 'PRJ-2024-014', 'Gallery Event', '2024-09-28', '2024-08-22',
        'Dallas', 'TX',
        4.0, '19:00', 'Alex Turner', staff_alex,
        4.0, '19:00', 'Emma Davis', staff_emma,
        ARRAY['photography', 'videography'],
        2500.00, 1200.00, 1300.00,
        'completed', 'delivered', '2024-08-20', '2024-10-05'
    );

    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_marcus, '2024-08-22', 800.00, 'credit_card', 'deposit', 'event', 'completed');
    INSERT INTO revenue (project_id, client_id, payment_date, amount, payment_method, payment_type, service_type, status)
    VALUES (project_id_temp, client_marcus, '2024-10-05', 1700.00, 'credit_card', 'final', 'event', 'completed');

    -- Project 15: Luxury Homes Monthly Service (Active - Recurring)
    project_id_temp := gen_random_uuid();
    INSERT INTO projects (
        id, client_id, ghl_opportunity_id, project_name, project_number, event_type, event_date, booking_date,
        venue_city, venue_state,
        photography_hours, photography_start_time, assigned_photographer, photographer_staff_id,
        drone_services, services, total_revenue, photography_revenue, drone_revenue,
        status, pipeline_stage, created_at
    ) VALUES (
        project_id_temp, client_victoria, 'OPP015', 'January Luxury Listing Photography', 'PRJ-2025-004', 'Real Estate', '2025-01-15', '2024-09-05',
        'Dallas', 'TX',
        4.0, '10:00', 'Alex Turner', staff_alex,
        true, ARRAY['photography', 'drone'],
        1800.00, 1400.00, 400.00,
        'confirmed', 'booked', '2024-09-02'
    );

END $$;

-- Success message
SELECT 'Sample data successfully inserted!' as message,
       (SELECT COUNT(*) FROM clients) as total_clients,
       (SELECT COUNT(*) FROM staff) as total_staff,
       (SELECT COUNT(*) FROM venues) as total_venues,
       (SELECT COUNT(*) FROM projects) as total_projects,
       (SELECT COUNT(*) FROM revenue) as total_revenue_records;
