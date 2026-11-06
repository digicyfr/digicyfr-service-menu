# Changelog

## 0.1.1

- Added the required `Contributors` readme header and refreshed `Tested up to` for WordPress 7.1.4 compatibility.
- Fixed WordPress Coding Standards findings in the test harness scripts (`tests/`), including variables that shadowed native WordPress globals (`$type`, `$taxonomy`, `$menu`).
- Added a PHPCS/WordPress Coding Standards check (`composer.json`, `phpcs.xml.dist`) to CI.
- No runtime behavior change.

## 0.1.0

- Initial release candidate with service posts, categories, merchant text fields, bounded block/shortcode renderer, archive confirmation, and CSV export.
