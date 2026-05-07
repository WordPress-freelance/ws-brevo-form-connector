<?php
/**
 * Plugin Name:      WS Brevo Form Connector
 * Plugin URI:       https://wordpress.org/plugins/ws-brevo-form-connector/
 * Description:      Connecteur universel Brevo pour WordPress. Synchronise vos contacts via endpoint AJAX (nopriv + priv) ou appel PHP direct — indépendant de tout plugin de formulaire.
 * Version:          1.3.2
 * Author:           WebStrategy
 * Author URI:       https://wordpress-freelance.com
 * License:          GPL-2.0+
 * License URI:      http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:      ws-brevo-form-connector
 * Domain Path:      /languages
 * Requires at least: 5.6
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WS_BREVO_FC_VERSION',     '1.3.2' );
define( 'WS_BREVO_FC_PLUGIN_FILE', __FILE__ );
define( 'WS_BREVO_FC_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'WS_BREVO_FC_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

require_once WS_BREVO_FC_PLUGIN_DIR . 'includes/class-ws-brevo-form-connector-activator.php';
require_once WS_BREVO_FC_PLUGIN_DIR . 'includes/class-ws-brevo-form-connector-deactivator.php';

register_activation_hook( __FILE__, array( 'WS_Brevo_FC_Activator',   'activate' ) );
register_deactivation_hook( __FILE__, array( 'WS_Brevo_FC_Deactivator', 'deactivate' ) );

require_once WS_BREVO_FC_PLUGIN_DIR . 'includes/class-ws-brevo-form-connector.php';

function run_ws_brevo_form_connector() {
    $plugin = new WS_Brevo_Form_Connector();
    $plugin->run();
}

run_ws_brevo_form_connector();
