'use client';

/**
 * A dependency-free SVG donut chart built from stroke-dasharray segments.
 * Segments are proportional to their value and each carries a hover title;
 * the legend beside it is plain list content, readable by screen readers
 * without the graphic.
 */

export interface DonutSegment {
  label: string;
  value: number;
  /** Tailwind stroke class for the SVG arc, from the M3 token palette. */
  strokeClass: string;
  /** Tailwind background class for the legend swatch. */
  swatchClass: string;
}

interface DonutChartProps {
  segments: DonutSegment[];
  ariaLabel: string;
  size?: number;
}

const CIRCUMFERENCE = 2 * Math.PI * 40;

export function DonutChart({ segments, ariaLabel, size = 140 }: DonutChartProps) {
  const total = segments.reduce((sum, segment) => sum + segment.value, 0);

  const arcs: Array<{ segment: DonutSegment; dash: number; offset: number }> = [];
  let consumed = 0;

  if (total > 0) {
    for (const segment of segments) {
      const dash = (segment.value / total) * CIRCUMFERENCE;
      arcs.push({ segment, dash, offset: -consumed });
      consumed += dash;
    }
  }

  const ariaText =
    ariaLabel +
    '. ' +
    segments.map((segment) => `${segment.label}: ${segment.value}`).join(', ');

  return (
    <div className="flex items-center gap-5">
      <svg
        role="img"
        aria-label={ariaText}
        width={size}
        height={size}
        viewBox="0 0 100 100"
        className="shrink-0 -rotate-90"
      >
        <circle cx="50" cy="50" r="40" className="fill-none stroke-surface-container-high" strokeWidth="14" />
        {total === 0 ? (
          <text
            x="50"
            y="50"
            textAnchor="middle"
            dominantBaseline="central"
            className="rotate-90 fill-current text-on-surface-variant"
            style={{ transformOrigin: '50px 50px' }}
          >
            No data
          </text>
        ) : (
          arcs.map(({ segment, dash, offset }) => (
            <circle
              key={segment.label}
              cx="50"
              cy="50"
              r="40"
              fill="none"
              strokeWidth="14"
              strokeLinecap="butt"
              className={segment.strokeClass}
              strokeDasharray={`${dash} ${CIRCUMFERENCE - dash}`}
              strokeDashoffset={offset}
            >
              <title>
                {segment.label}: {segment.value}
              </title>
            </circle>
          ))
        )}
      </svg>
      <ul className="space-y-2 text-sm">
        {segments.map((segment) => (
          <li key={segment.label} className="flex items-center gap-2">
            <span className={`inline-block h-3 w-3 rounded-sm ${segment.swatchClass}`} aria-hidden="true" />
            <span className="text-on-surface-variant">{segment.label}</span>
            <span className="ml-auto font-medium text-on-surface">{segment.value}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}
