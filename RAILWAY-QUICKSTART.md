# Railway Quick Start - Deploy Now! 🚀

This is your express guide to get Candid Analytics deployed to Railway in the next 30 minutes.

## Step 1: Create Railway Account (2 minutes)

1. Go to: https://railway.app
2. Click **"Login"** → Sign in with GitHub
3. Authorize Railway to access your repositories

## Step 2: Create New Project (1 minute)

1. Click **"New Project"**
2. Select **"Deploy from GitHub repo"**
3. Choose: `support318/candid-analytics-app`
4. Railway will create your project

## Step 3: Add PostgreSQL Database (1 minute)

1. In your project, click **"+ New"**
2. Select **"Database"**
3. Choose **"Add PostgreSQL"**
4. Railway will provision your database automatically
5. **Important**: Note the service name (usually just "Postgres")

## Step 4: Add Redis Cache (1 minute)

1. Click **"+ New"** again
2. Select **"Database"**
3. Choose **"Add Redis"**
4. Railway will provision Redis automatically

## Step 5: Deploy API Backend (3 minutes)

1. Click **"+ New"**
2. Select **"GitHub Repo"**
3. Choose `support318/candid-analytics-app`
4. Railway will ask about the service to deploy
5. Configure:
   - **Service Name**: `api`
   - **Root Directory**: Leave blank (Railway will auto-detect)
   - Click **"Add variables"**

### Add API Environment Variables:

Click **"Variables"** tab and add these:

```bash
# Copy these exactly - Railway will auto-inject database variables
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_NAME=${{Postgres.PGDATABASE}}
DB_USER=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}
REDIS_HOST=${{Redis.REDIS_HOST}}
REDIS_PORT=${{Redis.REDIS_PORT}}

# Security - use these as-is for now
JWT_SECRET=7d89e2c45095738f0b32b2761c98cce60028ec2f8e16f357717099454c0c3469fb65be356a98dd5f138a74b64800f1e53cb8297697b9cd19be032729e70f8caa
APP_ENV=production
APP_DEBUG=false

# GHL Integration
GHL_API_KEY=pit-4a0c3927-1650-44dd-b63d-2f65d81f84c3
GHL_LOCATION_ID=GHJ0X5n0UomysnUPNfao
GHL_API_BASE_URL=https://services.leadconnectorhq.com
GHL_API_VERSION=2021-07-28

# Frontend URL (we'll update this in Step 7)
FRONTEND_URL=https://analytics.candidstudios.net
ALLOWED_ORIGINS=https://analytics.candidstudios.net
```

6. Click **"Deploy"** - Railway will build and deploy the API

## Step 6: Deploy Frontend (3 minutes)

1. Click **"+ New"** → **"GitHub Repo"**
2. Choose `support318/candid-analytics-app` again
3. Configure:
   - **Service Name**: `frontend`
4. Click **"Variables"** tab and add:

```bash
VITE_API_URL=https://api.candidstudios.net
VITE_APP_NAME=Candid Analytics
VITE_APP_VERSION=1.0.0
VITE_APP_ENV=production
VITE_API_TIMEOUT=30000
VITE_ENABLE_AI_FEATURES=true
VITE_ENABLE_REAL_TIME=false
VITE_ENABLE_ANALYTICS=true
```

5. Click **"Deploy"**

## Step 7: Configure Custom Domains (5 minutes)

### For API (api.candidstudios.net):

1. Click on **"api"** service
2. Go to **"Settings"** → **"Domains"**
3. Click **"Custom Domain"**
4. Enter: `api.candidstudios.net`
5. Railway shows a CNAME target - **COPY THIS**

**Go to your DNS provider (Cloudflare/SiteGround):**
```
Type: CNAME
Name: api
Target: [paste Railway CNAME here]
TTL: Auto
```

### For Frontend (analytics.candidstudios.net):

1. Click on **"frontend"** service
2. Go to **"Settings"** → **"Domains"**
3. Click **"Custom Domain"**
4. Enter: `analytics.candidstudios.net`
5. Railway shows a CNAME target - **COPY THIS**

**Go to your DNS provider:**
```
Type: CNAME
Name: analytics
Target: [paste Railway CNAME here]
TTL: Auto
```

## Step 8: Run Database Migration (3 minutes)

### Option A: Using Railway Dashboard (Easiest)

1. Click on **"Postgres"** service
2. Go to **"Data"** tab
3. Click **"Query"**
4. Open `/database/00-essential-schema.sql` on your computer
5. Copy entire contents
6. Paste into Railway query editor
7. Click **"Run"**

### Option B: Using Railway CLI

If you have Railway CLI installed:

```bash
cd /mnt/c/code/candid-analytics-app
railway login
railway link [select your project]
railway run psql $DATABASE_URL < database/00-essential-schema.sql
```

## Step 9: Verify Deployment (5 minutes)

### Check API:
```bash
curl https://api.candidstudios.net/api/health
```

Expected response:
```json
{"status":"healthy","timestamp":"..."}
```

### Check Frontend:
Open browser: https://analytics.candidstudios.net

You should see the login page!

### Check Logs:
In Railway dashboard, click each service and view the **"Logs"** tab to ensure no errors.

## Step 10: First Login (2 minutes)

1. Navigate to: https://analytics.candidstudios.net
2. If no users exist yet, you'll need to create one via database:

```sql
-- Run this in Railway Postgres → Data → Query
INSERT INTO users (username, email, password_hash, role, status)
VALUES (
  'admin',
  'admin@candidstudios.net',
  '$2y$10$YourHashedPasswordHere',  -- You'll need to generate this
  'admin',
  'active'
);
```

Or use the API to create a user:
```bash
curl -X POST https://api.candidstudios.net/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "email": "admin@candidstudios.net",
    "password": "YourSecurePassword123!",
    "role": "admin"
  }'
```

---

## 🎉 You're Live!

Your Candid Analytics application is now running on Railway!

- **Frontend**: https://analytics.candidstudios.net
- **API**: https://api.candidstudios.net
- **Database**: Managed by Railway
- **Redis**: Managed by Railway

## Next Steps

1. ✅ Test login functionality
2. ✅ Verify GHL integration works
3. ⬜ Set up database backups (Railway Pro)
4. ⬜ Configure monitoring/alerts
5. ⬜ Update Make.com webhooks to point to new API URL
6. ⬜ Deprecate Vercel deployment

## Troubleshooting

### "Service won't deploy"
- Check **Logs** tab in Railway for build errors
- Verify Dockerfile is present in repo
- Try redeploying: Click **"Deploy"** button again

### "Can't connect to database"
- Verify environment variables use `${{Postgres.PGHOST}}` syntax
- Check Postgres service is running (green status)
- Review API logs for connection errors

### "CORS errors"
- Ensure `ALLOWED_ORIGINS` includes your frontend domain
- Check `FRONTEND_URL` matches your frontend domain
- Verify both domains are using HTTPS

### "DNS not resolving"
- Wait 5-10 minutes for DNS propagation
- Check DNS with: `dig analytics.candidstudios.net`
- Verify CNAME is pointing to Railway domain

## Cost

Railway charges based on usage. Expected costs:

- **Hobby Plan**: $5/month (includes $5 credit) - Good for testing
- **Pro Plan**: $20/month (includes $20 credit) - Recommended for production

Estimated monthly cost for this app: **~$15-25** with all services running.

## Support

Need help?
- **Full Guide**: See `RAILWAY-DEPLOYMENT.md` in this repo
- **Railway Docs**: https://docs.railway.app
- **Railway Discord**: https://discord.gg/railway

---

**Ready to deploy?** Start with Step 1 above! 🚀

**Estimated Total Time**: 30 minutes
**Difficulty**: Easy
**Prerequisites**: Railway account + GitHub access
