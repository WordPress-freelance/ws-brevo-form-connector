<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Brevo_FC_Deactivator {
    public static function deactivate() {
        // Nettoyage transients uniquement — les options sont conservées
        delete_transient( 'ws_brevo_fc_api_test' );
    }
}
