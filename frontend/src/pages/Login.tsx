import { useState } from 'react'
import {
  Box,
  Button,
  Card,
  CardContent,
  Container,
  TextField,
  Typography,
  Alert,
  CircularProgress,
} from '@mui/material'
import { Lock as LockIcon } from '@mui/icons-material'
import { useAuth } from '../hooks/useAuth'

const Login = () => {
  const [username, setUsername] = useState('')
  const [password, setPassword] = useState('')
  const { login, isLoggingIn, loginError } = useAuth()

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    login({ username, password })
  }

  return (
    <Box
      sx={{
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      }}
    >
      <Container maxWidth="sm">
        <Card sx={{ boxShadow: 6 }}>
          <CardContent sx={{ p: 4 }}>
            {/* Header */}
            <Box sx={{ textAlign: 'center', mb: 4 }}>
              <Box
                sx={{
                  display: 'inline-flex',
                  p: 2,
                  borderRadius: '50%',
                  bgcolor: 'primary.main',
                  color: 'white',
                  mb: 2,
                }}
              >
                <LockIcon sx={{ fontSize: 40 }} />
              </Box>
              <Typography variant="h4" component="h1" gutterBottom>
                Candid Analytics
              </Typography>
              <Typography variant="body2" color="text.secondary">
                Sign in to access your dashboard
              </Typography>
            </Box>

            {/* Error Alert */}
            {loginError && (
              <Alert severity="error" sx={{ mb: 3 }}>
                {loginError}
              </Alert>
            )}

            {/* Login Form */}
            <form onSubmit={handleSubmit}>
              <TextField
                fullWidth
                label="Username"
                variant="outlined"
                margin="normal"
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                disabled={isLoggingIn}
                required
                autoFocus
              />

              <TextField
                fullWidth
                label="Password"
                type="password"
                variant="outlined"
                margin="normal"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                disabled={isLoggingIn}
                required
              />

              <Button
                fullWidth
                type="submit"
                variant="contained"
                size="large"
                disabled={isLoggingIn}
                sx={{ mt: 3, mb: 2, py: 1.5 }}
              >
                {isLoggingIn ? (
                  <CircularProgress size={24} color="inherit" />
                ) : (
                  'Sign In'
                )}
              </Button>
            </form>

            {/* Footer */}
            <Typography
              variant="caption"
              color="text.secondary"
              align="center"
              display="block"
              sx={{ mt: 3 }}
            >
              © {new Date().getFullYear()} Candid Studios. All rights reserved.
            </Typography>
          </CardContent>
        </Card>
      </Container>
    </Box>
  )
}

export default Login
