export const API_CONFIG = {
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000',
  timeout: Number(import.meta.env.VITE_API_TIMEOUT) || 30000,
  headers: {
    'Content-Type': 'application/json',
  },
}

export const API_ENDPOINTS = {
  // Authentication
  auth: {
    login: '/api/auth/login',
    refresh: '/api/auth/refresh',
    logout: '/api/auth/logout',
  },

  // KPIs & Analytics
  kpis: {
    priority: '/api/v1/kpis/priority',
  },

  revenue: {
    analytics: '/api/v1/revenue',
    byLocation: '/api/v1/revenue/by-location',
  },

  sales: {
    funnel: '/api/v1/sales-funnel',
    leadSources: '/api/v1/lead-sources',
  },

  operations: {
    efficiency: '/api/v1/operations',
  },

  satisfaction: {
    metrics: '/api/v1/satisfaction',
    retention: '/api/v1/satisfaction/retention',
  },

  marketing: {
    performance: '/api/v1/marketing',
  },

  staff: {
    productivity: '/api/v1/staff',
  },

  // AI Features
  ai: {
    insights: '/api/v1/ai/insights',
    predictLead: '/api/v1/ai/predict-lead',
    similarClients: (clientId: string) => `/api/v1/ai/similar-clients/${clientId}`,
  },
}
