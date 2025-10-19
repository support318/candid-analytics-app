# Railway GitHub Deployment Setup

Quick guide to configure Railway services to deploy from GitHub.

## ✅ What's Already Done:
- All environment variables configured
- Database migration completed
- Code pushed to GitHub: `support318/candid-analytics-app`

## 🔧 Configure Services (Do this now):

### Step 1: Open Railway Dashboard
```bash
# The project URL is:
https://railway.app/project/cf7108df-2b97-4bd9-9bdd-e6930abc2d73
```

### Step 2: Configure API Service

1. Click on **"api"** service
2. Go to **Settings** tab
3. Under **Source**:
   - Should show: `support318/candid-analytics-app`
   - If not connected, click "Connect GitHub Repo"
4. Under **Build**:
   - **Root Directory**: `api`
   - **Dockerfile Path**: `Dockerfile`
5. Click **"Deploy"** at the top

### Step 3: Configure Frontend Service

1. Click on **"candid-analytics-app"** service
2. Go to **Settings** tab
3. Under **Source**:
   - Should show: `support318/candid-analytics-app`
   - If not connected, click "Connect GitHub Repo"
4. Under **Build**:
   - **Root Directory**: `frontend`
   - **Dockerfile Path**: `Dockerfile`
5. Click **"Deploy"** at the top

## 📊 Monitor Deployments:

Watch the build logs in Railway dashboard. Each service will:
1. Clone from GitHub
2. Build Docker image
3. Deploy container
4. Health check passes

## 🌐 Service URLs:

After deployment completes:
- **API**: https://api-production-fac1.up.railway.app
- **Frontend**: https://candid-analytics-app-production.up.railway.app

Test with:
```bash
curl https://api-production-fac1.up.railway.app/api/health
```

## ⏭️ Next Steps After Deployment:

1. Set up custom domains:
   - api.candidstudios.net → API service
   - analytics.candidstudios.net → Frontend service

2. Update frontend VITE_API_URL if using custom domain

---

**Estimated time**: 5 minutes
**Status**: Ready to configure
