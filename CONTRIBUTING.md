# Contributing to FlowSoft WP

Thank you for considering contributing to FlowSoft WP. This guide explains how to get started.

## Development Setup

1. Clone the repository into your WordPress `wp-content/plugins/` directory
2. Activate the plugin in WordPress admin
3. The plugin creates a `flowsoft_logs` table on activation

## Code Standards

- **PHP:** Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- **JavaScript:** Use jQuery patterns consistent with WordPress admin
- **CSS:** Use the existing CSS custom property system (`--fs-*`)

## Adding a New Module

1. Create `modules/class-module-{name}.php`
2. Implement the required interface methods (see `README.md`)
3. Register the module in `FlowSoft_Core::load_modules()`
4. Add allowed fields in `FlowSoft_Ajax::save_settings()` whitelist
5. Add numeric ranges if applicable in `$numeric_ranges`
6. Add default options in `FlowSoft_Activator::set_default_options()`

## Security Checklist

Before submitting changes, verify:

- [ ] All SQL queries use `$wpdb->prepare()`
- [ ] All user input is sanitized (`sanitize_text_field()`, `absint()`, etc.)
- [ ] AJAX endpoints call `$this->verify_nonce()`
- [ ] Exceptions return generic messages to users (no `$e->getMessage()` in responses)
- [ ] Numeric settings have min/max validation in `$numeric_ranges`

## Commit Messages

Use descriptive commit messages:
```
[module] Short description of the change

Longer explanation of what changed and why.
```

## Versioning

We follow [Semantic Versioning](https://semver.org/):
- MAJOR: Breaking changes
- MINOR: New features (backward-compatible)
- PATCH: Bug fixes

Update `FLOWSOFT_VERSION` in `flowsoft-wp.php`, `Stable tag` in `readme.txt`, and `CHANGELOG.md` for every release.
