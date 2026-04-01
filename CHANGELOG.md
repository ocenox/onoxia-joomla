# Changelog

## 1.2.5 — 2026-04-01

### Fixed
- JED compliance: package name changed from `pkg_onoxia` to `ONOXIA` (no type prefix allowed)
- JED compliance: component name changed from `com_onoxia` to `ONOXIA`
- JED compliance: plugin name changed to `System - ONOXIA AI Chatbot` (required `{Type} - {Name}` format)
- JED compliance: added `<updateservers>` block to component manifest
- JED compliance: added `<creationDate>` to package manifest
- Added `pkg_onoxia.sys.ini` language files for all 15 supported languages

## 1.2.0 — 2026-03-25

### Added
- Admin component `com_onoxia` with dashboard under Components > ONOXIA
- Connection status display (plugin active, token set, API reachable, site info)
- Configuration overview (sync settings, page restrictions)
- Quick links: Edit Settings, ONOXIA Dashboard, API Documentation
- Package installer `pkg_onoxia` bundling plugin + component
- German + English language files for component

### Changed
- Build script now creates 3 ZIPs: plugin, component, and package
- Recommended install method is now the package ZIP

## 1.1.0 — 2026-03-25

### Added
- 7 new languages: Japanese, Korean, Chinese, Thai, Malay, Indonesian, Vietnamese (15 total)
- Build script for reproducible release ZIPs
- CHANGELOG.md
- GitLab CI build and release pipeline

### Changed
- README rewritten in English for JED marketplace
- All 15 languages declared in plugin manifest

### Removed
- Empty `forms/` directory

## 1.0.0 — 2026-03-20

### Added
- Initial release
- Widget injection with site UUID and context tags
- Auto-sync Joomla articles as RAG sources
- llms.txt and sitemap import support
- Menu item restrictions for selective widget display
- 8 languages: EN, DE, FR, ES, IT, PT, NL, PL
- Joomla 4/5/6 compatibility (PSR-4, service provider, event subscriber)
