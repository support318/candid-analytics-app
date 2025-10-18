# Session Update - October 13, 2025 - ENV DEBUG

## 🔍 CURRENT ISSUE: GHL MCP Server Not Receiving Environment Variables

**Date:** 2025-10-13
**Status:** Debugging environment variable passing from Claude Code to MCP server

---

## ⚠️ PROBLEM IDENTIFIED

### Issue
GHL MCP server is receiving **401 Unauthorized** errors when making API calls, despite having correct credentials in the Claude Code config file.

### Root Cause Analysis

1. **Config File Check** ✅
   - Location: `/mnt/c/Users/ryanm/AppData/Roaming/Claude/claude_desktop_config.json`
   - Lines 21-28 contain correct GHL credentials:
     ```json
     "ghl-mcp-server": {
       "command": "node",
       "args": ["C:\\code\\ghl-mcp-server\\build\\index.js"],
       "env": {
         "GHL_API_KEY": "pit-3e3598f9-8042-4e33-9e29-8d81d8ed6fdb",
         "GHL_LOCATION_ID": "GHJ0X5n0UomysnUPNfao"
       }
     }
     ```

2. **Direct API Test** ✅
   - Tested API credentials directly with curl
   - Result: **Credentials are VALID**
   - API returns data when using `locationId` as query parameter

3. **Environment Variable Test** ❌
   - Ran: `node -e "console.log('API Key:', process.env.GHL_API_KEY);"`
   - Result: Both `GHL_API_KEY` and `GHL_LOCATION_ID` are **undefined**
   - **This proves Claude Code is NOT passing env vars to the MCP server process**

4. **GHL MCP Server Code** ✅
   - Source code correctly reads from `process.env.GHL_API_KEY` (line 33)
   - Source code correctly reads from `process.env.GHL_LOCATION_ID` (line 34)
   - No hardcoded values, no build-time issues

---

## ✅ FIX APPLIED

### Added Debug Logging to GHL MCP Server

**File Modified:** `/mnt/c/code/ghl-mcp-server/src/index.ts` (lines 22-28)

**Changes:**
```typescript
constructor() {
  // Debug: Log environment variables on startup
  console.error('=== GHL MCP Server Debug ===');
  console.error('GHL_API_KEY:', process.env.GHL_API_KEY ? `${process.env.GHL_API_KEY.substring(0, 10)}...` : 'MISSING');
  console.error('GHL_LOCATION_ID:', process.env.GHL_LOCATION_ID || 'MISSING');
  console.error('GHL_BASE_URL:', process.env.GHL_BASE_URL || 'DEFAULT');
  console.error('============================');

  // ... rest of constructor
}
```

**Build Status:** ✅ **REBUILT** - Server compiled with debug logging

---

## 🔄 NEXT STEPS (AFTER RESTART)

### Step 1: Restart Claude Code
**REQUIRED:** Claude Code must restart to:
1. Reload MCP server processes
2. Re-read the config file
3. Pass environment variables to child processes

### Step 2: Check Debug Logs

After restart, check MCP server logs in one of these locations:
- `%APPDATA%\Claude\logs\` (Windows)
- `%LOCALAPPDATA%\Claude\logs\` (Windows)
- Look for file named `mcp-server-ghl-mcp-server.log` or similar

**Expected Output:**
```
=== GHL MCP Server Debug ===
GHL_API_KEY: pit-3e3598...
GHL_LOCATION_ID: GHJ0X5n0UomysnUPNfao
GHL_BASE_URL: DEFAULT
============================
GoHighLevel MCP server running on stdio
```

**If Still Showing "MISSING":**
This indicates a bug in Claude Code's MCP server environment variable passing mechanism.

### Step 3: Test GHL MCP Connection

Once restarted, test with:
```
mcp__ghl__search_contacts with query="Michael"
```

**Expected Result:** Should return contacts without 401 error

### Step 4: Configure GHL Automations

Once connection works, use GHL MCP tools to set up three automations:
1. New Inquiry Webhook
2. Project Booking Webhook
3. Payment Received Webhook

---

## 🔑 CREDENTIALS REFERENCE

### GoHighLevel (VERIFIED WORKING)
- **API Token:** `pit-3e3598f9-8042-4e33-9e29-8d81d8ed6fdb` ✅
- **Location ID:** `GHJ0X5n0UomysnUPNfao` ✅
- **Base URL:** `https://services.leadconnectorhq.com`
- **Version Header:** `2021-07-28`
- **Test Contact:** ryan mayiras (`7B5tHbNYqnyn98un4rf4`)

### Make.com
- **API Key:** `a36a86ec-9640-47f9-8dda-e63040ae15ce`
- **Organization ID:** `2863954`
- **Team ID:** `431106`
- **Zone:** `us2`

### Analytics API
- **Production:** `https://api.candidstudios.net`
- **Local:** `http://localhost:8000`
- **Admin:** `admin` / `password`

---

## 📊 CURRENT STATUS

### ✅ Completed
1. Identified GHL MCP server env var issue
2. Verified API credentials work directly
3. Added debug logging to MCP server
4. Rebuilt MCP server with debug code
5. Confirmed MCP server source code is correct

### ⏳ Pending (Blocked by Restart)
1. [ ] Restart Claude Code
2. [ ] Verify debug logs show env vars
3. [ ] Test GHL MCP connection
4. [ ] Configure three GHL automations
5. [ ] Activate Make.com inquiry scenario
6. [ ] Test end-to-end webhook flow

---

## 🔧 WEBHOOK URLS (READY TO USE)

Once GHL MCP server is working, configure these webhooks in GHL automations:

### 1. New Inquiry Webhook
- **URL:** `https://hook.us2.make.com/kqatae9evr1u9yus2h5bj4gj5bjsvt98`
- **Scenario ID:** 3220597 (inactive, needs activation)
- **Trigger:** Contact Created
- **Forwards to:** `https://api.candidstudios.net/api/webhooks/inquiries`

### 2. Project Booking Webhook
- **URL:** `https://hook.us2.make.com/y2xbm2odcop42xuwxry9cvi8oo89b4zw`
- **Scenario ID:** 3220708 (active)
- **Trigger:** Pipeline stage changed to "Planning"
- **Forwards to:** `https://api.candidstudios.net/api/webhooks/projects`

### 3. Payment Received Webhook
- **URL:** `https://hook.us2.make.com/dv66lh7l72deqxbmbb13zeraz3clxeu6`
- **Scenario ID:** 3220714 (active)
- **Trigger:** Payment received
- **Forwards to:** `https://api.candidstudios.net/api/webhooks/revenue`

---

## 🧪 API VERIFICATION (CURL TESTS)

### Test That Worked ✅
```bash
curl -X GET "https://services.leadconnectorhq.com/contacts/?locationId=GHJ0X5n0UomysnUPNfao&limit=1" \
  -H "Authorization: Bearer pit-3e3598f9-8042-4e33-9e29-8d81d8ed6fdb" \
  -H "Version: 2021-07-28" \
  -H "Accept: application/json"
```

**Result:** Returned ryan mayiras contact successfully

### Tests That Failed ❌
```bash
# Without locationId parameter - Returns 403
curl -X GET "https://services.leadconnectorhq.com/contacts/" \
  -H "Authorization: Bearer pit-3e3598f9-8042-4e33-9e29-8d81d8ed6fdb"
```

**Conclusion:** This API token requires `locationId` parameter (location-scoped token, not agency token)

---

## 📝 ARCHITECTURE NOTES

### GHL MCP Server Flow
```
Claude Code Process
  ↓ (spawns with env vars from config)
Node.js Process (ghl-mcp-server)
  ↓ (reads process.env.GHL_API_KEY)
GHLClient Class
  ↓ (makes axios requests)
GoHighLevel API
```

**Problem Identified:** Environment variables not making it from step 1 → step 2

### Expected Behavior
When Claude Code spawns the MCP server process, it should:
1. Read `claude_desktop_config.json`
2. Extract `env` object from server config
3. Set those variables in the child process environment
4. Spawn `node C:\code\ghl-mcp-server\build\index.js`

### Actual Behavior
The child process receives an empty environment (or doesn't receive GHL_* vars)

---

## 🐛 POSSIBLE CAUSES

### 1. Claude Code Bug
Claude Code may not be properly passing environment variables to Windows-spawned MCP servers.

### 2. Windows Path Issues
The backslash paths in the config might be causing issues with env var passing.

### 3. Node Version Issues
Different node versions might handle environment variables differently.

### 4. Permissions Issues
Windows permissions might prevent environment variable inheritance.

---

## 🔄 RESUME COMMAND

**After restart, run:**
```
Continue with Candid Analytics - read SESSION_UPDATE_2025-10-13_ENV_DEBUG.md
```

**First test to run:**
```
mcp__ghl__search_contacts with query="ryan"
```

**If still failing, request MCP server logs from:**
```
%APPDATA%\Claude\logs\
```

---

## 📂 KEY FILES

### Configuration
- **Claude Config:** `/mnt/c/Users/ryanm/AppData/Roaming/Claude/claude_desktop_config.json`

### GHL MCP Server
- **Source:** `/mnt/c/code/ghl-mcp-server/src/index.ts` (modified)
- **Build:** `/mnt/c/code/ghl-mcp-server/build/index.js` (rebuilt)
- **Client:** `/mnt/c/code/ghl-mcp-server/build/ghl-client.js`

### Documentation
- **Previous Session:** `/mnt/c/code/candid-analytics-app/SESSION_UPDATE_2025-10-13_FINAL.md`
- **Setup Notes:** `/mnt/c/code/candid-analytics-app/SESSION_CONTINUATION_NOTES.md`
- **GHL Fields:** `/mnt/c/code/candid-analytics-app/GHL_FIELD_MAPPING.md`

### API
- **Webhook Controller:** `/mnt/c/code/candid-analytics-app/api/src/Controllers/WebhookController.php`
- **Docker Container:** `candid-analytics-api` (running on port 8000)

---

## 🎯 SUCCESS CRITERIA

### Environment Variable Fix
- [ ] Debug logs show `GHL_API_KEY: pit-3e3598...`
- [ ] Debug logs show `GHL_LOCATION_ID: GHJ0X5n0UomysnUPNfao`
- [ ] GHL MCP tools work without 401 errors

### Automation Setup
- [ ] Three GHL automations configured with webhook URLs
- [ ] Make.com inquiry scenario activated
- [ ] End-to-end test successful

---

## 💡 WORKAROUND (IF ENV VARS STILL FAIL)

If Claude Code continues to fail passing environment variables, we can:

1. **Hardcode credentials temporarily** in `/mnt/c/code/ghl-mcp-server/src/index.ts`:
   ```typescript
   this.ghlClient = new GHLClient({
     apiKey: process.env.GHL_API_KEY || 'pit-3e3598f9-8042-4e33-9e29-8d81d8ed6fdb',
     locationId: process.env.GHL_LOCATION_ID || 'GHJ0X5n0UomysnUPNfao',
     baseUrl: 'https://services.leadconnectorhq.com',
   });
   ```

2. **Use .env file** with dotenv package

3. **Create wrapper script** that sets env vars before calling node

4. **Manual setup** of GHL automations via UI

---

**RESTART CLAUDE CODE NOW TO TEST DEBUG LOGGING**
