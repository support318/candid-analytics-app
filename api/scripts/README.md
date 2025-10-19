# GHL Historical Data Sync Script

This script fetches all historical data from your GoHighLevel account and populates the Candid Analytics database.

## Prerequisites

1. GHL API credentials configured in Railway environment:
   - `GHL_API_KEY`
   - `GHL_LOCATION_ID`

2. Database credentials configured in Railway environment

## Usage

### Dry Run (Preview Only)
Test the sync without making any database changes:

```bash
railway run php scripts/sync-ghl-historical.php --dry-run
```

### Live Sync
Run the actual sync to populate the database:

```bash
railway run php scripts/sync-ghl-historical.php
```

## What It Syncs

1. **Contacts → Clients Table**
   - All GHL contacts with their custom fields
   - Tags, lead source, lifecycle stage
   - Creates new clients or updates existing ones

2. **Opportunities → Projects Table**
   - All opportunities from all pipelines
   - Maps pipeline stages to project statuses:
     - Lead → `lead`
     - Planning (Booked) → `booked`
     - Photo/Video Editing → `in-progress`
     - Delivery (Archived) → `completed`

3. **Calendar Appointments → Consultations Table**
   - Last 12 months + 6 months future
   - Consultation type, date, attendance status

## Adding GHL Credentials to Railway

Run these commands to add the credentials:

```bash
# Switch to API service
railway service api

# Add GHL API key
railway variables set GHL_API_KEY=pit-6790fa9f-7d36-4838-aaf7-faa825ec5b42

# Add GHL Location ID
railway variables set GHL_LOCATION_ID=GHJ0X5n0UomysnUPNfao
```

## Troubleshooting

### Error: "GHL_API_KEY environment variable required"
Make sure you've added the GHL credentials to Railway (see above)

### Error: "Database connection failed"
Verify Railway database environment variables are set correctly

### Error: "GHL API request failed"
- Check that your GHL API key is valid
- Verify the GHL Location ID is correct
- Ensure your GHL account has API access enabled

## After Running

1. Clear Redis cache: `railway run php -r "\\$redis = new \\Predis\\Client(['host' => getenv('REDIS_HOST')]); \\$redis->flushdb();"`
2. Refresh the analytics dashboard
3. Data should now appear!
