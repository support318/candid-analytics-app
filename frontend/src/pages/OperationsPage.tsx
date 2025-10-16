import { useState } from 'react'
import {
  Box,
  Typography,
  Paper,
  Grid,
  ToggleButtonGroup,
  ToggleButton,
  LinearProgress,
} from '@mui/material'
import {
  Schedule as ScheduleIcon,
  CheckCircle as CheckIcon,
  Error as ErrorIcon,
} from '@mui/icons-material'
import { useOperations } from '../hooks/useAnalytics'
import LoadingSpinner from '../components/LoadingSpinner'
import ErrorAlert from '../components/ErrorAlert'
import StatCard from '../components/StatCard'

const OperationsPage = () => {
  const [timeRange, setTimeRange] = useState<number>(12)

  const {
    data: opsData,
    isLoading,
    error,
    refetch,
  } = useOperations(timeRange)

  if (isLoading) {
    return <LoadingSpinner message="Loading operations metrics..." />
  }

  if (error) {
    return (
      <ErrorAlert
        message="Failed to load operations data. Please try again."
        onRetry={refetch}
      />
    )
  }

  if (!opsData) return null

  const onTimePercentage = opsData.on_time_percentage

  return (
    <Box>
      {/* Page Header */}
      <Box sx={{ mb: 4, display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
        <Box>
          <Typography variant="h4" component="h1" fontWeight="bold" gutterBottom>
            Operations Dashboard
          </Typography>
          <Typography variant="body2" color="text.secondary">
            Track delivery times and operational efficiency
          </Typography>
        </Box>

        <ToggleButtonGroup
          value={timeRange}
          exclusive
          onChange={(_, value) => value && setTimeRange(value)}
          size="small"
        >
          <ToggleButton value={6}>6 Months</ToggleButton>
          <ToggleButton value={12}>12 Months</ToggleButton>
          <ToggleButton value={24}>24 Months</ToggleButton>
        </ToggleButtonGroup>
      </Box>

      {/* Key Metrics */}
      <Grid container spacing={3} sx={{ mb: 4 }}>
        <Grid item xs={12} sm={6} md={4}>
          <StatCard
            title="Avg Photo Delivery"
            value={opsData.avg_photo_delivery_days ? Number(opsData.avg_photo_delivery_days).toFixed(1) : '0'}
            unit="days"
            icon={<ScheduleIcon />}
            color="primary"
            subtitle="Time to deliver photos"
          />
        </Grid>
        <Grid item xs={12} sm={6} md={4}>
          <StatCard
            title="Avg Video Delivery"
            value={opsData.avg_video_delivery_days ? Number(opsData.avg_video_delivery_days).toFixed(1) : '0'}
            unit="days"
            icon={<ScheduleIcon />}
            color="secondary"
            subtitle="Time to deliver videos"
          />
        </Grid>
        <Grid item xs={12} sm={6} md={4}>
          <StatCard
            title="Avg Response Time"
            value={opsData.avg_first_response_hours ? Number(opsData.avg_first_response_hours).toFixed(1) : '0'}
            unit="hrs"
            icon={<ScheduleIcon />}
            color="info"
            subtitle="First response time"
          />
        </Grid>
      </Grid>

      {/* Project Status */}
      <Paper sx={{ p: 3, mb: 4 }}>
        <Typography variant="h6" gutterBottom fontWeight="bold">
          Project Delivery Performance
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
          On-time vs delayed project delivery
        </Typography>

        <Grid container spacing={3}>
          <Grid item xs={12} md={4}>
            <Box sx={{ textAlign: 'center', p: 3, border: '1px solid', borderColor: 'divider', borderRadius: 2 }}>
              <CheckIcon sx={{ fontSize: 48, color: 'success.main', mb: 1 }} />
              <Typography variant="h3" fontWeight="bold" color="success.main">
                {opsData.projects_on_time}
              </Typography>
              <Typography variant="body2" color="text.secondary">
                On-Time Projects
              </Typography>
            </Box>
          </Grid>

          <Grid item xs={12} md={4}>
            <Box sx={{ textAlign: 'center', p: 3, border: '1px solid', borderColor: 'divider', borderRadius: 2 }}>
              <ErrorIcon sx={{ fontSize: 48, color: 'error.main', mb: 1 }} />
              <Typography variant="h3" fontWeight="bold" color="error.main">
                {opsData.projects_delayed}
              </Typography>
              <Typography variant="body2" color="text.secondary">
                Delayed Projects
              </Typography>
            </Box>
          </Grid>

          <Grid item xs={12} md={4}>
            <Box sx={{ textAlign: 'center', p: 3, border: '1px solid', borderColor: 'divider', borderRadius: 2 }}>
              <ScheduleIcon sx={{ fontSize: 48, color: 'primary.main', mb: 1 }} />
              <Typography variant="h3" fontWeight="bold" color="primary.main">
                {onTimePercentage ? Number(onTimePercentage).toFixed(1) : '0'}%
              </Typography>
              <Typography variant="body2" color="text.secondary">
                On-Time Rate
              </Typography>
            </Box>
          </Grid>
        </Grid>

        {/* Visual Progress Bar */}
        <Box sx={{ mt: 4 }}>
          <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 1 }}>
            <Typography variant="body2" fontWeight="bold">
              Overall Performance
            </Typography>
            <Typography variant="body2" color="text.secondary">
              {opsData.projects_on_time} of {opsData.projects_on_time + opsData.projects_delayed} projects
            </Typography>
          </Box>
          <LinearProgress
            variant="determinate"
            value={onTimePercentage}
            sx={{
              height: 12,
              borderRadius: 6,
              bgcolor: 'error.light',
              '& .MuiLinearProgress-bar': {
                bgcolor: onTimePercentage >= 80 ? 'success.main' : onTimePercentage >= 60 ? 'warning.main' : 'error.main',
              },
            }}
          />
          <Box sx={{ display: 'flex', justifyContent: 'space-between', mt: 1 }}>
            <Typography variant="caption" color="text.secondary">
              0%
            </Typography>
            <Typography variant="caption" color="text.secondary">
              100%
            </Typography>
          </Box>
        </Box>
      </Paper>

      {/* Insights */}
      <Paper sx={{ p: 3, bgcolor: 'info.light', color: 'info.contrastText' }}>
        <Typography variant="h6" gutterBottom>
          💡 Operational Insights
        </Typography>
        <Typography variant="body2" paragraph>
          {onTimePercentage >= 80
            ? 'Excellent! Your team is delivering projects on time consistently. Keep up the great work!'
            : onTimePercentage >= 60
            ? 'Good performance, but there\'s room for improvement. Consider analyzing delayed projects to identify bottlenecks.'
            : 'Your on-time delivery rate needs attention. Review your workflow and resource allocation to improve efficiency.'}
        </Typography>
        <Typography variant="body2">
          Average delivery times: Photo deliveries are averaging {opsData.avg_photo_delivery_days ? Number(opsData.avg_photo_delivery_days).toFixed(1) : '0'} days,
          while video deliveries take {opsData.avg_video_delivery_days ? Number(opsData.avg_video_delivery_days).toFixed(1) : '0'} days on average.
        </Typography>
      </Paper>
    </Box>
  )
}

export default OperationsPage
