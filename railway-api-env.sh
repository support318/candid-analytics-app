#!/bin/bash
# Railway API Service - Environment Variables Setup
# Run this after creating the API service

echo "Setting up API service environment variables..."

# Database - Reference Railway's Postgres service
railway variables --service api set DB_HOST='${{Postgres.PGHOST}}'
railway variables --service api set DB_PORT='${{Postgres.PGPORT}}'
railway variables --service api set DB_NAME='${{Postgres.PGDATABASE}}'
railway variables --service api set DB_USER='${{Postgres.PGUSER}}'
railway variables --service api set DB_PASSWORD='${{Postgres.PGPASSWORD}}'

# Redis - Reference Railway's Redis service
railway variables --service api set REDIS_HOST='${{Redis.REDIS_HOST}}'
railway variables --service api set REDIS_PORT='${{Redis.REDIS_PORT}}'

# JWT Secret
railway variables --service api set JWT_SECRET='7d89e2c45095738f0b32b2761c98cce60028ec2f8e16f357717099454c0c3469fb65be356a98dd5f138a74b64800f1e53cb8297697b9cd19be032729e70f8caa'

# App Configuration
railway variables --service api set APP_ENV='production'
railway variables --service api set APP_DEBUG='false'
railway variables --service api set APP_NAME='Candid Analytics API'

# GoHighLevel Integration
railway variables --service api set GHL_API_KEY='pit-4a0c3927-1650-44dd-b63d-2f65d81f84c3'
railway variables --service api set GHL_LOCATION_ID='GHJ0X5n0UomysnUPNfao'
railway variables --service api set GHL_API_BASE_URL='https://services.leadconnectorhq.com'
railway variables --service api set GHL_API_VERSION='2021-07-28'

# CORS & Frontend URL
railway variables --service api set FRONTEND_URL='https://analytics.candidstudios.net'
railway variables --service api set ALLOWED_ORIGINS='https://analytics.candidstudios.net,https://${{RAILWAY_STATIC_URL}}'

# Security
railway variables --service api set SESSION_LIFETIME='86400'
railway variables --service api set SESSION_SECURE='true'
railway variables --service api set SESSION_HTTP_ONLY='true'

# Rate Limiting
railway variables --service api set RATE_LIMIT_ENABLED='true'
railway variables --service api set RATE_LIMIT_MAX_REQUESTS='100'

echo "✅ API environment variables configured!"
echo "Note: You may need to redeploy the service for changes to take effect"
