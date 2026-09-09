# Changelog

All notable changes to this package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

These candidate notes target the prepared `v1.0.0` release line. No stable tag
or release date is claimed until the owner approves publication.

### Added

- Initial standalone extraction of the reusable Category and Category
  Translation domain/application contracts.
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

[Unreleased]: https://github.com/Maatify/category/compare/main...HEAD
