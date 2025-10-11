import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import { lazy } from 'react';
import RootLayout from './layout/RootLayout';

// Lazy load pages for code splitting
const Home = lazy(() => import('./pages/Home'));

// Getting Started
const Installation = lazy(() => import('./pages/GettingStarted/Installation'));
const FirstApi = lazy(() => import('./pages/GettingStarted/FirstApi'));
const ProjectStructure = lazy(() => import('./pages/GettingStarted/ProjectStructure'));

// Fundamentals (renamed from Architecture)
const FundamentalsOverview = lazy(() => import('./pages/Architecture/Overview'));
const Routing = lazy(() => import('./pages/Architecture/Routing'));
const Controllers = lazy(() => import('./pages/Architecture/Controllers'));
const Validation = lazy(() => import('./pages/Architecture/Validation'));
const HttpResponses = lazy(() => import('./pages/Reference/HttpResponses'));

// Database & Models
const ModelsOrm = lazy(() => import('./pages/Architecture/ModelsOrm'));
const Migrations = lazy(() => import('./pages/Architecture/Migrations'));
const DatabaseDrivers = lazy(() => import('./pages/Database/Drivers'));

// Advanced Features
const FileStorage = lazy(() => import('./pages/Architecture/FileStorage'));
const Caching = lazy(() => import('./pages/Caching/Configuration'));
const QueueSystem = lazy(() => import('./pages/Queue/Overview'));
const I18n = lazy(() => import('./pages/I18n/Overview'));

// Security
const SecurityOverview = lazy(() => import('./pages/Security/Overview'));
const Authentication = lazy(() => import('./pages/Security/ApiTokenAuth'));

// Deployment
const Configuration = lazy(() => import('./pages/Configuration/Env'));
const ProductionSetup = lazy(() => import('./pages/Deployment/Production'));

// Developer Tools
const CLIReference = lazy(() => import('./pages/CLI/Overview'));
const Debugging = lazy(() => import('./pages/Development/Debugging'));
const OpenApiTypes = lazy(() => import('./pages/Reference/OpenApi'));
const DependencyInjection = lazy(() => import('./pages/DI/Container'));

// Guides
const CrudApi = lazy(() => import('./pages/Guides/CrudApi'));
const OpenAiIntegration = lazy(() => import('./pages/Reference/OpenAiApi'));

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
            { path: 'getting-started/project-structure', element: <ProjectStructure /> },

            // Fundamentals
            { path: 'fundamentals/overview', element: <FundamentalsOverview /> },
            { path: 'fundamentals/routing', element: <Routing /> },
            { path: 'fundamentals/controllers', element: <Controllers /> },
            { path: 'fundamentals/validation', element: <Validation /> },
            { path: 'fundamentals/http-responses', element: <HttpResponses /> },

            // Database & Models
            { path: 'database/models-orm', element: <ModelsOrm /> },
            { path: 'database/migrations', element: <Migrations /> },
            { path: 'database/drivers', element: <DatabaseDrivers /> },

            // Advanced Features
            { path: 'advanced/file-storage', element: <FileStorage /> },
            { path: 'advanced/caching', element: <Caching /> },
            { path: 'advanced/queue', element: <QueueSystem /> },
            { path: 'advanced/i18n', element: <I18n /> },

            // Security
            { path: 'security/overview', element: <SecurityOverview /> },
            { path: 'security/authentication', element: <Authentication /> },

            // Deployment
            { path: 'deployment/configuration', element: <Configuration /> },
            { path: 'deployment/production', element: <ProductionSetup /> },

            // Developer Tools
            { path: 'tools/cli', element: <CLIReference /> },
            { path: 'tools/debugging', element: <Debugging /> },
            { path: 'tools/openapi-types', element: <OpenApiTypes /> },
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
