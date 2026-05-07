<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Brevo_Form_Connector {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version     = WS_BREVO_FC_VERSION;
        $this->plugin_name = 'ws-brevo-form-connector';
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once WS_BREVO_FC_PLUGIN_DIR . 'includes/class-ws-brevo-form-connector-loader.php';
        require_once WS_BREVO_FC_PLUGIN_DIR . 'includes/class-ws-brevo-form-connector-i18n.php';
        require_once WS_BREVO_FC_PLUGIN_DIR . 'includes/class-ws-brevo-form-connector-sync.php';
        require_once WS_BREVO_FC_PLUGIN_DIR . 'admin/class-ws-brevo-form-connector-admin.php';
        require_once WS_BREVO_FC_PLUGIN_DIR . 'public/class-ws-brevo-form-connector-public.php';
        $this->loader = new WS_Brevo_FC_Loader();
    }

    private function set_locale() {
        $i18n = new WS_Brevo_FC_i18n();
        $this->loader->add_action( 'plugins_loaded', $i18n, 'load_plugin_textdomain' );
    }

    private function define_admin_hooks() {
        $admin = new WS_Brevo_FC_Admin( $this->plugin_name, $this->version );

        $this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_menu',            $admin, 'add_plugin_admin_menu' );
        $this->loader->add_filter( 'admin_body_class',      $admin, 'add_admin_body_class' );
        $this->loader->add_action( 'admin_head',            $admin, 'inline_reset_css' );

        $this->loader->add_action( 'admin_post_ws_brevo_fc_save_settings', $admin, 'save_settings' );
        $this->loader->add_action( 'wp_ajax_ws_brevo_fc_test_api',         $admin, 'ajax_test_api' );
        $this->loader->add_action( 'wp_ajax_ws_brevo_fc_clear_log',        $admin, 'ajax_clear_log' );
    }

    private function define_public_hooks() {
        $public = new WS_Brevo_FC_Public( $this->plugin_name, $this->version );

        // Enqueue public JS + localize on every frontend page (unconditionally)
        $this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_scripts' );

        // AJAX endpoint — logged-in and logged-out users
        $this->loader->add_action( 'wp_ajax_ws_brevo_fc_submit',        $public, 'ajax_submit' );
        $this->loader->add_action( 'wp_ajax_nopriv_ws_brevo_fc_submit', $public, 'ajax_submit' );
    }

    public function run() { $this->loader->run(); }

    public function get_plugin_name() { return $this->plugin_name; }
    public function get_loader()      { return $this->loader; }
    public function get_version()     { return $this->version; }
}
