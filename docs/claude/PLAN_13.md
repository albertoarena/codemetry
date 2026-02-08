# PLAN_13: Sync codemetry/core and codemetry/laravel Versions

## Problem

When releasing a new version (e.g., v1.2.3):
- `codemetry/laravel` v1.2.3 requires `"codemetry/core": "^1.0"` (or `^1.2`)
- User runs `composer update codemetry/laravel`
- Composer installs laravel v1.2.3 but keeps core at v1.2.0 (satisfies constraint)
- Packages are out of sync

## Solution

**Inject exact version into `composer.json` during the split workflow.**

Before splitting the laravel package, update `packages/laravel/composer.json` to require the exact tag version being released. This modification happens only in the workflow (not committed to monorepo).

## File Changes

### `packages/laravel/composer.json`
Keep development-friendly constraint:
```json
"codemetry/core": "^1.0"
```

### `.github/workflows/split.yml`
Add a step before splitting laravel to inject the exact version:

```yaml
- name: Inject exact core version for laravel package
  if: startsWith(github.ref, 'refs/tags/')
  run: |
    TAG=${GITHUB_REF#refs/tags/}
    # Update composer.json to require exact version
    sed -i 's/"codemetry\/core": "[^"]*"/"codemetry\/core": "'$TAG'"/' packages/laravel/composer.json
```

This step runs BEFORE the subtree split, so the split laravel package will have the exact version requirement.

## Workflow Order

1. Checkout code
2. Configure Git
3. **NEW: Inject exact version into laravel/composer.json** (before split)
4. Split and push core (unchanged)
5. Split and push laravel (now has exact version)
6. Push tags
7. Create releases

## Result

When v1.2.3 is tagged:
- `codemetry/laravel` v1.2.3 will require `"codemetry/core": "v1.2.3"`
- `composer update codemetry/laravel` forces both packages to v1.2.3

## Acceptance Checklist

- [x] Split workflow updated with version injection step
- [x] Step runs before laravel split
- [x] Step only runs on tag pushes
- [x] Test with new tag to verify both packages sync

## Verification

1. Created tag v1.2.3
2. Verified split repo `codemetry-laravel` composer.json shows: `"codemetry/core": "v1.2.3"`
3. User can now run `composer update codemetry/laravel codemetry/core`
4. Both packages will sync to same version

## Status

**COMPLETED** - Verified with v1.2.3 release
