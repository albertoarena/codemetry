import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

export default defineConfig({
  site: 'https://albertoarena.github.io',
  base: '/codemetry',
  integrations: [
    starlight({
      title: 'Codemetry',
      description: 'Git-powered code metrics for daily quality and strain analysis',
      logo: {
        light: './src/assets/logo-light.svg',
        dark: './src/assets/logo-dark.svg',
        replacesTitle: false,
      },
      social: {
        github: 'https://github.com/albertoarena/codemetry',
      },
      editLink: {
        baseUrl: 'https://github.com/albertoarena/codemetry/edit/master/website/',
      },
      customCss: [
        './src/styles/custom.css',
      ],
      sidebar: [
        {
          label: 'Introduction',
          items: [
            { label: 'What is Codemetry?', link: '/' },
            { label: 'Quickstart', link: '/getting-started/quickstart/' },
          ],
        },
        {
          label: 'Getting Started',
          items: [
            { label: 'Installation', link: '/getting-started/installation/' },
            { label: 'Basic Usage', link: '/getting-started/usage/' },
            { label: 'CLI Options', link: '/getting-started/cli-options/' },
            { label: 'Example Output', link: '/getting-started/example-output/' },
          ],
        },
        {
          label: 'How it Works',
          items: [
            { label: 'Signals', link: '/concepts/signals/' },
            { label: 'Baseline & Normalization', link: '/concepts/baseline-and-normalization/' },
            { label: 'Scoring', link: '/concepts/scoring/' },
            { label: 'Confidence & Confounders', link: '/concepts/confidence-and-confounders/' },
          ],
        },
        {
          label: 'Configuration',
          items: [
            { label: 'Overview', link: '/configuration/' },
            { label: 'Keywords', link: '/configuration/keywords/' },
            { label: 'Caching', link: '/configuration/caching/' },
          ],
        },
        {
          label: 'AI Engines',
          items: [
            { label: 'Overview', link: '/ai-engines/' },
            { label: 'OpenAI', link: '/ai-engines/openai/' },
            { label: 'Anthropic (Claude)', link: '/ai-engines/anthropic/' },
            { label: 'DeepSeek', link: '/ai-engines/deepseek/' },
            { label: 'Google (Gemini)', link: '/ai-engines/google/' },
          ],
        },
        {
          label: 'Extending',
          items: [
            { label: 'Custom Providers', link: '/extending/' },
            { label: 'Framework Adapters', link: '/extending/adapters/' },
          ],
        },
        {
          label: 'Privacy & Safety',
          link: '/privacy/',
        },
        {
          label: 'Roadmap',
          link: '/roadmap/',
        },
      ],
    }),
  ],
});
