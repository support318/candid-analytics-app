@echo off
echo.
echo ========================================
echo 🚀 Candid Analytics - Vercel Deployment
echo ========================================
echo.

cd /d "%~dp0"

REM Check if vercel is installed
where vercel >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo 📦 Installing Vercel CLI...
    call npm install -g vercel
    echo ✅ Vercel CLI installed
    echo.
)

REM Check if logged in
echo 🔐 Checking Vercel authentication...
vercel whoami >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo Please login to Vercel:
    call vercel login
    echo ✅ Logged in successfully
    echo.
)

REM Deploy
echo 🚀 Deploying to Vercel...
echo.
call vercel --prod

echo.
echo ========================================
echo 🎉 Deployment Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Copy your production URL from above
echo 2. Go to Vercel dashboard
echo 3. Settings -^> Environment Variables
echo 4. Add: VITE_API_URL = https://api.candidstudios.net
echo 5. Redeploy: vercel --prod
echo.
echo Your dashboard will be live in ~2 minutes! 🚀
echo.

pause
