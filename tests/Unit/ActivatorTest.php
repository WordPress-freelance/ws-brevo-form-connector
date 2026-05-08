<?php
declare(strict_types=1);

namespace WsBrevoFC\Tests\Unit;

use WP_Mock\Tools\TestCase;
use WP_Mock;

/**
 * Tests for WS_Brevo_FC_Activator.
 */
class ActivatorTest extends TestCase {

    public function test_activate_calls_add_option_for_each_default_option() {
        // All options absent on fresh install
        WP_Mock::userFunction('get_option', [
            'return' => false,
        ]);

        // add_option must be called — capture all calls
        $calls = [];
        WP_Mock::userFunction('add_option', [
            'return' => function( $key, $value, $deprecated, $autoload ) use ( &$calls ) {
                $calls[] = ['key' => $key, 'value' => $value, 'autoload' => $autoload];
                return true;
            },
        ]);

        \WS_Brevo_FC_Activator::activate();

        $expected_options = [
            'ws_brevo_fc_api_key',
            'ws_brevo_fc_default_list_id',
            'ws_brevo_fc_trigger_field',
            'ws_brevo_fc_field_email',
            'ws_brevo_fc_field_firstname',
            'ws_brevo_fc_field_lastname',
            'ws_brevo_fc_field_phone',
            'ws_brevo_fc_field_company',
            'ws_brevo_fc_form_rules',
            'ws_brevo_fc_sync_log',
            'ws_brevo_fc_db_version',
        ];

        $registered = array_column($calls, 'key');
        foreach ($expected_options as $opt) {
            $this->assertContains($opt, $registered, "Missing option: $opt");
        }
        $this->assertCount(count($expected_options), $calls);
    }

    public function test_activate_skips_options_that_already_exist() {
        WP_Mock::userFunction('get_option', [
            'return' => 'already-set',
        ]);

        $called = false;
        WP_Mock::userFunction('add_option', [
            'return' => function() use (&$called) { $called = true; return true; },
        ]);

        \WS_Brevo_FC_Activator::activate();

        $this->assertFalse($called, 'add_option must not be called when options already exist');
    }

    public function test_activate_sets_trigger_field_default_to_ws_brevo_sync() {
        WP_Mock::userFunction('get_option', ['return' => false]);

        $trigger_value = null;
        WP_Mock::userFunction('add_option', [
            'return' => function($key, $value) use (&$trigger_value) {
                if ($key === 'ws_brevo_fc_trigger_field') {
                    $trigger_value = $value;
                }
                return true;
            },
        ]);

        \WS_Brevo_FC_Activator::activate();

        $this->assertSame('ws-brevo-sync', $trigger_value);
    }

    public function test_activate_uses_autoload_false_on_every_option() {
        WP_Mock::userFunction('get_option', ['return' => false]);

        $violations = [];
        WP_Mock::userFunction('add_option', [
            'return' => function($key, $value, $deprecated, $autoload) use (&$violations) {
                if ($autoload !== false) {
                    $violations[] = $key;
                }
                return true;
            },
        ]);

        \WS_Brevo_FC_Activator::activate();

        $this->assertEmpty(
            $violations,
            'These options have autoload !== false: ' . implode(', ', $violations)
        );
    }
}
