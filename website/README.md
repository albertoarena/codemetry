# Codemetry Documentation Website

This is the documentation website for [Codemetry](https://github.com/albertoarena/codemetry), built with [Astro](https://astro.build) and [Starlight](https://starlight.astro.build).

## Development

### Prerequisites

- Node.js 18+
- npm

### Setup

```bash
cd website
npm install
```

### Local Development

```bash
npm run dev
```

The site will be available at `http://localhost:4321/codemetry/`.

### Build

```bash
npm run build
```

Output is generated in `dist/`.

### Preview Build

```bash
npm run preview
```

## Structure

```
website/
├── src/
│   ├── assets/           # Logo images
│   ├── content/
│   │   └── docs/         # Documentation pages (MDX)
│   │       ├── index.mdx
│   │       ├── getting-started/
│   │       ├── concepts/
│   │       ├── configuration/
│   │       ├── ai-engines/
│   │       ├── extending/
│   │       ├── privacy/
│   │       └── roadmap/
│   └── styles/
│       └── custom.css    # Custom styling
├── astro.config.mjs      # Astro + Starlight configuration
└── package.json
```

## Deployment

The site is automatically deployed to GitHub Pages when changes are pushed to the `master` branch.

### Manual Deployment

The GitHub Actions workflow at `.github/workflows/deploy-docs.yml` handles deployment. To trigger manually:

1. Go to Actions tab in GitHub
2. Select "Deploy Documentation" workflow
3. Click "Run workflow"

## Writing Documentation

### Page Format

All documentation pages use MDX format:

```mdx
---
title: Page Title
description: Brief description for SEO
---

import { Aside } from '@astrojs/starlight/components';

Your content here...

<Aside type="tip">
  Helpful tip for readers.
</Aside>
```

### Available Components

- `Aside` - Callout boxes (tip, note, caution, danger)
- `Card` / `CardGrid` - Feature cards
- `Tabs` / `TabItem` - Tabbed content
- `Steps` - Numbered step lists

See [Starlight Components](https://starlight.astro.build/guides/components/) for full documentation.

### Adding New Pages

1. Create `.mdx` file in appropriate directory under `src/content/docs/`
2. Add frontmatter with `title` and `description`
3. Add to sidebar in `astro.config.mjs` if needed

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make changes to documentation
4. Test locally with `npm run dev`
5. Submit a pull request
