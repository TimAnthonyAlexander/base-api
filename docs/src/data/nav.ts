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
        title: 'Fundamentals',
        icon: 'foundation',
        children: [
            { title: 'Overview', path: '/fundamentals/overview', icon: 'visibility' },
            { title: 'Routing', path: '/fundamentals/routing', icon: 'route' },
            { title: 'Controllers', path: '/fundamentals/controllers', icon: 'settings_input_component' },
            { title: 'Validation', path: '/fundamentals/validation', icon: 'verified' },
            { title: 'HTTP Responses', path: '/fundamentals/http-responses', icon: 'http' },
        ]
    },
    {
        title: 'Database & Models',
        icon: 'storage',
        children: [
            { title: 'Models & ORM', path: '/database/models-orm', icon: 'schema' },
            { title: 'Migrations', path: '/database/migrations', icon: 'sync_alt' },
            { title: 'Database Drivers', path: '/database/drivers', icon: 'power' },
        ]
    },
    {
        title: 'Advanced Features',
        icon: 'auto_awesome',
        children: [
            { title: 'File Storage', path: '/advanced/file-storage', icon: 'cloud_upload' },
            { title: 'Caching', path: '/advanced/caching', icon: 'cached' },
            { title: 'Queue System', path: '/advanced/queue', icon: 'queue' },
            { title: 'Internationalization', path: '/advanced/i18n', icon: 'language' },
        ]
    },
    {
        title: 'Security',
        icon: 'security',
        children: [
            { title: 'Overview', path: '/security/overview', icon: 'shield' },
            { title: 'Authentication', path: '/security/authentication', icon: 'key' },
        ]
    },
    {
        title: 'Deployment',
        icon: 'rocket',
        children: [
            { title: 'Configuration', path: '/deployment/configuration', icon: 'settings' },
            { title: 'Production Setup', path: '/deployment/production', icon: 'cloud_done' },
        ]
    },
    {
        title: 'Developer Tools',
        icon: 'build',
        children: [
            { title: 'CLI Reference', path: '/tools/cli', icon: 'terminal' },
            { title: 'Debugging', path: '/tools/debugging', icon: 'bug_report' },
            { title: 'OpenAPI & Types', path: '/tools/openapi-types', icon: 'api' },
            { title: 'Dependency Injection', path: '/tools/dependency-injection', icon: 'hub' },
        ]
    },
    {
        title: 'Guides',
        icon: 'menu_book',
        children: [
            { title: 'Building a CRUD API', path: '/guides/crud-api', icon: 'edit_note' },
            { title: 'OpenAI Integration', path: '/guides/openai', icon: 'psychology' },
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
