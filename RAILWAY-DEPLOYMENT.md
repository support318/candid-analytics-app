# Railway Deployment Guide - Candid Analytics

Complete guide for deploying the Candid Analytics application to Railway.

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Quick Start](#quick-start)
3. [Detailed Setup](#detailed-setup)
4. [Environment Variables](#environment-variables)
5. [Custom Domains](#custom-domains)
6. [Database Migration](#database-migration)
7. [Post-Deployment](#post-deployment)
8. [Troubleshooting](#troubleshooting)

---

## Prerequisites

- Railway account: https://railway.app
- Railway CLI installed (optional but recommended)
- Git repository access
- Domain DNS access (for custom domains)

### Install Railway CLI

```bash
# macOS/Linux
curl -fsSL https://railway.app/install.sh | sh

# Windows (PowerShell)
iwr https://railway.app/install.ps1 | iex

# Verify installation
railway version
```

## Quick Start

### Option 1: Deploy via Railway Dashboard (Recommended for first deployment)

1. **Login to Railway**: https://railway.app
2. **Create New Project**: Click "New Project"
3. **Deploy from GitHub**:
   - Select "Deploy from GitHub repo"
   - Authorize GitHub if needed
   - Select: `support318/candid-analytics-app`
   - Railway will detect the monorepo structure

4. **Add Services**:
   Railway should auto-detect the services, but if not:
   - Click "New Service" → PostgreSQL
   - Click "New Service" → Redis
   - Services should auto-deploy from Dockerfiles

### Option 2: Deploy via Railway CLI

```bash
# Login to Railway
railway login

# Navigate to project directory
cd /path/to/candid-analytics-app

# Initialize Railway project
railway init

# Link to your Railway project (if already created)
railway link

# Deploy all services
railway up
```

---

## Detailed Setup

### Step 1: Create PostgreSQL Database

1. In Railway Dashboard, click **"New Service"**
2. Select **"Database" → "PostgreSQL"**
3. Railway will provision a PostgreSQL 16 instance
4. Note: Railway automatically creates these environment variables:
   - `DATABASE_URL`
   - `PGHOST`
   - `PGPORT`
   - `PGUSER`
   - `PGPASSWORD`
   - `PGDATABASE`

### Step 2: Create Redis Cache

1. Click **"New Service"**
2. Select **"Database" → "Redis"**
3. Railway will provision a Redis instance
4. Note the `REDIS_HOST` and `REDIS_PORT` variables

### Step 3: Deploy API Backend

1. Click **"New Service" → "GitHub Repo"**
2. Select your repository
3. Configure the service:
   - **Service Name**: `candid-analytics-api`
   - **Root Directory**: `/api` (if Railway doesn't auto-detect)
   - **Dockerfile Path**: `api/Dockerfile`
4. Add environment variables (see section below)
5. Click **"Deploy"**

### Step 4: Deploy Frontend

1. Click **"New Service" → "GitHub Repo"**
2. Select your repository (same repo)
3. Configure the service:
   - **Service Name**: `candid-analytics-frontend`
   - **Root Directory**: `/frontend`
   - **Dockerfile Path**: `frontend/Dockerfile`
4. Add environment variables (see section below)
5. Click **"Deploy"**

---

## Environment Variables

### API Service Environment Variables

Copy these to your API service in Railway:

```bash
# Database (Auto-injected by Railway PostgreSQL service)
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_NAME=${{Postgres.PGDATABASE}}
DB_USER=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}

# Redis (Auto-injected by Railway Redis service)
REDIS_HOST=${{Redis.REDIS_HOST}}
REDIS_PORT=${{Redis.REDIS_PORT}}

# JWT Secret (Generate new: openssl rand -hex 64)
JWT_SECRET=7d89e2c45095738f0b32b2761c98cce60028ec2f8e16f357717099454c0c3469fb65be356a98dd5f138a74b64800f1e53cb8297697b9cd19be032729e70f8caa

# Frontend URL (Update after deploying frontend)
FRONTEND_URL=https://analytics.candidstudios.net

# Allowed Origins
ALLOWED_ORIGINS=https://analytics.candidstudios.net,https://${{RAILWAY_STATIC_URL}}

# App Configuration
APP_ENV=production
APP_DEBUG=false
APP_NAME=Candid Analytics API

# GoHighLevel Integration
GHL_API_KEY=pit-4a0c3927-1650-44dd-b63d-2f65d81f84c3
GHL_LOCATION_ID=GHJ0X5n0UomysnUPNfao
GHL_API_BASE_URL=https://services.leadconnectorhq.com
GHL_API_VERSION=2021-07-28

# Security
SESSION_LIFETIME=86400
SESSION_SECURE=true
SESSION_HTTP_ONLY=true

# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_MAX_REQUESTS=100
```

### Frontend Service Environment Variables

```bash
# API URL (Update with your Railway API service URL)
VITE_API_URL=https://api.candidstudios.net

# App Configuration
VITE_APP_NAME=Candid Analytics
VITE_APP_VERSION=1.0.0
VITE_APP_ENV=production
VITE_API_TIMEOUT=30000

# Feature Flags
VITE_ENABLE_AI_FEATURES=true
VITE_ENABLE_REAL_TIME=false
VITE_ENABLE_ANALYTICS=true
```

---

## Custom Domains

### Frontend Domain: analytics.candidstudios.net

1. In Railway, go to **Frontend Service → Settings → Domains**
2. Click **"Add Domain"**
3. Enter: `analytics.candidstudios.net`
4. Railway will provide a CNAME target (e.g., `xyz.up.railway.app`)

**DNS Configuration:**
```
Type: CNAME
Name: analytics
Target: [Railway-provided-domain]
TTL: Auto or 3600
```

### API Domain: api.candidstudios.net

1. In Railway, go to **API Service → Settings → Domains**
2. Click **"Add Domain"**
3. Enter: `api.candidstudios.net`
4. Railway will provide a CNAME target

**DNS Configuration:**
```
Type: CNAME
Name: api
Target: [Railway-provided-domain]
TTL: Auto or 3600
```

**Verify DNS:**
```bash
# Check DNS propagation
dig analytics.candidstudios.net
dig api.candidstudios.net

# Or online: https://www.whatsmydns.net/
```

---

## Database Migration

### Run Schema Migration

**Option 1: Via Railway CLI**

```bash
# Connect to PostgreSQL service
railway run psql $DATABASE_URL

# In psql, run:
\i /path/to/database/00-essential-schema.sql
```

**Option 2: Using PostgreSQL Client**

```bash
# Get DATABASE_URL from Railway dashboard
railway variables

# Connect using psql
psql "postgresql://user:pass@host:port/dbname"

# Run schema file
\i database/00-essential-schema.sql
```

**Option 3: Via Railway Dashboard**

1. Go to PostgreSQL service → **Data** tab
2. Click **"Query"**
3. Copy and paste contents of `database/00-essential-schema.sql`
4. Click **"Execute"**

### Import Existing Data (if migrating)

If you have existing data to import:

```bash
# Export from current database
pg_dump -h localhost -U candid_analytics_user candid_analytics > backup.sql

# Import to Railway
railway run psql $DATABASE_URL < backup.sql
```

---

## Post-Deployment

### 1. Verify Services

Check that all services are running:

```bash
# API Health Check
curl https://api.candidstudios.net/api/health

# Frontend
curl https://analytics.candidstudios.net/health
```

### 2. Test Authentication

1. Navigate to: `https://analytics.candidstudios.net`
2. Try logging in with existing credentials
3. Check API responses in browser DevTools

### 3. Test GHL Integration

```bash
# Test GHL webhook
curl -X POST https://api.candidstudios.net/api/webhooks/ghl \
  -H "Content-Type: application/json" \
  -d '{"type": "ContactCreate", "contact": {...}}'
```

### 4. Monitor Logs

```bash
# View API logs
railway logs candid-analytics-api

# View Frontend logs
railway logs candid-analytics-frontend

# View Database logs
railway logs postgres
```

### 5. Update Vercel Deployment (Optional)

If you're migrating from Vercel, you can:
- Delete the Vercel project, or
- Set up a redirect from Vercel to Railway

---

## Troubleshooting

### Build Failures

**Issue**: Dockerfile build fails

```bash
# Check build logs in Railway dashboard
# Common fixes:

# 1. Clear Railway build cache
railway run --service api rm -rf /tmp/*

# 2. Rebuild from scratch
railway up --detach

# 3. Check Dockerfile syntax locally
docker build -t test-api -f api/Dockerfile api/
docker build -t test-frontend -f frontend/Dockerfile frontend/
```

### Database Connection Issues

**Issue**: API can't connect to PostgreSQL

1. Verify environment variables are set correctly:
   ```bash
   railway variables --service candid-analytics-api
   ```

2. Check PostgreSQL is running:
   ```bash
   railway status
   ```

3. Test connection:
   ```bash
   railway run --service api php -r "var_dump(getenv('DB_HOST'));"
   ```

### CORS Errors

**Issue**: Frontend can't connect to API

1. Check `ALLOWED_ORIGINS` includes frontend domain
2. Verify API `FRONTEND_URL` is correct
3. Check browser console for specific error
4. Update API `.htaccess` or CORS middleware

### SSL/HTTPS Issues

Railway automatically provides SSL certificates. If issues occur:

1. Wait 5-10 minutes for SSL provisioning
2. Check domain DNS is pointing correctly
3. Verify domain is added in Railway dashboard
4. Force HTTPS redirect is enabled

### Slow Performance

1. **Enable Redis caching**: Verify Redis service is connected
2. **Database indexing**: Check indexes on frequently queried columns
3. **Railway metrics**: Review CPU/Memory usage in dashboard
4. **Upgrade plan**: Consider Railway Pro plan for better resources

---

## Railway CLI Commands Reference

```bash
# Login
railway login

# Link project
railway link

# View environment variables
railway variables

# Set environment variable
railway variables --set KEY=value

# View logs
railway logs

# Run command in Railway environment
railway run [command]

# Connect to PostgreSQL
railway run psql $DATABASE_URL

# Deploy
railway up

# Get service status
railway status

# Open Railway dashboard
railway open
```

---

## Cost Estimation

Railway pricing (as of 2025):

- **Developer Plan**: $5/month
  - Included: $5 usage credit
  - ~500 hours of service uptime

- **Pro Plan**: $20/month
  - Included: $20 usage credit
  - Priority support

**Estimated costs for this project:**
- PostgreSQL: ~$10-15/month
- Redis: ~$5/month
- API Service: ~$5-10/month
- Frontend Service: ~$5/month

**Total**: ~$25-35/month (on Pro plan with credits applied)

---

## Next Steps

1. ✅ Deploy all services to Railway
2. ✅ Configure custom domains
3. ✅ Run database migrations
4. ✅ Test all functionality
5. ⬜ Set up monitoring/alerts
6. ⬜ Configure backups
7. ⬜ Update documentation with production URLs
8. ⬜ Notify team of new deployment

---

## Support

- **Railway Docs**: https://docs.railway.app
- **Railway Discord**: https://discord.gg/railway
- **Railway Status**: https://status.railway.app
- **This Project Issues**: https://github.com/support318/candid-analytics-app/issues

---

**Last Updated**: 2025-10-18
**Author**: Candid Studios Development Team
**Railway Project**: candid-analytics (Production)
