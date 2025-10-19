# GoHighLevel Webhook Configuration Guide

## ✅ Completed Steps

1. **Historical Data Sync** - Successfully synced from GHL:
   - ✅ 100 clients from contacts
   - ✅ 16 projects from opportunities
   - ⚠️  Consultations (calendar sync had API error - will be handled by webhooks)

2. **Database** - Populated with real production data
3. **Redis Cache** - Cleared to show fresh data
4. **Webhook Endpoints** - Created and deployed

## 🔧 Next Steps: Configure GHL Webhooks

Your GHL workflows are already set up to send data, but the webhook URLs need to be configured in GoHighLevel dashboard.

### Webhook URLs to Configure

Add these webhook URLs in your GoHighLevel account settings:

1. **Projects/Bookings Webhook**
   - URL: `https://api.candidstudios.net/api/webhooks/projects`
   - Trigger: When opportunity is created or updated
   - Method: POST

2. **Revenue/Payments Webhook**
   - URL: `https://api.candidstudios.net/api/webhooks/revenue`
   - Trigger: When invoice is paid (Stripe payment received)
   - Method: POST

3. **Inquiries/Leads Webhook**
   - URL: `https://api.candidstudios.net/api/webhooks/inquiries`
   - Trigger: When new contact is created
   - Method: POST

4. **Consultations/Appointments Webhook**
   - URL: `https://api.candidstudios.net/api/webhooks/consultations`
   - Trigger: When calendar appointment is created or updated
   - Method: POST

### How to Add Webhooks in GHL

1. Log in to your GoHighLevel account
2. Go to **Settings** → **Integrations** → **Webhooks**
3. Click **Add Webhook**
4. For each webhook above:
   - Enter the webhook URL
   - Select the appropriate trigger event
   - Set method to POST
   - (Optional) Add a webhook secret for security
5. Save each webhook

### Custom Field IDs Used

The following custom field IDs are currently configured in the webhook handlers. These should match your GHL account:

- **AFX1YsPB7QHBP50Ajs1Q** - Event Type
- **kvDBYw8fixMftjWdF51g** - Event Date
- **OwkEjGNrbE7Rq0TKBG3M** - Total Value/Budget
- **nstR5hDlCQJ6jpsFzxi7** - Venue Address
- **00cH1d6lq8m0U8tf3FHg** - Services (array)
- **T5nq3eiHUuXM0wFYNNg4** - Photography Hours
- **nHiHJxfNxRhvUfIu6oD6** - Videography Hours
- **iQOUEUruaZfPKln4sdKP** - Drone Services
- **Bz6tmEcB0S0pXupkha84** - Event Start Time
- **qpyukeOGutkXczPGJOyK** - Contact Name
- **xV2dxG35gDY1Vqb00Ql1** - Project Notes

⚠️ **IMPORTANT**: If these field IDs don't match your GHL account, the webhooks won't capture the custom field data correctly.

### To Verify Custom Field IDs

1. In GHL, go to **Settings** → **Custom Fields**
2. Click on each field to see its ID
3. Compare with the IDs listed above
4. If they don't match, we'll need to update the webhook handlers

## 📊 Testing the Dashboard

Now that the cache is cleared, test the dashboard:

1. Navigate to: **https://analytics.candidstudios.net**
2. Log in with: `admin` / `password`
3. Check if you see data on the KPI cards:
   - Total Revenue
   - Active Projects
   - Consultations Booked
   - Conversion Rate

### Expected Results

With 100 clients and 16 projects synced, you should see:
- **Clients Count**: 100
- **Projects Count**: 16
- Other metrics may be partial until webhooks are configured

### If Data Still Shows Errors

If you still see "Error Loading Data":
1. Open browser DevTools (F12)
2. Go to Network tab
3. Refresh the page
4. Check the API requests for errors
5. Let me know what error message appears

## 🔍 Known Issues to Address

1. **Contact ID Linking** - Many opportunities couldn't find their matching clients during sync
   - This might be a GHL API data structure issue
   - Needs investigation

2. **Calendar API 422 Error** - Calendar sync failed
   - Not critical - consultations will sync via webhooks
   - Can be debugged later if needed

## 🛠️ Utility Endpoints

For your reference, these endpoints are available:

- **Manual Sync (Dry Run)**: `GET https://api.candidstudios.net/api/sync/ghl-historical?dry_run=1`
- **Manual Sync (Live)**: `GET https://api.candidstudios.net/api/sync/ghl-historical`
- **Clear Cache**: `POST https://api.candidstudios.net/api/sync/clear-cache`
- **Health Check**: `GET https://api.candidstudios.net/api/health`

## 📝 Next Steps Summary

1. ✅ Check if dashboard now shows data at analytics.candidstudios.net
2. ⏳ Configure GHL webhooks using URLs above
3. ⏳ Verify custom field IDs match your GHL account
4. ⏳ Test end-to-end by creating a test lead in GHL and seeing if it appears in analytics

---

**Need Help?**
- Check Railway logs: `railway logs --service api`
- Test webhook endpoints with curl
- Review `/api/logs/app.log` for detailed error messages
