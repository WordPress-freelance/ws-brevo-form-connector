<?php
declare(strict_types=1);

namespace WsBrevoFC\Tests\Unit;

use WP_Mock\Tools\TestCase;
use WP_Mock;

/**
 * Tests for WS_Brevo_FC_Activator.
 */
class ActivatorTest extends TestCase {

    public function test_activate_registers_all_default_options() {
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

        // All options are absent on a fresh install
        WP_Mock::userFunction('get_option', [
            'return' => false,
            'times'  => count($expected_options),
        ]);

        // Each add_option must be called with autoload = false
        foreach ($expected_options as $option) {
            WP_Mock::userFunction('add_option', [
                'args'   => [$option, \WP_Mock\Functions\AnyValue::class, '', false],
                'return' => true,
                'times'  => 1,
            ]);
        }

        \WS_Brevo_FC_Activator::activate();

        $this->assertConditionsMet();
    }

    public function test_activate_skips_options_that_already_exist() {
        // get_option returns a non-false value → option already exists
        WP_Mock::userFunction('get_option', [
            'return' => 'existing-value',
        ]);

        // add_option must NOT be called at all
        WP_Mock::userFunction('add_option', [
            'times' => 0,
        ]);

        \WS_Brevo_FC_Activator::activate();

        $this->assertConditionsMet();
    }

    public function test_activate_sets_correct_default_for_trigger_field() {
        // Simulate all options absent except ws_brevo_fc_trigger_field
        WP_Mock::userFunction('get_option', [
            'return' => false,
        ]);

        $trigger_default_set = false;

        WP_Mock::userFunction('add_option', [
            'return' => function($key, $value, $deprecated, $autoload) use (&$trigger_default_set) {
                if ($key === 'ws_brevo_fc_trigger_field') {
                    $trigger_default_set = ($value === 'ws-brevo-sync' && $autoload === false);
                }
                return true;
            },
        ]);

        \WS_Brevo_FC_Activator::activate();

        $this->assertTrue($trigger_default_set, 'Trigger field default should be "ws-brevo-sync" with autoload=false');
    }

    public function test_activate_sets_autoload_false_on_all_options() {
        WP_Mock::userFunction('get_option', ['return' => false]);

        $autoload_violations = [];

        WP_Mock::userFunction('add_option', [
            'return' => function($key, $value, $deprecated, $autoload) use (&$autoload_violations) {
                if ($autoload !== false) {
                    $autoload_violations[] = $key;
                }
                return true;
            },
        ]);

        \WS_Brevo_FC_Activator::activate();

        $this->assertEmpty(
            $autoload_violations,
            'These options have autoload != false: ' . implode(', ', $autoload_violations)
        );
    }
}
