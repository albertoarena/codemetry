# CODEMETRY_WEBSITE.md — AI Agent Instructions (Static Docs Website with Astro)

## 0) Goal
Build a **static documentation website** for **Codemetry** that explains:
- What Codemetry is (metrics-based “mood proxy”, not emotion detection)
- How it works (signals → baseline → scoring → optional AI explanation)
- How to install and use it (Laravel first)
- Configuration (providers, baseline, AI engines)
- Extending it (custom providers, future adapters)
- Privacy & data handling (metrics-only; AI opt-in)
- Roadmap (dashboard V2, WP-CLI, Symfony adapters)

The site should look and feel similar to popular **Laravel package docs**:
- Clean left sidebar navigation
- Top header with project name + GitHub link
- Code blocks and callouts
- Versioned docs-ready structure (even if V1 uses only “latest”)

Framework: **Astro** (static site). Prefer a docs theme approach:
- Either Astro Starlight (recommended) OR a minimal custom theme.

Deliverable: an Astro project in a `website/` folder (or separate repo) that can be deployed to GitHub Pages / Netlify.

---

## 1) Non-goals (V1 website)
- No backend
- No user auth
- No analytics by default (optional later)
- No search (optional later; Starlight supports it if desired)

---

## 2) Tech choices (deterministic)
Choose **one** of the following and stick with it:

### Option A (recommended): Astro + Starlight
- Astro: latest stable
- `@astrojs/starlight` for docs layout, sidebar, theme, MD/MDX support

### Option B: Astro + Tailwind + custom docs layout
Only if Starlight is not acceptable. Must replicate:
- responsive sidebar
- nested nav
- “On this page” headings
- syntax highlighting

Unless instructed otherwise, implement **Option A**.

---

## 3) Repo structure
Place website in:

```
website/
  astro.config.mjs
  package.json
  public/
  src/
    content/
      docs/
        index.mdx
        getting-started/
        concepts/
        configuration/
        ai-engines/
        extending/
        privacy/
        roadmap/
    assets/
    components/   (only if needed)
    styles/
```

If in a monorepo with PHP packages, keep it as a top-level folder:
```
codemetry/
  packages/
  website/
```

---

## 4) Information architecture (sidebar)
Use these sections (keep names stable):

1) **Introduction**
   - What is Codemetry?
   - Quickstart (Laravel)

2) **Getting Started**
   - Installation
   - Basic usage
   - CLI options
   - Example outputs (table + JSON)

3) **How it Works**
   - Signals (what we measure)
   - Baseline & normalization
   - Heuristic scoring (bad/medium/good)
   - Confidence, reasons, confounders

4) **Configuration**
   - config/codemetry.php
   - keywords
   - baseline_days, follow_up_horizon_days
   - provider toggles
   - caching behavior

5) **AI Engines (Optional)**
   - Why AI is optional
   - Supported engines (OpenAI, Claude, DeepSeek, optional Google)
   - What data is sent (metrics-only)
   - Configuration examples
   - Failure behavior (graceful fallback)

6) **Extending Codemetry**
   - Writing a SignalProvider
   - Registering providers
   - Adapter concept (Laravel now, WP-CLI/Symfony later)

7) **Privacy & Safety**
   - No code upload by default
   - Metrics-only AI payload
   - Local-first philosophy

8) **Roadmap**
   - V2 dashboard (Telescope-like)
   - Additional adapters
   - Hotspot analysis

---

## 5) Required pages (content outlines)
Create these pages (MDX preferred). Each page must be complete, not placeholder.

### 5.1 `index.mdx` — Home
Include:
- Codemetry tagline: “Git-powered code metrics → daily quality/strain proxy”
- Primary CTA buttons: “Get Started”, “How it Works”, “GitHub”
- 3–5 highlights:
  - metrics-based signals
  - follow-up fixes proxy
  - baseline comparison
  - optional AI explanations (metrics-only)
  - Laravel-first, extensible architecture
- A short disclaimer: not emotion detection

### 5.2 Getting Started
- Installation (Composer + service provider auto-discovery)
- Publish config
- Run examples:
  - `php artisan codemetry:analyze --days=7`
  - `php artisan codemetry:analyze --days=7 --format=json`
- Explain the output fields

### 5.3 How it Works
Use diagrams in text form (ASCII or Mermaid if supported by Starlight):
- Pipeline: Git → Snapshot → Signals → Baseline → Normalization → Score → Report
Explain:
- churn, scatter, follow-up fix density
- why baseline matters (repo-specific norms)
- confidence + confounders

### 5.4 Configuration
Document config keys with examples:
- baseline_days
- follow_up_horizon_days
- keywords (fix/revert/wip)
- providers enabled/disabled
- cache location and invalidation rules

### 5.5 AI Engines
Emphasize:
- opt-in
- aggregated metrics only
- score adjustment bounds (if any)
Provide per-engine config examples (env vars + config array).

### 5.6 Extending
Walkthrough:
- implement `SignalProvider`
- add to provider registry
- best practices (fast, deterministic, never fail the pipeline)

### 5.7 Privacy
Clear bullets:
- What Codemetry reads (git metadata, stats)
- What it stores (optional, future)
- What it sends when AI enabled (metrics-only)
- How to disable AI

### 5.8 Roadmap
Short and concrete:
- local dashboard viewer
- WP-CLI adapter
- Symfony adapter
- more advanced fix correlation

---

## 6) Styling requirements (Laravel-esque)
- Use a clean neutral palette; support dark mode
- Sidebar with section headers
- Code blocks with syntax highlighting
- Callouts for “Tip”, “Warning”, “Note”
- Use a readable system font stack
- Top nav includes:
  - Codemetry logo/text
  - GitHub link
  - Version indicator (“v1.x” or “latest”)

If using Starlight:
- Configure `siteTitle`, `sidebar`, `social` links
- Add a minimal `customCss` for slightly “Laravel-like” spacing and typography

---

## 7) Content accuracy constraints
The docs must match the Codemetry design decisions:
- “Mood” is a proxy, not psychology.
- Default mode is heuristic scoring; AI optional.
- AI payload contains metrics only (no raw code).
- Laravel-first; adapters later.

If implementation details are not finalized, phrase as:
- “Codemetry currently…” or “In V1…”
- “Planned for V2…”

Avoid promising features that are not in V1.

---

## 8) Deployment (choose one, implement)
### Option A: GitHub Pages (recommended)
- Use GitHub Actions workflow:
  - install Node
  - `npm ci`
  - `npm run build`
  - deploy `dist/` to Pages

### Option B: Netlify
- Provide `netlify.toml` with build command and publish dir

Unless instructed otherwise, implement **Option A** with a `.github/workflows/deploy.yml`.

---

## 9) Implementation steps (short, step-by-step)
Each step must be completed and reviewed before moving to the next.

### STEP 01 — Scaffold Astro site
- Create `website/` with Astro + Starlight
- Confirm `npm run dev` works

### STEP 02 — Configure theme + navigation
- Set site title “Codemetry”
- Add sidebar structure from section 4
- Add GitHub link in header

### STEP 03 — Write core docs pages (complete content)
- Add required pages (index + sections)
- Ensure internal links work
- Add code snippets for Laravel usage

### STEP 04 — Add styling polish
- Dark mode verified
- Typography and spacing consistent
- Callouts look good

### STEP 05 — Add deployment
- GitHub Pages workflow (or Netlify)
- Document deploy steps in `website/README.md`

### STEP 06 — Final review
- Run `npm run build`
- Verify no broken links
- Ensure disclaimers and privacy statements are correct

---

## 10) Acceptance criteria
- Site builds successfully with `npm run build`
- Sidebar structure matches section 4
- All required pages exist and contain real documentation (no placeholders)
- Visual style is docs-friendly and Laravel-esque
- Deployment workflow present and correct
- No claims of “emotion detection”; always “metrics-based proxy”

---

## 11) Files to create (minimum)
- `website/package.json`
- `website/astro.config.mjs`
- `website/src/content/docs/index.mdx`
- `website/src/content/docs/getting-started/installation.mdx`
- `website/src/content/docs/getting-started/usage.mdx`
- `website/src/content/docs/concepts/signals.mdx`
- `website/src/content/docs/concepts/baseline-and-normalization.mdx`
- `website/src/content/docs/concepts/scoring.mdx`
- `website/src/content/docs/configuration/index.mdx`
- `website/src/content/docs/ai-engines/index.mdx`
- `website/src/content/docs/extending/index.mdx`
- `website/src/content/docs/privacy/index.mdx`
- `website/src/content/docs/roadmap/index.mdx`
- `website/README.md`
- `.github/workflows/deploy.yml` (if GitHub Pages)

---

## 12) Optional extras (only if time permits)
- Add “Example JSON output” page with a realistic sample
- Add “FAQ” page
- Add “Changelog” page (manual for now)
