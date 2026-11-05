=== Digicyfr Service Menu ===
Tags: services, business, price list, categories, shortcode
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

A reusable service list with categories, merchant-written price text, a block, shortcode, archive, and CSV export.

== Description ==

Create private service entries once, publish selected entries, and embed the ordered list with the Service Menu block or [digicyfr_service_menu] shortcode. Filter by an optional category slug. Duration and price are plain merchant text. No booking, checkout, tax, legal or price-compliance calculation is made.

The menu shows up to 100 services and visibly notes when more match. No single-service or taxonomy archive route is exposed. English and Polish strings are bundled. See README.md for CSV safety, data lifecycle and the full scope.

== Installation ==

1. Upload the plugin ZIP under Plugins > Add New > Upload Plugin and activate it.
2. Add services under Services. Use Excerpt for a plain-text description and the native Order field for 0–9999.
3. Add the Service Menu block or [digicyfr_service_menu category="optional-slug"] to a page.

== Frequently Asked Questions ==

= Can I enter a price such as "from 100 zł" or "Quote on request"? =

Yes. Price is optional text, not a calculated number.

= What does Archive do? =

After confirmation, it changes a published service to Draft. Publish restores it.

= What happens on uninstall? =

Service posts, terms and meta are retained to prevent accidental loss. Reactivate the plugin to edit them again.

== Changelog ==

= 0.1.0 =

* Initial candidate: reusable service posts, categories, text fields, bounded block/shortcode list, archive confirmation and guarded CSV export.
