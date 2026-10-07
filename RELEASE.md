# Release process — dynamic/silverstripe-site-tools

This project follows the **Dynamic Release Framework**. The process itself —
lifecycle, labels, milestones, runbook — lives there and is not restated here.
This file holds only this repository's parameters and overrides. If a section
would restate a framework default, it says `framework default` instead.

| | |
| --- | --- |
| **Framework** | [dynamic/release-framework](https://github.com/dynamic/release-framework) `1.x` |
| **Tier** | active |
| **Platform** | GitHub |
| **Deploy** | none |

## Cadence

| Parameter | Value |
| --- | --- |
| Profile | on-demand |
| Freeze | set per Essentials platform release: 2026-11-10 (Essentials 3.4), 2027-01-12 (Essentials 4.0). Every platform member's milestone carries the same freeze date and a `Platform:` line |
| Review window | freeze + 7 days |
| Production | freeze + 9 days |

## Branches

| Parameter | Value |
| --- | --- |
| Release line | `6` |
| Work branches | framework default |

`5` is the previous major (maintenance).

## Deploy

None. Libraries and modules are distributed by tag; consumers install the tag with Composer.
A release is the tag and the GitHub Release (framework `docs/deploy/none.md`).

| Check | Command |
| --- | --- |
| Tag is on the remote | `git ls-remote --tags origin <tag>` |
| Release is published, not a draft | `gh release view <tag> --json tagName,isDraft` |

A bad release is corrected forward with a patch release; never delete or move a published tag.

## Review

| Parameter | Value |
| --- | --- |
| Tool | Claude Code `/review-pr` (pr-review-toolkit) after the PR is opened; the author runs a local review before pushing |
| Approvals | framework default |

Label deviation: these repositories keep the autobuild priority labels `priority/critical`, `priority/high`,
`priority/medium`, `priority/low` and `effort/*` instead of the framework's `priority/1` to `priority/3`
(proposed upstream in dynamic/release-framework#4). `check-adoption.sh` will report the numeric labels as missing.

## Post-deploy

None.

## Hazards

- Classes marked `@deprecated` are removed only in the next major (7.0). Use `Deprecation::notice`; never remove anything in a minor.
- This repository is public: no client names or live domains in issues, PRs or commits.
- GitHub Actions is disabled on dynamic/* repositories. Run the suite and `phpcs` before every push.
