# Webhook Integration Guide
## Candid Analytics - Make.com to GHL Data Sync

This document describes how to set up webhooks from Make.com to send GoHighLevel data to your Candid Analytics dashboard.

---

## Overview

The Candid Analytics API provides webhook endpoints to receive real-time data from Make.com scenarios. These webhooks automatically sync your GHL data to the analytics database and refresh dashboard metrics.

### Base URL
```
Production: https://api.candidstudios.net
```

### Authentication
Webhooks use **HMAC SHA256 signature verification** for security.

**Webhook Secret:**
```
makecom_webhook_secret_2025_candid_studios_ghl_integration
```

---

## Webhook Endpoints

### 1. Projects/Bookings Webhook
**Endpoint:** `POST /api/webhooks/projects`

**Purpose:** Sync new bookings and project updates from GHL

**When to trigger:**
- New opportunity created in GHL
- Opportunity moves to "Planning" or "Booked" stage
- Project details updated (dates, revenue, status)

**Required Fields:**
```json
{
  "contact_id": "ghl_contact_id_here",      // REQUIRED: GHL contact ID
  "opportunity_name": "Smith Wedding",       // REQUIRED: Project name
  "booking_date": "2025-11-15",              // REQUIRED: Event/booking date
  "total_value": 3500,                       // REQUIRED: Project value in dollars
  "status": "booked",                        // Optional: booked, in-progress, completed
  "event_type": "wedding",                   // Optional: wedding, portrait, corporate, etc.
  "event_date": "2025-12-20",                // Optional: Actual event date
  "location": "Dallas, TX",                  // Optional: Event location
  "first_name": "John",                      // Optional: Client first name
  "last_name": "Smith",                      // Optional: Client last name
  "email": "john@example.com",               // Optional: Client email
  "phone": "+1-555-0123"                     // Optional: Client phone
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "project_id": "uuid-here",
    "client_id": "uuid-here"
  }
}
```

---

### 2. Revenue/Payments Webhook
**Endpoint:** `POST /api/webhooks/revenue`

**Purpose:** Record payment transactions and revenue

**When to trigger:**
- Payment received in GHL
- Invoice paid
- Deposit or retainer collected

**Required Fields:**
```json
{
  "contact_id": "ghl_contact_id_here",      // REQUIRED: GHL contact ID
  "amount": 1500.00,                         // REQUIRED: Payment amount in dollars
  "payment_date": "2025-10-15",              // Optional: defaults to today
  "payment_method": "credit_card",           // Optional: credit_card, bank_transfer, cash, check
  "category": "booking",                     // Optional: booking, deposit, final_payment, retainer
  "notes": "50% deposit payment"             // Optional: Payment notes
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "revenue_id": "uuid-here",
    "project_id": "uuid-here"
  }
}
```

---

### 3. Inquiries/Leads Webhook
**Endpoint:** `POST /api/webhooks/inquiries`

**Purpose:** Track new leads and inquiries from GHL

**When to trigger:**
- New contact created in GHL
- Form submission received
- New lead enters pipeline

**Required Fields:**
```json
{
  "contact_id": "ghl_contact_id_here",      // REQUIRED: GHL contact ID
  "source": "website",                       // Optional: website, referral, social, google, instagram
  "status": "new",                           // Optional: new, contacted, qualified, lost
  "inquiry_date": "2025-10-10",              // Optional: defaults to today
  "event_type": "wedding",                   // Optional: Event type inquiry
  "budget": 5000,                            // Optional: Client budget
  "outcome": null,                           // Optional: won, lost, null
  "first_name": "Sarah",                     // Optional: Client first name
  "last_name": "Johnson",                    // Optional: Client last name
  "email": "sarah@example.com",              // Optional: Client email
  "phone": "+1-555-0456"                     // Optional: Client phone
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "inquiry_id": "uuid-here",
    "client_id": "uuid-here"
  }
}
```

---

### 4. Test Webhook
**Endpoint:** `POST /api/webhooks/test`

**Purpose:** Test webhook connectivity and payload format

**No authentication required for testing**

**Payload:** Send any JSON
```json
{
  "test": "data",
  "message": "Hello from Make.com"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Test webhook received",
  "received_data": {
    "test": "data",
    "message": "Hello from Make.com"
  }
}
```

---

## Setting Up in Make.com

### Step 1: Add HTTP Module
1. In your Make.com scenario, add an **HTTP > Make a Request** module
2. Set URL to one of the webhook endpoints above
3. Set Method to **POST**
4. Set Request Content-Type to **JSON (application/json)**

### Step 2: Configure Headers
Add the following header for authentication:

**Header Name:** `X-Webhook-Signature`
**Header Value:** (Use the formula below)

```
{{sha256(body; "makecom_webhook_secret_2025_candid_studios_ghl_integration")}}
```

### Step 3: Build Request Body
Map your GHL data to the JSON structure shown above.

**Example for Projects Webhook:**
```json
{
  "contact_id": "{{contact.id}}",
  "opportunity_name": "{{opportunity.name}}",
  "booking_date": "{{contact.when_is_the_event_primary}}",
  "total_value": {{opportunity.monetary_value}},
  "status": "{{opportunity.status}}",
  "event_type": "{{contact.type_of_event_primary}}",
  "location": "{{contact.project_address_primary}}",
  "first_name": "{{contact.first_name}}",
  "last_name": "{{contact.last_name}}",
  "email": "{{contact.email}}",
  "phone": "{{contact.phone}}"
}
```

### Step 4: Test Connection
1. Use the `/api/webhooks/test` endpoint first to verify connectivity
2. Check the Make.com execution log for response status
3. Should see `200 OK` with success message

### Step 5: Test with Real Data
1. Switch to the actual endpoint (projects, revenue, inquiries)
2. Send test data from Make.com
3. Verify data appears in analytics dashboard
4. Check API logs if errors occur

---

## Error Handling

### Common Errors

**401 Unauthorized**
```json
{
  "success": false,
  "error": "Invalid webhook signature"
}
```
**Solution:** Check that your X-Webhook-Signature header is correctly calculated

**400 Bad Request**
```json
{
  "success": false,
  "error": "Missing required fields: contact_id and opportunity_name"
}
```
**Solution:** Ensure all required fields are included in payload

**404 Not Found**
```json
{
  "success": false,
  "error": "Client not found"
}
```
**Solution:** Create client first, or ensure contact_id exists in system

**500 Server Error**
```json
{
  "success": false,
  "error": "Server error processing webhook"
}
```
**Solution:** Check API logs for detailed error message

---

## Monitoring & Logging

All webhook requests are logged in the API logs:
```
/mnt/c/code/candid-analytics-app/api/logs/app.log
```

To view recent webhook activity:
```bash
docker exec candid-analytics-api tail -50 /var/www/logs/app.log | grep webhook
```

---

## Data Flow

```
GHL Event → Make.com Scenario → Webhook Endpoint → PostgreSQL Database → Materialized Views Refreshed → Dashboard Updated
```

### Automatic Updates
When a webhook is received:
1. Client record created/updated if needed
2. Project/Revenue/Inquiry record inserted/updated
3. Materialized views refreshed (all KPI dashboards update)
4. Redis cache cleared for affected metrics
5. Dashboard shows new data within 5-10 seconds

---

## Best Practices

### 1. Use Webhooks for Real-Time Events
- New bookings
- Payments received
- Lead form submissions
- Status changes

### 2. Handle Failures Gracefully
- Make.com should retry failed webhooks (3 attempts recommended)
- Use exponential backoff between retries
- Log failed webhooks for manual review

### 3. Data Validation
- Always send required fields
- Use proper date format: `YYYY-MM-DD`
- Send numeric values as numbers, not strings
- Validate contact_id exists before sending revenue/inquiry webhooks

### 4. Security
- Never expose webhook secret in client-side code
- Only send webhooks from Make.com scenarios
- Rotate webhook secret periodically

---

## Example Make.com Scenarios

### Scenario 1: New Booking
**Trigger:** GHL Opportunity Status Changed to "Booked"

**Steps:**
1. **GHL Trigger:** Watch Opportunities
2. **Router:** Filter where status = "booked"
3. **HTTP Request:** Send to `/api/webhooks/projects`
4. **Error Handler:** Log failures to Google Sheets

### Scenario 2: Payment Received
**Trigger:** GHL Payment Received Webhook

**Steps:**
1. **GHL Webhook:** Payment Received
2. **HTTP Request:** Send to `/api/webhooks/revenue`
3. **Conditional:** If success, update GHL contact custom field
4. **Error Handler:** Send notification email on failure

### Scenario 3: New Lead
**Trigger:** GHL Contact Created

**Steps:**
1. **GHL Trigger:** Watch Contacts
2. **Filter:** Only new contacts (created_at < 5 minutes ago)
3. **HTTP Request:** Send to `/api/webhooks/inquiries`
4. **Follow-up:** Tag contact as "synced to analytics"

---

## Testing Checklist

- [ ] Test webhook endpoint connectivity
- [ ] Verify signature authentication works
- [ ] Send test project data
- [ ] Send test revenue data
- [ ] Send test inquiry data
- [ ] Check dashboard updates within 10 seconds
- [ ] Verify data accuracy in Priority KPIs
- [ ] Test error handling (missing fields, invalid data)
- [ ] Monitor API logs for errors
- [ ] Test Make.com retry logic on failures

---

## Support

**API Logs Location:**
```
/mnt/c/code/candid-analytics-app/api/logs/app.log
```

**Database Connection:**
```bash
docker exec candid-analytics-db psql -U candid_analytics_user -d candid_analytics
```

**Restart API:**
```bash
docker restart candid-analytics-api
```

**Clear Cache:**
```bash
docker exec candid-analytics-redis redis-cli FLUSHALL
```

---

## Changelog

**2025-10-10:** Initial webhook system created
- Projects/bookings endpoint
- Revenue/payments endpoint
- Inquiries/leads endpoint
- HMAC signature authentication
- Auto-refresh materialized views
