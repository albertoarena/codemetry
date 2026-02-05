# Split Repositories Setup

Codemetry uses a monorepo structure for development, but publishes separate packages to Packagist. This document explains how to configure the automated repository splitting.

## Overview

```
albertoarena/codemetry           (monorepo - development)
    ├── packages/core            → albertoarena/codemetry-core
    └── packages/laravel         → albertoarena/codemetry-laravel
```

When you push a version tag (e.g., `v1.0.0`), the GitHub Actions workflow automatically:
1. Splits each package into its own branch
2. Pushes to the corresponding split repository
3. Creates matching version tags

## Prerequisites

- GitHub account with access to create repositories
- The monorepo (`albertoarena/codemetry`) already set up

## Setup Instructions

### 1. Create Split Repositories

Create two **empty** repositories on GitHub (no README, license, or .gitignore):

- `albertoarena/codemetry-core`
- `albertoarena/codemetry-laravel`

> **Important:** The repositories must be completely empty for the first push to work.

### 2. Create Personal Access Token

1. Go to [GitHub Settings → Developer settings → Personal access tokens → Fine-grained tokens](https://github.com/settings/tokens?type=beta)
2. Click **Generate new token**
3. Configure the token:
   - **Token name:** `monorepo-split`
   - **Expiration:** 90 days or custom (you'll need to rotate it)
   - **Repository access:** Select repositories
     - `albertoarena/codemetry`
     - `albertoarena/codemetry-core`
     - `albertoarena/codemetry-laravel`
   - **Permissions:**
     - Contents: **Read and write**
4. Click **Generate token**
5. **Copy the token** (you won't see it again)

### 3. Add Secret to Monorepo

1. Go to `albertoarena/codemetry` → **Settings → Secrets and variables → Actions**
2. Click **New repository secret**
3. Fill in:
   - **Name:** `SPLIT_TOKEN`
   - **Secret:** paste your token
4. Click **Add secret**

### 4. Verify Workflow

The split workflow is already configured in `.github/workflows/split.yml`. It triggers on:
- Push of version tags (`v*`)
- Manual dispatch (Actions tab → Split Monorepo → Run workflow)

## Creating a Release

### Tag and Push

```bash
# Ensure you're on master with latest changes
git checkout master
git pull

# Create annotated tag
git tag -a v1.0.0 -m "Release v1.0.0"

# Push tag to trigger split workflow
git push origin v1.0.0
```

### Verify Split

1. Go to **Actions** tab in the monorepo
2. Check that "Split Monorepo" workflow completed successfully
3. Verify the split repositories have:
   - The code from their respective `packages/` directory
   - The matching version tag

## Register on Packagist

After the first successful split:

1. Go to [packagist.org/packages/submit](https://packagist.org/packages/submit)
2. Log in with your GitHub account
3. Submit each repository URL:
   - `https://github.com/albertoarena/codemetry-core`
   - `https://github.com/albertoarena/codemetry-laravel`

### Enable Auto-Update

For automatic Packagist updates when you push new tags:

1. On each Packagist package page, click **Settings**
2. Copy the **API Token**
3. In each split repository on GitHub:
   - Go to **Settings → Webhooks → Add webhook**
   - Payload URL: `https://packagist.org/api/github?username=YOUR_PACKAGIST_USERNAME`
   - Content type: `application/json`
   - Secret: paste the Packagist API token
   - Events: **Just the push event**

## Troubleshooting

### "Repository is empty" error

The split repositories must exist before running the workflow. Create them on GitHub first.

### "Authentication failed" error

- Verify `SPLIT_TOKEN` secret is set correctly
- Check token hasn't expired
- Ensure token has `contents: write` permission for all three repos

### Tags not appearing in split repos

Check the workflow logs in Actions tab. The tag push step runs only when triggered by a tag push, not manual dispatch.

### Workflow doesn't trigger

Ensure:
- Tag follows `v*` pattern (e.g., `v1.0.0`, `v2.1.0-beta`)
- You pushed the tag: `git push origin v1.0.0`

## Token Rotation

When your token expires:

1. Generate a new token following step 2 above
2. Update the `SPLIT_TOKEN` secret in repository settings
3. No workflow changes needed

## Manual Split (Emergency)

If the workflow fails, you can split manually:

```bash
# Split core
git subtree split -P packages/core -b core-split
git push --force https://TOKEN@github.com/albertoarena/codemetry-core.git core-split:main

# Split laravel
git subtree split -P packages/laravel -b laravel-split
git push --force https://TOKEN@github.com/albertoarena/codemetry-laravel.git laravel-split:main
```

Replace `TOKEN` with your personal access token.
