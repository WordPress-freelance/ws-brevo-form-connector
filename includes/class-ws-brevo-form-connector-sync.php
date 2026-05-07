<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WS_Brevo_FC_Sync
 *
 * Classe statique centrale. Point d'entrée unique pour toute synchronisation
 * vers Brevo, appelable depuis n'importe quel contexte (form builders,
 * AJAX, code custom).
 *
 * Usage direct depuis du code custom :
 *   WS_Brevo_FC_Sync::contact( 'john@example.com', [
 *       'PRENOM'  => 'John',
 *       'NOM'     => 'Doe',
 *   ], 3, 'mon-formulaire' );
 */
class WS_Brevo_FC_Sync {

    /**
     * Point d'entrée principal.
     *
     * @param string $email      Adresse email (obligatoire).
     * @param array  $attributes Attributs Brevo (PRENOM, NOM, SMS, SOCIETE…).
     * @param int    $list_id    ID de liste Brevo. 0 = utiliser le défaut global.
     * @param string $form_id    Identifiant du formulaire source (pour les règles + le journal).
     *
     * @return array ['ok'=>bool, 'msg'=>string, 'code'=>int]
     */
    public static function contact( $email, array $attributes = array(), $list_id = 0, $form_id = '' ) {
        $api_key = get_option( 'ws_brevo_fc_api_key', '' );
        if ( empty( $api_key ) ) {
            return array( 'ok' => false, 'msg' => 'Cle API manquante.', 'code' => 0 );
        }

        $email = sanitize_email( $email );
        if ( ! is_email( $email ) ) {
            return array( 'ok' => false, 'msg' => 'Email invalide.', 'code' => 0 );
        }

        // Résoudre la liste : règle form_id > paramètre > défaut global
        $resolved_list = self::resolve_list( $form_id, $list_id );
        if ( $resolved_list === false ) {
            // Règle de formulaire explicitement désactivée
            return array( 'ok' => true, 'msg' => 'Sync desactivee pour ce formulaire.', 'code' => 0 );
        }

        $result = self::push( $api_key, $email, $attributes, (int) $resolved_list );
        self::log( $email, $form_id, (int) $resolved_list, $result );

        /**
         * Hook post-sync pour extensions ou code custom.
         * do_action( 'ws_brevo_fc_after_sync', $email, $attributes, $resolved_list, $result );
         */
        do_action( 'ws_brevo_fc_after_sync', $email, $attributes, $resolved_list, $result );

        return $result;
    }

    /**
     * Résout l'ID de liste cible.
     * Retourne false si une règle active désactive la sync pour ce formulaire
     * (list_id = 0 ET active = 1 signifie "sync désactivée").
     *
     * @return int|false  ID de liste résolu, ou false si sync désactivée.
     */
    public static function resolve_list( $form_id, $fallback_list_id = 0 ) {
        $form_id = (string) $form_id;
        $rules   = json_decode( get_option( 'ws_brevo_fc_form_rules', '[]' ), true );

        if ( is_array( $rules ) && $form_id !== '' ) {
            foreach ( $rules as $rule ) {
                if ( (string) ( $rule['form_id'] ?? '' ) === $form_id ) {
                    if ( empty( $rule['active'] ) ) return false; // désactivé explicitement
                    $lid = (int) ( $rule['list_id'] ?? 0 );
                    return $lid > 0 ? $lid : (int) get_option( 'ws_brevo_fc_default_list_id', 0 );
                }
            }
        }

        // Pas de règle spécifique : fallback paramètre puis défaut global
        if ( $fallback_list_id > 0 ) return $fallback_list_id;
        return (int) get_option( 'ws_brevo_fc_default_list_id', 0 );
    }

    /**
     * Construit les attributs Brevo depuis un tableau plat de champs de formulaire.
     * Lit le mapping configuré dans les options.
     *
     * @param array $fields Tableau associatif fieldName => value.
     * @return array Attributs Brevo.
     */
    public static function map_attributes( array $fields ) {
        $map = array(
            get_option( 'ws_brevo_fc_field_firstname', 'firstname' ) => 'PRENOM',
            get_option( 'ws_brevo_fc_field_lastname',  'lastname' )  => 'NOM',
            get_option( 'ws_brevo_fc_field_phone',     'phone' )     => 'SMS',
            get_option( 'ws_brevo_fc_field_company',   'company' )   => 'SOCIETE',
        );

        $attributes = array();
        foreach ( $fields as $name => $value ) {
            $name  = strtolower( trim( (string) $name ) );
            $value = sanitize_text_field( (string) $value );
            if ( isset( $map[ $name ] ) && $value !== '' ) {
                $attributes[ $map[ $name ] ] = $value;
            }
        }
        return $attributes;
    }

    /**
     * Appel API Brevo POST /v3/contacts.
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
     * Journalise une entrée (max 50, FIFO).
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
