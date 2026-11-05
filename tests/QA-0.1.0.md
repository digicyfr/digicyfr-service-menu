# Service Menu 0.1.0 local QA record

Date: 2026-11-05. This is a local self-hosted release candidate, not a public release. The ZIP was rebuilt after README changes; runtime bytes are unchanged.

## Exact artifact

- ZIP: `dist/digicyfr-service-menu-0.1.0.zip` (11 runtime/documentation files, one plugin root, mode 0644)
- SHA-256: `ff091f45141b650b1cbe6d4d6c9d3bc760d21280c5783fa4f97266560592308d`
- `tools/verify_zip.py --against-source` passed after the last runtime edit. The ZIP matches the manifest and source byte for byte. `sha256sum --check` passed from `dist/`.

## Automated checks passed

- PHP 8.3.33 syntax for runtime and test PHP; JavaScript syntax; block JSON parse; Polish MO load.
- WordPress Coding Standards 3.4.1 `WordPress` ruleset on runtime PHP: zero errors and warnings.
- Exact ZIP installed, activated, deactivated and reactivated in disposable WordPress 7.1/PHP 8.3.33.
- Real WordPress integration checks the private CPT/taxonomy/REST/search flags; native fields; registered private, object-aware meta; administrator nonce save; literal backslash round trip in merchant text; UTF-8-safe 120/200-character boundaries; two categories per service with no unfiltered duplication; ascending order and case-fold/ID ties; order changes without editing the page; unknown and deleted category filters; block/shortcode parity; blank-title publish rejection; invalid order preservation; hostile title/excerpt/meta HTML exclusion; publish-to-draft archive and native Publish restore; public visitor exclusion of draft and Trash; contributor denial of another owner's edit/archive/meta and export page.
- CSV fixture verifies exact quoted UTF-8 header, published and draft rows only, Polish text, quoted commas/newlines/quotes, apostrophe neutralization of leading `=`, `+`, `-`, `@`, tab, carriage return and whitespace-prefixed formulas. The export did not modify services.
- 100-item cap fixture includes 101 published services, a visible public limit note, and an administrator warning. A second boundary fixture adds 105 same-order numbered titles plus Polish accented titles; a separate Beta/beta fixture checks case-fold and ID ties; it verifies that the SQL selection and visible first 100 agree at the boundary.
- Deactivation and uninstall retain service posts, as documented.

## Browser checks on the runtime-identical earlier ZIP

- Chromium administrator session created and published a service with description, duration, price and order. Its public embed rendered at 390px without horizontal overflow.
- Archive confirmation was reached with keyboard Enter, changed the service to Draft, and removed it from the public menu. CSV export downloaded and included the draft row.
- An independent disposable Chromium run also traversed the native editor, archive confirmation, draft list and CSV download. On a 390px editor viewport, document width was 390px and the price field's right edge was 365px. Disposable sites and volumes were removed after checks.

## Plugin Check

WordPress.org Plugin Check ran against the runtime-identical earlier ZIP. Its only findings were `plugin_updater_detected` (error: the unique Update URI is disallowed for WordPress.org-hosted plugins) and `load_plugin_textdomainFound` (warning: WordPress.org-hosted plugins do not need bundled translation loading). This candidate is for Digicyfr self-hosted distribution, where those choices are intentional. WordPress.org submission would need a separate channel-specific review.

## Current artifact recheck and remaining checks

On 2026-11-05, the rebuilt ZIP and SHA-256 sidecar matched the standalone and workspace packagers. This exact rebuilt ZIP passed install/activate/deactivate/reactivate and the complete WordPress integration fixture on WordPress 7.1/PHP 8.3.33. Earlier browser and Plugin Check evidence is from a byte-different ZIP with identical runtime files.

- The integration fixture invokes real WordPress APIs but does not simulate a forged HTTP nonce. The archive and CSV browser flows exercised the normal admin endpoints; contributor access was checked at the object/action and export-page capability paths.
- The editor's interactive block inserter was not tested; server block registration and front-end render passed. Screen-reader testing and multisite network testing remain open. Compatibility outside WordPress 7.1/PHP 8.3.33 was not tested.
- At equal order, the bounded SQL query uses `LOWER(post_title)` compared bytewise and ID as tie-breaker. Case conversion follows the database character set; unusual Unicode ordering should be checked on a site's own database. No custom tables or upgrade migration are present in 0.1.0.
