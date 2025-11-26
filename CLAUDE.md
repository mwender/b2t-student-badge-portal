# CLAUDE.md - B2T Student Badge Portal

This file helps AI assistants understand and work with this WordPress plugin codebase.

## Plugin Overview

**Name**: B2T Student Badge Tabs
**Purpose**: Adds certification badge tracking and class/exam management to WooCommerce My Account section
**Version**: 1.5.0
**Author**: Michael Wender
**Requirements**: WordPress 6.1.0+, WooCommerce, ACF, Elementor

This plugin integrates with Zoho CRM to display student certifications, badges, and course enrollments. It implements the Open Badges v1.0 specification for digital credential verification.

## Architecture

### Directory Structure

```
b2t-student-badge-portal.php    # Main plugin file
/lib
  ├── /fns/                      # Namespaced function files (core logic)
  ├── /typerocket/               # TypeRocket framework for admin UI
  ├── /js/                       # Frontend JavaScript (jQuery-based)
  ├── /less/                     # LESS source files
  ├── /css/                      # Compiled CSS (minified)
  ├── /hbs/                      # Handlebars templates
  ├── /img/                      # Badge images
  ├── /badges/                   # Open Badges spec files
  └── /classes/                  # Additional classes
/templates/                       # Public-facing page templates
/vendor/                          # Composer dependencies (TypeRocket)
```

### Key Function Files

All function files use PHP namespaces and are located in `/lib/fns/`:

- **badge_cpt.php** - Badge custom post type registration (TypeRocket)
- **zoho.php** - Zoho CRM API integration (current)
- **salesforce.php** - Legacy Salesforce integration (deprecated v1.2.0)
- **endpoints.php** - Open Badges REST API implementation
- **student-resources.rest-api.php** - Resource page content API
- **woocommerce.php** - WooCommerce tab injection and customization
- **enqueues.php** - Asset loading and localization
- **content.php** - Content rendering functions
- **utilities.php** - Helper functions (file size formatting)

## Core Components

### 1. Badge Custom Post Type

**File**: `/lib/fns/badge_cpt.php`

Badges are stored as a custom post type with these characteristics:
- **Not publicly queryable** (public: false)
- **Meta fields** (via TypeRocket):
  - `Badge Image` - Color version (completed state)
  - `Badge Image Grayscale` - Grayscale version (incomplete state)
  - `Description` - Badge metadata
  - Post content = Criteria (HTML)
- **Custom template**: `/templates/badge.php` (minimal styling, removes admin bar)

### 2. WooCommerce Integration

**File**: `/lib/fns/woocommerce.php`
**Namespace**: `B2TBadges\fns\woocommerce`

Injects two custom tabs into WooCommerce My Account navigation:
1. **"Certification Program"** - Displays badges and certificates
2. **"Classes, Exams, and Downloads"** - Displays sortable table of enrollments

**Important**: Tabs only show for English (en_US) locale.

**Key Hooks**:
- `woocommerce_account_menu_items` - Inject tabs, reorder menu
- `woocommerce_before_account_navigation` - Mobile menu toggle
- `login_errors` - Add helpful message for new users

### 3. Zoho CRM Integration

**File**: `/lib/fns/zoho.php`
**Namespace**: `B2TBadges\fns\zoho`

Replaces legacy Salesforce integration (as of v1.2.0). Provides three main functions:

1. **`get_student_id($args)`** - Look up student ID by email
   - Input: email address
   - Output: Student ID object or WP_Error
   - Validates email, checks for single match

2. **`get_student_data($args)`** - Fetch classes or exams
   - Input: student_id, data_type ('classes' | 'exams')
   - Output: Array of enrollment records
   - **24-hour caching** via transients (5 min in local dev)
   - Enhances records with resource page links/download counts

3. **`get_student_badges($args)`** - Fetch certifications
   - Input: student_id
   - Output: Badge completion data with dates
   - **24-hour caching** via transients

**Configuration** (wp-config.php):
```php
define('B2T_BADGE_PORTAL_ZOHO_EP', 'https://...');
define('B2T_BADGE_PORTAL_ZOHO_API_KEY', '...');
define('IS_LOCAL', false); // true for dev mode
```

**REST Endpoints**: `GET /wp-json/b2tbadges/v1/zh/{action}`
- Actions: `getStudentId`, `getStudentBadges`, `getStudentClasses`, `getStudentExams`
- Requires nonce in production, open in local dev
- Returns JSON data for frontend rendering

### 4. Open Badges API

**File**: `/lib/fns/endpoints.php`
**Namespace**: `BadgePortal\fns\endpoint`

Implements Open Badges v1.0 specification with four REST routes:

1. **`GET /assertions`** - Badge assertion for specific user/badge
   - Params: email, badge (slug), completed (YYYY-MM-DD)

2. **`GET /badge-class`** - Badge class definition
   - Params: name (badge slug)

3. **`GET /criteria`** - Badge criteria HTML + image
   - Params: name (badge slug), completed (optional)
   - Returns grayscale image if not completed

4. **`GET /issuer`** - Issuer definition (site info)

### 5. Student Resources API

**File**: `/lib/fns/student-resources.rest-api.php`
**Namespace**: `B2TBadges\restapi`

**Route**: `GET /wp-json/studentresources/v1/post/{id}`

Fetches content from "resource-page" custom post type (class materials, downloads).
- Uses ACF repeater field "resources" for download list
- Displays via Download Manager (DLM) shortcode
- No authentication required

## Frontend JavaScript

All scripts use jQuery and are located in `/lib/js/`:

### badges-tab.js
**Purpose**: Renders certification badges and certificates

**Functionality**:
- AJAX fetch from `/wp-json/b2tbadges/v1/zh/getStudentBadges`
- Handlebars template rendering
- Integrates OpenBadges.org "Add to Backpack" functionality
- Date conversion and formatting
- Filters certificates to avoid duplicates (Legacy BA Certified → BA Certified)

**Dependencies**: jQuery, Handlebars, Handlebars-Intl, OpenBadges issuer API

### classes-tab.js
**Purpose**: Displays sortable classes/exams table

**Functionality**:
- Loads class and exam data via AJAX
- Client-side sorting (by name or date)
- Toggle ascending/descending (▲/▼ indicators)
- Handlebars table row rendering
- Date conversion from Zoho/Salesforce format

### student-resources-popup.js
**Purpose**: Modal popup for class materials

**Functionality**:
- Intercepts `.resource-page-link` clicks
- Fetches content via REST API
- Injects into Elementor popup modal
- Loading state and error handling

**Configuration**: Elementor popup ID stored in ACF option field

## Patterns & Conventions

### Namespacing

All function files use namespaces:
- `B2TBadges\fns\zoho\`
- `B2TBadges\fns\woocommerce\`
- `B2TBadges\fns\enqueues\`
- `BadgePortal\fns\endpoint\`
- `B2TBadges\restapi\`
- `B2TBadges\utilities\`

### Asset Loading

**File**: `/lib/fns/enqueues.php`

- Conditional loading based on WooCommerce endpoint
- `filemtime()` cache busting on CSS/JS files
- `wp_localize_script()` passes:
  - JSON API URLs
  - Student ID/email (current user)
  - Nonce for AJAX security
  - Elementor popup ID

### Handlebars Templates

**File**: `/lib/hbs/handlebars-templates.hbs`

Templates dynamically loaded in footer on account pages:
- **Badge template** - Individual badge with completion info
- **Certificate template** - Certificate with progress checklist
- **Table row template** - Class/exam record in table

Compiled templates passed via inline script.

### Styling

**Framework**: Custom LESS → compiled CSS via Grunt

**Structure**:
```
main.less
  ├── _variables.less     # Colors, spacing
  ├── _alerts.less        # Alert components
  ├── _grid.less          # Custom grid system
  ├── _tabs.less          # Tab UI
  ├── _layout.less        # WooCommerce layout
  ├── _tables.less        # Table styling
  └── _badge_cpt.less     # Badge page styles
```

**Build Process**:
- `grunt watch` - Development with live reload
- `grunt build` - Production minification
- Source maps generated for development

### Internationalization

**Text Domain**: `b2t-student-badge-tabs`
**Domain Path**: `/languages`

**Limited Implementation**: Only English (en_US) receives custom tabs. Tab text wrapped with `__()` for future translation support.

## Data Models

### Badge Record (from Zoho)
```json
{
  "name": "Agile Analysis",
  "badge_requirement_text": "Attend 3 of 4 classes:",
  "demonstration_text": "...",
  "completed": true,
  "completed_date": "2017-11-15",
  "criteria": [
    {
      "name": "Class Name",
      "type": "Class|Exam",
      "completed": true,
      "completed_date": "2017-06-30"
    }
  ]
}
```

### Class Record (from Zoho)
```json
{
  "Class__r": {
    "Name": "Class Name",
    "End_Date__c": "2017-09-15"
  },
  "resource_page": {
    "id": 123,
    "link_text": "3 Downloads"
  }
}
```

### Resource Page (WordPress)
- **Post Type**: `resource-page`
- **ACF Field**: `resources` (repeater with download links)
- Linked to Zoho class by name matching

## Caching Strategy

**Transients** (24-hour cache, 5 min in local dev):
- Student classes: `student-classes_{student_id}`
- Student exams: `student-exams_{student_id}`
- Student badges: `student-badges_{student_id}`

**User Meta**:
- Student ID cached as `zh_student_id` after first lookup

**Cache Invalidation**: None implemented - relies on time-based expiration

## Security

### Nonce Protection
- WordPress REST nonce (`wp_rest`) required for Zoho API calls
- Passed in `X-WP-Nonce` header
- Local dev mode (`IS_LOCAL`) allows nonce bypass

### Capability Checks
- Zoho/Salesforce endpoints check for `read` capability
- Badge template removes admin bar for public viewing

### Input Validation
- Email: `is_email()`
- Badge slugs: `sanitize_title()`
- Dates: Regex validation (YYYY-MM-DD)
- Post IDs: `\d+` pattern

### Data Encoding
- Query params: `add_query_arg()` (URL encoding)
- JSON: Decoded from Zoho/Salesforce responses
- HTML: Escaped in templates

## Business Rules

### Critical Rules to Follow

1. **Localization**: Custom tabs ONLY show for English (en_US) locale
2. **Legacy Certificate Handling**: "Legacy BA Certified" displays as "BA Certified"
3. **Badge Images**: Use grayscale image when badge not completed
4. **Resource Links**: Only show "Student Resources" if resource page exists
5. **Tab Ordering**: Custom tabs positioned after Dashboard, before Orders
6. **Tab Title Formatting**: "Classes, Exams, and Downloads" is exception to `ucwords()` rule

### Data Workflow

1. User views My Account → Plugin adds custom tabs
2. User clicks "Certification Program" → JavaScript loads badges
3. JavaScript fetches student badges from Zoho via REST endpoint
4. Handlebars renders badges (color or grayscale based on completion)
5. "Add to Backpack" button appears for completed badges
6. User clicks button → OpenBadges.org API bakes badge to backpack

## Dependencies

### Required Plugins
- **WooCommerce** - Account page framework
- **Advanced Custom Fields (ACF)** - Content management, options
- **Download Manager (DLM)** - File display in resources
- **Elementor** - Popup modal functionality

### JavaScript Libraries
- jQuery (WordPress bundled)
- Handlebars v4.0.5
- Handlebars-Intl (date formatting)
- OpenBadges.org issuer API (CDN)

### PHP Framework
- **TypeRocket v3.0** - Custom post type builder
- Composer-managed in `/vendor/`

### Build Tools
- Grunt (task runner)
- Less compiler
- load-grunt-tasks

## Known Issues & Technical Debt

### Deprecated Code
- **Salesforce Integration** (`/lib/fns/salesforce.php`) - Kept for reference, not used
- Replaced by Zoho in v1.2.0, but file remains in codebase

### No Cache Invalidation
- Transients rely on time-based expiration only
- Updates in Zoho may not appear immediately (up to 24 hours)
- Consider adding manual cache clear or webhook-based invalidation

### Hardcoded Dependencies
- Requires specific Elementor popup ID (configured via ACF)
- Assumes Download Manager plugin for resource display
- Limited to WooCommerce account page structure

### JavaScript Pattern
- Uses jQuery (older pattern, but consistent with WordPress ecosystem)
- No modern build process (ES6 modules, bundling)
- Consider migration to modern JS if major refactor needed

## Development Workflow

### Local Development

1. **Enable Local Mode** (wp-config.php):
   ```php
   define('IS_LOCAL', true);
   ```
   This enables:
   - 5-minute cache instead of 24-hour
   - Nonce bypass for REST endpoints
   - Additional debug logging

2. **Build Process**:
   ```bash
   npm install          # Install dependencies
   grunt watch          # Development with live reload
   grunt build          # Production minification
   ```

3. **Testing**:
   - View My Account page in WooCommerce
   - Check both custom tabs render correctly
   - Test with real student account (has classes/badges)
   - Verify "Add to Backpack" functionality

### Making Changes

**Adding New Zoho Data**:
1. Create new function in `/lib/fns/zoho.php`
2. Follow namespace pattern: `B2TBadges\fns\zoho\`
3. Add transient caching (24-hour)
4. Register REST endpoint in same file
5. Update permission callback (check nonce)

**Adding New Frontend Feature**:
1. Create JS file in `/lib/js/`
2. Enqueue conditionally in `/lib/fns/enqueues.php`
3. Add Handlebars template to `/lib/hbs/handlebars-templates.hbs`
4. Localize script with needed data (API URLs, nonces)
5. Update LESS files if styling needed
6. Run `grunt build` before committing

**Modifying Badge Display**:
1. Edit Handlebars templates in `/lib/hbs/`
2. Update JavaScript in `/lib/js/badges-tab.js` if logic changes
3. Test with both completed and incomplete badges
4. Verify "Add to Backpack" still works

### Git Workflow

**Branch Naming**: Use `claude/` prefix (e.g., `claude/feature-name`)
**Commits**: Clear, descriptive messages following existing style
**Testing**: Always test on WooCommerce account page before pushing

## Helpful Context for AI Assistants

### When Modifying Code

- **Always read files before editing** - Don't propose changes to code you haven't seen
- **Maintain namespace consistency** - All functions use namespaces
- **Preserve caching** - Don't remove transient caching without good reason
- **Test with real data** - Plugin requires Zoho API connection to function
- **Check localization** - Remember en_US restriction for tabs

### Common Tasks

**Adding a new badge field from Zoho**:
1. Update Zoho API function to include new field
2. Update Handlebars template to display field
3. Clear transient cache for testing

**Changing tab content**:
- Edit ACF option fields (`my_account_certification_program_tab`, `my_account_class_exams_tab`)
- Content managed in WordPress admin, not in code

**Updating styles**:
- Edit LESS files in `/lib/less/`
- Run `grunt build` to compile
- Changes appear in `/lib/css/main.min.css`

**Debugging API issues**:
- Check `IS_LOCAL` constant
- Look for `uber_log()` calls in Zoho functions
- Verify nonce in browser console
- Check transient expiration

### File Naming Patterns

- Function files: `kebab-case.php` (e.g., `student-resources.rest-api.php`)
- JavaScript files: `kebab-case.js` (e.g., `badges-tab.js`)
- LESS files: `_underscore-prefix.less` (e.g., `_variables.less`)
- Templates: `kebab-case.hbs` (e.g., `handlebars-templates.hbs`)

## Version History (Recent)

- **v1.5.0** - Localizing student resources popup (configurable Elementor popup ID via ACF)
- **v1.4.5** - Fixed tab name formatting for "Classes, Exams, and Downloads"
- **v1.4.4** - Updated tab title and documentation
- **v1.4.0** - Added Student Resources column to classes table
- **v1.3.0** - Migrated content to ACF options page
- **v1.2.0** - **Major migration from Salesforce to Zoho API**

## External Resources

- **Open Badges Specification**: https://openbadges.org/
- **TypeRocket Docs**: https://typerocket.com/docs/
- **WooCommerce My Account**: https://woocommerce.com/document/woocommerce-my-account/
- **Handlebars Templates**: https://handlebarsjs.com/

## Questions to Ask When Uncertain

1. Is this change for en_US locale only?
2. Does this affect cached data? Should transients be cleared?
3. Is this a breaking change for existing badges?
4. Does this require Zoho API changes too?
5. Will this work with both completed and incomplete badges?
6. Does this affect the Open Badges specification compliance?
7. Is the Elementor popup ID configured correctly?
8. Are resource pages linked correctly to classes?

---

This documentation is current as of version 1.5.0. Update this file when making significant architectural changes or adding major features.
