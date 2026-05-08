<?php
declare(strict_types=1);

namespace WsBrevoFC\Tests\Unit;

use WP_Mock\Tools\TestCase;
use WP_Mock;

/**
 * Tests for WS_Brevo_FC_Admin.
 */
class AdminTest extends TestCase {

    private \WS_Brevo_FC_Admin $admin;

    public function setUp(): void {
        parent::setUp();
        $this->admin = new \WS_Brevo_FC_Admin('ws-brevo-form-connector', '1.4.3');
    }

    // =========================================================================
    // plugin_action_links()
    // =========================================================================

    public function test_plugin_action_links_prepends_settings_and_more_plugins() {
        WP_Mock::userFunction('admin_url', [
            'return' => 'https://example.com/wp-admin/admin.php?page=ws-brevo-form-connector',
        ]);
        WP_Mock::userFunction('__', [
            'return' => function($text) { return $text; },
        ]);

        $result = $this->admin->plugin_action_links(['<a href="#">Deactivate</a>']);

        $this->assertCount(3, $result);
        $this->assertStringContainsString('plugin.wordpress-freelance.com', $result[0]);
        $this->assertStringContainsString('Settings', $result[1]);
        $this->assertStringContainsString('Deactivate', $result[2]);
    }

    public function test_plugin_action_links_more_plugins_opens_in_new_tab() {
        WP_Mock::userFunction('admin_url', ['return' => 'https://example.com/wp-admin/']);
        WP_Mock::userFunction('__', ['return' => function($text) { return $text; }]);

        $result = $this->admin->plugin_action_links([]);

        $this->assertStringContainsString('target="_blank"', $result[0]);
        $this->assertStringContainsString('rel="noopener"', $result[0]);
    }

    public function test_plugin_action_links_settings_points_to_plugin_page() {
        WP_Mock::userFunction('admin_url', [
            'return' => 'https://example.com/wp-admin/admin.php?page=ws-brevo-form-connector',
        ]);
        WP_Mock::userFunction('__', ['return' => function($text) { return $text; }]);

        $result = $this->admin->plugin_action_links([]);

        $this->assertStringContainsString('page=ws-brevo-form-connector', $result[1]);
    }

    // =========================================================================
    // add_admin_body_class()
    // =========================================================================

    public function test_add_admin_body_class_appends_class_on_plugin_screen() {
        // Use a real WP_Screen stub (defined in bootstrap.php)
        $screen     = new \WP_Screen();
        $screen->id = 'toplevel_page_ws-brevo-form-connector';

        WP_Mock::userFunction('get_current_screen', ['return' => $screen]);

        $result = $this->admin->add_admin_body_class('existing-class');

        $this->assertStringContainsString('ws-brevo-fc-page', $result);
        $this->assertStringContainsString('existing-class', $result);
    }

    public function test_add_admin_body_class_unchanged_on_other_screens() {
        $screen     = new \WP_Screen();
        $screen->id = 'edit-post';

        WP_Mock::userFunction('get_current_screen', ['return' => $screen]);

        $result = $this->admin->add_admin_body_class('existing-class');

        $this->assertStringNotContainsString('ws-brevo-fc-page', $result);
        $this->assertSame('existing-class', $result);
    }

    public function test_add_admin_body_class_safe_when_no_screen() {
        WP_Mock::userFunction('get_current_screen', ['return' => null]);

        $result = $this->admin->add_admin_body_class('existing-class');

        $this->assertSame('existing-class', $result);
    }

    // =========================================================================
    // ajax_test_api()
    // =========================================================================

    public function test_ajax_test_api_sends_error_when_nonce_invalid() {
        WP_Mock::userFunction('check_ajax_referer', [
            'args'   => ['ws_brevo_fc_nonce', 'nonce'],
            'return' => false,
        ]);
        WP_Mock::userFunction('current_user_can', ['return' => false]);
        WP_Mock::userFunction('__', ['return' => function($t) { return $t; }]);
        WP_Mock::userFunction('wp_send_json_error', ['times' => 1]);

        $this->admin->ajax_test_api();

        $this->assertConditionsMet();
    }

    public function test_ajax_test_api_sends_error_when_api_key_missing() {
        WP_Mock::userFunction('check_ajax_referer', ['return' => true]);
        WP_Mock::userFunction('current_user_can',   ['return' => true]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_api_key', ''],
            'return' => '',
        ]);
        WP_Mock::userFunction('__', ['return' => function($t) { return $t; }]);
        WP_Mock::userFunction('wp_send_json_error', [
            'args'  => ['API key not configured.'],
            'times' => 1,
        ]);

        $this->admin->ajax_test_api();

        $this->assertConditionsMet();
    }

    public function test_ajax_test_api_sends_success_on_valid_brevo_response() {
        WP_Mock::userFunction('check_ajax_referer', ['return' => true]);
        WP_Mock::userFunction('current_user_can',   ['return' => true]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_api_key', ''],
            'return' => 'xkeysib-real-key',
        ]);

        $body = json_encode(['email' => 'admin@acme.com', 'companyName' => 'ACME', 'plan' => [['type' => 'free']]]);
        WP_Mock::userFunction('wp_remote_get',                    ['return' => ['body' => $body]]);
        WP_Mock::userFunction('is_wp_error',                      ['return' => false]);
        WP_Mock::userFunction('wp_remote_retrieve_response_code', ['return' => 200]);
        WP_Mock::userFunction('wp_remote_retrieve_body',          ['return' => $body]);

        WP_Mock::userFunction('wp_send_json_success', [
            'args'  => [['email' => 'admin@acme.com', 'company' => 'ACME', 'plan' => 'free']],
            'times' => 1,
        ]);

        $this->admin->ajax_test_api();

        $this->assertConditionsMet();
    }
}
