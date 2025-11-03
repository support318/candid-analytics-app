-- Add missing full_name column to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(255);

-- Also add is_active for compatibility with users routes
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;

-- Update existing users to set is_active based on status
UPDATE users SET is_active = (status = 'active') WHERE is_active IS NULL;
