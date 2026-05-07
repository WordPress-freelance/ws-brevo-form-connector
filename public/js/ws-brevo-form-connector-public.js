/**
 * WS Brevo Form Connector — Public script
 *
 * Listens on every <form> submit across the page.
 * A form is synced to Brevo only if it contains an input whose
 * name attribute matches the configured trigger field.
 *
 * Config object injected by wp_localize_script as `wsBrevoFCPublic`:
 *   ajaxurl      {string} WordPress admin-ajax.php URL
 *   nonce        {string} wp_create_nonce value
 *   action       {string} AJAX action name
 *   triggerField {string} input name that opts a form in (e.g. "ws-brevo-sync")
 *   listId       {number} default Brevo list ID (0 = use plugin default)
 *   fields       {object} mapping: { email, firstname, lastname, phone, company }
 */
(function () {
  'use strict';

  if (typeof wsBrevoFCPublic === 'undefined') return;

  var cfg = wsBrevoFCPublic;

  /**
   * Reads the value of the first input matching a given name inside a form.
   * Returns an empty string if not found.
   *
   * @param {HTMLFormElement} form
   * @param {string}          name
   * @returns {string}
   */
  function fieldValue(form, name) {
    if (!name) return '';
    var el = form.querySelector('[name="' + name + '"]');
    return el ? el.value.trim() : '';
  }

  /**
   * Resolves the form_id from the form element:
   *   1. id attribute
   *   2. name attribute
   *   3. page path + index among forms on the page
   *
   * @param {HTMLFormElement} form
   * @returns {string}
   */
  function resolveFormId(form) {
    if (form.id)   return form.id;
    if (form.name) return form.name;
    var forms = document.querySelectorAll('form');
    var idx   = Array.prototype.indexOf.call(forms, form);
    return window.location.pathname.replace(/\//g, '-').replace(/^-/, '') + '-form-' + idx;
  }

  /**
   * Attempts to sync the form's contact data to Brevo.
   * Does nothing if the trigger field is absent or the email is empty.
   * Never prevents the form from submitting normally.
   *
   * @param {HTMLFormElement} form
   */
  function syncForm(form) {
    // Only sync forms that opted in via the trigger field
    if (!fieldValue(form, cfg.triggerField) && !form.querySelector('[name="' + cfg.triggerField + '"]')) {
      return;
    }

    var email = fieldValue(form, cfg.fields.email);
    if (!email) return;

    var params = new URLSearchParams({
      action:  cfg.action,
      nonce:   cfg.nonce,
      email:   email,
      form_id: resolveFormId(form),
    });

    // Optional fields — only include non-empty values
    var optional = {
      firstname: cfg.fields.firstname,
      lastname:  cfg.fields.lastname,
      phone:     cfg.fields.phone,
      company:   cfg.fields.company,
    };

    Object.keys(optional).forEach(function (key) {
      var val = fieldValue(form, optional[key]);
      if (val) params.append(key, val);
    });

    if (cfg.listId) params.append('list_id', cfg.listId);

    // Fire and forget — never block the form submission
    fetch(cfg.ajaxurl, {
      method:  'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body:    params.toString(),
    }).catch(function () {
      // Silent fail — do not disrupt user experience
    });
  }

  // Capture phase: intercepts submit before any inline handler can prevent it
  document.addEventListener('submit', function (e) {
    if (e.target && e.target.tagName === 'FORM') {
      syncForm(e.target);
    }
  }, true);

})();
