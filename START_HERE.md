# 🚀 START HERE - Deploy to Railway

**Status**: Everything is ready. Just need you to authenticate Railway CLI first.

---

## Quick Deploy (2 Commands)

Open your terminal and run:

```bash
# 1. Log in to Railway (opens browser)
railway login

# 2. Run automated deployment
cd /Users/ryanmayiras/Projects/candid-analytics-app
./railway-deploy-complete.sh
```

That's it! The script handles:
- ✅ Linking Railway project
- ✅ Applying database migration (22 columns)
- ✅ Updating environment variables
- ✅ Restarting services
- ✅ Verification checks

**Estimated Time**: 5-10 minutes

---

## What `railway login` Does

When you run `railway login`, it will:
1. Open your browser
2. Ask you to authenticate with Railway
3. Save an auth token locally
4. Enable all Railway commands to work

**Note**: This is a one-time setup. Once logged in, the token persists.

---

## After Deployment

The script will tell you to run these commands:

```bash
# Test import (no database writes)
php api/scripts/sync-ghl-historical-COMPLETE.php --dry-run

# If dry-run looks good, run full import
php api/scripts/sync-ghl-historical-COMPLETE.php

# Test dashboard
open https://analytics.candidstudios.net
```

---

## What Gets Deployed

### Database (22 new columns)
- **projects**: 7 columns (GHL linking, discounts, video flag, travel, calendar)
- **clients**: 6 columns (engagement score, mailing address, partner info)
- **deliverables**: 5 columns (file storage links)
- **reviews**: 3 columns (feedback text, review link)
- **staff_assignments**: 1 column (GHL staff ID)

### Import Script
- Uses ALL 57 analytics-relevant GHL custom fields
- Correct classification: `Planning` stage = PROJECT, others = INQUIRY
- Populates 5 tables with complete data

### Environment Variables
- New GHL API key: `pit-db1970e0-70d8-4d85-80f0-f04a4ba1cb8b`
- GHL Location ID: `GHJ0X5n0UomysnUPNfao`
- API base URL and version

---

## Why I Couldn't Deploy Automatically

The Railway MCP server I have access to requires the Railway CLI to be logged in first. The login process requires an interactive browser session for OAuth authentication, which I can't trigger automatically.

But once you run `railway login` (takes 30 seconds), everything else is automated!

---

## Troubleshooting

### "railway: command not found"
```bash
npm install -g railway
```

### Railway link fails
If auto-link fails in the script, manually run:
```bash
railway link
# Select: Candid Projects → candid-analytics-app → production
```
Then re-run the script.

### Need to check anything
All documentation is ready:
- `DEPLOY_COMMANDS.txt` - Quick command reference
- `QUICK_START_GUIDE.md` - 8-step detailed guide
- `DEPLOYMENT_READY.md` - Complete summary

---

## Summary

**What's Ready**:
- ✅ All code complete
- ✅ Database migration ready
- ✅ Import script ready
- ✅ Deployment script ready
- ✅ Local .env updated

**What You Need to Do**:
1. Run `railway login` (30 seconds)
2. Run `./railway-deploy-complete.sh` (5-10 minutes automated)
3. Test import and verify dashboard

That's it! 🎉

---

**Last Updated**: 2025-11-02
**Total Development Time**: Phases 1-5 complete
**Deployment Time**: 5-10 minutes after login
