-- Add 2FA support to users table
-- Migration: add_2fa_support.sql
-- Created: 2025-11-02

-- Add 2FA columns to users table
ALTER TABLE users
ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS two_factor_enabled BOOLEAN DEFAULT FALSE NOT NULL,
ADD COLUMN IF NOT EXISTS two_factor_backup_codes TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS two_factor_confirmed_at TIMESTAMP DEFAULT NULL;

-- Add index for faster lookups
CREATE INDEX IF NOT EXISTS idx_users_two_factor_enabled ON users(two_factor_enabled);

-- Add comment for documentation
COMMENT ON COLUMN users.two_factor_secret IS 'Encrypted TOTP secret for 2FA (base32 encoded)';
COMMENT ON COLUMN users.two_factor_enabled IS 'Whether 2FA is enabled and confirmed for this user';
COMMENT ON COLUMN users.two_factor_backup_codes IS 'JSON array of hashed backup codes for account recovery';
COMMENT ON COLUMN users.two_factor_confirmed_at IS 'Timestamp when 2FA was first successfully enabled';
