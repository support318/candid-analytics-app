import React, { useState, useEffect } from 'react';
import {
  Container,
  Paper,
  Typography,
  Box,
  TextField,
  Button,
  Alert,
  Grid,
  Divider,
  CircularProgress,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Chip
} from '@mui/material';
import {
  AccountCircle,
  Lock,
  Edit,
  Save,
  Cancel,
  Security,
  CheckCircle,
  Warning
} from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';

interface UserProfile {
  id: string;
  username: string;
  email: string;
  full_name: string | null;
  role: string;
  created_at: string;
  last_login: string | null;
  two_factor_enabled: boolean;
  two_factor_confirmed_at?: string | null;
}

const Profile: React.FC = () => {
  const navigate = useNavigate();
  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  // Profile editing state
  const [isEditingProfile, setIsEditingProfile] = useState(false);
  const [editedEmail, setEditedEmail] = useState('');
  const [editedFullName, setEditedFullName] = useState('');

  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  // 2FA state
  const [showDisable2FADialog, setShowDisable2FADialog] = useState(false);
  const [disable2FAPassword, setDisable2FAPassword] = useState('');
  const [disabling2FA, setDisabling2FA] = useState(false);

  useEffect(() => {
    fetchProfile();
  }, []);

  const fetchProfile = async () => {
    try {
      const response = await api.get('/api/v1/users/me');
      setProfile(response.data.data);
    } catch (err: any) {
      setError(err.response?.data?.error?.message || 'Failed to load profile');
    } finally {
      setLoading(false);
    }
  };

  const handleEditProfile = () => {
    if (profile) {
      setEditedEmail(profile.email);
      setEditedFullName(profile.full_name || '');
      setIsEditingProfile(true);
      setError(null);
      setSuccess(null);
    }
  };

  const handleCancelEdit = () => {
    setIsEditingProfile(false);
    setEditedEmail('');
    setEditedFullName('');
    setError(null);
    setSuccess(null);
  };

  const handleProfileUpdate = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setSuccess(null);

    // Validation
    if (!editedEmail) {
      setError('Email is required');
      return;
    }

    setUpdating(true);

    try {
      const response = await api.put('/api/v1/users/me', {
        email: editedEmail,
        full_name: editedFullName || null
      });

      setProfile(response.data.data);
      setSuccess('Profile updated successfully!');
      setIsEditingProfile(false);
    } catch (err: any) {
      setError(err.response?.data?.error?.message || 'Failed to update profile');
    } finally {
      setUpdating(false);
    }
  };

  const handlePasswordChange = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setSuccess(null);

    // Validation
    if (!currentPassword || !newPassword || !confirmPassword) {
      setError('All password fields are required');
      return;
    }

    if (newPassword !== confirmPassword) {
      setError('New passwords do not match');
      return;
    }

    if (newPassword.length < 8) {
      setError('Password must be at least 8 characters long');
      return;
    }

    setUpdating(true);

    try {
      await api.put('/api/v1/users/me/password', {
        current_password: currentPassword,
        new_password: newPassword
      });

      setSuccess('Password changed successfully!');
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
    } catch (err: any) {
      setError(err.response?.data?.error?.message || 'Failed to change password');
    } finally {
      setUpdating(false);
    }
  };

  const handleEnable2FA = () => {
    navigate('/two-factor-setup');
  };

  const handleOpenDisable2FADialog = () => {
    setShowDisable2FADialog(true);
    setDisable2FAPassword('');
    setError(null);
  };

  const handleCloseDisable2FADialog = () => {
    setShowDisable2FADialog(false);
    setDisable2FAPassword('');
    setError(null);
  };

  const handleDisable2FA = async () => {
    setError(null);
    setSuccess(null);

    if (!disable2FAPassword) {
      setError('Password is required to disable 2FA');
      return;
    }

    setDisabling2FA(true);

    try {
      await api.post('/api/v1/users/me/2fa/disable', {
        password: disable2FAPassword
      });

      setSuccess('Two-factor authentication has been disabled');
      setShowDisable2FADialog(false);
      setDisable2FAPassword('');

      // Refresh profile to update 2FA status
      await fetchProfile();
    } catch (err: any) {
      setError(err.response?.data?.error?.message || 'Failed to disable 2FA');
    } finally {
      setDisabling2FA(false);
    }
  };

  const getRoleBadgeColor = (role: string) => {
    switch (role) {
      case 'admin': return '#2e7d32';
      case 'manager': return '#1976d2';
      case 'viewer': return '#757575';
      default: return '#424242';
    }
  };

  if (loading) {
    return (
      <Container maxWidth="lg" sx={{ mt: 4, mb: 4 }}>
        <Box display="flex" justifyContent="center" alignItems="center" minHeight="400px">
          <CircularProgress />
        </Box>
      </Container>
    );
  }

  if (!profile) {
    return (
      <Container maxWidth="lg" sx={{ mt: 4, mb: 4 }}>
        <Alert severity="error">Failed to load profile</Alert>
      </Container>
    );
  }

  return (
    <Container maxWidth="lg" sx={{ mt: 4, mb: 4 }}>
      <Typography variant="h4" gutterBottom sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
        <AccountCircle /> My Profile
      </Typography>

      <Grid container spacing={3}>
        {/* Profile Information */}
        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 3 }}>
            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 1 }}>
              <Typography variant="h6">
                Profile Information
              </Typography>
              {!isEditingProfile && (
                <Button
                  startIcon={<Edit />}
                  onClick={handleEditProfile}
                  size="small"
                  variant="outlined"
                >
                  Edit
                </Button>
              )}
            </Box>
            <Divider sx={{ mb: 2 }} />

            {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
            {success && <Alert severity="success" sx={{ mb: 2 }}>{success}</Alert>}

            {isEditingProfile ? (
              <form onSubmit={handleProfileUpdate}>
                <TextField
                  fullWidth
                  label="Email"
                  type="email"
                  value={editedEmail}
                  onChange={(e) => setEditedEmail(e.target.value)}
                  margin="normal"
                  required
                />

                <TextField
                  fullWidth
                  label="Full Name"
                  value={editedFullName}
                  onChange={(e) => setEditedFullName(e.target.value)}
                  margin="normal"
                />

                <Box sx={{ mt: 3, display: 'flex', gap: 2 }}>
                  <Button
                    type="submit"
                    variant="contained"
                    color="primary"
                    startIcon={<Save />}
                    disabled={updating}
                    fullWidth
                  >
                    {updating ? <CircularProgress size={24} /> : 'Save Changes'}
                  </Button>
                  <Button
                    variant="outlined"
                    color="secondary"
                    startIcon={<Cancel />}
                    onClick={handleCancelEdit}
                    disabled={updating}
                    fullWidth
                  >
                    Cancel
                  </Button>
                </Box>
              </form>
            ) : (
              <>
                <Box sx={{ mb: 2 }}>
                  <Typography variant="body2" color="text.secondary">
                    Username
                  </Typography>
                  <Typography variant="body1" fontWeight="bold">
                    {profile.username}
                  </Typography>
                </Box>

                <Box sx={{ mb: 2 }}>
                  <Typography variant="body2" color="text.secondary">
                    Email
                  </Typography>
                  <Typography variant="body1" fontWeight="bold">
                    {profile.email}
                  </Typography>
                </Box>

                <Box sx={{ mb: 2 }}>
                  <Typography variant="body2" color="text.secondary">
                    Full Name
                  </Typography>
                  <Typography variant="body1" fontWeight="bold">
                    {profile.full_name || 'Not set'}
                  </Typography>
                </Box>

                <Box sx={{ mb: 2 }}>
                  <Typography variant="body2" color="text.secondary">
                    Role
                  </Typography>
                  <Box
                    component="span"
                    sx={{
                      display: 'inline-block',
                      px: 2,
                      py: 0.5,
                      borderRadius: 1,
                      backgroundColor: getRoleBadgeColor(profile.role),
                      color: 'white',
                      fontSize: '0.875rem',
                      fontWeight: 'bold',
                      textTransform: 'capitalize',
                      mt: 0.5
                    }}
                  >
                    {profile.role}
                  </Box>
                </Box>

                <Box sx={{ mb: 2 }}>
                  <Typography variant="body2" color="text.secondary">
                    Account Created
                  </Typography>
                  <Typography variant="body1">
                    {new Date(profile.created_at).toLocaleDateString()}
                  </Typography>
                </Box>

                {profile.last_login && (
                  <Box>
                    <Typography variant="body2" color="text.secondary">
                      Last Login
                    </Typography>
                    <Typography variant="body1">
                      {new Date(profile.last_login).toLocaleString()}
                    </Typography>
                  </Box>
                )}
              </>
            )}
          </Paper>
        </Grid>

        {/* Change Password */}
        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 3 }}>
            <Typography variant="h6" gutterBottom sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
              <Lock /> Change Password
            </Typography>
            <Divider sx={{ mb: 2 }} />

            {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
            {success && <Alert severity="success" sx={{ mb: 2 }}>{success}</Alert>}

            <form onSubmit={handlePasswordChange}>
              <TextField
                fullWidth
                type="password"
                label="Current Password"
                value={currentPassword}
                onChange={(e) => setCurrentPassword(e.target.value)}
                margin="normal"
                required
              />

              <TextField
                fullWidth
                type="password"
                label="New Password"
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
                margin="normal"
                required
                helperText="Minimum 8 characters"
              />

              <TextField
                fullWidth
                type="password"
                label="Confirm New Password"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                margin="normal"
                required
              />

              <Button
                type="submit"
                variant="contained"
                color="primary"
                fullWidth
                sx={{ mt: 3 }}
                disabled={updating}
              >
                {updating ? <CircularProgress size={24} /> : 'Change Password'}
              </Button>
            </form>
          </Paper>
        </Grid>

        {/* Two-Factor Authentication */}
        <Grid item xs={12}>
          <Paper sx={{ p: 3 }}>
            <Typography variant="h6" gutterBottom sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
              <Security /> Two-Factor Authentication
            </Typography>
            <Divider sx={{ mb: 2 }} />

            <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 2 }}>
              <Box>
                <Typography variant="body1" sx={{ mb: 1 }}>
                  Status: {profile.two_factor_enabled ? (
                    <Chip
                      label="Enabled"
                      color="success"
                      icon={<CheckCircle />}
                      size="small"
                      sx={{ ml: 1 }}
                    />
                  ) : (
                    <Chip
                      label="Disabled"
                      color="warning"
                      icon={<Warning />}
                      size="small"
                      sx={{ ml: 1 }}
                    />
                  )}
                </Typography>
                {profile.two_factor_enabled && profile.two_factor_confirmed_at && (
                  <Typography variant="body2" color="text.secondary">
                    Enabled on {new Date(profile.two_factor_confirmed_at).toLocaleString()}
                  </Typography>
                )}
                <Typography variant="body2" color="text.secondary" sx={{ mt: 1 }}>
                  {profile.two_factor_enabled
                    ? 'Your account is protected with two-factor authentication using time-based one-time passwords (TOTP).'
                    : 'Add an extra layer of security to your account by enabling two-factor authentication.'}
                </Typography>
              </Box>

              <Box>
                {profile.two_factor_enabled ? (
                  <Button
                    variant="outlined"
                    color="error"
                    onClick={handleOpenDisable2FADialog}
                  >
                    Disable 2FA
                  </Button>
                ) : (
                  <Button
                    variant="contained"
                    color="primary"
                    onClick={handleEnable2FA}
                    startIcon={<Security />}
                  >
                    Enable 2FA
                  </Button>
                )}
              </Box>
            </Box>

            {!profile.two_factor_enabled && (
              <Alert severity="info" sx={{ mt: 2 }}>
                <Typography variant="body2">
                  <strong>Recommended:</strong> Enable two-factor authentication to significantly improve your account security.
                  You'll need an authenticator app like Google Authenticator, Microsoft Authenticator, or Authy.
                </Typography>
              </Alert>
            )}
          </Paper>
        </Grid>
      </Grid>

      {/* Disable 2FA Confirmation Dialog */}
      <Dialog open={showDisable2FADialog} onClose={handleCloseDisable2FADialog} maxWidth="sm" fullWidth>
        <DialogTitle>Disable Two-Factor Authentication</DialogTitle>
        <DialogContent>
          {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}

          <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
            Are you sure you want to disable two-factor authentication? This will make your account less secure.
          </Typography>

          <TextField
            fullWidth
            type="password"
            label="Confirm Your Password"
            value={disable2FAPassword}
            onChange={(e) => setDisable2FAPassword(e.target.value)}
            autoFocus
            helperText="Enter your current password to confirm"
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={handleCloseDisable2FADialog} disabled={disabling2FA}>
            Cancel
          </Button>
          <Button
            onClick={handleDisable2FA}
            color="error"
            variant="contained"
            disabled={disabling2FA || !disable2FAPassword}
          >
            {disabling2FA ? <CircularProgress size={24} /> : 'Disable 2FA'}
          </Button>
        </DialogActions>
      </Dialog>
    </Container>
  );
};

export default Profile;
