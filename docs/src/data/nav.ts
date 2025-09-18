export type NavNode = { 
  title: string; 
  path?: string; 
  children?: NavNode[];
  icon?: string;
};

export const NAV: NavNode[] = [
  { 
    title: 'Home', 
    path: '/',
    icon: 'home'
  },
  { 
    title: 'Getting Started', 
    children: [
      { title: 'Installation', path: '/getting-started/installation' },
      { title: 'First API', path: '/getting-started/first-api' },
      { title: 'Project Structure', path: '/getting-started/project-structure' },
    ]
  },
  { 
    title: 'Architecture', 
    children: [
      { title: 'Overview', path: '/architecture/overview' },
      { title: 'Routing', path: '/architecture/routing' },
      { title: 'Controllers', path: '/architecture/controllers' },
      { title: 'Models & ORM', path: '/architecture/models-orm' },
      { title: 'Migrations', path: '/architecture/migrations' },
      { title: 'Validation', path: '/architecture/validation' },
    ]
  },
  { 
    title: 'Database', 
    children: [
      { title: 'Drivers', path: '/database/drivers' },
      { title: 'Seeding', path: '/database/seeding' },
      { title: 'Transactions', path: '/database/transactions' },
    ]
  },
  { 
    title: 'Configuration', 
    children: [
      { title: 'Environment', path: '/configuration/env' },
      { title: 'Caching', path: '/configuration/caching' },
      { title: 'Rate Limiting', path: '/configuration/rate-limiting' },
      { title: 'CORS', path: '/configuration/cors' },
      { title: 'Logging', path: '/configuration/logging' },
    ]
  },
  { 
    title: 'Dependency Injection', 
    children: [
      { title: 'Container', path: '/di/container' },
      { title: 'Service Providers', path: '/di/service-providers' },
      { title: 'Testing with DI', path: '/di/testing-with-di' },
    ]
  },
  { 
    title: 'CLI', 
    children: [
      { title: 'Overview', path: '/cli/overview' },
      { title: 'Development', path: '/cli/development' },
      { title: 'Database', path: '/cli/database' },
      { title: 'Docs Generation', path: '/cli/docs-generation' },
      { title: 'Caching', path: '/cli/caching' },
    ]
  },
  { 
    title: 'Internationalization', 
    children: [
      { title: 'Overview', path: '/i18n/overview' },
      { title: 'Scan & Fill', path: '/i18n/scan-fill' },
      { title: 'Providers', path: '/i18n/providers' },
    ]
  },
  { 
    title: 'Security', 
    children: [
      { title: 'Overview', path: '/security/overview' },
      { title: 'Authentication', path: '/security/auth' },
      { title: 'Input Validation', path: '/security/input-validation' },
      { title: 'Hardening Checklist', path: '/security/hardening-checklist' },
    ]
  },
  { 
    title: 'Performance', 
    children: [
      { title: 'Benchmarks', path: '/performance/benchmarks' },
      { title: 'Caching Strategies', path: '/performance/caching-strategies' },
      { title: 'Production Tuning', path: '/performance/production-tuning' },
    ]
  },
  { 
    title: 'Guides', 
    children: [
      { title: 'CRUD API', path: '/guides/crud-api' },
      { title: 'File Uploads', path: '/guides/file-uploads' },
      { title: 'Pagination & Sorting', path: '/guides/pagination-sorting' },
      { title: 'Webhooks', path: '/guides/webhooks' },
      { title: 'Versioned APIs', path: '/guides/versioned-apis' },
    ]
  },
  { 
    title: 'Reference', 
    children: [
      { title: 'OpenAPI', path: '/reference/openapi' },
      { title: 'HTTP Responses', path: '/reference/http-responses' },
      { title: 'Cache API', path: '/reference/cache-api' },
    ]
  },
  { 
    title: 'Troubleshooting', 
    children: [
      { title: 'Common Errors', path: '/troubleshooting/common-errors' },
      { title: 'FAQ', path: '/troubleshooting/faq' },
    ]
  },
  { 
    title: 'Contributing', 
    children: [
      { title: 'Guide', path: '/contributing/guide' },
      { title: 'Code of Conduct', path: '/contributing/code-of-conduct' },
      { title: 'Release Process', path: '/contributing/release-process' },
    ]
  },
  { 
    title: 'Roadmap', 
    path: '/roadmap' 
  },
];
