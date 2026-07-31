# CHANGELOG

## [Unreleased]

### Added

- Added theme manager - Inachis no longer requires installation as a composer dependency for customisation. The default theme is now located in `templates/themes/default`, and the Theme scanner will look in `templates/themes/` for new themes.
- Added Role-based permissions with default configurations for editors/admins/etc.
- Added Review Threads. There is now the option to add inline comments to content for reviewers to peruse before publishing/scheduling content to be live
- Added page for managing Tags
- Added CSP reporting and policy manager,
- Added option to switch between list - grid - table view styles
- Added resources filter to identify duplicate resources and unused images
- Added auto-updater

### Changed

- Added expiry date to Page content types
- View States will now remember view style, filters, sorting options between sesssions for each context
- Re-organises Repository and Entity classes into more logical folders - this is a breaking change for backwards compatibility
- PHPStan now passes fully on codebase, and PHPUnit has complete-ish code coverage

## [1.1.0] 2026-06-02

### Added

- Added Dashboard with statistics and other useful reporting and CTAs
- Added 'waste bin' for temporarily storing deleted items before either restoring or permanently deleting
- Added sitemap.xml that references sitemaps per content type, limited to 500000 URLs per file
- Added RSS feed with category support

## [1.0.0] 2026-03-26

### Added

- Initial release
