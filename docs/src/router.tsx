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
const DatabaseSeeding = lazy(() => import('./pages/Database/Seeding'));
const DatabaseTransactions = lazy(() => import('./pages/Database/Transactions'));
const ConfigurationEnv = lazy(() => import('./pages/Configuration/Env'));
const ConfigurationCaching = lazy(() => import('./pages/Configuration/Caching'));
const ConfigurationRateLimiting = lazy(() => import('./pages/Configuration/RateLimiting'));
const ConfigurationCors = lazy(() => import('./pages/Configuration/Cors'));
const ConfigurationLogging = lazy(() => import('./pages/Configuration/Logging'));
const DIContainer = lazy(() => import('./pages/DI/Container'));
const DIServiceProviders = lazy(() => import('./pages/DI/ServiceProviders'));
const DITestingWithDI = lazy(() => import('./pages/DI/TestingWithDI'));
const CLIOverview = lazy(() => import('./pages/CLI/Overview'));
const CLIDevelopment = lazy(() => import('./pages/CLI/Development'));
const CLIDatabase = lazy(() => import('./pages/CLI/Database'));
const CLIDocsGeneration = lazy(() => import('./pages/CLI/DocsGeneration'));
const CLICaching = lazy(() => import('./pages/CLI/Caching'));
const I18nOverview = lazy(() => import('./pages/I18n/Overview'));
const I18nScanFill = lazy(() => import('./pages/I18n/ScanFill'));
const I18nProviders = lazy(() => import('./pages/I18n/Providers'));
const SecurityOverview = lazy(() => import('./pages/Security/Overview'));
const SecurityAuth = lazy(() => import('./pages/Security/Auth'));
const SecurityInputValidation = lazy(() => import('./pages/Security/InputValidation'));
const SecurityHardeningChecklist = lazy(() => import('./pages/Security/HardeningChecklist'));
const PerformanceBenchmarks = lazy(() => import('./pages/Performance/Benchmarks'));
const PerformanceCachingStrategies = lazy(() => import('./pages/Performance/CachingStrategies'));
const PerformanceProductionTuning = lazy(() => import('./pages/Performance/ProductionTuning'));
const GuidesCrudApi = lazy(() => import('./pages/Guides/CrudApi'));
const GuidesFileUploads = lazy(() => import('./pages/Guides/FileUploads'));
const GuidesPaginationSorting = lazy(() => import('./pages/Guides/PaginationSorting'));
const GuidesWebhooks = lazy(() => import('./pages/Guides/Webhooks'));
const GuidesVersionedApis = lazy(() => import('./pages/Guides/VersionedApis'));
const ReferenceOpenApi = lazy(() => import('./pages/Reference/OpenApi'));
const ReferenceHttpResponses = lazy(() => import('./pages/Reference/HttpResponses'));
const ReferenceCacheApi = lazy(() => import('./pages/Reference/CacheApi'));
const TroubleshootingCommonErrors = lazy(() => import('./pages/Troubleshooting/CommonErrors'));
const TroubleshootingFaq = lazy(() => import('./pages/Troubleshooting/Faq'));
const ContributingGuide = lazy(() => import('./pages/Contributing/Guide'));
const ContributingCodeOfConduct = lazy(() => import('./pages/Contributing/CodeOfConduct'));
const ContributingReleaseProcess = lazy(() => import('./pages/Contributing/ReleaseProcess'));
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
      { path: 'database/seeding', element: <DatabaseSeeding /> },
      { path: 'database/transactions', element: <DatabaseTransactions /> },
      
      // Configuration
      { path: 'configuration/env', element: <ConfigurationEnv /> },
      { path: 'configuration/caching', element: <ConfigurationCaching /> },
      { path: 'configuration/rate-limiting', element: <ConfigurationRateLimiting /> },
      { path: 'configuration/cors', element: <ConfigurationCors /> },
      { path: 'configuration/logging', element: <ConfigurationLogging /> },
      
      // Dependency Injection
      { path: 'di/container', element: <DIContainer /> },
      { path: 'di/service-providers', element: <DIServiceProviders /> },
      { path: 'di/testing-with-di', element: <DITestingWithDI /> },
      
      // CLI
      { path: 'cli/overview', element: <CLIOverview /> },
      { path: 'cli/development', element: <CLIDevelopment /> },
      { path: 'cli/database', element: <CLIDatabase /> },
      { path: 'cli/docs-generation', element: <CLIDocsGeneration /> },
      { path: 'cli/caching', element: <CLICaching /> },
      
      // I18n
      { path: 'i18n/overview', element: <I18nOverview /> },
      { path: 'i18n/scan-fill', element: <I18nScanFill /> },
      { path: 'i18n/providers', element: <I18nProviders /> },
      
      // Security
      { path: 'security/overview', element: <SecurityOverview /> },
      { path: 'security/auth', element: <SecurityAuth /> },
      { path: 'security/input-validation', element: <SecurityInputValidation /> },
      { path: 'security/hardening-checklist', element: <SecurityHardeningChecklist /> },
      
      // Performance
      { path: 'performance/benchmarks', element: <PerformanceBenchmarks /> },
      { path: 'performance/caching-strategies', element: <PerformanceCachingStrategies /> },
      { path: 'performance/production-tuning', element: <PerformanceProductionTuning /> },
      
      // Guides
      { path: 'guides/crud-api', element: <GuidesCrudApi /> },
      { path: 'guides/file-uploads', element: <GuidesFileUploads /> },
      { path: 'guides/pagination-sorting', element: <GuidesPaginationSorting /> },
      { path: 'guides/webhooks', element: <GuidesWebhooks /> },
      { path: 'guides/versioned-apis', element: <GuidesVersionedApis /> },
      
      // Reference
      { path: 'reference/openapi', element: <ReferenceOpenApi /> },
      { path: 'reference/http-responses', element: <ReferenceHttpResponses /> },
      { path: 'reference/cache-api', element: <ReferenceCacheApi /> },
      
      // Troubleshooting
      { path: 'troubleshooting/common-errors', element: <TroubleshootingCommonErrors /> },
      { path: 'troubleshooting/faq', element: <TroubleshootingFaq /> },
      
      // Contributing
      { path: 'contributing/guide', element: <ContributingGuide /> },
      { path: 'contributing/code-of-conduct', element: <ContributingCodeOfConduct /> },
      { path: 'contributing/release-process', element: <ContributingReleaseProcess /> },
      
      // Roadmap
      { path: 'roadmap', element: <Roadmap /> },
    ],
  },
]);

export default function AppRouter() {
  return <RouterProvider router={router} />;
}
