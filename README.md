# FlowSoft WP — Developer Documentation

**Version:** 1.3.0  
**Requires PHP:** 7.4+  
**Requires WordPress:** 5.8+

## Architecture

FlowSoft WP follows a modular singleton architecture. Each optimization concern is encapsulated in its own module class with a consistent interface.

```
flowsoft-wp/
├── flowsoft-wp.php              # Entry point, constants, autoloading
├── .htaccess                    # Directory protection
├── includes/
│   ├── class-flowsoft-core.php       # Singleton orchestrator
│   ├── class-flowsoft-logger.php     # Activity & security logging
│   ├── class-flowsoft-scheduler.php  # WP-Cron management
│   ├── class-flowsoft-activator.php  # Activation routines
│   └── class-flowsoft-deactivator.php
├── admin/
│   ├── class-flowsoft-admin.php      # Admin UI registration
│   ├── class-flowsoft-ajax.php       # AJAX endpoints (rate-limited)
│   ├── views/
│   │   ├── dashboard.php
│   │   ├── modules.php
│   │   ├── settings.php
│   │   ├── logs.php
│   │   └── docs.php
│   ├── css/flowsoft-admin.css
│   └── js/flowsoft-admin.js
└── modules/
    ├── class-module-database.php
    ├── class-module-transients.php
    ├── class-module-heartbeat.php
    ├── class-module-revisions.php
    ├── class-module-assets.php
    ├── class-module-cron.php
    ├── class-module-media.php
    └── class-module-cache.php
```

## Module Interface

Every module must implement:

| Method | Return | Description |
|---|---|---|
| `get_id()` | `string` | Unique module identifier |
| `get_name()` | `string` | Display name |
| `get_description()` | `string` | Short description |
| `get_icon()` | `string` | SVG markup |
| `get_schedule()` | `string` | `immediate`, `daily`, `sixhours`, or `weekly` |
| `get_stats()` | `array` | Key-value stats for module card |
| `get_settings_fields()` | `array` | Settings field definitions |
| `run()` | `array` | Execute optimization; returns `['success' => bool, 'message' => string]` |

Modules with `immediate` schedule also implement `apply($settings)` called on every page load.

## Constants

Defined in `flowsoft-wp.php`:

| Constant | Default | Purpose |
|---|---|---|
| `FLOWSOFT_MAX_OVERHEAD_MB` | 10 | DB overhead threshold (MB) |
| `FLOWSOFT_MAX_EXPIRED_TRANSIENTS` | 100 | Health score penalty threshold |
| `FLOWSOFT_MAX_REVISIONS_THRESHOLD` | 500 | Health score penalty threshold |
| `FLOWSOFT_HEALTH_CACHE_TTL` | 300s | Health score cache duration |
| `FLOWSOFT_DB_OVERHEAD_CACHE_TTL` | 3600s | DB overhead query cache |
| `FLOWSOFT_MAX_LOG_AGE_DAYS` | 30 | Auto-cleanup: max log age |
| `FLOWSOFT_MAX_LOG_ENTRIES` | 10000 | Auto-cleanup: max log count |
| `FLOWSOFT_RATE_LIMIT_MAX` | 10 | AJAX requests per window |
| `FLOWSOFT_RATE_LIMIT_WINDOW` | 60s | Rate limit window |
| `FLOWSOFT_HEARTBEAT_MIN/MAX` | 15/300 | Heartbeat interval range (seconds) |
| `FLOWSOFT_CACHE_TTL_MIN/MAX` | 300/31536000 | Cache TTL range (seconds) |
| `FLOWSOFT_MAX_REVISIONS_MIN/MAX` | 1/100 | Revision limit range |
| `FLOWSOFT_MAX_IMAGE_SIZE_MIN/MAX` | 256/8192 | Image size limit range (KB) |

## Security

- **Rate Limiting:** 10 AJAX requests/minute per user (transient-based)
- **Nonce Verification:** All AJAX endpoints verify `flowsoft_nonce`
- **Capability Check:** `manage_options` required for all operations
- **Security Logging:** Failed nonces, unauthorized access, and rate limit events logged to `security` module
- **Input Validation:** Whitelist-based field filtering, numeric range clamping
- **Directory Protection:** `.htaccess` denies direct PHP file access
- **Error Handling:** Technical exception details logged internally; generic messages returned to users

## AJAX Endpoints

All endpoints require `nonce` parameter with `flowsoft_nonce` action.

| Action | Method | Description |
|---|---|---|
| `flowsoft_run_module` | POST | Run a single module |
| `flowsoft_toggle_module` | POST | Enable/disable a module |
| `flowsoft_save_settings` | POST | Save module settings |
| `flowsoft_get_stats` | POST | Get dashboard statistics |
| `flowsoft_get_logs` | POST | Get paginated logs |
| `flowsoft_clear_logs` | POST | Clear all logs |
| `flowsoft_run_all` | POST | Run all enabled modules |
| `flowsoft_export_logs` | POST | Export logs as CSV |

## Database

### Table: `{prefix}_flowsoft_logs`

| Column | Type | Index |
|---|---|---|
| id | BIGINT UNSIGNED PK | PRIMARY |
| module | VARCHAR(50) | KEY, COMPOSITE(module_status) |
| action_type | VARCHAR(100) | — |
| message | TEXT | — |
| items_affected | INT | — |
| bytes_freed | BIGINT | — |
| status | VARCHAR(20) | KEY, COMPOSITE(module_status, created_status) |
| created_at | DATETIME | KEY, COMPOSITE(created_status) |

## Hooks & Filters

| Hook | Type | Description |
|---|---|---|
| `flowsoft_cache_prefetch_domains` | filter | Modify DNS prefetch domain list |
| `flowsoft_daily_optimization` | action | Daily cron event |
| `flowsoft_sixhours_optimization` | action | 6-hour cron event |
| `flowsoft_weekly_optimization` | action | Weekly cron event |
| `flowsoft_monthly_log_cleanup` | action | Monthly log cleanup |
