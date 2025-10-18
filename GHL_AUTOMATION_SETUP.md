# GoHighLevel Automation Setup - Direct API Integration

## Issue Resolved

The webhook integration is now working! The issue was a **PostgreSQL array literal error** caused by GHL sending empty tags arrays like `[""]`.

### What Was Fixed

**File**: `api/src/Controllers/WebhookController.php`

**Lines 188-195 (receiveProject) and 291-298 (receiveInquiry)**:
```php
// Filter out empty tags to avoid PostgreSQL array literal errors
$tagsArray = $data['tags'] ?? [];
if (is_array($tagsArray)) {
    $tagsArray = array_filter($tagsArray, function($tag) {
        return !empty($tag);
    });
}
$tags = !empty($tagsArray) ? json_encode(array_values($tagsArray)) : null;
```

This fix:
- Filters out empty strings from tags arrays
- Prevents PostgreSQL `malformed array literal: "[\"\"]"` errors
- Allows webhooks to successfully create inquiries and projects

---

## Recommended Architecture: Direct GHL → API

**Skip Make.com entirely** and configure GHL automations to call the API directly.

### Why Direct Integration is Better

1. **Simpler**: Fewer points of failure
2. **Faster**: No intermediary processing
3. **More Reliable**: API designed to handle GHL webhooks natively
4. **Easier Debugging**: Logs show exactly what went wrong

---

## GHL Automation Configuration

### 1. New Inquiry Webhook

**Trigger**: Contact Created (or Form Submitted)

**Action**: Custom Webhook

**URL**: `https://api.candidstudios.net/api/webhooks/inquiries`

**Method**: POST

**Headers**:
```
Content-Type: application/json
```

**Body** (JSON):
```json
{
  "id": "{{contact.id}}",
  "firstName": "{{contact.first_name}}",
  "lastName": "{{contact.last_name}}",
  "email": "{{contact.email}}",
  "phone": "{{contact.phone}}",
  "source": "{{contact.source}}",
  "dateAdded": "{{contact.date_added}}",
  "tags": {{contact.tags}},
  "customField": {
    "AFX1YsPB7QHBP50Ajs1Q": "{{contact.custom_fields.AFX1YsPB7QHBP50Ajs1Q}}",
    "kvDBYw8fixMftjWdF51g": "{{contact.custom_fields.kvDBYw8fixMftjWdF51g}}",
    "OwkEjGNrbE7Rq0TKBG3M": "{{contact.custom_fields.OwkEjGNrbE7Rq0TKBG3M}}",
    "xV2dxG35gDY1Vqb00Ql1": "{{contact.custom_fields.xV2dxG35gDY1Vqb00Ql1}}",
    "nstR5hDlCQJ6jpsFzxi7": "{{contact.custom_fields.nstR5hDlCQJ6jpsFzxi7}}"
  }
}
```

---

### 2. New Booking Webhook

**Trigger**: Pipeline Stage Changed

**Filter**: Pipeline = "Planning"

**Action**: Custom Webhook

**URL**: `https://api.candidstudios.net/api/webhooks/projects`

**Method**: POST

**Headers**:
```
Content-Type: application/json
```

**Body** (JSON):
```json
{
  "id": "{{contact.id}}",
  "firstName": "{{contact.first_name}}",
  "lastName": "{{contact.last_name}}",
  "email": "{{contact.email}}",
  "phone": "{{contact.phone}}",
  "tags": {{contact.tags}},
  "status": "booked",
  "customField": {
    "AFX1YsPB7QHBP50Ajs1Q": "{{contact.custom_fields.AFX1YsPB7QHBP50Ajs1Q}}",
    "kvDBYw8fixMftjWdF51g": "{{contact.custom_fields.kvDBYw8fixMftjWdF51g}}",
    "OwkEjGNrbE7Rq0TKBG3M": "{{contact.custom_fields.OwkEjGNrbE7Rq0TKBG3M}}",
    "nstR5hDlCQJ6jpsFzxi7": "{{contact.custom_fields.nstR5hDlCQJ6jpsFzxi7}}",
    "00cH1d6lq8m0U8tf3FHg": "{{contact.custom_fields.00cH1d6lq8m0U8tf3FHg}}",
    "T5nq3eiHUuXM0wFYNNg4": "{{contact.custom_fields.T5nq3eiHUuXM0wFYNNg4}}",
    "nHiHJxfNxRhvUfIu6oD6": "{{contact.custom_fields.nHiHJxfNxRhvUfIu6oD6}}",
    "iQOUEUruaZfPKln4sdKP": "{{contact.custom_fields.iQOUEUruaZfPKln4sdKP}}",
    "Bz6tmEcB0S0pXupkha84": "{{contact.custom_fields.Bz6tmEcB0S0pXupkha84}}",
    "qpyukeOGutkXczPGJOyK": "{{contact.custom_fields.qpyukeOGutkXczPGJOyK}}",
    "xV2dxG35gDY1Vqb00Ql1": "{{contact.custom_fields.xV2dxG35gDY1Vqb00Ql1}}"
  }
}
```

---

### 3. Payment Received Webhook

**Trigger**: Payment Received

**Action**: Custom Webhook

**URL**: `https://api.candidstudios.net/api/webhooks/revenue`

**Method**: POST

**Headers**:
```
Content-Type: application/json
```

**Body** (JSON):
```json
{
  "contact_id": "{{contact.id}}",
  "amount": "{{payment.amount}}",
  "payment_date": "{{payment.date}}",
  "payment_method": "{{payment.method}}",
  "payment_type": "{{payment.type}}",
  "category": "{{payment.category}}"
}
```

---

## Custom Field Reference

| Field ID | Description |
|----------|-------------|
| `AFX1YsPB7QHBP50Ajs1Q` | Event Type (Wedding, Portrait, etc.) |
| `kvDBYw8fixMftjWdF51g` | Event Date |
| `OwkEjGNrbE7Rq0TKBG3M` | Total Value/Budget |
| `xV2dxG35gDY1Vqb00Ql1` | Project Notes |
| `nstR5hDlCQJ6jpsFzxi7` | Venue Address/Event Location |
| `00cH1d6lq8m0U8tf3FHg` | Services (array) |
| `T5nq3eiHUuXM0wFYNNg4` | Photography Hours |
| `nHiHJxfNxRhvUfIu6oD6` | Videography Hours |
| `iQOUEUruaZfPKln4sdKP` | Drone Services (Yes/No) |
| `Bz6tmEcB0S0pXupkha84` | Event Start Time |
| `qpyukeOGutkXczPGJOyK` | Contact Name |

---

## Testing

### Test Inquiry Webhook

```bash
curl -X POST http://localhost:8000/api/webhooks/inquiries \
  -H "Content-Type: application/json" \
  -d '{
    "id": "test_contact_123",
    "firstName": "Test",
    "lastName": "Client",
    "email": "test@example.com",
    "phone": "+15551234567",
    "source": "Website Form",
    "dateAdded": "2025-10-13T14:00:00Z",
    "tags": ["lead", "website"],
    "customField": {
      "AFX1YsPB7QHBP50Ajs1Q": "Wedding",
      "kvDBYw8fixMftjWdF51g": "2025-12-15",
      "OwkEjGNrbE7Rq0TKBG3M": "5000",
      "xV2dxG35gDY1Vqb00Ql1": "Test inquiry notes",
      "nstR5hDlCQJ6jpsFzxi7": "Austin, TX"
    }
  }'
```

**Expected Response**:
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

## What About Make.com?

### Option 1: Delete Make.com Scenarios (Recommended)

The three scenarios created can be deleted:
1. Analytics: GHL New Inquiry Webhook (ID: 3220597)
2. Analytics: GHL Project Booking
3. Analytics: Payment received

**Why**: Direct API integration is simpler and more reliable.

### Option 2: Keep Make.com for Advanced Processing

If you want Make.com to do additional processing (enrichment, notifications, etc.), the scenarios are fixed in:
- `/tmp/fixed-inquiry-with-openai.json`

The fixes include:
- OpenAI module now has `{{1}}` instead of literal "1"
- HTTP module now has `{{3.choices[].message.content}}` instead of `{{3.result}}`
- Temperature set to 0.1 for consistent parsing
- Model changed to `gpt-4o-mini`

---

## Monitoring

### Check API Logs

```bash
docker logs -f candid-analytics-api
```

### Check Recent Inquiries

```bash
curl http://localhost:8000/api/inquiries
```

### Check Recent Projects

```bash
curl http://localhost:8000/api/projects
```

---

## Next Steps

1. **Update GHL Automations**: Change webhook URLs to point directly to API endpoints
2. **Test Each Automation**: Submit test forms/data through GHL
3. **Monitor Logs**: Watch API logs for successful webhook processing
4. **Delete Make.com Scenarios** (optional): If direct integration works well

---

## Troubleshooting

### Issue: Empty Tags Error

**Symptom**: `malformed array literal: "[\"\"]"`

**Status**: **FIXED** ✅

The API now filters empty tags before storing in database.

### Issue: Missing Custom Fields

**Symptom**: Custom field values are null in database

**Solution**: Verify GHL custom field IDs match exactly (case-sensitive)

### Issue: 400 Invalid Payload

**Symptom**: API returns "Invalid payload"

**Solution**:
1. Check JSON syntax in webhook body
2. Verify Content-Type header is `application/json`
3. Check API logs for specific parsing errors

### Issue: 500 Server Error

**Symptom**: API returns "Server error processing webhook"

**Solution**:
1. Check API logs: `docker logs candid-analytics-api`
2. Verify database is running: `docker ps`
3. Check database connection in `.env` file
