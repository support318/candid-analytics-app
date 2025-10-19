-- Sample Data for Candid Studios Analytics Dashboard
-- This creates realistic data for the past 12 months

-- Clear existing data (optional - comment out if you want to keep existing data)
-- TRUNCATE TABLE client_reviews, revenue_transactions, project_deliverables, staff_assignments, projects, inquiries, clients RESTART IDENTITY CASCADE;

-- Insert Sample Clients (20 clients)
INSERT INTO clients (id, ghl_contact_id, full_name, email, phone, status, tags, location, created_at, updated_at) VALUES
('c1000001-0000-0000-0000-000000000001', 'ghl_001', 'Sarah & Michael Johnson', 'sarah.johnson@email.com', '+1-555-0101', 'active', ARRAY['wedding', 'vip'], 'Dallas, TX', NOW() - INTERVAL '11 months', NOW()),
('c1000001-0000-0000-0000-000000000002', 'ghl_002', 'Emily Chen', 'emily.chen@email.com', '+1-555-0102', 'active', ARRAY['portrait', 'family'], 'Austin, TX', NOW() - INTERVAL '10 months', NOW()),
('c1000001-0000-0000-0000-000000000003', 'ghl_003', 'David Rodriguez', 'david.r@business.com', '+1-555-0103', 'active', ARRAY['corporate', 'headshots'], 'Houston, TX', NOW() - INTERVAL '9 months', NOW()),
('c1000001-0000-0000-0000-000000000004', 'ghl_004', 'Jessica & Ryan Smith', 'jessica.smith@email.com', '+1-555-0104', 'active', ARRAY['wedding', 'video'], 'San Antonio, TX', NOW() - INTERVAL '8 months', NOW()),
('c1000001-0000-0000-0000-000000000005', 'ghl_005', 'Tech Startup Inc', 'events@techstartup.com', '+1-555-0105', 'active', ARRAY['corporate', 'event'], 'Dallas, TX', NOW() - INTERVAL '7 months', NOW()),
('c1000001-0000-0000-0000-000000000006', 'ghl_006', 'Amanda Martinez', 'amanda.m@email.com', '+1-555-0106', 'active', ARRAY['maternity', 'family'], 'Fort Worth, TX', NOW() - INTERVAL '6 months', NOW()),
('c1000001-0000-0000-0000-000000000007', 'ghl_007', 'Robert & Lisa Thompson', 'robert.thompson@email.com', '+1-555-0107', 'active', ARRAY['wedding', 'drone'], 'Austin, TX', NOW() - INTERVAL '5 months', NOW()),
('c1000001-0000-0000-0000-000000000008', 'ghl_008', 'Creative Agency LLC', 'hello@creativeagency.com', '+1-555-0108', 'active', ARRAY['commercial', 'product'], 'Dallas, TX', NOW() - INTERVAL '4 months', NOW()),
('c1000001-0000-0000-0000-000000000009', 'ghl_009', 'Jennifer & Mark Davis', 'jennifer.davis@email.com', '+1-555-0109', 'active', ARRAY['engagement', 'couple'], 'Houston, TX', NOW() - INTERVAL '3 months', NOW()),
('c1000001-0000-0000-0000-000000000010', 'ghl_010', 'The Williams Family', 'williams.family@email.com', '+1-555-0110', 'active', ARRAY['family', 'portrait'], 'Dallas, TX', NOW() - INTERVAL '2 months', NOW()),
('c1000001-0000-0000-0000-000000000011', 'ghl_011', 'Nicole & Brandon Lee', 'nicole.lee@email.com', '+1-555-0111', 'active', ARRAY['wedding', 'photo+video'], 'Austin, TX', NOW() - INTERVAL '60 days', NOW()),
('c1000001-0000-0000-0000-000000000012', 'ghl_012', 'Corporate Events Co', 'bookings@corpevents.com', '+1-555-0112', 'active', ARRAY['corporate', 'recurring'], 'Dallas, TX', NOW() - INTERVAL '50 days', NOW()),
('c1000001-0000-0000-0000-000000000013', 'ghl_013', 'Sophia Anderson', 'sophia.a@email.com', '+1-555-0113', 'active', ARRAY['senior', 'portrait'], 'San Antonio, TX', NOW() - INTERVAL '40 days', NOW()),
('c1000001-0000-0000-0000-000000000014', 'ghl_014', 'James & Rachel Brown', 'james.brown@email.com', '+1-555-0114', 'active', ARRAY['anniversary', 'couple'], 'Houston, TX', NOW() - INTERVAL '30 days', NOW()),
('c1000001-0000-0000-0000-000000000015', 'ghl_015', 'Real Estate Pros', 'photos@realestategroup.com', '+1-555-0115', 'active', ARRAY['real-estate', 'commercial'], 'Dallas, TX', NOW() - INTERVAL '25 days', NOW()),
('c1000001-0000-0000-0000-000000000016', 'ghl_016', 'Maria Garcia', 'maria.garcia@email.com', '+1-555-0116', 'lead', ARRAY['wedding'], 'Austin, TX', NOW() - INTERVAL '20 days', NOW()),
('c1000001-0000-0000-0000-000000000017', 'ghl_017', 'The Peterson Family', 'peterson.fam@email.com', '+1-555-0117', 'lead', ARRAY['newborn', 'family'], 'Dallas, TX', NOW() - INTERVAL '15 days', NOW()),
('c1000001-0000-0000-0000-000000000018', 'ghl_018', 'Tech Conference 2025', 'organizer@techconf.com', '+1-555-0118', 'lead', ARRAY['event', 'corporate'], 'Houston, TX', NOW() - INTERVAL '10 days', NOW()),
('c1000001-0000-0000-0000-000000000019', 'ghl_019', 'Ashley & Chris White', 'ashley.white@email.com', '+1-555-0119', 'lead', ARRAY['engagement'], 'San Antonio, TX', NOW() - INTERVAL '5 days', NOW()),
('c1000001-0000-0000-0000-000000000020', 'ghl_020', 'Downtown Restaurant', 'manager@restaurant.com', '+1-555-0120', 'lead', ARRAY['commercial', 'food'], 'Dallas, TX', NOW() - INTERVAL '2 days', NOW());

-- Insert Sample Inquiries (30 inquiries - mix of converted and not)
INSERT INTO inquiries (id, client_id, source, service_type, event_date, budget_range, status, inquiry_date, converted_to_booking) VALUES
('i1000001-0000-0000-0000-000000000001', 'c1000001-0000-0000-0000-000000000001', 'website', 'wedding', NOW() + INTERVAL '60 days', '$3000-$5000', 'converted', NOW() - INTERVAL '11 months', true),
('i1000001-0000-0000-0000-000000000002', 'c1000001-0000-0000-0000-000000000002', 'instagram', 'portrait', NOW() - INTERVAL '9 months', '$500-$1000', 'converted', NOW() - INTERVAL '10 months', true),
('i1000001-0000-0000-0000-000000000003', 'c1000001-0000-0000-0000-000000000003', 'referral', 'headshots', NOW() - INTERVAL '8 months', '$1000-$2000', 'converted', NOW() - INTERVAL '9 months', true),
('i1000001-0000-0000-0000-000000000004', 'c1000001-0000-0000-0000-000000000004', 'google', 'wedding', NOW() - INTERVAL '7 months', '$4000-$6000', 'converted', NOW() - INTERVAL '8 months', true),
('i1000001-0000-0000-0000-000000000005', 'c1000001-0000-0000-0000-000000000005', 'referral', 'event', NOW() - INTERVAL '6 months', '$2000-$3000', 'converted', NOW() - INTERVAL '7 months', true),
('i1000001-0000-0000-0000-000000000006', 'c1000001-0000-0000-0000-000000000006', 'facebook', 'maternity', NOW() - INTERVAL '5 months', '$800-$1200', 'converted', NOW() - INTERVAL '6 months', true),
('i1000001-0000-0000-0000-000000000007', 'c1000001-0000-0000-0000-000000000007', 'website', 'wedding', NOW() - INTERVAL '4 months', '$5000-$7000', 'converted', NOW() - INTERVAL '5 months', true),
('i1000001-0000-0000-0000-000000000008', 'c1000001-0000-0000-0000-000000000008', 'google', 'commercial', NOW() - INTERVAL '3 months', '$1500-$2500', 'converted', NOW() - INTERVAL '4 months', true),
('i1000001-0000-0000-0000-000000000009', 'c1000001-0000-0000-0000-000000000009', 'instagram', 'engagement', NOW() - INTERVAL '2 months', '$600-$1000', 'converted', NOW() - INTERVAL '3 months', true),
('i1000001-0000-0000-0000-000000000010', 'c1000001-0000-0000-0000-000000000010', 'referral', 'family', NOW() - INTERVAL '1 month', '$700-$1200', 'converted', NOW() - INTERVAL '2 months', true),
('i1000001-0000-0000-0000-000000000011', 'c1000001-0000-0000-0000-000000000011', 'website', 'wedding', NOW() + INTERVAL '90 days', '$4500-$6500', 'converted', NOW() - INTERVAL '60 days', true),
('i1000001-0000-0000-0000-000000000012', 'c1000001-0000-0000-0000-000000000012', 'referral', 'corporate', NOW() + INTERVAL '30 days', '$2500-$4000', 'converted', NOW() - INTERVAL '50 days', true),
('i1000001-0000-0000-0000-000000000013', 'c1000001-0000-0000-0000-000000000013', 'google', 'senior', NOW() - INTERVAL '35 days', '$500-$800', 'converted', NOW() - INTERVAL '40 days', true),
('i1000001-0000-0000-0000-000000000014', 'c1000001-0000-0000-0000-000000000014', 'instagram', 'anniversary', NOW() - INTERVAL '25 days', '$800-$1200', 'converted', NOW() - INTERVAL '30 days', true),
('i1000001-0000-0000-0000-000000000015', 'c1000001-0000-0000-0000-000000000015', 'website', 'real-estate', NOW() - INTERVAL '20 days', '$1200-$2000', 'converted', NOW() - INTERVAL '25 days', true),
-- Not converted inquiries (leads still in pipeline)
('i1000001-0000-0000-0000-000000000016', 'c1000001-0000-0000-0000-000000000016', 'facebook', 'wedding', NOW() + INTERVAL '120 days', '$3000-$5000', 'consultation-scheduled', NOW() - INTERVAL '20 days', false),
('i1000001-0000-0000-0000-000000000017', 'c1000001-0000-0000-0000-000000000017', 'referral', 'newborn', NOW() + INTERVAL '30 days', '$600-$1000', 'quote-sent', NOW() - INTERVAL '15 days', false),
('i1000001-0000-0000-0000-000000000018', 'c1000001-0000-0000-0000-000000000018', 'google', 'event', NOW() + INTERVAL '180 days', '$3500-$5000', 'consultation-scheduled', NOW() - INTERVAL '10 days', false),
('i1000001-0000-0000-0000-000000000019', 'c1000001-0000-0000-0000-000000000019', 'instagram', 'engagement', NOW() + INTERVAL '60 days', '$500-$800', 'new-inquiry', NOW() - INTERVAL '5 days', false),
('i1000001-0000-0000-0000-000000000020', 'c1000001-0000-0000-0000-000000000020', 'website', 'commercial', NOW() + INTERVAL '45 days', '$1500-$2500', 'quote-sent', NOW() - INTERVAL '2 days', false);

-- Insert Sample Projects (15 completed/in-progress projects)
INSERT INTO projects (id, client_id, inquiry_id, project_type, event_date, location, status, booking_value, services_provided, created_at, updated_at) VALUES
('p1000001-0000-0000-0000-000000000001', 'c1000001-0000-0000-0000-000000000001', 'i1000001-0000-0000-0000-000000000001', 'wedding', NOW() - INTERVAL '10 months', 'Dallas Arboretum, Dallas TX', 'completed', 4500.00, ARRAY['photography', 'videography', 'drone'], NOW() - INTERVAL '11 months', NOW() - INTERVAL '9 months'),
('p1000001-0000-0000-0000-000000000002', 'c1000001-0000-0000-0000-000000000002', 'i1000001-0000-0000-0000-000000000002', 'portrait', NOW() - INTERVAL '9 months', 'Studio, Austin TX', 'completed', 850.00, ARRAY['photography'], NOW() - INTERVAL '10 months', NOW() - INTERVAL '8 months'),
('p1000001-0000-0000-0000-000000000003', 'c1000001-0000-0000-0000-000000000003', 'i1000001-0000-0000-0000-000000000003', 'headshots', NOW() - INTERVAL '8 months', 'Corporate Office, Houston TX', 'completed', 1500.00, ARRAY['photography'], NOW() - INTERVAL '9 months', NOW() - INTERVAL '7 months'),
('p1000001-0000-0000-0000-000000000004', 'c1000001-0000-0000-0000-000000000004', 'i1000001-0000-0000-0000-000000000004', 'wedding', NOW() - INTERVAL '7 months', 'Historic Chapel, San Antonio TX', 'completed', 5200.00, ARRAY['photography', 'videography'], NOW() - INTERVAL '8 months', NOW() - INTERVAL '6 months'),
('p1000001-0000-0000-0000-000000000005', 'c1000001-0000-0000-0000-000000000005', 'i1000001-0000-0000-0000-000000000005', 'event', NOW() - INTERVAL '6 months', 'Convention Center, Dallas TX', 'completed', 2800.00, ARRAY['photography', 'videography'], NOW() - INTERVAL '7 months', NOW() - INTERVAL '5 months'),
('p1000001-0000-0000-0000-000000000006', 'c1000001-0000-0000-0000-000000000006', 'i1000001-0000-0000-0000-000000000006', 'maternity', NOW() - INTERVAL '5 months', 'Outdoor Location, Fort Worth TX', 'completed', 950.00, ARRAY['photography'], NOW() - INTERVAL '6 months', NOW() - INTERVAL '4 months'),
('p1000001-0000-0000-0000-000000000007', 'c1000001-0000-0000-0000-000000000007', 'i1000001-0000-0000-0000-000000000007', 'wedding', NOW() - INTERVAL '4 months', 'Lake Travis, Austin TX', 'completed', 6500.00, ARRAY['photography', 'videography', 'drone'], NOW() - INTERVAL '5 months', NOW() - INTERVAL '3 months'),
('p1000001-0000-0000-0000-000000000008', 'c1000001-0000-0000-0000-000000000008', 'i1000001-0000-0000-0000-000000000008', 'commercial', NOW() - INTERVAL '3 months', 'Studio, Dallas TX', 'completed', 2200.00, ARRAY['photography', 'editing'], NOW() - INTERVAL '4 months', NOW() - INTERVAL '2 months'),
('p1000001-0000-0000-0000-000000000009', 'c1000001-0000-0000-0000-000000000009', 'i1000001-0000-0000-0000-000000000009', 'engagement', NOW() - INTERVAL '2 months', 'Downtown Houston, Houston TX', 'completed', 750.00, ARRAY['photography'], NOW() - INTERVAL '3 months', NOW() - INTERVAL '1 month'),
('p1000001-0000-0000-0000-000000000010', 'c1000001-0000-0000-0000-000000000010', 'i1000001-0000-0000-0000-000000000010', 'family', NOW() - INTERVAL '1 month', 'Park, Dallas TX', 'completed', 900.00, ARRAY['photography'], NOW() - INTERVAL '2 months', NOW() - INTERVAL '15 days'),
('p1000001-0000-0000-0000-000000000011', 'c1000001-0000-0000-0000-000000000011', 'i1000001-0000-0000-0000-000000000011', 'wedding', NOW() + INTERVAL '90 days', 'Vineyard, Austin TX', 'in-progress', 5800.00, ARRAY['photography', 'videography', 'drone'], NOW() - INTERVAL '60 days', NOW()),
('p1000001-0000-0000-0000-000000000012', 'c1000001-0000-0000-0000-000000000012', 'i1000001-0000-0000-0000-000000000012', 'corporate', NOW() + INTERVAL '30 days', 'Hotel Ballroom, Dallas TX', 'in-progress', 3200.00, ARRAY['photography', 'videography'], NOW() - INTERVAL '50 days', NOW()),
('p1000001-0000-0000-0000-000000000013', 'c1000001-0000-0000-0000-000000000013', 'i1000001-0000-0000-0000-000000000013', 'senior', NOW() - INTERVAL '35 days', 'Campus, San Antonio TX', 'completed', 650.00, ARRAY['photography'], NOW() - INTERVAL '40 days', NOW() - INTERVAL '25 days'),
('p1000001-0000-0000-0000-000000000014', 'c1000001-0000-0000-0000-000000000014', 'i1000001-0000-0000-0000-000000000014', 'anniversary', NOW() - INTERVAL '25 days', 'Restaurant, Houston TX', 'completed', 1000.00, ARRAY['photography', 'videography'], NOW() - INTERVAL '30 days', NOW() - INTERVAL '15 days'),
('p1000001-0000-0000-0000-000000000015', 'c1000001-0000-0000-0000-000000000015', 'i1000001-0000-0000-0000-000000000015', 'real-estate', NOW() - INTERVAL '20 days', 'Luxury Home, Dallas TX', 'completed', 1600.00, ARRAY['photography', 'drone'], NOW() - INTERVAL '25 days', NOW() - INTERVAL '10 days');

-- Insert Revenue Transactions
INSERT INTO revenue_transactions (id, project_id, client_id, transaction_type, amount, payment_method, transaction_date, notes) VALUES
-- Project 1 (Wedding - $4500)
('r1000001-0000-0000-0000-000000000001', 'p1000001-0000-0000-0000-000000000001', 'c1000001-0000-0000-0000-000000000001', 'deposit', 1500.00, 'credit_card', NOW() - INTERVAL '11 months', '33% deposit'),
('r1000001-0000-0000-0000-000000000002', 'p1000001-0000-0000-0000-000000000001', 'c1000001-0000-0000-0000-000000000001', 'final_payment', 3000.00, 'bank_transfer', NOW() - INTERVAL '10 months', 'Final payment'),
-- Project 2 (Portrait - $850)
('r1000001-0000-0000-0000-000000000003', 'p1000001-0000-0000-0000-000000000002', 'c1000001-0000-0000-0000-000000000002', 'full_payment', 850.00, 'credit_card', NOW() - INTERVAL '9 months', 'Full payment'),
-- Project 3 (Headshots - $1500)
('r1000001-0000-0000-0000-000000000004', 'p1000001-0000-0000-0000-000000000003', 'c1000001-0000-0000-0000-000000000003', 'full_payment', 1500.00, 'check', NOW() - INTERVAL '8 months', 'Corporate check'),
-- Project 4 (Wedding - $5200)
('r1000001-0000-0000-0000-000000000005', 'p1000001-0000-0000-0000-000000000004', 'c1000001-0000-0000-0000-000000000004', 'deposit', 2000.00, 'credit_card', NOW() - INTERVAL '8 months', 'Deposit'),
('r1000001-0000-0000-0000-000000000006', 'p1000001-0000-0000-0000-000000000004', 'c1000001-0000-0000-0000-000000000004', 'final_payment', 3200.00, 'credit_card', NOW() - INTERVAL '7 months', 'Final payment'),
-- Project 5 (Event - $2800)
('r1000001-0000-0000-0000-000000000007', 'p1000001-0000-0000-0000-000000000005', 'c1000001-0000-0000-0000-000000000005', 'deposit', 1000.00, 'bank_transfer', NOW() - INTERVAL '7 months', 'Deposit'),
('r1000001-0000-0000-0000-000000000008', 'p1000001-0000-0000-0000-000000000005', 'c1000001-0000-0000-0000-000000000005', 'final_payment', 1800.00, 'bank_transfer', NOW() - INTERVAL '6 months', 'Final payment'),
-- Project 6 (Maternity - $950)
('r1000001-0000-0000-0000-000000000009', 'p1000001-0000-0000-0000-000000000006', 'c1000001-0000-0000-0000-000000000006', 'full_payment', 950.00, 'credit_card', NOW() - INTERVAL '5 months', 'Full payment'),
-- Project 7 (Wedding - $6500)
('r1000001-0000-0000-0000-000000000010', 'p1000001-0000-0000-0000-000000000007', 'c1000001-0000-0000-0000-000000000007', 'deposit', 2500.00, 'credit_card', NOW() - INTERVAL '5 months', 'Deposit'),
('r1000001-0000-0000-0000-000000000011', 'p1000001-0000-0000-0000-000000000007', 'c1000001-0000-0000-0000-000000000007', 'final_payment', 4000.00, 'credit_card', NOW() - INTERVAL '4 months', 'Final payment'),
-- Project 8 (Commercial - $2200)
('r1000001-0000-0000-0000-000000000012', 'p1000001-0000-0000-0000-000000000008', 'c1000001-0000-0000-0000-000000000008', 'full_payment', 2200.00, 'bank_transfer', NOW() - INTERVAL '3 months', 'Full payment'),
-- Project 9 (Engagement - $750)
('r1000001-0000-0000-0000-000000000013', 'p1000001-0000-0000-0000-000000000009', 'c1000001-0000-0000-0000-000000000009', 'full_payment', 750.00, 'credit_card', NOW() - INTERVAL '2 months', 'Full payment'),
-- Project 10 (Family - $900)
('r1000001-0000-0000-0000-000000000014', 'p1000001-0000-0000-0000-000000000010', 'c1000001-0000-0000-0000-000000000010', 'full_payment', 900.00, 'credit_card', NOW() - INTERVAL '1 month', 'Full payment'),
-- Project 11 (Wedding - deposit only so far)
('r1000001-0000-0000-0000-000000000015', 'p1000001-0000-0000-0000-000000000011', 'c1000001-0000-0000-0000-000000000011', 'deposit', 2000.00, 'credit_card', NOW() - INTERVAL '60 days', 'Deposit'),
-- Project 12 (Corporate - deposit only)
('r1000001-0000-0000-0000-000000000016', 'p1000001-0000-0000-0000-000000000012', 'c1000001-0000-0000-0000-000000000012', 'deposit', 1200.00, 'bank_transfer', NOW() - INTERVAL '50 days', 'Deposit'),
-- Project 13 (Senior - $650)
('r1000001-0000-0000-0000-000000000017', 'p1000001-0000-0000-0000-000000000013', 'c1000001-0000-0000-0000-000000000013', 'full_payment', 650.00, 'credit_card', NOW() - INTERVAL '35 days', 'Full payment'),
-- Project 14 (Anniversary - $1000)
('r1000001-0000-0000-0000-000000000018', 'p1000001-0000-0000-0000-000000000014', 'c1000001-0000-0000-0000-000000000014', 'full_payment', 1000.00, 'credit_card', NOW() - INTERVAL '25 days', 'Full payment'),
-- Project 15 (Real Estate - $1600)
('r1000001-0000-0000-0000-000000000019', 'p1000001-0000-0000-0000-000000000015', 'c1000001-0000-0000-0000-000000000015', 'full_payment', 1600.00, 'bank_transfer', NOW() - INTERVAL '20 days', 'Full payment');

-- Insert Project Deliverables (tracking delivery times)
INSERT INTO project_deliverables (id, project_id, deliverable_type, expected_delivery_date, actual_delivery_date, status) VALUES
-- Photo deliveries (7-14 day turnaround)
('d1000001-0000-0000-0000-000000000001', 'p1000001-0000-0000-0000-000000000001', 'photos', NOW() - INTERVAL '9 months 20 days', NOW() - INTERVAL '9 months 22 days', 'delivered'),
('d1000001-0000-0000-0000-000000000002', 'p1000001-0000-0000-0000-000000000002', 'photos', NOW() - INTERVAL '8 months 20 days', NOW() - INTERVAL '8 months 18 days', 'delivered'),
('d1000001-0000-0000-0000-000000000003', 'p1000001-0000-0000-0000-000000000003', 'photos', NOW() - INTERVAL '7 months 20 days', NOW() - INTERVAL '7 months 17 days', 'delivered'),
('d1000001-0000-0000-0000-000000000004', 'p1000001-0000-0000-0000-000000000004', 'photos', NOW() - INTERVAL '6 months 20 days', NOW() - INTERVAL '6 months 19 days', 'delivered'),
('d1000001-0000-0000-0000-000000000005', 'p1000001-0000-0000-0000-000000000006', 'photos', NOW() - INTERVAL '4 months 20 days', NOW() - INTERVAL '4 months 16 days', 'delivered'),
('d1000001-0000-0000-0000-000000000006', 'p1000001-0000-0000-0000-000000000007', 'photos', NOW() - INTERVAL '3 months 20 days', NOW() - INTERVAL '3 months 18 days', 'delivered'),
('d1000001-0000-0000-0000-000000000007', 'p1000001-0000-0000-0000-000000000009', 'photos', NOW() - INTERVAL '1 month 20 days', NOW() - INTERVAL '1 month 17 days', 'delivered'),
('d1000001-0000-0000-0000-000000000008', 'p1000001-0000-0000-0000-000000000010', 'photos', NOW() - INTERVAL '15 days', NOW() - INTERVAL '12 days', 'delivered'),
-- Video deliveries (21-30 day turnaround)
('d1000001-0000-0000-0000-000000000009', 'p1000001-0000-0000-0000-000000000001', 'video', NOW() - INTERVAL '9 months', NOW() - INTERVAL '8 months 28 days', 'delivered'),
('d1000001-0000-0000-0000-000000000010', 'p1000001-0000-0000-0000-000000000004', 'video', NOW() - INTERVAL '6 months', NOW() - INTERVAL '5 months 28 days', 'delivered'),
('d1000001-0000-0000-0000-000000000011', 'p1000001-0000-0000-0000-000000000007', 'video', NOW() - INTERVAL '3 months', NOW() - INTERVAL '2 months 27 days', 'delivered');

-- Insert Staff Assignments
INSERT INTO staff_assignments (id, project_id, staff_name, role, hours_worked) VALUES
('s1000001-0000-0000-0000-000000000001', 'p1000001-0000-0000-0000-000000000001', 'Alex Thompson', 'lead_photographer', 8),
('s1000001-0000-0000-0000-000000000002', 'p1000001-0000-0000-0000-000000000001', 'Sam Rivera', 'videographer', 8),
('s1000001-0000-0000-0000-000000000003', 'p1000001-0000-0000-0000-000000000002', 'Jordan Lee', 'photographer', 2),
('s1000001-0000-0000-0000-000000000004', 'p1000001-0000-0000-0000-000000000003', 'Alex Thompson', 'lead_photographer', 3),
('s1000001-0000-0000-0000-000000000005', 'p1000001-0000-0000-0000-000000000004', 'Alex Thompson', 'lead_photographer', 8),
('s1000001-0000-0000-0000-000000000006', 'p1000001-0000-0000-0000-000000000004', 'Sam Rivera', 'videographer', 8),
('s1000001-0000-0000-0000-000000000007', 'p1000001-0000-0000-0000-000000000005', 'Jordan Lee', 'photographer', 6),
('s1000001-0000-0000-0000-000000000008', 'p1000001-0000-0000-0000-000000000005', 'Sam Rivera', 'videographer', 6),
('s1000001-0000-0000-0000-000000000009', 'p1000001-0000-0000-0000-000000000006', 'Jordan Lee', 'photographer', 2),
('s1000001-0000-0000-0000-000000000010', 'p1000001-0000-0000-0000-000000000007', 'Alex Thompson', 'lead_photographer', 10),
('s1000001-0000-0000-0000-000000000011', 'p1000001-0000-0000-0000-000000000007', 'Sam Rivera', 'videographer', 10),
('s1000001-0000-0000-0000-000000000012', 'p1000001-0000-0000-0000-000000000008', 'Jordan Lee', 'photographer', 4),
('s1000001-0000-0000-0000-000000000013', 'p1000001-0000-0000-0000-000000000009', 'Alex Thompson', 'lead_photographer', 2),
('s1000001-0000-0000-0000-000000000014', 'p1000001-0000-0000-0000-000000000010', 'Jordan Lee', 'photographer', 2),
('s1000001-0000-0000-0000-000000000015', 'p1000001-0000-0000-0000-000000000013', 'Alex Thompson', 'lead_photographer', 2),
('s1000001-0000-0000-0000-000000000016', 'p1000001-0000-0000-0000-000000000014', 'Sam Rivera', 'videographer', 3),
('s1000001-0000-0000-0000-000000000017', 'p1000001-0000-0000-0000-000000000015', 'Jordan Lee', 'photographer', 3);

-- Insert Client Reviews
INSERT INTO client_reviews (id, client_id, project_id, rating, review_text, review_date, would_recommend) VALUES
('rev10001-0000-0000-0000-000000000001', 'c1000001-0000-0000-0000-000000000001', 'p1000001-0000-0000-0000-000000000001', 5, 'Absolutely stunning photos and video! Alex and Sam were amazing to work with. They captured every special moment perfectly!', NOW() - INTERVAL '9 months', true),
('rev10001-0000-0000-0000-000000000002', 'c1000001-0000-0000-0000-000000000002', 'p1000001-0000-0000-0000-000000000002', 5, 'Jordan was professional and made us feel so comfortable. The family portraits are beautiful!', NOW() - INTERVAL '8 months', true),
('rev10001-0000-0000-0000-000000000003', 'c1000001-0000-0000-0000-000000000003', 'p1000001-0000-0000-0000-000000000003', 5, 'Great corporate headshots! Quick turnaround and excellent quality.', NOW() - INTERVAL '7 months', true),
('rev10001-0000-0000-0000-000000000004', 'c1000001-0000-0000-0000-000000000004', 'p1000001-0000-0000-0000-000000000004', 5, 'Our wedding photos and video exceeded all expectations! Worth every penny.', NOW() - INTERVAL '6 months', true),
('rev10001-0000-0000-0000-000000000005', 'c1000001-0000-0000-0000-000000000005', 'p1000001-0000-0000-0000-000000000005', 4, 'Great event coverage. Very professional team. Only minor issue was delivery took a bit longer than expected.', NOW() - INTERVAL '5 months', true),
('rev10001-0000-0000-0000-000000000006', 'c1000001-0000-0000-0000-000000000006', 'p1000001-0000-0000-0000-000000000006', 5, 'Beautiful maternity photos! Jordan has such a great eye for lighting and angles.', NOW() - INTERVAL '4 months', true),
('rev10001-0000-0000-0000-000000000007', 'c1000001-0000-0000-0000-000000000007', 'p1000001-0000-0000-0000-000000000007', 5, 'The drone footage was incredible! Best decision we made for our wedding photography package.', NOW() - INTERVAL '3 months', true),
('rev10001-0000-0000-0000-000000000008', 'c1000001-0000-0000-0000-000000000008', 'p1000001-0000-0000-0000-000000000008', 5, 'Professional product photography for our agency. Will definitely use again!', NOW() - INTERVAL '2 months', true),
('rev10001-0000-0000-0000-000000000009', 'c1000001-0000-0000-0000-000000000009', 'p1000001-0000-0000-0000-000000000009', 5, 'Perfect engagement photos! Alex captured our personalities perfectly.', NOW() - INTERVAL '1 month', true),
('rev10001-0000-0000-0000-000000000010', 'c1000001-0000-0000-0000-000000000010', 'p1000001-0000-0000-0000-000000000010', 4, 'Great family session. Kids were a handful but Jordan was patient and got amazing shots!', NOW() - INTERVAL '15 days', true);

-- Summary Stats
SELECT
    'Data Import Complete!' as status,
    (SELECT COUNT(*) FROM clients) as total_clients,
    (SELECT COUNT(*) FROM inquiries) as total_inquiries,
    (SELECT COUNT(*) FROM projects) as total_projects,
    (SELECT COUNT(*) FROM revenue_transactions) as total_transactions,
    (SELECT SUM(amount) FROM revenue_transactions) as total_revenue,
    (SELECT COUNT(*) FROM client_reviews) as total_reviews,
    (SELECT ROUND(AVG(rating), 2) FROM client_reviews) as avg_rating;
