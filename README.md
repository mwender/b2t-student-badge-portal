# B2T Student Badge Portal
**Contributors:** thewebist
**Tags:** wordpress,woocommerce,salesforce
**Requires at least:** 6.7.0
**Tested up to:** 6.9
**Stable tag:** 1.6.1
**License:** GPLv2 or later
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin which provides a listing of B2T Student Badges/Certificates inside a user's WooCommerce account pages.

## Using Your Badge with a Digital Wallet

As of version 1.6.0, badges issued through the B2T Student Badge Portal follow the Open Badges 2.0 standard and are compatible with modern digital badge wallets.

When you click “Add to Backpack” (or equivalent) for a completed badge or certificate:

1. The badge is securely issued by the B2T Training website.
2. A permanent badge URL is generated.
3. That badge URL is copied to your clipboard.

You can then paste this URL into a compatible badge wallet to import and store your badge.

Supported wallets include (but are not limited to):

- Badgr
- Canvas Credentials
- Other Open Badges 2.0–compatible wallets

The badge URL remains valid permanently and can be reused at any time to re-import the badge if needed.

## Changelog

### 1.6.1
* Correctly sending REST Nonce via `X-WP-Nonce` header.
* Glob loading `lib/fns/*.php`.

### 1.6.0
* Migrated badge issuance to a modern Open Badges 2.0–compliant flow.
* Removed dependency on the deprecated OpenBadges Backpack issuer API.
* Added persistent storage for issued badge assertions.
* Introduced canonical, privacy-safe assertion URLs (`/assertions/{assertion_id}`).
* Implemented explicit server-side badge issuance endpoint.
* Updated frontend badge actions to issue and copy canonical assertion URLs for use with modern badge wallets (Badgr, Canvas Credentials, etc.).

### 1.5.0
* Updating the Student Resources Popup to not show any text when there are downloads available but no content added to the Student Resources CPT.
* Localizing `student-resources-popup.js` to allow setting the Elementor Popup ID via the WP Admin.

### 1.4.5
* Added exception so that WC My Account tab for "Classes, Exams, and Downloads" does not get run through `ucwords()`.

### 1.4.4
* Updating "My Account > Classes/Exams" to "My Account > Classes, Exams, and Downloads".

### 1.4.3
* Correcting Popup ID.

### 1.4.2
* Updating Elementor popup id in `student-resources-popup.js`.

### 1.4.1
* Setting `IS_LOCAL` if not already defined.

### 1.4.0
* Adding "Student Resources" column to "My Account > Classes/Exams" table.

### 1.3.1
* Updating Certification and Classes text content to pull from an ACF Group > Subfield.

### 1.3.0
* Converting Certification and Classes tab content to be pulled from an ACF Options Page field.

### 1.2.2
* Handling no student found for given email.

### 1.2.1
* Restoring REST `permission_callback` for Zoho API.
* Bugfix: Removing call to undefined function in Zoho API.

### 1.2.0
* Switching from Salesforce to Zoho API Endpoints.

### 1.1.5
* Adding additional checks before attempting to initialize `$student` inside `enqueue_scripts()`.

### 1.1.4
* Logging SF API Login response.
* Adding `uber_log()`.

### 1.1.3.1
* Using top level namespace for `new WP_Error`.

### 1.1.3
* Throwing a WP_Error when SalesForce session isn't established.

### 1.1.2
* BUGFIX: Commenting out invalid check of array in SalesForce response data.

### 1.1.1
* Updating colors to match B2T branding.
* Updating default badge image.

### 1.1.0
* Changing "Netmind" to "B2T Training".

### 1.0.5
* Adding compiled CSS to repositorty (i.e. `lib/css/`).

### 1.0.4
* Removing "Cert. Program" and "Classes/Exams" for non-English viewers.

### 1.0.3
* Checking for the existence of `$wp_query->query_vars`.

### 1.0.2
* WP 5.5 compatibility: Adding `permission_callback` for REST endpoints.

### 1.0.1
* Adding "No Classes Found" message for an empty class data response returned from SalesForce.

### 1.0.0
* Student Classes tab.
* Initial production release.

### 0.1.4
* Making sure `No Student Data Found` message shows.
* Updating My Account subnav order.

### 0.1.3
* Ensuring SalesForce session is present before querying data from the API.
* Overall layout improvements.

### 0.1.2
* Badge/Certificates tab layout updates.

### 0.1.1
* Adding default badge image.

### 0.1.0
* Moving SF login inside enqueues.

### 0.0.9
* Connects to SalesForce to retrieve Student data.