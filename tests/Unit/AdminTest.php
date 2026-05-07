<?php
namespace WsBrevoFC\Tests\Unit;

use WP_Mock;
use WP_Mock\Tools\TestCase;
use WS_Brevo_FC_Admin;

class AdminTest extends TestCase {

    private WS_Brevo_FC_Admin $admin;

    public function setUp(): void {
        parent::setUp();
        $this->admin = new WS_Brevo_FC_Admin( 'ws-brevo-form-connector', '1.4.3' );
    }

    // -------------------------------------------------------------------------
    // plugin_action_links()
    // -------------------------------------------------------------------------

    public function test_plugin_action_links_prepends_settings_and_more_plugins(): void {
        WP_Mock::userFunction( 'admin_url' )
            ->with( 'admin.php?page=ws-brevo-form-connector' )
            ->andReturn( 'http://example.com/wp-admin/admin.php?page=ws-brevo-form-connector' );

        WP_Mock::userFunction( '__' )
            ->andReturnArg( 0 );

        $original = [ '<a href="#">Deactivate</a>' ];
        $result   = $this->admin->plugin_action_links( $original );

        $this->assertCount( 3, $result );
        $this->assertStringContainsString( 'plugins.wordpress-freelance.com', $result[0] );
        $this->assertStringContainsString( 'wp-admin', $result[1] );
        $this->assertSame( $original[0], $result[2] );
    }

    public function test_plugin_action_links_opens_more_plugins_in_new_tab(): void {
        WP_Mock::userFunction( 'admin_url' )->andReturn( 'http://example.com/wp-admin/' );
        WP_Mock::userFunction( '__' )->andReturnArg( 0 );

        $result = $this->admin->plugin_action_links( [] );

        $this->assertStringContainsString( 'target="_blank"', $result[0] );
    }

    public function test_plugin_action_links_preserves_original_links(): void {
        WP_Mock::userFunction( 'admin_url' )->andReturn( 'http://example.com/' );
        WP_Mock::userFunction( '__' )->andReturnArg( 0 );

        $original = [ '<a>Edit</a>', '<a>Deactivate</a>' ];
        $result   = $this->admin->plugin_action_links( $original );

        $this->assertContains( '<a>Edit</a>',       $result );
        $this->assertContains( '<a>Deactivate</a>', $result );
    }

    // -------------------------------------------------------------------------
    // add_admin_body_class()
    // -------------------------------------------------------------------------

    public function test_add_admin_body_class_appends_class_on_plugin_page(): void {
        $screen     = \Mockery::mock( 'WP_Screen' );
        $screen->id = 'toplevel_page_ws-brevo-form-connector';

        WP_Mock::userFunction( 'get_current_screen' )->andReturn( $screen );

        $result = $this->admin->add_admin_body_class( 'wp-admin' );

        $this->assertStringContainsString( 'ws-brevo-fc-page', $result );
    }

    public function test_add_admin_body_class_does_not_append_class_on_other_page(): void {
        $screen     = \Mockery::mock( 'WP_Screen' );
        $screen->id = 'edit-post';

        WP_Mock::userFunction( 'get_current_screen' )->andReturn( $screen );

        $result = $this->admin->add_admin_body_class( 'wp-admin' );

        $this->assertStringNotContainsString( 'ws-brevo-fc-page', $result );
    }

    // -------------------------------------------------------------------------
    // ajax_test_api()
    // -------------------------------------------------------------------------

    public function test_ajax_test_api_returns_error_when_api_key_missing(): void {
        WP_Mock::userFunction( 'check_ajax_referer' )
            ->with( 'ws_brevo_fc_nonce', 'nonce' )
            ->andReturn( true );
        WP_Mock::userFunction( 'current_user_can' )
            ->with( 'manage_options' )
            ->andReturn( true );
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_api_key', '' )
            ->andReturn( '' );
        WP_Mock::userFunction( '__' )->andReturnArg( 0 );
        WP_Mock::userFunction( 'wp_send_json_error' )->once();

        $this->admin->ajax_test_api();

        $this->assertConditionsMet();
    }

    public function test_ajax_test_api_returns_success_on_valid_key(): void {
        WP_Mock::userFunction( 'check_ajax_referer' )->andReturn( true );
        WP_Mock::userFunction( 'current_user_can' )->andReturn( true );
        WP_Mock::userFunction( 'get_option' )
            ->with( 'ws_brevo_fc_api_key', '' )
            ->andReturn( 'xkeysib-valid' );

        $response = [ 'response' => [ 'code' => 200 ] ];
        WP_Mock::userFunction( 'wp_remote_get' )->andReturn( $response );
        WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
        WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
        WP_Mock::userFunction( 'wp_remote_retrieve_body' )
            ->andReturn( json_encode( [ 'email' => 'owner@example.com', 'companyName' => 'ACME', 'plan' => [ [ 'type' => 'free' ] ] ] ) );
        WP_Mock::userFunction( 'wp_send_json_success' )
            ->once()
            ->with( \Mockery::on( fn( $d ) => $d['email'] === 'owner@example.com' ) );

        $this->admin->ajax_test_api();

        $this->assertConditionsMet();
    }
}
