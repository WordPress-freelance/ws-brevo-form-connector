<?php
namespace WsBrevoFC\Tests\Unit;

use WP_Mock;
use WP_Mock\Tools\TestCase;
use WS_Brevo_FC_Activator;

class ActivatorTest extends TestCase {

    /** Expected option defaults with their default values. */
    private function expectedOptions(): array {
        return [
            'ws_brevo_fc_api_key'         => '',
            'ws_brevo_fc_default_list_id' => '',
            'ws_brevo_fc_trigger_field'   => 'ws-brevo-sync',
            'ws_brevo_fc_field_email'     => 'email',
            'ws_brevo_fc_field_firstname' => 'firstname',
            'ws_brevo_fc_field_lastname'  => 'lastname',
            'ws_brevo_fc_field_phone'     => 'phone',
            'ws_brevo_fc_field_company'   => 'company',
            'ws_brevo_fc_form_rules'      => '[]',
            'ws_brevo_fc_sync_log'        => '[]',
            'ws_brevo_fc_db_version'      => '1.3.0',
        ];
    }

    public function test_activate_calls_add_option_for_each_default(): void {
        foreach ( $this->expectedOptions() as $key => $value ) {
            // Option does not exist yet → add_option should be called
            WP_Mock::userFunction( 'get_option' )
                ->with( $key )
                ->andReturn( false );

            // 4th argument must be false (autoload disabled)
            WP_Mock::userFunction( 'add_option' )
                ->with( $key, $value, '', false )
                ->once();
        }

        WS_Brevo_FC_Activator::activate();

        $this->assertConditionsMet();
    }

    public function test_activate_skips_add_option_when_option_already_exists(): void {
        foreach ( $this->expectedOptions() as $key => $value ) {
            // Option already exists → get_option returns non-false
            WP_Mock::userFunction( 'get_option' )
                ->with( $key )
                ->andReturn( 'some-existing-value' );
        }

        // add_option must NOT be called for any key
        WP_Mock::userFunction( 'add_option' )->never();

        WS_Brevo_FC_Activator::activate();

        $this->assertConditionsMet();
    }

    public function test_activate_uses_autoload_false_as_fourth_argument(): void {
        // Spot-check on a single option that autoload = false
        $checked = 'ws_brevo_fc_api_key';

        foreach ( $this->expectedOptions() as $key => $value ) {
            WP_Mock::userFunction( 'get_option' )->with( $key )->andReturn( false );

            if ( $key === $checked ) {
                WP_Mock::userFunction( 'add_option' )
                    ->with( $checked, $value, '', false )
                    ->once();
            } else {
                WP_Mock::userFunction( 'add_option' )
                    ->with( $key, $value, '', false )
                    ->andReturn( true );
            }
        }

        WS_Brevo_FC_Activator::activate();

        $this->assertConditionsMet();
    }
}
