Documentation Site Plan (Vite + React + TS + React Router + MUI, sx-only)

High-level goals
	•	Fast, single-page docs with first-class navigation.
	•	All content authored as React/TSX pages; optional MDX later.
	•	GitHub Pages-ready via vite.config.ts base setting.
	•	Strict MUI theming with dark/light mode and a compact, readable typography scale.

App architecture

docs/
  index.html
  vite.config.ts
  tsconfig.json
  src/
    main.tsx
    App.tsx
    router.tsx
    theme/
      index.ts
      palette.ts
      typography.ts
      components.ts
    layout/
      RootLayout.tsx
      AppHeader.tsx
      AppDrawer.tsx
      SidebarTree.tsx
      Search.tsx
    pages/
      Home.tsx
      GettingStarted/
        Installation.tsx
        FirstApi.tsx
        ProjectStructure.tsx
      Architecture/
        Overview.tsx
        Routing.tsx
        Controllers.tsx
        ModelsOrm.tsx
        Migrations.tsx
        Validation.tsx
      Database/
        Drivers.tsx
        Seeding.tsx
        Transactions.tsx
      Configuration/
        Env.tsx
        Caching.tsx
        RateLimiting.tsx
        Cors.tsx
        Logging.tsx
      DI/
        Container.tsx
        ServiceProviders.tsx
        TestingWithDI.tsx
      CLI/
        Overview.tsx
        Development.tsx
        Database.tsx
        DocsGeneration.tsx
        Caching.tsx
      I18n/
        Overview.tsx
        ScanFill.tsx
        Providers.tsx
      Security/
        Overview.tsx
        Auth.tsx
        InputValidation.tsx
        HardeningChecklist.tsx
      Performance/
        Benchmarks.tsx
        CachingStrategies.tsx
        ProductionTuning.tsx
      Guides/
        CrudApi.tsx
        FileUploads.tsx
        PaginationSorting.tsx
        Webhooks.tsx
        VersionedApis.tsx
      Reference/
        OpenApi.tsx
        HttpResponses.tsx
        CacheApi.tsx
      Troubleshooting/
        CommonErrors.tsx
        Faq.tsx
      Contributing/
        Guide.tsx
        CodeOfConduct.tsx
        ReleaseProcess.tsx
      Roadmap.tsx
    data/
      nav.ts
      versions.ts

Routing
	•	React Router createBrowserRouter with nested routes under /.
	•	Left drawer items driven by data/nav.ts tree; URL path mirrors sections.
	•	Search uses client-side index built from nav.ts and page titles/headings.

Layout
	•	RootLayout: persistent AppBar + permanent Drawer on lg+, swipeable on sm/md.
	•	Content container with maxWidth lg, readable line-length, sx={{ px: 3, py: 2 }}.
	•	Breadcrumbs under header. Footer with version selector and GitHub links.

Theming
	•	theme/index.ts exports createTheme with:
	•	Palette: neutral grays, accent primary = blue, secondary = purple.
	•	Typography: fontSize: 14, tighter headings, code font set on <code>.
	•	Components overrides:
	•	MuiLink underline hover.
	•	MuiListItemButton compact density.
	•	MuiTable monospace cells for env/CLI tables.
	•	Color mode toggle in header; store preference in localStorage.

Navigation system
	•	Sidebar shows sections and pages as a tree from data/nav.ts.
	•	Active item highlighting based on current route.
	•	Collapsible groups with remember-state.
	•	Top search field with fuzzy match (simple client-side: fuse.js if desired, or own trigram); result list shows page title and path.

Example data/nav.ts shape:

export type NavNode = { title: string; path?: string; children?: NavNode[] }
export const NAV: NavNode[] = [
  { title: 'Getting Started', children: [
    { title: 'Installation', path: '/getting-started/installation' },
    { title: 'First API', path: '/getting-started/first-api' },
    { title: 'Project Structure', path: '/getting-started/project-structure' },
  ]},
  { title: 'Architecture', children: [
    { title: 'Overview', path: '/architecture/overview' },
    { title: 'Routing', path: '/architecture/routing' },
    { title: 'Controllers', path: '/architecture/controllers' },
    { title: 'Models & ORM', path: '/architecture/models-orm' },
    { title: 'Migrations', path: '/architecture/migrations' },
    { title: 'Validation', path: '/architecture/validation' },
  ]},
]

Content authoring
	•	MUI + sx only, no Tailwind.
	•	Each page exports a functional component with clear sections:
	•	Short “Why this matters”.
	•	One minimal example.
	•	Deep dive subsections.
	•	Code blocks: <CodeBlock language="bash" code="..." /> and <CodeBlock language="php" code="..." />.
	•	Reusable callouts: <Callout type="tip" title="...">...</Callout> with MUI Alert.

Shared components
	•	CodeBlock: Prism highlighting, copy-to-clipboard IconButton on top-right, sx for spacing.
	•	Callout: wraps Alert with severity mapping.
	•	Admonition: optional variant with title bar.
	•	EnvTable: MUI Table specialized for .env keys with columns Key, Default, Description.
	•	ApiMethod: badge-like chips for GET/POST/PUT/DELETE.

Page mapping from earlier plan
	•	Move all deep technicals into these pages under the same names as listed in your previous restructuring. Each page will mirror the content you already drafted for the markdown-first plan, but as TSX with MUI components and code blocks.
	•	Keep README minimal and link to / of this docs app via GitHub Pages (set vite base).

Versioning strategy
	•	Start with single “Latest”.
	•	When you tag v0.5+, introduce a simple versions registry in data/versions.ts:
	•	Build-time flag VITE_DOCS_VERSION.
	•	Host previous builds under /v0.4/, /v0.5/ by deploying artifacts to gh-pages with subfolders. The header version switcher routes to those prefixes.

GitHub Pages deployment
	•	In vite.config.ts set base: '/<repo>/'.
	•	Add GitHub Action deploy-docs.yml:
	•	on: push to main in /docs or /src paths.
	•	Build with npm ci && npm run build.
	•	Upload dist to gh-pages branch.
	•	For versioned deploys, job copies dist to gh-pages/<version>/ and updates index.html at root to latest.

Scripts
	•	dev: vite
	•	build: vite build
	•	preview: vite preview

Header actions
	•	Search input
	•	Dark/light toggle
	•	Version selector
	•	GitHub icon button

Performance
	•	Keep bundle lean. Avoid heavy markdown engines until needed.
	•	Split routes by section with lazy() to reduce initial load.

Initial pages to implement first
	•	Home
	•	Getting Started: Installation, First API, Project Structure
	•	Architecture: Overview
	•	Configuration: Env
	•	Guides: CRUD API
	•	Reference: OpenAPI
	•	Troubleshooting: Common Errors

These give a complete onboarding path and a credible public surface immediately.
