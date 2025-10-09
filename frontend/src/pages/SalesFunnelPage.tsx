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
  Cell,
} from 'recharts'
import { useSalesFunnel, useLeadSources } from '../hooks/useAnalytics'
import LoadingSpinner from '../components/LoadingSpinner'
import ErrorAlert from '../components/ErrorAlert'
import StatCard from '../components/StatCard'
import { TrendingUp as TrendingUpIcon } from '@mui/icons-material'

const COLORS = ['#2e7d32', '#43a047', '#66bb6a', '#81c784', '#a5d6a7']

const SalesFunnelPage = () => {
  const [timeRange, setTimeRange] = useState<number>(12)

  const {
    data: funnelData,
    isLoading: funnelLoading,
    error: funnelError,
    refetch: refetchFunnel,
  } = useSalesFunnel(timeRange)

  const {
    data: leadSourceData,
    isLoading: leadSourceLoading,
    error: leadSourceError,
    refetch: refetchLeadSource,
  } = useLeadSources()

  if (funnelLoading || leadSourceLoading) {
    return <LoadingSpinner message="Loading sales funnel data..." />
  }

  if (funnelError || leadSourceError) {
    return (
      <ErrorAlert
        message="Failed to load sales funnel data. Please try again."
        onRetry={() => {
          refetchFunnel()
          refetchLeadSource()
        }}
      />
    )
  }

  if (!funnelData || !leadSourceData) return null

  // Calculate summary metrics
  const totalLeads = funnelData[0]?.count || 0
  const convertedLeads = funnelData[funnelData.length - 1]?.count || 0
  const overallConversion = totalLeads > 0 ? (convertedLeads / totalLeads) * 100 : 0

  // Format data for funnel chart
  const funnelChartData = funnelData.map((stage) => ({
    stage: stage.stage,
    count: stage.count,
    value: stage.value,
    conversionRate: stage.conversion_rate,
  }))

  return (
    <Box>
      {/* Page Header */}
      <Box sx={{ mb: 4, display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
        <Box>
          <Typography variant="h4" component="h1" fontWeight="bold" gutterBottom>
            Sales Funnel
          </Typography>
          <Typography variant="body2" color="text.secondary">
            Track leads through your sales pipeline
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
            title="Total Leads"
            value={totalLeads}
            icon={<TrendingUpIcon />}
            color="primary"
            subtitle={`Last ${timeRange} months`}
          />
        </Grid>
        <Grid item xs={12} sm={4}>
          <StatCard
            title="Converted"
            value={convertedLeads}
            icon={<TrendingUpIcon />}
            color="success"
            subtitle="Bookings made"
          />
        </Grid>
        <Grid item xs={12} sm={4}>
          <StatCard
            title="Conversion Rate"
            value={overallConversion.toFixed(1)}
            unit="%"
            icon={<TrendingUpIcon />}
            color="info"
            subtitle="Lead to booking"
          />
        </Grid>
      </Grid>

      {/* Funnel Visualization */}
      <Paper sx={{ p: 3, mb: 4 }}>
        <Typography variant="h6" gutterBottom fontWeight="bold">
          Sales Funnel Stages
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
          Lead progression through pipeline stages
        </Typography>
        <ResponsiveContainer width="100%" height={400}>
          <BarChart data={funnelChartData} layout="vertical">
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis type="number" />
            <YAxis dataKey="stage" type="category" width={100} />
            <Tooltip
              formatter={(value: number, name: string) => {
                if (name === 'count') return [`${value} leads`, 'Count']
                if (name === 'value') return [`$${value.toLocaleString()}`, 'Value']
                return [value, name]
              }}
            />
            <Legend />
            <Bar dataKey="count" name="Lead Count">
              {funnelChartData.map((_, index) => (
                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
              ))}
            </Bar>
          </BarChart>
        </ResponsiveContainer>
      </Paper>

      {/* Conversion Rates Table */}
      <Paper sx={{ p: 3, mb: 4 }}>
        <Typography variant="h6" gutterBottom fontWeight="bold">
          Stage Conversion Rates
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
          Conversion performance at each stage
        </Typography>
        <TableContainer>
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>Stage</TableCell>
                <TableCell align="right">Lead Count</TableCell>
                <TableCell align="right">Potential Value</TableCell>
                <TableCell align="right">Conversion Rate</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {funnelData.map((stage) => (
                <TableRow key={stage.stage}>
                  <TableCell component="th" scope="row" sx={{ fontWeight: 'bold' }}>
                    {stage.stage}
                  </TableCell>
                  <TableCell align="right">{stage.count}</TableCell>
                  <TableCell align="right">${stage.value.toLocaleString()}</TableCell>
                  <TableCell align="right">
                    <Typography
                      variant="body2"
                      color={stage.conversion_rate >= 50 ? 'success.main' : 'text.primary'}
                      fontWeight="bold"
                    >
                      {stage.conversion_rate.toFixed(1)}%
                    </Typography>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      </Paper>

      {/* Lead Sources */}
      <Paper sx={{ p: 3 }}>
        <Typography variant="h6" gutterBottom fontWeight="bold">
          Lead Sources Performance
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
          Where your best leads come from
        </Typography>
        <TableContainer>
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>Source</TableCell>
                <TableCell align="right">Leads</TableCell>
                <TableCell align="right">Conversions</TableCell>
                <TableCell align="right">Conversion Rate</TableCell>
                <TableCell align="right">Revenue</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {leadSourceData.map((source) => (
                <TableRow key={source.source}>
                  <TableCell component="th" scope="row" sx={{ fontWeight: 'bold' }}>
                    {source.source}
                  </TableCell>
                  <TableCell align="right">{source.leads}</TableCell>
                  <TableCell align="right">{source.conversions}</TableCell>
                  <TableCell align="right">
                    <Typography
                      variant="body2"
                      color={source.conversion_rate >= 20 ? 'success.main' : 'text.primary'}
                      fontWeight="bold"
                    >
                      {source.conversion_rate.toFixed(1)}%
                    </Typography>
                  </TableCell>
                  <TableCell align="right">${source.revenue.toLocaleString()}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      </Paper>
    </Box>
  )
}

export default SalesFunnelPage
