<?php
declare(strict_types=1);

namespace WsBrevoFC\Tests\Unit;

use WP_Mock\Tools\TestCase;
use WP_Mock;

/**
 * Tests for WS_Brevo_FC_Public.
 *
 * Covers: enqueue_scripts(), ajax_submit() — nonce failure, missing email, success.
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

    public function test_enqueue_scripts_registers_public_js() {
        WP_Mock::userFunction('wp_enqueue_script', [
            'args'  => [
                'ws-brevo-form-connector',
                \WP_Mock\Functions\AnyValue::class,
                [],
                '1.4.3',
                true,
            ],
            'times' => 1,
        ]);
        WP_Mock::userFunction('wp_create_nonce', [
            'args'   => ['ws_brevo_fc_public'],
            'return' => 'test-nonce-123',
        ]);
        WP_Mock::userFunction('admin_url', ['return' => 'https://example.com/wp-admin/admin-ajax.php']);
        WP_Mock::userFunction('get_option', ['return' => '']);
        WP_Mock::userFunction('wp_localize_script', ['times' => 1]);

        $this->public->enqueue_scripts();

        $this->assertConditionsMet();
    }

    public function test_enqueue_scripts_localizes_trigger_field_from_options() {
        WP_Mock::userFunction('wp_enqueue_script', ['return' => true]);
        WP_Mock::userFunction('wp_create_nonce', ['return' => 'nonce']);
        WP_Mock::userFunction('admin_url',        ['return' => 'https://example.com/wp-admin/admin-ajax.php']);

        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_trigger_field', 'ws-brevo-sync'],
            'return' => 'my-custom-trigger',
        ]);
        WP_Mock::userFunction('get_option', ['return' => '']);

        $localized_data = null;
        WP_Mock::userFunction('wp_localize_script', [
            'return' => function($handle, $object, $data) use (&$localized_data) {
                $localized_data = $data;
            },
        ]);

        $this->public->enqueue_scripts();

        $this->assertSame('my-custom-trigger', $localized_data['triggerField'] ?? null);
    }

    public function test_enqueue_scripts_passes_field_mapping_to_js() {
        WP_Mock::userFunction('wp_enqueue_script', ['return' => true]);
        WP_Mock::userFunction('wp_create_nonce', ['return' => 'nonce']);
        WP_Mock::userFunction('admin_url',        ['return' => 'https://example.com/wp-admin/admin-ajax.php']);
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

        $localized_data = null;
        WP_Mock::userFunction('wp_localize_script', [
            'return' => function($handle, $object, $data) use (&$localized_data) {
                $localized_data = $data;
            },
        ]);

        $this->public->enqueue_scripts();

        $this->assertSame('your-email', $localized_data['fields']['email'] ?? null);
        $this->assertSame(3, $localized_data['listId'] ?? null);
    }

    // =========================================================================
    // ajax_submit()
    // =========================================================================

    public function test_ajax_submit_sends_403_when_nonce_is_invalid() {
        WP_Mock::userFunction('check_ajax_referer', [
            'args'   => ['ws_brevo_fc_public', 'nonce', false],
            'return' => false,
        ]);
        WP_Mock::userFunction('__', [
            'return' => function($text) { return $text; },
        ]);
        WP_Mock::userFunction('wp_send_json_error', [
            'args'  => [['message' => 'Invalid nonce.'], 403],
            'times' => 1,
        ]);

        $this->public->ajax_submit();

        $this->assertConditionsMet();
    }

    public function test_ajax_submit_sends_error_when_email_is_empty() {
        $_POST = ['email' => '', 'nonce' => 'valid'];

        WP_Mock::userFunction('check_ajax_referer', ['return' => true]);
        WP_Mock::userFunction('wp_unslash',        ['return' => function($v) { return $v; }]);
        WP_Mock::userFunction('sanitize_email',    ['return' => '']);
        WP_Mock::userFunction('sanitize_text_field',['return' => function($v) { return $v; }]);
        // Sync class returns error for invalid email
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_api_key', ''],
            'return' => 'xkeysib-test',
        ]);
        WP_Mock::userFunction('is_email', ['return' => false]);
        WP_Mock::userFunction('wp_send_json_error', ['times' => 1]);

        $this->public->ajax_submit();

        $this->assertConditionsMet();

        $_POST = [];
    }

    public function test_ajax_submit_calls_sync_and_sends_success() {
        $_POST = [
            'email'     => 'john@example.com',
            'firstname' => 'John',
            'lastname'  => 'Doe',
            'phone'     => '',
            'company'   => '',
            'form_id'   => 'my-form',
            'nonce'     => 'valid',
        ];

        WP_Mock::userFunction('check_ajax_referer', ['return' => true]);
        WP_Mock::userFunction('wp_unslash', [
            'return' => function($v) { return $v; },
        ]);
        WP_Mock::userFunction('sanitize_email',     ['return' => 'john@example.com']);
        WP_Mock::userFunction('sanitize_text_field',['return' => function($v) { return $v; }]);

        // WS_Brevo_FC_Sync::contact() internals
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
        WP_Mock::userFunction('wp_date',       ['return' => '07/05/2026 10:00:00']);
        WP_Mock::userFunction('update_option', ['return' => true]);
        WP_Mock::expectAction('ws_brevo_fc_after_sync');

        WP_Mock::userFunction('__', [
            'return' => function($text) { return $text; },
        ]);
        WP_Mock::userFunction('wp_send_json_success', [
            'args'  => [['message' => 'Contact synced.']],
            'times' => 1,
        ]);

        $this->public->ajax_submit();

        $this->assertConditionsMet();

        $_POST = [];
    }
}
