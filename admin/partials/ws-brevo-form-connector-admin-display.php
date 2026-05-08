<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
$api_key       = get_option( 'ws_brevo_fc_api_key', '' );
$list_id       = get_option( 'ws_brevo_fc_default_list_id', '' );
$trigger_field = get_option( 'ws_brevo_fc_trigger_field', 'ws-brevo-sync' );
$f_email       = get_option( 'ws_brevo_fc_field_email',     'email' );
$f_first       = get_option( 'ws_brevo_fc_field_firstname', 'firstname' );
$f_last        = get_option( 'ws_brevo_fc_field_lastname',  'lastname' );
$f_phone       = get_option( 'ws_brevo_fc_field_phone',     'phone' );
$f_company     = get_option( 'ws_brevo_fc_field_company',   'company' );
$form_rules    = json_decode( get_option( 'ws_brevo_fc_form_rules', '[]' ), true ) ?: array();
$sync_log      = json_decode( get_option( 'ws_brevo_fc_sync_log',   '[]' ), true ) ?: array();
$ajax_url      = admin_url( 'admin-ajax.php' );
?>

<div class="ws-brevo-fc-wrap">

  <?php if ( isset( $_GET['saved'] ) ) : ?>
  <div class="wsbfc-notice-ok"><?php esc_html_e( 'Settings saved.', 'ws-brevo-form-connector' ); ?></div>
  <?php endif; ?>

  <div class="wsbfc-header">
    <img src="<?php echo esc_url( WS_BREVO_FC_PLUGIN_URL . 'assets/logo.svg' ); ?>" alt="WS Brevo Form Connector" width="44" height="44" style="border-radius:10px;flex-shrink:0;" />
    <div>
      <div class="wsbfc-header-title"><?php esc_html_e( 'Brevo Form Connector', 'ws-brevo-form-connector' ); ?></div>
      <div class="wsbfc-header-sub"><?php esc_html_e( 'Universal Brevo contact sync — no form plugin required', 'ws-brevo-form-connector' ); ?></div>
    </div>
    <span class="wsbfc-version">v<?php echo WS_BREVO_FC_VERSION; ?></span>
  </div>

  <div class="wsbfc-tabs">
    <button class="wsbfc-tab active" data-tab="tab-settings"><?php esc_html_e( 'Configuration', 'ws-brevo-form-connector' ); ?></button>
    <button class="wsbfc-tab" data-tab="tab-mapping"><?php esc_html_e( 'Field Mapping', 'ws-brevo-form-connector' ); ?></button>
    <button class="wsbfc-tab" data-tab="tab-rules"><?php esc_html_e( 'Routing Rules', 'ws-brevo-form-connector' ); ?></button>
    <button class="wsbfc-tab" data-tab="tab-ajax"><?php esc_html_e( 'AJAX Endpoint', 'ws-brevo-form-connector' ); ?></button>
    <button class="wsbfc-tab" data-tab="tab-logs"><?php esc_html_e( 'Log', 'ws-brevo-form-connector' ); ?> (<?php echo count( $sync_log ); ?>)</button>
  </div>

  <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
    <?php wp_nonce_field( 'ws_brevo_fc_save_settings' ); ?>
    <input type="hidden" name="action" value="ws_brevo_fc_save_settings" />

    <!-- ═══ CONFIGURATION ═══ -->
    <div id="tab-settings" class="wsbfc-panel active">

      <div class="wsbfc-card">
        <div class="wsbfc-card-title">🔑 <?php esc_html_e( 'Brevo Connection', 'ws-brevo-form-connector' ); ?></div>

        <div class="wsbfc-field">
          <label class="wsbfc-label" for="ws_brevo_fc_api_key"><?php esc_html_e( 'Brevo API Key', 'ws-brevo-form-connector' ); ?></label>
          <div class="wsbfc-api-row">
            <input type="password" id="ws_brevo_fc_api_key" name="ws_brevo_fc_api_key"
                   class="wsbfc-input mono"
                   value="<?php echo esc_attr( $api_key ); ?>"
                   placeholder="xkeysib-…" />
            <button type="button" class="wsbfc-btn wsbfc-btn-ghost" id="wsbfc-toggle-key"><?php esc_html_e( 'Show', 'ws-brevo-form-connector' ); ?></button>
            <button type="button" class="wsbfc-btn wsbfc-btn-ghost" id="wsbfc-test-api"><?php esc_html_e( 'Test connection', 'ws-brevo-form-connector' ); ?></button>
          </div>
          <div id="wsbfc-test-result" class="wsbfc-test-result"></div>
        </div>

        <div class="wsbfc-api-guide">
          <div class="wsbfc-api-guide-title">
            <?php esc_html_e( 'How to get your Brevo API key', 'ws-brevo-form-connector' ); ?>
          </div>
          <ol class="wsbfc-api-guide-steps">
            <li><?php
              printf(
                /* translators: %s: URL to Brevo login page */
                wp_kses( __( 'Go to <a href="%s" target="_blank" rel="noopener noreferrer">app.brevo.com</a> and log in to your account.', 'ws-brevo-form-connector' ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ),
                'https://app.brevo.com'
              );
            ?></li>
            <li><?php esc_html_e( 'Click on your account name or avatar in the top-right corner of the page.', 'ws-brevo-form-connector' ); ?></li>
            <li><?php esc_html_e( 'Select "SMTP & API" from the dropdown menu.', 'ws-brevo-form-connector' ); ?></li>
            <li><?php esc_html_e( 'Click the "API Keys" tab.', 'ws-brevo-form-connector' ); ?></li>
            <li><?php esc_html_e( 'Click "Generate a new API key".', 'ws-brevo-form-connector' ); ?></li>
            <li><?php esc_html_e( 'Give it a name (e.g. "WordPress — My Site") so you can identify it later.', 'ws-brevo-form-connector' ); ?></li>
            <li><?php esc_html_e( 'Click "Generate". Copy the key immediately — it will only be shown once.', 'ws-brevo-form-connector' ); ?></li>
            <li><?php esc_html_e( 'Paste it in the field above and click "Save settings".', 'ws-brevo-form-connector' ); ?></li>
          </ol>
        </div>

        <div class="wsbfc-field" style="margin-top:20px;">
          <label class="wsbfc-label" for="ws_brevo_fc_default_list_id"><?php esc_html_e( 'Default List ID', 'ws-brevo-form-connector' ); ?></label>
          <input type="number" id="ws_brevo_fc_default_list_id" name="ws_brevo_fc_default_list_id"
                 class="wsbfc-input" style="max-width:160px;"
                 value="<?php echo esc_attr( $list_id ); ?>"
                 placeholder="e.g. 3" min="1" />
          <div class="wsbfc-hint">
            <?php esc_html_e( 'In Brevo, go to Contacts → Lists, click your list. The ID is visible in the page URL.', 'ws-brevo-form-connector' ); ?>
          </div>
        </div>
      </div>

      <div class="wsbfc-card">
        <div class="wsbfc-card-title">⚡ <?php esc_html_e( 'Trigger Field', 'ws-brevo-form-connector' ); ?></div>

        <div class="wsbfc-info">
          <?php esc_html_e( 'Add a hidden input with this exact name to every form you want to sync. The plugin JS will detect it and send the contact to Brevo.', 'ws-brevo-form-connector' ); ?>
        </div>

        <div class="wsbfc-field">
          <label class="wsbfc-label" for="ws_brevo_fc_trigger_field"><?php esc_html_e( 'Trigger field name (name= attribute)', 'ws-brevo-form-connector' ); ?></label>
          <input type="text" id="ws_brevo_fc_trigger_field" name="ws_brevo_fc_trigger_field"
                 class="wsbfc-input mono"
                 value="<?php echo esc_attr( $trigger_field ); ?>"
                 placeholder="ws-brevo-sync" />
          <div class="wsbfc-hint"><?php esc_html_e( 'Copy this into your forms as a hidden input:', 'ws-brevo-form-connector' ); ?></div>
          <pre class="wsbfc-snippet">&lt;input type="hidden" name="<?php echo esc_attr( $trigger_field ); ?>" value="1"&gt;</pre>
        </div>
      </div>

      <div class="wsbfc-form-actions">
        <button type="submit" class="wsbfc-btn wsbfc-btn-primary"><?php esc_html_e( 'Save settings', 'ws-brevo-form-connector' ); ?></button>
      </div>
    </div>

    <!-- ═══ FIELD MAPPING ═══ -->
    <div id="tab-mapping" class="wsbfc-panel">
      <div class="wsbfc-info">
        <?php esc_html_e( 'Enter the input name= attribute used in your forms for each field. The JS reads these to map form values to Brevo contact attributes.', 'ws-brevo-form-connector' ); ?>
      </div>
      <div class="wsbfc-card">
        <div class="wsbfc-card-title">📋 <?php esc_html_e( 'Form fields → Brevo attributes', 'ws-brevo-form-connector' ); ?></div>
        <div class="wsbfc-mapping-grid">

          <div class="wsbfc-field">
            <label class="wsbfc-label"><?php esc_html_e( 'Email field *', 'ws-brevo-form-connector' ); ?></label>
            <input type="text" name="ws_brevo_fc_field_email" class="wsbfc-input"
                   value="<?php echo esc_attr( $f_email ); ?>" placeholder="email" />
            <div class="wsbfc-hint">→ EMAIL <?php esc_html_e( '(required)', 'ws-brevo-form-connector' ); ?></div>
          </div>

          <div class="wsbfc-field">
            <label class="wsbfc-label"><?php esc_html_e( 'First name field', 'ws-brevo-form-connector' ); ?></label>
            <input type="text" name="ws_brevo_fc_field_firstname" class="wsbfc-input"
                   value="<?php echo esc_attr( $f_first ); ?>" placeholder="firstname" />
            <div class="wsbfc-hint">→ PRENOM</div>
          </div>

          <div class="wsbfc-field">
            <label class="wsbfc-label"><?php esc_html_e( 'Last name field', 'ws-brevo-form-connector' ); ?></label>
            <input type="text" name="ws_brevo_fc_field_lastname" class="wsbfc-input"
                   value="<?php echo esc_attr( $f_last ); ?>" placeholder="lastname" />
            <div class="wsbfc-hint">→ NOM</div>
          </div>

          <div class="wsbfc-field">
            <label class="wsbfc-label"><?php esc_html_e( 'Phone field', 'ws-brevo-form-connector' ); ?></label>
            <input type="text" name="ws_brevo_fc_field_phone" class="wsbfc-input"
                   value="<?php echo esc_attr( $f_phone ); ?>" placeholder="phone" />
            <div class="wsbfc-hint">→ SMS</div>
          </div>

          <div class="wsbfc-field">
            <label class="wsbfc-label"><?php esc_html_e( 'Company field', 'ws-brevo-form-connector' ); ?></label>
            <input type="text" name="ws_brevo_fc_field_company" class="wsbfc-input"
                   value="<?php echo esc_attr( $f_company ); ?>" placeholder="company" />
            <div class="wsbfc-hint">→ SOCIETE</div>
          </div>

        </div>
      </div>
      <div class="wsbfc-form-actions">
        <button type="submit" class="wsbfc-btn wsbfc-btn-primary"><?php esc_html_e( 'Save settings', 'ws-brevo-form-connector' ); ?></button>
      </div>
    </div>

    <!-- ═══ ROUTING RULES ═══ -->
    <div id="tab-rules" class="wsbfc-panel">
      <div class="wsbfc-info">
        <?php esc_html_e( 'Route a specific form_id to a different Brevo list, or disable sync entirely for it. The form_id appears in the log after a first submission.', 'ws-brevo-form-connector' ); ?>
      </div>
      <div class="wsbfc-card">
        <div class="wsbfc-card-title">🔀 <?php esc_html_e( 'Rules by source', 'ws-brevo-form-connector' ); ?></div>
        <table class="wsbfc-rules-table">
          <thead>
            <tr>
              <th style="width:35%">form_id</th>
              <th style="width:30%"><?php esc_html_e( 'Brevo list ID', 'ws-brevo-form-connector' ); ?></th>
              <th style="width:20%;text-align:center;"><?php esc_html_e( 'Active', 'ws-brevo-form-connector' ); ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody id="wsbfc-rules-tbody">
            <?php foreach ( $form_rules as $rule ) : ?>
            <tr class="wsbfc-rule-row">
              <td><input type="text" name="form_rule_id[]" value="<?php echo esc_attr( $rule['form_id'] ?? '' ); ?>" placeholder="contact" /></td>
              <td><input type="number" name="form_rule_list_id[]" value="<?php echo esc_attr( $rule['list_id'] ?? '' ); ?>" placeholder="3" min="0" /></td>
              <td style="text-align:center;">
                <label class="wsbfc-toggle">
                  <input type="checkbox" name="form_rule_active[]" value="1" <?php checked( $rule['active'] ?? 1, 1 ); ?> />
                  <span class="wsbfc-toggle-slider"></span>
                </label>
              </td>
              <td><button type="button" class="wsbfc-remove-rule" title="<?php esc_attr_e( 'Remove', 'ws-brevo-form-connector' ); ?>">×</button></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="wsbfc-add-rule">
          <button type="button" class="wsbfc-btn wsbfc-btn-ghost" id="wsbfc-add-rule">+ <?php esc_html_e( 'Add rule', 'ws-brevo-form-connector' ); ?></button>
        </div>
      </div>
      <div class="wsbfc-form-actions">
        <button type="submit" class="wsbfc-btn wsbfc-btn-primary"><?php esc_html_e( 'Save settings', 'ws-brevo-form-connector' ); ?></button>
      </div>
    </div>

  </form><!-- end form -->

  <!-- ═══ AJAX ENDPOINT ═══ -->
  <div id="tab-ajax" class="wsbfc-panel">
    <div class="wsbfc-card">
      <div class="wsbfc-card-title">⚡ <?php esc_html_e( 'Universal AJAX endpoint', 'ws-brevo-form-connector' ); ?></div>

      <div class="wsbfc-info">
        <?php esc_html_e( 'Available for logged-in and logged-out users (wp_ajax + wp_ajax_nopriv). The wsBrevoFCPublic config object is automatically injected in wp_footer on every frontend page.', 'ws-brevo-form-connector' ); ?>
      </div>

      <div class="wsbfc-field">
        <label class="wsbfc-label">URL</label>
        <input type="text" class="wsbfc-input mono" readonly
               value="<?php echo esc_attr( $ajax_url ); ?>"
               style="max-width:600px;" onclick="this.select()" />
      </div>

      <div class="wsbfc-field">
        <label class="wsbfc-label">POST params</label>
        <pre class="wsbfc-code">action    = ws_brevo_fc_submit          // required
nonce     = wsBrevoFCPublic.nonce       // required
email     = john@example.com            // required
firstname = John                        // optional
lastname  = Doe                         // optional
phone     = +33600000000                // optional
company   = ACME                        // optional
list_id   = 3                           // optional — overrides default
form_id   = my-custom-source           // optional — for rules & log</pre>
      </div>

      <div class="wsbfc-field">
        <label class="wsbfc-label">JS example</label>
        <pre class="wsbfc-code">fetch(wsBrevoFCPublic.ajaxurl, {
  method:  'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: new URLSearchParams({
    action:    wsBrevoFCPublic.action,
    nonce:     wsBrevoFCPublic.nonce,
    email:     'john@example.com',
    firstname: 'John',
    form_id:   'my-form',
  }).toString(),
});</pre>
      </div>

      <div class="wsbfc-field">
        <label class="wsbfc-label">PHP direct call</label>
        <pre class="wsbfc-code">WS_Brevo_FC_Sync::contact(
    'john@example.com',
    [ 'PRENOM' => 'John', 'NOM' => 'Doe' ],
    3,            // list_id (0 = global default)
    'my-hook'     // form_id for log
);</pre>
      </div>

    </div>
  </div>

  <!-- ═══ LOG ═══ -->
  <div id="tab-logs" class="wsbfc-panel">
    <div class="wsbfc-card">
      <div class="wsbfc-log-header">
        <div class="wsbfc-card-title" style="margin-bottom:0;padding-bottom:0;border-bottom:none;">
          📋 <?php esc_html_e( 'Sync log', 'ws-brevo-form-connector' ); ?>
        </div>
        <?php if ( ! empty( $sync_log ) ) : ?>
        <button type="button" class="wsbfc-btn wsbfc-btn-danger" id="wsbfc-clear-log">
          <?php esc_html_e( 'Clear log', 'ws-brevo-form-connector' ); ?>
        </button>
        <?php endif; ?>
      </div>
      <div style="border-bottom:0.5px solid var(--border);margin:12px 0;"></div>

      <?php if ( empty( $sync_log ) ) : ?>
        <div class="wsbfc-log-empty"><?php esc_html_e( 'No sync recorded yet.', 'ws-brevo-form-connector' ); ?></div>
      <?php else : ?>
        <table class="wsbfc-log-table">
          <thead>
            <tr>
              <th style="width:16%"><?php esc_html_e( 'Date', 'ws-brevo-form-connector' ); ?></th>
              <th style="width:26%"><?php esc_html_e( 'Email', 'ws-brevo-form-connector' ); ?></th>
              <th style="width:20%"><?php esc_html_e( 'Source', 'ws-brevo-form-connector' ); ?></th>
              <th style="width:10%"><?php esc_html_e( 'List', 'ws-brevo-form-connector' ); ?></th>
              <th style="width:12%"><?php esc_html_e( 'Status', 'ws-brevo-form-connector' ); ?></th>
              <th><?php esc_html_e( 'Detail', 'ws-brevo-form-connector' ); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ( array_reverse( $sync_log ) as $entry ) : ?>
            <tr>
              <td><?php echo esc_html( $entry['ts'] ?? '' ); ?></td>
              <td><span class="wsbfc-log-email"><?php echo esc_html( $entry['email'] ?? '—' ); ?></span></td>
              <td style="color:var(--t3);font-size:11px;"><?php echo esc_html( $entry['form_id'] ?? '—' ); ?></td>
              <td><?php echo esc_html( $entry['list_id'] ?? '—' ); ?></td>
              <td>
                <?php $ok = ( $entry['status'] ?? '' ) === 'ok'; ?>
                <span class="wsbfc-log-status <?php echo $ok ? 'ok' : 'fail'; ?>">
                  <?php echo $ok ? esc_html__( '✓ OK', 'ws-brevo-form-connector' ) : esc_html__( '✗ Error', 'ws-brevo-form-connector' ); ?>
                </span>
              </td>
              <td style="color:var(--t4);font-size:11px;"><?php echo esc_html( $entry['msg'] ?? '' ); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

</div>
