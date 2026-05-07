<?php
// Plugin constants required before loading classes
define( 'ABSPATH',                 dirname( __DIR__ ) . '/' );
define( 'WS_BREVO_FC_VERSION',     '1.5.0' );
define( 'WS_BREVO_FC_PLUGIN_FILE', dirname( __DIR__ ) . '/ws-brevo-form-connector.php' );
define( 'WS_BREVO_FC_PLUGIN_DIR',  dirname( __DIR__ ) . '/' );
define( 'WS_BREVO_FC_PLUGIN_URL',  'http://example.com/wp-content/plugins/ws-brevo-form-connector/' );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

WP_Mock::bootstrap();

// Load only the classes under test — not the main plugin file
require_once dirname( __DIR__ ) . '/includes/class-ws-brevo-form-connector-sync.php';
require_once dirname( __DIR__ ) . '/includes/class-ws-brevo-form-connector-activator.php';
require_once dirname( __DIR__ ) . '/includes/class-ws-brevo-form-connector-deactivator.php';
require_once dirname( __DIR__ ) . '/admin/class-ws-brevo-form-connector-admin.php';
require_once dirname( __DIR__ ) . '/public/class-ws-brevo-form-connector-public.php';
