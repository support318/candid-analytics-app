# 🚀 Ready to Deploy - Execute These Commands

**Status**: All development work complete. Ready for Railway deployment.

**What's been done**:
- ✅ Local `api/.env` updated with new GHL API key
- ✅ Database migration created (`database/03-ghl-field-enhancements.sql`)
- ✅ Complete import script created (`api/scripts/sync-ghl-historical-COMPLETE.php`)
- ✅ Automated deployment script created (`deploy-to-railway.sh`)

---

## Option 1: Automated Script (Recommended)

Just run this one command from your terminal:

```bash
cd /Users/ryanmayiras/Projects/candid-analytics-app
./deploy-to-railway.sh
```

The script will:
1. Check Railway CLI
2. Prompt you to link if needed
3. Apply database migrations
4. Update environment variables
5. Restart services
6. Show you next steps

---

## Option 2: Manual Step-by-Step

If you prefer to execute each step manually:

### 1. Link Railway (if not already linked)
```bash
cd /Users/ryanmayiras/Projects/candid-analytics-app
railway link
# Select: Candid Projects → candid-analytics-app → production
```

### 2. Apply Database Migration
```bash
railway run psql $DATABASE_URL < database/03-ghl-field-enhancements.sql
```

### 3. Verify Migration
```bash
railway run psql $DATABASE_URL -c "SELECT column_name FROM information_schema.columns WHERE table_name='projects' AND column_name IN ('ghl_opportunity_id', 'discount_type', 'has_video');"
```

Expected output: 3 rows (ghl_opportunity_id, discount_type, has_video)

### 4. Update Environment Variables
```bash
railway variables set GHL_API_KEY=pit-db1970e0-70d8-4d85-80f0-f04a4ba1cb8b
railway variables set GHL_LOCATION_ID=GHJ0X5n0UomysnUPNfao
railway variables set GHL_API_BASE_URL=https://services.leadconnectorhq.com
railway variables set GHL_API_VERSION=2021-07-28
```

### 5. Verify Variables
```bash
railway variables | grep GHL
```

Expected output:
```
GHL_API_KEY=pit-db1970e0-70d8-4d85-80f0-f04a4ba1cb8b
GHL_LOCATION_ID=GHJ0X5n0UomysnUPNfao
GHL_API_BASE_URL=https://services.leadconnectorhq.com
GHL_API_VERSION=2021-07-28
```

### 6. Restart Services
```bash
railway up
```

---

## After Deployment: Test the Import

### Test with Dry-Run (No Database Writes)
```bash
php api/scripts/sync-ghl-historical-COMPLETE.php --dry-run
```

**Look for:**
- ✅ "Connected to database"
- ✅ "Fetched X contacts"
- ✅ "Fetched X opportunities"
- ✅ "BOOKED PROJECT" for Planning stage items
- ✅ "INQUIRY" for non-Planning items
- ✅ "This was a DRY RUN"

### If Dry-Run Looks Good, Run Full Import
```bash
php api/scripts/sync-ghl-historical-COMPLETE.php
```

**Expected Output:**
```
📊 Statistics:
  Clients Created: X
  Projects Created: X (only Planning stage)
  Inquiries Created: X (all other stages)
  Staff Assignments Created: X
  Deliverables Created: X
  Reviews Created: X
  Errors: 0
```

---

## Verify Data in Database

```bash
railway run psql $DATABASE_URL
```

Then run:

```sql
-- Check counts
SELECT
    (SELECT COUNT(*) FROM clients) as clients,
    (SELECT COUNT(*) FROM projects) as projects,
    (SELECT COUNT(*) FROM inquiries) as inquiries,
    (SELECT COUNT(*) FROM staff_assignments) as staff,
    (SELECT COUNT(*) FROM deliverables) as deliverables,
    (SELECT COUNT(*) FROM reviews) as reviews;

-- Check sample project with all fields
SELECT
    p.project_name,
    p.event_type,
    p.has_video,
    p.discount_type,
    c.first_name,
    c.engagement_score
FROM projects p
JOIN clients c ON p.client_id = c.id
LIMIT 3;

-- Exit
\q
```

---

## Test Dashboard

```bash
open https://analytics.candidstudios.net
```

**Verify:**
- Revenue metrics displaying
- Sales funnel showing data
- Staff productivity visible
- No errors in console

---

## Summary of Changes

### Database Schema (22 new columns)
- **projects**: 7 columns (ghl_opportunity_id, discount_type, discount_amount, has_video, travel_distance, calendar_event_id)
- **clients**: 6 columns (engagement_score, mailing_address, partner_first_name, partner_last_name, partner_email, partner_phone)
- **deliverables**: 5 columns (raw_images_link, raw_video_link, final_images_link, final_video_link, additional_videos_link)
- **reviews**: 3 columns (photographer_feedback, videographer_feedback, review_link)
- **staff_assignments**: 1 column (ghl_staff_id)

### Import Script Features
- Uses ALL 57 analytics-relevant GHL custom fields
- Correct booking classification (Planning stage = project)
- Populates 5 tables: clients, projects/inquiries, staff_assignments, deliverables, reviews
- Comprehensive error handling
- Dry-run mode for testing

---

## 🆘 Quick Troubleshooting

### "railway: command not found"
```bash
npm install -g railway
railway login
```

### "401 Unauthorized" from GHL API
- Check API key is correct: `pit-db1970e0-70d8-4d85-80f0-f04a4ba1cb8b`
- Verify it's in both `api/.env` (✅ already done) and Railway variables

### "column already exists" error
- This is safe to ignore - migration uses `IF NOT EXISTS`

### Import shows 0 projects created
- Check dry-run output to see "BOOKED PROJECT" vs "INQUIRY" classification
- Verify opportunities exist in GHL with stage = "Planning"

---

## Files Created

All in `/Users/ryanmayiras/Projects/candid-analytics-app/`:

1. ✅ `database/03-ghl-field-enhancements.sql` - Database migration
2. ✅ `api/scripts/sync-ghl-historical-COMPLETE.php` - Complete import script
3. ✅ `api/.env` - Updated with new GHL API key
4. ✅ `deploy-to-railway.sh` - Automated deployment script
5. ✅ `QUICK_START_GUIDE.md` - Detailed 8-step guide
6. ✅ `RAILWAY_MIGRATION_GUIDE.md` - Migration-specific guide
7. ✅ `PHASE_1-4_COMPLETE_SUMMARY.md` - Comprehensive summary
8. ✅ `GHL_COMPLETE_FIELD_MAPPING_V2.md` - 91-field reference
9. ✅ `DEPLOY_NOW.md` - This file

---

**Last Updated**: 2025-11-02
**Estimated Deployment Time**: 10-15 minutes
**Next After Import**: Build n8n workflows for real-time sync
