# Current Status - GHL Data Import Project

**Date:** 2025-10-16
**Status:** Planning Phase - Analyzing Workflow

---

## What We Just Completed

1. ✅ Fixed all dashboard white screen errors (Revenue, Sales Funnel, Operations, Satisfaction, Marketing, Staff, AI Insights)
2. ✅ Fixed PostgreSQL string-to-number conversion issues across 30+ instances
3. ✅ Successfully imported 113 contacts from GHL (but data classification is incorrect)

---

## The Core Problem

**User's Critical Requirement:**
> "Only contacts with opportunities that have entered the planning phase and that have signed their agreement have booked."

**Current Issue:**
- Import script incorrectly marks contacts as "booked" based on tags
- Should be based on: **Opportunity in "Planning" pipeline stage + Signed Agreement**

**Database Current State:**
- 113 contacts total
- 107 marked as "lead" (incorrect classification)
- 7 marked as "customer" (incorrect classification)
- 88 inquiries created
- 3 projects (sample data, not real)
- 1 revenue record (sample data, not real)

---

## What User Just Requested

**User said:** "NO please use the go high level mcp server and our make.com server to analyze how our company workflow works and operates first so we can implement everything correctly"

**Intent:** User wants me to:
1. Understand their ACTUAL workflow by analyzing GHL pipelines and stages
2. Understand how Make.com automation is configured
3. Map out their entire lead → client journey
4. THEN implement the import correctly based on their real process

---

## Next Steps (When You Resume)

### 1. Analyze GHL Workflow Structure
- Get all pipelines and their stages from GHL
- Identify which stage is "Planning"
- Understand what "signed agreement" means in their system
- Map out the complete opportunity lifecycle

### 2. Analyze Make.com Automation
- Check what workflows are configured
- Understand what triggers move contacts between stages
- Identify when revenue/payments are recorded

### 3. Create Workflow Documentation
- Document the complete lead → client journey
- Identify key milestones and status changes
- Map GHL stages to analytics database lifecycle stages

### 4. Implement Import Logic
- Only AFTER understanding the workflow
- Classify contacts based on actual pipeline stage
- Create projects only for truly booked clients
- Import only real revenue, not estimates

---

## GHL API Credentials

**API Key:** `pit-4a0c3927-1650-44dd-b63d-2f65d81f84c3`
**Location ID:** `GHJ0X5n0UomysnUPNfao`
**Base URL:** `https://services.leadconnectorhq.com`
**API Version:** `2021-07-28`

---

## Key GHL Custom Field IDs

From `/mnt/c/code/candid-analytics-app/GHL_FIELD_MAPPING.md`:

| Field ID | Field Name | Maps To |
|----------|-----------|---------|
| `AFX1YsPB7QHBP50Ajs1Q` | Event Type | `projects.event_type` |
| `kvDBYw8fixMftjWdF51g` | Event Date | `projects.event_date` |
| `OwkEjGNrbE7Rq0TKBG3M` | Estimated Budget/Value | `inquiries.budget` (NOT revenue!) |
| `00cH1d6lq8m0U8tf3FHg` | Services | `projects.metadata.services` |
| `T5nq3eiHUuXM0wFYNNg4` | Photo Hours | `projects.metadata.photo_hours` |
| `nHiHJxfNxRhvUfIu6oD6` | Video Hours | `projects.metadata.video_hours` |
| `nstR5hDlCQJ6jpsFzxi7` | Event Location | `projects.venue_address` |
| `xV2dxG35gDY1Vqb00Ql1` | Notes | `projects.notes` |

---

## Important Files

### Database Schema
- `/mnt/c/code/candid-analytics-app/database/00-essential-schema.sql` - Main schema with clients, projects, revenue, inquiries tables

### Import Scripts
- `/mnt/c/code/candid-analytics-app/api/import-ghl.php` - Current import (INCORRECT - needs rewrite)
- `/mnt/c/code/candid-analytics-app/scripts/import-ghl-data.php` - Alternative import script

### Documentation
- `/mnt/c/code/candid-analytics-app/GHL_FIELD_MAPPING.md` - Custom field IDs and mappings
- `/mnt/c/code/candid-analytics-app/GHL_DATA_IMPORT_GUIDE.md` - User guide (needs updating)

---

## MCP Server Issues

**GHL MCP Server:** Getting 401 authentication errors
**n8n MCP Server:** Connection refused (not running?)

**Workaround:** Use direct curl commands with API credentials provided above

---

## Key API Endpoints to Query

1. **Get Pipelines & Stages:**
   ```
   GET /opportunities/pipelines?locationId=GHJ0X5n0UomysnUPNfao
   ```

2. **Get Opportunities:**
   ```
   GET /opportunities/search?location_id=GHJ0X5n0UomysnUPNfao&limit=100
   ```

3. **Get Contacts:**
   ```
   GET /contacts/?locationId=GHJ0X5n0UomysnUPNfao&limit=100
   ```

4. **Get Payments/Invoices:**
   ```
   Need to research this endpoint
   ```

---

## User's Business Logic (To Verify)

**LEAD:**
- Contact exists in GHL
- May have inquiry/consultation
- Has NOT reached "Planning" pipeline stage
- Has NOT signed agreement

**BOOKED CLIENT:**
- Contact exists in GHL
- Has opportunity in "Planning" pipeline stage
- Has signed agreement (need to identify this field)
- Should have project record in analytics DB

**REVENUE:**
- ONLY created when actual payment received
- NOT from estimated values
- Should match real payments in GHL

---

## When You Resume

1. **FIRST:** Query GHL to get pipelines and understand their workflow
2. **SECOND:** Map out their complete lead journey stages
3. **THIRD:** Create workflow documentation for user to confirm
4. **FOURTH:** Rewrite import script based on confirmed workflow
5. **FIFTH:** Clean and re-import data correctly

**DO NOT:** Import any data until workflow is understood and confirmed

---

## User Mood/Context

User was frustrated that I was making assumptions about their workflow instead of analyzing their actual GHL setup. They want me to understand their REAL process first, then implement accordingly.

**User's exact words:** "I dont think you planned this out completely" and "please use the go high level mcp server and our make.com server to analyze how our company workflow works and operates first"

---

## Command to Resume Analysis

```bash
# Get pipelines and stages
curl -X GET "https://services.leadconnectorhq.com/opportunities/pipelines?locationId=GHJ0X5n0UomysnUPNfao" \
  -H "Authorization: Bearer pit-4a0c3927-1650-44dd-b63d-2f65d81f84c3" \
  -H "Version: 2021-07-28" \
  -H "Content-Type: application/json"

# Get sample opportunities to see structure
curl -X GET "https://services.leadconnectorhq.com/opportunities/search?location_id=GHJ0X5n0UomysnUPNfao&limit=20" \
  -H "Authorization: Bearer pit-4a0c3927-1650-44dd-b63d-2f65d81f84c3" \
  -H "Version: 2021-07-28" \
  -H "Content-Type: application/json"
```

---

**REMEMBER:** User wants workflow analysis FIRST, implementation SECOND. Do not make assumptions.