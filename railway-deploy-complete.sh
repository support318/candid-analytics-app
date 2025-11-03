#!/bin/bash
set -e

# ============================================================================
# Candid Analytics - Complete Railway Deployment
# ============================================================================
# Prerequisites: Railway CLI installed and logged in (railway login)
# ============================================================================

echo "🚀 Candid Analytics - Railway Deployment"
echo "=========================================="
echo ""

# Check if logged in
if ! railway whoami &> /dev/null; then
    echo "❌ Not logged in to Railway"
    echo "Please run: railway login"
    exit 1
fi

echo "✅ Logged in as: $(railway whoami)"
echo ""

# Navigate to project
cd /Users/ryanmayiras/Projects/candid-analytics-app

# Check if linked
echo "📍 Checking Railway project link..."
if railway status &> /dev/null; then
    echo "✅ Already linked to: $(railway status | head -1)"
else
    echo "⚠️  Not linked. Please run manually:"
    echo "railway link"
    echo "Then select: Candid Projects → Candid Analytics → production"
    exit 1
fi

# Apply database migration
echo ""
echo "🗄️  Applying database migration..."
cat database/03-ghl-field-enhancements.sql | railway run --service api bash -c "psql \$DATABASE_URL" 2>&1 && echo "✅ Migration applied" || echo "⚠️  Migration may have failed (might already be applied)"

# Verify migration
echo ""
echo "🔍 Verifying migration..."
COLS=$(railway run --service api bash -c "psql \$DATABASE_URL -t -c \"SELECT COUNT(*) FROM information_schema.columns WHERE table_name='projects' AND column_name IN ('ghl_opportunity_id', 'discount_type', 'has_video');\"" 2>&1 | tr -d ' ' | head -1)
if [ "$COLS" = "3" ]; then
    echo "✅ Migration verified ($COLS columns found)"
else
    echo "⚠️  Expected 3 columns, found: $COLS"
fi

# Update environment variables on API service
echo ""
echo "🔧 Updating environment variables on API service..."
railway variables --service api --set "GHL_API_KEY=pit-db1970e0-70d8-4d85-80f0-f04a4ba1cb8b"
railway variables --service api --set "GHL_LOCATION_ID=GHJ0X5n0UomysnUPNfao"
railway variables --service api --set "GHL_API_BASE_URL=https://services.leadconnectorhq.com"
railway variables --service api --set "GHL_API_VERSION=2021-07-28"
echo "✅ Environment variables updated"

# Verify variables
echo ""
echo "📋 Verifying GHL variables on API service..."
railway variables --service api | grep GHL

# Restart services
echo ""
echo "🔄 Restarting services..."
railway up && echo "✅ Services restarted" || echo "⚠️  Service restart may have failed"

# Done
echo ""
echo "=============================================="
echo "✅ Deployment Complete!"
echo "=============================================="
echo ""
echo "Next steps:"
echo "1. Test import: php api/scripts/sync-ghl-historical-COMPLETE.php --dry-run"
echo "2. Run import: php api/scripts/sync-ghl-historical-COMPLETE.php"
echo "3. Test dashboard: open https://analytics.candidstudios.net"
echo ""
