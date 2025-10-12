import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import { lazy } from 'react';
import RootLayout from './layout/RootLayout';

// Lazy load pages for code splitting
const Home = lazy(() => import('./pages/Home'));

// Getting Started
const Installation = lazy(() => import('./pages/GettingStarted/Installation'));
const FirstApi = lazy(() => import('./pages/GettingStarted/FirstApi'));
const ProjectStructure = lazy(() => import('./pages/GettingStarted/ProjectStructure'));

// Fundamentals
const Routing = lazy(() => import('./pages/Fundamentals/Routing'));
const Controllers = lazy(() => import('./pages/Fundamentals/Controllers'));
const Validation = lazy(() => import('./pages/Fundamentals/Validation'));
const HttpResponses = lazy(() => import('./pages/Fundamentals/HttpResponses'));

// Database & Models
const ModelsOrm = lazy(() => import('./pages/Database/ModelsOrm'));
const Migrations = lazy(() => import('./pages/Database/Migrations'));
const DatabaseDrivers = lazy(() => import('./pages/Database/Drivers'));

// Advanced Features
const FileStorage = lazy(() => import('./pages/Advanced/FileStorage'));
const Caching = lazy(() => import('./pages/Advanced/Caching'));
const CachingApi = lazy(() => import('./pages/Advanced/CachingApi'));
const QueueSystem = lazy(() => import('./pages/Advanced/Queue'));
const QueueCreatingJobs = lazy(() => import('./pages/Advanced/QueueCreatingJobs'));
const QueueProcessingJobs = lazy(() => import('./pages/Advanced/QueueProcessingJobs'));
const I18n = lazy(() => import('./pages/Advanced/I18n'));

// Security
const SecurityOverview = lazy(() => import('./pages/Security/Overview'));
const Authentication = lazy(() => import('./pages/Security/ApiTokenAuth'));
const Permissions = lazy(() => import('./pages/Security/Permissions'));

// Deployment
const Configuration = lazy(() => import('./pages/Deployment/Configuration'));
const ProductionSetup = lazy(() => import('./pages/Deployment/Production'));

// Developer Tools
const CLIReference = lazy(() => import('./pages/Tools/Cli'));
const Debugging = lazy(() => import('./pages/Tools/Debugging'));
const OpenApiTypes = lazy(() => import('./pages/Tools/OpenApiTypes'));
const TypeScriptSdk = lazy(() => import('./pages/Tools/TypeScriptSdk'));
const DependencyInjection = lazy(() => import('./pages/Tools/DependencyInjection'));

// Guides
const CrudApi = lazy(() => import('./pages/Guides/CrudApi'));
const OpenAiIntegration = lazy(() => import('./pages/Guides/OpenAi'));

// Troubleshooting
const CommonErrors = lazy(() => import('./pages/Troubleshooting/CommonErrors'));
const Faq = lazy(() => import('./pages/Troubleshooting/Faq'));

// Community
const Community = lazy(() => import('./pages/Community'));

const router = createBrowserRouter([
    {
        path: '/',
        element: <RootLayout />,
        children: [
            { index: true, element: <Home /> },

            // Getting Started
            { path: 'getting-started/installation', element: <Installation /> },
            { path: 'getting-started/first-api', element: <FirstApi /> },

            // Fundamentals
            { path: 'fundamentals/routing', element: <Routing /> },
            { path: 'fundamentals/controllers', element: <Controllers /> },
            { path: 'fundamentals/validation', element: <Validation /> },
            { path: 'fundamentals/http-responses', element: <HttpResponses /> },
            { path: 'fundamentals/project-structure', element: <ProjectStructure /> },

            // Database & Models
            { path: 'database/models-orm', element: <ModelsOrm /> },
            { path: 'database/migrations', element: <Migrations /> },
            { path: 'database/drivers', element: <DatabaseDrivers /> },

            // Advanced Features
            { path: 'advanced/file-storage', element: <FileStorage /> },
            { path: 'advanced/caching', element: <Caching /> },
            { path: 'advanced/caching-api', element: <CachingApi /> },
            { path: 'advanced/queue', element: <QueueSystem /> },
            { path: 'advanced/queue-creating-jobs', element: <QueueCreatingJobs /> },
            { path: 'advanced/queue-processing-jobs', element: <QueueProcessingJobs /> },
            { path: 'advanced/i18n', element: <I18n /> },

            // Security
            { path: 'security/overview', element: <SecurityOverview /> },
            { path: 'security/authentication', element: <Authentication /> },
            { path: 'security/permissions', element: <Permissions /> },

            // Deployment
            { path: 'deployment/configuration', element: <Configuration /> },
            { path: 'deployment/production', element: <ProductionSetup /> },

            // Developer Tools
            { path: 'tools/cli', element: <CLIReference /> },
            { path: 'tools/debugging', element: <Debugging /> },
            { path: 'tools/openapi-types', element: <OpenApiTypes /> },
            { path: 'tools/typescript-sdk', element: <TypeScriptSdk /> },
            { path: 'tools/dependency-injection', element: <DependencyInjection /> },

            // Guides
            { path: 'guides/crud-api', element: <CrudApi /> },
            { path: 'guides/openai', element: <OpenAiIntegration /> },

            // Troubleshooting
            { path: 'troubleshooting/common-errors', element: <CommonErrors /> },
            { path: 'troubleshooting/faq', element: <Faq /> },

            // Community
            { path: 'community', element: <Community /> },
        ],
    },
]);

export default function AppRouter() {
    return <RouterProvider router={router} />;
}
