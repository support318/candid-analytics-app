# Candid Studios Analytics - Complete Deployment Guide

## 🎯 What You Have Now

A complete, production-ready **PHP API backend** with:
✅ All 9 API endpoints configured and ready
✅ JWT authentication system
✅ Redis caching for fast performance
✅ PostgreSQL integration
✅ Error handling and logging

---

## 📋 What You Need Before Starting

### Required Software
1. **Server** - Ubuntu 20.04+ (or your existing server)
2. **PHP 8.0+** with extensions: `pgsql`, `pdo_pgsql`, `redis`, `curl`, `mbstring`, `json`
3. **PostgreSQL 16+** with pgvector (already set up from previous session)
4. **Redis 7+** for caching
5. **Nginx** or **Apache** web server
6. **Composer** (PHP package manager)
7. **SSL Certificate** (Let's Encrypt - free)

### Required Accounts
- Domain access (for `api.candidstudios.net` and `analytics.candidstudios.net`)
- Server SSH access
- Database credentials

---

## 🚀 Step-by-Step Deployment

### Step 1: Prepare Your Server (15 minutes)

**Connect to your server:**
```bash
ssh user@your-server.com
```

**Install required software:**
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.1 and extensions
sudo apt install -y php8.1 php8.1-fpm php8.1-pgsql php8.1-redis php8.1-curl php8.1-mbstring php8.1-json php8.1-cli

# Install Redis
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Nginx
sudo apt install -y nginx
```

**Verify installations:**
```bash
php -v                    # Should show PHP 8.1+
redis-cli ping           # Should return "PONG"
composer --version       # Should show Composer version
```

---

### Step 2: Deploy API Backend (20 minutes)

**Create directory:**
```bash
sudo mkdir -p /var/www/api.candidstudios.net
sudo chown -R $USER:$USER /var/www/api.candidstudios.net
```

**Copy API files to server:**
```bash
# From your local machine (Windows)
cd /mnt/c/code/candid-analytics-app/api
scp -r * user@your-server.com:/var/www/api.candidstudios.net/
```

**On the server, set up the API:**
```bash
cd /var/www/api.candidstudios.net

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Copy environment file
cp .env.example .env

# Edit environment variables
nano .env
```

**Configure .env file:**
```bash
# IMPORTANT: Update these values

# Database (your PostgreSQL from previous setup)
DB_HOST=localhost
DB_PORT=5432
DB_NAME=candid_analytics
DB_USER=candid_analytics_user
DB_PASSWORD=your-actual-database-password

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=

# JWT Secret (generate a random 64-character string)
JWT_SECRET=generate-a-very-long-random-string-here-64-characters-minimum
JWT_ALGORITHM=HS256
JWT_EXPIRES_IN=3600
JWT_REFRESH_EXPIRES_IN=2592000

# Frontend URL (where your React app will be)
FRONTEND_URL=https://analytics.candidstudios.net
ALLOWED_ORIGINS=https://analytics.candidstudios.net,http://localhost:5173

# App settings
APP_ENV=production
APP_DEBUG=false
```

**Save and exit** (Ctrl+X, then Y, then Enter)

**Create logs directory:**
```bash
mkdir -p logs
chmod 755 logs
```

**Set proper permissions:**
```bash
sudo chown -R www-data:www-data /var/www/api.candidstudios.net
sudo chmod -R 755 /var/www/api.candidstudios.net
sudo chmod 777 /var/www/api.candidstudios.net/logs
```

---

### Step 3: Configure Nginx (15 minutes)

**Create Nginx configuration:**
```bash
sudo nano /etc/nginx/sites-available/api.candidstudios.net
```

**Add this configuration:**
```nginx
server {
    listen 80;
    server_name api.candidstudios.net;
    root /var/www/api.candidstudios.net/public;
    index index.php;

    # Logs
    access_log /var/log/nginx/api.candidstudios.net-access.log;
    error_log /var/log/nginx/api.candidstudios.net-error.log;

    # API routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Hide PHP version
    fastcgi_hide_header X-Powered-By;

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }
}
```

**Enable the site:**
```bash
sudo ln -s /etc/nginx/sites-available/api.candidstudios.net /etc/nginx/sites-enabled/
sudo nginx -t                    # Test configuration
sudo systemctl reload nginx      # Reload Nginx
```

---

### Step 4: Set Up SSL Certificate (10 minutes)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Get SSL certificate (follow prompts)
sudo certbot --nginx -d api.candidstudios.net

# Certificate will auto-renew, but you can test:
sudo certbot renew --dry-run
```

---

### Step 5: Create Database Tables (if not done)

```bash
# Connect to PostgreSQL
sudo -u postgres psql -d candid_analytics

# Run the schema files from previous session
\i /path/to/candid-analytics/database/schema.sql
\i /path/to/candid-analytics/database/pgvector-ai-tables.sql
\i /path/to/candid-analytics/database/kpi-materialized-views.sql

# Create a user for API authentication (required for login)
INSERT INTO users (username, email, password_hash, role, status)
VALUES (
    'admin',
    'admin@candidstudios.net',
    '$2y$10$YourHashedPasswordHere',  -- See instructions below
    'admin',
    'active'
);

# Create refresh_tokens table (for JWT)
CREATE TABLE IF NOT EXISTS users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS refresh_tokens (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES users(id),
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    revoked_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

\q
```

**Generate password hash:**
```bash
php -r "echo password_hash('YourStrongPassword123!', PASSWORD_BCRYPT);"
```

Copy the output and use it in the INSERT statement above.

---

### Step 6: Test Your API (5 minutes)

**Test health check:**
```bash
curl https://api.candidstudios.net/api/health
```

**Expected response:**
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "version": "1.0.0",
    "timestamp": "2025-01-10T14:30:00+00:00"
  }
}
```

**Test login:**
```bash
curl -X POST https://api.candidstudios.net/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"YourStrongPassword123!"}'
```

**Expected response:**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJh...",
    "refresh_token": "abc123...",
    "expires_in": 3600,
    "user": {
      "id": "uuid-here",
      "username": "admin",
      "email": "admin@candidstudios.net",
      "role": "admin"
    }
  }
}
```

**Test KPI endpoint (with token):**
```bash
curl https://api.candidstudios.net/api/v1/kpis/priority \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN_HERE"
```

---

## ✅ API Endpoints Available

Once deployed, these endpoints are ready:

### Authentication
- `POST /api/auth/login` - Login
- `POST /api/auth/refresh` - Refresh token
- `POST /api/auth/logout` - Logout

### KPIs & Analytics
- `GET /api/v1/kpis/priority` - Priority KPIs
- `GET /api/v1/revenue?months=12` - Revenue analytics
- `GET /api/v1/revenue/by-location` - Revenue by city
- `GET /api/v1/sales-funnel?months=12` - Sales funnel
- `GET /api/v1/lead-sources` - Lead source performance
- `GET /api/v1/operations?months=12` - Operational efficiency
- `GET /api/v1/satisfaction?months=12` - Client satisfaction
- `GET /api/v1/satisfaction/retention` - Client retention
- `GET /api/v1/marketing?months=12` - Marketing performance
- `GET /api/v1/staff?months=6` - Staff productivity

### AI Features
- `GET /api/v1/ai/insights` - AI-powered insights
- `POST /api/v1/ai/predict-lead` - Predict lead conversion
- `GET /api/v1/ai/similar-clients/{clientId}` - Similar clients

### Webhooks
- `POST /api/webhooks/lead-capture` - Lead capture webhook
- `POST /api/webhooks/project-booked` - Project booked webhook

---

## 🐛 Troubleshooting

### Issue: "500 Internal Server Error"

**Solution 1:** Check PHP error log
```bash
sudo tail -f /var/log/nginx/api.candidstudios.net-error.log
sudo tail -f /var/www/api.candidstudios.net/logs/app.log
```

**Solution 2:** Check PHP-FPM is running
```bash
sudo systemctl status php8.1-fpm
sudo systemctl restart php8.1-fpm
```

### Issue: "Database connection failed"

**Solution:** Verify PostgreSQL credentials
```bash
# Test connection
psql -h localhost -U candid_analytics_user -d candid_analytics

# Check PostgreSQL is running
sudo systemctl status postgresql
```

### Issue: "Redis connection failed"

**Solution:** Check Redis service
```bash
sudo systemctl status redis-server
redis-cli ping    # Should return "PONG"
```

### Issue: "CORS error in browser"

**Solution:** Check `.env` file has correct `ALLOWED_ORIGINS`
```bash
ALLOWED_ORIGINS=https://analytics.candidstudios.net,http://localhost:5173
```

---

## 📊 Monitoring

**Check API logs:**
```bash
# Application logs
tail -f /var/www/api.candidstudios.net/logs/app.log

# Nginx access logs
sudo tail -f /var/log/nginx/api.candidstudios.net-access.log

# Nginx error logs
sudo tail -f /var/log/nginx/api.candidstudios.net-error.log
```

**Monitor Redis cache:**
```bash
redis-cli
> INFO stats
> KEYS kpis:*
> GET kpis:priority
```

**Monitor PostgreSQL performance:**
```bash
sudo -u postgres psql -d candid_analytics
SELECT * FROM pg_stat_activity WHERE datname = 'candid_analytics';
SELECT * FROM mv_priority_kpis LIMIT 1;  -- Test materialized view
```

---

## 🎯 Next Steps

### 1. Your API is Now Live! ✅

The backend API is fully deployed and ready to serve data.

### 2. Build React Frontend (Next Session)

Now that your API is working, the next step is:
- Create React dashboard with Vite + TypeScript
- Build 8 dashboard pages
- Connect to your API
- Deploy to Vercel

### 3. Connect Make.com Webhooks

Your webhook endpoints are ready at:
- `https://api.candidstudios.net/api/webhooks/lead-capture`
- `https://api.candidstudios.net/api/webhooks/project-booked`

---

## 📞 Need Help?

**Common Commands:**
```bash
# Restart all services
sudo systemctl restart nginx php8.1-fpm redis-server postgresql

# Check all service status
sudo systemctl status nginx php8.1-fpm redis-server postgresql

# View logs
sudo tail -f /var/www/api.candidstudios.net/logs/app.log

# Test API
curl https://api.candidstudios.net/api/health
```

---

## ✨ Success!

Your API backend is now live at: **https://api.candidstudios.net**

You can test it with:
- Health check: `https://api.candidstudios.net/api/health`
- Login: `https://api.candidstudios.net/api/auth/login`
- KPIs (after login): `https://api.candidstudios.net/api/v1/kpis/priority`

**Next:** Build the React frontend to visualize all this data! 🎉
