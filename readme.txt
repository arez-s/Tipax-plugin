=== Tipax Shipping ===
Contributors: Alireza Sabahi, E-Tipax
Tags: Tipax, shipping, woocommerce,persian woocommerce,persian,ووکامرس فارسی
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
WC requires at least: 7.1
WC tested up to: 10.9
Stable tag: 3.13.6
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Tipax Company Direct Shipping Order for WooCommerce

== Description ==

Tipax integration with WooCommerce. Using this plugin, you will be able to place a Tipax order from your WooCommerce.

Key capabilities in the current release:

- Light, responsive Persian admin panel with locally bundled Vazirmatn typography, WordPress icons, and consistent navigation, forms, cards, and tables
- Automatic Tipax tracking sync with scheduled daily background updates
- Status-aware tracking updates restricted to active Tipax status IDs
- Batch tracking status refresh tools inside the admin Orders page
- Date-range filtering in the Tipax Orders page, including older and all-date orders
- Strict Tipax shipping-method filtering with a separate opt-in view for non-Tipax orders
- Secure single and multi-parcel cancellation from the Tipax Orders page
- Single and bulk 10x16 cm Tipax shipping-label printing in Tipax and WooCommerce order screens
- Selectable full, sender-free, barcode-only, and sender-phone-free Tipax label layouts
- Packing-aware heavy-order splitting that keeps every package inside the weight and dimension limits returned by Tipax `PackingPrices`
- Catalog-aware no-packing resolution that uses only Tipax no-packing IDs 507, 504, and 521 after validating weight, dimensions, the 30.5 kg boundary, and the envelope value ceiling
- Editable default-package dimensions in settings and retry forms, with exact catalog dimensions enforced for selected Tipax packing
- Optional Same Day pricing and registration for eligible Tehran-to-Tehran orders before 13:00 Iran time, with service ID 12, Tipax restrictions, and mandatory recipient map coordinates
- Minimal responsive Neshan location picker for Same Day in classic, Elementor/custom, and WooCommerce Block Checkout, with all Neshan credentials kept behind a restricted same-origin server proxy
- Public tracking by tracking code or numeric contract code via shortcode and a dedicated WooCommerce My Account tracking section
- Selectable offline or online Tipax registration during Checkout, with configurable online timeout and automatic offline fallback
- Online manual registration and retry tools in the Tipax Orders screen, independent of the Checkout registration mode
- Province-grouped Tipax Cities management screen backed by the same local city table used for Checkout city resolution
- Manual city-list synchronization from Tipax with safe insert/update behavior and preserved per-city settings
- Automatic replacement of obsolete Tipax city IDs during synchronization, with legacy aliases retained for old WooCommerce orders
- Per-city Tipax shipping availability controls that keep the city selectable in Checkout while hiding the Tipax rate
- Conflict-safe Tipax city resolution that remains independent from city IDs used by other shipping plugins
- Tipax city selectors for classic shortcode Checkout, Elementor/custom Checkout layouts, and WooCommerce Checkout Blocks without replacing the Checkout page content
- Automatic Tapin-to-Tipax mapping only for confirmed title differences, stored in a dedicated mapping table and guarded by province and unique-candidate validation
- Stored two-way city coverage reports for Tapin cities missing from Tipax, ambiguous matches, and Tipax cities missing from Tapin
- Financial report screen for the latest Tipax account transactions
- Support/ticketing screen with validated department and priority selectors for creating, listing, and viewing Tipax tickets
- Configurable log recording with the ability to fully disable logging and hide the Logs screen
- Complete sender/receiver settlement flow in which Tipax charges the full API shipping rate to the sender for credit, wallet, and cash methods, while any configured customer shipping charge is added independently to the WooCommerce order
- Tipax cash-on-delivery gateway support for both classic Checkout and WooCommerce Checkout Blocks
- Correct postpay shipping text in classic and block Checkout instead of displaying postpay delivery as free shipping
- Conflict-safe display of Iranian province codes and numeric third-party city IDs in WooCommerce orders and Tipax labels
- Built-in tutorial tab with the official plugin video and documentation portal link

== Requirements and Compatibility ==

- WordPress 5.8 or newer. Activation and core-service loading were verified on WordPress 5.8 and 7.0.2.
- WooCommerce 7.1 or newer. Compatibility smoke tests were run with WooCommerce 7.1.1 and 10.9.4.
- PHP 7.4 or newer. The supported range was smoke-tested on PHP 7.4 and PHP 8.5.
- The PHP `mbstring` extension is required by the bundled barcode generator.
- The PHP `openssl` extension is recommended for the authenticated static-map credential; a portable server-side fallback is included for compatible hosts without OpenSSL.
- Outbound HTTPS access to `https://omapi.tipax.ir` is required for authentication, rates, orders, tracking, cancellation, financial reports, and tickets.
- Same Day map display additionally requires outbound HTTPS access from the WordPress server to `https://api.neshan.org/v5/static`.
- Both legacy WooCommerce order storage and High-Performance Order Storage (HPOS) paths are supported.
- WooCommerce must be installed and active before this extension can be used.

== Installation ==

A complete Persian, RTL, print-ready installation and initial setup guide is included in `docs/INSTALLATION-FA.html`.

= Using The WordPress Dashboard =

1. Navigate to the Add New in the plugins dashboard
2. Search for Tipax Shipping
3. Click Install Now
4. Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the Add New in the plugins dashboard
2. Navigate to the Upload area
3. Select tipax-shipping.zip from your computer
4. Click Install Now
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download tipax-shipping.zip
2. Extract the tipax-shipping.zip directory to your computer
3. Upload the tipax-shipping.zip directory to the /wp-content/plugins/ directory
4. Activate the plugin in the Plugin dashboard

== External Services ==

= Tipax Plugin Hub =

- Service URL: https://tipaxplugin.ir
- Privacy Policy: https://tipaxplugin.ir/privacy
- Service Terms: https://tipaxplugin.ir/terms

The standalone distribution displays the popup after installation or explicit plugin reactivation. Acknowledgement and successful site registration enable the service. The choice survives ordinary page loads and later plugin updates; a manual reactivation asks again. The second action below acknowledgement deactivates this plugin using WordPress's normal deactivation API. Escape, outside clicks, timers and old sessionStorage dismissal flags cannot close the popup. Until acknowledgement, no installation status is sent and hub update checks are not scheduled.

The popup explains the exact data before acceptance: site URL/domain, random technical installation UUID, plugin version, WordPress version, WooCommerce version, PHP version, Tipax username after acknowledging the expanded disclosure, active/inactive state and plugin version changes. Status/version notifications use lifecycle events and a daily background heartbeat. Deactivation notification is best effort and may not arrive during a network outage. This release keeps local cumulative totals of real Tipax price inquiries and successfully registered Tipax orders. An unchecked optional setting enables one signed aggregate snapshot per day; revoking it stops transmission and drops pending snapshots.

The optional usage checkbox identifies Tipax as the recipient and discloses service improvement and offering exclusive discounts as the purposes. Its label and description are shared by the custom Tipax API settings page and the WooCommerce Tipax shipping settings. This is separate from WooCommerce's own usage tracking preference.

No customer name, phone, delivery address, cart contents, order amount, order ID, tracking code, Tipax password/token or raw Tipax API response is sent to this hub. Raw server IPs may be visible in normal hosting access logs. The expanded username disclosure is acknowledged once while existing service acceptance remains valid; ordinary page loads do not prompt again after that. Usage statistics have a separate optional checkbox in the existing settings. Deactivating the plugin stops future hub communication after a best-effort final inactive notification when permission had already been granted. Previously received records follow the hub privacy policy.

Published raster images are synchronized hourly in background tasks and validated by MIME, dimensions, size and SHA-256. A published `notice_banner` appears above every Tipax admin tab using only its tipaxplugin.ir URL; no destination-site image fallback is displayed. Publication is checked again when opening or returning to an admin tab. Deleted/archived images are removed from display and their local validation cache is purged with an authenticated, nonce-protected request. Replacing the published banner changes it on the next synchronization without a plugin update. The checkout, rate calculation and order submission paths do not wait for the hub. Direct-distribution update metadata comes from `/api/v1/releases/latest?channel=direct`; packages are installed only through WordPress's normal updater and validated against the published SHA-256. The old sabahi.ir update endpoint is no longer used. Update checks do not attach the site URL, installation ID or usage counts.

Starting with 3.13.1, the standalone distribution supports the hub's optional requirement to use the latest published final direct version. An hourly background check receives this policy alongside validated release metadata. When the installed version is older than the required version, Tipax services pause and an admin notice points to WordPress's normal update screen. Installing the required version, or receiving an explicit removal of the requirement from the hub, restores service. Missing or invalid policies do not introduce a block; a network error preserves the last valid policy. Shipping and order paths read cached local state only. Previously published clients through 3.13.0 do not recognize this command.

A separate WordPress.org package omits the standalone updater and Update URI, removes the service acceptance gate and mandatory-update gate, and keeps status reporting and remote images optional and disabled by default. Do not submit the standalone package to the directory.

= Shipping services =

This plugin connects to the Tipax delivery API service in order to calculate shipping rates, create shipments, retrieve tracking information, and synchronize the supported city list.

The following external service is used:

- Service Name: Tipax Online Delivery API
- Service URL: https://omapi.tipax.ir
- Terms of Use: https://tipaxco.com/rules/
- Privacy Policy: https://tipaxco.com/rules/

For Same Day delivery, the WordPress server requests static Neshan map images and returns only the PNG bytes through a signed, rate-limited, Tehran-only same-origin proxy. No plaintext Neshan credential is present in the package or sent to the customer's browser. The selected latitude and longitude are stored with the WooCommerce order and sent to Tipax only for Same Day service ID 12.

- Service Name: Neshan Maps Platform
- Static Map URL: https://api.neshan.org/v5/static
- Platform URL: https://platform.neshan.org

No manual key configuration is required for normal installations. The default static-map credential is stored as an authenticated AES-256-GCM payload and is decrypted only in server-side PHP memory. A portable obfuscated fallback is available when OpenSSL is unavailable. For emergency rotation or controlled deployments, the bundled value can be overridden with the `TIPAX_NESHAN_STATIC_MAP_KEY` environment variable or the following `wp-config.php` constant:

`define('TIPAX_NESHAN_STATIC_MAP_KEY', getenv('TIPAX_NESHAN_STATIC_MAP_KEY') ?: 'YOUR_STATIC_MAP_KEY');`

A valid override always takes priority over the bundled credential; invalid placeholders fall back safely to the bundled server-side value. Do not add a plaintext key to this plugin directory, a theme, frontend JavaScript, HTML, logs, or a distributable ZIP. A Web SDK key is not required by this implementation.

By using this plugin, data such as customer shipping address, order details, and tracking requests may be sent to Tipax for the purpose of delivery and logistics management.

Please review Tipax's terms and policies to ensure compliance with your local data regulations.

== Bundled Fonts ==

Vazirmatn is bundled locally for the plugin admin panel. Copyright 2015 The Vazirmatn Project Authors.
Source: https://github.com/rastikerdar/vazirmatn
License: SIL Open Font License 1.1, included in assets/admin/fonts/OFL.txt.

== Changelog ==
= 3.13.6 - 2026-11-12 =
* Added an informational account setup guide with the official E-Tipax registration link on the API configuration tab.
* Translated the plugin description into Persian, displayed the author as علیرضا صباحی, and removed the personal author website link.
* Restricted sender address lists and origin selection to peopleAddressTypeId=1, including cached origin validation and explicit sender typing when creating addresses.

= 3.13.5 - 2026-11-07 =
* Fixed the tutorial documentation link text and icon contrast in normal, hover, and keyboard-focus states.
* Positioned the rial currency label to the left of amounts in the financial report table while preserving left-side negative signs.
* Positioned hours and minutes to the right of dates in the financial report table, including Persian and Arabic digits.

= 3.13.4 - 2026-11-04 =
* Recognized the reported province spelling "کهکیلویه وبویراحمد" as an explicit alias of "کهگیلویه و بویراحمد".
* Combined this alias in admin province options, city counts, searches, pagination, and checkout city lookup without relaxing city-name matching.
* Preserved the selected province when opening existing admin filters containing the alias.

= 3.13.3 - 2026-11-04 =
* Fixed incomplete checkout city lists when a province has multiple equivalent Arabic/Persian spellings or spacing variants.
* Unified equivalent province names in the Cities admin filters, headings, searches, and pagination without changing stored city IDs.
* Hid RW province and staff provinces from the Cities admin list, filters, and summary counts while preserving their database records.

= 3.13.2 - 2026-11-03 =
* Translated the API connection instructions into Persian.
* Redesigned the admin panel with a light slate background, white cards, dark teal accents, and consistent spacing and borders.
* Bundled Vazirmatn locally and replaced admin emoji icons with standard WordPress Dashicons.
* Standardized tab heights, active states, buttons, forms, and primary and secondary address actions.
* Added keyboard-accessible horizontal scrolling for wide tables on smaller screens, including AJAX-loaded results.
* Improved address, order, city, support, and financial layouts while preserving existing workflows and the approved service acknowledgement popup.

= 3.13.1 - 2026-11-02 =
* Support the hub's requirement to use the latest final direct version, with hourly background policy checks.
* Pause outdated Tipax services while keeping WordPress updates available; clear cached Tipax rates when policy changes.
* Restore service after updating or receiving an explicit unlock command. Directory builds omit this feature.

= 3.13.0 - 2026-11-02 =
* Disclose the Tipax username before sharing it with the hub.
* Keep local totals of real Tipax price inquiries and successfully registered Tipax orders.
* Add an unchecked optional setting for one daily signed counter snapshot on both existing settings pages; revoking permission stops transmission.
* Use the approved tracking label and description and validate the settings form nonce and permissions before saving.
* Keep existing service acceptance valid while requesting acknowledgement of the expanded username disclosure.
* Preserve hourly background image synchronization.

= 3.12.6 - 2026-10-31 =
* Persisted pending installation credentials before registration, so a lost response retries the same installation instead of creating a duplicate.
* Serialized concurrent registration attempts and retained compatibility with existing installation credentials.

= 3.12.5 - 2026-10-30 =
* Confirmed active status immediately after acknowledgement, including reactivated sites that already have a hub installation ID.
* Only mark lifecycle notifications successful when the hub confirms the requested status; queue a short retry after delivery failures.

= 3.12.4 - 2026-10-30 =
* Added the requested deactivation text action below the acknowledgement button; rejection uses WordPress plugin deactivation.
* Removed temporary popup dismissal: Escape, outside clicks and old sessionStorage values cannot close the choice.
* Ask for a choice on explicit plugin reactivation while preserving acknowledgement across ordinary updates and page loads.

= 3.12.3 - 2026-10-30 =
* Revalidated banner publication when opening or returning to plugin tabs so archived/deleted images are removed without waiting for WP-Cron.
* Removed local-image display fallback; banners always use their tipaxplugin.ir image URL.
* Removed unpublished entries from local media metadata before attempting replacement downloads.

= 3.12.2 - 2026-10-30 =
* Removed the extra service/hub menu, tab, status page and related settings link; preserved the one-time popup.

= 3.12.1 - 2026-10-30 =
* Applied the approved #33936e popup with only the specified title, paragraph and single acknowledgement button.
* Included WooCommerce version alongside the existing WordPress, PHP and plugin versions in status reporting.

= 3.12.0 - 2026-10-30 =
* Added a one-time acceptance popup and site-bound registration for the standalone service; acceptance survives later updates.
* Added signed activation/deactivation/version reporting after acceptance, without order counts, quote counts or customer data.
* Kept all hub requests outside checkout and order/pricing operations; no usage counters are instrumented.
* Added verified, locally cached hub images and a standalone update source at tipaxplugin.ir.
* Removed the sabahi.ir updater and its site-URL tracking query.
* Added a separate directory package without direct updates, with optional reporting and no service gate.

= 3.11.2 - 2026-10-15 =
* Added a portable server-side credential fallback for hosts without the PHP OpenSSL extension and ignored invalid override placeholders.
* Restored zero-configuration Same Day map installation by adding an authenticated AES-256-GCM bundled static-map credential fallback.
* Kept the credential absent from plaintext source, HTML, JavaScript, browser requests, logs, and the public map-proxy response.
* Kept environment and `wp-config.php` credentials as higher-priority overrides for immediate key rotation without a plugin release.
* Verified the decrypted fallback directly against the Neshan static-map endpoint without printing or persisting the plaintext value.
* Documented that bundled encryption is extraction resistance rather than a replacement for a centrally controlled credential gateway.

= 3.11.1 - 2026-10-15 =
* Removed the Neshan Web SDK and all embedded Neshan credentials from the plugin source and distributable package.
* Replaced browser-side key usage with a server-rendered static-map picker that preserves click, drag, zoom, geolocation, responsive layout, and Same Day coordinate selection.
* Added a signed same-origin map proxy with one-hour access tokens, per-map-session rate limiting, strict Tehran coordinates, fixed Neshan upstream, bounded image dimensions, bounded zoom, PNG validation, and generic upstream errors.
* Added a server-only `TIPAX_NESHAN_STATIC_MAP_KEY` environment/`wp-config.php` configuration path and documented secure provisioning.
* Removed the reverse-geocoding request because the supplied key is scoped to static maps rather than the Reverse Geocoding service.
* Restricted Same Day coordinates and browser geolocation to the Tehran service area before they can be stored or sent to Tipax.

= 3.11.0 - 2026-10-15 =
* Added a minimal responsive Neshan location picker that appears only when the eligible Tipax Same Day rate is selected.
* Made recipient latitude and longitude mandatory for Same Day across classic Checkout, Elementor/custom Checkout, and WooCommerce Checkout Blocks.
* Isolated the Neshan SDK inside a same-origin iframe so Leaflet versions loaded by themes or other shipping plugins cannot conflict with Tipax Checkout.
* Added nonce-protected WooCommerce session storage, Store API schema and validation, classic Checkout validation, and HPOS-compatible order metadata for the selected coordinates.
* Added Same Day destination coordinates to every service-ID-12 registration payload, including online, offline, automatic, and manual retry flows, without changing other Tipax service payloads.
* Kept reverse geocoding optional so a Neshan service-key permission failure does not prevent coordinate selection or Same Day registration.

= 3.10.5 - 2026-10-06 =
* Added the optional Tipax Same Day service with an independent service-ID-12 pricing request alongside the normal in-city rate.
* Restricted Same Day to Tehran-to-Tehran shipments created before 13:00 Iran time, with a maximum total weight of 12 kg, maximum rotatable dimensions of 37 x 37 x 15 cm, and envelope or parcel pack types only.
* Preserved Same Day service ID 12 across Checkout registration, offline orders, automatic registration, and manual retry without adding latitude or longitude fields or changing the existing packing IDs.
* Replaced weight-only default packing with a catalog-aware no-packing resolver that exclusively selects IDs 507, 504, or 521 from the current `PackingPrices` limits.
* Added dimension and parcel-value validation for envelope segments, including the current 2,000,000 Toman ceiling, and safely falls back from no-packing envelope ID 507 to no-packing parcel ID 504 when eligible.
* Updated the no-packing heavy segment to the current 100 kg limit, retained automatic splitting above that limit, and now stops locally with a clear error when no valid no-packing segment exists.
* Unified no-packing behavior across pricing, online registration, offline registration, and manual retry, while keeping exact catalog data for explicitly selected paid packing.
* Removed the three internal no-packing segment records from the paid-packing selector and automatically treats their legacy saved IDs as the default no-packing mode.
* Updated packing ID 504 to cover parcels through 30.5 kg and packing ID 521 to cover parcels above 30.5 kg through 100 kg only when parcel height is greater than 10 cm.
* Enforced a maximum payload height of 5 cm for flyer and envelope packing, including catalog records whose nominal dimensions use a different axis order.
* Left parcel and heavy-segment value limits to the authenticated Tipax contract so standard 100,000,000 Toman and contractual 300,000,000 Toman limits are not incorrectly hardcoded in the plugin.
* Added an admin Tutorial tab with the official Tipax plugin video and documentation link while keeping the navigation on a single responsive row.
* Replaced the misleading free-shipping label for postpay rates with a message explaining that shipping is paid at delivery in classic and block Checkout.
* Fixed PHP warnings when Tipax returns an HTTP 400 error as a JSON string and now preserves the actual API message as a failed registration state for safe manual retry.
* Fixed manual retry for envelopes, flyers, and branded sacks whose `PackingPrices` records contain a zero nominal dimension; payload dimensions now use valid catalog minimum/maximum fallbacks and remain within every reported bound.

= 3.10.4 - 2026-10-06 =
* Fixed duplicate cities remaining after Tipax changed a city ID, including the `1217` to `3076` replacement for Eyvan in Ilam.
* Made the current API city record authoritative while storing legacy city-ID aliases for historical orders, labels, retries, and Tapin mappings.
* Preserved per-city disabled status when an obsolete city ID is replaced by its current ID.
* Deduplicated repeated API city identities by province and normalized city title without merging same-named cities from different provinces.
* Updated the bundled initial city catalog and added integration coverage for synchronization, deletion, aliases, and old-order resolution.

= 3.10.3 - 2026-09-05 =
* Sorted all standard Iranian provinces in an explicit, server-independent Persian alphabetical order across classic, custom, and block Checkout surfaces.
* Preserved province labels supplied by WooCommerce and retained custom provinces added by other extensions.
* Added regression coverage for Persian province ordering and isolation from non-Iranian country data.

= 3.10.2 - 2026-09-05 =
* Corrected default no-packing weight profiles to `507/10/3` below 2 kg, `504/20/9` from 2 through 30 kg, and `521/60/19` above 30 through 80 kg.
* Corrected all PackagingContents mappings and automatically repaired stale content IDs saved by older releases: envelope `10=>3`, parcel `20=>9`, mini-pack `50=>10`, heavy `60=>19`, and eco-pack `70=>36`.
* Raised the default per-package segment ceiling to 80 kg and split heavier shipments into valid packages before pricing, Checkout registration, offline registration, and manual retry.
* Updated packing guidance in settings, retry forms, and the Persian installation guide.
* Added regression coverage for the 2 kg, 30 kg, and 80 kg boundaries, the reported 18 kg payload, content mappings, and heavy-order splitting.

= 3.10.1 - 2026-09-05 =
* Fixed numeric Iranian city IDs and province codes remaining visible in WooCommerce admin when the order used a non-Tipax shipping method.
* Limited the display-only resolver to Iranian addresses while preserving raw order data and every shipping method's stored city ID.

= 3.10.0 - 2026-09-02 =
* Added a complete Persian installation and initial setup guide covering WooCommerce shipping zones, API connection, origin address, cities, settlement, Checkout testing, tracking, and troubleshooting.
* Restored Tipax city-list loading without disabling Gutenberg or replacing the Checkout page content with `[woocommerce_checkout]`.
* Added one city-loading flow for classic Checkout, shortcode layouts, Elementor/custom Checkout forms, and WooCommerce Checkout Blocks.
* Added Checkout Block synchronization through the WooCommerce cart data store and Store API while keeping the core city field valid.
* Fixed the first Checkout Block city request being discarded when its loading state triggered another DOM observer pass.
* Prevented the core city text field or duplicate Tipax selects from appearing after a Checkout Block Store API rerender.
* Removed the forced disabling of Tipax cities when Persian WooCommerce Shipping or another shipping extension is active.
* Made the explicit "Tipax cities" setting authoritative: enabled lets Tipax render the city list, while disabled preserves the theme or another extension's city field for backend mapping.
* Preserved custom field priority, classes, and attributes used by Elementor and other Checkout builders.
* Replaced the city AJAX HTML response with nonce-protected JSON and server-side province code/title resolution.
* Prevented Tipax numeric city IDs from being confused with third-party city IDs by prioritizing authoritative city titles during backend resolution.
* Added regression coverage for explicit city ownership, third-party field preservation, and custom Checkout field compatibility.

= 3.9.9 - 2026-09-02 =
* Added automatic default packing selection per parcel: `packingId=504` below 2 kg and `packingId=507` from 2 through 30 kg.
* Made the configured default weight use kilograms and default package dimensions use centimeters independently from WooCommerce product units.
* Preserved the exact merchant-entered length, width, and height for default packing instead of converting them into cubic dimensions.
* Made selected Tipax packing use its exact catalog dimensions and matching `packType` across pricing, Checkout registration, offline retry, and manual registration.
* Hid manual dimension controls for catalog packing and restored them when switching back to default packing in settings and the retry form.
* Removed silent fallback from an explicitly selected catalog packing to default packing when its weight or metadata is invalid.
* Added regression coverage for the 2 kg boundary, the 30 kg boundary, heavy splitting, exact manual dimensions, and catalog-dimension replacement.

= 3.9.8 - 2026-08-24 =
* Fixed the receiver postcode baseline overlapping the label footer divider.
* Added dedicated numeric typography and safe bottom spacing to the receiver postcode block without changing the 10x16 cm label size.

= 3.9.7 - 2026-08-24 =
* Fixed numeric third-party city IDs and Iranian province codes appearing in WooCommerce order lists and Tipax labels.
* Added order-location resolution through saved Tipax snapshots, Persian WooCommerce/Tapin city data, the local Tipax city table, and full-address matching.
* Added an Iran fallback when Checkout stores an empty country alongside province codes such as `HRZ`.
* Kept Tipax destination resolution independent from other shipping plugins and prevented current Checkout session data from affecting manual retries.
* Persisted resolved Tipax destination snapshots for WooCommerce Checkout Blocks orders.

= 3.9.6 - 2026-08-22 =
* Fixed Flyer A4 and other selected-packaging validation to enforce both the minimum and maximum dimensions returned by Tipax.
* Made `packingId`, `packType`, and `packageContentId` update atomically across Checkout, online registration, offline registration, and manual retries.
* Made the current Tipax `PackingPrices` catalog authoritative for selected packing metadata, repairing stale saved `packType` values automatically.
* Added regression coverage for Flyer A4 dimensions, rotated dimensions, and replacement of stale packing metadata.

= 3.9.5 - 2026-08-22 =
* Fixed the Tipax "segment type not found" error for heavy orders using the default no-packing option.
* Matched default `packingId=504` and `packType=20` to their 30 kg maximum from `PackingPrices`, splitting heavier orders into valid packages.
* Used the selected packing's own `maxWeight` for package splitting and added a safe 30 kg fallback when selected packing metadata cannot be applied.
* Preserved realistic dimensions during weight-only splitting and removed the unsafe package-count cap based on cart item quantity.
* Kept total shipment weight, package value, and COD allocation consistent across pricing, Checkout registration, and manual retries.

= 3.9.4 - 2026-08-22 =
* Fixed Tipax COD registration when the WooCommerce collectible total, including a configured customer shipping charge, is greater than the original goods value.
* Ensured every registration package has `packageValue` greater than or equal to its allocated `cod` amount, as required by the Tipax API.
* Applied the COD value constraint consistently to Checkout registration, manual retries, and multi-package orders without changing non-COD package values.

= 3.9.3 - 2026-08-22 =
* Fixed classic Checkout hiding and deselecting the Tipax COD gateway when WooCommerce renders the sole Tipax shipping rate as a hidden input.
* Kept Tipax COD as the only available payment method when COD is enabled and Tipax shipping is selected, while relying on the server-side WooCommerce session for shipping selection.
* Verified the Tipax COD gateway remains compatible with WooCommerce Checkout Blocks.

= 3.9.2 - 2026-08-22 =
* Corrected receiver post-pay plus COD registration to keep `paymentType=20` and send the WooCommerce order total only through the separate `cod` field.
* Removed the invalid global COD override to `paymentType=30`; COD no longer replaces the configured freight settlement method.
* Added versioned order settlement snapshots and made order-level snapshots authoritative over stale shipping-rate metadata.
* Added settlement snapshot persistence for WooCommerce Checkout Blocks.
* Invalidated WooCommerce shipping-rate caches after settlement-setting changes and once during this settlement schema upgrade.

= 3.9.1 - 2026-08-22 =
* Restored Tipax COD requests to the required `paymentType=30` while retaining the complete WooCommerce order total in the `cod` field.
* Added backward repair for settlement snapshots created before the COD payment-type correction.
* Added native Tipax COD payment-method registration for WooCommerce Checkout Blocks while retaining classic Checkout support.
* Hardened payment-gateway loading against WooCommerce/plugin initialization order differences and declared Cart/Checkout Blocks compatibility.

= 3.9.0 - 2026-08-22 =
* Added a dedicated `tipax_city_mappings` database table for genuine Tapin/Tipax city-title differences; ordinary exact matches are not persisted.
* Added scheduled Tapin city mapping synchronization and a stored two-way coverage report without adding new admin UI.
* Added strict province validation and unique-candidate enforcement for every mapped Tipax city ID.
* Added the controlled `صنعتی` qualifier alias needed for township names such as `شهرک عباس آباد` and `شهرک صنعتی عباس آباد`, without treating broader names such as `شهر صنعتی پرند` as equivalent to `پرند`.
* Added unique province-scoped matching for safe parenthetical qualifiers such as `خورزوق` / `خورزوق (اصفهان)` and `اصفهان-(سپاهان شهر)` / `سپاهان شهر`.
* Prevented the optional `شهر` prefix rule from incorrectly stripping the beginning of names that start with `شهرک`.

= 3.8.3 - 2026-08-22 =
* Removed the incorrect sender-contribution split from settings, pricing, Checkout labels, and manual retry registration.
* Made the full Tipax API shipping rate payable by the sender for all sender methods: credit (`10`), wallet (`50`), and cash (`100`).
* Applied the configured free, fixed, or Tipax-calculated customer shipping charge consistently to all sender methods as a WooCommerce order charge independent from Tipax settlement.
* Preserved fixed route-based customer shipping charges in offline Checkout mode even when the Tipax pricing service times out.
* Corrected `cod` so sender cash no longer submits a shipping remainder; `cod` contains the WooCommerce order total only when the Tipax COD gateway is selected.
* Added backward-compatible normalization of the former `cash_postpay` setting to `cash`.

= 3.8.2 - 2026-08-22 =
* Made Tipax COD exclusive when COD is enabled and Tipax shipping is selected, force-removing online gateways to prevent customers from paying both online and again on delivery.
* Synchronized the Tipax COD gateway availability with the plugin's COD setting.

= 3.8.1 - 2026-08-22 =
* Renamed the sender-side `paymentType=100` method from cash/post-pay to cash across the settings and Checkout labels; post-pay terminology is now reserved for the receiver side.

= 3.8.0 - 2026-08-22 =
* Rebuilt settlement settings around the documented sender-paid and receiver-paid flows.
* Added exact Tipax payment type mappings: receiver post-pay `20`, sender credit `10`, sender wallet `50`, and sender cash `100`.
* Added optional post-pay quote display, free/Tipax/fixed receiver amounts for credit and wallet settlement, per-route fixed amounts, and sender contribution calculation for cash settlement.
* Added the `cod` field to every registration package and proportional COD distribution across multi-package orders.
* Added settlement snapshots to Tipax shipping rates and orders so offline and manual retry registration retain the Checkout payment model.
* Added fresh pricing during offline sender-cash retry to calculate and submit the real receiver shipping remainder before registration.
* Made Tipax COD optional and non-exclusive, retained other WooCommerce payment gateways, and stopped COD orders from being marked as paid before delivery.
* Added backward-compatible migration behavior for all previous `checkout_method` values.

= 3.7.0 - 2026-08-22 =
* Made Tipax destination city resolution independent from numeric city IDs supplied by other shipping plugins.
* Added Checkout capture of the selected city's visible title and resolved it exclusively against the local Tipax city table before pricing or registration.
* Added normalized Persian city and province matching for spaces, zero-width spaces, Arabic/Persian letter variants, and names such as `شهر قدس`, `شهرقدس`, and `قدس`.
* Added province validation before accepting a numeric Tipax city ID, preventing colliding external IDs from silently selecting a different Tipax city.
* Stored the resolved Tipax destination city ID and title in dedicated order metadata for online, offline, and manual retry registration paths.

= 3.6.5 - 2026-08-09 =
* Fixed manual Tipax retry validation so the parcel's real dimensions are checked instead of comparing the selected packing's physical dimensions with its own content limits.
* Added rotation-aware packing dimension checks, allowing valid parcels such as 10x10x5 cm to fit packing limits of 40x30x5 cm regardless of dimension order.
* Preserved the order's real dimensions while switching packing types in the manual retry form.

= 3.6.4 - 2026-08-03 =
* Registered city refresh and city toggle AJAX actions during the plugin's core initialization so they are always available in independent `admin-ajax.php` requests.
* Removed page-level duplicate city AJAX registration and added explicit module-loading errors when the city admin handler is unavailable.
* Added actionable city-toggle errors for WordPress's unregistered-action `0` response, expired nonces, malformed JSON, and server failures.

= 3.6.3 - 2026-08-03 =
* Replaced the mixed Persian, Latin, and numeric tracking-form placeholder with a clear Persian-only instruction.

= 3.6.2 - 2026-08-03 =
* Fixed invalid admin AJAX responses caused by logging an array-valued GET request body and reading object properties from array API responses.
* Changed empty Tipax GET requests to omit the request body and unnecessary content type while retaining the bearer authorization header.
* Optimized city synchronization by loading existing cities once, skipping unchanged records, updating only changed rows, and bulk-inserting new cities in batches.
* Raised admin synchronization resource limits where hosting permits and extended the city synchronization lock for long-running requests.
* Added actionable city-sync errors for expired nonces, malformed JSON responses, and HTTP server failures.

= 3.6.1 - 2026-08-03 =
* Made the plugin admin tabs smaller and more compact so all enabled tabs, including the last Logs tab, remain inside the page.
* Replaced the hidden horizontal tab overflow with a responsive grid: one compact row on wide screens, two five-column rows on medium screens, and two columns on mobile.

= 3.6.0 - 2026-08-03 =
* Added automatic public tracking input detection: numeric-only values use `Tracking/ByContractCode/{contractCode}`, while alphanumeric values continue to use `Tracking/ByTrackingCode`.
* Added secure normalization for Latin, Persian, and Arabic contract-code digits before building the Tipax contract endpoint.
* Added support for displaying every shipment returned for a contract code as a separate tracking card with a contract summary and parcel count.
* Added extraction of nested sender and receiver names and contract-response origin and destination cities.
* Updated the shortcode, WooCommerce My Account form, frontend messages, and Settings shortcode guide to explain tracking by either code type.

= 3.5.1 - 2026-08-02 =
* Accepted Tipax city records whose `stateId` is null, preserving their province title and storing a safe internal zero value instead of discarding the city.
* Changed the province filter to use province titles, allowing cities without a province ID to remain fully searchable and manageable.
* Grouped city rows by province with province-wide city counts and individual or global expand/collapse controls.
* Changed the province summary to count distinct province titles and displayed missing province IDs as a dash.

= 3.5.0 - 2026-08-02 =
* Added a Cities menu and admin tab showing the local `tipax_cities` database table used by Checkout and Tipax city-ID resolution.
* Added searchable and paginated city management with province and enabled-status filters, summary counters, and last-sync information.
* Added authenticated manual synchronization through `Cities/plusstate`, safely updating existing rows and inserting new cities without deleting local records or per-city settings.
* Added secure per-city enable switches. Disabled cities remain selectable in WooCommerce Checkout, but the Tipax shipping method and pricing request are skipped for those destinations.
* Preserved disabled-city settings during API synchronization and removed their options and sync metadata during plugin uninstall.
* Corrected the Tipax Checkout city AJAX response to return sorted, escaped option elements without invalid nested select markup.

= 3.4.1 - 2026-08-02 =
* Changed logging to fail closed: the log file is written only when `logging_enabled` is explicitly saved as `yes`.
* Prevented missing, malformed, or unavailable settings from enabling logging by default.
* Kept log directory creation, file rotation, appending, and clearing behind the logging-enabled guard.

= 3.4.0 - 2026-08-02 =
* Changed the Tipax offline option into an explicit Checkout registration mode: enabled always stores Checkout shipments for later manual registration, while disabled attempts immediate online registration.
* Added configurable online Checkout registration timeout behavior that stores non-responsive Tipax requests as offline orders ready for manual registration.
* Kept Tipax Orders admin registration, retry, and pricing-preview actions online with their independent 45-second timeout, regardless of the Checkout mode.
* Updated offline order labels and actions so pending shipments clearly expose a direct `ثبت در تیپاکس` action.
* Prevented revisiting the WooCommerce thank-you page from automatically retrying an order already queued for manual Tipax registration.
* Kept the Tipax shipping method available as an offline fallback when the Checkout pricing request encounters a transport timeout.
* Rewrote the Settings guidance and only displays the online timeout field while online Checkout registration is selected.

= 3.3.3 - 2026-08-02 =
* Treated Tipax status ID `34` as already cancelled across order actions, server-side cancellation checks, and label printing.
* Prevented repeated cancellation requests and hid the cancellation action for shipments already reported as cancelled by Tipax.
* Fixed HTTP 411 responses from the cancellation endpoint by always sending an explicit PUT body and `Content-Length` header.

= 3.3.2 - 2026-08-02 =
* Renamed the tracking report label from "rejected/skipped" to "finished" so terminal shipment statuses are not confused with rejected orders or errors.
* Updated batch messages and order-row badges to consistently describe these shipments as having finished tracking updates.

= 3.3.1 - 2026-08-02 =
* Raised the effective WordPress minimum from 5.2 to 5.8 to match the supported WooCommerce baseline and the WordPress APIs used by the plugin.
* Added explicit WordPress, WooCommerce, PHP, and required-plugin metadata to the main plugin header and readme.
* Declared WooCommerce 7.1 as the minimum supported version and WooCommerce 10.9 as tested up to.
* Verified plugin activation and core service loading on WordPress 5.8 / WooCommerce 7.1.1 / PHP 7.4.
* Verified plugin activation and core service loading on WordPress 7.0.2 / WooCommerce 10.9.4 with PHP 7.4 and PHP 8.5.
* Documented the required PHP `mbstring` extension, HPOS support, WooCommerce dependency, and outbound Tipax API access.
* Declared HPOS compatibility through WooCommerce FeaturesUtil and converted the remaining tracking snapshot and checkout phone writes to WooCommerce CRUD metadata APIs.

= 3.3.0 - 2026-08-02 =
* Added a global label-layout selector to the plugin Settings screen with the existing full layout as the default.
* Added sender-free and sender-phone-free 10x16 cm layouts based on the supplied label designs.
* Added a privacy-focused barcode-only layout with an enlarged centered Code 128 barcode and tracking code.
* Applied the selected layout consistently to single, bulk, Tipax Orders, and WooCommerce order label printing.
* Added strict layout-value validation so invalid or legacy settings safely fall back to the full label.

= 3.2.9 - 2026-08-02 =
* Added extraction and HPOS-compatible storage of the Tipax `statusId` returned by tracking requests.
* Restricted subsequent manual, batch, and scheduled tracking updates to status IDs `6`, `59`, `88`, `119`, `129`, and `309`.
* Allowed legacy orders without a stored status ID to be checked once, then excluded terminal statuses from future API requests.
* Added separate terminal-status reporting and replaced update actions for finished statuses with a clear tracking-finished indicator.

= 3.2.8 - 2026-08-02 =
* Added authenticated PUT requests for the Tipax `Parcels/Cancel/{trackingCode}` endpoint.
* Added a protected cancellation action for registered Tipax orders with a confirmation step in the Orders table.
* Added multi-parcel cancellation with per-tracking-code success, failure, and partial-result handling.
* Stored cancelled tracking codes, cancellation timestamps, latest results, and WooCommerce order notes using HPOS-compatible order metadata.
* Prevented cancelled parcels from being tracked automatically, printed again, or cancelled repeatedly while preserving their tracking-code history.

= 3.2.7 - 2026-08-02 =
* Replaced free-form numeric ticket department and priority fields with restricted select controls.
* Added the supported E-Tipax technical, sales, financial, and WordPress plugin departments to the ticket form.
* Added important, normal, and low priority options with normal priority and WordPress plugin support selected by default.
* Added server-side department and priority validation so unsupported or tampered IDs are rejected before calling the Tipax API.

= 3.2.6 - 2026-08-02 =
* Fixed non-Tipax orders leaking into the plugin Orders page because `shipping_method` is not a supported `WC_Order_Query` parameter.
* Added exact HPOS and legacy shipping-method queries with correct status, date-range, total-count, and pagination handling.
* Added a separate "non-Tipax orders" view that preserves date filters and pagination while keeping Tipax registration, tracking, and label actions disabled.
* Added the shipping method column and HPOS-compatible order metadata handling to the plugin Orders table.
* Restricted scheduled tracking candidates to verified Tipax orders and made tracking-attempt metadata HPOS-compatible.

= 3.2.5 - 2026-07-29 =
* Finalized single and checkbox-based bulk printing of 10x16 cm Tipax labels in the plugin Orders page.
* Added Tipax-only label printing to HPOS and legacy WooCommerce order lists and single-order screens.
* Added sharp server-generated Code 128 barcode previews and print output for every shipment tracking code.
* Updated sender details to prefer the selected Tipax origin address and postal code, with WooCommerce store details as a fallback.
* Fixed nonce-protected bulk-print redirects so all selected order IDs reach the print endpoint correctly.

= 3.2.4 - 2026-07-29 =
* Added a redesigned 10x16 cm Tipax shipping label based on the provided print layout, with sender, receiver, postal code, order, package, and tracking details.
* Added single and checkbox-based bulk label printing to the Tipax Orders page.
* Added Tipax label columns and bulk print actions to both HPOS and legacy WooCommerce Orders screens.
* Replaced the previous partial barcode metabox with a Tipax-only order metabox containing barcode previews and print/preview actions.
* Switched label barcodes to server-generated Code 128 SVG output for sharp printing without external JavaScript dependencies.

= 3.2.3 - 2026-07-29 =
* Added start/end date filters to the Tipax Orders page so orders older than the default 10-day window can be viewed.
* Added quick views for the latest 10 days and all order dates, with the selected range preserved across pagination.
* Added responsive styling and active-range context for the Orders date filter.

= 3.2.2 - 2026-07-29 =
* Normalized Tipax tracking codes to lowercase across order registration, storage, display, shortcode input, My Account tracking, and API requests.
* Added a one-time migration that converts previously stored Tipax tracking-code metadata to lowercase.
* Matched `Tracking/ByTrackingCode` requests to the Tipax API format by using `application/json` and logging the actual JSON payload without an extra encoding layer.

= 3.2.1 - 2026-07-27 =
* Fixed frontend/bootstrap loading so tracking hooks are registered reliably after `plugins_loaded`, including multisite/network-active WooCommerce setups.
* Fixed Elementor/Text Editor shortcode rendering so `[tipax_tracking]` is converted into the full tracking widget instead of remaining raw text.
* Hardened My Account tracking endpoint/query-var registration and added a dashboard shortcut fallback for the tracking section.
* Fixed financial report amount rendering in RTL layouts so negative values and amount/currency ordering display correctly.

= 3.2.0 - 2026-07-27 =
* Added automated Tipax tracking synchronization with daily scheduled background updates and batch status refresh tools in the admin Orders page.
* Added public shipment tracking with a shortcode and a dedicated WooCommerce My Account tracking endpoint/page for customers.
* Added a financial reporting screen for reviewing up to the latest 100 Tipax financial transactions.
* Added a support/ticketing screen for creating tickets, listing submitted tickets, and viewing ticket details from the Tipax API.
* Added a settings toggle to fully disable Tipax log recording and hide the Logs menu/tab when logging is turned off.
* Fixed multiple Persian admin text rendering/encoding issues in Orders, Support, and Financial screens.
* Kept the Logs menu/tab pinned to the end of the Tipax admin navigation when logging is enabled.

= 3.1.1 - 2026-06-30 =
* Added a modal-based manual Tipax retry flow in the Orders tab so admins can review and update the origin address, weight, packing type, and manual dimensions before trying registration again.
* Added Tipax price preview support inside the manual retry modal and improved success/error result rendering for both pricing preview and final order registration.
* Fixed admin modal behavior so Tipax address and order modals open above the page correctly, support long-form scrolling, and keep labels and copy readable in the custom dark theme.
* Improved modal form input UX by forcing LTR/left-aligned numeric fields, removing default browser number spinners, and showing the address default option as a switch.
* Clarified required fields in the Tipax address-create modal and explicitly marked `unit` and `floor` as required in the form UI.
* Updated Tipax order registration calls to use the `Orders/PluginOrder` endpoint so plugin-created orders are separated from other Tipax order sources without changing the payload structure.

= 3.1.0 - 2026-06-30 =
* Styled Tipax admin dropdowns on WooCommerce shipping settings with a forced light theme so select menus remain white and readable even inside dark admin contexts.
* Removed the collection-method selector from the settings UI and now always keep the existing same-day collection value in the API payload.
* Improved checkout pricing refresh so Tipax pricing can be recalculated as soon as the customer selects or enters the destination city.
* Added Tipax address creation inside the Addresses tab with a modal-based form and API-backed address registration flow.
* Simplified the create-address UI by removing district, latitude, and longitude fields from the modal form.
* Matched the address form validation to the current integration requirements and enforced `unit` and `floor` entry alongside the required address fields used by the merchant flow.
* Improved Tipax address response handling so newly created address IDs are stored correctly and remain usable as the site's origin address.
* Added local origin-address selection in the Addresses tab, allowing the merchant to choose any synced Tipax address as the website origin instead of relying only on the Tipax default address.
* Updated pricing and order requests to use the locally selected origin address ID when calling Tipax APIs.
* Added configurable Tipax request timeout settings for pricing and order registration, with an optional offline fallback mode managed from the plugin settings tab.
* Added offline pricing fallback so the Tipax method can stay available as `تیپاکس (پس کرایه)` when Tipax pricing times out and offline mode is enabled.
* Added offline/pending order registration states so orders that could not be registered with Tipax within the timeout can be reviewed and retried later from the admin orders list.
* Changed manual admin retry flows to use a longer request timeout than storefront requests so delayed Tipax responses can still be processed by site admins.
* Fixed the add-address modal scrolling behavior so long forms remain accessible after the modal opens.
* Removed the unnecessary customer-facing checkout notice that was previously shown for offline pricing fallback orders.

= 3.0.0 - 2026-06-12 =
* Rebuilt the plugin architecture into a modular service-based structure.
* Added dedicated modules for authentication/token management, API communication, pricing, package building, order/shipment creation, address management, tracking, admin pages, and reporting/logs.
* Centralized procedural helper functions in includes/tipax-functions.php.
* Refactored shipping-rate calculation and package preparation into pricing services while preserving the WooCommerce shipping method behavior.
* Refactored shipment/order registration logic into the order service and payload builder.
* Removed duplicate and legacy unreachable code paths after modularization.
* Removed the WooCommerce shipping-zone Tipax settings form/edit flow because Tipax settings are now managed from the dedicated Tipax admin menu.
* Added a Tipax plugin settings button to the WooCommerce shipping-zone method edit modal.
* Redesigned the Tipax admin menu and tab pages with a dedicated dark, responsive admin UI.
* Improved Tipax admin forms, notices, tables, logs, diagnostics, addresses, orders, and wallet UI.
* Added compatibility wrappers/aliases for legacy class names used by older code paths.
* Fixed early loading of the Tipax shipping method before WooCommerce loads WC_Shipping_Method.
* Fixed Tipax log file path and made log directory creation resilient.
* Kept existing checkout, COD, pricing, address, barcode, tracking, diagnostics, wallet, and logging features compatible with the previous behavior.

= 2.4.0 - 2026-02-24 =
PickupType added to settings
PackType integrated with Tipax Api
= 2.3.1 - 2026-01-24 =
Some minor bugs fixed
= 2.3.0 - 2025-11-24 =
Fix duplicated phone Fields in checkout page
Fix Sistan-va-Balopuchestan state problem in checkout
= 2.2.9 - 2025-10-18 =
Tipax Shipping Menu Translation
Some minor bugs fixed
= 2.2.8 - 2025-10-14 =
Fix multiple product pricing and order
Add print label
= 2.2.7 - 2025-10-6 =
Fix ServiceId Issue on Pricing
Add Updater to Plugin
= 2.2.6 - 2025-09-25 =
Solve Digits Plugin confilict
= 2.2.5 - 2025-09-13 =
Optimize shipping cost
Remove postal code validation
Optimize orders page in settings
= 2.2.0 - 2025-07-13 =
Envlope added
Some minor bugs fixed
= 2.0.0 - 2025-07-13 =
Plugin setup changed
COD added as shipping method
Some minor bugs fixed
= 1.2 - 2023-12-04 =
Wallet Recharge
Get wallet remaining assets
Print order item barcodes
Ship to another address bug fix
= 1.1.1 - 2023-12-04 =
Submit Origin Address from plugin
Some minor bugs fixed
= 1.1.0 - 2023-12-01 =
Plugin Slug Renamed
Some minor and major bugs fixed
= 1.0.0 - 2023-10-01 =
* Official Plugin Release
