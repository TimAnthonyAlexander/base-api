import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import { lazy } from 'react';
import RootLayout from './layout/RootLayout';

// Lazy load pages for code splitting
const Home = lazy(() => import('./pages/Home'));
const Installation = lazy(() => import('./pages/GettingStarted/Installation'));
const FirstApi = lazy(() => import('./pages/GettingStarted/FirstApi'));
const ProjectStructure = lazy(() => import('./pages/GettingStarted/ProjectStructure'));
const ArchitectureOverview = lazy(() => import('./pages/Architecture/Overview'));
const Routing = lazy(() => import('./pages/Architecture/Routing'));
const Controllers = lazy(() => import('./pages/Architecture/Controllers'));
const ModelsOrm = lazy(() => import('./pages/Architecture/ModelsOrm'));
const Migrations = lazy(() => import('./pages/Architecture/Migrations'));
const Validation = lazy(() => import('./pages/Architecture/Validation'));
const DatabaseDrivers = lazy(() => import('./pages/Database/Drivers'));
const ConfigurationEnv = lazy(() => import('./pages/Configuration/Env'));
const ConfigurationCaching = lazy(() => import('./pages/Configuration/Caching'));
const DIContainer = lazy(() => import('./pages/DI/Container'));
const CLIOverview = lazy(() => import('./pages/CLI/Overview'));
const I18nOverview = lazy(() => import('./pages/I18n/Overview'));
const SecurityOverview = lazy(() => import('./pages/Security/Overview'));
const GuidesCrudApi = lazy(() => import('./pages/Guides/CrudApi'));
const ReferenceOpenApi = lazy(() => import('./pages/Reference/OpenApi'));
const ReferenceHttpResponses = lazy(() => import('./pages/Reference/HttpResponses'));
const ReferenceCacheApi = lazy(() => import('./pages/Reference/CacheApi'));
const TroubleshootingCommonErrors = lazy(() => import('./pages/Troubleshooting/CommonErrors'));
const TroubleshootingFaq = lazy(() => import('./pages/Troubleshooting/Faq'));
const Roadmap = lazy(() => import('./pages/Roadmap'));

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
      
      // Architecture
      { path: 'architecture/overview', element: <ArchitectureOverview /> },
      { path: 'architecture/routing', element: <Routing /> },
      { path: 'architecture/controllers', element: <Controllers /> },
      { path: 'architecture/models-orm', element: <ModelsOrm /> },
      { path: 'architecture/migrations', element: <Migrations /> },
      { path: 'architecture/validation', element: <Validation /> },
      
      // Database
      { path: 'database/drivers', element: <DatabaseDrivers /> },
      
      // Configuration
      { path: 'configuration/env', element: <ConfigurationEnv /> },
      { path: 'configuration/caching', element: <ConfigurationCaching /> },
      
      // Dependency Injection
      { path: 'di/container', element: <DIContainer /> },
      
      // CLI
      { path: 'cli/overview', element: <CLIOverview /> },
      
      // I18n
      { path: 'i18n/overview', element: <I18nOverview /> },
      
      // Security
      { path: 'security/overview', element: <SecurityOverview /> },
      
      // Guides
      { path: 'guides/crud-api', element: <GuidesCrudApi /> },
      
      // Reference
      { path: 'reference/openapi', element: <ReferenceOpenApi /> },
      { path: 'reference/http-responses', element: <ReferenceHttpResponses /> },
      { path: 'reference/cache-api', element: <ReferenceCacheApi /> },
      
      // Troubleshooting
      { path: 'troubleshooting/common-errors', element: <TroubleshootingCommonErrors /> },
      { path: 'troubleshooting/faq', element: <TroubleshootingFaq /> },
      
      // Roadmap
      { path: 'roadmap', element: <Roadmap /> },
    ],
  },
]);

export default function AppRouter() {
  return <RouterProvider router={router} />;
}
