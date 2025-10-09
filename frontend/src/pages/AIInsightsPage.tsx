import {
  Box,
  Typography,
  Paper,
  Grid,
  Card,
  CardContent,
  Chip,
  Alert,
  AlertTitle,
} from '@mui/material'
import {
  AutoAwesome as AIIcon,
  TrendingUp as TrendingUpIcon,
  Warning as WarningIcon,
  Lightbulb as LightbulbIcon,
} from '@mui/icons-material'
import { useAIInsights } from '../hooks/useAnalytics'
import LoadingSpinner from '../components/LoadingSpinner'
import ErrorAlert from '../components/ErrorAlert'

const AIInsightsPage = () => {
  const {
    data: insights,
    isLoading,
    error,
    refetch,
  } = useAIInsights()

  if (isLoading) {
    return <LoadingSpinner message="Analyzing your data with AI..." />
  }

  if (error) {
    return (
      <ErrorAlert
        message="Failed to load AI insights. Please try again."
        onRetry={refetch}
      />
    )
  }

  if (!insights || insights.length === 0) {
    return (
      <Box sx={{ p: 3 }}>
        <Alert severity="info">
          <AlertTitle>No AI Insights Available</AlertTitle>
          AI insights will appear here once you have sufficient data. Keep using the system
          and check back soon!
        </Alert>
      </Box>
    )
  }

  // Group insights by impact level
  const highImpact = insights.filter((i) => i.impact === 'high')
  const mediumImpact = insights.filter((i) => i.impact === 'medium')
  const lowImpact = insights.filter((i) => i.impact === 'low')

  const getImpactColor = (impact: 'high' | 'medium' | 'low') => {
    switch (impact) {
      case 'high':
        return 'error'
      case 'medium':
        return 'warning'
      case 'low':
        return 'info'
    }
  }

  const getImpactIcon = (impact: 'high' | 'medium' | 'low') => {
    switch (impact) {
      case 'high':
        return <WarningIcon />
      case 'medium':
        return <TrendingUpIcon />
      case 'low':
        return <LightbulbIcon />
    }
  }

  const renderInsightCard = (insight: typeof insights[0]) => (
    <Card key={`${insight.insight_type}-${insight.title}`} sx={{ height: '100%' }}>
      <CardContent>
        {/* Header */}
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', mb: 2 }}>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <Box
              sx={{
                bgcolor: `${getImpactColor(insight.impact)}.light`,
                color: `${getImpactColor(insight.impact)}.main`,
                p: 1,
                borderRadius: 1,
                display: 'flex',
              }}
            >
              {getImpactIcon(insight.impact)}
            </Box>
            <Typography variant="caption" color="text.secondary" textTransform="uppercase">
              {insight.insight_type.replace('_', ' ')}
            </Typography>
          </Box>
          <Chip
            label={`${insight.impact} impact`}
            size="small"
            color={getImpactColor(insight.impact)}
          />
        </Box>

        {/* Title */}
        <Typography variant="h6" fontWeight="bold" gutterBottom>
          {insight.title}
        </Typography>

        {/* Description */}
        <Typography variant="body2" color="text.secondary" paragraph>
          {insight.description}
        </Typography>

        {/* Recommendation */}
        <Paper sx={{ p: 2, bgcolor: 'primary.light', color: 'primary.contrastText' }}>
          <Typography variant="caption" fontWeight="bold" display="block" gutterBottom>
            💡 RECOMMENDED ACTION
          </Typography>
          <Typography variant="body2">{insight.recommendation}</Typography>
        </Paper>

        {/* Confidence Score */}
        <Box sx={{ mt: 2, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <Typography variant="caption" color="text.secondary">
            AI Confidence
          </Typography>
          <Chip
            label={`${(insight.confidence * 100).toFixed(0)}%`}
            size="small"
            variant="outlined"
          />
        </Box>
      </CardContent>
    </Card>
  )

  return (
    <Box>
      {/* Page Header */}
      <Box sx={{ mb: 4 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 1 }}>
          <AIIcon sx={{ fontSize: 40, color: 'primary.main' }} />
          <Typography variant="h4" component="h1" fontWeight="bold">
            AI-Powered Insights
          </Typography>
        </Box>
        <Typography variant="body2" color="text.secondary">
          Intelligent recommendations powered by machine learning analysis of your business data
        </Typography>
      </Box>

      {/* Summary Stats */}
      <Grid container spacing={2} sx={{ mb: 4 }}>
        <Grid item xs={12} sm={4}>
          <Paper sx={{ p: 2, textAlign: 'center', bgcolor: 'error.light', color: 'error.contrastText' }}>
            <Typography variant="h4" fontWeight="bold">
              {highImpact.length}
            </Typography>
            <Typography variant="body2">High Priority Insights</Typography>
          </Paper>
        </Grid>
        <Grid item xs={12} sm={4}>
          <Paper sx={{ p: 2, textAlign: 'center', bgcolor: 'warning.light', color: 'warning.contrastText' }}>
            <Typography variant="h4" fontWeight="bold">
              {mediumImpact.length}
            </Typography>
            <Typography variant="body2">Medium Priority Insights</Typography>
          </Paper>
        </Grid>
        <Grid item xs={12} sm={4}>
          <Paper sx={{ p: 2, textAlign: 'center', bgcolor: 'info.light', color: 'info.contrastText' }}>
            <Typography variant="h4" fontWeight="bold">
              {lowImpact.length}
            </Typography>
            <Typography variant="body2">Low Priority Insights</Typography>
          </Paper>
        </Grid>
      </Grid>

      {/* High Impact Insights */}
      {highImpact.length > 0 && (
        <Box sx={{ mb: 4 }}>
          <Typography variant="h5" fontWeight="bold" gutterBottom sx={{ color: 'error.main' }}>
            🔥 High Priority - Take Action Now
          </Typography>
          <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
            These insights require immediate attention and could significantly impact your business
          </Typography>
          <Grid container spacing={3}>
            {highImpact.map((insight, index) => (
              <Grid item xs={12} md={6} key={index}>
                {renderInsightCard(insight)}
              </Grid>
            ))}
          </Grid>
        </Box>
      )}

      {/* Medium Impact Insights */}
      {mediumImpact.length > 0 && (
        <Box sx={{ mb: 4 }}>
          <Typography variant="h5" fontWeight="bold" gutterBottom sx={{ color: 'warning.main' }}>
            ⚡ Medium Priority - Address Soon
          </Typography>
          <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
            Important opportunities to improve your business performance
          </Typography>
          <Grid container spacing={3}>
            {mediumImpact.map((insight, index) => (
              <Grid item xs={12} md={6} key={index}>
                {renderInsightCard(insight)}
              </Grid>
            ))}
          </Grid>
        </Box>
      )}

      {/* Low Impact Insights */}
      {lowImpact.length > 0 && (
        <Box>
          <Typography variant="h5" fontWeight="bold" gutterBottom sx={{ color: 'info.main' }}>
            💡 Low Priority - Consider When Possible
          </Typography>
          <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
            Minor optimizations and suggestions for continuous improvement
          </Typography>
          <Grid container spacing={3}>
            {lowImpact.map((insight, index) => (
              <Grid item xs={12} md={6} key={index}>
                {renderInsightCard(insight)}
              </Grid>
            ))}
          </Grid>
        </Box>
      )}

      {/* Info Box */}
      <Paper sx={{ p: 3, mt: 4, bgcolor: 'primary.light', color: 'primary.contrastText' }}>
        <Typography variant="h6" gutterBottom>
          🤖 How AI Insights Work
        </Typography>
        <Typography variant="body2" paragraph>
          Our AI analyzes your business data including revenue trends, client behavior, lead conversion
          patterns, and operational metrics to identify opportunities and potential issues.
        </Typography>
        <Typography variant="body2">
          Insights are updated daily and ranked by potential impact on your business. The confidence
          score indicates how certain the AI is about each recommendation based on historical data
          patterns.
        </Typography>
      </Paper>
    </Box>
  )
}

export default AIInsightsPage
