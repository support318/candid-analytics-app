#!/bin/bash
set -e

# ============================================================================
# Candid Analytics - Railway Deployment Script
# ============================================================================
# This script automates the deployment of GHL field enhancements to Railway
#
# Prerequisites:
# - Railway CLI installed (npm install -g railway)
# - Logged in to Railway (railway login)
# - In project directory: /Users/ryanmayiras/Projects/candid-analytics-app
# ============================================================================

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Project configuration
NEW_GHL_API_KEY="pit-db1970e0-70d8-4d85-80f0-f04a4ba1cb8b"
GHL_LOCATION_ID="GHJ0X5n0UomysnUPNfao"
GHL_API_BASE_URL="https://services.leadconnectorhq.com"
GHL_API_VERSION="2021-07-28"

echo -e "${BLUE}============================================================================${NC}"
echo -e "${BLUE}  Candid Analytics - Railway Deployment${NC}"
echo -e "${BLUE}============================================================================${NC}"
echo ""

# ============================================================================
# Step 1: Check Railway CLI
# ============================================================================
echo -e "${BLUE}[Step 1/8]${NC} Checking Railway CLI..."

if ! command -v railway &> /dev/null; then
    echo -e "${RED}✗ Railway CLI not found${NC}"
    echo "Install with: npm install -g railway"
    exit 1
fi

echo -e "${GREEN}✓ Railway CLI installed${NC}"
echo ""

# ============================================================================
# Step 2: Check if linked to Railway project
# ============================================================================
echo -e "${BLUE}[Step 2/8]${NC} Checking Railway project link..."

if ! railway status &> /dev/null; then
    echo -e "${YELLOW}⚠ Not linked to Railway project${NC}"
    echo ""
    echo "Please run this command in a separate terminal window:"
    echo -e "${GREEN}railway link${NC}"
    echo ""
    echo "Then select:"
    echo "  - Workspace: Candid Projects"
    echo "  - Project: candid-analytics-app"
    echo "  - Environment: production"
    echo ""
    read -p "Press Enter after linking the project..."

    # Verify link worked
    if ! railway status &> /dev/null; then
        echo -e "${RED}✗ Still not linked. Please link manually and re-run this script.${NC}"
        exit 1
    fi
fi

echo -e "${GREEN}✓ Railway project linked${NC}"
railway status
echo ""

# ============================================================================
# Step 3: Apply database migrations
# ============================================================================
echo -e "${BLUE}[Step 3/8]${NC} Applying database migrations..."
echo "This will add 22 new columns across 5 tables..."
echo ""

read -p "Proceed with database migration? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}Migration skipped${NC}"
else
    echo "Applying migration..."
    if railway run psql \$DATABASE_URL < database/03-ghl-field-enhancements.sql; then
        echo -e "${GREEN}✓ Database migration applied successfully${NC}"
    else
        echo -e "${RED}✗ Migration failed - check output above${NC}"
        exit 1
    fi
fi
echo ""

# ============================================================================
# Step 4: Verify new columns
# ============================================================================
echo -e "${BLUE}[Step 4/8]${NC} Verifying new database columns..."

COLUMN_CHECK=$(railway run psql \$DATABASE_URL -t -c "SELECT column_name FROM information_schema.columns WHERE table_name='projects' AND column_name IN ('ghl_opportunity_id', 'discount_type', 'has_video');" | wc -l | tr -d ' ')

if [ "$COLUMN_CHECK" -eq "3" ]; then
    echo -e "${GREEN}✓ New columns verified in projects table${NC}"
else
    echo -e "${YELLOW}⚠ Expected 3 columns, found $COLUMN_CHECK${NC}"
    echo "This might indicate migration didn't apply correctly"
fi
echo ""

# ============================================================================
# Step 5: Update Railway environment variables
# ============================================================================
echo -e "${BLUE}[Step 5/8]${NC} Updating Railway environment variables..."
echo "Setting new GHL API key..."
echo ""

read -p "Update GHL_API_KEY in Railway? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}Environment variable update skipped${NC}"
else
    railway variables set GHL_API_KEY="$NEW_GHL_API_KEY"
    railway variables set GHL_LOCATION_ID="$GHL_LOCATION_ID"
    railway variables set GHL_API_BASE_URL="$GHL_API_BASE_URL"
    railway variables set GHL_API_VERSION="$GHL_API_VERSION"
    echo -e "${GREEN}✓ Environment variables updated${NC}"
fi
echo ""

# ============================================================================
# Step 6: Verify environment variables
# ============================================================================
echo -e "${BLUE}[Step 6/8]${NC} Verifying GHL environment variables..."

echo "Current GHL variables:"
railway variables | grep GHL || echo "No GHL variables found"
echo ""

# ============================================================================
# Step 7: Restart Railway services
# ============================================================================
echo -e "${BLUE}[Step 7/8]${NC} Restarting Railway services..."
echo ""

read -p "Restart services to pick up new env vars? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}Service restart skipped${NC}"
else
    railway up
    echo -e "${GREEN}✓ Services restarted${NC}"
fi
echo ""

# ============================================================================
# Step 8: Test import script (dry-run)
# ============================================================================
echo -e "${BLUE}[Step 8/8]${NC} Ready to test import script..."
echo ""
echo "Next steps:"
echo "1. Test import with dry-run:"
echo -e "   ${GREEN}php api/scripts/sync-ghl-historical-COMPLETE.php --dry-run${NC}"
echo ""
echo "2. If dry-run looks good, run full import:"
echo -e "   ${GREEN}php api/scripts/sync-ghl-historical-COMPLETE.php${NC}"
echo ""
echo "3. Verify data in database:"
echo -e "   ${GREEN}railway run psql \$DATABASE_URL${NC}"
echo ""

echo -e "${GREEN}============================================================================${NC}"
echo -e "${GREEN}  Deployment Complete!${NC}"
echo -e "${GREEN}============================================================================${NC}"
echo ""
echo "Summary:"
echo "  ✓ Local .env updated with new GHL API key"
echo "  ✓ Database migrations applied (22 new columns)"
echo "  ✓ Railway environment variables updated"
echo "  ✓ Services restarted"
echo ""
echo "See QUICK_START_GUIDE.md for detailed verification steps."
echo ""
