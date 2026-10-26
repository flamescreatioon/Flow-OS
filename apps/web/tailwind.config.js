/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './src/pages/**/*.{js,ts,jsx,tsx,mdx}',
    './src/components/**/*.{js,ts,jsx,tsx,mdx}',
    './src/app/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  theme: {
    extend: {
      // Material Design 3-style roles backed by the CSS custom properties
      // defined in src/app/globals.css.
      colors: {
        primary: {
          DEFAULT: 'rgb(var(--color-primary) / <alpha-value>)',
          container: 'rgb(var(--color-primary-container) / <alpha-value>)',
        },
        'on-primary': {
          DEFAULT: 'rgb(var(--color-on-primary) / <alpha-value>)',
          container: 'rgb(var(--color-on-primary-container) / <alpha-value>)',
        },
        'secondary-container':
          'rgb(var(--color-secondary-container) / <alpha-value>)',
        'on-secondary-container':
          'rgb(var(--color-on-secondary-container) / <alpha-value>)',
        surface: {
          DEFAULT: 'rgb(var(--color-surface) / <alpha-value>)',
          container: 'rgb(var(--color-surface-container) / <alpha-value>)',
          'container-high':
            'rgb(var(--color-surface-container-high) / <alpha-value>)',
        },
        'on-surface': {
          DEFAULT: 'rgb(var(--color-on-surface) / <alpha-value>)',
          variant: 'rgb(var(--color-on-surface-variant) / <alpha-value>)',
        },
        scrim: 'rgb(var(--color-scrim) / <alpha-value>)',
        success: {
          DEFAULT: 'rgb(var(--color-success) / <alpha-value>)',
          container: 'rgb(var(--color-success-container) / <alpha-value>)',
        },
        'on-success-container':
          'rgb(var(--color-on-success-container) / <alpha-value>)',
        warning: {
          DEFAULT: 'rgb(var(--color-warning) / <alpha-value>)',
          container: 'rgb(var(--color-warning-container) / <alpha-value>)',
        },
        'on-warning-container':
          'rgb(var(--color-on-warning-container) / <alpha-value>)',
        danger: {
          DEFAULT: 'rgb(var(--color-danger) / <alpha-value>)',
          container: 'rgb(var(--color-danger-container) / <alpha-value>)',
        },
        'on-danger-container':
          'rgb(var(--color-on-danger-container) / <alpha-value>)',
      },
      fontFamily: {
        sans: [
          'Inter',
          'system-ui',
          '-apple-system',
          'BlinkMacSystemFont',
          'Segoe UI',
          'Roboto',
          'sans-serif',
        ],
      },
    },
  },
  plugins: [],
};
