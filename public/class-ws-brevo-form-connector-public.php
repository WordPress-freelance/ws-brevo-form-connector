<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WS_Brevo_FC_Public
 *
 * Expose l'endpoint AJAX universel (priv + nopriv) et injecte
 * le nonce en footer. Aucune dépendance à un plugin de formulaire.
 */
class WS_Brevo_FC_Public {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    /**
     * Injecte wsBrevoFCPublic en wp_footer.
     *
     * Utilisation JS :
     *   fetch( wsBrevoFCPublic.ajaxurl, {
     *     method: 'POST',
     *     headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
     *     body: new URLSearchParams({
     *       action:    wsBrevoFCPublic.action,
     *       nonce:     wsBrevoFCPublic.nonce,
     *       email:     'john@example.com',
     *       firstname: 'John',
     *     })
     *   });
     */
    public function output_public_nonce() {
        echo '<script>var wsBrevoFCPublic=' . wp_json_encode( array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ws_brevo_fc_public' ),
            'action'  => 'ws_brevo_fc_submit',
        ) ) . ';</script>' . "\n";
    }

    /**
     * Endpoint AJAX universel.
     *
     * Paramètres POST attendus :
     *   action    — ws_brevo_fc_submit (obligatoire)
     *   nonce     — wsBrevoFCPublic.nonce (obligatoire)
     *   email     — adresse email (obligatoire)
     *   firstname — prénom (optionnel)
     *   lastname  — nom (optionnel)
     *   phone     — téléphone (optionnel)
     *   company   — entreprise (optionnel)
     *   list_id   — ID liste Brevo (optionnel, override le défaut global)
     *   form_id   — identifiant source (optionnel, pour les règles et le journal)
     *
     * Réponse JSON :
     *   { success: true,  data: { message: '...' } }
     *   { success: false, data: { message: '...' } }
     */
    public function ajax_submit() {
        if ( ! check_ajax_referer( 'ws_brevo_fc_public', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Nonce invalide.' ), 403 );
        }

        $email   = sanitize_email( wp_unslash( $_POST['email']   ?? '' ) );
        $form_id = sanitize_text_field( wp_unslash( $_POST['form_id'] ?? 'ajax' ) );
        $list_id = absint( $_POST['list_id'] ?? 0 );

        $fields = array(
            get_option( 'ws_brevo_fc_field_firstname', 'firstname' ) => sanitize_text_field( wp_unslash( $_POST['firstname'] ?? '' ) ),
            get_option( 'ws_brevo_fc_field_lastname',  'lastname' )  => sanitize_text_field( wp_unslash( $_POST['lastname']  ?? '' ) ),
            get_option( 'ws_brevo_fc_field_phone',     'phone' )     => sanitize_text_field( wp_unslash( $_POST['phone']     ?? '' ) ),
            get_option( 'ws_brevo_fc_field_company',   'company' )   => sanitize_text_field( wp_unslash( $_POST['company']   ?? '' ) ),
        );

        $attributes = WS_Brevo_FC_Sync::map_attributes( $fields );
        $result     = WS_Brevo_FC_Sync::contact( $email, $attributes, $list_id, $form_id );

        if ( $result['ok'] ) {
            wp_send_json_success( array( 'message' => 'Contact synchronise.' ) );
        } else {
            wp_send_json_error( array( 'message' => $result['msg'] ), 400 );
        }
    }
}
