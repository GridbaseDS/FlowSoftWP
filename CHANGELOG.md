# Changelog

All notable changes to FlowSoft WP will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [1.3.0] - 2026-04-23

### Added
- **Cache Optimization Module**: Browser cache headers, page cache headers for visitors, DNS prefetch/preconnect, Object Cache diagnostics, cache prewarming, and stale cache cleanup.
- **Security hardening**: AJAX rate limiting (10 req/min per user), security event logging, `.htaccess` directory protection.
- **Automatic log cleanup**: Monthly cron purges logs older than 30 days and caps at 10,000 entries.
- **Numeric range validation** for all settings (heartbeat intervals, cache TTLs, revision limits, image sizes).
- **Composite database indexes** on `flowsoft_logs` table for faster query performance.
- **Constants** for all magic numbers (`FLOWSOFT_MAX_OVERHEAD_MB`, `FLOWSOFT_HEALTH_CACHE_TTL`, etc.).
- **Log export** capability (CSV format).
- **Technical documentation**: `README.md` for developers, `CONTRIBUTING.md` guide.

### Changed
- Refactored `calculate_health_score()` into discrete private methods for readability.
- Cached `information_schema` query result with a 1-hour transient.
- Improved error handling: user-facing messages are now generic; full details are logged internally.
- Used `$wpdb->prepare()` for the `information_schema` overhead query.

### Fixed
- `readme.txt` Stable tag version mismatch (was 1.2.0, now 1.3.0).
- Added `wp_nonce_field()` to the settings form for CSRF protection.

## [1.2.0] - 2026-04-20

### Added
- Initial release with 7 optimization modules.
- Database Optimizer, Transient Manager, Heartbeat Control, Revision Manager, Asset Optimizer, Cron Health Monitor, Media Optimizer.
- Premium admin dashboard with health score gauge.
- Activity logging system with filters and pagination.
- Per-module enable/disable toggles.
- Scheduled background optimizations via WP-Cron.
