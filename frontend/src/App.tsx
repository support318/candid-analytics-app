import { Routes, Route, Navigate } from 'react-router-dom'
import { useAuthStore } from './store/authStore'
import Login from './pages/Login'
import DashboardLayout from './components/DashboardLayout'
import PriorityKPIsPage from './pages/PriorityKPIsPage'
import RevenuePage from './pages/RevenuePage'
import SalesFunnelPage from './pages/SalesFunnelPage'
import OperationsPage from './pages/OperationsPage'
import SatisfactionPage from './pages/SatisfactionPage'
import MarketingPage from './pages/MarketingPage'
import StaffPage from './pages/StaffPage'
import AIInsightsPage from './pages/AIInsightsPage'
import Profile from './pages/Profile'
import Users from './pages/Users'
import TwoFactorSetup from './pages/TwoFactorSetup'

// Protected Route Component
const ProtectedRoute = ({ children }: { children: React.ReactNode }) => {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated)

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />
  }

  return <>{children}</>
}

function App() {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated)

  return (
    <Routes>
      {/* Login Route */}
      <Route
        path="/login"
        element={
          isAuthenticated ? <Navigate to="/dashboard" replace /> : <Login />
        }
      />

      {/* Protected Dashboard Routes */}
      <Route
        path="/dashboard"
        element={
          <ProtectedRoute>
            <DashboardLayout />
          </ProtectedRoute>
        }
      >
        {/* Default dashboard redirect */}
        <Route index element={<Navigate to="/dashboard/kpis" replace />} />

        {/* Dashboard Pages */}
        <Route path="kpis" element={<PriorityKPIsPage />} />
        <Route path="revenue" element={<RevenuePage />} />
        <Route path="sales" element={<SalesFunnelPage />} />
        <Route path="operations" element={<OperationsPage />} />
        <Route path="satisfaction" element={<SatisfactionPage />} />
        <Route path="marketing" element={<MarketingPage />} />
        <Route path="staff" element={<StaffPage />} />
        <Route path="ai-insights" element={<AIInsightsPage />} />
        <Route path="profile" element={<Profile />} />
        <Route path="users" element={<Users />} />
      </Route>

      {/* Two-Factor Authentication Setup (Protected, but outside dashboard layout) */}
      <Route
        path="/two-factor-setup"
        element={
          <ProtectedRoute>
            <TwoFactorSetup />
          </ProtectedRoute>
        }
      />

      {/* Root redirect */}
      <Route path="/" element={<Navigate to="/dashboard" replace />} />

      {/* 404 fallback */}
      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  )
}

export default App
