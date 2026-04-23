=== FlowSoft WP ===
Contributors: gridbasedigital
Tags: performance, optimization, database, cache, speed, cleanup, heartbeat, transients, revisions
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.3.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Agente de Optimización Continua y Silenciosa para WordPress. Monitorea, limpia y optimiza tu sitio 24/7 en segundo plano.

== Description ==

FlowSoft WP is not just another cache plugin — it's an intelligent performance agent that works autonomously in the background. It monitors, cleans, and optimizes your WordPress site 24/7, ensuring your server always operates at peak speed without you lifting a finger.

**Key Features:**

* **Database Optimizer** — Automatically cleans post revisions, auto-drafts, trash, spam comments, orphaned metadata, and optimizes database tables.
* **Transient Manager** — Cleans expired and orphaned transients every 6 hours to prevent database bloat.
* **Heartbeat Control** — Reduces WordPress Heartbeat API frequency to lower server CPU usage without breaking functionality.
* **Revision Manager** — Limits the number of post revisions stored and cleans excess revisions weekly.
* **Asset Optimizer** — Removes emoji scripts, disables oEmbed, strips query strings from assets, and optionally defers JavaScript.
* **Cron Health Monitor** — Detects and cleans duplicate or orphaned WP-Cron events daily.
* **Media Optimizer** — Identifies unattached and oversized media files to keep your uploads folder lean.
* **Cache Optimizer** — Manages browser cache headers, page cache headers, DNS prefetch, Object Cache diagnostics, and cache prewarming.

**Dashboard Features:**

* Real-time Health Score with animated gauge
* Module status grid with enable/disable toggles
* Detailed activity logs with filters and pagination
* Quick-action buttons for manual optimization
* Per-module configuration settings
* Modern, premium admin UI

== Installation ==

1. Upload the `flowsoft-wp` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **FlowSoft WP** in the admin sidebar to access the dashboard.

== Frequently Asked Questions ==

= Does FlowSoft WP replace my caching plugin? =
No. FlowSoft WP optimizes database overhead, asset loading, and background processes. It complements caching plugins like WP Super Cache or W3 Total Cache.

= Is it safe to run database optimizations? =
Yes. FlowSoft WP uses standard WordPress functions and safe SQL operations. However, we always recommend maintaining backups.

= Can I disable specific modules? =
Absolutely. Each module can be independently enabled or disabled from the Modules page.

= Will it slow down my site? =
No. Heavy operations run via WP-Cron in the background at scheduled times (e.g., 3 AM). Real-time optimizations (like removing emoji scripts) are lightweight.

== Changelog ==

= 1.3.0 =
* Added Cache Optimization module (browser headers, page cache, DNS prefetch, Object Cache diagnostics)
* Added security hardening: rate limiting, nonce validation, directory protection
* Added automatic log cleanup (30-day retention, 10,000 max entries)
* Added numeric range validation for all module settings
* Added composite database indexes for improved log query performance
* Refactored health score calculation into modular private methods
* Cached heavy information_schema queries with 1-hour transient
* Improved error handling: generic user messages, detailed internal logs
* Defined constants for all magic numbers
* Created CHANGELOG.md and technical documentation

= 1.2.0 =
* Initial release
* 7 optimization modules
* Premium admin dashboard
* Activity logging system
* Health score monitoring
