<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php
$api_key    = get_option( 'ws_brevo_fc_api_key', '' );
$list_id    = get_option( 'ws_brevo_fc_default_list_id', '' );
$f_email    = get_option( 'ws_brevo_fc_field_email', 'email' );
$f_first    = get_option( 'ws_brevo_fc_field_firstname', 'firstname' );
$f_last     = get_option( 'ws_brevo_fc_field_lastname', 'lastname' );
$f_phone    = get_option( 'ws_brevo_fc_field_phone', 'phone' );
$f_company  = get_option( 'ws_brevo_fc_field_company', 'company' );
$form_rules = json_decode( get_option( 'ws_brevo_fc_form_rules', '[]' ), true ) ?: array();
$sync_log   = json_decode( get_option( 'ws_brevo_fc_sync_log',   '[]' ), true ) ?: array();

$ajax_url   = admin_url( 'admin-ajax.php' );
?>

<div class="ws-brevo-fc-wrap">

  <?php if ( isset( $_GET['saved'] ) ) : ?>
  <div class="wsbfc-notice-ok">✓ Parametres enregistres.</div>
  <?php endif; ?>

  <div class="wsbfc-header">
    <div class="wsbfc-logo">Web<br>Strategy</div>
    <div>
      <div class="wsbfc-header-title">Brevo Form Connector</div>
      <div class="wsbfc-header-sub">Synchronisation universelle formulaires → Brevo</div>
    </div>
    <span class="wsbfc-version">v<?php echo WS_BREVO_FC_VERSION; ?></span>
  </div>

  <div class="wsbfc-tabs">
    <button class="wsbfc-tab active" data-tab="tab-settings">Configuration</button>
    <button class="wsbfc-tab" data-tab="tab-mapping">Mapping champs</button>
    <button class="wsbfc-tab" data-tab="tab-rules">Regles par formulaire</button>
    <button class="wsbfc-tab" data-tab="tab-ajax">Endpoint AJAX</button>
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
          <label class="wsbfc-label" for="ws_brevo_fc_api_key">Cle API Brevo</label>
          <div class="wsbfc-api-row">
            <input type="password" id="ws_brevo_fc_api_key" name="ws_brevo_fc_api_key"
                   class="wsbfc-input mono"
                   value="<?php echo esc_attr( $api_key ); ?>"
                   placeholder="xkeysib-…" />
            <button type="button" class="wsbfc-btn wsbfc-btn-ghost" id="wsbfc-toggle-key">Afficher</button>
            <button type="button" class="wsbfc-btn wsbfc-btn-ghost" id="wsbfc-test-api">Tester la connexion</button>
          </div>
          <div class="wsbfc-hint">Disponible dans Brevo → Parametres → Cles API &amp; SMTP.</div>
          <div id="wsbfc-test-result" class="wsbfc-test-result"></div>
        </div>

        <div class="wsbfc-field">
          <label class="wsbfc-label" for="ws_brevo_fc_default_list_id">ID de liste par defaut</label>
          <input type="number" id="ws_brevo_fc_default_list_id" name="ws_brevo_fc_default_list_id"
                 class="wsbfc-input" style="max-width:160px;"
                 value="<?php echo esc_attr( $list_id ); ?>"
                 placeholder="ex: 3" min="1" />
          <div class="wsbfc-hint">Tous les formulaires sans regle specifique envoient ici.</div>
        </div>
      </div>

      <div class="wsbfc-info">
        <strong>Builders detectes automatiquement :</strong> Contact Form 7 · Gravity Forms · WPForms · Elementor Forms Pro · Avada / Fusion Forms · Ninja Forms · Fluent Forms · Formidable Forms. Pour tout autre formulaire, utilisez l'endpoint AJAX universel (onglet « Endpoint AJAX »).
      </div>

      <div class="wsbfc-form-actions">
        <button type="submit" class="wsbfc-btn wsbfc-btn-primary">Enregistrer</button>
      </div>
    </div>

    <!-- ═══ MAPPING ═══ -->
    <div id="tab-mapping" class="wsbfc-panel">
      <div class="wsbfc-info">
        Indiquez le <strong>nom de champ</strong> utilise dans vos formulaires (pas le label). Le plugin cherche ces noms dans chaque submission pour alimenter les attributs Brevo correspondants.
      </div>
      <div class="wsbfc-card">
        <div class="wsbfc-card-title">📋 Champs formulaire → Attributs Brevo</div>
        <div class="wsbfc-mapping-grid">

          <div class="wsbfc-field">
            <label class="wsbfc-label">Champ Email *</label>
            <input type="text" name="ws_brevo_fc_field_email" class="wsbfc-input"
                   value="<?php echo esc_attr( $f_email ); ?>" placeholder="email" />
            <div class="wsbfc-hint">→ EMAIL (obligatoire)</div>
          </div>

          <div class="wsbfc-field">
            <label class="wsbfc-label">Champ Prenom</label>
            <input type="text" name="ws_brevo_fc_field_firstname" class="wsbfc-input"
                   value="<?php echo esc_attr( $f_first ); ?>" placeholder="firstname" />
            <div class="wsbfc-hint">→ PRENOM</div>
          </div>

          <div class="wsbfc-field">
            <label class="wsbfc-label">Champ Nom</label>
            <input type="text" name="ws_brevo_fc_field_lastname" class="wsbfc-input"
                   value="<?php echo esc_attr( $f_last ); ?>" placeholder="lastname" />
            <div class="wsbfc-hint">→ NOM</div>
          </div>

          <div class="wsbfc-field">
            <label class="wsbfc-label">Champ Telephone</label>
            <input type="text" name="ws_brevo_fc_field_phone" class="wsbfc-input"
                   value="<?php echo esc_attr( $f_phone ); ?>" placeholder="phone" />
            <div class="wsbfc-hint">→ SMS</div>
          </div>

          <div class="wsbfc-field">
            <label class="wsbfc-label">Champ Entreprise</label>
            <input type="text" name="ws_brevo_fc_field_company" class="wsbfc-input"
                   value="<?php echo esc_attr( $f_company ); ?>" placeholder="company" />
            <div class="wsbfc-hint">→ SOCIETE</div>
          </div>

        </div>
      </div>
      <div class="wsbfc-form-actions">
        <button type="submit" class="wsbfc-btn wsbfc-btn-primary">Enregistrer</button>
      </div>
    </div>

    <!-- ═══ RÈGLES PAR FORMULAIRE ═══ -->
    <div id="tab-rules" class="wsbfc-panel">
      <div class="wsbfc-info">
        Chaque formulaire est identifie par un prefixe + son ID natif (<strong>cf7-42</strong>, <strong>gf-3</strong>, <strong>avada-7</strong>, <strong>ajax</strong>…). Ces valeurs apparaissent dans le journal des synchronisations. Definissez ici une liste Brevo specifique ou desactivez la sync pour un formulaire donne.
      </div>
      <div class="wsbfc-card">
        <div class="wsbfc-card-title">🔀 Regles par formulaire</div>
        <table class="wsbfc-rules-table">
          <thead>
            <tr>
              <th style="width:35%">ID formulaire</th>
              <th style="width:30%">ID liste Brevo</th>
              <th style="width:20%;text-align:center;">Actif</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="wsbfc-rules-tbody">
            <?php foreach ( $form_rules as $rule ) : ?>
            <tr class="wsbfc-rule-row">
              <td><input type="text" name="form_rule_id[]" value="<?php echo esc_attr( $rule['form_id'] ?? '' ); ?>" placeholder="cf7-42" /></td>
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
          <button type="button" class="wsbfc-btn wsbfc-btn-ghost" id="wsbfc-add-rule">+ Ajouter une regle</button>
        </div>
      </div>
      <div class="wsbfc-form-actions">
        <button type="submit" class="wsbfc-btn wsbfc-btn-primary">Enregistrer</button>
      </div>
    </div>

  </form><!-- end form -->

  <!-- ═══ ENDPOINT AJAX ═══ -->
  <div id="tab-ajax" class="wsbfc-panel">
    <div class="wsbfc-card">
      <div class="wsbfc-card-title">⚡ Endpoint AJAX universel</div>

      <div class="wsbfc-info" style="margin-bottom:20px;">
        Utilisez cet endpoint pour tout formulaire non supporte nativement ou pour des integrations JS custom. Fonctionne pour les utilisateurs <strong>connectes et deconnectes</strong> (wp_ajax_nopriv).
      </div>

      <div class="wsbfc-field">
        <label class="wsbfc-label">URL</label>
        <input type="text" class="wsbfc-input mono" readonly
               value="<?php echo esc_attr( $ajax_url ); ?>"
               style="max-width:600px;color:var(--accent-l);" onclick="this.select()" />
      </div>

      <div class="wsbfc-field">
        <label class="wsbfc-label">Parametres POST</label>
        <div class="wsbfc-code-block">
<pre style="background:var(--bg-deep);border:0.5px solid var(--border-a);border-radius:8px;padding:16px;color:var(--accent-l);font-size:12px;font-family:monospace;overflow-x:auto;margin:0;">action    = ws_brevo_fc_submit    <span style="color:var(--t4);">// obligatoire</span>
nonce     = wsBrevoFCPublic.nonce  <span style="color:var(--t4);">// injecte automatiquement en footer</span>
email     = john@example.com       <span style="color:var(--t4);">// obligatoire</span>
firstname = John                   <span style="color:var(--t4);">// optionnel</span>
lastname  = Doe                    <span style="color:var(--t4);">// optionnel</span>
phone     = +33600000000           <span style="color:var(--t4);">// optionnel</span>
company   = ACME                   <span style="color:var(--t4);">// optionnel</span>
list_id   = 3                      <span style="color:var(--t4);">// optionnel — override la liste par defaut</span>
form_id   = mon-formulaire-custom  <span style="color:var(--t4);">// optionnel — pour les regles + le journal</span></pre>
        </div>
      </div>

      <div class="wsbfc-field">
        <label class="wsbfc-label">Exemple fetch JS</label>
<pre style="background:var(--bg-deep);border:0.5px solid var(--border-a);border-radius:8px;padding:16px;color:var(--t2);font-size:12px;font-family:monospace;overflow-x:auto;margin:0;"><span style="color:var(--accent-mid);">// wsBrevoFCPublic est injecte automatiquement dans wp_footer</span>
fetch(wsBrevoFCPublic.ajaxurl, {
  method: <span style="color:var(--green);">'POST'</span>,
  headers: { <span style="color:var(--green);">'Content-Type'</span>: <span style="color:var(--green);">'application/x-www-form-urlencoded'</span> },
  body: new URLSearchParams({
    action:    <span style="color:var(--green);">'ws_brevo_fc_submit'</span>,
    nonce:     wsBrevoFCPublic.nonce,
    email:     <span style="color:var(--green);">'john@example.com'</span>,
    firstname: <span style="color:var(--green);">'John'</span>,
    lastname:  <span style="color:var(--green);">'Doe'</span>,
    form_id:   <span style="color:var(--green);">'mon-formulaire'</span>,
  })
})
.then(r => r.json())
.then(data => {
  <span style="color:var(--accent-mid);">// data.success === true si OK</span>
  console.log(data);
});</pre>
      </div>

      <div class="wsbfc-field">
        <label class="wsbfc-label">Appel PHP direct (depuis du code custom)</label>
<pre style="background:var(--bg-deep);border:0.5px solid var(--border-a);border-radius:8px;padding:16px;color:var(--t2);font-size:12px;font-family:monospace;overflow-x:auto;margin:0;">WS_Brevo_FC_Sync::contact(
    <span style="color:var(--green);">'john@example.com'</span>,
    [
        <span style="color:var(--green);">'PRENOM'</span>  => <span style="color:var(--green);">'John'</span>,
        <span style="color:var(--green);">'NOM'</span>     => <span style="color:var(--green);">'Doe'</span>,
        <span style="color:var(--green);">'SOCIETE'</span> => <span style="color:var(--green);">'ACME'</span>,
    ],
    <span style="color:var(--accent-l);">3</span>,          <span style="color:var(--accent-mid);">// list_id (0 = defaut global)</span>
    <span style="color:var(--green);">'mon-hook'</span>  <span style="color:var(--accent-mid);">// form_id pour le journal</span>
);</pre>
      </div>

    </div>
  </div>

  <!-- ═══ JOURNAL ═══ -->
  <div id="tab-logs" class="wsbfc-panel">
    <div class="wsbfc-card">
      <div class="wsbfc-log-header">
        <div class="wsbfc-card-title" style="margin-bottom:0;padding-bottom:0;border-bottom:none;">📋 Journal de synchronisation</div>
        <?php if ( ! empty( $sync_log ) ) : ?>
        <button type="button" class="wsbfc-btn wsbfc-btn-danger" id="wsbfc-clear-log">Vider le journal</button>
        <?php endif; ?>
      </div>
      <div style="border-bottom:0.5px solid var(--border);margin:12px 0;"></div>

      <?php if ( empty( $sync_log ) ) : ?>
        <div class="wsbfc-log-empty">Aucune synchronisation enregistree.</div>
      <?php else : ?>
        <table class="wsbfc-log-table">
          <thead>
            <tr>
              <th style="width:16%">Date</th>
              <th style="width:26%">Email</th>
              <th style="width:20%">Formulaire</th>
              <th style="width:10%">Liste</th>
              <th style="width:12%">Statut</th>
              <th>Detail</th>
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
