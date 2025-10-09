import { useMutation } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { apiClient, getErrorMessage } from '../lib/apiClient'
import { useAuthStore } from '../store/authStore'
import { API_ENDPOINTS } from '../config/api'
import { LoginCredentials, AuthResponse } from '../types'

export const useAuth = () => {
  const navigate = useNavigate()
  const { setAuth, clearAuth, isAuthenticated, user } = useAuthStore()

  const loginMutation = useMutation({
    mutationFn: async (credentials: LoginCredentials) => {
      const response = await apiClient.post<AuthResponse>(
        API_ENDPOINTS.auth.login,
        credentials
      )
      return response.data
    },
    onSuccess: (data) => {
      if (data.success) {
        setAuth(
          data.data.user,
          data.data.access_token,
          data.data.refresh_token
        )
        navigate('/dashboard')
      }
    },
  })

  const logoutMutation = useMutation({
    mutationFn: async () => {
      const response = await apiClient.post(API_ENDPOINTS.auth.logout)
      return response.data
    },
    onSuccess: () => {
      clearAuth()
      navigate('/login')
    },
    onError: () => {
      // Clear auth even if logout request fails
      clearAuth()
      navigate('/login')
    },
  })

  const login = (credentials: LoginCredentials) => {
    return loginMutation.mutate(credentials)
  }

  const logout = () => {
    return logoutMutation.mutate()
  }

  return {
    user,
    isAuthenticated,
    login,
    logout,
    isLoggingIn: loginMutation.isPending,
    isLoggingOut: logoutMutation.isPending,
    loginError: loginMutation.error
      ? getErrorMessage(loginMutation.error)
      : null,
  }
}
