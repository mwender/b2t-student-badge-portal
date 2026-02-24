# B2T Student Badge Portal — Research Notes

**Date:** 2026-02-24
**Scope:** `localdev-b2t-student-badge-portal`

**High-level purpose**
This is a WordPress plugin that extends WooCommerce “My Account” with two custom tabs (“Certification Program” and “Classes, Exams, and Downloads”), fetches student data from Zoho CRM, renders badge/certificate progress, and issues Open Badges 2.0 assertions through custom REST endpoints. It also exposes a public badge CPT (“Badge”) with a custom front-end template and manages badge issuance storage in a custom DB table.

**Primary entrypoint**
- `b2t-student-badge-portal.php`
  - Defines plugin constants and starts a PHP session.
  - Autoloads all files in `lib/fns/*.php` on `after_setup_theme` (TypeRocket is bootstrapped first).
  - Registers activation hook to create the badge assertions table via `dbDelta()`.

**Directory layout**
- `lib/fns`
  - Core runtime logic split by concern (WooCommerce, REST, Zoho, CPT, assets).
- `lib/js`
  - Front-end AJAX + Handlebars rendering for account tabs and Elementor popup.
- `lib/hbs`
  - Handlebars templates injected into footer.
- `lib/less + /lib/css`
  - LESS sources and compiled CSS.
- `templates`
  - Badge CPT template.
- `lib/typerocket`
  - Bundled TypeRocket framework (admin forms for CPT fields).
- `lib/badges`
  - Static, legacy-style Open Badges example artifacts.

**Runtime flow (My Account)**
- `lib/fns/endpoints.php`
  - Registers two WooCommerce endpoints (`classes`, `certification`) using `add_rewrite_endpoint()`.
- `lib/fns/woocommerce.php`
  - Inserts two new tabs into My Account menu (only if locale is `en_US`).
  - Reorders the menu to place the new tabs right after Dashboard.
  - Adds a mobile “Show Menu” toggle and custom login error messaging.
- `lib/fns/content.php`
  - Renders the markup for the two tab pages.
  - Pulls tab content from ACF options fields.
- `lib/fns/enqueues.php`
  - Loads Handlebars and page-specific JS.
  - Localizes endpoints, student id/email, and REST nonce.
  - Injects Handlebars templates into the footer.
- `lib/js/badges-tab.js`
  - Fetches badges/certificates from Zoho via the plugin REST endpoint.
  - Renders badges/certificates using Handlebars.
  - “Add to Backpack” issues an OB2 assertion and copies the canonical assertion URL.
- `lib/js/classes-tab.js`
  - Fetches classes and exams, merges them into a single list, and supports client-side sorting.
  - Uses a Handlebars row template.
- `lib/js/student-resources-popup.js`
  - Opens an Elementor popup and fetches resource page content via a custom REST endpoint.

**REST API surface (Open Badges + Zoho + resources)**
- `lib/fns/endpoints.php` (namespace `BadgePortal\fns\endpoint`)
  - `GET /wp-json/b2tbadges/v1/assertions`
    - Builds a **non-persistent** OB2 assertion for email/badge/completed. 
    - Uses a deterministic SHA256 token based on email, badge slug, date, and `wp_salt('auth')`.
  - `GET /wp-json/b2tbadges/v1/badge-class`
    - Returns a BadgeClass for a `badge` CPT.
  - `GET /wp-json/b2tbadges/v1/criteria`
    - Returns HTML criteria + image. Can return grayscale image for incomplete badges.
  - `GET /wp-json/b2tbadges/v1/issuer`
    - Returns Issuer profile with site data.
  - `GET /wp-json/b2tbadges/v1/assertions/{assertion_id}`
    - Looks up a stored, canonical assertion by hash in the custom table.
  - `POST /wp-json/b2tbadges/v1/issue-assertion`
    - Creates or returns a stored assertion and persists it to the table.
- `lib/fns/zoho.php` (namespace `B2TBadges\fns\zoho`)
  - `GET /wp-json/b2tbadges/v1/zh/{action}`
    - Actions: `getStudentId`, `getStudentBadges`, `getStudentClasses`, `getStudentExams`.
    - Uses Zoho CRM Function endpoints and caches results with transients.
- `lib/fns/student-resources.rest-api.php`
  - `GET /wp-json/studentresources/v1/post/{id}`
    - Returns a resource page’s title/content and a download table built from ACF repeater rows.
- `lib/fns/cors.php`
  - Adds permissive CORS headers for all `b2tbadges/v1` routes.
  - Registers `OPTIONS` for preflight across the namespace.

**Data storage**
- Custom table: `{$wpdb->prefix}b2t_badge_assertions`
  - Created on activation.
  - Columns: `assertion_id`, `recipient_hash`, `badge_slug`, `issued_on`, `assertion_json`, `created_at`, `revoked_at`.
- User meta:
  - `zh_student_id` (cached result of Zoho `getStudentId`).
- Transients:
  - `student-classes_{student_id}`
  - `student-exams_{student_id}`
  - `student-badges_{student_id}`

**Badge CPT (admin + public)**
- `lib/fns/badge_cpt.php`
  - Registers the `badge` CPT via TypeRocket.
  - `public` false but `publicly_queryable` true (accessible by URL, hidden from listings/search).
  - Uses TypeRocket fields for badge image (color/grayscale) and “Criteria” (post_content).
  - Overrides template to `/templates/badge.php` and hides admin bar on badge pages.
- `/templates/badge.php`
  - Minimal template that renders badge image, content, and a footer notice.

**Front-end rendering specifics**
- Handlebars templates injected in footer for:
  - Badges
  - Certificates
  - Class/Exam rows
- `badges-tab.js` specifics:
  - Uses `criteria` endpoint to fetch criteria HTML and badge image.
  - Applies special logic to avoid double-listing “Legacy BA Certified”.
  - For completed badges, `Add to Backpack` issues an OB2 assertion and copies the canonical assertion URL.
- `classes-tab.js` specifics:
  - Merges classes and exams into a single list with a consistent date sort.
  - Adds per-column sort indicators.
- `student-resources-popup.js` specifics:
  - Depends on Elementor Pro popup module.
  - Uses `wpApiSettings.root` when available; falls back to `window.location.origin`.

**Styling + build**
- LESS sources in `/lib/less` compiled to `/lib/css/main.css` via Grunt.
- `Gruntfile.js` defines:
  - `grunt build` (minified)
  - `grunt builddev` (sourcemap + unminified)
  - `grunt watch` (live rebuild on LESS/JS/HBS changes)
- CSS includes tab styling, grid, checkmark icons (embedded SVG), and badge CPT layout.
- `lib/fns/inlinestyles.php` injects full normalize/milligram CSS for badge CPT pages and loads Roboto from Google Fonts.
- `lib/fns/enqueues.php` aggressively dequeues all non-admin styles/scripts on badge pages and re-enqueues only the plugin CSS.

**Dependencies and assumptions baked into the plugin**
- WooCommerce (My Account endpoints and menu).
- ACF (options fields + repeater field `resources`).
- Elementor Pro (popup modal for resource downloads).
- TypeRocket (CPT and metabox UI).
- jQuery + jQuery UI tabs (bundled with WP).
- Handlebars + Handlebars-Intl (bundled in `/lib/js`).
- A download helper shortcode `[download_data]` (likely from a download manager plugin).
- Custom function `uber_log()` is called in several places but not defined in this plugin.

**Open Badges 2.0 issuance mechanics**
- Deterministic assertion IDs are hashed from email, badge slug, date, and `wp_salt('auth')`.
- `issue-assertion` stores a JSON snapshot of the assertion for future retrieval by ID.
- `assertions/{assertion_id}` returns the stored assertion only if not revoked.
- Public CORS headers for `b2tbadges/v1` routes allow use by external badge wallets.

**Config required (wp-config.php)**
- `B2T_BADGE_PORTAL_ZOHO_EP`
- `B2T_BADGE_PORTAL_ZOHO_API_KEY`
- `IS_LOCAL` (optional override; used for transient TTL behavior)

**Legacy/ancillary artifacts**
- `/lib/badges` contains static, older Open Badges artifacts (likely pre-OB2 flow).
- `/lib/classes/plugin-updater.php` provides GitHub release-based updates via the WordPress updater UI.
- `/lib/typerocket` expects `vendor/autoload.php` inside the framework folder; in this repo snapshot that vendor directory is not present.

**Notable specificities and quirks**
- Locale gating: the custom WooCommerce tabs are only inserted for `en_US`.
- `classes-tab.js` and `badges-tab.js` use X-WP-Nonce headers for GET requests.
- `badges-tab.js` sends `security: wpvars.security` to `issue-assertion`, but the localized data only defines `nonce`.
- `load_badge_template()` and `enqueue_badge_scripts()` rely on `$wp_query->query_vars['post_type']` (can be undefined early in some contexts).
- `uber_log()` calls imply an external logger is required or logging is currently a no-op/undefined.
- `student-resources` REST route is public (no permission callback), which may be intentional but is a privacy consideration.
- `issue-assertion` is public (permission callback returns true) to allow client-side issuance.

**Files reviewed in detail**
- `b2t-student-badge-portal.php`
- `lib/fns/*.php`
- `lib/js/*.js`
- `lib/hbs/handlebars-templates.hbs`
- `lib/less/*.less`
- `templates/badge.php`
- `lib/classes/plugin-updater.php`
- `README.md`
- `Gruntfile.js`
- `package.json`
- `lib/badges/*`
