# URGENT: GHL API Key Update Required

## Current Status
✅ Phase 1 Complete - Custom fields documented (11 fields mapped)
✅ Phase 2 Complete - Import script fixed with correct classification logic

## ⚠️ BLOCKER: GHL API Key Expired

**Current API Key:** `pit-4a0c3927-1650-44dd-b63d-2f65d81f84c3`
**Status:** ❌ EXPIRED/INVALID (returns 401 Invalid Private Integration token)

**Error When Testing:**
```bash
curl -s "https://services.leadconnectorhq.com/locations/GHJ0X5n0UomysnUPNfao/customFields" \
  -H "Authorization: Bearer pit-4a0c3927-1650-44dd-b63d-2f65d81f84c3" \
  -H "Version: 2021-07-28"

Response: {"statusCode":401,"message":"Invalid Private Integration token"}
```

---

## How to Get New GHL API Key

### Option 1: Via GoHighLevel Dashboard
1. Log into GoHighLevel: https://app.gohighlevel.com/
2. Go to Settings → Integrations → API
3. Click "Create New API Key" or "Private Integration"
4. Copy the new API key (starts with "pit-")
5. Update in all 3 locations (see below)

### Option 2: Via Developer Portal
1. Visit: https://marketplace.gohighlevel.com/
2. Navigate to your app/integration
3. Go to API Keys section
4. Generate new Private Integration Token
5. Copy and update (see below)

---

## Where to Update the New API Key

### 1. Railway Environment Variables (PRODUCTION)
```bash
# Connect to Railway
cd /Users/ryanmayiras/Projects/candid-analytics-app
railway link

# Set new API key
railway variables set GHL_API_KEY=<NEW_API_KEY_HERE>

# Restart services
railway up
```

### 2. Local .env File (DEVELOPMENT)
```bash
# File: /Users/ryanmayiras/Projects/candid-analytics-app/api/.env
GHL_API_KEY=<NEW_API_KEY_HERE>
GHL_LOCATION_ID=GHJ0X5n0UomysnUPNfao
GHL_API_BASE_URL=https://services.leadconnectorhq.com
GHL_API_VERSION=2021-07-28
```

### 3. .env.railway Template (DOCUMENTATION)
```bash
# File: /Users/ryanmayiras/Projects/candid-analytics-app/api/.env.railway
GHL_API_KEY=<NEW_API_KEY_HERE>
```

---

## What You Can Do NOW (Without API Key)

While waiting for new API key, you can:

### ✅ Update Webhook Handlers
The webhook handlers don't need to CALL the GHL API - they RECEIVE data FROM GHL.
We can update them to use the correct custom field IDs now.

### ✅ Set Up n8n Workflows
n8n workflows need GHL credentials, but you can:
1. Build the workflow structure
2. Save as templates
3. Add GHL credentials later when API key is ready

### ✅ Review Database Schema
Make sure all tables are created correctly in Railway PostgreSQL.

### ✅ Update Railway Environment Variables
Set all other environment variables (SMTP, JWT secrets, etc.)

---

## What You CANNOT Do (Until API Key Updated)

### ❌ Historical Data Import
Can't run `sync-ghl-historical-FIXED.php` because it needs to call GHL API

### ❌ Test GHL API Calls
Can't test custom field discovery or data fetching

### ❌ Activate n8n Real-Time Webhooks
n8n needs to authenticate with GHL API for webhook setup

---

## Testing the New API Key (Once You Have It)

### Test 1: List Custom Fields
```bash
curl -s "https://services.leadconnectorhq.com/locations/GHJ0X5n0UomysnUPNfao/customFields" \
  -H "Authorization: Bearer <NEW_API_KEY>" \
  -H "Version: 2021-07-28" | python3 -m json.tool
```

**Expected:** JSON array of all custom fields

### Test 2: List Contacts
```bash
curl -s "https://services.leadconnectorhq.com/contacts/?locationId=GHJ0X5n0UomysnUPNfao&limit=5" \
  -H "Authorization: Bearer <NEW_API_KEY>" \
  -H "Version: 2021-07-28" | python3 -m json.tool
```

**Expected:** JSON with 5 contacts

### Test 3: Historical Import (Dry Run)
```bash
cd /Users/ryanmayiras/Projects/candid-analytics-app/api/scripts
php sync-ghl-historical-FIXED.php --dry-run
```

**Expected:** Shows what would be imported (no errors)

---

## Immediate Action Required

1. ⚠️ **Get new GHL API key from GoHighLevel dashboard**
2. ⚠️ **Update Railway environment variables**
3. ⚠️ **Test API key with curl commands above**
4. ✅ **Then we can proceed with:**
   - Historical data import
   - n8n workflow activation
   - End-to-end testing

---

## Timeline Estimate

| Task | Time | Can Do Now? |
|------|------|-------------|
| Get new GHL API key | 5 min | ✅ Yes |
| Update Railway vars | 2 min | ✅ Yes (after key) |
| Test API connection | 2 min | ❌ No (needs key) |
| Update webhook handlers | 20 min | ✅ Yes (doing now) |
| Build n8n workflows | 60 min | ⚠️ Partial (structure only) |
| Historical import | 10 min | ❌ No (needs key) |
| End-to-end testing | 30 min | ❌ No (needs key) |

**Total Time After API Key:** ~2 hours
**Current Blocker:** API key acquisition

---

## Alternative: Use Existing Working API Key

If you have another GHL integration that's working, you can:
1. Check your other projects for working API keys
2. Use the same key temporarily
3. Generate new key later

**Check these locations for existing keys:**
- Other Railway projects
- .env files in other directories
- GoHighLevel integrations you've created before
- Make.com scenarios (if still configured)

---

**Last Updated:** 2025-11-02
**Status:** Waiting for GHL API key update
**Next Step:** User provides new API key
