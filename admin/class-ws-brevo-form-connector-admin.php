<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Brevo_FC_Admin {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    public function plugin_action_links( $links ) {
        $custom = array(
            '<a href="https://plugins.wordpress-freelance.com" target="_blank" rel="noopener">' . __( 'More plugins', 'ws-brevo-form-connector' ) . '</a>',
            '<a href="' . admin_url( 'admin.php?page=' . $this->plugin_name ) . '">' . __( 'Settings', 'ws-brevo-form-connector' ) . '</a>',
        );
        return array_merge( $custom, $links );
    }

    public function enqueue_styles( $hook ) {
        if ( strpos( $hook, 'ws-brevo-form-connector' ) === false ) return;
        wp_enqueue_style(
            $this->plugin_name . '-fonts',
            'https://fonts.googleapis.com/css2?family=Lora:wght@500;600&family=Inter:wght@400;500&display=swap',
            array(), null
        );
        wp_enqueue_style(
            $this->plugin_name,
            WS_BREVO_FC_PLUGIN_URL . 'admin/css/ws-brevo-form-connector-admin.css',
            array(), $this->version
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( strpos( $hook, 'ws-brevo-form-connector' ) === false ) return;
        wp_enqueue_script(
            $this->plugin_name,
            WS_BREVO_FC_PLUGIN_URL . 'admin/js/ws-brevo-form-connector-admin.js',
            array( 'jquery' ), $this->version, true
        );
        wp_localize_script( $this->plugin_name, 'wsBrevoFC', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ws_brevo_fc_nonce' ),
            'i18n'    => array(
                'testOk'      => __( 'Connection successful', 'ws-brevo-form-connector' ),
                'testFail'    => __( 'Connection failed', 'ws-brevo-form-connector' ),
                'clearConfirm'=> __( 'Clear the sync log?', 'ws-brevo-form-connector' ),
                'testing'     => __( 'Testing…', 'ws-brevo-form-connector' ),
                'testBtn'     => __( 'Test connection', 'ws-brevo-form-connector' ),
            ),
        ) );
    }

    public function add_plugin_admin_menu() {
        add_menu_page(
            __( 'WS Brevo Form Connector', 'ws-brevo-form-connector' ),
            __( 'Brevo Connector', 'ws-brevo-form-connector' ),
            'manage_options',
            $this->plugin_name,
            array( $this, 'display_plugin_admin_page' ),
            'dashicons-email-alt',
            56
        );
    }

    public function add_admin_body_class( $classes ) {
        $screen = get_current_screen();
        if ( $screen && strpos( $screen->id, 'ws-brevo-form-connector' ) !== false ) {
            $classes .= ' ws-brevo-fc-page';
        }
        return $classes;
    }

    public function inline_reset_css() {
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'ws-brevo-form-connector' ) === false ) return;
        // Fix Avada and competing themes that inject white backgrounds into admin pages
        // IMPORTANT: never reset margin on #wpcontent — WordPress uses margin-left to push
        // content past the sidebar. Only reset background and padding.
        echo '<style>
        .ws-brevo-fc-page #wpwrap,
        .ws-brevo-fc-page #wpcontent,
        .ws-brevo-fc-page #wpbody,
        .ws-brevo-fc-page #wpbody-content { background: #14121C !important; }
        .ws-brevo-fc-page #wpbody,
        .ws-brevo-fc-page #wpbody-content { padding: 0 !important; }
        .ws-brevo-fc-page .wrap,
        .ws-brevo-fc-page #wpcontent .wrap { margin: 0 !important; padding: 0 !important; background: #14121C !important; max-width: none !important; }
        </style>';
    }

    public function display_plugin_admin_page() {
        require_once WS_BREVO_FC_PLUGIN_DIR . 'admin/partials/ws-brevo-form-connector-admin-display.php';
    }

    public function save_settings() {
        check_admin_referer( 'ws_brevo_fc_save_settings' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( __( 'Access denied.', 'ws-brevo-form-connector' ) );

        update_option( 'ws_brevo_fc_api_key',         sanitize_text_field( $_POST['ws_brevo_fc_api_key']         ?? '' ) );
        update_option( 'ws_brevo_fc_default_list_id', absint( $_POST['ws_brevo_fc_default_list_id']              ?? 0 ) );
        update_option( 'ws_brevo_fc_trigger_field',   sanitize_key( $_POST['ws_brevo_fc_trigger_field']          ?? 'ws-brevo-sync' ) );
        update_option( 'ws_brevo_fc_field_email',     sanitize_text_field( $_POST['ws_brevo_fc_field_email']      ?? 'email' ) );
        update_option( 'ws_brevo_fc_field_firstname', sanitize_text_field( $_POST['ws_brevo_fc_field_firstname']  ?? 'firstname' ) );
        update_option( 'ws_brevo_fc_field_lastname',  sanitize_text_field( $_POST['ws_brevo_fc_field_lastname']   ?? 'lastname' ) );
        update_option( 'ws_brevo_fc_field_phone',     sanitize_text_field( $_POST['ws_brevo_fc_field_phone']      ?? 'phone' ) );
        update_option( 'ws_brevo_fc_field_company',   sanitize_text_field( $_POST['ws_brevo_fc_field_company']    ?? 'company' ) );

        // Per-source routing rules
        $rules     = array();
        $raw_ids   = $_POST['form_rule_id']      ?? array();
        $raw_lists = $_POST['form_rule_list_id'] ?? array();
        $raw_act   = $_POST['form_rule_active']  ?? array();

        foreach ( $raw_ids as $i => $fid ) {
            $fid = sanitize_text_field( $fid );
            if ( $fid === '' ) continue;
            $rules[] = array(
                'form_id' => $fid,
                'list_id' => absint( $raw_lists[ $i ] ?? 0 ),
                'active'  => isset( $raw_act[ $i ] ) ? 1 : 0,
            );
        }

        update_option( 'ws_brevo_fc_form_rules', wp_json_encode( $rules ) );

        wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_name . '&saved=1' ) );
        exit;
    }

    public function ajax_test_api() {
        check_ajax_referer( 'ws_brevo_fc_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Access denied.', 'ws-brevo-form-connector' ) );
        }

        $api_key = get_option( 'ws_brevo_fc_api_key', '' );
        if ( empty( $api_key ) ) {
            wp_send_json_error( __( 'API key not configured.', 'ws-brevo-form-connector' ) );
        }

        $response = wp_remote_get( 'https://api.brevo.com/v3/account', array(
            'headers' => array( 'api-key' => $api_key ),
            'timeout' => 10,
        ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 200 && ! empty( $body['email'] ) ) {
            wp_send_json_success( array(
                'email'   => $body['email'],
                'company' => $body['companyName'] ?? '',
                'plan'    => $body['plan'][0]['type'] ?? 'N/A',
            ) );
        } else {
            wp_send_json_error( $body['message'] ?? ( 'HTTP ' . $code ) );
        }
    }

    public function ajax_clear_log() {
        check_ajax_referer( 'ws_brevo_fc_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Access denied.', 'ws-brevo-form-connector' ) );
        }
        update_option( 'ws_brevo_fc_sync_log', '[]' );
        wp_send_json_success();
    }
}
