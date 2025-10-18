# GoHighLevel to Analytics Database Field Mapping

## Real Contact: Michael Obrand (4Yu702qGLPHc17MS9cvl)

### Contact Status: **LEAD** (Not booked, no payments made)
- Type: `lead`
- Tags: `["website form submission lead", "event", "initial consultation booked"]`
- Date Added: 2025-10-10

---

## Standard GHL Contact Fields

| GHL Field | Value (Michael Obrand) | Analytics DB Field | Notes |
|-----------|------------------------|-------------------|-------|
| `id` | `4Yu702qGLPHc17MS9cvl` | `clients.ghl_contact_id` | ✅ Primary identifier |
| `firstName` | `Michael` | `clients.first_name` | ✅ Correct |
| `lastName` | `Obrand` | `clients.last_name` | ✅ Correct |
| `email` | `mike@kidnection.co` | `clients.email` | ✅ Correct |
| `phone` | `+13058908996` | `clients.phone` | ✅ Correct |
| `source` | `website inquiry form` | `clients.lead_source` | ✅ Correct |
| `type` | `lead` | `clients.lifecycle_stage` | ✅ Maps to lifecycle_stage |
| `tags` | `["website form submission lead", "event", "initial consultation booked"]` | `clients.tags` | ✅ Array field |
| `dateAdded` | `2025-10-10T16:26:19.436Z` | `clients.created_at` | ✅ ISO timestamp |

---

## Custom Fields (Most Important)

Michael Obrand's actual custom fields from GHL:

| Custom Field ID | Field Name (Inferred) | Value | Type | Analytics Mapping |
|-----------------|----------------------|-------|------|-------------------|
| `00cH1d6lq8m0U8tf3FHg` | **Services Interested In** | `["Videography", "Video Editing"]` | Array | `projects.metadata` |
| `AFX1YsPB7QHBP50Ajs1Q` | **Event Type** | `event` | String | `projects.event_type` |
| `Bz6tmEcB0S0pXupkha84` | **Event Start Time** | `10:00 AM` | String | `projects.metadata.event_time` |
| `T5nq3eiHUuXM0wFYNNg4` | **Photography Hours** | `0` | Number | `projects.metadata.photo_hours` |
| `nHiHJxfNxRhvUfIu6oD6` | **Videography Hours** | `10` | Number | `projects.metadata.video_hours` |
| `iQOUEUruaZfPKln4sdKP` | **Drone Services** | `No` | String | `projects.metadata.drone_services` |
| `kvDBYw8fixMftjWdF51g` | **Event Date** | `2025-11-01` | Date | `projects.event_date` |
| `nstR5hDlCQJ6jpsFzxi7` | **Event Location** | `7895 N University Dr suite 501, Parkland, FL, 33076...` | String | `projects.venue_address` |
| `qpyukeOGutkXczPGJOyK` | **Contact Name** | `Krystal Mayo` | String | `projects.metadata.contact_name` |
| `OwkEjGNrbE7Rq0TKBG3M` | **Estimated Budget/Value** | `4055.79` | Number | `projects.total_revenue` (estimate) |
| `xV2dxG35gDY1Vqb00Ql1` | **Project Description/Notes** | "I'm reaching out on behalf of KIDnection..." | Text | `projects.notes` |

---

## Key Insights

### What We Know About Michael Obrand:

1. **He is a LEAD, not a booking**
   - Status: `lead`
   - He has NOT booked yet
   - He has NOT made any payments
   - Tag shows "initial consultation booked" but not "booked"

2. **His Project Details:**
   - Training videos for KIDnection (early childhood center)
   - Event Type: `event` (corporate/training)
   - Event Date: November 1, 2025
   - Location: Parkland, FL
   - Services: Videography + Video Editing only (0 photo hours, 10 video hours)
   - Estimated Value: $4,055.79
   - No drone services

3. **Contact Information:**
   - Email: mike@kidnection.co
   - Phone: +13058908996
   - Primary Contact: Krystal Mayo
   - Address: 7895 N University Dr suite 501, Parkland, FL

---

## Correct Webhook Field Mapping

### For `/api/webhooks/inquiries` (New Leads)

```php
$inquiryData = [
    'client_id' => $clientId,
    'inquiry_date' => $data['dateAdded'] ?? date('Y-m-d'),
    'source' => $data['source'] ?? 'unknown',
    'event_type' => $data['customFields']['AFX1YsPB7QHBP50Ajs1Q'] ?? null, // Event Type field
    'event_date' => $data['customFields']['kvDBYw8fixMftjWdF51g'] ?? null, // Event Date field
    'budget' => $data['customFields']['OwkEjGNrbE7Rq0TKBG3M'] ?? null, // Budget/Value field
    'status' => 'new',
    'notes' => $data['customFields']['xV2dxG35gDY1Vqb00Ql1'] ?? null // Description field
];
```

### For `/api/webhooks/projects` (When They Book)

```php
$projectData = [
    'client_id' => $clientId,
    'project_name' => $data['firstName'] . ' ' . $data['lastName'] . ' - ' . ($customFields['AFX1YsPB7QHBP50Ajs1Q'] ?? 'Project'),
    'booking_date' => date('Y-m-d'), // Today's date when webhook fires
    'event_date' => $customFields['kvDBYw8fixMftjWdF51g'] ?? date('Y-m-d'),
    'event_type' => $customFields['AFX1YsPB7QHBP50Ajs1Q'] ?? 'other',
    'venue_address' => $customFields['nstR5hDlCQJ6jpsFzxi7'] ?? null,
    'status' => 'booked',
    'total_revenue' => $customFields['OwkEjGNrbE7Rq0TKBG3M'] ?? 0,
    'metadata' => json_encode([
        'services' => $customFields['00cH1d6lq8m0U8tf3FHg'] ?? [],
        'photo_hours' => $customFields['T5nq3eiHUuXM0wFYNNg4'] ?? 0,
        'video_hours' => $customFields['nHiHJxfNxRhvUfIu6oD6'] ?? 0,
        'drone_services' => $customFields['iQOUEUruaZfPKln4sdKP'] ?? 'No',
        'event_time' => $customFields['Bz6tmEcB0S0pXupkha84'] ?? null,
        'contact_name' => $customFields['qpyukeOGutkXczPGJOyK'] ?? null
    ]),
    'notes' => $customFields['xV2dxG35gDY1Vqb00Ql1'] ?? null
];
```

### For `/api/webhooks/revenue` (Payment Received)

```php
// Only create revenue when actual payment is recorded in GHL
// NOT when they're just a lead with an estimated value
$revenueData = [
    'project_id' => $projectId,
    'client_id' => $clientId,
    'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
    'amount' => $data['amount'], // Actual payment amount, not estimate
    'payment_method' => $data['payment_method'] ?? 'other',
    'payment_type' => $data['payment_type'] ?? 'deposit', // deposit, partial, final
    'status' => 'completed'
];
```

---

## The Problem With Our Previous Approach

### What I Did Wrong:
1. ❌ Created fake booking data for Michael who is just a lead
2. ❌ Created fake payment records ($1,250 deposit + $1,250 final)
3. ❌ Set his status to "booked" when he's actually "lead"
4. ❌ Used hardcoded field names instead of custom field IDs
5. ❌ Assumed payment data in webhook when none exists

### What Should Have Happened:
1. ✅ Create only an INQUIRY record (he's a lead, not booked)
2. ✅ Store estimated value in inquiry.budget, NOT in revenue
3. ✅ Set status to "new" or "contacted", NOT "booked"
4. ✅ Wait for actual booking webhook to create project
5. ✅ Wait for actual payment webhook to create revenue

---

## Next Steps

1. **Map Custom Field IDs** - Need to get field names from GHL to know what each ID represents
2. **Update Webhook Controller** - Use correct field mappings
3. **Create Inquiry Webhook** - For new leads like Michael
4. **Only Create Projects on Booking** - Not for every lead
5. **Only Create Revenue on Payment** - Not for estimated values

---

**Last Updated:** 2025-10-13
**Contact Used for Analysis:** Michael Obrand (4Yu702qGLPHc17MS9cvl)
