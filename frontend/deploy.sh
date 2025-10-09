#!/bin/bash

echo "🚀 Candid Analytics - Vercel Deployment Script"
echo "=============================================="
echo ""

# Check if in correct directory
if [ ! -f "package.json" ]; then
    echo "❌ Error: Please run this script from the frontend directory"
    echo "Run: cd /mnt/c/code/candid-analytics-app/frontend"
    exit 1
fi

echo "✅ Correct directory confirmed"
echo ""

# Check if vercel is installed
if ! command -v vercel &> /dev/null; then
    echo "📦 Installing Vercel CLI..."
    npm install -g vercel
    echo "✅ Vercel CLI installed"
    echo ""
fi

# Check if logged in
echo "🔐 Checking Vercel authentication..."
if ! vercel whoami &> /dev/null; then
    echo "Please login to Vercel:"
    vercel login
    echo "✅ Logged in successfully"
    echo ""
fi

# Deploy
echo "🚀 Deploying to Vercel..."
echo ""
vercel --prod

echo ""
echo "=============================================="
echo "🎉 Deployment Complete!"
echo "=============================================="
echo ""
echo "Next steps:"
echo "1. Copy your production URL from above"
echo "2. Go to Vercel dashboard → Settings → Environment Variables"
echo "3. Add: VITE_API_URL = https://api.candidstudios.net"
echo "4. Redeploy: vercel --prod"
echo ""
echo "Your dashboard will be live in ~2 minutes! 🚀"
