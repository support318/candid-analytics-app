import { useState } from 'react'
import {
  Box,
  Typography,
  Paper,
  Grid,
  ToggleButtonGroup,
  ToggleButton,
} from '@mui/material'
import {
  LineChart,
  Line,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts'
import { useRevenueAnalytics, useRevenueByLocation } from '../hooks/useAnalytics'
import LoadingSpinner from '../components/LoadingSpinner'
import ErrorAlert from '../components/ErrorAlert'
import StatCard from '../components/StatCard'
import {
  AttachMoney as MoneyIcon,
  TrendingUp as TrendingUpIcon,
} from '@mui/icons-material'

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82ca9d']

const RevenuePage = () => {
  const [timeRange, setTimeRange] = useState<number>(12)

  const {
    data: revenueData,
    isLoading: revenueLoading,
    error: revenueError,
    refetch: refetchRevenue,
  } = useRevenueAnalytics(timeRange)

  const {
    data: locationData,
    isLoading: locationLoading,
    error: locationError,
    refetch: refetchLocation,
  } = useRevenueByLocation()

  if (revenueLoading || locationLoading) {
    return <LoadingSpinner message="Loading revenue analytics..." />
  }

  if (revenueError || locationError) {
    return (
      <ErrorAlert
        message="Failed to load revenue data. Please try again."
        onRetry={() => {
          refetchRevenue()
          refetchLocation()
        }}
      />
    )
  }

  if (!revenueData || !locationData || revenueData.length === 0 || locationData.length === 0) {
    return (
      <Box sx={{ p: 4, textAlign: 'center' }}>
        <Typography variant="h6" color="text.secondary">
          No revenue data available for the selected time period.
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mt: 2 }}>
          Revenue data: {revenueData ? `${revenueData.length} records` : 'undefined'}<br />
          Location data: {locationData ? `${locationData.length} records` : 'undefined'}
        </Typography>
      </Box>
    )
  }

  // Calculate totals
  const totalRevenue = revenueData.reduce((sum, item) => sum + item.total_revenue, 0)
  const totalBookings = revenueData.reduce((sum, item) => sum + item.booking_count, 0)
  const avgBookingValue = totalRevenue / totalBookings

  // Format data for charts
  const chartData = revenueData.map((item) => ({
    month: new Date(item.month).toLocaleDateString('en-US', { month: 'short', year: 'numeric' }),
    revenue: item.total_revenue,
    bookings: item.booking_count,
    avgValue: item.avg_booking_value,
  }))

  const locationChartData = locationData.map((item) => ({
    name: item.location,
    value: item.revenue,
    bookings: item.booking_count,
  }))

  return (
    <Box>
      {/* Page Header */}
      <Box sx={{ mb: 4, display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
        <Box>
          <Typography variant="h4" component="h1" fontWeight="bold" gutterBottom>
            Revenue Analytics
          </Typography>
          <Typography variant="body2" color="text.secondary">
            Comprehensive revenue tracking and analysis
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

      {/* Summary Cards */}
      <Grid container spacing={3} sx={{ mb: 4 }}>
        <Grid item xs={12} sm={4}>
          <StatCard
            title="Total Revenue"
            value={`$${totalRevenue.toLocaleString()}`}
            icon={<MoneyIcon />}
            color="success"
            subtitle={`Last ${timeRange} months`}
          />
        </Grid>
        <Grid item xs={12} sm={4}>
          <StatCard
            title="Total Bookings"
            value={totalBookings}
            icon={<TrendingUpIcon />}
            color="primary"
            subtitle={`Last ${timeRange} months`}
          />
        </Grid>
        <Grid item xs={12} sm={4}>
          <StatCard
            title="Avg Booking Value"
            value={`$${avgBookingValue.toLocaleString(undefined, { maximumFractionDigits: 0 })}`}
            icon={<MoneyIcon />}
            color="secondary"
            subtitle="Per booking"
          />
        </Grid>
      </Grid>

      {/* Revenue Trend Chart */}
      <Paper sx={{ p: 3, mb: 4 }}>
        <Typography variant="h6" gutterBottom fontWeight="bold">
          Revenue Trend
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
          Monthly revenue over time
        </Typography>
        <ResponsiveContainer width="100%" height={350}>
          <LineChart data={chartData}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="month" />
            <YAxis />
            <Tooltip
              formatter={(value: number) => `$${value.toLocaleString()}`}
            />
            <Legend />
            <Line
              type="monotone"
              dataKey="revenue"
              stroke="#2e7d32"
              strokeWidth={2}
              name="Revenue"
            />
            <Line
              type="monotone"
              dataKey="bookings"
              stroke="#1976d2"
              strokeWidth={2}
              name="Bookings"
            />
          </LineChart>
        </ResponsiveContainer>
      </Paper>

      {/* Revenue by Location */}
      <Paper sx={{ p: 3 }}>
        <Typography variant="h6" gutterBottom fontWeight="bold">
          Revenue by Location
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
          Geographic distribution of revenue
        </Typography>
        <Grid container spacing={3}>
          <Grid item xs={12} md={6}>
            <ResponsiveContainer width="100%" height={300}>
              <PieChart>
                <Pie
                  data={locationChartData}
                  cx="50%"
                  cy="50%"
                  labelLine={false}
                  label={(entry) => `${entry.name}: $${(entry.value / 1000).toFixed(0)}k`}
                  outerRadius={100}
                  fill="#8884d8"
                  dataKey="value"
                >
                  {locationChartData.map((_, index) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip formatter={(value: number) => `$${value.toLocaleString()}`} />
              </PieChart>
            </ResponsiveContainer>
          </Grid>
          <Grid item xs={12} md={6}>
            <Box sx={{ pl: 2 }}>
              {locationData.map((location, index) => (
                <Box key={location.location} sx={{ mb: 2 }}>
                  <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 0.5 }}>
                    <Typography variant="body2" fontWeight="bold">
                      {location.location}
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                      ${location.revenue.toLocaleString()}
                    </Typography>
                  </Box>
                  <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                    <Box
                      sx={{
                        width: 12,
                        height: 12,
                        borderRadius: '50%',
                        bgcolor: COLORS[index % COLORS.length],
                      }}
                    />
                    <Typography variant="caption" color="text.secondary">
                      {location.booking_count} bookings • {location.percentage.toFixed(1)}%
                    </Typography>
                  </Box>
                </Box>
              ))}
            </Box>
          </Grid>
        </Grid>
      </Paper>
    </Box>
  )
}

export default RevenuePage
