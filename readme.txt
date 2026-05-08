=== WS Brevo Form Connector ===
Contributors: webstrategy
Tags: brevo, sendinblue, email marketing, crm, ajax
Requires at least: 5.6
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sync contacts to Brevo via a universal AJAX endpoint and a PHP API. No form plugin required.

== Description ==

WS Brevo Form Connector is an infrastructure brick: it exposes an AJAX endpoint and a static PHP method to push contacts to Brevo from any source — custom HTML form, JavaScript, WordPress hook, or third-party plugin.

The plugin has no dependency on any form builder.

This plugin relies on the **Brevo** (formerly Sendinblue) third-party service to store contact data. By configuring and using this plugin, you agree to Brevo's terms of service and privacy policy:

* [Brevo Terms of Service](https://www.brevo.com/legal/termsofuse/)
* [Brevo Privacy Policy](https://www.brevo.com/legal/privacypolicy/)

**How it works**

1. Configure your Brevo API key and default list ID in the plugin settings.
2. Choose a trigger field name (e.g. `ws-brevo-sync`).
3. Add a hidden input with that name to any form you want to track.
4. The plugin JS intercepts every form submission on the page, detects the trigger field, and silently syncs the contact to Brevo without interrupting the form flow.

**Features**

* Universal AJAX endpoint — works for logged-in and logged-out users (wp_ajax + wp_ajax_nopriv)
* Configurable trigger field: opt any form in by adding a single hidden input
* Configurable field name mapping (email, first name, last name, phone, company)
* Per-source routing rules: send each form_id to a different Brevo list
* Sync log (last 50 entries) with status and error details
* Built-in API connection test
* Direct PHP call via `WS_Brevo_FC_Sync::contact()` for developers
* `ws_brevo_fc_after_sync` action hook for third-party extensions

== Installation ==

1. Install and activate the plugin from the WordPress admin.
2. Go to **Brevo Connector** in the admin menu.
3. Enter your Brevo API key (Brevo → Settings → API Keys & SMTP).
4. Set your default Brevo list ID.
5. Copy the trigger field snippet into any form you want to sync.

== Frequently Asked Questions ==

= Which form plugins are supported? =

None in particular — and that is by design. The plugin intercepts native browser form submissions, so it works with any form regardless of how it was built.

= How do I opt a form in? =

Add this hidden input anywhere inside the form:

`<input type="hidden" name="ws-brevo-sync" value="1">`

The field name is configurable in the plugin settings.

= Where do I find my Brevo list ID? =

In Brevo, go to Contacts → Lists, click your list. The ID is visible in the page URL.

= How do I send a specific form to a different list? =

In the Routing Rules tab, add a rule with the form_id (shown in the sync log after the first submission) and set the target list ID.

= Can I call the sync from PHP directly? =

Yes:

`WS_Brevo_FC_Sync::contact( 'john@example.com', [ 'PRENOM' => 'John', 'NOM' => 'Doe' ], 3, 'my-source' );`

== Privacy Policy ==

This plugin transmits personal data entered in your forms to the Brevo API (`https://api.brevo.com`), a third-party service operated by Brevo SAS.

**What data is sent:** email address, and optionally first name, last name, phone number, and company name — only the fields present in the submitted form.

**When data is sent:** only when a form submission contains the configured trigger field (a hidden input you add manually to forms you want to track). No data is collected passively or without a user actively submitting a form.

**Who receives the data:** Brevo SAS. Data is processed according to Brevo's privacy policy: https://www.brevo.com/legal/privacypolicy/

**Your responsibilities:** as the site owner, you are responsible for informing your users that their contact data will be stored in Brevo, and for obtaining any consent required by applicable law (GDPR, CCPA, etc.). We recommend updating your site's privacy policy accordingly.

== Screenshots ==

1. Configuration tab — Brevo API key field with step-by-step guide and trigger field setup.
2. Field Mapping tab — Map your form field names to Brevo contact attributes.
3. AJAX Endpoint tab — Universal endpoint documentation with JS and PHP code examples.
4. Sync Log tab — Last 50 sync entries with status, source, target list, and error details.

== Changelog ==

= 1.5.0 =
* Admin: step-by-step API key guide in Configuration tab (fully i18n)
* Admin: updated Default List ID hint with clearer instructions
* readme.txt: Tested up to 6.8, Privacy Policy section, Brevo ToS and Privacy Policy links
* Assets: 4 wordpress.org screenshots (1200x900)
* .pot: 67 translatable strings

= 1.4.3 =
* Tests: absint native stub in bootstrap, replace expectAction with userFunction do_action
* CI: codecov-action updated to v5 (Node.js 22)
* Chore: removed stray phpunit.xml.dist

= 1.4.2 =
* Fix: admin inputs use hard hex colors with !important — WordPress admin CSS override proof
* Fix: box-shadow reset on inputs to neutralise WP focus ring

= 1.4.1 =
* Fix: Avada white frame — add #wpbody to reset selectors, use #14121C instead of transparent
* Fix: never reset margin on #wpcontent (WordPress sidebar layout preservation)

= 1.4.0 =
* Added plugin SVG logo in assets/
* Added plugin_action_links: Settings and More plugins links in the plugin list

= 1.3.2 =
* Removed map_attributes() — dead code, no longer needed after AJAX endpoint refactor

= 1.3.1 =
* Simplified ajax_submit: direct Brevo attribute mapping, removed redundant option remapping

= 1.3.0 =
* Added configurable trigger field (opt any form in via a hidden input)
* All source code comments and strings in English
* Full i18n — strings extracted to .pot file
* Public JS: intercepts all form submits, checks trigger field, fire-and-forget AJAX
* Removed public/css directory
* All add_option calls use autoload = false

= 1.2.0 =
* Removed all form builder-specific adapters — plugin is now fully standalone
* No dependency on any form plugin

= 1.1.0 =
* Added universal AJAX endpoint (wp_ajax + wp_ajax_nopriv)
* Nonce injected automatically in wp_footer via wp_localize_script
* Added WS_Brevo_FC_Sync static class as single sync entry point
* Added ws_brevo_fc_after_sync action hook for third-party extensions
* Unit tests (WP_Mock + PHPUnit 9), GitHub Actions CI with coverage matrix PHP 7.4 to 8.2

= 1.0.0 =
* Initial release
