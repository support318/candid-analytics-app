#!/bin/bash
# Railway Frontend Service - Environment Variables Setup
# Run this after creating the Frontend service

echo "Setting up Frontend service environment variables..."

# API URL
railway variables --service frontend set VITE_API_URL='https://api.candidstudios.net'

# App Configuration
railway variables --service frontend set VITE_APP_NAME='Candid Analytics'
railway variables --service frontend set VITE_APP_VERSION='1.0.0'
railway variables --service frontend set VITE_APP_ENV='production'
railway variables --service frontend set VITE_API_TIMEOUT='30000'

# Feature Flags
railway variables --service frontend set VITE_ENABLE_AI_FEATURES='true'
railway variables --service frontend set VITE_ENABLE_REAL_TIME='false'
railway variables --service frontend set VITE_ENABLE_ANALYTICS='true'

echo "✅ Frontend environment variables configured!"
echo "Note: You may need to redeploy the service for changes to take effect"
