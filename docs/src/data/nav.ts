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
      { title: 'File Storage', path: '/architecture/file-storage' },
    ]
  },
  { 
    title: 'Database', 
    children: [
      { title: 'Drivers', path: '/database/drivers' },
    ]
  },
  { 
    title: 'Configuration', 
    children: [
      { title: 'Environment', path: '/configuration/env' },
      { title: 'Caching', path: '/configuration/caching' },
    ]
  },
  { 
    title: 'Dependency Injection', 
    children: [
      { title: 'Container', path: '/di/container' },
    ]
  },
  { 
    title: 'CLI', 
    children: [
      { title: 'Overview', path: '/cli/overview' },
    ]
  },
  { 
    title: 'Internationalization', 
    children: [
      { title: 'Overview', path: '/i18n/overview' },
    ]
  },
  { 
    title: 'Security', 
    children: [
      { title: 'Overview', path: '/security/overview' },
    ]
  },
  { 
    title: 'Guides', 
    children: [
      { title: 'CRUD API', path: '/guides/crud-api' },
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
    title: 'Roadmap', 
    path: '/roadmap' 
  },
];
