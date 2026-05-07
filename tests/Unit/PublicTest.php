<?php
namespace WsBrevoFC\Tests\Unit;

use WP_Mock;
use WP_Mock\Tools\TestCase;
use WS_Brevo_FC_Public;

class PublicTest extends TestCase {

    private WS_Brevo_FC_Public $public;

    public function setUp(): void {
        parent::setUp();
        $this->public = new WS_Brevo_FC_Public( 'ws-brevo-form-connector', '1.4.3' );
        $_POST        = [];
    }

    public function tearDown(): void {
        parent::tearDown();
        $_POST = [];
    }

    // -------------------------------------------------------------------------
    // ajax_submit() — nonce
    // -------------------------------------------------------------------------

    public function test_ajax_submit_returns_403_on_invalid_nonce(): void {
        WP_Mock::userFunction( 'check_ajax_referer' )
            ->with( 'ws_brevo_fc_public', 'nonce', false )
            ->andReturn( false );

        WP_Mock::userFunction( '__' )->andReturnArg( 0 );

        WP_Mock::userFunction( 'wp_send_json_error' )
            ->once()
            ->with( \Mockery::any(), 403 );

        $this->public->ajax_submit();

        $this->assertConditionsMet();
    }

    // -------------------------------------------------------------------------
    // ajax_submit() — field handling
    // -------------------------------------------------------------------------

    public function test_ajax_submit_syncs_contact_on_valid_payload(): void {
        $_POST = [
            'nonce'     => 'valid',
            'email'     => 'john@example.com',
            'firstname' => 'John',
            'lastname'  => 'Doe',
            'form_id'   => 'my-form',
        ];

        WP_Mock::userFunction( 'check_ajax_referer' )->andReturn( true );
        WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'sanitize_email' )->andReturn( 'john@example.com' );
        WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );

        // WS_Brevo_FC_Sync::contact() internals
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_api_key', '' )
            ->andReturn( 'xkeysib-test' );
        WP_Mock::userFunction( 'is_email' )->andReturn( true );
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_form_rules', '[]' )
            ->andReturn( '[]' );
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_default_list_id', 0 )
            ->andReturn( 3 );
        WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( 'json_encode' );
        WP_Mock::userFunction( 'wp_remote_post' )
            ->andReturn( [ 'response' => [ 'code' => 201 ] ] );
        WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
        WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 201 );
        WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( '{}' );
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_sync_log', '[]' )
            ->andReturn( '[]' );
        WP_Mock::userFunction( 'wp_date' )->andReturn( '07/05/2026 10:00:00' );
        WP_Mock::userFunction( 'update_option' )->andReturn( true );
        WP_Mock::userFunction( 'do_action' )->andReturn( null );

        WP_Mock::userFunction( '__' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'wp_send_json_success' )->once();

        $this->public->ajax_submit();

        $this->assertConditionsMet();
    }

    public function test_ajax_submit_maps_firstname_to_prenom_brevo_attribute(): void {
        $_POST = [
            'nonce'     => 'valid',
            'email'     => 'jane@example.com',
            'firstname' => 'Jane',
            'lastname'  => '',
            'form_id'   => 'test',
        ];

        WP_Mock::userFunction( 'check_ajax_referer' )->andReturn( true );
        WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'sanitize_email' )->andReturn( 'jane@example.com' );
        WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );

        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_api_key', '' )
            ->andReturn( 'xkeysib-test' );
        WP_Mock::userFunction( 'is_email' )->andReturn( true );
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_form_rules', '[]' )
            ->andReturn( '[]' );
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_default_list_id', 0 )
            ->andReturn( 3 );
        WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( 'json_encode' );

        // Assert PRENOM is in the payload
        WP_Mock::userFunction( 'wp_remote_post' )
            ->once()
            ->with(
                \Mockery::any(),
                \Mockery::on( function ( $args ) {
                    $body = json_decode( $args['body'], true );
                    return isset( $body['attributes']['PRENOM'] )
                        && $body['attributes']['PRENOM'] === 'Jane';
                } )
            )
            ->andReturn( [] );
        WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
        WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 201 );
        WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( '{}' );
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_sync_log', '[]' )
            ->andReturn( '[]' );
        WP_Mock::userFunction( 'wp_date' )->andReturn( '07/05/2026 10:00:00' );
        WP_Mock::userFunction( 'update_option' )->andReturn( true );
        WP_Mock::userFunction( 'do_action' )->andReturn( null );
        WP_Mock::userFunction( '__' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'wp_send_json_success' )->once();

        $this->public->ajax_submit();

        $this->assertConditionsMet();
    }

    public function test_ajax_submit_returns_error_when_sync_fails(): void {
        $_POST = [
            'nonce'   => 'valid',
            'email'   => 'john@example.com',
            'form_id' => 'test',
        ];

        WP_Mock::userFunction( 'check_ajax_referer' )->andReturn( true );
        WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'sanitize_email' )->andReturn( 'john@example.com' );
        WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );

        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_api_key', '' )
            ->andReturn( 'xkeysib-test' );
        WP_Mock::userFunction( 'is_email' )->andReturn( true );
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_form_rules', '[]' )
            ->andReturn( '[]' );
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_default_list_id', 0 )
            ->andReturn( 3 );
        WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( 'json_encode' );
        WP_Mock::userFunction( 'wp_remote_post' )->andReturn( [] );
        WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
        WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 500 );
        WP_Mock::userFunction( 'wp_remote_retrieve_body' )
            ->andReturn( json_encode( [ 'message' => 'Internal Server Error' ] ) );
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_sync_log', '[]' )
            ->andReturn( '[]' );
        WP_Mock::userFunction( 'wp_date' )->andReturn( '07/05/2026 10:00:00' );
        WP_Mock::userFunction( 'update_option' )->andReturn( true );
        WP_Mock::userFunction( 'do_action' )->andReturn( null );

        WP_Mock::userFunction( 'wp_send_json_error' )
            ->once()
            ->with( \Mockery::any(), 400 );

        $this->public->ajax_submit();

        $this->assertConditionsMet();
    }

    // -------------------------------------------------------------------------
    // enqueue_scripts()
    // -------------------------------------------------------------------------

    public function test_enqueue_scripts_registers_public_js(): void {
        WP_Mock::userFunction( 'get_option' )->andReturn( 'ws-brevo-sync' );
        WP_Mock::userFunction( 'admin_url' )->andReturn( 'http://example.com/wp-admin/admin-ajax.php' );
        WP_Mock::userFunction( 'wp_create_nonce' )->andReturn( 'abc123' );

        WP_Mock::userFunction( 'wp_enqueue_script' )
            ->once()
            ->with(
                'ws-brevo-form-connector',
                \Mockery::on( fn( $url ) => str_contains( $url, 'ws-brevo-form-connector-public.js' ) ),
                [],
                \Mockery::any(),
                true
            );

        WP_Mock::userFunction( 'wp_localize_script' )
            ->once()
            ->with( 'ws-brevo-form-connector', 'wsBrevoFCPublic', \Mockery::type( 'array' ) );

        $this->public->enqueue_scripts();

        $this->assertConditionsMet();
    }
}
