# GoHighLevel Workflow Documentation for Candid Studios Analytics

**Generated:** 2025-11-22
**Purpose:** Complete mapping of GHL pipelines, stages, and custom fields for analytics integration

---

## 1. Pipeline Structure

### Pipeline 1: SALES (`olVOJoYVKm8BmBy9S7ei`)
**Purpose:** Lead management and consultation scheduling

| Stage ID | Stage Name | Position | Analytics Classification |
|----------|-----------|----------|-------------------------|
| `72db62c2-49da-4b3c-8363-4fd1017a9b2d` | New Leads | 0 | `lead` |
| `81306890-5c62-47ef-9711-aa971baee91b` | No Phone Number | 1 | `lead` |
| `d77b8f27-bfeb-47bc-906c-530d57c56257` | Voicemail Left | 2 | `lead` |
| `e621678a-8331-44dc-b080-118d5ec279fd` | Phone Answered | 3 | `lead` |
| `e9378b30-5873-49e6-8c8f-4245b84e6899` | On Hold | 4 | `lead` |
| `3ef5e08e-5a88-4f1d-9247-04065bd76f34` | Appointment Booked | 5 | `consultation_scheduled` |
| `e7357644-92eb-451b-921e-0235e1a36a3b` | Consultation Over | 6 | `consultation_completed` |
| `fb341044-3ace-4e14-b74e-71a0373578e9` | Consultation Missed | 7 | `consultation_missed` |
| `61fed760-3d6f-48c1-97e5-a3df78a06c8d` | Consultation Rescheduled | 8 | `consultation_rescheduled` |

---

### Pipeline 2: PLANNING (`L2s9gNWdWzCbutNTC4DE`)
**Purpose:** Booked clients - Project planning and timeline management
**CRITICAL:** This is the BOOKING pipeline. Contacts here have signed agreement + Stripe invoice generated.

| Stage ID | Stage Name | Position | Analytics Classification |
|----------|-----------|----------|-------------------------|
| `bad101e4-ff48-4ab8-845a-1660f0c0c7da` | **Booked** | 0 | `booked` |
| `4b38c918-6dd5-41f3-8449-b2c253ed6835` | Assigned | 1 | `booked` |
| `89cdbe39-ab04-483c-86a1-5d8105f25df6` | 12 Months Out | 2 | `booked` |
| `9a24b260-ab1b-479e-8371-cda846baade9` | 3 Months Out | 3 | `booked` |
| `25e276a7-7ebd-4ba3-845f-3b9cb22506a6` | 1 Month Out | 4 | `booked` |
| `02760604-6478-403c-a917-625d8c7a1703` | 1 Week Out | 5 | `booked` |
| `af3ccf1b-0764-40d0-8e17-08128baae769` | On Hold | 6 | `on_hold` |

---

### Pipeline 3: PHOTO EDITING (`7c0c7dVZbpVUsfBxztAb`)
**Purpose:** Photo post-production workflow

| Stage ID | Stage Name | Position | Analytics Classification |
|----------|-----------|----------|-------------------------|
| `2add1f69-af57-401f-b481-ce2e755cb0e8` | Editing Queue | 0 | `editing_queue` |
| `fbf4d9ad-b0e6-4520-9f08-a562ba0e7e29` | Editing In Progress | 1 | `editing_in_progress` |
| `5c06a953-a5a2-4ffe-a909-c71430f17be3` | Quality Assurance | 2 | `qa` |
| `1660c3ee-d810-4de6-8c5d-9b912b6bd71d` | Candid Studios Final Review | 3 | `final_review` |
| `394cf04b-dfa6-4668-b01a-2b9d56fd5533` | Revisions | 4 | `revisions` |
| `a7f3031c-24fb-428b-993a-f45dd25d7f4a` | Delivery to Client | 5 | `delivered` |
| `9594cf41-8762-4a56-b7e8-d85cfd734de8` | Client Requested Adjustments | 6 | `revisions_requested` |
| `06725bc7-edec-49e1-bc8f-945fa2a94b65` | Completed | 7 | `completed` |

---

### Pipeline 4: VIDEO EDITING (`HdUdwCdOxIShUwe7fgZb`)
**Purpose:** Video post-production workflow

| Stage ID | Stage Name | Position | Analytics Classification |
|----------|-----------|----------|-------------------------|
| `b3fc77ea-ecb4-42f3-ae69-16c6740f27cc` | Editing Queue | 0 | `editing_queue` |
| `438b0aaf-b44c-4a75-8c58-a26618057eab` | Editing In Progress | 1 | `editing_in_progress` |
| `7a55948f-adcc-4f59-9b4f-a3de18c8787b` | Quality Assurance | 2 | `qa` |
| `7c37bdd7-89b9-4154-bfe8-e2dc8e572f48` | Candid Studios Final Review | 3 | `final_review` |
| `a75c3764-22c3-4552-8c67-fe52f4a1fe4c` | Revisions | 4 | `revisions` |
| `e4f4d9d7-ddb1-4281-b3c1-01a501842a93` | Delivery to Client | 5 | `delivered` |
| `6c19907b-ed71-471c-bf5c-c0145360b3d2` | Client Requested Adjustments | 6 | `revisions_requested` |
| `7f03e315-7868-4688-9276-5cfddace6dd7` | Completed | 7 | `completed` |

---

### Pipeline 5: ARCHIVED (`KJcBDVCLIV8c7vbkJZnb`)
**Purpose:** Completed and archived items

| Stage ID | Stage Name | Position | Analytics Classification |
|----------|-----------|----------|-------------------------|
| `29b1ff6d-816c-43de-9d6e-653995a17d85` | Archived | 0 | `archived` |
| `07ff7240-a824-4319-8502-2dab8bb41154` | Photo Project Completed | 1 | `completed` |
| `768910b4-88b9-4f8c-a150-222650749331` | Video Project Completed | 2 | `completed` |
| `e3b0095b-361d-4083-a457-e3e20eef57f4` | Application Denied | 3 | `denied` |
| `7d1edbf7-f28b-4dca-a22b-addb6c39b711` | Employee Terminated | 4 | `terminated` |

---

## 2. Business Logic for Client Classification

### LEAD (Not yet booked)
A contact is a **LEAD** if:
- They exist in GHL
- Their opportunity is in the **SALES** pipeline (any stage)
- They have NOT moved to the PLANNING pipeline

### BOOKED CLIENT (Has signed agreement)
A contact becomes a **BOOKED CLIENT** when:
1. They sign the agreement
2. Opportunity moves to **PLANNING** pipeline ("Booked" stage)
3. Stripe invoice is generated and emailed

**Key Pipeline ID:** `L2s9gNWdWzCbutNTC4DE`
**Key Stage ID:** `bad101e4-ff48-4ab8-845a-1660f0c0c7da` (Booked)

### REVENUE TRACKING
Revenue is ONLY recorded when:
- **Actual Stripe payment is received** (not estimated values)
- Refunds are tracked as negative transactions
- Invoice generation does NOT count as revenue

---

## 3. Custom Fields Mapping

### Project Core Fields

| Field ID | Field Name | Data Type | Analytics Mapping |
|----------|-----------|-----------|-------------------|
| `AFX1YsPB7QHBP50Ajs1Q` | Event Type | SINGLE_OPTIONS | `projects.event_type` |
| `kvDBYw8fixMftjWdF51g` | Event Date | DATE | `projects.event_date` |
| `T5nq3eiHUuXM0wFYNNg4` | Photography Hours | SINGLE_OPTIONS | `projects.photo_hours` |
| `nHiHJxfNxRhvUfIu6oD6` | Videography Hours | SINGLE_OPTIONS | `projects.video_hours` |
| `nstR5hDlCQJ6jpsFzxi7` | Project Location | TEXT | `projects.venue_address` |
| `OwkEjGNrbE7Rq0TKBG3M` | Opportunity Value | TEXT | `projects.estimated_value` (NOT revenue!) |
| `00cH1d6lq8m0U8tf3FHg` | Services Interested In | CHECKBOX | `projects.services` |
| `Bz6tmEcB0S0pXupkha84` | Start Time | SINGLE_OPTIONS | `projects.start_time` |
| `NZh0hsK8OaQ1vHrU0Lkq` | Does This Project Have Video? | SINGLE_OPTIONS | `projects.has_video` |
| `xV2dxG35gDY1Vqb00Ql1` | Additional Notes | LARGE_TEXT | `projects.notes` |

### Staff Assignment Fields

| Field ID | Field Name | Data Type | Analytics Mapping |
|----------|-----------|-----------|-------------------|
| `G4eZc8UKyPgGr36nyR50` | Assigned Photographer | TEXT | `staff_assignments.photographer_id` |
| `3wvwfiEBn28TH67xK0R2` | Assigned Videographer | TEXT | `staff_assignments.videographer_id` |
| `NUC0izbVu26XEiriE5Up` | Assigned Photographer (First Name) | TEXT | `staff.photographer_name` |
| `HH0onKM31fhdsh4pnvh3` | Assigned Videographer (First Name) | TEXT | `staff.videographer_name` |
| `as6qzWMAaodZSH2JgUCt` | Project Manager | TEXT | `staff_assignments.project_manager` |

### Delivery/Fulfillment Fields

| Field ID | Field Name | Data Type | Analytics Mapping |
|----------|-----------|-----------|-------------------|
| `bp5oCoPifWXOOcN7Z79F` | Link To Raw Images | TEXT | `deliverables.raw_photos_url` |
| `K3fNomA8tFU3wShooTTh` | Link to RAW Video Content | TEXT | `deliverables.raw_video_url` |
| `epv4xKKDDS1HqbiRz7Wc` | Link to Final Image Gallery | TEXT | `deliverables.final_photos_url` |
| `QjjCsBRRNu0FlD0ocEJk` | Link to Final Video | TEXT | `deliverables.final_video_url` |
| `cGEUu0JUCDJDwJsHsyQa` | Link For Additional Videos | TEXT | `deliverables.additional_videos_url` |

### Feedback Fields

| Field ID | Field Name | Data Type | Analytics Mapping |
|----------|-----------|-----------|-------------------|
| `fIkJwAvbFzQGcLbKTbat` | Review Link Based On Location | TEXT | `reviews.review_link` |
| `CgCTDcu9MCtIIKWlcHZV` | Feedback For Photographers | LARGE_TEXT | `feedback.photographer_feedback` |
| `P7Et5cQwWqWPnFpeY7Wf` | Feedback For Videographers | LARGE_TEXT | `feedback.videographer_feedback` |
| `Moa0uJbJTUs3gi4d8zw1` | Photographers Notes To Editors | LARGE_TEXT | `notes.photographer_to_editor` |

### Partner Information Fields

| Field ID | Field Name | Data Type | Analytics Mapping |
|----------|-----------|-----------|-------------------|
| `WPisKIBj4RYy6LkapuX7` | Partner's First Name | TEXT | `clients.partner_first_name` |
| `7jNpL5BQB3DHJ5mcsFZP` | Partner's Last Name | TEXT | `clients.partner_last_name` |
| `AtOOSx6IrAHhwMps1Ayi` | Partner's Email | TEXT | `clients.partner_email` |
| `iCPDAVCj8RtdRyGNFF12` | Partner's Phone | TEXT | `clients.partner_phone` |

### Marketing Fields

| Field ID | Field Name | Data Type | Analytics Mapping |
|----------|-----------|-----------|-------------------|
| `zPbacyR7OIixOVjHefk5` | Engagement Score | NUMERICAL | `clients.engagement_score` |
| `WIGtBzBlqXEgpAY1D9I9` | Promo Code | TEXT | `inquiries.promo_code` |

---

## 4. MISSING CUSTOM FIELDS (Need to be created in GHL)

### Review/Rating Fields (5 fields)

| Field Name | Data Type | Options | Purpose |
|------------|-----------|---------|---------|
| Overall Project Rating | SINGLE_OPTIONS | 1-5 stars | Track client satisfaction |
| Photographer Rating | SINGLE_OPTIONS | 1-5 stars | Track photographer performance |
| Videographer Rating | SINGLE_OPTIONS | 1-5 stars | Track videographer performance |
| NPS Score | NUMERICAL (0-10) | - | Net Promoter Score |
| Would You Recommend Us? | RADIO | Yes, No | Simple recommendation tracking |

### Delivery Tracking Fields (5 fields)

| Field Name | Data Type | Options | Purpose |
|------------|-----------|---------|---------|
| Actual Photo Delivery Date | DATE | - | Track when photos delivered |
| Actual Video Delivery Date | DATE | - | Track when video delivered |
| Photo Revision Count | NUMERICAL | Min: 0 | Count photo revisions |
| Video Revision Count | NUMERICAL | Min: 0 | Count video revisions |
| Project Delivery Status | SINGLE_OPTIONS | Pending, Raw Photos Delivered, Final Photos Delivered, Video Delivered, Complete, Revisions Requested | Overall delivery tracking |

### Review Tracking Fields (1 field)

| Field Name | Data Type | Purpose |
|------------|-----------|---------|
| Review Submission Date | DATE | When client submitted review |

---

## 5. Data Flow Architecture

```
GHL Contact Created
    |
    v
n8n: "Analytics: GHL New Inquiry Webhook"
    |
    v
Analytics API: POST /api/webhooks/inquiries
    |
    v
Database: clients + inquiries tables
    |
    |--- Client signs agreement & moves to PLANNING pipeline
    |
    v
n8n: "Analytics: GHL Project Booking"
    |
    v
Analytics API: POST /api/webhooks/bookings
    |
    v
Database: projects table (status = 'booked')
    |
    |--- Stripe payment received
    |
    v
n8n: "Analytics: Payment Received"
    |
    v
Analytics API: POST /api/webhooks/payments
    |
    v
Database: revenue_transactions table
```

---

## 6. n8n Workflow Configuration

### Existing Workflows to Configure:

1. **Analytics: GHL New Inquiry Webhook** (`dEKmDvMJYZoBlwPZ`)
   - Trigger: GHL webhook on new contact/lead
   - Action: POST to Analytics API `/api/webhooks/inquiries`

2. **Analytics: GHL Project Booking** (`V7bB25Z2CGGwCacU`)
   - Trigger: GHL webhook when opportunity moves to PLANNING pipeline
   - Condition: Pipeline ID = `L2s9gNWdWzCbutNTC4DE`
   - Action: POST to Analytics API `/api/webhooks/bookings`

3. **Analytics: Payment Received** (`1vEcU4DWf0w8RU3U`)
   - Trigger: Stripe webhook on payment.succeeded
   - Action: POST to Analytics API `/api/webhooks/payments`

### New Workflows Needed:

4. **Analytics: Stripe Refund**
   - Trigger: Stripe webhook on charge.refunded
   - Action: POST to Analytics API `/api/webhooks/refunds`

5. **Analytics: Delivery Update**
   - Trigger: GHL webhook when opportunity moves to editing pipeline stages
   - Action: POST to Analytics API `/api/webhooks/deliveries`

---

## 7. API Key Information

**GHL API:**
- API Key: `pit-6790fa9f-7d36-4838-aaf7-faa825ec5b42`
- Location ID: `GHJ0X5n0UomysnUPNfao`
- Base URL: `https://services.leadconnectorhq.com`
- Version: `2021-07-28`

**n8n (Railway):**
- URL: `https://n8n-production-5eb7.up.railway.app`

---

## 8. Implementation Checklist

- [ ] Create 11 missing custom fields in GHL
- [ ] Update n8n "Analytics: GHL New Inquiry Webhook" workflow
- [ ] Update n8n "Analytics: GHL Project Booking" workflow
- [ ] Update n8n "Analytics: Payment Received" workflow (Stripe integration)
- [ ] Create n8n "Analytics: Stripe Refund" workflow
- [ ] Update Analytics API webhook endpoints
- [ ] Update database schema with new fields
- [ ] Clean existing data in analytics database
- [ ] Re-import all contacts/opportunities correctly
- [ ] Test end-to-end flow
- [ ] Deploy and verify

---

**Last Updated:** 2025-11-22
