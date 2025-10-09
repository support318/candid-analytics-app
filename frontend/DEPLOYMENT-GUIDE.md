# React Frontend Deployment Guide

Complete guide to deploy your Candid Analytics React dashboard to production.

## 📋 Prerequisites

Before deploying, make sure you have:

- ✅ API backend deployed and running (see `/api/DEPLOYMENT-GUIDE.md`)
- ✅ API URL (e.g., `https://api.candidstudios.net`)
- ✅ Node.js 18+ installed locally
- ✅ npm installed locally
- ✅ Git installed (for version control)

## 🎯 Deployment Options

Choose one of these deployment methods:

### Option 1: Vercel (Recommended - Free Tier Available)
- ✅ Automatic deployments from Git
- ✅ Global CDN
- ✅ SSL certificates included
- ✅ Environment variable management
- ✅ Preview deployments for branches
- **Best for:** Fast, easy deployment with CI/CD

### Option 2: Netlify (Alternative - Free Tier Available)
- ✅ Similar features to Vercel
- ✅ Drag-and-drop deployment option
- ✅ Form handling built-in
- **Best for:** Alternative to Vercel

### Option 3: Traditional Web Server (VPS/Shared Hosting)
- ✅ Full control over server
- ✅ Can host alongside API
- **Best for:** Existing infrastructure, cost optimization

---

## 🚀 Method 1: Deploy to Vercel (Recommended)

### Step 1: Prepare Your Project

```bash
cd /mnt/c/code/candid-analytics-app/frontend

# Test the build locally first
npm install
npm run build

# Verify build succeeded
ls -la dist/
```

### Step 2: Initialize Git (if not already)

```bash
# Initialize git repository
git init

# Add all files
git add .

# Create initial commit
git commit -m "Initial commit - Candid Analytics Dashboard"
```

### Step 3: Push to GitHub

```bash
# Create a new repository on GitHub (https://github.com/new)
# Then push your code:

git remote add origin https://github.com/YOUR_USERNAME/candid-analytics-dashboard.git
git branch -M main
git push -u origin main
```

### Step 4: Deploy to Vercel

**Option A: Using Vercel CLI (Recommended)**

```bash
# Install Vercel CLI globally
npm install -g vercel

# Login to Vercel
vercel login

# Deploy (from the frontend directory)
vercel

# Follow the prompts:
# ? Set up and deploy "~/candid-analytics-app/frontend"? [Y/n] Y
# ? Which scope? [Your Account]
# ? Link to existing project? [y/N] N
# ? What's your project's name? candid-analytics
# ? In which directory is your code located? ./
# ? Want to override the settings? [y/N] N

# Deploy to production
vercel --prod
```

**Option B: Using Vercel Dashboard**

1. Go to https://vercel.com/new
2. Click "Import Git Repository"
3. Select your GitHub repository
4. Configure project:
   - **Framework Preset:** Vite
   - **Root Directory:** frontend (if in monorepo) or leave blank
   - **Build Command:** `npm run build`
   - **Output Directory:** `dist`
5. Click "Deploy"

### Step 5: Configure Environment Variables

In Vercel Dashboard:

1. Go to your project
2. Click "Settings" → "Environment Variables"
3. Add these variables:

```
Name: VITE_API_URL
Value: https://api.candidstudios.net
Environment: Production, Preview, Development

Name: VITE_APP_NAME
Value: Candid Analytics
Environment: Production, Preview, Development
```

4. Click "Save"
5. Redeploy to apply changes

### Step 6: Configure Custom Domain (Optional)

1. In Vercel Dashboard → "Settings" → "Domains"
2. Add domain: `analytics.candidstudios.net`
3. Follow DNS configuration instructions:
   - Add CNAME record pointing to `cname.vercel-dns.com`
4. Wait for SSL certificate (automatic, takes a few minutes)

### Step 7: Test Your Deployment

```bash
# Open your deployed app
vercel open

# Or visit directly:
# https://candid-analytics.vercel.app
# https://analytics.candidstudios.net (if custom domain configured)
```

**Test Checklist:**
- ✅ Login page loads
- ✅ Can log in with credentials
- ✅ Dashboard redirects work
- ✅ All 8 pages load without errors
- ✅ Charts and data display correctly
- ✅ No console errors in browser dev tools

---

## 🚀 Method 2: Deploy to Netlify

### Step 1: Prepare Your Project

```bash
cd /mnt/c/code/candid-analytics-app/frontend
npm install
npm run build
```

### Step 2: Deploy Using Netlify CLI

```bash
# Install Netlify CLI
npm install -g netlify-cli

# Login to Netlify
netlify login

# Initialize project
netlify init

# Deploy to production
netlify deploy --prod --dir=dist
```

### Step 3: Configure Environment Variables

```bash
# Using CLI
netlify env:set VITE_API_URL "https://api.candidstudios.net"

# Or in Netlify Dashboard:
# Site Settings → Environment Variables
```

### Step 4: Configure Redirects for SPA

Create `public/_redirects` file:

```
/*    /index.html   200
```

Rebuild and redeploy:

```bash
npm run build
netlify deploy --prod --dir=dist
```

---

## 🚀 Method 3: Deploy to Traditional Web Server

### Step 1: Build the Application

```bash
cd /mnt/c/code/candid-analytics-app/frontend

# Install dependencies
npm install

# Create production build
npm run build
```

### Step 2: Copy Files to Server

```bash
# Create directory on server
ssh user@your-server.com
sudo mkdir -p /var/www/analytics.candidstudios.net
sudo chown -R $USER:$USER /var/www/analytics.candidstudios.net

# Exit SSH and copy files from local machine
cd /mnt/c/code/candid-analytics-app/frontend
scp -r dist/* user@your-server.com:/var/www/analytics.candidstudios.net/
```

### Step 3: Configure Nginx

```bash
# SSH back into server
ssh user@your-server.com

# Create Nginx configuration
sudo nano /etc/nginx/sites-available/analytics.candidstudios.net
```

Add this configuration:

```nginx
server {
    listen 80;
    server_name analytics.candidstudios.net;
    root /var/www/analytics.candidstudios.net;
    index index.html;

    # Gzip compression
    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml;
    gzip_comp_level 6;

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # SPA routing - serve index.html for all routes
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/analytics.candidstudios.net /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 4: Set Up SSL Certificate

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d analytics.candidstudios.net

# Certificate will auto-renew
sudo certbot renew --dry-run
```

### Step 5: Update for Future Deployments

Create a deployment script `deploy.sh`:

```bash
#!/bin/bash
# Local script to deploy frontend updates

cd /mnt/c/code/candid-analytics-app/frontend

echo "Building application..."
npm run build

echo "Deploying to server..."
scp -r dist/* user@your-server.com:/var/www/analytics.candidstudios.net/

echo "Deployment complete!"
echo "Visit: https://analytics.candidstudios.net"
```

Make it executable:

```bash
chmod +x deploy.sh
```

Run deployments with:

```bash
./deploy.sh
```

---

## 🔧 Environment Configuration

### Development Environment

Create `.env`:

```bash
VITE_API_URL=http://localhost:8000
VITE_APP_NAME=Candid Analytics (Dev)
VITE_ENABLE_AI_FEATURES=true
```

### Production Environment

**For Vercel/Netlify:** Set in dashboard

**For traditional server:** Create `.env.production`:

```bash
VITE_API_URL=https://api.candidstudios.net
VITE_APP_NAME=Candid Analytics
VITE_ENABLE_AI_FEATURES=true
```

---

## 🐛 Troubleshooting

### Build Fails

```bash
# Clear cache and reinstall
rm -rf node_modules dist
npm install
npm run build
```

### API Connection Fails

1. Check `.env` has correct API URL
2. Verify API backend is accessible:
   ```bash
   curl https://api.candidstudios.net/api/health
   ```
3. Check CORS configuration in API `public/index.php`

### Routes Not Working (404 errors)

**Problem:** Direct navigation to `/dashboard/revenue` returns 404

**Solution:** Configure rewrites/redirects:

- **Vercel:** Already configured in `vercel.json`
- **Netlify:** Add `public/_redirects` file (see Method 2)
- **Nginx:** Use `try_files` directive (see Method 3)

### Environment Variables Not Working

**Problem:** `VITE_API_URL` is undefined

**Solution:**
1. Ensure variable starts with `VITE_` prefix
2. Restart dev server after changing `.env`
3. Rebuild for production: `npm run build`

---

## 📊 Performance Optimization

### Enable Compression

Already configured in:
- ✅ Vercel/Netlify (automatic)
- ✅ Nginx configuration (gzip enabled)

### Optimize Images

```bash
# Install image optimization tool
npm install -g sharp-cli

# Optimize images
sharp -i public/*.png -o public/ --webp
```

### Analyze Bundle Size

```bash
npm run build

# Install analyzer
npm install -D rollup-plugin-visualizer

# Add to vite.config.ts:
# import { visualizer } from 'rollup-plugin-visualizer'
# plugins: [react(), visualizer()]

# Rebuild and view report
npm run build
```

---

## ✅ Post-Deployment Checklist

After deployment, verify:

- [ ] Login page loads successfully
- [ ] Can authenticate with credentials
- [ ] All 8 dashboard pages load without errors
- [ ] Charts and visualizations display data
- [ ] API requests succeed (check Network tab)
- [ ] No console errors in browser dev tools
- [ ] Mobile responsive design works
- [ ] SSL certificate is valid (https://)
- [ ] Custom domain resolves correctly
- [ ] Page load time < 3 seconds

---

## 🎯 Next Steps

1. ✅ Frontend deployed successfully
2. **Update Make.com webhooks** to use production API URL
3. **Test end-to-end flow** (GHL → Make.com → API → Dashboard)
4. **Monitor performance** (Vercel Analytics or Google Analytics)
5. **Set up error tracking** (Sentry or similar)

---

## 📞 Support

**Common Issues:**

1. **Can't login:** Check API backend is running, verify credentials in database
2. **Blank pages:** Check browser console for errors, verify API endpoints return data
3. **Slow loading:** Enable compression, check API response times
4. **CORS errors:** Update `ALLOWED_ORIGINS` in API `.env`

**Logs:**

- **Vercel:** Dashboard → Deployment → Logs
- **Netlify:** Dashboard → Deploys → Deploy Log
- **Nginx:** `/var/log/nginx/error.log`

---

**🎉 Congratulations! Your analytics dashboard is now live!**

Visit your dashboard and start making data-driven decisions for Candid Studios.
