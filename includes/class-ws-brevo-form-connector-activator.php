<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Brevo_FC_Activator {

    public static function activate() {
        // Default option values — autoload disabled (false as 4th argument)
        $defaults = array(
            'ws_brevo_fc_api_key'         => '',
            'ws_brevo_fc_default_list_id' => '',
            'ws_brevo_fc_trigger_field'   => 'ws-brevo-sync',
            'ws_brevo_fc_field_email'     => 'email',
            'ws_brevo_fc_field_firstname' => 'firstname',
            'ws_brevo_fc_field_lastname'  => 'lastname',
            'ws_brevo_fc_field_phone'     => 'phone',
            'ws_brevo_fc_field_company'   => 'company',
            'ws_brevo_fc_form_rules'      => '[]',
            'ws_brevo_fc_sync_log'        => '[]',
            'ws_brevo_fc_db_version'      => '1.3.0',
        );
        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value, '', false );
            }
        }
    }
}
