import {
    Home,
    RocketLaunch,
    Download,
    Api,
    Folder,
    Route,
    SettingsInputComponent,
    Verified,
    Public,
    DataObject,
    SyncAlt,
    Power,
    Extension,
    CloudUpload,
    Cached,
    Queue,
    Language,
    Security,
    Lock,
    Password,
    VpnKey,
    Settings,
    Publish,
    Build,
    Terminal,
    BugReport,
    Hub,
    MenuBook,
    List,
    Psychology,
    HelpOutline,
    Error,
    Quiz,
    People,
    Category,
    Code,
    PlayArrow,
    AddTask,
} from '@mui/icons-material';
import { type SvgIconTypeMap } from '@mui/material/SvgIcon';
import { type OverridableComponent } from '@mui/types';

type MuiIcon = OverridableComponent<SvgIconTypeMap<object, 'svg'>> & { muiName: string };

export type NavNode = {
    title: string;
    path?: string;
    children?: NavNode[];
    icon?: MuiIcon;
};

export const NAV: NavNode[] = [
    {
        title: 'Home',
        path: '/',
        icon: Home
    },
    {
        title: 'Getting Started',
        icon: RocketLaunch,
        children: [
            { title: 'Installation', path: '/getting-started/installation', icon: Download },
            { title: 'First API', path: '/getting-started/first-api', icon: Api },
        ]
    },
    {
        title: 'Fundamentals',
        icon: Category,
        children: [
            { title: 'Routing', path: '/fundamentals/routing', icon: Route },
            { title: 'Controllers', path: '/fundamentals/controllers', icon: SettingsInputComponent },
            { title: 'Validation', path: '/fundamentals/validation', icon: Verified },
            { title: 'HTTP Responses', path: '/fundamentals/http-responses', icon: Public },
            { title: 'Project Structure', path: '/fundamentals/project-structure', icon: Folder },
        ]
    },
    {
        title: 'Database & Models',
        icon: DataObject,
        children: [
            { title: 'Models & ORM', path: '/database/models-orm', icon: DataObject },
            { title: 'Migrations', path: '/database/migrations', icon: SyncAlt },
            { title: 'Database Drivers', path: '/database/drivers', icon: Power },
        ]
    },
    {
        title: 'Security',
        icon: Security,
        children: [
            { title: 'Overview', path: '/security/overview', icon: Lock },
            { title: 'Authentication', path: '/security/authentication', icon: Password },
            { title: 'Permissions', path: '/security/permissions', icon: VpnKey },
        ]
    },
    {
        title: 'Developer Tools',
        icon: Build,
        children: [
            { title: 'CLI Reference', path: '/tools/cli', icon: Terminal },
            { title: 'OpenAPI Generation', path: '/tools/openapi-types', icon: Api },
            { title: 'TypeScript SDK', path: '/tools/typescript-sdk', icon: Code },
            { title: 'Debugging', path: '/tools/debugging', icon: BugReport },
            { title: 'Dependency Injection', path: '/tools/dependency-injection', icon: Hub },
        ]
    },
    {
        title: 'Advanced Features',
        icon: Extension,
        children: [
            { title: 'File Storage', path: '/advanced/file-storage', icon: CloudUpload },
            { title: 'Caching', path: '/advanced/caching', icon: Cached },
            { title: 'Cache API Reference', path: '/advanced/caching-api', icon: Code },
            { title: 'Queue System', path: '/advanced/queue', icon: Queue },
            { title: 'Creating Jobs', path: '/advanced/queue-creating-jobs', icon: AddTask },
            { title: 'Processing Jobs', path: '/advanced/queue-processing-jobs', icon: PlayArrow },
            { title: 'Internationalization', path: '/advanced/i18n', icon: Language },
        ]
    },
    {
        title: 'Deployment',
        icon: RocketLaunch,
        children: [
            { title: 'Configuration', path: '/deployment/configuration', icon: Settings },
            { title: 'Production Setup', path: '/deployment/production', icon: Publish },
        ]
    },
    {
        title: 'Guides',
        icon: MenuBook,
        children: [
            { title: 'Building a CRUD API', path: '/guides/crud-api', icon: List },
            { title: 'OpenAI Integration', path: '/guides/openai', icon: Psychology },
        ]
    },
    {
        title: 'Troubleshooting',
        icon: HelpOutline,
        children: [
            { title: 'Common Errors', path: '/troubleshooting/common-errors', icon: Error },
            { title: 'FAQ', path: '/troubleshooting/faq', icon: Quiz },
        ]
    },
    {
        title: 'Community',
        path: '/community',
        icon: People
    },
];
