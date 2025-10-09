// Authentication Types
export interface User {
  id: string
  username: string
  email: string
  role: string
}

export interface LoginCredentials {
  username: string
  password: string
}

export interface AuthResponse {
  success: boolean
  data: {
    access_token: string
    refresh_token: string
    expires_in: number
    user: User
  }
}

export interface ApiResponse<T> {
  success: boolean
  data: T
  meta?: {
    cached?: boolean
    timestamp?: string
  }
}

export interface ApiError {
  success: false
  error: {
    code: string
    message: string
    details?: Record<string, unknown>
  }
}

// KPI Types
export interface PriorityKPI {
  metric_name: string
  current_value: number
  previous_value: number
  change_percentage: number
  trend: 'up' | 'down' | 'stable'
  target_value?: number
  unit: string
}

export interface PriorityKPIs {
  today_bookings: number
  today_revenue: number
  month_revenue: number
  month_bookings: number
  conversion_rate: number
  avg_booking_value: number
  leads_in_pipeline: number
  projects_in_progress: number
  avg_delivery_time_days: number
  client_satisfaction_score: number
  updated_at: string
}

// Revenue Types
export interface RevenueAnalytics {
  month: string
  total_revenue: number
  booking_count: number
  avg_booking_value: number
  yoy_growth: number
}

export interface RevenueByLocation {
  location: string
  revenue: number
  booking_count: number
  percentage: number
}

// Sales Funnel Types
export interface SalesFunnel {
  stage: string
  count: number
  value: number
  conversion_rate: number
}

export interface LeadSource {
  source: string
  leads: number
  conversions: number
  conversion_rate: number
  revenue: number
}

// Operations Types
export interface OperationsMetrics {
  avg_photo_delivery_days: number
  avg_video_delivery_days: number
  avg_first_response_hours: number
  projects_on_time: number
  projects_delayed: number
  on_time_percentage: number
}

// Satisfaction Types
export interface SatisfactionMetrics {
  avg_rating: number
  total_reviews: number
  nps_score: number
  repeat_booking_rate: number
  referral_rate: number
}

export interface RetentionMetrics {
  month: string
  repeat_clients: number
  total_clients: number
  retention_rate: number
}

// Marketing Types
export interface MarketingMetrics {
  email_campaigns: number
  email_open_rate: number
  email_click_rate: number
  social_posts: number
  social_engagement_rate: number
  ad_spend: number
  ad_revenue: number
  roi: number
}

// Staff Types
export interface StaffProductivity {
  staff_id: string
  staff_name: string
  projects_completed: number
  avg_client_rating: number
  revenue_generated: number
  efficiency_score: number
}

// AI Types
export interface AIInsight {
  insight_type: string
  title: string
  description: string
  impact: 'high' | 'medium' | 'low'
  recommendation: string
  confidence: number
}

export interface LeadPrediction {
  lead_id: string
  conversion_probability: number
  estimated_value: number
  recommended_actions: string[]
  factors: Record<string, number>
}

export interface SimilarClient {
  client_id: string
  client_name: string
  similarity_score: number
  shared_attributes: string[]
}

// Chart Data Types
export interface ChartDataPoint {
  name: string
  value: number
  [key: string]: string | number
}

export interface TimeSeriesDataPoint {
  date: string
  value: number
  [key: string]: string | number
}
