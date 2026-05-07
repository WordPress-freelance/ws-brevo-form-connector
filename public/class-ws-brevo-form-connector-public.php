<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Public-facing functionality.
 *
 * Enqueues the front-end JS script and exposes the AJAX endpoint.
 * No dependency on any form plugin.
 */
class WS_Brevo_FC_Public {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    /**
     * Enqueues the public JS and passes config via wp_localize_script.
     * Hooked on wp_enqueue_scripts — runs unconditionally on every frontend page.
     *
     * The JS will:
     *   1. Listen on every <form> submit event.
     *   2. Check whether the form contains an input whose name matches triggerField.
     *   3. If found, map the other configured fields and POST to the AJAX endpoint.
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            WS_BREVO_FC_PLUGIN_URL . 'public/js/ws-brevo-form-connector-public.js',
            array(),
            $this->version,
            true // load in footer
        );

        wp_localize_script( $this->plugin_name, 'wsBrevoFCPublic', array(
            'ajaxurl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'ws_brevo_fc_public' ),
            'action'       => 'ws_brevo_fc_submit',
            // Trigger field: the input name the user must add to any form to opt-in
            'triggerField' => get_option( 'ws_brevo_fc_trigger_field', 'ws-brevo-sync' ),
            'listId'       => (int) get_option( 'ws_brevo_fc_default_list_id', 0 ),
            // Field name mapping: what name= attribute maps to which Brevo attribute
            'fields'       => array(
                'email'     => get_option( 'ws_brevo_fc_field_email',     'email' ),
                'firstname' => get_option( 'ws_brevo_fc_field_firstname', 'firstname' ),
                'lastname'  => get_option( 'ws_brevo_fc_field_lastname',  'lastname' ),
                'phone'     => get_option( 'ws_brevo_fc_field_phone',     'phone' ),
                'company'   => get_option( 'ws_brevo_fc_field_company',   'company' ),
            ),
        ) );
    }

    /**
     * Universal AJAX endpoint — logged-in and logged-out users.
     *
     * Expected POST params:
     *   action    — ws_brevo_fc_submit (required)
     *   nonce     — from wsBrevoFCPublic.nonce (required)
     *   email     — email address (required)
     *   firstname — first name (optional)
     *   lastname  — last name (optional)
     *   phone     — phone number (optional)
     *   company   — company name (optional)
     *   list_id   — Brevo list ID override (optional)
     *   form_id   — source identifier for rules & log (optional)
     *
     * JSON response:
     *   { success: true,  data: { message: '...' } }
     *   { success: false, data: { message: '...' } }
     */
    public function ajax_submit() {
        if ( ! check_ajax_referer( 'ws_brevo_fc_public', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'ws-brevo-form-connector' ) ), 403 );
        }

        $email   = sanitize_email( wp_unslash( $_POST['email']   ?? '' ) );
        $form_id = sanitize_text_field( wp_unslash( $_POST['form_id'] ?? 'ajax' ) );
        $list_id = absint( $_POST['list_id'] ?? 0 );

        // The JS already resolves field names from config and sends normalized keys.
        // Map directly to Brevo attributes — no option lookup needed here.
        $attributes = array_filter( array(
            'PRENOM'  => sanitize_text_field( wp_unslash( $_POST['firstname'] ?? '' ) ),
            'NOM'     => sanitize_text_field( wp_unslash( $_POST['lastname']  ?? '' ) ),
            'SMS'     => sanitize_text_field( wp_unslash( $_POST['phone']     ?? '' ) ),
            'SOCIETE' => sanitize_text_field( wp_unslash( $_POST['company']   ?? '' ) ),
        ) );

        $result = WS_Brevo_FC_Sync::contact( $email, $attributes, $list_id, $form_id );

        if ( $result['ok'] ) {
            wp_send_json_success( array( 'message' => __( 'Contact synced.', 'ws-brevo-form-connector' ) ) );
        } else {
            wp_send_json_error( array( 'message' => $result['msg'] ), 400 );
        }
    }
}
