<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

// WordPress constants required by plugin classes
define('ABSPATH',                 '/tmp/');
define('WS_BREVO_FC_VERSION',     '1.4.3');
define('WS_BREVO_FC_PLUGIN_FILE', dirname(__DIR__) . '/ws-brevo-form-connector.php');
define('WS_BREVO_FC_PLUGIN_DIR',  dirname(__DIR__) . '/');
define('WS_BREVO_FC_PLUGIN_URL',  'https://example.com/wp-content/plugins/ws-brevo-form-connector/');

WP_Mock::bootstrap();

// Load plugin classes under test — no WP bootstrap needed, WP_Mock stubs all globals
require_once dirname(__DIR__) . '/includes/class-ws-brevo-form-connector-sync.php';
require_once dirname(__DIR__) . '/includes/class-ws-brevo-form-connector-activator.php';
require_once dirname(__DIR__) . '/includes/class-ws-brevo-form-connector-deactivator.php';
require_once dirname(__DIR__) . '/admin/class-ws-brevo-form-connector-admin.php';
require_once dirname(__DIR__) . '/public/class-ws-brevo-form-connector-public.php';
