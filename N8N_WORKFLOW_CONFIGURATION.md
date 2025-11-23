# n8n Workflow Configuration Guide for Candid Analytics

**Generated:** 2025-11-22
**n8n Instance:** https://n8n-production-5eb7.up.railway.app
**Analytics API:** https://api.candidstudios.net

---

## CRITICAL ISSUES FOUND

All three analytics workflows have **empty body parameters** - they're not forwarding any data to the API!

### Current Workflow Status:

| Workflow | ID | Webhook Path | Target Endpoint | Status |
|----------|-----|--------------|-----------------|--------|
| Analytics: GHL New Inquiry Webhook | dEKmDvMJYZoBlwPZ | /analytics-new-inquiry | /api/webhooks/inquiries | BROKEN - Empty body |
| Analytics: GHL Project Booking | V7bB25Z2CGGwCacU | /project-booking | /api/webhooks/projects | BROKEN - Empty body, missing pipeline_id |
| Analytics: Payment Received | 1vEcU4DWf0w8RU3U | /payment-received | /api/webhooks/revenue | WRONG ENDPOINT |

---

## 1. Analytics: GHL New Inquiry Webhook

**Purpose:** Receives new leads from GHL and forwards to Analytics API

### Webhook URLs:
- **n8n Webhook (receives from GHL):** `https://n8n-production-5eb7.up.railway.app/webhook/analytics-new-inquiry`
- **Analytics API (sends to):** `https://api.candidstudios.net/api/webhooks/inquiries`

### Required Configuration:

**Step 1: Configure the HTTP Request node "Forward to Analytics API"**

Change from empty body parameters to **JSON Body** mode with the following expression:

```json
{
  "ghl_contact_id": "{{ $json.contact_id || $json.contactId }}",
  "ghl_location_id": "{{ $json.location_id || $json.locationId }}",
  "first_name": "{{ $json.first_name || $json.firstName || $json.customData?.first_name }}",
  "last_name": "{{ $json.last_name || $json.lastName || $json.customData?.last_name }}",
  "email": "{{ $json.email }}",
  "phone": "{{ $json.phone }}",
  "source": "{{ $json.source || $json.customData?.lead_source || 'website' }}",
  "event_type": "{{ $json.customData?.['AFX1YsPB7QHBP50Ajs1Q'] || $json.customData?.event_type }}",
  "event_date": "{{ $json.customData?.['kvDBYw8fixMftjWdF51g'] || $json.customData?.event_date }}",
  "venue_address": "{{ $json.customData?.['nstR5hDlCQJ6jpsFzxi7'] || $json.customData?.project_location }}",
  "notes": "{{ $json.customData?.['xV2dxG35gDY1Vqb00Ql1'] || $json.customData?.additional_notes }}",
  "promo_code": "{{ $json.customData?.['WIGtBzBlqXEgpAY1D9I9'] || $json.customData?.promo_code }}",
  "partner_first_name": "{{ $json.customData?.['WPisKIBj4RYy6LkapuX7'] }}",
  "partner_last_name": "{{ $json.customData?.['7jNpL5BQB3DHJ5mcsFZP'] }}",
  "partner_email": "{{ $json.customData?.['AtOOSx6IrAHhwMps1Ayi'] }}",
  "partner_phone": "{{ $json.customData?.['iCPDAVCj8RtdRyGNFF12'] }}",
  "raw_data": {{ JSON.stringify($json) }}
}
```

**Step 2: Remove the OpenAI node** (unnecessary for simple data forwarding)

---

## 2. Analytics: GHL Project Booking

**Purpose:** Creates projects when opportunities move to PLANNING pipeline (Booked stage)

### CRITICAL: This workflow should ONLY trigger when:
1. Opportunity moves to **PLANNING pipeline** (`L2s9gNWdWzCbutNTC4DE`)
2. Specifically to **Booked stage** (`bad101e4-ff48-4ab8-845a-1660f0c0c7da`)

### Webhook URLs:
- **n8n Webhook (receives from GHL):** `https://n8n-production-5eb7.up.railway.app/webhook/project-booking`
- **Analytics API (sends to):** `https://api.candidstudios.net/api/webhooks/projects`

### Required Configuration:

**Step 1: Add an IF node after the webhook to filter by pipeline**

Add a condition node that checks:
```
$json.pipeline_id === 'L2s9gNWdWzCbutNTC4DE'
```

**Step 2: Configure the HTTP Request node with proper body:**

```json
{
  "ghl_contact_id": "{{ $json.contact_id || $json.contactId }}",
  "ghl_opportunity_id": "{{ $json.opportunity_id || $json.opportunityId }}",
  "ghl_pipeline_id": "{{ $json.pipeline_id || $json.pipelineId }}",
  "ghl_stage_id": "{{ $json.stage_id || $json.stageId }}",
  "ghl_stage_name": "{{ $json.stage_name || $json.stageName || 'Booked' }}",
  "client_name": "{{ $json.contact_name || $json.contactName || ($json.first_name + ' ' + $json.last_name) }}",
  "email": "{{ $json.email }}",
  "phone": "{{ $json.phone }}",
  "event_type": "{{ $json.customData?.['AFX1YsPB7QHBP50Ajs1Q'] }}",
  "event_date": "{{ $json.customData?.['kvDBYw8fixMftjWdF51g'] }}",
  "venue_name": "{{ $json.customData?.venue_name }}",
  "venue_address": "{{ $json.customData?.['nstR5hDlCQJ6jpsFzxi7'] }}",
  "estimated_value": "{{ $json.monetary_value || $json.customData?.['OwkEjGNrbE7Rq0TKBG3M'] || 0 }}",
  "has_photography": "{{ $json.customData?.['00cH1d6lq8m0U8tf3FHg']?.includes('Photography') || false }}",
  "has_videography": "{{ $json.customData?.['NZh0hsK8OaQ1vHrU0Lkq'] === 'Yes' || $json.customData?.['00cH1d6lq8m0U8tf3FHg']?.includes('Videography') || false }}",
  "photo_hours": "{{ $json.customData?.['T5nq3eiHUuXM0wFYNNg4'] || 0 }}",
  "video_hours": "{{ $json.customData?.['nHiHJxfNxRhvUfIu6oD6'] || 0 }}",
  "photographer_name": "{{ $json.customData?.['NUC0izbVu26XEiriE5Up'] }}",
  "videographer_name": "{{ $json.customData?.['HH0onKM31fhdsh4pnvh3'] }}",
  "project_manager": "{{ $json.customData?.['as6qzWMAaodZSH2JgUCt'] }}",
  "notes": "{{ $json.customData?.['xV2dxG35gDY1Vqb00Ql1'] }}",
  "raw_data": {{ JSON.stringify($json) }}
}
```

---

## 3. Analytics: Payment Received (NEEDS NEW CONFIGURATION)

**Purpose:** Records actual Stripe payments as revenue

### CRITICAL: This workflow needs to receive Stripe webhooks, NOT GHL webhooks!

### Option A: Stripe Direct Webhook (Recommended)

Configure Stripe to send `payment_intent.succeeded` events directly to n8n:

**Stripe Webhook URL:** `https://n8n-production-5eb7.up.railway.app/webhook/stripe-payment`

**Required Configuration:**

1. Change webhook path from `payment-received` to `stripe-payment`
2. Change target URL from `/api/webhooks/revenue` to `/api/webhooks/stripe/payment`
3. Configure body:

```json
{
  "stripe_payment_id": "{{ $json.data.object.id }}",
  "stripe_charge_id": "{{ $json.data.object.latest_charge }}",
  "stripe_customer_id": "{{ $json.data.object.customer }}",
  "stripe_invoice_id": "{{ $json.data.object.invoice }}",
  "amount": "{{ $json.data.object.amount / 100 }}",
  "currency": "{{ $json.data.object.currency }}",
  "payment_date": "{{ new Date($json.data.object.created * 1000).toISOString() }}",
  "description": "{{ $json.data.object.description }}",
  "client_email": "{{ $json.data.object.receipt_email }}",
  "metadata": {{ JSON.stringify($json.data.object.metadata) }},
  "raw_data": {{ JSON.stringify($json) }}
}
```

### Option B: GHL Intermediary (If GHL handles Stripe)

If payments are processed through GHL and GHL sends payment notifications:

```json
{
  "ghl_contact_id": "{{ $json.contact_id || $json.contactId }}",
  "stripe_payment_id": "{{ $json.payment_intent_id || $json.stripe_payment_id }}",
  "amount": "{{ $json.amount || $json.payment_amount }}",
  "payment_date": "{{ $json.payment_date || new Date().toISOString() }}",
  "description": "{{ $json.description || 'Payment received' }}",
  "raw_data": {{ JSON.stringify($json) }}
}
```

---

## 4. NEW WORKFLOW NEEDED: Stripe Refund

**Purpose:** Track refunds for accurate revenue reporting

### Create new workflow with:

**Webhook Path:** `/stripe-refund`
**Target URL:** `https://api.candidstudios.net/api/webhooks/stripe/refund`

**Body Configuration:**

```json
{
  "stripe_refund_id": "{{ $json.data.object.id }}",
  "stripe_charge_id": "{{ $json.data.object.charge }}",
  "stripe_payment_id": "{{ $json.data.object.payment_intent }}",
  "refund_amount": "{{ $json.data.object.amount / 100 }}",
  "currency": "{{ $json.data.object.currency }}",
  "refund_date": "{{ new Date($json.data.object.created * 1000).toISOString() }}",
  "reason": "{{ $json.data.object.reason }}",
  "status": "{{ $json.data.object.status }}",
  "raw_data": {{ JSON.stringify($json) }}
}
```

---

## 5. OPTIONAL: Delivery Status Updates

**Purpose:** Track project delivery milestones

### Create workflow with:

**Webhook Path:** `/delivery-update`
**Target URL:** `https://api.candidstudios.net/api/webhooks/deliveries`

**Body Configuration:**

```json
{
  "ghl_contact_id": "{{ $json.contact_id }}",
  "ghl_opportunity_id": "{{ $json.opportunity_id }}",
  "delivery_status": "{{ $json.customData?.delivery_status }}",
  "raw_photos_url": "{{ $json.customData?.['bp5oCoPifWXOOcN7Z79F'] }}",
  "raw_video_url": "{{ $json.customData?.['K3fNomA8tFU3wShooTTh'] }}",
  "final_photos_url": "{{ $json.customData?.['epv4xKKDDS1HqbiRz7Wc'] }}",
  "final_video_url": "{{ $json.customData?.['QjjCsBRRNu0FlD0ocEJk'] }}",
  "additional_videos_url": "{{ $json.customData?.['cGEUu0JUCDJDwJsHsyQa'] }}",
  "photographer_notes": "{{ $json.customData?.['Moa0uJbJTUs3gi4d8zw1'] }}",
  "raw_data": {{ JSON.stringify($json) }}
}
```

---

## 6. OPTIONAL: Client Reviews

**Purpose:** Track client feedback and ratings

### Create workflow with:

**Webhook Path:** `/client-review`
**Target URL:** `https://api.candidstudios.net/api/webhooks/reviews`

**Body Configuration:**

```json
{
  "ghl_contact_id": "{{ $json.contact_id }}",
  "overall_rating": "{{ $json.customData?.overall_rating }}",
  "photographer_rating": "{{ $json.customData?.photographer_rating }}",
  "videographer_rating": "{{ $json.customData?.videographer_rating }}",
  "nps_score": "{{ $json.customData?.nps_score }}",
  "would_recommend": "{{ $json.customData?.would_recommend === 'Yes' }}",
  "review_text": "{{ $json.customData?.review_text }}",
  "review_platform": "{{ $json.customData?.review_platform }}",
  "review_link": "{{ $json.customData?.['fIkJwAvbFzQGcLbKTbat'] }}",
  "raw_data": {{ JSON.stringify($json) }}
}
```

---

## GHL Webhook Configuration in GHL

For GHL to send data to n8n, you need to configure webhooks in GoHighLevel:

### Location: Settings > Webhooks

Create the following webhooks:

| Event | Webhook URL |
|-------|-------------|
| Contact Created | `https://n8n-production-5eb7.up.railway.app/webhook/analytics-new-inquiry` |
| Opportunity Stage Changed | `https://n8n-production-5eb7.up.railway.app/webhook/project-booking` |

### Stripe Configuration:

In Stripe Dashboard > Developers > Webhooks:

| Event | Webhook URL |
|-------|-------------|
| payment_intent.succeeded | `https://n8n-production-5eb7.up.railway.app/webhook/stripe-payment` |
| charge.refunded | `https://n8n-production-5eb7.up.railway.app/webhook/stripe-refund` |

---

## Custom Field ID Reference

These are the GHL custom field IDs used in the configurations above:

| Field ID | Field Name |
|----------|-----------|
| `AFX1YsPB7QHBP50Ajs1Q` | Event Type |
| `kvDBYw8fixMftjWdF51g` | Event Date |
| `T5nq3eiHUuXM0wFYNNg4` | Photography Hours |
| `nHiHJxfNxRhvUfIu6oD6` | Videography Hours |
| `nstR5hDlCQJ6jpsFzxi7` | Project Location |
| `OwkEjGNrbE7Rq0TKBG3M` | Opportunity Value |
| `00cH1d6lq8m0U8tf3FHg` | Services Interested In |
| `NZh0hsK8OaQ1vHrU0Lkq` | Does This Project Have Video? |
| `xV2dxG35gDY1Vqb00Ql1` | Additional Notes |
| `NUC0izbVu26XEiriE5Up` | Assigned Photographer (First Name) |
| `HH0onKM31fhdsh4pnvh3` | Assigned Videographer (First Name) |
| `as6qzWMAaodZSH2JgUCt` | Project Manager |
| `WPisKIBj4RYy6LkapuX7` | Partner's First Name |
| `7jNpL5BQB3DHJ5mcsFZP` | Partner's Last Name |
| `AtOOSx6IrAHhwMps1Ayi` | Partner's Email |
| `iCPDAVCj8RtdRyGNFF12` | Partner's Phone |
| `WIGtBzBlqXEgpAY1D9I9` | Promo Code |
| `bp5oCoPifWXOOcN7Z79F` | Link To Raw Images |
| `K3fNomA8tFU3wShooTTh` | Link to RAW Video Content |
| `epv4xKKDDS1HqbiRz7Wc` | Link to Final Image Gallery |
| `QjjCsBRRNu0FlD0ocEJk` | Link to Final Video |
| `cGEUu0JUCDJDwJsHsyQa` | Link For Additional Videos |
| `fIkJwAvbFzQGcLbKTbat` | Review Link Based On Location |
| `Moa0uJbJTUs3gi4d8zw1` | Photographers Notes To Editors |

---

## Pipeline ID Reference

| Pipeline | ID |
|----------|-----|
| SALES | `olVOJoYVKm8BmBy9S7ei` |
| PLANNING (Bookings) | `L2s9gNWdWzCbutNTC4DE` |
| PHOTO EDITING | `7c0c7dVZbpVUsfBxztAb` |
| VIDEO EDITING | `HdUdwCdOxIShUwe7fgZb` |
| ARCHIVED | `KJcBDVCLIV8c7vbkJZnb` |

**CRITICAL: Only opportunities in PLANNING pipeline (`L2s9gNWdWzCbutNTC4DE`) should create projects!**

---

## Testing Steps

1. **Test Inquiry Webhook:**
   ```bash
   curl -X POST https://n8n-production-5eb7.up.railway.app/webhook/analytics-new-inquiry \
     -H "Content-Type: application/json" \
     -d '{"contact_id": "test123", "first_name": "Test", "last_name": "User", "email": "test@example.com"}'
   ```

2. **Test Project Booking Webhook:**
   ```bash
   curl -X POST https://n8n-production-5eb7.up.railway.app/webhook/project-booking \
     -H "Content-Type: application/json" \
     -d '{"contact_id": "test123", "opportunity_id": "opp123", "pipeline_id": "L2s9gNWdWzCbutNTC4DE", "stage_id": "bad101e4-ff48-4ab8-845a-1660f0c0c7da"}'
   ```

3. **Check Analytics API directly:**
   ```bash
   curl -X POST https://api.candidstudios.net/api/webhooks/test \
     -H "Content-Type: application/json" \
     -d '{"test": true, "message": "Hello from test"}'
   ```

---

**Last Updated:** 2025-11-22
