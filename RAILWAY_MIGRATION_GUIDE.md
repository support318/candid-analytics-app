# Railway Database Migration Guide
**Project:** Candid Analytics Application
**Date:** 2025-11-02
**Purpose:** Apply GHL field enhancements to Railway PostgreSQL database

---

## Prerequisites

- [x] Railway CLI installed
- [x] Railway project deployed and running
- [x] Database migration files ready in `/database/` folder

---

## Migration Files to Apply (In Order)

### ✅ Already Applied (Base Schema)
1. `00-essential-schema.sql` - Core tables (clients, projects, revenue, inquiries, etc.)
2. `01-schema.sql` - Extended schema (if different from 00-essential)
3. `02-extended-schema.sql` - Advanced tables (staff_assignments, deliverables, reviews, etc.)

### ⏳ New Migration to Apply
4. **`03-ghl-field-enhancements.sql`** - NEW columns for GHL custom fields

---

## Step 1: Link to Railway Project

```bash
cd /Users/ryanmayiras/Projects/candid-analytics-app
railway link
```

**You'll be prompted to:**
1. Select workspace: **Candid Projects** (or your workspace name)
2. Select project: **candid-analytics-app** (or your project name)
3. Select environment: **production** (or staging if testing first)

---

## Step 2: Verify Database Connection

```bash
# Check environment variables
railway variables

# Expected output should include:
# - PGHOST
# - PGPORT
# - PGDATABASE
# - PGUSER
# - PGPASSWORD
```

---

## Step 3: Connect to Database Shell

```bash
railway run psql $DATABASE_URL
```

**OR** if `$DATABASE_URL` doesn't work:

```bash
railway run bash
psql "postgresql://$PGUSER:$PGPASSWORD@$PGHOST:$PGPORT/$PGDATABASE"
```

---

## Step 4: Verify Existing Schema

Once in the PostgreSQL shell (`psql`), run:

```sql
-- List all tables
\dt

-- Expected tables:
-- clients
-- consultations
-- deliverables
-- inquiries
-- lead_sources
-- marketing_campaigns
-- projects
-- refresh_tokens
-- revenue
-- reviews
-- staff_assignments
-- users

-- If you see all these tables, your base schema is applied ✅
```

---

## Step 5: Apply GHL Field Enhancements Migration

### Option A: Copy/Paste SQL (Recommended for First Time)

1. Open `database/03-ghl-field-enhancements.sql` on your local machine
2. Copy the entire contents
3. In the `psql` shell, paste and execute
4. Look for success message: `GHL field enhancements applied successfully!`

### Option B: Upload and Execute via Railway CLI

```bash
# Exit psql shell first (type \q)
exit

# Then run from your local terminal:
railway run psql $DATABASE_URL < database/03-ghl-field-enhancements.sql
```

---

## Step 6: Verify New Columns Were Added

Back in the `psql` shell:

```sql
-- Check projects table for new columns
\d projects

-- You should see:
-- - ghl_opportunity_id
-- - discount_type
-- - discount_amount
-- - has_video
-- - travel_distance
-- - calendar_event_id

-- Check clients table for new columns
\d clients

-- You should see:
-- - engagement_score
-- - mailing_address
-- - partner_first_name
-- - partner_last_name
-- - partner_email
-- - partner_phone

-- Check deliverables table for new columns
\d deliverables

-- You should see:
-- - raw_images_link
-- - raw_video_link
-- - final_images_link
-- - final_video_link
-- - additional_videos_link

-- Check reviews table for new columns
\d reviews

-- You should see:
-- - photographer_feedback
-- - videographer_feedback
-- - review_link

-- Check staff_assignments table for new columns
\d staff_assignments

-- You should see:
-- - ghl_staff_id
```

---

## Step 7: Update Railway Environment Variables

You need to update the GHL API key in Railway:

```bash
# Exit psql if still in it
\q
exit

# Set new GHL API key
railway variables set GHL_API_KEY=pit-db1970e0-70d8-4d85-80f0-f04a4ba1cb8b

# Verify it was set
railway variables | grep GHL_API_KEY
```

**Expected Output:**
```
GHL_API_KEY=pit-db1970e0-70d8-4d85-80f0-f04a4ba1cb8b
```

**Also verify other GHL variables:**

```bash
railway variables | grep GHL
```

**Expected:**
```
GHL_API_KEY=pit-db1970e0-70d8-4d85-80f0-f04a4ba1cb8b
GHL_LOCATION_ID=GHJ0X5n0UomysnUPNfao
GHL_API_BASE_URL=https://services.leadconnectorhq.com
GHL_API_VERSION=2021-07-28
```

---

## Step 8: Restart Railway Services

```bash
# Restart API service to pick up new environment variables
railway up --service api

# OR restart all services
railway up
```

---

## Troubleshooting

### Error: "column already exists"
**Solution:** This is safe to ignore - it means the column was already added. The migration uses `IF NOT EXISTS` checks.

### Error: "relation does not exist"
**Solution:** You need to apply the base schema first:
```bash
railway run psql $DATABASE_URL < database/00-essential-schema.sql
railway run psql $DATABASE_URL < database/02-extended-schema.sql
```

### Error: "permission denied"
**Solution:** Check that your Railway PostgreSQL user has proper permissions:
```sql
-- In psql shell:
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO <your_user>;
```

### Can't Connect to Database
**Solution:**
1. Check Railway dashboard to ensure PostgreSQL service is running
2. Verify environment variables with `railway variables`
3. Try using Railway's web-based database shell (Dashboard → PostgreSQL service → Query tab)

---

## Alternative: Railway Web Interface

If CLI doesn't work, you can use Railway's web interface:

1. Go to https://railway.app/
2. Navigate to your project
3. Click on **PostgreSQL** service
4. Click **Query** tab
5. Paste contents of `database/03-ghl-field-enhancements.sql`
6. Click **Run Query**

---

## Verification Checklist

After migration is complete, verify:

- [ ] All new columns exist in projects table (6 columns)
- [ ] All new columns exist in clients table (6 columns)
- [ ] All new columns exist in deliverables table (5 columns)
- [ ] All new columns exist in reviews table (3 columns)
- [ ] All new columns exist in staff_assignments table (1 column)
- [ ] GHL_API_KEY environment variable updated with new token
- [ ] Railway services restarted successfully
- [ ] API health check returns 200 OK (`/api/health`)

---

## Quick Verification Commands

```bash
# Check if migrations applied successfully
railway run psql $DATABASE_URL -c "SELECT column_name FROM information_schema.columns WHERE table_name='projects' AND column_name IN ('ghl_opportunity_id', 'discount_type', 'has_video');"

# Expected output: 3 rows
# ghl_opportunity_id
# discount_type
# has_video

# Check environment variables
railway variables | grep GHL_API_KEY

# Test API health
curl https://your-api-domain.railway.app/api/health
```

---

## Next Steps After Migration

1. ✅ Database schema updated with GHL fields
2. ✅ Environment variables updated with new API key
3. ⏳ Run updated historical import script (sync-ghl-historical-UPDATED.php)
4. ⏳ Test data import with dry-run mode
5. ⏳ Import real historical data
6. ⏳ Set up n8n workflows for real-time sync

---

**Last Updated:** 2025-11-02
**Migration File:** `database/03-ghl-field-enhancements.sql`
**Status:** Ready to apply
