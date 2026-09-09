# Changelog

All notable changes to this package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

These unreleased notes describe the proposed `v1.0.0` release line. No stable
tag, release date, or owner-approved release metadata is claimed.

### Added

- Initial standalone extraction of the reusable Category and optional Category
  Content domain/application contracts.
- MySQL schema, PDO repositories, transaction adapter, and real-engine
  Integration tests for hierarchy, lifecycle, ordering, and visibility.
- Root Package Reference and standards-aligned Composer/CI configuration.

### Changed

- Reconciled the roadmap and package documentation with the selective pinned
  standards adoption and the current v1 runtime contract.
- Documented separate management and consumer visibility reads, bounded
  unpaginated lists, deterministic ordering, and deferred pagination/search.
- Prepared release-facing documentation for owner approval without creating a
  tag, release, or Packagist publication.
- Corrected the previous content model to unified Category Content,
  supporting one unlocalized (`language_code = NULL`) row and localized rows
  under a database-enforced logical identity.

[Unreleased]: https://github.com/Maatify/category/compare/main...HEAD
