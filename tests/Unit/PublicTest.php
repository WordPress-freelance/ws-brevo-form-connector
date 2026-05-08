<?php
declare(strict_types=1);

namespace WsBrevoFC\Tests\Unit;

use WP_Mock\Tools\TestCase;
use WP_Mock;

/**
 * Tests for WS_Brevo_FC_Public.
 */
class PublicTest extends TestCase {

    private \WS_Brevo_FC_Public $public;

    public function setUp(): void {
        parent::setUp();
        $this->public = new \WS_Brevo_FC_Public('ws-brevo-form-connector', '1.4.3');
    }

    // =========================================================================
    // enqueue_scripts()
    // =========================================================================

    public function test_enqueue_scripts_registers_public_js_in_footer() {
        WP_Mock::userFunction('wp_enqueue_script', [
            'times' => 1,
        ]);
        WP_Mock::userFunction('wp_create_nonce', ['return' => 'test-nonce']);
        WP_Mock::userFunction('admin_url',        ['return' => 'https://example.com/wp-admin/admin-ajax.php']);
        WP_Mock::userFunction('get_option',       ['return' => '']);
        WP_Mock::userFunction('wp_localize_script',['return' => true]);

        $this->public->enqueue_scripts();

        $this->assertConditionsMet();
    }

    public function test_enqueue_scripts_passes_trigger_field_to_js() {
        WP_Mock::userFunction('wp_enqueue_script', ['return' => true]);
        WP_Mock::userFunction('wp_create_nonce',   ['return' => 'nonce']);
        WP_Mock::userFunction('admin_url',          ['return' => 'https://example.com/wp-admin/admin-ajax.php']);

        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_trigger_field', 'ws-brevo-sync'],
            'return' => 'my-trigger',
        ]);
        WP_Mock::userFunction('get_option', ['return' => '']);

        $localized = null;
        WP_Mock::userFunction('wp_localize_script', [
            'return' => function($handle, $name, $data) use (&$localized) {
                $localized = $data;
                return true;
            },
        ]);

        $this->public->enqueue_scripts();

        $this->assertSame('my-trigger', $localized['triggerField'] ?? null);
    }

    public function test_enqueue_scripts_passes_field_mapping_to_js() {
        WP_Mock::userFunction('wp_enqueue_script', ['return' => true]);
        WP_Mock::userFunction('wp_create_nonce',   ['return' => 'nonce']);
        WP_Mock::userFunction('admin_url',          ['return' => 'https://example.com/wp-admin/admin-ajax.php']);

        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_trigger_field', 'ws-brevo-sync'],
            'return' => 'ws-brevo-sync',
        ]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_default_list_id', 0],
            'return' => 3,
        ]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_field_email', 'email'],
            'return' => 'your-email',
        ]);
        WP_Mock::userFunction('get_option', ['return' => '']);

        $localized = null;
        WP_Mock::userFunction('wp_localize_script', [
            'return' => function($h, $n, $data) use (&$localized) {
                $localized = $data;
                return true;
            },
        ]);

        $this->public->enqueue_scripts();

        $this->assertSame('your-email', $localized['fields']['email'] ?? null);
        $this->assertSame(3, $localized['listId'] ?? null);
    }

    // =========================================================================
    // ajax_submit()
    // =========================================================================

    public function test_ajax_submit_sends_403_on_invalid_nonce() {
        WP_Mock::userFunction('check_ajax_referer', [
            'args'   => ['ws_brevo_fc_public', 'nonce', false],
            'return' => false,
        ]);
        WP_Mock::userFunction('__', ['return' => function($t) { return $t; }]);
        WP_Mock::userFunction('wp_send_json_error', [
            'args'  => [['message' => 'Invalid nonce.'], 403],
            'times' => 1,
        ]);

        $this->public->ajax_submit();

        $this->assertConditionsMet();
    }

    public function test_ajax_submit_sends_error_on_empty_email() {
        $_POST = ['email' => '', 'nonce' => 'valid', 'form_id' => 'test'];

        WP_Mock::userFunction('check_ajax_referer', ['return' => true]);
        WP_Mock::userFunction('wp_unslash',         ['return' => function($v) { return $v; }]);
        WP_Mock::userFunction('sanitize_email',     ['return' => '']);
        WP_Mock::userFunction('sanitize_text_field',['return' => function($v) { return $v; }]);
        // Sync fails on invalid email
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_api_key', ''],
            'return' => 'xkeysib-test',
        ]);
        WP_Mock::userFunction('is_email', ['return' => false]);
        WP_Mock::userFunction('__',       ['return' => function($t) { return $t; }]);
        WP_Mock::userFunction('wp_send_json_error', ['times' => 1]);

        $this->public->ajax_submit();

        $this->assertConditionsMet();

        $_POST = [];
    }

    public function test_ajax_submit_sends_success_on_valid_submission() {
        $_POST = [
            'email'     => 'john@example.com',
            'firstname' => 'John',
            'lastname'  => 'Doe',
            'phone'     => '',
            'company'   => '',
            'form_id'   => 'my-form',
            'list_id'   => '0',
            'nonce'     => 'valid',
        ];

        WP_Mock::userFunction('check_ajax_referer', ['return' => true]);
        WP_Mock::userFunction('wp_unslash',         ['return' => function($v) { return $v; }]);
        WP_Mock::userFunction('sanitize_email',     ['return' => 'john@example.com']);
        WP_Mock::userFunction('sanitize_text_field',['return' => function($v) { return $v; }]);

        // Sync class internals
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_api_key', ''],
            'return' => 'xkeysib-test',
        ]);
        WP_Mock::userFunction('is_email', ['return' => true]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_form_rules', '[]'],
            'return' => '[]',
        ]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_default_list_id', 0],
            'return' => 3,
        ]);
        WP_Mock::userFunction('wp_json_encode', ['return' => '{}']);
        WP_Mock::userFunction('wp_remote_post', ['return' => ['body' => '']]);
        WP_Mock::userFunction('is_wp_error',    ['return' => false]);
        WP_Mock::userFunction('wp_remote_retrieve_response_code', ['return' => 201]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_sync_log', '[]'],
            'return' => '[]',
        ]);
        WP_Mock::userFunction('wp_date',        ['return' => '07/05/2026 10:00:00']);
        WP_Mock::userFunction('update_option',  ['return' => true]);
        WP_Mock::userFunction('do_action', ['return' => null]);

        WP_Mock::userFunction('__', ['return' => function($t) { return $t; }]);
        WP_Mock::userFunction('wp_send_json_success', [
            'args'  => [['message' => 'Contact synced.']],
            'times' => 1,
        ]);

        $this->public->ajax_submit();

        $this->assertConditionsMet();

        $_POST = [];
    }
}
