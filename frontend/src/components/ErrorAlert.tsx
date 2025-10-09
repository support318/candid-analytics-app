import { Alert, AlertTitle, Box, Button } from '@mui/material'
import { Refresh as RefreshIcon } from '@mui/icons-material'

interface ErrorAlertProps {
  title?: string
  message: string
  onRetry?: () => void
}

const ErrorAlert = ({
  title = 'Error Loading Data',
  message,
  onRetry,
}: ErrorAlertProps) => {
  return (
    <Box sx={{ p: 3 }}>
      <Alert
        severity="error"
        action={
          onRetry && (
            <Button
              color="inherit"
              size="small"
              startIcon={<RefreshIcon />}
              onClick={onRetry}
            >
              Retry
            </Button>
          )
        }
      >
        <AlertTitle>{title}</AlertTitle>
        {message}
      </Alert>
    </Box>
  )
}

export default ErrorAlert
