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
  Email as EmailIcon,
  Insights as InsightsIcon,
  AttachMoney as MoneyIcon,
  Campaign as CampaignIcon,
} from '@mui/icons-material'
import { useMarketing } from '../hooks/useAnalytics'
import LoadingSpinner from '../components/LoadingSpinner'
import ErrorAlert from '../components/ErrorAlert'
import StatCard from '../components/StatCard'

const MarketingPage = () => {
  const [timeRange, setTimeRange] = useState<number>(12)

  const {
    data: marketingData,
    isLoading,
    error,
    refetch,
  } = useMarketing(timeRange)

  if (isLoading) {
    return <LoadingSpinner message="Loading marketing metrics..." />
  }

  if (error) {
    return (
      <ErrorAlert
        message="Failed to load marketing data. Please try again."
        onRetry={refetch}
      />
    )
  }

  if (!marketingData) return null

  const roi = marketingData.roi

  return (
    <Box>
      {/* Page Header */}
      <Box sx={{ mb: 4, display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
        <Box>
          <Typography variant="h4" component="h1" fontWeight="bold" gutterBottom>
            Marketing Performance
          </Typography>
          <Typography variant="body2" color="text.secondary">
            Track marketing campaigns and ROI
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

      {/* Email Marketing Metrics */}
      <Paper sx={{ p: 3, mb: 4 }}>
        <Typography variant="h6" gutterBottom fontWeight="bold">
          Email Marketing
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
          Email campaign performance metrics
        </Typography>

        <Grid container spacing={3}>
          <Grid item xs={12} sm={6} md={4}>
            <StatCard
              title="Total Campaigns"
              value={marketingData.email_campaigns}
              icon={<CampaignIcon />}
              color="primary"
              subtitle={`Last ${timeRange} months`}
            />
          </Grid>
          <Grid item xs={12} sm={6} md={4}>
            <StatCard
              title="Avg Open Rate"
              value={marketingData.email_open_rate.toFixed(1)}
              unit="%"
              icon={<EmailIcon />}
              color="info"
              subtitle="Email opens"
            />
          </Grid>
          <Grid item xs={12} sm={6} md={4}>
            <StatCard
              title="Avg Click Rate"
              value={marketingData.email_click_rate.toFixed(1)}
              unit="%"
              icon={<EmailIcon />}
              color="secondary"
              subtitle="Link clicks"
            />
          </Grid>
        </Grid>
      </Paper>

      {/* Social Media Metrics */}
      <Paper sx={{ p: 3, mb: 4 }}>
        <Typography variant="h6" gutterBottom fontWeight="bold">
          Social Media
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
          Social media engagement and reach
        </Typography>

        <Grid container spacing={3}>
          <Grid item xs={12} sm={6}>
            <StatCard
              title="Total Posts"
              value={marketingData.social_posts}
              icon={<InsightsIcon />}
              color="primary"
              subtitle={`Last ${timeRange} months`}
            />
          </Grid>
          <Grid item xs={12} sm={6}>
            <StatCard
              title="Avg Engagement Rate"
              value={marketingData.social_engagement_rate.toFixed(1)}
              unit="%"
              icon={<InsightsIcon />}
              color="success"
              subtitle="Likes, comments, shares"
            />
          </Grid>
        </Grid>
      </Paper>

      {/* Ad Performance & ROI */}
      <Paper sx={{ p: 3, mb: 4 }}>
        <Typography variant="h6" gutterBottom fontWeight="bold">
          Advertising Performance
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
          Paid advertising spend and return on investment
        </Typography>

        <Grid container spacing={3}>
          <Grid item xs={12} sm={6} md={4}>
            <StatCard
              title="Ad Spend"
              value={`$${marketingData.ad_spend.toLocaleString()}`}
              icon={<MoneyIcon />}
              color="error"
              subtitle="Total investment"
            />
          </Grid>
          <Grid item xs={12} sm={6} md={4}>
            <StatCard
              title="Ad Revenue"
              value={`$${marketingData.ad_revenue.toLocaleString()}`}
              icon={<MoneyIcon />}
              color="success"
              subtitle="Revenue generated"
            />
          </Grid>
          <Grid item xs={12} sm={6} md={4}>
            <StatCard
              title="ROI"
              value={roi.toFixed(1)}
              unit="x"
              icon={<InsightsIcon />}
              color={roi >= 3 ? 'success' : roi >= 1.5 ? 'info' : 'warning'}
              subtitle="Return on investment"
            />
          </Grid>
        </Grid>

        {/* ROI Visual */}
        <Box sx={{ mt: 4, p: 3, bgcolor: 'background.default', borderRadius: 1 }}>
          <Typography variant="body2" color="text.secondary" gutterBottom>
            For every $1 spent on advertising, you generate:
          </Typography>
          <Typography variant="h3" fontWeight="bold" color="success.main">
            ${roi.toFixed(2)}
          </Typography>
        </Box>
      </Paper>

      {/* Insights */}
      <Paper sx={{ p: 3, bgcolor: 'primary.light', color: 'primary.contrastText' }}>
        <Typography variant="h6" gutterBottom>
          📊 Marketing Insights
        </Typography>
        <Typography variant="body2" paragraph>
          <strong>Email Performance:</strong>{' '}
          {marketingData.email_open_rate >= 20
            ? 'Your email open rate is above industry average. Keep engaging your audience!'
            : 'Consider improving subject lines and send times to boost open rates.'}
        </Typography>
        <Typography variant="body2" paragraph>
          <strong>Social Media:</strong>{' '}
          {marketingData.social_engagement_rate >= 3
            ? 'Excellent social media engagement! Your content resonates with your audience.'
            : 'Try different content types and posting times to improve engagement.'}
        </Typography>
        <Typography variant="body2">
          <strong>Advertising ROI:</strong>{' '}
          {roi >= 3
            ? 'Excellent ROI! Your advertising campaigns are highly profitable.'
            : roi >= 1.5
            ? 'Good ROI. Consider optimizing campaigns to improve returns.'
            : 'Your ROI needs improvement. Review targeting and messaging to increase conversions.'}
        </Typography>
      </Paper>
    </Box>
  )
}

export default MarketingPage
