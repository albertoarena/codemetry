# Plan: Branch Protection Ruleset for `master`

**Status: COMPLETED** (applied 2026-05-14)

## Goal

Strengthen the existing `master` branch protection for `albertoarena/codemetry` so that:
- No one can push directly to `master` (except the owner who can bypass)
- External contributors must fork and open PRs
- CI status checks must pass before merging
- Branch cannot be deleted or force-pushed (already enforced)

## Current State

A ruleset already exists (ID: `12505957`) with:
- Deletion protection
- Non-fast-forward (force-push) protection
- Admin bypass

**Missing:** pull request requirement and required status checks.

The CI workflow (`.github/workflows/tests.yml`) already runs Pest tests on PHP 8.2, 8.3, and 8.4 for pushes to `master` and all PRs. The matrix job names produced are: `test (8.2)`, `test (8.3)`, `test (8.4)`.

## Steps

### Step 1: Update the existing ruleset via GitHub API

Update ruleset `12505957` to add pull request and status check rules while keeping the existing deletion and force-push rules.

**Command:**

```bash
gh api repos/albertoarena/codemetry/rulesets/12505957 \
  --method PUT \
  --input - <<'EOF'
{
  "name": "master",
  "target": "branch",
  "enforcement": "active",
  "conditions": {
    "ref_name": {
      "include": ["~DEFAULT_BRANCH"],
      "exclude": []
    }
  },
  "bypass_actors": [
    {
      "actor_id": 5,
      "actor_type": "RepositoryRole",
      "bypass_mode": "always"
    }
  ],
  "rules": [
    {
      "type": "deletion"
    },
    {
      "type": "non_fast_forward"
    },
    {
      "type": "pull_request",
      "parameters": {
        "required_approving_review_count": 1,
        "dismiss_stale_reviews_on_push": true,
        "require_code_owner_review": false,
        "require_last_push_approval": false,
        "required_review_thread_resolution": false
      }
    },
    {
      "type": "required_status_checks",
      "parameters": {
        "strict_required_status_checks_policy": true,
        "required_status_checks": [
          { "context": "test (8.2)" },
          { "context": "test (8.3)" },
          { "context": "test (8.4)" }
        ]
      }
    }
  ]
}
EOF
```

**What each rule does:**

| Rule | Purpose |
|---|---|
| `deletion` | Prevents deleting the `master` branch (already active) |
| `non_fast_forward` | Prevents force-pushes to `master` (already active) |
| `pull_request` | Requires a PR with **1 approval** before merging; dismisses stale reviews on new pushes. Admin bypasses this, so owner PRs don't need approval |
| `required_status_checks` | All 3 CI matrix jobs must pass; `strict_required_status_checks_policy: true` means the PR branch must be up to date with `master` before merging |

**Status check context names:** These match the job name (`test`) combined with the matrix value — GitHub renders them as `test (8.2)`, `test (8.3)`, `test (8.4)`.

---

### Step 2: Verify

- Confirm the updated ruleset is visible in Settings > Rulesets
- Confirm all 4 rules are listed (deletion, non-fast-forward, pull request, status checks)

---

## Rollback

To revert to the previous state (deletion + force-push protection only):

```bash
gh api repos/albertoarena/codemetry/rulesets/12505957 \
  --method PUT \
  --input - <<'EOF'
{
  "name": "master",
  "target": "branch",
  "enforcement": "active",
  "conditions": {
    "ref_name": {
      "include": ["~DEFAULT_BRANCH"],
      "exclude": []
    }
  },
  "bypass_actors": [
    {
      "actor_id": 5,
      "actor_type": "RepositoryRole",
      "bypass_mode": "always"
    }
  ],
  "rules": [
    {
      "type": "deletion"
    },
    {
      "type": "non_fast_forward"
    }
  ]
}
EOF
```

## Notes

- No changes to the CI workflow are needed — it already covers all supported PHP versions
- The `deploy-docs.yml` and `split.yml` workflows are not included as required checks since they serve different purposes (docs deployment and monorepo subtree splitting)
- No CODEOWNERS file is included in this plan (can be added later if desired)
