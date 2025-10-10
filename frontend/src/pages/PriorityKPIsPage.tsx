import { Grid, Typography, Box, Paper } from '@mui/material'
import {
  AttachMoney as MoneyIcon,
  EventAvailable as BookingIcon,
  TrendingUp as ConversionIcon,
  Schedule as TimeIcon,
  Star as SatisfactionIcon,
  Work as ProjectIcon,
} from '@mui/icons-material'
import { usePriorityKPIs } from '../hooks/useAnalytics'
import StatCard from '../components/StatCard'
import LoadingSpinner from '../components/LoadingSpinner'
import ErrorAlert from '../components/ErrorAlert'
import { format } from 'date-fns'

const PriorityKPIsPage = () => {
  const { data: kpis, isLoading, error, refetch } = usePriorityKPIs()

  if (isLoading) return <LoadingSpinner message="Loading KPIs..." />

  if (error)
    return (
      <ErrorAlert
        message="Failed to load priority KPIs. Please try again."
        onRetry={refetch}
      />
    )

  if (!kpis) return null

  return (
    <Box>
      {/* Page Header */}
      <Box sx={{ mb: 4 }}>
        <Typography variant="h4" component="h1" fontWeight="bold" gutterBottom>
          Priority KPIs Dashboard
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Real-time overview of your most important business metrics
        </Typography>
        <Typography variant="caption" color="text.secondary" display="block" sx={{ mt: 1 }}>
          Last updated: {format(new Date(kpis.last_updated), 'MMM dd, yyyy hh:mm a')}
        </Typography>
      </Box>

      {/* KPI Cards Grid */}
      <Grid container spacing={3}>
        {/* Today's Revenue */}
        <Grid item xs={12} sm={6} md={4}>
          <StatCard
            title="Today's Revenue"
            value={`$${kpis.today_revenue.toLocaleString()}`}
            icon={<MoneyIcon />}
            color="success"
            subtitle="Generated today"
          />
        </Grid>

        {/* Today's Bookings */}
        <Grid item xs={12} sm={6} md={4}>
          <StatCard
            title="Today's Bookings"
            value={kpis.today_bookings}
            icon={<BookingIcon />}
            color="primary"
            subtitle="New bookings today"
          />
        </Grid>

        {/* Month Revenue */}
        <Grid item xs={12} sm={6} md={4}>
          <StatCard
            title="Month Revenue"
            value={`$${kpis.month_revenue.toLocaleString()}`}
            icon={<MoneyIcon />}
            color="success"
            subtitle="This month's total"
          />
        </Grid>

        {/* Month Bookings */}
        <Grid item xs={12} sm={6} md={4}>
          <StatCard
            title="Month Bookings"
            value={kpis.month_bookings}
            icon={<BookingIcon />}
            color="primary"
            subtitle="Bookings this month"
          />
        </Grid>

        {/* Conversion Rate */}
        <Grid item xs={12} sm={6} md={4}>
          <StatCard
            title="Conversion Rate"
            value={kpis.conversion_rate.toFixed(1)}
            unit="%"
            icon={<ConversionIcon />}
            color="info"
            subtitle="Lead to booking"
          />
        </Grid>

        {/* Average Booking Value */}
        <Grid item xs={12} sm={6} md={4}>
          <StatCard
            title="Avg Booking Value"
            value={`$${kpis.avg_booking_value.toLocaleString()}`}
            icon={<MoneyIcon />}
            color="secondary"
            subtitle="Per booking"
          />
        </Grid>

        {/* Leads in Pipeline */}
        <Grid item xs={12} sm={6} md={4}>
          <StatCard
            title="Leads in Pipeline"
            value={kpis.leads_in_pipeline}
            icon={<ProjectIcon />}
            color="warning"
            subtitle="Active leads"
          />
        </Grid>

        {/* Projects in Progress */}
        <Grid item xs={12} sm={6} md={4}>
          <StatCard
            title="Projects in Progress"
            value={kpis.projects_in_progress}
            icon={<ProjectIcon />}
            color="info"
            subtitle="Active projects"
          />
        </Grid>

        {/* Avg Delivery Time */}
        <Grid item xs={12} sm={6} md={4}>
          <StatCard
            title="Avg Delivery Time"
            value={((kpis.avg_photo_delivery_days + kpis.avg_video_delivery_days) / 2).toFixed(1)}
            unit="days"
            icon={<TimeIcon />}
            color="primary"
            subtitle="Time to delivery"
          />
        </Grid>

        {/* Client Satisfaction */}
        <Grid item xs={12} sm={6} md={4}>
          <StatCard
            title="Client Satisfaction"
            value={kpis.avg_client_rating.toFixed(1)}
            unit="/5"
            icon={<SatisfactionIcon />}
            color="success"
            subtitle="Average rating"
          />
        </Grid>
      </Grid>

      {/* Info Box */}
      <Paper sx={{ p: 3, mt: 4, bgcolor: 'primary.light', color: 'primary.contrastText' }}>
        <Typography variant="h6" gutterBottom>
          💡 Quick Insights
        </Typography>
        <Typography variant="body2">
          Your KPIs are updated in real-time as new data flows in from Go High Level via Make.com.
          Use the navigation menu to explore detailed analytics for revenue, sales funnel, operations,
          and more.
        </Typography>
      </Paper>
    </Box>
  )
}

export default PriorityKPIsPage
