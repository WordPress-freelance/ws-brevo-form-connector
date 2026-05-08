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

// Native stubs for simple WordPress utility functions — no need to mock these
if ( ! function_exists( 'absint' ) ) {
    function absint( $value ): int { return abs( (int) $value ); }
}
if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) { return is_array($value) ? array_map('wp_unslash', $value) : stripslashes((string) $value); }
}

// Stub WordPress classes — not provided by WP_Mock, needed for Mockery mocks
if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private string $message;
        public function __construct( string $code = '', string $message = '' ) {
            $this->message = $message;
        }
        public function get_error_message(): string { return $this->message; }
    }
}

if ( ! class_exists( 'WP_Screen' ) ) {
    class WP_Screen {
        public string $id = '';
    }
}

// Load plugin classes under test
require_once dirname(__DIR__) . '/includes/class-ws-brevo-form-connector-sync.php';
require_once dirname(__DIR__) . '/includes/class-ws-brevo-form-connector-activator.php';
require_once dirname(__DIR__) . '/includes/class-ws-brevo-form-connector-deactivator.php';
require_once dirname(__DIR__) . '/admin/class-ws-brevo-form-connector-admin.php';
require_once dirname(__DIR__) . '/public/class-ws-brevo-form-connector-public.php';
