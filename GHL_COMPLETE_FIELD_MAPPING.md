# Complete GoHighLevel Custom Field Mapping for Analytics Dashboard
**Last Updated:** 2025-11-02
**Status:** 11 Core Fields Documented, Additional Fields Pending API Access

---

## API Configuration

### GoHighLevel API Credentials
```
API Key: pit-4a0c3927-1650-44dd-b63d-2f65d81f84c3 (⚠️ EXPIRED - NEEDS UPDATE)
Location ID: GHJ0X5n0UomysnUPNfao
API Base URL: https://services.leadconnectorhq.com
API Version: 2021-07-28
```

**⚠️ ACTION REQUIRED:** Update GHL API key to discover additional custom fields
**API Endpoint:** `GET /locations/{locationId}/customFields`

---

## Part 1: DOCUMENTED CUSTOM FIELDS (11 Fields)

### Core Project/Event Fields

| # | GHL Field ID | Field Name | Type | Analytics Mapping | Priority |
|---|---|---|---|---|---|
| 1 | `00cH1d6lq8m0U8tf3FHg` | **Services Interested In** | Array | `projects.metadata->services` | ⭐ High |
| 2 | `AFX1YsPB7QHBP50Ajs1Q` | **Event Type** | String | `projects.event_type` | ⭐⭐⭐ Critical |
| 3 | `Bz6tmEcB0S0pXupkha84` | **Event Start Time** | String | `projects.metadata->event_time` | Medium |
| 4 | `T5nq3eiHUuXM0wFYNNg4` | **Photography Hours** | Number | `projects.metadata->photo_hours` | ⭐ High |
| 5 | `nHiHJxfNxRhvUfIu6oD6` | **Videography Hours** | Number | `projects.metadata->video_hours` | ⭐ High |
| 6 | `iQOUEUruaZfPKln4sdKP` | **Drone Services** | String (Yes/No) | `projects.metadata->drone_services` | Medium |
| 7 | `kvDBYw8fixMftjWdF51g` | **Event Date** | Date (YYYY-MM-DD) | `projects.event_date` | ⭐⭐⭐ Critical |
| 8 | `nstR5hDlCQJ6jpsFzxi7` | **Event Location** | String (Address) | `projects.venue_address` | ⭐⭐ High |
| 9 | `qpyukeOGutkXczPGJOyK` | **Contact Name** | String | `projects.metadata->contact_name` | Medium |
| 10 | `OwkEjGNrbE7Rq0TKBG3M` | **Estimated Budget/Value** | Number (Decimal) | `inquiries.budget` or `projects.total_revenue` | ⭐⭐⭐ Critical |
| 11 | `xV2dxG35gDY1Vqb00Ql1` | **Project Description/Notes** | Text (Long) | `projects.notes` or `inquiries.notes` | ⭐ High |

---

## Part 2: STANDARD GHL CONTACT FIELDS

| GHL Field | Example Value | Analytics DB Field | Required | Notes |
|---|---|---|---|---|
| `id` | `4Yu702qGLPHc17MS9cvl` | `clients.ghl_contact_id` | ✅ Yes | Primary key for GHL integration |
| `firstName` | `Michael` | `clients.first_name` | ✅ Yes | |
| `lastName` | `Obrand` | `clients.last_name` | No | |
| `email` | `mike@example.com` | `clients.email` | ✅ Yes | Required for client creation |
| `phone` | `+13058908996` | `clients.phone` | No | |
| `source` | `website inquiry form` | `clients.lead_source` | No | Maps to lead_sources table |
| `type` | `lead` / `client` | `clients.lifecycle_stage` | No | Updated when they book |
| `tags` | `["event", "consultation"]` | `clients.tags` | No | Array of tags |
| `dateAdded` | `2025-10-10T16:26:19.436Z` | `clients.created_at` | No | ISO timestamp |

---

## Part 3: STANDARD GHL OPPORTUNITY FIELDS

| GHL Field | Example Value | Analytics DB Field | Required | Notes |
|---|---|---|---|---|
| `id` | `opp_xyz123` | `projects.ghl_opportunity_id` | ✅ Yes | Links project to GHL deal |
| `name` | `John Smith - Wedding` | `projects.project_name` | ✅ Yes | |
| `pipelineId` | `pipeline_abc` | - | No | Used for routing |
| **`pipelineStage`** | **`Planning`** | **`projects.status`** | ⭐⭐⭐ | **CRITICAL: Determines if booked** |
| `monetaryValue` | `5000.00` | `projects.total_revenue` | No | Estimated value |
| `status` | `open` / `won` / `lost` | `inquiries.status` | No | |
| `contactId` | `4Yu702qGLPHc17MS9cvl` | `projects.client_id` | ✅ Yes | FK to clients table |
| `createdAt` | `2025-10-10T16:26:19.436Z` | `inquiries.inquiry_date` | No | When opportunity created |

---

## Part 4: BOOKING CLASSIFICATION LOGIC

### How to Identify a "Booked" Project

**Rule:** A contact becomes a BOOKED PROJECT when:
```
opportunity.pipelineStage === 'Planning'
```

**Data Flow:**

1. **New Lead (Inquiry Stage):**
   - Contact created in GHL
   - Opportunity created but NOT in "Planning" stage
   - Import as: `inquiries` table record
   - Status: `new` or `contacted`

2. **Booked Project (Planning Stage):**
   - Opportunity moved to "Planning" pipeline stage
   - Import as: `projects` table record
   - Status: `booked`
   - Update client lifecycle_stage to `client`

3. **Payment Received:**
   - Invoice marked as paid in GHL
   - Import as: `revenue_transactions` table record
   - Update project.total_revenue (running total)

---

## Part 5: MISSING CUSTOM FIELDS (Need to Discover/Create)

### Staff Assignment Fields (for Staff Productivity metrics)
**Status:** ❓ Unknown if these exist in GHL

- [ ] Photographer Assigned (User ID or Name)
- [ ] Videographer Assigned (User ID or Name)
- [ ] Project Manager Assigned
- [ ] Sales Agent Assigned
- [ ] Hours Worked (per staff member)

**If Missing:** Consider tracking in separate system or creating custom fields in GHL

### Delivery/Fulfillment Fields (for Operations metrics)
**Status:** ❓ Unknown if these exist in GHL

- [ ] Expected Photo Delivery Date
- [ ] Expected Video Delivery Date
- [ ] Actual Delivery Date
- [ ] Delivery Status (pending/delivered/late)
- [ ] Revision Count
- [ ] Deliverable Type (photos/video/album)

**If Missing:** May need to use project management tool or create GHL custom fields

### Review/Satisfaction Fields (for Client Satisfaction metrics)
**Status:** ❓ Unknown if these exist in GHL

- [ ] Overall Rating (1-5 stars)
- [ ] Photographer Rating (1-5)
- [ ] Videographer Rating (1-5)
- [ ] Communication Rating (1-5)
- [ ] Value Rating (1-5)
- [ ] NPS Score (0-10)
- [ ] Would Recommend (Yes/No)
- [ ] Review Feedback Text
- [ ] Review Date

**If Missing:** Consider using separate review platform or Google Forms → GHL

### Marketing/Campaign Fields (for Marketing metrics)
**Status:** ❓ Unknown if these exist in GHL

- [ ] Campaign Name
- [ ] Campaign Type (google_ads, facebook_ads, instagram, email, etc.)
- [ ] Campaign Budget
- [ ] Campaign Spend
- [ ] Ad Impressions
- [ ] Ad Clicks
- [ ] Email Opens
- [ ] Email Clicks
- [ ] Social Engagement

**If Missing:** May need to integrate with Google Ads API, Facebook Ads API, etc.

---

## Part 6: FIELD TRANSFORMATION RULES

### Data Type Conversions

| GHL Type | Example GHL Value | Transform To | Example DB Value |
|---|---|---|---|
| String (Yes/No) | `"Yes"` | Boolean | `true` |
| String (Yes/No) | `"No"` | Boolean | `false` |
| ISO Date | `"2025-10-10T16:26:19.436Z"` | Date | `2025-10-10` |
| Date String | `"2025-11-01"` | Date | `2025-11-01` |
| Number String | `"4055.79"` | Decimal | `4055.79` |
| Array | `["Videography", "Editing"]` | JSON | `["Videography", "Editing"]` |
| Empty String | `""` | NULL | `NULL` |

### Validation Rules

| Field | Validation | Default | Notes |
|---|---|---|---|
| event_date | Must be valid date, future date preferred | `NULL` | |
| total_revenue | Must be >= 0 | `0.00` | |
| photo_hours | Must be >= 0 | `0` | |
| video_hours | Must be >= 0 | `0` | |
| drone_services | "Yes" or "No" only | `"No"` | |
| event_type | Must be in enum list | `"other"` | wedding, portrait, event, corporate, real-estate, other |

---

## Part 7: DATABASE TABLE MAPPING

### For New Inquiries (Leads NOT in Planning stage)

```php
// Table: clients
INSERT INTO clients (
    ghl_contact_id,
    first_name,
    last_name,
    email,
    phone,
    lead_source,
    lifecycle_stage,
    tags,
    created_at
) VALUES (
    $ghlContact['id'],
    $ghlContact['firstName'],
    $ghlContact['lastName'],
    $ghlContact['email'],
    $ghlContact['phone'],
    $ghlContact['source'],
    'lead', // Not 'client' until they book
    json_encode($ghlContact['tags']),
    $ghlContact['dateAdded']
);

// Table: inquiries
INSERT INTO inquiries (
    client_id,
    inquiry_date,
    source,
    event_type,
    event_date,
    budget,
    status,
    notes
) VALUES (
    $clientId,
    $opportunity['createdAt'],
    $contact['source'],
    $customFields['AFX1YsPB7QHBP50Ajs1Q'], // Event Type
    $customFields['kvDBYw8fixMftjWdF51g'], // Event Date
    $customFields['OwkEjGNrbE7Rq0TKBG3M'], // Estimated Budget
    'new',
    $customFields['xV2dxG35gDY1Vqb00Ql1'] // Notes
);
```

### For Booked Projects (Moved to Planning stage)

```php
// Table: projects
INSERT INTO projects (
    client_id,
    ghl_opportunity_id,
    project_name,
    booking_date,
    event_date,
    event_type,
    venue_address,
    status,
    total_revenue,
    metadata,
    notes
) VALUES (
    $clientId,
    $opportunity['id'],
    $opportunity['name'],
    NOW(), // Booking date = when moved to Planning
    $customFields['kvDBYw8fixMftjWdF51g'], // Event Date
    $customFields['AFX1YsPB7QHBP50Ajs1Q'], // Event Type
    $customFields['nstR5hDlCQJ6jpsFzxi7'], // Venue Address
    'booked',
    $customFields['OwkEjGNrbE7Rq0TKBG3M'], // Estimated Value
    json_encode([
        'services' => $customFields['00cH1d6lq8m0U8tf3FHg'],
        'photo_hours' => $customFields['T5nq3eiHUuXM0wFYNNg4'],
        'video_hours' => $customFields['nHiHJxfNxRhvUfIu6oD6'],
        'drone_services' => $customFields['iQOUEUruaZfPKln4sdKP'],
        'event_time' => $customFields['Bz6tmEcB0S0pXupkha84'],
        'contact_name' => $customFields['qpyukeOGutkXczPGJOyK']
    ]),
    $customFields['xV2dxG35gDY1Vqb00Ql1'] // Notes
);

// Update client lifecycle
UPDATE clients SET lifecycle_stage = 'client' WHERE id = $clientId;

// Update inquiry status (if it exists)
UPDATE inquiries SET status = 'booked', outcome = 'won'
WHERE client_id = $clientId AND event_date = $eventDate;
```

### For Revenue (Invoice Paid)

```php
// Table: revenue_transactions
INSERT INTO revenue_transactions (
    project_id,
    client_id,
    payment_date,
    amount,
    payment_method,
    payment_type,
    status
) VALUES (
    $projectId,
    $clientId,
    $invoice['paidAt'], // Actual payment date
    $invoice['amount'], // Actual amount paid
    $invoice['paymentMethod'], // From GHL invoice
    'deposit', // or 'partial', 'final', 'addon'
    'completed'
);

// Update project total revenue
UPDATE projects
SET total_revenue = (
    SELECT SUM(amount) FROM revenue_transactions WHERE project_id = $projectId
)
WHERE id = $projectId;
```

---

## Part 8: METRICS THAT CAN BE CALCULATED

### ✅ Metrics We CAN Calculate with Current Fields (11 fields)

| Metric Category | Metrics Available | Custom Fields Required |
|---|---|---|
| **Revenue Analytics** | Monthly revenue, booking count, avg booking value, YoY growth | `OwkEjGNrbE7Rq0TKBG3M` (Budget) + Invoice data |
| **Sales Funnel** | Inquiries, consultations, bookings, conversion rate | Pipeline stage + `AFX1YsPB7QHBP50Ajs1Q` (Event Type) |
| **Lead Sources** | Lead source performance, conversion by source | Standard `source` field |
| **Revenue by Location** | Revenue by venue/location | `nstR5hDlCQJ6jpsFzxi7` (Event Location) |
| **Booking Trends** | Seasonal patterns, event type distribution | `AFX1YsPB7QHBP50Ajs1Q` (Event Type) + `kvDBYw8fixMftjWdF51g` (Event Date) |
| **Service Mix** | Photo vs video hours, drone usage | `T5nq3eiHUuXM0wFYNNg4`, `nHiHJxfNxRhvUfIu6oD6`, `iQOUEUruaZfPKln4sdKP` |

### ⚠️ Metrics We CANNOT Calculate (Missing Fields)

| Metric Category | Metrics NOT Available | Fields Needed |
|---|---|---|
| **Staff Productivity** | Projects per staff, revenue per staff, hours worked | Staff assignment fields |
| **Operations/Delivery** | Delivery time, on-time %, revision count | Delivery date fields |
| **Client Satisfaction** | Ratings, NPS, sentiment analysis | Review/rating fields |
| **Marketing Performance** | Campaign ROI, impressions, clicks, email metrics | Marketing campaign fields |

**Decision Required:**
1. Create these custom fields in GHL
2. Integrate with external tools (Asana, Monday, Google Forms, etc.)
3. Remove these metrics from dashboard

---

## Part 9: NEXT STEPS

### Immediate Actions:

1. ✅ **Use 11 Documented Fields** - Proceed with import using known custom fields
2. 🔧 **Update GHL API Key** - Get new API token to discover additional fields
3. 📋 **Audit GHL Account** - Log into GHL, manually check what custom fields actually exist
4. 🎯 **Prioritize Metrics** - Decide which missing metrics are most important
5. 🔨 **Create Missing Fields** - Add custom fields in GHL for priority metrics
6. 🔄 **Update Import Script** - Add new fields to sync logic
7. 🧪 **Test Import** - Run historical import with corrected logic
8. 📊 **Verify Dashboard** - Ensure metrics display correctly

---

## Part 10: TESTING DATA EXAMPLES

### Example 1: Lead (NOT Booked)
**Contact:** Michael Obrand
**GHL ID:** 4Yu702qGLPHc17MS9cvl
**Pipeline Stage:** Lead (NOT "Planning")
**Should Import As:** `inquiries` table
**Should NOT Create:** `projects` table record

**Custom Fields:**
```json
{
  "AFX1YsPB7QHBP50Ajs1Q": "event",
  "kvDBYw8fixMftjWdF51g": "2025-11-01",
  "OwkEjGNrbE7Rq0TKBG3M": "4055.79",
  "nHiHJxfNxRhvUfIu6oD6": "10",
  "T5nq3eiHUuXM0wFYNNg4": "0"
}
```

### Example 2: Booked Project
**Contact:** Jane Smith
**Pipeline Stage:** "Planning" ⭐
**Should Import As:** `projects` table
**Should Update:** `clients.lifecycle_stage` to 'client'

---

**Last Updated:** 2025-11-02
**Maintained By:** Analytics Team
**Next Review:** After GHL API key updated
