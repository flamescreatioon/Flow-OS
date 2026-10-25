import coreWebVitals from 'eslint-config-next/core-web-vitals';

export default [
  ...coreWebVitals,
  {
    rules: {
      'react/no-unescaped-entities': 'off',
    },
  },
];
