# GoHighLevel Webhook Configuration Guide

**Date**: 2025-11-02
**Project**: Candid Analytics Platform
**Purpose**: Real-time sync from GHL to Analytics Database

---

## Overview

Configure 4 webhooks in GoHighLevel to automatically sync data to the Candid Analytics platform in real-time.

**Webhook Base URL**: `https://api.candidstudios.net/api/webhooks`

**Authentication**: Webhooks use signature validation with `WEBHOOK_SECRET`
**Current Secret**: `makecom_webhook_secret_2025_candid_studios_ghl_integration`

---

## Webhook #1: Opportunity Updates (Projects & Inquiries)

**Trigger**: When opportunity is created or updated
**Webhook URL**: `https://api.candidstudios.net/api/webhooks/projects`

### GHL Configuration Steps:

1. Go to **Settings** → **Integrations** → **Webhooks**
2. Click **Add Webhook**
3. Configure:
   - **Name**: `Candid Analytics - Opportunities`
   - **Webhook URL**: `https://api.candidstudios.net/api/webhooks/projects`
   - **Trigger**: `Opportunity Created` + `Opportunity Updated`
   - **Method**: `POST`
   - **Headers**:
     - `Content-Type: application/json`
     - `X-Webhook-Secret: makecom_webhook_secret_2025_candid_studios_ghl_integration`

4. **Payload** - Include all fields:
   - ✅ Opportunity ID
   - ✅ Contact ID
   - ✅ Pipeline Stage
   - ✅ Status
   - ✅ Custom Fields (all 57 fields)
   - ✅ Created/Updated timestamps

5. Click **Save**

### What This Webhook Does:

- **If pipeline stage = "Planning"**: Creates/updates PROJECT record
- **Otherwise**: Creates/updates INQUIRY record
- Links to client via contact ID
- Syncs all custom fields (event date, type, venue, pricing, etc.)

---

## Webhook #2: Contact Updates (Clients)

**Trigger**: When contact is created or updated
**Webhook URL**: `https://api.candidstudios.net/api/webhooks/inquiries`

### GHL Configuration Steps:

1. Go to **Settings** → **Integrations** → **Webhooks**
2. Click **Add Webhook**
3. Configure:
   - **Name**: `Candid Analytics - Contacts`
   - **Webhook URL**: `https://api.candidstudios.net/api/webhooks/inquiries`
   - **Trigger**: `Contact Created` + `Contact Updated`
   - **Method**: `POST`
   - **Headers**:
     - `Content-Type: application/json`
     - `X-Webhook-Secret: makecom_webhook_secret_2025_candid_studios_ghl_integration`

4. **Payload** - Include all fields:
   - ✅ Contact ID
   - ✅ Name (first/last)
   - ✅ Email
   - ✅ Phone
   - ✅ Tags
   - ✅ Source
   - ✅ Custom Fields (partner info, mailing address, engagement score)

5. Click **Save**

### What This Webhook Does:

- Creates/updates CLIENT record in database
- Syncs contact info, tags, partner details
- Updates engagement score
- Links to inquiries and projects via contact ID

---

## Webhook #3: Appointment Updates (Consultations)

**Trigger**: When appointment/calendar event is created or updated
**Webhook URL**: `https://api.candidstudios.net/api/webhooks/consultations`

### GHL Configuration Steps:

1. Go to **Settings** → **Integrations** → **Webhooks**
2. Click **Add Webhook**
3. Configure:
   - **Name**: `Candid Analytics - Appointments`
   - **Webhook URL**: `https://api.candidstudios.net/api/webhooks/consultations`
   - **Trigger**: `Appointment Created` + `Appointment Updated` + `Appointment Cancelled`
   - **Method**: `POST`
   - **Headers**:
     - `Content-Type: application/json`
     - `X-Webhook-Secret: makecom_webhook_secret_2025_candid_studios_ghl_integration`

4. **Payload** - Include all fields:
   - ✅ Appointment ID
   - ✅ Contact ID
   - ✅ Calendar ID
   - ✅ Event Start/End Time
   - ✅ Status (scheduled/completed/cancelled)
   - ✅ Meeting Link
   - ✅ Notes

5. Click **Save**

### What This Webhook Does:

- Creates/updates CONSULTATION record
- Links to client via contact ID
- Tracks consultation outcomes
- Updates project metadata with calendar event ID

---

## Webhook #4: Payment/Revenue Updates

**Trigger**: When payment is received
**Webhook URL**: `https://api.candidstudios.net/api/webhooks/revenue`

### GHL Configuration Steps:

1. Go to **Settings** → **Integrations** → **Webhooks**
2. Click **Add Webhook**
3. Configure:
   - **Name**: `Candid Analytics - Payments`
   - **Webhook URL**: `https://api.candidstudios.net/api/webhooks/revenue`
   - **Trigger**: `Payment Received` + `Invoice Paid`
   - **Method**: `POST`
   - **Headers**:
     - `Content-Type: application/json`
     - `X-Webhook-Secret: makecom_webhook_secret_2025_candid_studios_ghl_integration`

4. **Payload** - Include all fields:
   - ✅ Transaction ID
   - ✅ Amount
   - ✅ Currency
   - ✅ Payment Method
   - ✅ Contact ID / Opportunity ID
   - ✅ Payment Date
   - ✅ Status

5. Click **Save**

### What This Webhook Does:

- Updates project revenue tracking
- Records payment milestones (deposit, balance, etc.)
- Updates financial KPIs in real-time

---

## Testing Webhooks

### Test Endpoint

Use the test endpoint to verify webhook delivery:

```bash
curl -X POST https://api.candidstudios.net/api/webhooks/test \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Secret: makecom_webhook_secret_2025_candid_studios_ghl_integration" \
  -d '{"test": "data", "timestamp": "2025-11-02T12:00:00Z"}'
```

**Expected Response**:
```json
{
  "success": true,
  "message": "Test webhook received",
  "received_data": {
    "test": "data",
    "timestamp": "2025-11-02T12:00:00Z"
  }
}
```

### Verify in GHL

After configuring each webhook in GHL:

1. Trigger a test event (create/update a contact, opportunity, etc.)
2. Check **Settings** → **Integrations** → **Webhooks** → Click on webhook → View **Delivery Log**
3. Verify:
   - ✅ Status: 200 OK
   - ✅ Response time < 2 seconds
   - ✅ No errors in payload

### Check Logs

Monitor webhook processing on Railway:

```bash
railway logs --service api
```

Look for entries like:
```
[INFO] Webhook received: projects
[INFO] Processing opportunity: abc123
[INFO] Project created/updated successfully
```

---

## Troubleshooting

### Webhook Returns 401 Unauthorized

**Cause**: Missing or incorrect `X-Webhook-Secret` header

**Fix**: Verify the header value matches exactly:
```
X-Webhook-Secret: makecom_webhook_secret_2025_candid_studios_ghl_integration
```

### Webhook Returns 400 Bad Request

**Cause**: Missing required fields in payload

**Fix**: Ensure GHL webhook is configured to include ALL fields, especially:
- Contact ID
- Opportunity ID (for projects)
- Custom Fields

### Webhook Returns 500 Internal Server Error

**Cause**: Database connection issue or data validation error

**Fix**:
1. Check Railway logs: `railway logs --service api`
2. Verify database is running: `railway run --service api php scripts/verify-database.php`
3. Check for missing custom field IDs in webhook payload

### Data Not Syncing

**Cause**: Webhook not triggered by GHL

**Fix**:
1. Check GHL webhook delivery logs
2. Verify trigger events are configured correctly
3. Test manually by creating/updating a record in GHL

---

## Security Notes

- ✅ Webhooks are NOT protected by JWT authentication (line 119 in index.php)
- ✅ Use signature validation with `WEBHOOK_SECRET` instead
- ✅ Always use HTTPS (enforced)
- ✅ Validate webhook secret on every request
- ⚠️ **DO NOT** expose `WEBHOOK_SECRET` in client-side code
- ⚠️ **DO NOT** log full webhook payloads (may contain PII)

---

## Post-Configuration Checklist

After configuring all 4 webhooks:

- [ ] Test each webhook with real data
- [ ] Verify delivery logs in GHL show 200 OK responses
- [ ] Check Railway logs for successful processing
- [ ] Run database verification: `php scripts/verify-database.php`
- [ ] Monitor for 24 hours to ensure consistent sync
- [ ] Document any custom field ID changes

---

## Webhook URLs Summary

| Webhook | URL | Trigger Events |
|---------|-----|----------------|
| Opportunities | `https://api.candidstudios.net/api/webhooks/projects` | Opportunity Created/Updated |
| Contacts | `https://api.candidstudios.net/api/webhooks/inquiries` | Contact Created/Updated |
| Appointments | `https://api.candidstudios.net/api/webhooks/consultations` | Appointment Created/Updated/Cancelled |
| Payments | `https://api.candidstudios.net/api/webhooks/revenue` | Payment Received/Invoice Paid |

---

## Next Steps

1. Configure all 4 webhooks in GHL dashboard (requires login access)
2. Test each webhook with sample data
3. Verify real-time sync is working
4. Monitor for errors in first 24 hours

**Estimated Time**: 20-30 minutes
**Access Required**: GHL Admin account
