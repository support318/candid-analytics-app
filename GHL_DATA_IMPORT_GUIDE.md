# GoHighLevel Data Import Guide

This guide will help you import your historical GoHighLevel data into the Candid Analytics dashboard so you can see real, accurate reporting.

## 🎯 What This Does

The import script pulls your real historical data from GoHighLevel and populates the analytics database with:
- **All contacts** (leads and clients) from the past year
- **Opportunities** (inquiries and booked projects)
- **Project details** (event types, dates, values)
- **Custom field data** (services, hours, locations)

This replaces the sample data with your actual business data for accurate analytics.

---

## 📋 Prerequisites

### 1. Get Your GHL API Key

1. Log into your GoHighLevel account
2. Go to **Settings** → **Integrations** → **API Key**
3. Click **Create API Key**
4. Copy the API key (starts with `pk_` or similar)
5. Save it somewhere secure

### 2. Find Your Location ID

1. In GoHighLevel, go to **Settings** → **Business Profile**
2. Look for your **Location ID** (a long alphanumeric string)
3. Or check the URL when you're logged in: `app.gohighlevel.com/location/YOUR_LOCATION_ID`

---

## 🚀 Running the Import

### Step 1: Test First (Dry Run)

Always run a dry run first to see what would be imported:

```bash
cd /mnt/c/code/candid-analytics-app

php scripts/import-ghl-data.php \
  --api-key=YOUR_GHL_API_KEY \
  --location-id=YOUR_LOCATION_ID \
  --dry-run
```

This will show you:
- How many contacts would be imported
- Their names, emails, and lifecycle stages
- Associated opportunities and projects
- **Nothing is actually imported** in dry-run mode

### Step 2: Run the Actual Import

Once you've verified the dry run looks correct:

```bash
php scripts/import-ghl-data.php \
  --api-key=YOUR_GHL_API_KEY \
  --location-id=YOUR_LOCATION_ID
```

This will:
- Import all contacts from the past year
- Import their opportunities and projects
- Update the analytics database
- Refresh materialized views for dashboard

### Step 3: Custom Date Range (Optional)

To import data from a specific date range:

```bash
php scripts/import-ghl-data.php \
  --api-key=YOUR_GHL_API_KEY \
  --location-id=YOUR_LOCATION_ID \
  --start-date=2024-01-01 \
  --end-date=2025-10-16
```

---

## 🔄 Ongoing Data Sync

After the initial import, you have two options for keeping data in sync:

### Option 1: Set Up GHL Webhooks (Recommended)

Configure GoHighLevel to automatically push new data to your analytics API:

**See:** [GHL_AUTOMATION_SETUP.md](./GHL_AUTOMATION_SETUP.md)

This will automatically capture:
- New inquiries (when contact is created)
- New bookings (when opportunity moves to "Won")
- Payments received

### Option 2: Run Import Script Periodically

Set up a cron job to run the import script daily:

```bash
# Add to crontab (crontab -e)
0 2 * * * cd /mnt/c/code/candid-analytics-app && php scripts/import-ghl-data.php --api-key=YOUR_KEY --location-id=YOUR_ID --start-date=$(date -d '7 days ago' +\%Y-\%m-\%d) >> /var/log/ghl-import.log 2>&1
```

---

## 📊 What Gets Imported

### Contacts
- ✅ Name, email, phone
- ✅ Lead source
- ✅ Tags
- ✅ Lifecycle stage (lead, qualified, client)
- ✅ Date added

### Opportunities
- ✅ Opportunity name
- ✅ Monetary value
- ✅ Status (open, won, lost)
- ✅ Notes

### Projects (from Won opportunities)
- ✅ Project name
- ✅ Booking date
- ✅ Event date
- ✅ Event type
- ✅ Total revenue
- ✅ Status

### Custom Fields
- ✅ Event type
- ✅ Event date
- ✅ Event location
- ✅ Services (photo, video, drone)
- ✅ Photography hours
- ✅ Videography hours
- ✅ Estimated value
- ✅ Project notes

---

## 🔍 Troubleshooting

### Error: "Database connection failed"

**Solution**: Make sure Docker containers are running:
```bash
cd /mnt/c/code/candid-analytics-app
docker-compose up -d
```

### Error: "GHL API request failed with status 401"

**Cause**: Invalid API key

**Solutions**:
1. Verify API key is correct (no extra spaces)
2. Make sure API key has not expired
3. Check API key has "Read" permissions in GHL
4. Try regenerating the API key in GHL

### Error: "GHL API request failed with status 403"

**Cause**: Location ID is incorrect or you don't have access

**Solutions**:
1. Double-check the Location ID
2. Verify you're using the correct GHL account
3. Make sure API key is for the correct location

### Warning: "Could not refresh materialized views"

**Cause**: Materialized view doesn't exist yet

**Solution**: Run the view creation script first:
```bash
docker exec -i candid-analytics-db psql -U candid_analytics_user -d candid_analytics < database/create_all_materialized_views.sql
```

### No Data Shows Up in Dashboard

**Solutions**:
1. Clear browser cache and hard refresh (Ctrl+Shift+R)
2. Check if data was actually imported:
   ```bash
   docker exec -it candid-analytics-db psql -U candid_analytics_user -d candid_analytics -c "SELECT COUNT(*) FROM clients;"
   ```
3. Restart API container:
   ```bash
   docker-compose restart api
   ```
4. Check API logs:
   ```bash
   docker-compose logs -f api
   ```

---

## 📈 After Import

Once the import is complete:

1. **Refresh Your Dashboard**
   - Go to https://analytics.candidstudios.net
   - Hard refresh (Ctrl+Shift+R) to clear cache
   - All tabs should now show real data

2. **Verify Data Accuracy**
   - Check Priority KPIs tab for recent metrics
   - Compare Revenue tab to your actual bookings
   - Verify Sales Funnel stages match your GHL pipeline

3. **Set Up Webhooks** (if not already done)
   - Follow [GHL_AUTOMATION_SETUP.md](./GHL_AUTOMATION_SETUP.md)
   - This ensures new data flows in automatically

---

## 🔐 Security Notes

- **Never commit your API key** to git
- Store API key in environment variables for production
- Consider creating a separate GHL API key for analytics only
- Restrict API key permissions to "Read Only" if possible

---

## 📞 Need Help?

If you encounter issues:

1. **Check Logs**:
   ```bash
   docker-compose logs -f api
   docker-compose logs -f postgres
   ```

2. **Test API Health**:
   ```bash
   curl https://api.candidstudios.net/api/health
   ```

3. **Test Database Connection**:
   ```bash
   docker exec -it candid-analytics-db psql -U candid_analytics_user -d candid_analytics -c "SELECT version();"
   ```

4. **Run Dry Run Again**:
   ```bash
   php scripts/import-ghl-data.php --api-key=YOUR_KEY --location-id=YOUR_ID --dry-run
   ```

---

## 🎉 Success!

Once you see real data in your dashboard:
- ✅ Revenue analytics show your actual bookings
- ✅ Sales funnel reflects your true conversion rates
- ✅ Operations metrics show real delivery times
- ✅ Staff productivity displays actual performance
- ✅ AI Insights provide actionable recommendations

Your Candid Analytics dashboard is now powered by real data! 🚀

---

**Last Updated:** 2025-10-16
