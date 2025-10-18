#!/bin/bash
# Railway Deployment Setup Script
# Run this after creating the Railway project

set -e

echo "🚀 Candid Analytics - Railway Deployment Setup"
echo "=============================================="
echo ""

# Check if we're in the right directory
if [ ! -f "railway.toml" ]; then
    echo "❌ Error: railway.toml not found. Please run this from the project root."
    exit 1
fi

echo "✅ Found railway.toml"
echo ""

# Check if logged in to Railway
if ! railway whoami &> /dev/null; then
    echo "❌ Not logged in to Railway. Please run: railway login"
    exit 1
fi

echo "✅ Logged in to Railway as: $(railway whoami)"
echo ""

echo "📋 Setup Instructions:"
echo ""
echo "Since Railway CLI doesn't support adding services via CLI, you'll need to:"
echo ""
echo "1. Open Railway Dashboard: https://railway.app/project/$(railway status 2>&1 | grep -oP 'Project: \K[a-f0-9-]+')"
echo ""
echo "2. Add these services:"
echo "   - PostgreSQL (Database)"
echo "   - Redis (Database)"
echo "   - API Backend (from GitHub repo)"
echo "   - Frontend (from GitHub repo)"
echo ""
echo "3. Or use these commands to open the dashboard:"
echo ""
echo "   railway open"
echo ""
echo "Press Enter to open Railway dashboard now..."
read

railway open

echo ""
echo "✅ Dashboard opened!"
echo ""
echo "Next: Follow the instructions in RAILWAY-QUICKSTART.md"
