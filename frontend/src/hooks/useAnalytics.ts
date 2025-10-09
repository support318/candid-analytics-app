import { useQuery } from '@tanstack/react-query'
import { apiClient } from '../lib/apiClient'
import { API_ENDPOINTS } from '../config/api'
import {
  ApiResponse,
  PriorityKPIs,
  RevenueAnalytics,
  RevenueByLocation,
  SalesFunnel,
  LeadSource,
  OperationsMetrics,
  SatisfactionMetrics,
  RetentionMetrics,
  MarketingMetrics,
  StaffProductivity,
  AIInsight,
  LeadPrediction,
  SimilarClient,
} from '../types'

// Priority KPIs
export const usePriorityKPIs = () => {
  return useQuery({
    queryKey: ['kpis', 'priority'],
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<PriorityKPIs>>(
        API_ENDPOINTS.kpis.priority
      )
      return response.data.data
    },
    refetchInterval: 60000, // Refetch every minute
  })
}

// Revenue Analytics
export const useRevenueAnalytics = (months: number = 12) => {
  return useQuery({
    queryKey: ['revenue', 'analytics', months],
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<RevenueAnalytics[]>>(
        `${API_ENDPOINTS.revenue.analytics}?months=${months}`
      )
      return response.data.data
    },
  })
}

export const useRevenueByLocation = () => {
  return useQuery({
    queryKey: ['revenue', 'by-location'],
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<RevenueByLocation[]>>(
        API_ENDPOINTS.revenue.byLocation
      )
      return response.data.data
    },
  })
}

// Sales Funnel
export const useSalesFunnel = (months: number = 12) => {
  return useQuery({
    queryKey: ['sales', 'funnel', months],
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<SalesFunnel[]>>(
        `${API_ENDPOINTS.sales.funnel}?months=${months}`
      )
      return response.data.data
    },
  })
}

export const useLeadSources = () => {
  return useQuery({
    queryKey: ['sales', 'lead-sources'],
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<LeadSource[]>>(
        API_ENDPOINTS.sales.leadSources
      )
      return response.data.data
    },
  })
}

// Operations
export const useOperations = (months: number = 12) => {
  return useQuery({
    queryKey: ['operations', months],
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<OperationsMetrics>>(
        `${API_ENDPOINTS.operations.efficiency}?months=${months}`
      )
      return response.data.data
    },
  })
}

// Satisfaction
export const useSatisfaction = (months: number = 12) => {
  return useQuery({
    queryKey: ['satisfaction', 'metrics', months],
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<SatisfactionMetrics>>(
        `${API_ENDPOINTS.satisfaction.metrics}?months=${months}`
      )
      return response.data.data
    },
  })
}

export const useRetention = () => {
  return useQuery({
    queryKey: ['satisfaction', 'retention'],
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<RetentionMetrics[]>>(
        API_ENDPOINTS.satisfaction.retention
      )
      return response.data.data
    },
  })
}

// Marketing
export const useMarketing = (months: number = 12) => {
  return useQuery({
    queryKey: ['marketing', months],
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<MarketingMetrics>>(
        `${API_ENDPOINTS.marketing.performance}?months=${months}`
      )
      return response.data.data
    },
  })
}

// Staff
export const useStaff = (months: number = 6) => {
  return useQuery({
    queryKey: ['staff', months],
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<StaffProductivity[]>>(
        `${API_ENDPOINTS.staff.productivity}?months=${months}`
      )
      return response.data.data
    },
  })
}

// AI Features
export const useAIInsights = () => {
  return useQuery({
    queryKey: ['ai', 'insights'],
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<AIInsight[]>>(
        API_ENDPOINTS.ai.insights
      )
      return response.data.data
    },
  })
}

export const usePredictLead = (leadData: Record<string, unknown>) => {
  return useQuery({
    queryKey: ['ai', 'predict-lead', leadData],
    queryFn: async () => {
      const response = await apiClient.post<ApiResponse<LeadPrediction>>(
        API_ENDPOINTS.ai.predictLead,
        leadData
      )
      return response.data.data
    },
    enabled: false, // Manual trigger
  })
}

export const useSimilarClients = (clientId: string) => {
  return useQuery({
    queryKey: ['ai', 'similar-clients', clientId],
    queryFn: async () => {
      const response = await apiClient.get<ApiResponse<SimilarClient[]>>(
        API_ENDPOINTS.ai.similarClients(clientId)
      )
      return response.data.data
    },
    enabled: !!clientId,
  })
}
