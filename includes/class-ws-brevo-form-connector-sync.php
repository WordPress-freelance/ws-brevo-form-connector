<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Core sync class — static, callable from anywhere.
 *
 * Direct usage from custom code:
 *   WS_Brevo_FC_Sync::contact( 'john@example.com', [
 *       'PRENOM'  => 'John',
 *       'NOM'     => 'Doe',
 *   ], 3, 'my-source' );
 */
class WS_Brevo_FC_Sync {

    /**
     * Main entry point.
     *
     * @param string $email      Email address (required).
     * @param array  $attributes Brevo attributes (PRENOM, NOM, SMS, SOCIETE…).
     * @param int    $list_id    Brevo list ID. 0 = use global default.
     * @param string $form_id    Source identifier (used for rules & log).
     *
     * @return array { ok: bool, msg: string, code: int }
     */
    public static function contact( $email, array $attributes = array(), $list_id = 0, $form_id = '' ) {
        $api_key = get_option( 'ws_brevo_fc_api_key', '' );
        if ( empty( $api_key ) ) {
            return array( 'ok' => false, 'msg' => 'API key not configured.', 'code' => 0 );
        }

        $email = sanitize_email( $email );
        if ( ! is_email( $email ) ) {
            return array( 'ok' => false, 'msg' => 'Invalid email address.', 'code' => 0 );
        }

        // Resolve target list: form rule > parameter > global default
        $resolved_list = self::resolve_list( $form_id, $list_id );
        if ( $resolved_list === false ) {
            // Sync explicitly disabled for this source
            return array( 'ok' => true, 'msg' => 'Sync disabled for this source.', 'code' => 0 );
        }

        $result = self::push( $api_key, $email, $attributes, (int) $resolved_list );
        self::log( $email, $form_id, (int) $resolved_list, $result );

        /**
         * Fires after a sync attempt, successful or not.
         *
         * @param string $email         Contact email.
         * @param array  $attributes    Brevo attributes sent.
         * @param int    $resolved_list Resolved list ID.
         * @param array  $result        { ok, msg, code }
         */
        do_action( 'ws_brevo_fc_after_sync', $email, $attributes, $resolved_list, $result );

        return $result;
    }

    /**
     * Resolves the target list ID for a given source.
     *
     * Returns false if a rule explicitly disables sync for this source.
     *
     * @param string $form_id          Source identifier.
     * @param int    $fallback_list_id Caller-provided fallback (0 = use global default).
     *
     * @return int|false Resolved list ID, or false if sync is disabled.
     */
    public static function resolve_list( $form_id, $fallback_list_id = 0 ) {
        $form_id = (string) $form_id;
        $rules   = json_decode( get_option( 'ws_brevo_fc_form_rules', '[]' ), true );

        if ( is_array( $rules ) && $form_id !== '' ) {
            foreach ( $rules as $rule ) {
                if ( (string) ( $rule['form_id'] ?? '' ) === $form_id ) {
                    if ( empty( $rule['active'] ) ) return false;
                    $lid = (int) ( $rule['list_id'] ?? 0 );
                    return $lid > 0 ? $lid : (int) get_option( 'ws_brevo_fc_default_list_id', 0 );
                }
            }
        }

        if ( $fallback_list_id > 0 ) return $fallback_list_id;
        return (int) get_option( 'ws_brevo_fc_default_list_id', 0 );
    }

    /**
     * Calls Brevo API POST /v3/contacts.
     *
     * @return array { ok: bool, msg: string, code: int }
     */
    private static function push( $api_key, $email, array $attributes, int $list_id ) {
        $body = array( 'email' => $email, 'updateEnabled' => true );
        if ( ! empty( $attributes ) ) $body['attributes'] = $attributes;
        if ( $list_id > 0 )           $body['listIds']    = array( $list_id );

        $response = wp_remote_post( 'https://api.brevo.com/v3/contacts', array(
            'headers' => array(
                'api-key'      => $api_key,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 10,
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'ok' => false, 'msg' => $response->get_error_message(), 'code' => 0 );
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $ok   = in_array( $code, array( 201, 204 ), true );
        $msg  = '';
        if ( ! $ok ) {
            $parsed = json_decode( wp_remote_retrieve_body( $response ), true );
            $msg    = $parsed['message'] ?? ( 'HTTP ' . $code );
        }
        return array( 'ok' => $ok, 'msg' => $msg, 'code' => $code );
    }

    /**
     * Appends an entry to the sync log (max 50, FIFO).
     */
    private static function log( $email, $form_id, $list_id, array $result ) {
        $log = json_decode( get_option( 'ws_brevo_fc_sync_log', '[]' ), true );
        if ( ! is_array( $log ) ) $log = array();
        $log[] = array(
            'ts'      => wp_date( 'd/m/Y H:i:s' ),
            'email'   => $email,
            'form_id' => $form_id,
            'list_id' => $list_id,
            'status'  => $result['ok'] ? 'ok' : 'error',
            'msg'     => $result['msg'],
        );
        if ( count( $log ) > 50 ) $log = array_slice( $log, -50 );
        update_option( 'ws_brevo_fc_sync_log', wp_json_encode( $log ) );
    }
}
