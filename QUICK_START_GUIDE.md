# Quick Start Guide - Analytics Dashboard Setup
**Keep this open while deploying!**

---

## ⚡ Quick Reference

**New GHL API Token:** `pit-db1970e0-70d8-4d85-80f0-f04a4ba1cb8b`
**GHL Location ID:** `GHJ0X5n0UomysnUPNfao`
**Project Path:** `/Users/ryanmayiras/Projects/candid-analytics-app`

---

## 🎯 Step-by-Step Checklist

### 1️⃣ Apply Database Migrations (5 minutes)

```bash
cd /Users/ryanmayiras/Projects/candid-analytics-app
railway link  # Select: Candid Projects → candid-analytics-app → production
railway run psql $DATABASE_URL < database/03-ghl-field-enhancements.sql
```

**Verify:**
```bash
railway run psql $DATABASE_URL -c "SELECT column_name FROM information_schema.columns WHERE table_name='projects' AND column_name IN ('ghl_opportunity_id', 'discount_type', 'has_video');"
# Should return 3 rows
```

✅ **Done when:** You see 3 rows returned (ghl_opportunity_id, discount_type, has_video)

---

### 2️⃣ Update Railway Environment Variables (2 minutes)

```bash
railway variables set GHL_API_KEY=pit-db1970e0-70d8-4d85-80f0-f04a4ba1cb8b
railway variables | grep GHL  # Verify all 4 GHL variables present
railway up  # Restart services
```

**Expected output:**
```
GHL_API_KEY=pit-db1970e0-70d8-4d85-80f0-f04a4ba1cb8b
GHL_LOCATION_ID=GHJ0X5n0UomysnUPNfao
GHL_API_BASE_URL=https://services.leadconnectorhq.com
GHL_API_VERSION=2021-07-28
```

✅ **Done when:** All 4 GHL variables shown, services restarted

---

### 3️⃣ Update Local .env (30 seconds)

```bash
nano api/.env
# Change GHL_API_KEY line to:
# GHL_API_KEY=pit-db1970e0-70d8-4d85-80f0-f04a4ba1cb8b
# Save: Ctrl+X, Y, Enter
```

✅ **Done when:** File saved with new API key

---

### 4️⃣ Test Import (Dry Run) (5 minutes)

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

✅ **Done when:** No errors, classification looks correct

---

### 5️⃣ Backup Database (2 minutes)

```bash
railway run pg_dump $DATABASE_URL > backup-$(date +%Y%m%d-%H%M).sql
```

✅ **Done when:** Backup file created

---

### 6️⃣ Run Full Import (10-20 minutes depending on data size)

```bash
php api/scripts/sync-ghl-historical-COMPLETE.php
```

**Watch for:**
- Clients Created: X
- Projects Created: X (only Planning stage)
- Inquiries Created: X (all other stages)
- Staff Assignments Created: X
- Deliverables Created: X
- Reviews Created: X
- **Errors: 0** ← This should be 0!

✅ **Done when:** Import completes with 0 errors

---

### 7️⃣ Verify Data (5 minutes)

```bash
railway run psql $DATABASE_URL
```

```sql
-- Check counts
SELECT
    (SELECT COUNT(*) FROM clients) as clients,
    (SELECT COUNT(*) FROM projects) as projects,
    (SELECT COUNT(*) FROM inquiries) as inquiries,
    (SELECT COUNT(*) FROM staff_assignments) as staff,
    (SELECT COUNT(*) FROM deliverables) as deliverables,
    (SELECT COUNT(*) FROM reviews) as reviews;

-- Check sample project
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

✅ **Done when:** Counts look reasonable, sample data populated

---

### 8️⃣ Test Dashboard (2 minutes)

```bash
open https://analytics.candidstudios.net
```

**Check:**
- Revenue metrics displaying
- Sales funnel showing data
- Staff productivity visible
- No errors in console

✅ **Done when:** Dashboard loads and shows imported data

---

## 🆘 Common Issues & Fixes

### "railway: command not found"
```bash
npm install -g railway
railway login
```

### "permission denied" in psql
```sql
-- Run this in psql:
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO <your_user>;
```

### "401 Unauthorized" from GHL API
- Check API key is correct: `pit-db1970e0-70d8-4d85-80f0-f04a4ba1cb8b`
- Verify it's in both `api/.env` and Railway variables

### "column already exists" error
- This is safe to ignore - migration uses `IF NOT EXISTS`

### Import shows 0 projects created
- Check pipeline stage values in GHL
- Verify dry-run output shows "BOOKED PROJECT" for some items
- Ensure opportunities exist with stage = "Planning"

---

## 📂 File Locations

**Migration SQL:**
```
/Users/ryanmayiras/Projects/candid-analytics-app/database/03-ghl-field-enhancements.sql
```

**Import Script:**
```
/Users/ryanmayiras/Projects/candid-analytics-app/api/scripts/sync-ghl-historical-COMPLETE.php
```

**Local .env:**
```
/Users/ryanmayiras/Projects/candid-analytics-app/api/.env
```

**Documentation:**
```
/Users/ryanmayiras/Projects/candid-analytics-app/GHL_COMPLETE_FIELD_MAPPING_V2.md
/Users/ryanmayiras/Projects/candid-analytics-app/RAILWAY_MIGRATION_GUIDE.md
/Users/ryanmayiras/Projects/candid-analytics-app/PHASE_1-4_COMPLETE_SUMMARY.md
```

---

## 🎯 Success = All 8 Steps Complete

Once all 8 steps are checked off:
- ✅ Database schema updated with 22 new columns
- ✅ Historical data imported with correct classification
- ✅ Staff, deliverables, reviews tracking functional
- ✅ Dashboard displaying all metrics

**Next:** Set up n8n workflows for real-time sync

---

## 📊 What the Import Does

**For each GHL opportunity:**

1. **Creates/Updates Client** (with engagement score, mailing address, partner info)

2. **Classification:**
   - If `pipelineStage === "Planning"`:
     - ✅ Create PROJECT
     - ✅ Create STAFF_ASSIGNMENTS (photographer, videographer, PM, sales)
     - ✅ Create DELIVERABLES (with deadline + file links)
     - ✅ Create REVIEWS (if feedback exists)
     - ✅ Update client.lifecycle_stage to 'client'
   - Else:
     - 📋 Create INQUIRY (lead)
     - Keep client.lifecycle_stage as 'lead'

3. **Populates 57 custom fields across all tables**

---

**Estimated Total Time:** ~30-45 minutes

**Questions?** Check `PHASE_1-4_COMPLETE_SUMMARY.md` for detailed explanations

---

**Last Updated:** 2025-11-02
