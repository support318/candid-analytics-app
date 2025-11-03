import React, { useState, useEffect } from 'react';
import {
  Container,
  Paper,
  Typography,
  Box,
  TextField,
  Button,
  Alert,
  Stepper,
  Step,
  StepLabel,
  List,
  ListItem,
  ListItemIcon,
  ListItemText,
  Divider,
  CircularProgress
} from '@mui/material';
import {
  Security,
  QrCode,
  CheckCircle,
  ContentCopy,
  Download
} from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';

interface SetupResponse {
  secret: string;
  qr_code_uri: string;
  manual_entry_key: string;
  backup_codes: string[];
}

const TwoFactorSetup: React.FC = () => {
  const navigate = useNavigate();
  const [activeStep, setActiveStep] = useState(0);
  const [setupData, setSetupData] = useState<SetupResponse | null>(null);
  const [verificationCode, setVerificationCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);

  const steps = ['Generate Secret', 'Scan QR Code', 'Verify Code', 'Save Backup Codes'];

  useEffect(() => {
    initiateSetup();
  }, []);

  const initiateSetup = async () => {
    setLoading(true);
    setError(null);

    try {
      const response = await api.post('/api/v1/users/me/2fa/setup');
      setSetupData(response.data.data);
      setActiveStep(1);
    } catch (err: any) {
      setError(err.response?.data?.error?.message || 'Failed to initiate 2FA setup');
    } finally {
      setLoading(false);
    }
  };

  const handleVerify = async () => {
    if (!verificationCode || verificationCode.length !== 6) {
      setError('Please enter a valid 6-digit code');
      return;
    }

    setLoading(true);
    setError(null);

    try {
      await api.post('/api/v1/users/me/2fa/verify', {
        code: verificationCode
      });
      setSuccess(true);
      setActiveStep(3);
    } catch (err: any) {
      setError(err.response?.data?.error?.message || 'Invalid verification code');
    } finally {
      setLoading(false);
    }
  };

  const handleCopyCode = (code: string) => {
    navigator.clipboard.writeText(code);
  };

  const handleCopyAllBackupCodes = () => {
    if (setupData?.backup_codes) {
      const codes = setupData.backup_codes.join('\n');
      navigator.clipboard.writeText(codes);
    }
  };

  const handleDownloadBackupCodes = () => {
    if (setupData?.backup_codes) {
      const codes = setupData.backup_codes.join('\n');
      const blob = new Blob([codes], { type: 'text/plain' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'candid-analytics-backup-codes.txt';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    }
  };

  const handleComplete = () => {
    navigate('/profile');
  };

  if (loading && !setupData) {
    return (
      <Container maxWidth="md" sx={{ mt: 4, mb: 4 }}>
        <Box display="flex" justifyContent="center" alignItems="center" minHeight="400px">
          <CircularProgress />
        </Box>
      </Container>
    );
  }

  return (
    <Container maxWidth="md" sx={{ mt: 4, mb: 4 }}>
      <Typography variant="h4" gutterBottom sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
        <Security /> Two-Factor Authentication Setup
      </Typography>

      <Stepper activeStep={activeStep} sx={{ mt: 3, mb: 4 }}>
        {steps.map((label) => (
          <Step key={label}>
            <StepLabel>{label}</StepLabel>
          </Step>
        ))}
      </Stepper>

      {error && <Alert severity="error" sx={{ mb: 3 }}>{error}</Alert>}
      {success && <Alert severity="success" sx={{ mb: 3 }}>2FA has been enabled successfully!</Alert>}

      {/* Step 1: Scan QR Code */}
      {activeStep === 1 && setupData && (
        <Paper sx={{ p: 4 }}>
          <Typography variant="h6" gutterBottom sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <QrCode /> Scan QR Code with Authenticator App
          </Typography>
          <Divider sx={{ mb: 3 }} />

          <Box sx={{ textAlign: 'center', mb: 3 }}>
            <img
              src={setupData.qr_code_uri}
              alt="2FA QR Code"
              style={{ maxWidth: '300px', border: '1px solid #ddd', padding: '10px' }}
            />
          </Box>

          <Alert severity="info" sx={{ mb: 3 }}>
            <strong>Supported Apps:</strong>
            <br />
            • Google Authenticator (iOS/Android)
            <br />
            • Microsoft Authenticator (iOS/Android)
            <br />
            • Authy (iOS/Android/Desktop)
            <br />
            • 1Password (Premium)
          </Alert>

          <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
            Can't scan the QR code? Enter this key manually in your authenticator app:
          </Typography>

          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 3 }}>
            <TextField
              fullWidth
              value={setupData.manual_entry_key}
              InputProps={{ readOnly: true }}
              size="small"
            />
            <Button
              variant="outlined"
              startIcon={<ContentCopy />}
              onClick={() => handleCopyCode(setupData.manual_entry_key)}
            >
              Copy
            </Button>
          </Box>

          <Button
            variant="contained"
            fullWidth
            onClick={() => setActiveStep(2)}
          >
            Next: Enter Verification Code
          </Button>
        </Paper>
      )}

      {/* Step 2: Verify Code */}
      {activeStep === 2 && (
        <Paper sx={{ p: 4 }}>
          <Typography variant="h6" gutterBottom>
            Enter Verification Code
          </Typography>
          <Divider sx={{ mb: 3 }} />

          <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
            Enter the 6-digit code from your authenticator app to verify setup:
          </Typography>

          <TextField
            fullWidth
            label="6-Digit Code"
            value={verificationCode}
            onChange={(e) => setVerificationCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
            inputProps={{ maxLength: 6, style: { fontSize: '24px', letterSpacing: '8px', textAlign: 'center' } }}
            sx={{ mb: 3 }}
            autoFocus
          />

          <Box sx={{ display: 'flex', gap: 2 }}>
            <Button
              variant="outlined"
              onClick={() => setActiveStep(1)}
              fullWidth
            >
              Back
            </Button>
            <Button
              variant="contained"
              fullWidth
              onClick={handleVerify}
              disabled={loading || verificationCode.length !== 6}
            >
              {loading ? <CircularProgress size={24} /> : 'Verify & Enable 2FA'}
            </Button>
          </Box>
        </Paper>
      )}

      {/* Step 3: Save Backup Codes */}
      {activeStep === 3 && setupData && (
        <Paper sx={{ p: 4 }}>
          <Typography variant="h6" gutterBottom sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <Download /> Save Your Backup Codes
          </Typography>
          <Divider sx={{ mb: 3 }} />

          <Alert severity="warning" sx={{ mb: 3 }}>
            <strong>Important:</strong> Save these backup codes in a secure location.
            You can use them to access your account if you lose access to your authenticator app.
            Each code can only be used once.
          </Alert>

          <List>
            {setupData.backup_codes.map((code, index) => (
              <ListItem
                key={index}
                sx={{
                  border: '1px solid #ddd',
                  borderRadius: 1,
                  mb: 1,
                  fontFamily: 'monospace'
                }}
                secondaryAction={
                  <Button
                    size="small"
                    startIcon={<ContentCopy />}
                    onClick={() => handleCopyCode(code)}
                  >
                    Copy
                  </Button>
                }
              >
                <ListItemIcon>
                  <CheckCircle color="success" />
                </ListItemIcon>
                <ListItemText
                  primary={code}
                  primaryTypographyProps={{ fontFamily: 'monospace', fontSize: '18px' }}
                />
              </ListItem>
            ))}
          </List>

          <Box sx={{ display: 'flex', gap: 2, mt: 3 }}>
            <Button
              variant="outlined"
              startIcon={<ContentCopy />}
              onClick={handleCopyAllBackupCodes}
              fullWidth
            >
              Copy All Codes
            </Button>
            <Button
              variant="outlined"
              startIcon={<Download />}
              onClick={handleDownloadBackupCodes}
              fullWidth
            >
              Download as File
            </Button>
          </Box>

          <Button
            variant="contained"
            color="success"
            fullWidth
            onClick={handleComplete}
            sx={{ mt: 3 }}
          >
            Complete Setup
          </Button>
        </Paper>
      )}
    </Container>
  );
};

export default TwoFactorSetup;
