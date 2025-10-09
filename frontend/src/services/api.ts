import axios from 'axios';

// Create axios instance with default config
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'https://api.candidstudios.net',
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true,
});

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const authStore = localStorage.getItem('auth-storage');
    if (authStore) {
      try {
        const { state } = JSON.parse(authStore);
        const token = state?.accessToken;
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
      } catch (error) {
        console.error('Error parsing auth storage:', error);
      }
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor for handling errors
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    // If error is 401 and we haven't retried yet
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      try {
        // Try to refresh token
        const authStore = localStorage.getItem('auth-storage');
        if (authStore) {
          const { state } = JSON.parse(authStore);
          const refreshToken = state?.refreshToken;

          if (refreshToken) {
            const response = await axios.post(
              `${import.meta.env.VITE_API_URL || 'https://api.candidstudios.net'}/api/auth/refresh`,
              { refresh_token: refreshToken }
            );

            const { access_token, refresh_token } = response.data.data;

            // Update tokens in storage
            state.accessToken = access_token;
            state.refreshToken = refresh_token;
            localStorage.setItem('auth-storage', JSON.stringify({ state }));

            // Retry original request with new token
            originalRequest.headers.Authorization = `Bearer ${access_token}`;
            return api(originalRequest);
          }
        }
      } catch (refreshError) {
        // If refresh fails, clear auth and redirect to login
        localStorage.removeItem('auth-storage');
        window.location.href = '/login';
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  }
);

export default api;
