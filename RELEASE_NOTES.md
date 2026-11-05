# digicyfr-service-menu 0.1.0 release notes

This is the first stable self-hosted GPL-2.0-or-later release for manual ZIP installation. Source: https://github.com/digicyfr/digicyfr-service-menu. The `release/0.1.0` branch records the reviewed version. The branch alone is source code; use the attached `digicyfr-service-menu-0.1.0.zip` and verify its `.sha256` sidecar before installation.

## Included

Reusable service catalog, categories, plain-text duration and price fields, public block and shortcode, draft archive, and guarded CSV export.

## Verification and compatibility

WordPress 7.1 and PHP 8.3.33. The exact ZIP passed install/activate/deactivate/reactivate, WordPress integration, interactive block insertion and category filtering in Chromium, browser keyboard and 390px checks, PHP/JS syntax and WPCS. WordPress.org Plugin Check ran on a prior runtime-identical candidate and found only self-host distribution findings. The exact ZIP passed plugin-owned axe-core scans with zero violations; the full editor has WordPress-generated taxonomy-tab findings also present in a core Classic Editor baseline. See the [QA record](tests/QA-0.1.0.md).

## Limits and follow-up checks

Browser and axe automation support this stable release decision but do not certify WCAG conformance. A human screen-reader pass, two axe-incomplete editor contrast nodes, representative business-site validation and multisite/network behavior remain untested. The Unicode title sort order follows the site database collation. No payment, booking or legal price-compliance feature is offered.

The `Update URI` protects this self-hosted plugin from an unrelated WordPress.org plugin with the same slug. It does not provide automatic updates. WordPress.org submission would need a separate review and distribution-specific changes. See [QA record](tests/QA-0.1.0.md) for the detailed local evidence.
