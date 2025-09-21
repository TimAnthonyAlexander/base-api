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
        icon: 'rocket_launch',
        children: [
            { title: 'Installation', path: '/getting-started/installation', icon: 'download' },
            { title: 'First API', path: '/getting-started/first-api', icon: 'api' },
            { title: 'Project Structure', path: '/getting-started/project-structure', icon: 'folder' },
        ]
    },
    {
        title: 'Architecture',
        icon: 'architecture',
        children: [
            { title: 'Overview', path: '/architecture/overview', icon: 'visibility' },
            { title: 'Routing', path: '/architecture/routing', icon: 'route' },
            { title: 'Controllers', path: '/architecture/controllers', icon: 'settings_input_component' },
            { title: 'Models & ORM', path: '/architecture/models-orm', icon: 'schema' },
            { title: 'Migrations', path: '/architecture/migrations', icon: 'sync_alt' },
            { title: 'Validation', path: '/architecture/validation', icon: 'verified' },
            { title: 'File Storage', path: '/architecture/file-storage', icon: 'cloud_upload' },
        ]
    },
    {
        title: 'Queue System',
        icon: 'queue',
        children: [
            { title: 'Overview', path: '/queue/overview', icon: 'visibility' },
            { title: 'Creating Jobs', path: '/queue/creating-jobs', icon: 'add_task' },
            { title: 'Processing Jobs', path: '/queue/processing-jobs', icon: 'play_arrow' },
            { title: 'Configuration', path: '/queue/configuration', icon: 'tune' },
        ]
    },
    {
        title: 'Database',
        icon: 'storage',
        children: [
            { title: 'Drivers', path: '/database/drivers', icon: 'power' },
        ]
    },
    {
        title: 'Configuration',
        icon: 'settings',
        children: [
            { title: 'Environment', path: '/configuration/env', icon: 'eco' },
            { title: 'Caching', path: '/configuration/caching', icon: 'cached' },
        ]
    },
    {
        title: 'Deployment',
        icon: 'cloud_upload',
        children: [
            { title: 'Production Setup', path: '/deployment/production', icon: 'rocket' },
        ]
    },
    {
        title: 'Dependency Injection',
        icon: 'hub',
        children: [
            { title: 'Container', path: '/di/container', icon: 'inventory_2' },
        ]
    },
    {
        title: 'CLI',
        icon: 'terminal',
        children: [
            { title: 'Overview', path: '/cli/overview', icon: 'visibility' },
        ]
    },
    {
        title: 'Development',
        icon: 'build',
        children: [
            { title: 'Debug & Profiling', path: '/development/debugging', icon: 'bug_report' },
        ]
    },
    {
        title: 'Internationalization',
        icon: 'language',
        children: [
            { title: 'Overview', path: '/i18n/overview', icon: 'visibility' },
        ]
    },
    {
        title: 'Security',
        icon: 'security',
        children: [
            { title: 'Overview', path: '/security/overview', icon: 'visibility' },
        ]
    },
    {
        title: 'Guides',
        icon: 'menu_book',
        children: [
            { title: 'CRUD API', path: '/guides/crud-api', icon: 'api' },
        ]
    },
    {
        title: 'Reference',
        icon: 'library_books',
        children: [
            { title: 'OpenAPI', path: '/reference/openapi', icon: 'api' },
            { title: 'HTTP Responses', path: '/reference/http-responses', icon: 'http' },
            { title: 'Cache API', path: '/reference/cache-api', icon: 'cached' },
        ]
    },
    {
        title: 'Troubleshooting',
        icon: 'help_outline',
        children: [
            { title: 'Common Errors', path: '/troubleshooting/common-errors', icon: 'error' },
            { title: 'FAQ', path: '/troubleshooting/faq', icon: 'quiz' },
        ]
    },
    {
        title: 'Community',
        path: '/community',
        icon: 'people'
    },
];
