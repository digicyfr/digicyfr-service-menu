# Service Menu 0.1.0 local QA record

Date: 2026-11-05. The exact ZIP is published as a GitHub prerelease for self-hosted installation; stable promotion remains gated. The ZIP was rebuilt after README changes; runtime bytes are unchanged.

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

## Post-tag browser check on the exact GitHub ZIP

On 2026-11-05, the public `v0.1.0` ZIP and checksum were downloaded from GitHub; `sha256sum --check` matched `ff091f45141b650b1cbe6d4d6c9d3bc760d21280c5783fa4f97266560592308d`. The downloaded ZIP installed, activated, deactivated and reactivated on a fresh disposable WordPress 7.1/PHP 8.3.33 site. Chromium 156.0.8078.4 then exercised the real block editor:

- Searched the block inserter for **Service Menu**, clicked its result, and confirmed one `digicyfr/service-menu` block appeared with its editor placeholder and sidebar controls. [Inserted block screenshot](evidence/0.1.0/block-inserted.png).
- Entered the optional category slug `konsultacje` in the block sidebar, published a page, and confirmed its saved markup was `<!-- wp:digicyfr/service-menu {"category":"konsultacje"} /-->`. [Category inspector screenshot](evidence/0.1.0/category-inspector.png).
- Viewed the saved page at a 390px viewport. A published Polish service in that category and its price text appeared, the response was HTTP 200, document width was 390px, and Chromium reported no page errors. [Public render screenshot](evidence/0.1.0/public-390px.png).

The site, service and screenshots contain synthetic test data only. The disposable containers and volumes were removed after the check. This closes the interactive block insertion gate for the exact GitHub ZIP.

## Exact-ZIP automated accessibility check (2026-11-05)

The public GitHub ZIP and SHA-256 sidecar were downloaded again and verified; `scripts/release.py --verify` matched the 11 packaged source files and hash `ff091f45141b650b1cbe6d4d6c9d3bc760d21280c5783fa4f97266560592308d`. A fresh disposable WordPress 7.1/PHP 8.3.33 site with one synthetic service and one synthetic published block page was checked in Playwright Chromium with axe-core 4.10.3 (WCAG 2 A/AA, 2.1 AA and 2.2 AA rules). The [machine-readable report](evidence/0.1.0/axe-audit-2026-11-05.json) records all nodes and comparisons.

- The plugin's public service list, custom service details meta box, export page and archive confirmation had **zero axe violations** and zero incomplete checks. The full public page had the Twenty Twenty-Five navigation `list` violation also present on a WordPress core post without the service block.
- The full service editor had `aria-required-children`, `aria-required-parent` and `listitem` findings in WordPress's generated hierarchical taxonomy tabs. For a like-for-like baseline, a disposable test-only MU filter opened a core post in Classic Editor. Its native Categories tabs had the same three rule IDs and analogous `#category-tabs` targets; the plugin targets were `#digicyfr_service_cat-tabs`. These are WordPress core taxonomy meta-box markup findings, not custom service field markup. The core Classic Editor had additional findings outside the plugin's UI. The plugin editor also had two axe-incomplete contrast nodes, which require manual follow-up.
- Tab from Duration reached Price; both custom fields had associated labels. The browser accessibility tree exposed the synthetic service name and description. Public page and service editor document widths were 390px at a 390px viewport, with no page JavaScript errors.
- Automated axe does not replace a human NVDA/VoiceOver check. The disposable containers and volumes were removed, and the release ZIP, branch and tag were unchanged.

## Plugin Check

WordPress.org Plugin Check ran against the runtime-identical earlier ZIP. Its only findings were `plugin_updater_detected` (error: the unique Update URI is disallowed for WordPress.org-hosted plugins) and `load_plugin_textdomainFound` (warning: WordPress.org-hosted plugins do not need bundled translation loading). This candidate is for Digicyfr self-hosted distribution, where those choices are intentional. WordPress.org submission would need a separate channel-specific review.

## Current artifact recheck and remaining checks

On 2026-11-05, the rebuilt ZIP and SHA-256 sidecar matched the standalone and workspace packagers. This exact rebuilt ZIP passed install/activate/deactivate/reactivate and the complete WordPress integration fixture on WordPress 7.1/PHP 8.3.33. Earlier browser and Plugin Check evidence is from a byte-different ZIP with identical runtime files.

- The integration fixture invokes real WordPress APIs but does not simulate a forged HTTP nonce. The archive and CSV browser flows exercised the normal admin endpoints; contributor access was checked at the object/action and export-page capability paths.
- Interactive block insertion and filtered public rendering passed on the exact GitHub ZIP as recorded above. Human screen-reader testing and multisite network testing remain open. Compatibility outside WordPress 7.1/PHP 8.3.33 was not tested.
- At equal order, the bounded SQL query uses `LOWER(post_title)` compared bytewise and ID as tie-breaker. Case conversion follows the database character set; unusual Unicode ordering should be checked on a site's own database. No custom tables or upgrade migration are present in 0.1.0.
