# Maatify/category Standards Manifest

This file is the local resolver record for the repository's selective pinned
adoption. It records composition and provenance; the underlying standards and
profiles remain the source of truth for their own rules.

## Adoption metadata

- **Upstream repository:** `Maatify/php-engineering-standards`
- **Adoption commit:** `639bbdb7c70c1d6db9e8d5cfef93b23fb926afd3`
- **Adoption date:** `2026-09-12` (commit timestamp `2026-09-12T14:08:43+03:00`)
- **Floating upstream `main`:** not used
- **Adoption model:** Selective Pinned Adoption

## Pinned Adoption Control Set

These files are required to resolve and audit the active adoption:

- [`standards/STANDARDS_ADOPTION_STANDARD_AR.md`](standards/STANDARDS_ADOPTION_STANDARD_AR.md)
- [`standards/profiles/REPOSITORY_GOVERNANCE_PROFILE.md`](standards/profiles/REPOSITORY_GOVERNANCE_PROFILE.md)
- [`standards/profiles/BASE_MODULE_PROFILE.md`](standards/profiles/BASE_MODULE_PROFILE.md)
- [`standards/profiles/COMPOSER_PACKAGE_PROFILE.md`](standards/profiles/COMPOSER_PACKAGE_PROFILE.md)

All four files are copied from the adoption commit above. No unused profile
manifest is part of this control set.

## Active Profile Activations

| Profile | Profile version | Scope | Direct relationship |
|---|---:|---|---|
| [`repository-governance`](standards/profiles/REPOSITORY_GOVERNANCE_PROFILE.md) | `1.0.0` | `/` | no inheritance |
| [`base-module`](standards/profiles/BASE_MODULE_PROFILE.md) | `1.0.0` | `/` | extends `composer-package` |

The inherited `composer-package` profile is pinned in the Control Set and is
resolved for the `base-module` activation. Slim and Project-Aware Slim profiles
are not active and are not copied.

## Pinned Applicable Standards Set

The resolved set is the union of the direct standards of the active profiles
and the inherited `composer-package` profile. Versions below use the exact
identity declared by each source file; no undeclared semantic version is
invented.

| Standard | Local path | Source version / identity | Resolved through |
|---|---|---|---|
| AI Collaboration Workflow | [`standards/ai/AI_COLLABORATION_WORKFLOW_AR.md`](standards/ai/AI_COLLABORATION_WORKFLOW_AR.md) | `5.3.0` | `repository-governance` |
| GitHub Phase Stack Workflow | [`standards/GITHUB_PHASE_STACK_WORKFLOW_AR.md`](standards/GITHUB_PHASE_STACK_WORKFLOW_AR.md) | `2.2.0` | `repository-governance` |
| Module Building Standard | [`standards/modules/MODULE_BUILDING_STANDARD.md`](standards/modules/MODULE_BUILDING_STANDARD.md) | `v1.3` | `base-module` |
| Package Building Standard | [`standards/packages/PACKAGE_BUILDING_STANDARD.md`](standards/packages/PACKAGE_BUILDING_STANDARD.md) | `v1.2` | inherited `composer-package` |
| Composer Package Standard | [`standards/packages/COMPOSER_PACKAGE_STANDARD.md`](standards/packages/COMPOSER_PACKAGE_STANDARD.md) | `v1.1` | inherited `composer-package` |
| CI Workflow Standard | [`standards/packages/CI_WORKFLOW_STANDARD.md`](standards/packages/CI_WORKFLOW_STANDARD.md) | not declared in source | inherited `composer-package` |
| Library Presentation Standard | [`standards/packages/LIBRARY_PRESENTATION_STANDARD.md`](standards/packages/LIBRARY_PRESENTATION_STANDARD.md) | not declared in source | inherited `composer-package` |
| Testing Standard | [`standards/testing/TESTING_STANDARD.md`](standards/testing/TESTING_STANDARD.md) | `v1.1` | inherited `composer-package` |

### Auditable profile resolution

```text
repository-governance (/)
└── AI Collaboration Workflow 5.3.0
└── GitHub Phase Stack Workflow 2.2.0

base-module (/)
├── Module Building Standard v1.3
└── composer-package (inherited)
    ├── Package Building Standard v1.2
    ├── Composer Package Standard v1.1
    ├── CI Workflow Standard (source version not declared)
    ├── Library Presentation Standard (source version not declared)
    └── Testing Standard v1.1
```

`STANDARDS_ADOPTION_STANDARD_AR.md` is part of the Control Set and is not an
engineering standard resolved by either active Profile.

## Additional Standards and exceptions

- **Explicit Additional Standards:** None.
- **Explicit Exceptions/Overrides:** None.

## Integrity and closure requirements

- Every retained pinned standard and profile is byte-for-byte equal to its
  upstream blob at the adoption commit.
- Relative links remain within the retained local adoption set.
- Unused profiles, unused module standards, `docs/audits/`, and `docs/decisions/`
  are not copied into this repository's adoption set.
- Normal engineering tasks resolve from this manifest and the local pinned
  files; they do not require an upstream network request.
