<?php
declare(strict_types=1);

namespace WsBrevoFC\Tests\Unit;

use WP_Mock\Tools\TestCase;
use WP_Mock;

/**
 * Tests for WS_Brevo_FC_Sync.
 *
 * Covers: resolve_list(), contact() validation, success, and API error paths.
 */
class SyncTest extends TestCase {

    // =========================================================================
    // resolve_list()
    // =========================================================================

    public function test_resolve_list_returns_global_default_when_no_rules() {
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_form_rules', '[]'],
            'return' => '[]',
        ]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_default_list_id', 0],
            'return' => 3,
        ]);

        $this->assertSame(3, \WS_Brevo_FC_Sync::resolve_list('any-form', 0));
    }

    public function test_resolve_list_returns_caller_fallback_when_no_rules() {
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_form_rules', '[]'],
            'return' => '[]',
        ]);

        $this->assertSame(7, \WS_Brevo_FC_Sync::resolve_list('any-form', 7));
    }

    public function test_resolve_list_returns_rule_list_id_on_match() {
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_form_rules', '[]'],
            'return' => json_encode([['form_id' => 'newsletter', 'list_id' => 5, 'active' => 1]]),
        ]);

        $this->assertSame(5, \WS_Brevo_FC_Sync::resolve_list('newsletter', 0));
    }

    public function test_resolve_list_returns_false_when_rule_is_inactive() {
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_form_rules', '[]'],
            'return' => json_encode([['form_id' => 'newsletter', 'list_id' => 5, 'active' => 0]]),
        ]);

        $this->assertFalse(\WS_Brevo_FC_Sync::resolve_list('newsletter', 0));
    }

    public function test_resolve_list_falls_back_to_global_default_when_rule_list_id_is_zero() {
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_form_rules', '[]'],
            'return' => json_encode([['form_id' => 'newsletter', 'list_id' => 0, 'active' => 1]]),
        ]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_default_list_id', 0],
            'return' => 2,
        ]);

        $this->assertSame(2, \WS_Brevo_FC_Sync::resolve_list('newsletter', 0));
    }

    public function test_resolve_list_ignores_non_matching_rules() {
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_form_rules', '[]'],
            'return' => json_encode([['form_id' => 'other', 'list_id' => 9, 'active' => 1]]),
        ]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_default_list_id', 0],
            'return' => 3,
        ]);

        $this->assertSame(3, \WS_Brevo_FC_Sync::resolve_list('contact', 0));
    }

    // =========================================================================
    // contact() — validation errors
    // =========================================================================

    public function test_contact_fails_when_api_key_is_empty() {
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_api_key', ''],
            'return' => '',
        ]);

        $result = \WS_Brevo_FC_Sync::contact('john@example.com');

        $this->assertFalse($result['ok']);
        $this->assertSame('API key not configured.', $result['msg']);
        $this->assertSame(0, $result['code']);
    }

    public function test_contact_fails_when_email_is_invalid() {
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_api_key', ''],
            'return' => 'xkeysib-test',
        ]);
        WP_Mock::userFunction('sanitize_email', ['return' => 'bad-email']);
        WP_Mock::userFunction('is_email',       ['return' => false]);

        $result = \WS_Brevo_FC_Sync::contact('bad-email');

        $this->assertFalse($result['ok']);
        $this->assertSame('Invalid email address.', $result['msg']);
    }

    public function test_contact_returns_ok_true_when_source_sync_is_disabled() {
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_api_key', ''],
            'return' => 'xkeysib-test',
        ]);
        WP_Mock::userFunction('sanitize_email', ['return' => 'john@example.com']);
        WP_Mock::userFunction('is_email',       ['return' => true]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_form_rules', '[]'],
            'return' => json_encode([['form_id' => 'blocked', 'list_id' => 3, 'active' => 0]]),
        ]);

        $result = \WS_Brevo_FC_Sync::contact('john@example.com', [], 0, 'blocked');

        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('disabled', strtolower($result['msg']));
    }

    // =========================================================================
    // contact() — API success (201 / 204)
    // =========================================================================

    private function mock_successful_contact(): void {
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_api_key', ''],
            'return' => 'xkeysib-test',
        ]);
        WP_Mock::userFunction('sanitize_email', ['return' => 'john@example.com']);
        WP_Mock::userFunction('is_email',       ['return' => true]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_form_rules', '[]'],
            'return' => '[]',
        ]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_default_list_id', 0],
            'return' => 3,
        ]);
        WP_Mock::userFunction('wp_json_encode', ['return' => '{"email":"john@example.com"}']);
        WP_Mock::userFunction('wp_remote_post', ['return' => ['body' => '']]);
        WP_Mock::userFunction('is_wp_error',    ['return' => false]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_sync_log', '[]'],
            'return' => '[]',
        ]);
        WP_Mock::userFunction('wp_date',       ['return' => '07/05/2026 10:00:00']);
        WP_Mock::userFunction('update_option', ['return' => true]);
        WP_Mock::expectAction('ws_brevo_fc_after_sync');
    }

    public function test_contact_returns_ok_true_on_201_response() {
        $this->mock_successful_contact();
        WP_Mock::userFunction('wp_remote_retrieve_response_code', ['return' => 201]);

        $result = \WS_Brevo_FC_Sync::contact('john@example.com', ['PRENOM' => 'John'], 0, 'test');

        $this->assertTrue($result['ok']);
        $this->assertSame(201, $result['code']);
    }

    public function test_contact_returns_ok_true_on_204_response() {
        $this->mock_successful_contact();
        WP_Mock::userFunction('wp_remote_retrieve_response_code', ['return' => 204]);

        $result = \WS_Brevo_FC_Sync::contact('john@example.com', [], 0, 'test');

        $this->assertTrue($result['ok']);
        $this->assertSame(204, $result['code']);
    }

    // =========================================================================
    // contact() — API errors
    // =========================================================================

    public function test_contact_fails_on_wp_error() {
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_api_key', ''],
            'return' => 'xkeysib-test',
        ]);
        WP_Mock::userFunction('sanitize_email', ['return' => 'john@example.com']);
        WP_Mock::userFunction('is_email',       ['return' => true]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_form_rules', '[]'],
            'return' => '[]',
        ]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_default_list_id', 0],
            'return' => 3,
        ]);
        WP_Mock::userFunction('wp_json_encode', ['return' => '{}']);

        $wp_error = \Mockery::mock('\WP_Error');
        $wp_error->shouldReceive('get_error_message')->andReturn('cURL error 6');

        WP_Mock::userFunction('wp_remote_post', ['return' => $wp_error]);
        WP_Mock::userFunction('is_wp_error',    ['return' => true]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_sync_log', '[]'],
            'return' => '[]',
        ]);
        WP_Mock::userFunction('wp_date',       ['return' => '07/05/2026 10:00:00']);
        WP_Mock::userFunction('update_option', ['return' => true]);
        WP_Mock::expectAction('ws_brevo_fc_after_sync');

        $result = \WS_Brevo_FC_Sync::contact('john@example.com', [], 0, 'test');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('cURL', $result['msg']);
        $this->assertSame(0, $result['code']);
    }

    public function test_contact_fails_on_400_response_with_brevo_error_message() {
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_api_key', ''],
            'return' => 'xkeysib-test',
        ]);
        WP_Mock::userFunction('sanitize_email', ['return' => 'john@example.com']);
        WP_Mock::userFunction('is_email',       ['return' => true]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_form_rules', '[]'],
            'return' => '[]',
        ]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_default_list_id', 0],
            'return' => 3,
        ]);
        WP_Mock::userFunction('wp_json_encode', ['return' => '{}']);
        WP_Mock::userFunction('wp_remote_post', ['return' => ['body' => '{"message":"Invalid email"}']]);
        WP_Mock::userFunction('is_wp_error',    ['return' => false]);
        WP_Mock::userFunction('wp_remote_retrieve_response_code', ['return' => 400]);
        WP_Mock::userFunction('wp_remote_retrieve_body', [
            'return' => '{"message":"Invalid email"}',
        ]);
        WP_Mock::userFunction('get_option', [
            'args'   => ['ws_brevo_fc_sync_log', '[]'],
            'return' => '[]',
        ]);
        WP_Mock::userFunction('wp_date',       ['return' => '07/05/2026 10:00:00']);
        WP_Mock::userFunction('update_option', ['return' => true]);
        WP_Mock::expectAction('ws_brevo_fc_after_sync');

        $result = \WS_Brevo_FC_Sync::contact('john@example.com', [], 0, 'test');

        $this->assertFalse($result['ok']);
        $this->assertSame(400, $result['code']);
        $this->assertSame('Invalid email', $result['msg']);
    }
}
