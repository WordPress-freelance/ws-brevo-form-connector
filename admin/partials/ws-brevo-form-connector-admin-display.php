<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
$api_key    = get_option( 'ws_brevo_fc_api_key', '' );
$list_id    = get_option( 'ws_brevo_fc_default_list_id', '' );
$f_first    = get_option( 'ws_brevo_fc_field_firstname', 'firstname' );
$f_last     = get_option( 'ws_brevo_fc_field_lastname',  'lastname' );
$f_phone    = get_option( 'ws_brevo_fc_field_phone',     'phone' );
$f_company  = get_option( 'ws_brevo_fc_field_company',   'company' );
$form_rules = json_decode( get_option( 'ws_brevo_fc_form_rules', '[]' ), true ) ?: array();
$sync_log   = json_decode( get_option( 'ws_brevo_fc_sync_log',   '[]' ), true ) ?: array();
$ajax_url   = admin_url( 'admin-ajax.php' );
?>

<div class="ws-brevo-fc-wrap">

  <?php if ( isset( $_GET['saved'] ) ) : ?>
  <div class="wsbfc-notice-ok">✓ Paramètres enregistrés.</div>
  <?php endif; ?>

  <div class="wsbfc-header">
    <div class="wsbfc-logo">Web<br>Strategy</div>
    <div>
      <div class="wsbfc-header-title">Brevo Form Connector</div>
      <div class="wsbfc-header-sub">Connecteur universel WordPress → Brevo</div>
    </div>
    <span class="wsbfc-version">v<?php echo WS_BREVO_FC_VERSION; ?></span>
  </div>

  <div class="wsbfc-tabs">
    <button class="wsbfc-tab active" data-tab="tab-settings">Configuration</button>
    <button class="wsbfc-tab" data-tab="tab-mapping">Mapping champs</button>
    <button class="wsbfc-tab" data-tab="tab-rules">Règles</button>
    <button class="wsbfc-tab" data-tab="tab-ajax">Intégration</button>
    <button class="wsbfc-tab" data-tab="tab-logs">Journal (<?php echo count( $sync_log ); ?>)</button>
  </div>

  <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
    <?php wp_nonce_field( 'ws_brevo_fc_save_settings' ); ?>
    <input type="hidden" name="action" value="ws_brevo_fc_save_settings" />

    <!-- ═══ CONFIGURATION ═══ -->
    <div id="tab-settings" class="wsbfc-panel active">
      <div class="wsbfc-card">
        <div class="wsbfc-card-title">🔑 Connexion Brevo</div>

        <div class="wsbfc-field">
          <label class="wsbfc-label" for="ws_brevo_fc_api_key">Clé API Brevo</label>
          <div class="wsbfc-api-row">
            <input type="password" id="ws_brevo_fc_api_key" name="ws_brevo_fc_api_key"
                   class="wsbfc-input mono"
                   value="<?php echo esc_attr( $api_key ); ?>"
                   placeholder="xkeysib-…" />
            <button type="button" class="wsbfc-btn wsbfc-btn-ghost" id="wsbfc-toggle-key">Afficher</button>
            <button type="button" class="wsbfc-btn wsbfc-btn-ghost" id="wsbfc-test-api">Tester la connexion</button>
          </div>
          <div class="wsbfc-hint">Disponible dans Brevo → Paramètres → Clés API &amp; SMTP.</div>
          <div id="wsbfc-test-result" class="wsbfc-test-result"></div>
        </div>

        <div class="wsbfc-field">
          <label class="wsbfc-label" for="ws_brevo_fc_default_list_id">ID de liste par défaut</label>
          <input type="number" id="ws_brevo_fc_default_list_id" name="ws_brevo_fc_default_list_id"
                 class="wsbfc-input" style="max-width:160px;"
                 value="<?php echo esc_attr( $list_id ); ?>"
                 placeholder="ex: 3" min="1" />
          <div class="wsbfc-hint">Toutes les soumissions sans règle spécifique atterrissent dans cette liste.</div>
        </div>
      </div>
      <div class="wsbfc-form-actions">
        <button type="submit" class="wsbfc-btn wsbfc-btn-primary">Enregistrer</button>
      </div>
    </div>

    <!-- ═══ MAPPING ═══ -->
    <div id="tab-mapping" class="wsbfc-panel">
      <div class="wsbfc-info">
        Ces noms de paramètres sont ceux que vous envoyez dans vos appels AJAX ou PHP. Modifiez-les uniquement si vous utilisez une convention différente dans votre code.
      </div>
      <div class="wsbfc-card">
        <div class="wsbfc-card-title">📋 Paramètres → Attributs Brevo</div>
        <div class="wsbfc-mapping-grid">

          <div class="wsbfc-field">
            <label class="wsbfc-label">Paramètre Prénom</label>
            <input type="text" name="ws_brevo_fc_field_firstname" class="wsbfc-input"
                   value="<?php echo esc_attr( $f_first ); ?>" placeholder="firstname" />
            <div class="wsbfc-hint">→ attribut Brevo PRENOM</div>
          </div>

          <div class="wsbfc-field">
            <label class="wsbfc-label">Paramètre Nom</label>
            <input type="text" name="ws_brevo_fc_field_lastname" class="wsbfc-input"
                   value="<?php echo esc_attr( $f_last ); ?>" placeholder="lastname" />
            <div class="wsbfc-hint">→ attribut Brevo NOM</div>
          </div>

          <div class="wsbfc-field">
            <label class="wsbfc-label">Paramètre Téléphone</label>
            <input type="text" name="ws_brevo_fc_field_phone" class="wsbfc-input"
                   value="<?php echo esc_attr( $f_phone ); ?>" placeholder="phone" />
            <div class="wsbfc-hint">→ attribut Brevo SMS</div>
          </div>

          <div class="wsbfc-field">
            <label class="wsbfc-label">Paramètre Entreprise</label>
            <input type="text" name="ws_brevo_fc_field_company" class="wsbfc-input"
                   value="<?php echo esc_attr( $f_company ); ?>" placeholder="company" />
            <div class="wsbfc-hint">→ attribut Brevo SOCIETE</div>
          </div>

        </div>
      </div>
      <div class="wsbfc-form-actions">
        <button type="submit" class="wsbfc-btn wsbfc-btn-primary">Enregistrer</button>
      </div>
    </div>

    <!-- ═══ RÈGLES ═══ -->
    <div id="tab-rules" class="wsbfc-panel">
      <div class="wsbfc-info">
        Routez certains appels vers une liste Brevo spécifique en fonction du <strong>form_id</strong> passé dans la requête. Vous pouvez aussi désactiver la sync pour un form_id donné en décochant le toggle.
      </div>
      <div class="wsbfc-card">
        <div class="wsbfc-card-title">🔀 Règles par form_id</div>
        <table class="wsbfc-rules-table">
          <thead>
            <tr>
              <th style="width:35%">form_id</th>
              <th style="width:30%">ID liste Brevo</th>
              <th style="width:20%;text-align:center;">Actif</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="wsbfc-rules-tbody">
            <?php foreach ( $form_rules as $rule ) : ?>
            <tr class="wsbfc-rule-row">
              <td><input type="text" name="form_rule_id[]" value="<?php echo esc_attr( $rule['form_id'] ?? '' ); ?>" placeholder="ex: contact" /></td>
              <td><input type="number" name="form_rule_list_id[]" value="<?php echo esc_attr( $rule['list_id'] ?? '' ); ?>" placeholder="3" min="0" /></td>
              <td style="text-align:center;">
                <label class="wsbfc-toggle">
                  <input type="checkbox" name="form_rule_active[]" value="1" <?php checked( $rule['active'] ?? 1, 1 ); ?> />
                  <span class="wsbfc-toggle-slider"></span>
                </label>
              </td>
              <td><button type="button" class="wsbfc-remove-rule" title="Supprimer">×</button></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="wsbfc-add-rule">
          <button type="button" class="wsbfc-btn wsbfc-btn-ghost" id="wsbfc-add-rule">+ Ajouter une règle</button>
        </div>
      </div>
      <div class="wsbfc-form-actions">
        <button type="submit" class="wsbfc-btn wsbfc-btn-primary">Enregistrer</button>
      </div>
    </div>

  </form>

  <!-- ═══ INTÉGRATION ═══ -->
  <div id="tab-ajax" class="wsbfc-panel">
    <div class="wsbfc-card">
      <div class="wsbfc-card-title">⚡ Endpoint AJAX</div>
      <div class="wsbfc-info" style="margin-bottom:20px;">
        Cet endpoint accepte n'importe quelle source — formulaire natif HTML, JS custom, plugin tiers via hook PHP. L'objet <code>wsBrevoFC</code> est automatiquement disponible sur toutes les pages frontend.
      </div>

      <div class="wsbfc-field">
        <label class="wsbfc-label">URL</label>
        <input type="text" class="wsbfc-input mono" readonly
               value="<?php echo esc_attr( $ajax_url ); ?>"
               style="max-width:600px;" onclick="this.select()" />
      </div>

      <div class="wsbfc-field">
        <label class="wsbfc-label">Paramètres POST</label>
<pre style="background:var(--bg-deep);border:0.5px solid var(--border-a);border-radius:8px;padding:16px;color:var(--accent-l);font-size:12px;font-family:monospace;overflow-x:auto;margin:0;">action    = ws_brevo_fc_submit     <span style="color:var(--t4);">// obligatoire</span>
nonce     = wsBrevoFC.nonce        <span style="color:var(--t4);">// obligatoire</span>
email     = john@example.com       <span style="color:var(--t4);">// obligatoire</span>
firstname = John                   <span style="color:var(--t4);">// optionnel</span>
lastname  = Doe                    <span style="color:var(--t4);">// optionnel</span>
phone     = +33600000000           <span style="color:var(--t4);">// optionnel</span>
company   = ACME                   <span style="color:var(--t4);">// optionnel</span>
list_id   = 3                      <span style="color:var(--t4);">// optionnel — override la liste par défaut</span>
form_id   = contact                <span style="color:var(--t4);">// optionnel — pour les règles et le journal</span></pre>
      </div>

      <div class="wsbfc-field">
        <label class="wsbfc-label">JavaScript</label>
<pre style="background:var(--bg-deep);border:0.5px solid var(--border-a);border-radius:8px;padding:16px;color:var(--t2);font-size:12px;font-family:monospace;overflow-x:auto;margin:0;"><span style="color:var(--accent-mid);">// wsBrevoFC est injecté automatiquement en wp_footer</span>
fetch(wsBrevoFC.ajaxurl, {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: new URLSearchParams({
    action:    wsBrevoFC.action,
    nonce:     wsBrevoFC.nonce,
    email:     'john@example.com',
    firstname: 'John',
    lastname:  'Doe',
    form_id:   'contact',
  })
}).then(r => r.json()).then(console.log);</pre>
      </div>

      <div class="wsbfc-field">
        <label class="wsbfc-label">PHP direct</label>
<pre style="background:var(--bg-deep);border:0.5px solid var(--border-a);border-radius:8px;padding:16px;color:var(--t2);font-size:12px;font-family:monospace;overflow-x:auto;margin:0;">WS_Brevo_FC_Sync::contact(
    'john@example.com',
    [ 'PRENOM' => 'John', 'NOM' => 'Doe' ],
    3,          <span style="color:var(--accent-mid);">// list_id (0 = défaut global)</span>
    'contact'   <span style="color:var(--accent-mid);">// form_id pour le journal</span>
);</pre>
      </div>
    </div>
  </div>

  <!-- ═══ JOURNAL ═══ -->
  <div id="tab-logs" class="wsbfc-panel">
    <div class="wsbfc-card">
      <div class="wsbfc-log-header">
        <div class="wsbfc-card-title" style="margin-bottom:0;padding-bottom:0;border-bottom:none;">📋 Journal</div>
        <?php if ( ! empty( $sync_log ) ) : ?>
        <button type="button" class="wsbfc-btn wsbfc-btn-danger" id="wsbfc-clear-log">Vider</button>
        <?php endif; ?>
      </div>
      <div style="border-bottom:0.5px solid var(--border);margin:12px 0;"></div>

      <?php if ( empty( $sync_log ) ) : ?>
        <div class="wsbfc-log-empty">Aucune synchronisation enregistrée.</div>
      <?php else : ?>
        <table class="wsbfc-log-table">
          <thead>
            <tr>
              <th style="width:16%">Date</th>
              <th style="width:28%">Email</th>
              <th style="width:18%">form_id</th>
              <th style="width:10%">Liste</th>
              <th style="width:12%">Statut</th>
              <th>Détail</th>
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
                  <?php echo $ok ? '✓ OK' : '✗ Erreur'; ?>
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
