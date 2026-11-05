# digicyfr-service-menu 0.1.0 release notes

This is the first free GPL-2.0-or-later release for manual ZIP installation. Source: https://github.com/digicyfr/digicyfr-service-menu. The `release/0.1.0` branch records the reviewed version. The branch alone is source code; use the attached `digicyfr-service-menu-0.1.0.zip` and verify its `.sha256` sidecar before installation.

## Included

Reusable service catalog, categories, plain-text duration and price fields, public block and shortcode, draft archive, and guarded CSV export.

## Verification and compatibility

WordPress 7.1 and PHP 8.3.33. The exact ZIP passed install/activate/deactivate/reactivate, WordPress integration, interactive block insertion and category filtering in Chromium, browser keyboard and 390px checks, PHP/JS syntax, WPCS, and Plugin Check with only self-host distribution findings. The interactive block check was completed after the `v0.1.0` tag without changing the ZIP.

## Limits and follow-up checks

A manual screen-reader pass and multisite/network behavior remain untested. The Unicode title sort order follows the site database collation. No payment, booking or legal price-compliance feature is offered.

The `Update URI` protects this self-hosted plugin from an unrelated WordPress.org plugin with the same slug. It does not provide automatic updates. WordPress.org submission would need a separate review and distribution-specific changes. See [QA record](tests/QA-0.1.0.md) for the detailed local evidence.
