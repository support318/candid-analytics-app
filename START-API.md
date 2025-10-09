# 🚀 Candid Analytics - Complete Deployment Guide

## ✅ System Status

**FULLY DEPLOYED AND OPERATIONAL!**

- ✅ Frontend: https://candid-analytics-fky6y2vam-support-6191s-projects.vercel.app/
- ✅ API: https://api.candidstudios.net/api/health
- ✅ Database: PostgreSQL (Docker)
- ✅ Cache: Redis (Docker)
- ✅ Authentication: Working with JWT

## 🔑 Login Credentials

- **Username:** admin
- **Password:** password
- **Role:** admin

## 📝 Quick Start (If System is Down)

### Step 1: Start Docker Containers

Open **Command Prompt** (cmd) in WSL and run:

```cmd
cd C:\code\candid-analytics-app
docker-compose up -d
```

This will start:
- ✅ PostgreSQL database (port 5432)
- ✅ Redis cache (port 6379)
- ✅ PHP API (port 8000)

**First time setup takes 2-3 minutes** (installing PHP extensions, Composer dependencies)

## Step 3: Check if API is Running

Wait about 2-3 minutes, then test:

```cmd
curl http://localhost:8000/api/health
```

You should see:
```json
{"success":true,"data":{"status":"healthy"}}
```

## Step 4: Restart Cloudflare Tunnel

```bash
# Find the running tunnel process
ps aux | grep cloudflared

# Or if running as a service
sudo systemctl restart cloudflared

# Or restart manually
cloudflared tunnel run
```

## Step 5: Test Public API

After tunnel restarts, test:

```bash
curl https://api.candidstudios.net/api/health
```

You should see the same healthy response!

## Step 6: Create Database User

```bash
# Connect to PostgreSQL container
docker exec -it candid-analytics-db psql -U candid_analytics_user -d candid_analytics

# Create admin user for login
INSERT INTO users (username, email, password_hash, role, status)
VALUES (
    'admin',
    'admin@candidstudios.net',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- password: password
    'admin',
    'active'
);

# Exit
\q
```

## Step 7: Update Frontend URL

Go to Vercel dashboard and update environment variable:
- **Key:** `VITE_API_URL`
- **Value:** `https://api.candidstudios.net`

Then redeploy:
```cmd
cd C:\code\candid-analytics-app\frontend
vercel --prod
```

## 🎉 Done!

Your dashboard should now work at:
https://candid-analytics-fky6y2vam-support-6191s-projects.vercel.app/

Login with:
- **Username:** admin
- **Password:** password

---

## 📋 Useful Commands

**View logs:**
```cmd
docker-compose logs -f api
```

**Restart API only:**
```cmd
docker-compose restart api
```

**Stop everything:**
```cmd
docker-compose down
```

**Start everything:**
```cmd
docker-compose up -d
```

**View running containers:**
```cmd
docker ps
```
