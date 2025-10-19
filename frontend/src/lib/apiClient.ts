import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios'
import { API_CONFIG, API_ENDPOINTS } from '../config/api'
import { useAuthStore } from '../store/authStore'
import { ApiError, ApiResponse } from '../types'

// Create axios instance
export const apiClient = axios.create({
  baseURL: API_CONFIG.baseURL,
  timeout: API_CONFIG.timeout,
  headers: API_CONFIG.headers,
  withCredentials: true,
})

// Request interceptor - Add JWT token
apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    // Read directly from localStorage to avoid Zustand hydration timing issues
    const authStore = localStorage.getItem('auth-storage')
    if (authStore) {
      try {
        const { state } = JSON.parse(authStore)
        const accessToken = state?.accessToken

        if (accessToken && config.headers) {
          config.headers.Authorization = `Bearer ${accessToken}`
        }
      } catch (error) {
        console.error('Error parsing auth storage:', error)
      }
    }

    return config
  },
  (error) => Promise.reject(error)
)

// Response interceptor - Handle token refresh
apiClient.interceptors.response.use(
  (response) => response,
  async (error: AxiosError<ApiError>) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & {
      _retry?: boolean
    }

    // If 401 and not already retried, try to refresh token
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true

      try {
        const { refreshToken, updateAccessToken, clearAuth } =
          useAuthStore.getState()

        if (!refreshToken) {
          clearAuth()
          window.location.href = '/login'
          return Promise.reject(error)
        }

        // Try to refresh token
        const response = await axios.post<ApiResponse<{ access_token: string }>>(
          `${API_CONFIG.baseURL}${API_ENDPOINTS.auth.refresh}`,
          { refresh_token: refreshToken }
        )

        if (response.data.success) {
          const newAccessToken = response.data.data.access_token
          updateAccessToken(newAccessToken)

          // Retry original request with new token
          if (originalRequest.headers) {
            originalRequest.headers.Authorization = `Bearer ${newAccessToken}`
          }

          return apiClient(originalRequest)
        } else {
          clearAuth()
          window.location.href = '/login'
        }
      } catch (refreshError) {
        useAuthStore.getState().clearAuth()
        window.location.href = '/login'
        return Promise.reject(refreshError)
      }
    }

    return Promise.reject(error)
  }
)

// Helper function to extract error message
export const getErrorMessage = (error: unknown): string => {
  if (axios.isAxiosError(error)) {
    const apiError = error.response?.data as ApiError
    return apiError?.error?.message || error.message
  }
  return 'An unexpected error occurred'
}

// Helper function to check if response is successful
export const isApiSuccess = <T>(
  response: ApiResponse<T> | ApiError
): response is ApiResponse<T> => {
  return response.success === true
}
