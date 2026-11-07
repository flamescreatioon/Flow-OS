'use client';

/**
 * A dependency-free SVG bar chart for dashboard-scale data. Every bar is a
 * rect sized by proportion; the chart exposes an aria label so screen
 * readers get the values, and each bar carries a <title> for hover context.
 */

export interface BarChartPoint {
  label: string;
  value: number;
}

interface BarChartProps {
  points: BarChartPoint[];
  /** Accessible summary of what the chart shows, including the values. */
  ariaLabel: string;
  height?: number;
  /** Formats the value shown in each bar's hover title. */
  formatValue?: (value: number) => string;
}

export function BarChart({ points, ariaLabel, height = 160, formatValue }: BarChartProps) {
  const width = 560;
  const padding = { top: 8, right: 8, bottom: 24, left: 8 };
  const plotHeight = height - padding.top - padding.bottom;
  const slot = (width - padding.left - padding.right) / Math.max(points.length, 1);
  const barWidth = Math.min(slot * 0.6, 56);
  const max = Math.max(...points.map((point) => point.value), 0);

  const ariaText =
    ariaLabel +
    '. ' +
    points.map((point) => `${point.label}: ${formatValue ? formatValue(point.value) : point.value}`).join(', ');

  return (
    <svg
      role="img"
      aria-label={ariaText}
      viewBox={`0 0 ${width} ${height}`}
      className="w-full"
      preserveAspectRatio="xMidYMid meet"
    >
      {max === 0 ? (
        <text
          x={width / 2}
          y={padding.top + plotHeight / 2}
          textAnchor="middle"
          className="fill-current text-on-surface-variant"
        >
          No data yet
        </text>
      ) : (
        points.map((point, index) => {
          const barHeight = Math.max((point.value / max) * plotHeight, point.value > 0 ? 4 : 0);
          const x = padding.left + index * slot + (slot - barWidth) / 2;
          const y = padding.top + plotHeight - barHeight;
          return (
            <g key={point.label}>
              <rect
                x={x}
                y={y}
                width={barWidth}
                height={barHeight}
                rx={6}
                className="fill-primary"
              >
                <title>
                  {point.label}: {formatValue ? formatValue(point.value) : point.value}
                </title>
              </rect>
              <text
                x={x + barWidth / 2}
                y={height - 6}
                textAnchor="middle"
                className="fill-current text-on-surface-variant"
              >
                {point.label}
              </text>
            </g>
          );
        })
      )}
    </svg>
  );
}
