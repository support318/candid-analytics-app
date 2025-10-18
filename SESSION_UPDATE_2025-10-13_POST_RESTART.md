# Session Update - October 13, 2025 - Post-Restart Debug

**Date:** 2025-10-13
**Time:** ~22:38 (after second restart)
**Status:** Waiting for restart to test debug logging

---

## 🔄 CURRENT SITUATION

### First Restart (22:35)
- ✅ Restarted Claude Code
- ❌ Still getting 401 errors from GHL MCP server
- ❌ Debug logs showed NO debug output from our console.error statements
- 🔍 **Discovery**: Old build was running (logs from Oct 9, no recent startup)

### Actions Taken
1. ✅ Rebuilt GHL MCP server: `npm run build` at `/mnt/c/code/ghl-mcp-server`
2. ✅ Verified debug code exists in build: `grep -n "GHL MCP Server Debug" build/index.js` shows line 17
3. ⏳ **NEXT STEP: Second restart required to load new build**

---

## 🎯 WHAT TO DO AFTER NEXT RESTART

### Step 1: Check Debug Logs
Run this command immediately after restart:
```bash
tail -50 /mnt/c/Users/ryanm/AppData/Roaming/Claude/logs/mcp-server-ghl-mcp-server.log
```

### Step 2: Look for Debug Output
**Expected output (if env vars working):**
```
=== GHL MCP Server Debug ===
GHL_API_KEY: pit-3e3598...
GHL_LOCATION_ID: GHJ0X5n0UomysnUPNfao
GHL_BASE_URL: DEFAULT
============================
GoHighLevel MCP server running on stdio
```

**If still showing "MISSING":**
```
=== GHL MCP Server Debug ===
GHL_API_KEY: MISSING
GHL_LOCATION_ID: MISSING
GHL_BASE_URL: DEFAULT
============================
GoHighLevel MCP server running on stdio
```

### Step 3: Test GHL Connection
```
mcp__ghl__search_contacts with query="ryan"
```

**Expected Results:**
- ✅ If env vars working: Returns contact data
- ❌ If env vars missing: 401 error continues

---

## 🔧 DEBUG CODE ADDED

### Location
`/mnt/c/code/ghl-mcp-server/src/index.ts` (lines 22-28)

### Code
```typescript
constructor() {
  // Debug: Log environment variables on startup
  console.error('=== GHL MCP Server Debug ===');
  console.error('GHL_API_KEY:', process.env.GHL_API_KEY ? `${process.env.GHL_API_KEY.substring(0, 10)}...` : 'MISSING');
  console.error('GHL_LOCATION_ID:', process.env.GHL_LOCATION_ID || 'MISSING');
  console.error('GHL_BASE_URL:', process.env.GHL_BASE_URL || 'DEFAULT');
  console.error('============================');

  this.ghlClient = new GHLClient({
    apiKey: process.env.GHL_API_KEY || '',
    locationId: process.env.GHL_LOCATION_ID || '',
    baseUrl: process.env.GHL_BASE_URL || 'https://services.leadconnectorhq.com',
  });
}
```

### Build Status
- ✅ **REBUILT** at ~22:38
- ✅ Debug code confirmed in `/mnt/c/code/ghl-mcp-server/build/index.js:17`

---

## 📋 ISSUE TIMELINE

### Initial Problem Discovery
- **Time:** ~22:35 (first restart)
- **Issue:** GHL MCP server returning 401 errors
- **Test:** `mcp__ghl__search_contacts` with query="ryan"
- **Result:** `Error executing search_contacts: Failed to search contacts: Request failed with status code 401`

### Log Analysis
- **File:** `/mnt/c/Users/ryanm/AppData/Roaming/Claude/logs/mcp-server-ghl-mcp-server.log`
- **Last startup:** 2025-10-09T07:32:57.285Z (4 days old!)
- **Finding:** Server was NOT restarted, old process still running
- **Missing:** No debug output from our console.error statements

### Rebuild Action
- **Command:** `cd /mnt/c/code/ghl-mcp-server && npm run build`
- **Result:** Successful compilation
- **Verification:** `grep -n "GHL MCP Server Debug" build/index.js` → Found at line 17

---

## 🔑 CREDENTIALS (VERIFIED WORKING VIA CURL)

### GoHighLevel API
- **API Token:** `pit-3e3598f9-8042-4e33-9e29-8d81d8ed6fdb` ✅
- **Location ID:** `GHJ0X5n0UomysnUPNfao` ✅
- **Base URL:** `https://services.leadconnectorhq.com`
- **Version Header:** `2021-07-28`

### Config File Location
`/mnt/c/Users/ryanm/AppData/Roaming/Claude/claude_desktop_config.json` (lines 21-28)

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

---

## 🐛 ROOT CAUSE HYPOTHESIS

### Primary Suspect: Claude Code Not Passing Env Vars
Claude Code is spawning the MCP server process but NOT passing the `env` object from the config file to the child process.

### Evidence
1. Config file has correct credentials ✅
2. Direct curl test with credentials works ✅
3. MCP server code correctly reads from `process.env` ✅
4. `process.env.GHL_API_KEY` returns undefined when tested ❌

### Windows-Specific Issue?
Possible that Claude Code has a bug on Windows where environment variables from config aren't passed to child processes spawned with `node.exe`.

---

## 🔄 NEXT SESSION COMMANDS

### Resume Command
```
Continue with Candid Analytics GHL MCP debug - read SESSION_UPDATE_2025-10-13_POST_RESTART.md
```

### First Test After Restart
```bash
# 1. Check logs for debug output
tail -50 /mnt/c/Users/ryanm/AppData/Roaming/Claude/logs/mcp-server-ghl-mcp-server.log

# 2. Test GHL connection
mcp__ghl__search_contacts query="ryan"
```

---

## 📂 KEY FILES

### GHL MCP Server
- **Source:** `/mnt/c/code/ghl-mcp-server/src/index.ts`
- **Build:** `/mnt/c/code/ghl-mcp-server/build/index.js`
- **Client:** `/mnt/c/code/ghl-mcp-server/src/ghl-client.ts`

### Configuration
- **Claude Config:** `/mnt/c/Users/ryanm/AppData/Roaming/Claude/claude_desktop_config.json`
- **Logs:** `/mnt/c/Users/ryanm/AppData/Roaming/Claude/logs/mcp-server-ghl-mcp-server.log`

### Documentation
- **Previous Session:** `/mnt/c/code/candid-analytics-app/SESSION_UPDATE_2025-10-13_ENV_DEBUG.md`
- **Field Mapping:** `/mnt/c/code/candid-analytics-app/GHL_FIELD_MAPPING.md`

---

## 💡 WORKAROUNDS IF ENV VARS STILL FAIL

### Option 1: Hardcode Credentials (Temporary)
Modify `/mnt/c/code/ghl-mcp-server/src/index.ts`:
```typescript
this.ghlClient = new GHLClient({
  apiKey: process.env.GHL_API_KEY || 'pit-3e3598f9-8042-4e33-9e29-8d81d8ed6fdb',
  locationId: process.env.GHL_LOCATION_ID || 'GHJ0X5n0UomysnUPNfao',
  baseUrl: 'https://services.leadconnectorhq.com',
});
```

### Option 2: Use .env File
1. Install dotenv: `npm install dotenv`
2. Create `/mnt/c/code/ghl-mcp-server/.env`
3. Load in index.ts: `import 'dotenv/config'`

### Option 3: Wrapper Script
Create `/mnt/c/code/ghl-mcp-server/start.bat`:
```batch
@echo off
set GHL_API_KEY=pit-3e3598f9-8042-4e33-9e29-8d81d8ed6fdb
set GHL_LOCATION_ID=GHJ0X5n0UomysnUPNfao
node "C:\code\ghl-mcp-server\build\index.js"
```

Update config to use wrapper:
```json
"command": "C:\\code\\ghl-mcp-server\\start.bat"
```

### Option 4: Manual GHL Setup
Skip MCP server and configure webhooks manually in GHL UI.

---

## ✅ SUCCESS CRITERIA

### Debug Logging Working
- [ ] Logs show "=== GHL MCP Server Debug ===" on startup
- [ ] Logs show `GHL_API_KEY: pit-3e3598...` (first 10 chars)
- [ ] Logs show `GHL_LOCATION_ID: GHJ0X5n0UomysnUPNfao`

### GHL Connection Working
- [ ] `mcp__ghl__search_contacts` returns contacts without 401 error
- [ ] Can retrieve contact by ID
- [ ] Can create test contact

### Ready for Automation Setup
- [ ] Three webhooks configured in GHL
- [ ] Make.com scenarios activated
- [ ] End-to-end test successful

---

## 🎯 PROJECT GOAL REMINDER

**Objective:** Set up analytics pipeline for Candid Studios

**Flow:**
```
GHL Events → Webhooks → Make.com → Analytics API → Database
```

**Three Key Webhooks:**
1. New Inquiry (Contact Created)
2. Project Booking (Pipeline → Planning)
3. Payment Received (Payment Event)

**Current Blocker:** GHL MCP server can't authenticate due to missing environment variables

---

**STATUS: Waiting for second restart to test debug logging**

**NEXT STEP: Restart Claude Code, then run tests above**
