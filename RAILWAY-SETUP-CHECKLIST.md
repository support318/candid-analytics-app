# Railway Setup Checklist - Candid Analytics
## ✅ Quick Reference Guide

**Project**: Candid Analytics (Already Created!)
**Status**: Ready to add services
**Time**: ~15 minutes

---

## 🎯 Service Overview

You need to add 4 services:
1. ✅ PostgreSQL Database
2. ✅ Redis Cache
3. ✅ API Backend (PHP)
4. ✅ Frontend (React)

---

## 📋 Step-by-Step Checklist

### ☑️ Step 1: Open Railway Dashboard

Run in terminal:
```bash
railway open
```

Or visit: https://railway.app/project

---

### ☑️ Step 2: Add PostgreSQL Database

1. Click **"+ New"** button
2. Select **"Database"**
3. Choose **"PostgreSQL"**
4. Click **"Add"**
5. Wait ~30 seconds for provisioning
6. ✅ PostgreSQL is ready!

**Note the service name**: Usually "Postgres" (you'll need this for env vars)

---

### ☑️ Step 3: Add Redis Cache

1. Click **"+ New"** button again
2. Select **"Database"**
3. Choose **"Redis"**
4. Click **"Add"**
5. Wait ~30 seconds for provisioning
6. ✅ Redis is ready!

**Note the service name**: Usually "Redis"

---

### ☑️ Step 4: Deploy API Backend

#### 4a. Create Service

1. Click **"+ New"**
2. Select **"GitHub Repo"**
3. Choose **"support318/candid-analytics-app"**
4. Railway will detect multiple services

#### 4b. Configure Service

- **Service Name**: `api`
- **Source**: Root directory should auto-detect `api/Dockerfile`
- Click **"Add Service"**

#### 4c. Add Environment Variables

Click on the **api** service → **Variables** tab → Click **"+ New Variable"**

**Copy and paste these variables** (Railway will auto-inject database references):

```env
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_NAME=${{Postgres.PGDATABASE}}
DB_USER=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}
REDIS_HOST=${{Redis.REDIS_HOST}}
REDIS_PORT=${{Redis.REDIS_PORT}}
JWT_SECRET=7d89e2c45095738f0b32b2761c98cce60028ec2f8e16f357717099454c0c3469fb65be356a98dd5f138a74b64800f1e53cb8297697b9cd19be032729e70f8caa
APP_ENV=production
APP_DEBUG=false
APP_NAME=Candid Analytics API
GHL_API_KEY=pit-4a0c3927-1650-44dd-b63d-2f65d81f84c3
GHL_LOCATION_ID=GHJ0X5n0UomysnUPNfao
GHL_API_BASE_URL=https://services.leadconnectorhq.com
GHL_API_VERSION=2021-07-28
FRONTEND_URL=https://analytics.candidstudios.net
ALLOWED_ORIGINS=https://analytics.candidstudios.net,https://${{RAILWAY_STATIC_URL}}
SESSION_LIFETIME=86400
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
RATE_LIMIT_ENABLED=true
RATE_LIMIT_MAX_REQUESTS=100
```

**Pro tip**: You can use "Raw Editor" mode and paste all at once!

#### 4d. Deploy

1. Click **"Deploy"** or wait for auto-deploy
2. Watch the logs - build takes ~3-5 minutes
3. ✅ API will be live when you see "apache2 started"

---

### ☑️ Step 5: Deploy Frontend

#### 5a. Create Service

1. Click **"+ New"**
2. Select **"GitHub Repo"**
3. Choose **"support318/candid-analytics-app"** (same repo)
4. Railway will detect frontend service

#### 5b. Configure Service

- **Service Name**: `frontend`
- **Source**: Root directory should auto-detect `frontend/Dockerfile`
- Click **"Add Service"**

#### 5c. Add Environment Variables

Click on the **frontend** service → **Variables** tab → **"+ New Variable"**

**Copy and paste these:**

```env
VITE_API_URL=https://api.candidstudios.net
VITE_APP_NAME=Candid Analytics
VITE_APP_VERSION=1.0.0
VITE_APP_ENV=production
VITE_API_TIMEOUT=30000
VITE_ENABLE_AI_FEATURES=true
VITE_ENABLE_REAL_TIME=false
VITE_ENABLE_ANALYTICS=true
```

#### 5d. Deploy

1. Click **"Deploy"**
2. Build takes ~2-3 minutes
3. ✅ Frontend will be live!

---

### ☑️ Step 6: Configure Custom Domain - API

1. Click on **api** service
2. Go to **Settings** → **Networking**
3. Click **"Generate Domain"** (optional - for testing)
4. Click **"Custom Domain"**
5. Enter: `api.candidstudios.net`
6. Railway will show you a CNAME record - **COPY THIS!**

Example:
```
CNAME: api.candidstudios.net → abc123.up.railway.app
```

#### Add to DNS:

Go to your DNS provider (Cloudflare, SiteGround, etc.):

```
Type: CNAME
Name: api
Value: [paste Railway CNAME here]
TTL: Auto (or 3600)
Proxy: OFF (if Cloudflare - use DNS only)
```

**Save DNS changes**

---

### ☑️ Step 7: Configure Custom Domain - Frontend

1. Click on **frontend** service
2. Go to **Settings** → **Networking**
3. Click **"Generate Domain"** (optional - for testing)
4. Click **"Custom Domain"**
5. Enter: `analytics.candidstudios.net`
6. Railway will show you a CNAME record - **COPY THIS!**

#### Add to DNS:

```
Type: CNAME
Name: analytics
Value: [paste Railway CNAME here]
TTL: Auto (or 3600)
Proxy: OFF (if Cloudflare - use DNS only)
```

**Save DNS changes**

---

### ☑️ Step 8: Run Database Migration

#### Option A: Railway Dashboard (Easiest)

1. Click on **Postgres** service
2. Go to **Data** tab
3. Click **"Query"** button
4. Open file: `/mnt/c/code/candid-analytics-app/database/00-essential-schema.sql`
5. Copy entire contents
6. Paste into Railway query editor
7. Click **"Execute"** or **"Run"**
8. ✅ Schema created!

#### Option B: Via Railway CLI

In your terminal:
```bash
cd /mnt/c/code/candid-analytics-app
railway link  # Select "Candid Analytics" project
railway run psql $DATABASE_URL < database/00-essential-schema.sql
```

---

### ☑️ Step 9: Verify Deployment

#### Check Services Status

All 4 services should show **green checkmark** ✅:
- ✅ Postgres
- ✅ Redis
- ✅ api
- ✅ frontend

#### Test API Health

In terminal:
```bash
# Using Railway domain (while DNS propagates)
curl https://api-candid-analytics.up.railway.app/api/health

# Or using custom domain (after DNS)
curl https://api.candidstudios.net/api/health
```

Expected response:
```json
{"status":"healthy","timestamp":"2025-10-18T..."}
```

#### Test Frontend

Open in browser:
```
https://frontend-candid-analytics.up.railway.app
```

Or after DNS:
```
https://analytics.candidstudios.net
```

You should see the login page!

---

### ☑️ Step 10: Monitor Logs

#### View API Logs

1. Click **api** service
2. Go to **Deployments** tab
3. Click latest deployment
4. View logs - look for:
   - ✅ "Database connected"
   - ✅ "Redis connected"
   - ✅ "Server started on port 80"

#### View Frontend Logs

1. Click **frontend** service
2. Go to **Deployments** tab
3. Look for:
   - ✅ "Build successful"
   - ✅ "nginx started"

---

## 🎉 Deployment Complete!

### Your Live URLs:

**Frontend**:
- Railway: `https://frontend-candid-analytics.up.railway.app`
- Custom: `https://analytics.candidstudios.net` (after DNS)

**API**:
- Railway: `https://api-candid-analytics.up.railway.app`
- Custom: `https://api.candidstudios.net` (after DNS)

---

## 🔍 Troubleshooting

### Build Failed?

**API Service:**
- Check Dockerfile exists at `api/Dockerfile`
- Review build logs for errors
- Ensure PHP extensions are installing correctly

**Frontend Service:**
- Check Dockerfile exists at `frontend/Dockerfile`
- Review npm install logs
- Check Vite build output

### Can't Connect to Database?

1. Verify Postgres service is running (green)
2. Check environment variables use `${{Postgres.PGHOST}}` syntax
3. Review API logs for connection errors

### CORS Errors?

1. Check `ALLOWED_ORIGINS` includes your frontend domain
2. Verify `FRONTEND_URL` is set correctly
3. Check browser console for specific error

### DNS Not Resolving?

1. Wait 5-15 minutes for propagation
2. Check with: `dig api.candidstudios.net`
3. Verify CNAME points to Railway domain
4. If using Cloudflare, disable proxy (use DNS only)

---

## 📊 Next Steps

1. ✅ Create admin user account
2. ✅ Test GHL integration
3. ✅ Import existing data (if any)
4. ⬜ Set up monitoring/alerts
5. ⬜ Configure database backups
6. ⬜ Update Make.com webhooks
7. ⬜ Deprecate Vercel deployment

---

## 💰 Cost Estimate

**Monthly Cost** (with Railway Pro plan):
- PostgreSQL: ~$10-15
- Redis: ~$5
- API Service: ~$5-10
- Frontend Service: ~$5
- **Total**: ~$25-35/month

**Railway Plans**:
- Hobby: $5/month (includes $5 credit)
- Pro: $20/month (includes $20 credit) - Recommended

---

## 📞 Support

- **Railway Docs**: https://docs.railway.app
- **Railway Discord**: https://discord.gg/railway
- **Project Issues**: https://github.com/support318/candid-analytics-app/issues

---

**Time to Complete**: 15-20 minutes
**Last Updated**: 2025-10-18
**Author**: Candid Studios Team
