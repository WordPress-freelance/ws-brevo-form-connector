<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Brevo_FC_i18n {
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'ws-brevo-form-connector',
            false,
            dirname( dirname( plugin_basename( WS_BREVO_FC_PLUGIN_FILE ) ) ) . '/languages/'
        );
    }
}
