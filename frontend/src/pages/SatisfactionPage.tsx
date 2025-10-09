import { useState } from 'react'
import {
  Box,
  Typography,
  Paper,
  Grid,
  ToggleButtonGroup,
  ToggleButton,
  Rating,
} from '@mui/material'
import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts'
import {
  Star as StarIcon,
  ThumbUp as ThumbUpIcon,
  Repeat as RepeatIcon,
  Recommend as RecommendIcon,
} from '@mui/icons-material'
import { useSatisfaction, useRetention } from '../hooks/useAnalytics'
import LoadingSpinner from '../components/LoadingSpinner'
import ErrorAlert from '../components/ErrorAlert'
import StatCard from '../components/StatCard'

const SatisfactionPage = () => {
  const [timeRange, setTimeRange] = useState<number>(12)

  const {
    data: satisfactionData,
    isLoading: satisfactionLoading,
    error: satisfactionError,
    refetch: refetchSatisfaction,
  } = useSatisfaction(timeRange)

  const {
    data: retentionData,
    isLoading: retentionLoading,
    error: retentionError,
    refetch: refetchRetention,
  } = useRetention()

  if (satisfactionLoading || retentionLoading) {
    return <LoadingSpinner message="Loading satisfaction metrics..." />
  }

  if (satisfactionError || retentionError) {
    return (
      <ErrorAlert
        message="Failed to load satisfaction data. Please try again."
        onRetry={() => {
          refetchSatisfaction()
          refetchRetention()
        }}
      />
    )
  }

  if (!satisfactionData || !retentionData) return null

  // Format data for retention chart
  const retentionChartData = retentionData.map((item) => ({
    month: new Date(item.month).toLocaleDateString('en-US', { month: 'short', year: 'numeric' }),
    rate: item.retention_rate,
    repeatClients: item.repeat_clients,
    totalClients: item.total_clients,
  }))

  return (
    <Box>
      {/* Page Header */}
      <Box sx={{ mb: 4, display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
        <Box>
          <Typography variant="h4" component="h1" fontWeight="bold" gutterBottom>
            Client Satisfaction
          </Typography>
          <Typography variant="body2" color="text.secondary">
            Track client happiness and retention metrics
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
        <Grid item xs={12} sm={6} md={3}>
          <StatCard
            title="Average Rating"
            value={satisfactionData.avg_rating.toFixed(1)}
            unit="/5"
            icon={<StarIcon />}
            color="success"
            subtitle={`From ${satisfactionData.total_reviews} reviews`}
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <StatCard
            title="NPS Score"
            value={satisfactionData.nps_score.toFixed(0)}
            icon={<ThumbUpIcon />}
            color="primary"
            subtitle="Net Promoter Score"
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <StatCard
            title="Repeat Booking Rate"
            value={satisfactionData.repeat_booking_rate.toFixed(1)}
            unit="%"
            icon={<RepeatIcon />}
            color="secondary"
            subtitle="Client retention"
          />
        </Grid>
        <Grid item xs={12} sm={6} md={3}>
          <StatCard
            title="Referral Rate"
            value={satisfactionData.referral_rate.toFixed(1)}
            unit="%"
            icon={<RecommendIcon />}
            color="info"
            subtitle="Word of mouth"
          />
        </Grid>
      </Grid>

      {/* Rating Visual */}
      <Paper sx={{ p: 3, mb: 4 }}>
        <Typography variant="h6" gutterBottom fontWeight="bold">
          Client Rating Overview
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
          Overall client satisfaction score
        </Typography>

        <Box sx={{ textAlign: 'center', py: 4 }}>
          <Typography variant="h2" fontWeight="bold" sx={{ mb: 2 }}>
            {satisfactionData.avg_rating.toFixed(1)}
          </Typography>
          <Rating
            value={satisfactionData.avg_rating}
            precision={0.1}
            size="large"
            readOnly
            sx={{ fontSize: '3rem', mb: 2 }}
          />
          <Typography variant="body1" color="text.secondary">
            Based on {satisfactionData.total_reviews} client reviews
          </Typography>
        </Box>
      </Paper>

      {/* Retention Trend */}
      <Paper sx={{ p: 3, mb: 4 }}>
        <Typography variant="h6" gutterBottom fontWeight="bold">
          Client Retention Trend
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
          Percentage of clients who return for repeat bookings
        </Typography>
        <ResponsiveContainer width="100%" height={350}>
          <LineChart data={retentionChartData}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="month" />
            <YAxis />
            <Tooltip
              formatter={(value: number, name: string) => {
                if (name === 'rate') return [`${value.toFixed(1)}%`, 'Retention Rate']
                return [value, name]
              }}
            />
            <Legend />
            <Line
              type="monotone"
              dataKey="rate"
              stroke="#9c27b0"
              strokeWidth={2}
              name="Retention Rate"
            />
          </LineChart>
        </ResponsiveContainer>
      </Paper>

      {/* NPS Explanation */}
      <Paper sx={{ p: 3, bgcolor: 'success.light', color: 'success.contrastText' }}>
        <Typography variant="h6" gutterBottom>
          📊 Understanding Your Metrics
        </Typography>
        <Typography variant="body2" paragraph>
          <strong>NPS Score ({satisfactionData.nps_score.toFixed(0)}):</strong>{' '}
          {satisfactionData.nps_score >= 50
            ? 'Excellent! Your clients are highly satisfied and likely to recommend you.'
            : satisfactionData.nps_score >= 0
            ? 'Good score. Focus on converting passive clients to promoters.'
            : 'Your NPS needs improvement. Prioritize addressing client concerns.'}
        </Typography>
        <Typography variant="body2">
          <strong>Repeat Booking Rate ({satisfactionData.repeat_booking_rate.toFixed(1)}%):</strong>{' '}
          {satisfactionData.repeat_booking_rate >= 30
            ? 'Great client loyalty! Your clients love working with you.'
            : 'Consider loyalty programs or follow-up campaigns to improve retention.'}
        </Typography>
      </Paper>
    </Box>
  )
}

export default SatisfactionPage
