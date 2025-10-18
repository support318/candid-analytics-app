# URGENT: Make.com HTTP Module Fix Required

## Problem
The HTTP modules in all three Make.com scenarios are sending `{{1}}` which Make interprets as just the module ID (the number 1), not the webhook data.

This causes the API to receive: `1` (just an integer)
Instead of: `{"id": "...", "firstName": "...", "customField": {...}}` (the full webhook payload)

## Error in Logs
```
TypeError: CandidAnalytics\Controllers\WebhookController::parsePayload(): Return value must be of type ?array, int returned
```

This means Make is literally sending the integer `1` instead of the webhook data.

## Solution

You must manually update the HTTP module in each scenario. **NOTE: `toJSON()` doesn't exist in Make.com!**

### Option A: Use "Application/json" Body Type (EASIEST)

1. Go to: https://us2.make.com/scenarios/3220597
2. Click on the **HTTP - Make a request** module (module 2)
3. Change **Body Type** from "Raw" to **"Application/json"**
4. Make.com will show you a form to map individual fields
5. Map all fields from module 1 (webhook) to the form fields
6. Save

### Option B: Construct JSON manually (if Option A doesn't work)

1. Go to: https://us2.make.com/scenarios/3220597
2. Click on the **HTTP - Make a request** module (module 2)
3. Keep **Body Type** as "Raw"
4. Keep **Content-Type** as "application/json"
5. In the **Request content** field, paste this:

```json
{
    "id": "{{1.id}}",
    "firstName": "{{1.firstName}}",
    "lastName": "{{1.lastName}}",
    "email": "{{1.email}}",
    "phone": "{{1.phone}}",
    "source": "{{1.source}}",
    "dateAdded": "{{1.dateAdded}}",
    "type": "{{1.type}}",
    "tags": {{ifempty(1.tags; [])}},
    "customField": {
        "AFX1YsPB7QHBP50Ajs1Q": "{{1.customField.AFX1YsPB7QHBP50Ajs1Q}}",
        "kvDBYw8fixMftjWdF51g": "{{1.customField.kvDBYw8fixMftjWdF51g}}",
        "OwkEjGNrbE7Rq0TKBG3M": "{{1.customField.OwkEjGNrbE7Rq0TKBG3M}}",
        "xV2dxG35gDY1Vqb00Ql1": "{{1.customField.xV2dxG35gDY1Vqb00Ql1}}",
        "nstR5hDlCQJ6jpsFzxi7": "{{1.customField.nstR5hDlCQJ6jpsFzxi7}}"
    }
}
```

6. Save the scenario

### Option C: Pass Everything (SIMPLEST IF IT WORKS)

1. In the HTTP module, keep Body Type as "Raw"
2. Click in the Request content field
3. In the mapping panel, expand module 1 (Custom Webhook)
4. Look for a field called "Body" or "__IMTCONN__" or similar
5. Click on that entire field to insert it (it should show something like `{{1.__IMTCONN__}}`)
6. This passes the raw webhook body directly through

### Scenario 2: Analytics: GHL Project Booking (ID: 3220708)
Follow the same steps as Scenario 1 (Option A, B, or C above)

### Scenario 3: Analytics: Payment received (ID: 3220714)
Follow the same steps as Scenario 1 (Option A, B, or C above)

## Why {{1}} Alone Doesn't Work

When you use `{{1}}` in the data field without proper context, Make.com doesn't know you want the entire data structure serialized. It might just send the module reference itself.

You need to either:
- Use proper field mapping (Option A)
- Manually construct the JSON with explicit field references (Option B)
- Find and use the special "Body" field that contains the raw webhook data (Option C)

## Quick Summary

**Problem**: `{{1}}` sends the number "1" instead of the webhook data

**Solution**: Use one of these approaches:
1. Change Body Type to "Application/json" and map fields in the UI
2. Manually construct JSON with `{{1.id}}`, `{{1.firstName}}`, etc.
3. Use the raw body field from the webhook (usually `{{1.__IMTCONN__}}` or similar)

## Testing After Fix

After updating the scenarios, test by:

1. Open Make scenario in "Run once" mode
2. Trigger from GHL or use "Run this module only" to send test data
3. Check the logs:
   ```bash
   docker logs candid-analytics-api --tail 50 | grep "Received webhook payload"
   ```
4. You should see actual contact data, not just `"body": "1"`

## Expected Success Output

After the fix, logs should show:
```
Received webhook payload: {"body": "{\"id\":\"4Yu702qGLPHc17MS9cvl\",\"firstName\":\"Michael\",..."}
Parsed webhook payload successfully: {"keys": ["id","firstName","lastName","customField",...]}
```

Not:
```
Payload is not an array: {"type": "integer", "value": 1}
```
