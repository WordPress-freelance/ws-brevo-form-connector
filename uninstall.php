<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Options
$options = array(
    'ws_brevo_fc_api_key',
    'ws_brevo_fc_default_list_id',
    'ws_brevo_fc_field_email',
    'ws_brevo_fc_field_firstname',
    'ws_brevo_fc_field_lastname',
    'ws_brevo_fc_field_phone',
    'ws_brevo_fc_field_company',
    'ws_brevo_fc_form_rules',
    'ws_brevo_fc_sync_log',
    'ws_brevo_fc_db_version',
);
foreach ( $options as $opt ) {
    delete_option( $opt );
}

// Transients
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ws_brevo_fc_%' OR option_name LIKE '_transient_timeout_ws_brevo_fc_%'" );
