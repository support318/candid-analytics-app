import { Card, CardContent, Typography, Box, Chip } from '@mui/material'
import {
  TrendingUp as TrendingUpIcon,
  TrendingDown as TrendingDownIcon,
  Remove as RemoveIcon,
} from '@mui/icons-material'

interface StatCardProps {
  title: string
  value: string | number
  unit?: string
  trend?: 'up' | 'down' | 'stable'
  changePercentage?: number
  subtitle?: string
  icon?: React.ReactElement
  color?: 'primary' | 'secondary' | 'success' | 'error' | 'warning' | 'info'
}

const StatCard = ({
  title,
  value,
  unit = '',
  trend,
  changePercentage,
  subtitle,
  icon,
  color = 'primary',
}: StatCardProps) => {
  const getTrendIcon = (): React.ReactElement | undefined => {
    switch (trend) {
      case 'up':
        return <TrendingUpIcon fontSize="small" />
      case 'down':
        return <TrendingDownIcon fontSize="small" />
      case 'stable':
        return <RemoveIcon fontSize="small" />
      default:
        return undefined
    }
  }

  const getTrendColor = () => {
    if (trend === 'up') return 'success'
    if (trend === 'down') return 'error'
    return 'default'
  }

  return (
    <Card
      sx={{
        height: '100%',
        display: 'flex',
        flexDirection: 'column',
        position: 'relative',
        overflow: 'visible',
      }}
    >
      <CardContent>
        {/* Header with icon */}
        <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2 }}>
          <Typography variant="body2" color="text.secondary" fontWeight={500}>
            {title}
          </Typography>
          {icon && (
            <Box
              sx={{
                bgcolor: `${color}.light`,
                color: `${color}.main`,
                p: 0.75,
                borderRadius: 1,
                display: 'flex',
              }}
            >
              {icon}
            </Box>
          )}
        </Box>

        {/* Main value */}
        <Box sx={{ mb: 1 }}>
          <Typography variant="h4" component="div" fontWeight="bold">
            {value}
            {unit && (
              <Typography
                component="span"
                variant="h6"
                color="text.secondary"
                sx={{ ml: 0.5 }}
              >
                {unit}
              </Typography>
            )}
          </Typography>
        </Box>

        {/* Trend and subtitle */}
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, flexWrap: 'wrap' }}>
          {trend && changePercentage !== undefined && (
            <Chip
              icon={getTrendIcon()}
              label={`${changePercentage > 0 ? '+' : ''}${changePercentage.toFixed(1)}%`}
              size="small"
              color={getTrendColor()}
              variant="outlined"
            />
          )}
          {subtitle && (
            <Typography variant="caption" color="text.secondary">
              {subtitle}
            </Typography>
          )}
        </Box>
      </CardContent>
    </Card>
  )
}

export default StatCard
