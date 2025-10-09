# Candid Analytics Dashboard - React Frontend

Professional analytics dashboard built with React, TypeScript, and Material-UI for Candid Studios.

## 🎯 What This Is

A modern, responsive React Single Page Application (SPA) that connects to your PHP API backend to display comprehensive business analytics with beautiful charts and visualizations.

## ✨ Features

### 8 Dashboard Pages
1. **Priority KPIs** - Real-time key performance indicators
2. **Revenue Analytics** - Revenue trends and location breakdown
3. **Sales Funnel** - Lead progression and conversion tracking
4. **Operations** - Delivery times and project status
5. **Client Satisfaction** - Ratings, NPS, and retention metrics
6. **Marketing Performance** - Email, social, and ad campaign ROI
7. **Staff Productivity** - Team performance and efficiency
8. **AI Insights** - Machine learning-powered recommendations

### Key Features
- 🔐 JWT authentication with automatic token refresh
- 📊 Interactive charts and visualizations (Recharts)
- 🎨 Material-UI component library
- ⚡ React Query for efficient data fetching and caching
- 📱 Fully responsive design (mobile, tablet, desktop)
- 🚀 Fast loading with Vite build tool
- 💾 Persistent auth state with Zustand
- 🔄 Real-time data updates (1-minute refresh interval)

## 🛠 Technology Stack

- **React 18** - UI library
- **TypeScript** - Type safety
- **Vite** - Build tool and dev server
- **Material-UI (MUI)** - Component library
- **React Router v6** - Navigation
- **TanStack Query (React Query)** - Data fetching
- **Zustand** - State management
- **Axios** - HTTP client
- **Recharts** - Data visualization
- **date-fns** - Date formatting

## 📋 Prerequisites

- Node.js 18+ and npm
- Running API backend (see `/api` folder)
- API URL (local or deployed)

## 🚀 Getting Started

### 1. Install Dependencies

```bash
cd /mnt/c/code/candid-analytics-app/frontend
npm install
```

### 2. Configure Environment

Create `.env` file from example:

```bash
cp .env.example .env
```

Edit `.env` with your API URL:

```bash
# For local development
VITE_API_URL=http://localhost:8000

# For production
VITE_API_URL=https://api.candidstudios.net
```

### 3. Run Development Server

```bash
npm run dev
```

The app will open at `http://localhost:5173`

### 4. Build for Production

```bash
npm run build
```

Built files will be in the `dist/` folder.

### 5. Preview Production Build

```bash
npm run preview
```

## 📁 Project Structure

```
frontend/
├── src/
│   ├── components/          # Reusable UI components
│   │   ├── DashboardLayout.tsx    # Main layout with navigation
│   │   ├── StatCard.tsx           # KPI card component
│   │   ├── LoadingSpinner.tsx     # Loading state
│   │   └── ErrorAlert.tsx         # Error display
│   │
│   ├── pages/              # Dashboard pages
│   │   ├── Login.tsx              # Login page
│   │   ├── PriorityKPIsPage.tsx   # Priority KPIs dashboard
│   │   ├── RevenuePage.tsx        # Revenue analytics
│   │   ├── SalesFunnelPage.tsx    # Sales funnel
│   │   ├── OperationsPage.tsx     # Operations metrics
│   │   ├── SatisfactionPage.tsx   # Client satisfaction
│   │   ├── MarketingPage.tsx      # Marketing performance
│   │   ├── StaffPage.tsx          # Staff productivity
│   │   └── AIInsightsPage.tsx     # AI insights
│   │
│   ├── hooks/              # Custom React hooks
│   │   ├── useAuth.ts             # Authentication hook
│   │   └── useAnalytics.ts        # Data fetching hooks
│   │
│   ├── store/              # State management
│   │   └── authStore.ts           # Zustand auth store
│   │
│   ├── lib/                # Utilities
│   │   └── apiClient.ts           # Axios configuration
│   │
│   ├── config/             # Configuration
│   │   └── api.ts                 # API endpoints
│   │
│   ├── types/              # TypeScript types
│   │   └── index.ts               # Type definitions
│   │
│   ├── App.tsx             # Main app component with routing
│   ├── main.tsx            # App entry point
│   ├── theme.ts            # Material-UI theme
│   └── index.css           # Global styles
│
├── public/                 # Static assets
├── dist/                   # Production build output
├── package.json            # Dependencies
├── tsconfig.json           # TypeScript config
├── vite.config.ts          # Vite config
├── vercel.json             # Vercel deployment config
└── README.md               # This file
```

## 🔐 Authentication Flow

1. User enters username/password on Login page
2. Credentials sent to API `/api/auth/login`
3. API returns JWT access token + refresh token
4. Tokens stored in Zustand store (persisted to localStorage)
5. Access token attached to all API requests via Axios interceptor
6. If access token expires (401), automatically refresh using refresh token
7. If refresh fails, redirect to login page

## 📊 Data Fetching Strategy

- **React Query** manages all server state
- **5-minute cache** for dashboard data (balances freshness and performance)
- **Automatic refetch** on window focus (configurable)
- **Priority KPIs** refetch every 60 seconds for real-time updates
- **Error handling** with retry logic
- **Loading states** for better UX

## 🎨 Styling & Theming

The app uses Material-UI's theming system. Customize colors in `src/theme.ts`:

```typescript
const theme = createTheme({
  palette: {
    primary: { main: '#1976d2' },    // Blue
    secondary: { main: '#9c27b0' },  // Purple
    success: { main: '#2e7d32' },    // Green
    // ... more colors
  },
})
```

## 📱 Responsive Design

- **Mobile**: < 600px (single column layout)
- **Tablet**: 600px - 960px (2 columns)
- **Desktop**: > 960px (3-4 columns, sidebar always visible)

## 🚀 Deployment

### Option 1: Vercel (Recommended)

1. **Install Vercel CLI:**
   ```bash
   npm install -g vercel
   ```

2. **Login to Vercel:**
   ```bash
   vercel login
   ```

3. **Deploy:**
   ```bash
   cd /mnt/c/code/candid-analytics-app/frontend
   vercel
   ```

4. **Set Environment Variables:**
   Go to Vercel dashboard → Project Settings → Environment Variables

   Add:
   ```
   VITE_API_URL = https://api.candidstudios.net
   ```

5. **Deploy to Production:**
   ```bash
   vercel --prod
   ```

Your dashboard will be live at `https://your-project.vercel.app`

### Option 2: Netlify

1. **Install Netlify CLI:**
   ```bash
   npm install -g netlify-cli
   ```

2. **Build the app:**
   ```bash
   npm run build
   ```

3. **Deploy:**
   ```bash
   netlify deploy --prod --dir=dist
   ```

### Option 3: Manual Deployment

1. **Build the app:**
   ```bash
   npm run build
   ```

2. **Upload `dist/` folder** to your web server

3. **Configure web server** to serve `index.html` for all routes (SPA routing)

**Nginx example:**
```nginx
location / {
    try_files $uri $uri/ /index.html;
}
```

## 🔧 Configuration

### API Endpoints

Edit `src/config/api.ts` to customize API endpoints:

```typescript
export const API_ENDPOINTS = {
  auth: {
    login: '/api/auth/login',
    // ...
  },
  kpis: {
    priority: '/api/v1/kpis/priority',
  },
  // ...
}
```

### Cache Duration

Adjust cache times in `src/main.tsx`:

```typescript
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000, // 5 minutes
    },
  },
})
```

## 🐛 Troubleshooting

### "Failed to fetch" errors

**Problem:** Cannot connect to API

**Solution:**
1. Verify API URL in `.env` is correct
2. Check API backend is running
3. Verify CORS is configured in API (see API `public/index.php`)
4. Check browser console for specific errors

### Authentication loops (infinite redirects)

**Problem:** Login succeeds but immediately logs out

**Solution:**
1. Check JWT token is being stored (check localStorage in browser dev tools)
2. Verify API returns correct token format
3. Check API `/api/v1/kpis/priority` endpoint works with token

### Charts not displaying

**Problem:** Charts show blank or "No data"

**Solution:**
1. Check API endpoints return data (test with curl or Postman)
2. Verify data format matches TypeScript types in `src/types/index.ts`
3. Check browser console for errors

### Build fails

**Problem:** `npm run build` fails

**Solution:**
1. Delete `node_modules` and reinstall: `rm -rf node_modules && npm install`
2. Clear Vite cache: `rm -rf node_modules/.vite`
3. Check for TypeScript errors: `npm run lint`

## 📝 Development Tips

### Adding a New Dashboard Page

1. **Create page component** in `src/pages/NewPage.tsx`
2. **Add route** in `src/App.tsx`
3. **Add navigation item** in `src/components/DashboardLayout.tsx`
4. **Create data hook** in `src/hooks/useAnalytics.ts` (if needed)
5. **Add types** in `src/types/index.ts`

### Testing with Mock Data

For development without API, you can mock data:

```typescript
// In src/hooks/useAnalytics.ts
export const usePriorityKPIs = () => {
  return useQuery({
    queryKey: ['kpis', 'priority'],
    queryFn: async () => {
      // Return mock data instead of API call
      return {
        today_revenue: 5000,
        today_bookings: 3,
        // ...
      }
    },
  })
}
```

## 📞 Support

If you encounter issues:

1. Check API logs: `/var/www/api.candidstudios.net/logs/app.log`
2. Check browser console for JavaScript errors
3. Verify environment variables are set correctly
4. Test API endpoints directly with curl or Postman

## ✅ What's Included

- ✅ Complete React app with TypeScript
- ✅ 8 fully functional dashboard pages
- ✅ JWT authentication with token refresh
- ✅ Material-UI component library
- ✅ Recharts data visualizations
- ✅ Responsive design (mobile, tablet, desktop)
- ✅ Production-ready build configuration
- ✅ Vercel deployment configuration
- ✅ Comprehensive error handling
- ✅ Loading states for all pages
- ✅ Type-safe API client

## 🎯 Next Steps

1. **Deploy the API backend** (see `/api/DEPLOYMENT-GUIDE.md`)
2. **Test the frontend locally** with `npm run dev`
3. **Deploy the frontend** to Vercel or Netlify
4. **Configure custom domain** (analytics.candidstudios.net)
5. **Connect Make.com webhooks** to API endpoints

---

**Built with ❤️ for Candid Studios**

For more information, see the main project documentation in `/START-HERE.md`
