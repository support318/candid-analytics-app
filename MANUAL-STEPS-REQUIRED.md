# ⚠️ MANUAL STEPS REQUIRED - Quick Guide

**Status**: Automated deployment 100% complete! 🎉
**Remaining**: 3 manual steps before production launch

**Total Time**: 30-45 minutes
**Last Updated**: 2025-11-02

---

## ✅ What's Already Done (Automated)

- ✅ Database migrated (22 new columns)
- ✅ Historical data imported (19 clients, 19 inquiries)
- ✅ Environment variables configured
- ✅ Custom domains working (`analytics.candidstudios.net`, `api.candidstudios.net`)
- ✅ Frontend deployed with all 8 dashboard pages
- ✅ API deployed and healthy
- ✅ Webhook endpoints ready and verified

---

## 🔴 Step 1: Change Admin Password (2 minutes - CRITICAL)

**Current Credentials** (⚠️ change immediately!):
```
Username: admin@candidstudios.net
Password: CandidStudios2025!
```

### Method A: Via Dashboard (Recommended)

1. Go to https://analytics.candidstudios.net/login
2. Log in with credentials above
3. Click your profile icon (top right)
4. Go to "Profile" page
5. Click "Change Password"
6. Enter new strong password
7. Click "Save"

### Method B: Via Command Line

```bash
cd ~/Projects/candid-analytics-app/api
railway run --service api php scripts/create-admin.php
```

Follow prompts to create new admin account or update existing.

---

## 🟡 Step 2: Configure GHL Webhooks (20-30 minutes)

**Purpose**: Enable real-time sync from GoHighLevel to Analytics Dashboard

**Access Required**: GHL Admin account

**Quick Steps**:

1. Log into GoHighLevel dashboard
2. Go to **Settings** → **Integrations** → **Webhooks**
3. Click **"Add Webhook"** (4 times for 4 webhooks)

### Webhook #1: Opportunities
- **Name**: `Candid Analytics - Opportunities`
- **URL**: `https://api.candidstudios.net/api/webhooks/projects`
- **Trigger**: `Opportunity Created` + `Opportunity Updated`
- **Method**: `POST`
- **Headers**:
  ```
  Content-Type: application/json
  X-Webhook-Secret: makecom_webhook_secret_2025_candid_studios_ghl_integration
  ```
- **Payload**: Include all fields ✅

### Webhook #2: Contacts
- **Name**: `Candid Analytics - Contacts`
- **URL**: `https://api.candidstudios.net/api/webhooks/inquiries`
- **Trigger**: `Contact Created` + `Contact Updated`
- **Method**: `POST`
- **Headers**: (same as above)
- **Payload**: Include all fields ✅

### Webhook #3: Appointments
- **Name**: `Candid Analytics - Appointments`
- **URL**: `https://api.candidstudios.net/api/webhooks/consultations`
- **Trigger**: `Appointment Created` + `Appointment Updated` + `Appointment Cancelled`
- **Method**: `POST`
- **Headers**: (same as above)
- **Payload**: Include all fields ✅

### Webhook #4: Payments
- **Name**: `Candid Analytics - Payments`
- **URL**: `https://api.candidstudios.net/api/webhooks/revenue`
- **Trigger**: `Payment Received` + `Invoice Paid`
- **Method**: `POST`
- **Headers**: (same as above)
- **Payload**: Include all fields ✅

**Test Each Webhook**:
1. After saving each webhook, go to webhook delivery logs
2. Create/update a test record in GHL
3. Verify webhook shows "200 OK" status
4. Check data appears in Analytics Dashboard

**Detailed Instructions**: See `GHL-WEBHOOK-SETUP.md`

---

## 🟢 Step 3: Set Up OpenAI API Key (5 minutes - OPTIONAL)

**Purpose**: Enable AI-powered insights and predictive analytics

**Features Unlocked**:
- Revenue forecasting
- Trend analysis
- Anomaly detection
- Automated recommendations

**How to Add**:

```bash
cd ~/Projects/candid-analytics-app
railway variables --service api --set "OPENAI_API_KEY=sk-your-key-here"
railway up --service api  # Restart to apply
```

**Get API Key**:
1. Go to https://platform.openai.com/api-keys
2. Create new secret key
3. Copy and paste into command above

**Skip if**:
- You don't have OpenAI account
- Not using AI features yet
- Want to add later (can enable anytime)

---

## ✅ Verification Checklist

After completing manual steps, verify:

### 1. Test Login
```
✓ Go to https://analytics.candidstudios.net/login
✓ Log in with NEW admin password
✓ Verify dashboard loads
```

### 2. Test Webhooks
```
✓ Create test contact in GHL
✓ Verify it appears in Analytics dashboard
✓ Update contact in GHL
✓ Verify update syncs to dashboard
```

### 3. Test All Pages
```
✓ Click through all 8 dashboard pages
✓ Verify data loads (may be limited initially)
✓ Check no errors in browser console
```

### 4. Test AI Insights (if enabled)
```
✓ Go to AI Insights page
✓ Verify AI features are working
✓ Check for any API errors
```

---

## 📊 What to Expect

### First 24 Hours
- Webhook data starts flowing in real-time
- Historical data already present (19 clients, 19 inquiries)
- May see limited data on some pages (normal)
- Monitor webhook delivery logs in GHL

### First Week
- More complete data as GHL records update
- Revenue tracking starts populating
- KPIs become more meaningful
- Team can start using dashboard regularly

### Ongoing
- Real-time sync keeps data current
- Historical trends build up
- AI insights improve with more data
- Can customize reports as needed

---

## 🆘 Need Help?

### Common Issues

**Can't log in?**
- Verify you changed password correctly
- Try clearing browser cache
- Check username is exact: `admin@candidstudios.net`

**Webhooks showing errors?**
- Verify `X-Webhook-Secret` header is exact (copy from this file)
- Check GHL webhook delivery logs
- Test webhook manually: `curl -X POST https://api.candidstudios.net/api/webhooks/test -H "Content-Type: application/json" -H "X-Webhook-Secret: makecom_webhook_secret_2025_candid_studios_ghl_integration" -d '{"test":"data"}'`

**Dashboard pages not loading?**
- Check API health: https://api.candidstudios.net/api/health
- View Railway logs: `railway logs --service api`
- Refresh page and check browser console

**AI features not working?**
- Verify OpenAI API key is set: `railway variables --service api | grep OPENAI`
- Check OpenAI account has credits
- Restart API service: `railway up --service api`

### View Logs

```bash
cd ~/Projects/candid-analytics-app
railway logs --service api          # API logs
railway logs --service candid-analytics-app  # Frontend logs
```

### Health Checks

**API Health**:
```bash
curl https://api.candidstudios.net/api/health
```

**Database Check**:
```bash
cd ~/Projects/candid-analytics-app/api
railway run --service api php scripts/verify-database.php
```

---

## 📚 Documentation

- **This File**: Quick start for manual steps
- **DEPLOYMENT-COMPLETE-SUMMARY.md**: Full deployment summary
- **GHL-WEBHOOK-SETUP.md**: Detailed webhook configuration
- **START_HERE.md**: Original deployment guide

---

## 🎯 Summary

**What You Need to Do**:
1. ✅ Change admin password (2 min) - **DO THIS FIRST!**
2. ✅ Configure 4 GHL webhooks (20-30 min)
3. ✅ (Optional) Add OpenAI API key (5 min)
4. ✅ Test and verify everything works (10 min)

**Estimated Total Time**: 30-45 minutes

**Then**: Your Candid Analytics platform is fully operational! 🎉

---

**Questions?** Check documentation files or Railway logs for troubleshooting.

**Last Updated**: 2025-11-02
