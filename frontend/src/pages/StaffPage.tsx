import { useState } from 'react'
import {
  Box,
  Typography,
  Paper,
  Grid,
  ToggleButtonGroup,
  ToggleButton,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Avatar,
  Chip,
} from '@mui/material'
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from 'recharts'
import { People as PeopleIcon, Star as StarIcon } from '@mui/icons-material'
import { useStaff } from '../hooks/useAnalytics'
import LoadingSpinner from '../components/LoadingSpinner'
import ErrorAlert from '../components/ErrorAlert'

const StaffPage = () => {
  const [timeRange, setTimeRange] = useState<number>(6)

  const {
    data: staffData,
    isLoading,
    error,
    refetch,
  } = useStaff(timeRange)

  if (isLoading) {
    return <LoadingSpinner message="Loading staff productivity..." />
  }

  if (error) {
    return (
      <ErrorAlert
        message="Failed to load staff data. Please try again."
        onRetry={refetch}
      />
    )
  }

  if (!staffData || staffData.length === 0) {
    return (
      <Box sx={{ p: 3 }}>
        <Typography>No staff data available for the selected time range.</Typography>
      </Box>
    )
  }

  // Calculate totals
  const totalProjects = staffData.reduce((sum, staff) => sum + Number(staff.projects_completed || 0), 0)
  const totalRevenue = staffData.reduce((sum, staff) => sum + Number(staff.revenue_generated || 0), 0)
  const avgRating = staffData.reduce((sum, staff) => sum + Number(staff.avg_client_rating || 0), 0) / staffData.length

  // Format data for chart
  const chartData = staffData.map((staff) => ({
    name: staff.staff_name ? staff.staff_name.split(' ')[0] : 'Unknown', // First name only for chart
    projects: Number(staff.projects_completed || 0),
    revenue: Number(staff.revenue_generated || 0) / 1000, // In thousands
    rating: Number(staff.avg_client_rating || 0),
  }))

  return (
    <Box>
      {/* Page Header */}
      <Box sx={{ mb: 4, display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
        <Box>
          <Typography variant="h4" component="h1" fontWeight="bold" gutterBottom>
            Staff Productivity
          </Typography>
          <Typography variant="body2" color="text.secondary">
            Track team performance and efficiency
          </Typography>
        </Box>

        <ToggleButtonGroup
          value={timeRange}
          exclusive
          onChange={(_, value) => value && setTimeRange(value)}
          size="small"
        >
          <ToggleButton value={3}>3 Months</ToggleButton>
          <ToggleButton value={6}>6 Months</ToggleButton>
          <ToggleButton value={12}>12 Months</ToggleButton>
        </ToggleButtonGroup>
      </Box>

      {/* Summary Cards */}
      <Grid container spacing={3} sx={{ mb: 4 }}>
        <Grid item xs={12} sm={4}>
          <Paper sx={{ p: 3, textAlign: 'center' }}>
            <PeopleIcon sx={{ fontSize: 40, color: 'primary.main', mb: 1 }} />
            <Typography variant="h4" fontWeight="bold">
              {staffData.length}
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Active Team Members
            </Typography>
          </Paper>
        </Grid>
        <Grid item xs={12} sm={4}>
          <Paper sx={{ p: 3, textAlign: 'center' }}>
            <Typography variant="h4" fontWeight="bold" color="primary.main">
              {totalProjects}
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Total Projects Completed
            </Typography>
          </Paper>
        </Grid>
        <Grid item xs={12} sm={4}>
          <Paper sx={{ p: 3, textAlign: 'center' }}>
            <StarIcon sx={{ fontSize: 40, color: 'success.main', mb: 1 }} />
            <Typography variant="h4" fontWeight="bold">
              {avgRating ? avgRating.toFixed(1) : '0'}/5
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Average Client Rating
            </Typography>
          </Paper>
        </Grid>
      </Grid>

      {/* Performance Chart */}
      <Paper sx={{ p: 3, mb: 4 }}>
        <Typography variant="h6" gutterBottom fontWeight="bold">
          Projects Completed by Staff
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
          Individual team member performance
        </Typography>
        <ResponsiveContainer width="100%" height={350}>
          <BarChart data={chartData}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis yAxisId="left" />
            <YAxis yAxisId="right" orientation="right" />
            <Tooltip />
            <Legend />
            <Bar yAxisId="left" dataKey="projects" fill="#1976d2" name="Projects" />
            <Bar
              yAxisId="right"
              dataKey="revenue"
              fill="#2e7d32"
              name="Revenue ($1k)"
            />
          </BarChart>
        </ResponsiveContainer>
      </Paper>

      {/* Staff Details Table */}
      <Paper sx={{ p: 3 }}>
        <Typography variant="h6" gutterBottom fontWeight="bold">
          Team Performance Details
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
          Detailed breakdown of individual performance metrics
        </Typography>
        <TableContainer>
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>Staff Member</TableCell>
                <TableCell align="right">Projects</TableCell>
                <TableCell align="right">Revenue Generated</TableCell>
                <TableCell align="right">Avg Client Rating</TableCell>
                <TableCell align="right">Efficiency Score</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {staffData
                .sort((a, b) => Number(b.efficiency_score || 0) - Number(a.efficiency_score || 0))
                .map((staff) => (
                  <TableRow key={staff.staff_id}>
                    <TableCell component="th" scope="row">
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <Avatar sx={{ width: 32, height: 32, bgcolor: 'primary.main' }}>
                          {staff.staff_name ? staff.staff_name.charAt(0) : '?'}
                        </Avatar>
                        <Typography variant="body2" fontWeight="bold">
                          {staff.staff_name || 'Unknown'}
                        </Typography>
                      </Box>
                    </TableCell>
                    <TableCell align="right">{staff.projects_completed || 0}</TableCell>
                    <TableCell align="right">
                      ${staff.revenue_generated ? Number(staff.revenue_generated).toLocaleString() : '0'}
                    </TableCell>
                    <TableCell align="right">
                      <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'flex-end', gap: 0.5 }}>
                        <StarIcon
                          sx={{
                            fontSize: 16,
                            color: Number(staff.avg_client_rating) >= 4.5 ? 'success.main' : 'warning.main',
                          }}
                        />
                        <Typography
                          variant="body2"
                          color={Number(staff.avg_client_rating) >= 4.5 ? 'success.main' : 'text.primary'}
                          fontWeight="bold"
                        >
                          {staff.avg_client_rating ? Number(staff.avg_client_rating).toFixed(1) : '0'}
                        </Typography>
                      </Box>
                    </TableCell>
                    <TableCell align="right">
                      <Chip
                        label={`${staff.efficiency_score ? Number(staff.efficiency_score).toFixed(0) : '0'}%`}
                        size="small"
                        color={
                          Number(staff.efficiency_score) >= 85
                            ? 'success'
                            : Number(staff.efficiency_score) >= 70
                            ? 'info'
                            : 'warning'
                        }
                      />
                    </TableCell>
                  </TableRow>
                ))}
            </TableBody>
          </Table>
        </TableContainer>
      </Paper>

      {/* Total Revenue Box */}
      <Paper sx={{ p: 3, mt: 4, bgcolor: 'success.light', color: 'success.contrastText' }}>
        <Typography variant="h6" gutterBottom>
          💰 Total Team Revenue
        </Typography>
        <Typography variant="h4" fontWeight="bold" sx={{ mb: 1 }}>
          ${totalRevenue ? Number(totalRevenue).toLocaleString() : '0'}
        </Typography>
        <Typography variant="body2">
          Generated by {staffData.length} team members over the last {timeRange} months
        </Typography>
      </Paper>
    </Box>
  )
}

export default StaffPage
