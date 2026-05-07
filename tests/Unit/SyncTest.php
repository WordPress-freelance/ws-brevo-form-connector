<?php
namespace WsBrevoFC\Tests\Unit;

use WP_Mock;
use WP_Mock\Tools\TestCase;
use WS_Brevo_FC_Sync;

/**
 * Tests for WS_Brevo_FC_Sync.
 *
 * Covers: contact(), resolve_list(), push() (via contact()), log() (via contact()).
 */
class SyncTest extends TestCase {

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Mocks the most common get_option calls in a single test run.
     * Pass an array of [ option_name => return_value ] pairs to override defaults.
     */
    private function mockOptions( array $overrides = [] ): void {
        $defaults = [
            'ws_brevo_fc_api_key'         => 'xkeysib-test-key',
            'ws_brevo_fc_form_rules'      => '[]',
            'ws_brevo_fc_default_list_id' => 3,
            'ws_brevo_fc_sync_log'        => '[]',
        ];
        $opts = array_merge( $defaults, $overrides );

        foreach ( $opts as $key => $value ) {
            WP_Mock::userFunction( 'get_option' )
                ->with( $key, \Mockery::any() )
                ->andReturn( $value );
        }
    }

    /** Mocks a successful wp_remote_post returning $http_code. */
    private function mockRemotePost( int $http_code, string $body = '' ): void {
        $response = [ 'response' => [ 'code' => $http_code ] ];
        WP_Mock::userFunction( 'wp_remote_post' )->andReturn( $response );
        WP_Mock::userFunction( 'is_wp_error' )->with( $response )->andReturn( false );
        WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( $http_code );
        WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( $body ?: '{}' );
    }

    /** Mocks the log write (get_option + update_option + wp_date). */
    private function mockLogWrite(): void {
        WP_Mock::userFunction( 'wp_date' )->andReturn( '07/05/2026 10:00:00' );
        WP_Mock::userFunction( 'update_option' )->andReturn( true );
    }

    /** Mocks wp_json_encode to use native json_encode. */
    private function mockJsonEncode(): void {
        WP_Mock::userFunction( 'wp_json_encode' )
            ->andReturnUsing( fn( $data ) => json_encode( $data ) );
    }

    /** Mocks sanitize_email + is_email for a valid address. */
    private function mockValidEmail( string $email ): void {
        WP_Mock::userFunction( 'sanitize_email' )->andReturn( $email );
        WP_Mock::userFunction( 'is_email' )->andReturn( true );
    }

    // -------------------------------------------------------------------------
    // contact() — guard clauses
    // -------------------------------------------------------------------------

    public function test_contact_returns_error_when_api_key_is_empty(): void {
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_api_key', '' )
            ->andReturn( '' );

        $result = WS_Brevo_FC_Sync::contact( 'test@example.com' );

        $this->assertFalse( $result['ok'] );
        $this->assertStringContainsString( 'API key', $result['msg'] );
        $this->assertSame( 0, $result['code'] );
    }

    public function test_contact_returns_error_for_invalid_email(): void {
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_api_key', '' )
            ->andReturn( 'xkeysib-test' );
        WP_Mock::userFunction( 'sanitize_email' )->andReturn( 'invalid' );
        WP_Mock::userFunction( 'is_email' )->andReturn( false );

        $result = WS_Brevo_FC_Sync::contact( 'invalid' );

        $this->assertFalse( $result['ok'] );
        $this->assertStringContainsString( 'Invalid email', $result['msg'] );
    }

    public function test_contact_returns_ok_true_when_sync_disabled_by_rule(): void {
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_api_key', '' )
            ->andReturn( 'xkeysib-test' );
        $this->mockValidEmail( 'john@example.com' );
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_form_rules', '[]' )
            ->andReturn( json_encode( [ [ 'form_id' => 'my-form', 'list_id' => 3, 'active' => 0 ] ] ) );
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_default_list_id', \Mockery::any() )
            ->andReturn( 3 );

        $result = WS_Brevo_FC_Sync::contact( 'john@example.com', [], 0, 'my-form' );

        $this->assertTrue( $result['ok'] );
        $this->assertStringContainsString( 'disabled', $result['msg'] );
    }

    // -------------------------------------------------------------------------
    // contact() — successful push
    // -------------------------------------------------------------------------

    public function test_contact_returns_ok_true_on_http_201(): void {
        $this->mockOptions();
        $this->mockValidEmail( 'john@example.com' );
        $this->mockJsonEncode();
        $this->mockRemotePost( 201 );
        $this->mockLogWrite();
        WP_Mock::userFunction( 'do_action' )->andReturn( null );

        $result = WS_Brevo_FC_Sync::contact( 'john@example.com', [ 'PRENOM' => 'John' ] );

        $this->assertTrue( $result['ok'] );
        $this->assertSame( 201, $result['code'] );
    }

    public function test_contact_returns_ok_true_on_http_204(): void {
        $this->mockOptions();
        $this->mockValidEmail( 'john@example.com' );
        $this->mockJsonEncode();
        $this->mockRemotePost( 204 );
        $this->mockLogWrite();
        WP_Mock::userFunction( 'do_action' )->andReturn( null );

        $result = WS_Brevo_FC_Sync::contact( 'john@example.com' );

        $this->assertTrue( $result['ok'] );
        $this->assertSame( 204, $result['code'] );
    }

    public function test_contact_returns_error_on_http_400(): void {
        $this->mockOptions();
        $this->mockValidEmail( 'john@example.com' );
        $this->mockJsonEncode();
        $this->mockRemotePost( 400, json_encode( [ 'message' => 'Bad request' ] ) );
        $this->mockLogWrite();
        WP_Mock::userFunction( 'do_action' )->andReturn( null );

        $result = WS_Brevo_FC_Sync::contact( 'john@example.com' );

        $this->assertFalse( $result['ok'] );
        $this->assertStringContainsString( 'Bad request', $result['msg'] );
    }

    public function test_contact_returns_error_on_wp_error(): void {
        $this->mockOptions();
        $this->mockValidEmail( 'john@example.com' );
        $this->mockJsonEncode();

        $wp_error = \Mockery::mock( 'WP_Error' );
        $wp_error->shouldReceive( 'get_error_message' )->andReturn( 'cURL error 28: timeout' );

        WP_Mock::userFunction( 'wp_remote_post' )->andReturn( $wp_error );
        WP_Mock::userFunction( 'is_wp_error' )->with( $wp_error )->andReturn( true );
        $this->mockLogWrite();
        WP_Mock::userFunction( 'do_action' )->andReturn( null );

        $result = WS_Brevo_FC_Sync::contact( 'john@example.com' );

        $this->assertFalse( $result['ok'] );
        $this->assertStringContainsString( 'timeout', $result['msg'] );
        $this->assertSame( 0, $result['code'] );
    }

    public function test_contact_fires_after_sync_action(): void {
        $this->mockOptions();
        $this->mockValidEmail( 'john@example.com' );
        $this->mockJsonEncode();
        $this->mockRemotePost( 201 );
        $this->mockLogWrite();

        WP_Mock::expectAction( 'ws_brevo_fc_after_sync' );

        WS_Brevo_FC_Sync::contact( 'john@example.com' );

        $this->assertConditionsMet();
    }

    public function test_contact_sends_list_id_when_resolved(): void {
        $this->mockOptions( [ 'ws_brevo_fc_default_list_id' => 5 ] );
        $this->mockValidEmail( 'john@example.com' );
        $this->mockJsonEncode();

        WP_Mock::userFunction( 'wp_remote_post' )
            ->once()
            ->with(
                'https://api.brevo.com/v3/contacts',
                \Mockery::on( function ( $args ) {
                    $body = json_decode( $args['body'], true );
                    return isset( $body['listIds'] ) && in_array( 5, $body['listIds'] );
                } )
            )
            ->andReturn( [] );
        WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
        WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 201 );
        WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( '{}' );
        $this->mockLogWrite();
        WP_Mock::userFunction( 'do_action' )->andReturn( null );

        WS_Brevo_FC_Sync::contact( 'john@example.com' );

        $this->assertConditionsMet();
    }

    // -------------------------------------------------------------------------
    // resolve_list()
    // -------------------------------------------------------------------------

    public function test_resolve_list_returns_global_default_when_no_rules_no_fallback(): void {
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_form_rules', '[]' )
            ->andReturn( '[]' );
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_default_list_id', 0 )
            ->andReturn( 4 );

        $result = WS_Brevo_FC_Sync::resolve_list( '' );

        $this->assertSame( 4, $result );
    }

    public function test_resolve_list_returns_fallback_when_no_matching_rule(): void {
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_form_rules', '[]' )
            ->andReturn( '[]' );

        $result = WS_Brevo_FC_Sync::resolve_list( 'some-form', 7 );

        $this->assertSame( 7, $result );
    }

    public function test_resolve_list_returns_false_when_rule_is_inactive(): void {
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_form_rules', '[]' )
            ->andReturn( json_encode( [ [ 'form_id' => 'contact', 'list_id' => 3, 'active' => 0 ] ] ) );

        $result = WS_Brevo_FC_Sync::resolve_list( 'contact' );

        $this->assertFalse( $result );
    }

    public function test_resolve_list_returns_rule_list_id_when_rule_is_active(): void {
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_form_rules', '[]' )
            ->andReturn( json_encode( [ [ 'form_id' => 'contact', 'list_id' => 9, 'active' => 1 ] ] ) );

        $result = WS_Brevo_FC_Sync::resolve_list( 'contact' );

        $this->assertSame( 9, $result );
    }

    public function test_resolve_list_falls_back_to_global_default_when_rule_list_id_is_zero(): void {
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_form_rules', '[]' )
            ->andReturn( json_encode( [ [ 'form_id' => 'contact', 'list_id' => 0, 'active' => 1 ] ] ) );
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_default_list_id', 0 )
            ->andReturn( 2 );

        $result = WS_Brevo_FC_Sync::resolve_list( 'contact' );

        $this->assertSame( 2, $result );
    }

    public function test_resolve_list_ignores_non_matching_rules(): void {
        $rules = json_encode( [ [ 'form_id' => 'other-form', 'list_id' => 9, 'active' => 1 ] ] );
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_form_rules', '[]' )
            ->andReturn( $rules );
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_default_list_id', 0 )
            ->andReturn( 1 );

        $result = WS_Brevo_FC_Sync::resolve_list( 'contact', 0 );

        $this->assertSame( 1, $result );
    }
}
