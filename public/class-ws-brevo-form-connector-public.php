<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WS_Brevo_FC_Public
 *
 * Adaptateurs pour chaque form builder + endpoint AJAX universel.
 * Chaque handler normalise les données de son builder vers un tableau
 * plat (email + champs), puis délègue à WS_Brevo_FC_Sync::contact().
 */
class WS_Brevo_FC_Public {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // NONCE PUBLIC
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Injecte un nonce et l'URL AJAX en footer pour l'endpoint nopriv.
     * Usage JS :
     *   fetch(wsBrevoFCPublic.ajaxurl, {
     *     method: 'POST',
     *     body: new URLSearchParams({
     *       action: 'ws_brevo_fc_submit',
     *       nonce:   wsBrevoFCPublic.nonce,
     *       email:   'john@example.com',
     *       firstname: 'John',
     *     })
     *   });
     */
    public function output_public_nonce() {
        $data = array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ws_brevo_fc_public' ),
            'action'  => 'ws_brevo_fc_submit',
        );
        echo '<script>var wsBrevoFCPublic=' . wp_json_encode( $data ) . ';</script>' . "\n";
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ENDPOINT AJAX UNIVERSEL (priv + nopriv)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * POST admin-ajax.php
     * Params : action, nonce, email, firstname, lastname, phone, company,
     *          list_id (optionnel), form_id (optionnel)
     */
    public function ajax_submit() {
        // Vérification nonce (fonctionne pour utilisateurs connectés ET déconnectés)
        if ( ! check_ajax_referer( 'ws_brevo_fc_public', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => 'Nonce invalide.' ), 403 );
        }

        $email    = sanitize_email( wp_unslash( $_POST['email']     ?? '' ) );
        $form_id  = sanitize_text_field( wp_unslash( $_POST['form_id']  ?? 'ajax' ) );
        $list_id  = absint( $_POST['list_id'] ?? 0 );

        $f_first  = get_option( 'ws_brevo_fc_field_firstname', 'firstname' );
        $f_last   = get_option( 'ws_brevo_fc_field_lastname',  'lastname' );
        $f_phone  = get_option( 'ws_brevo_fc_field_phone',     'phone' );
        $f_co     = get_option( 'ws_brevo_fc_field_company',   'company' );

        $fields = array(
            $f_first => sanitize_text_field( wp_unslash( $_POST['firstname'] ?? $_POST[ $f_first ] ?? '' ) ),
            $f_last  => sanitize_text_field( wp_unslash( $_POST['lastname']  ?? $_POST[ $f_last  ] ?? '' ) ),
            $f_phone => sanitize_text_field( wp_unslash( $_POST['phone']     ?? $_POST[ $f_phone ] ?? '' ) ),
            $f_co    => sanitize_text_field( wp_unslash( $_POST['company']   ?? $_POST[ $f_co    ] ?? '' ) ),
        );

        $attributes = WS_Brevo_FC_Sync::map_attributes( $fields );
        $result     = WS_Brevo_FC_Sync::contact( $email, $attributes, $list_id, $form_id );

        if ( $result['ok'] ) {
            wp_send_json_success( array( 'message' => 'Contact synchronise.' ) );
        } else {
            wp_send_json_error( array( 'message' => $result['msg'] ), 400 );
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CONTACT FORM 7
    // hook : wpcf7_mail_sent( WPCF7_ContactForm $contact_form )
    // ═══════════════════════════════════════════════════════════════════════

    public function handle_cf7( $contact_form ) {
        $submission = WPCF7_Submission::get_instance();
        if ( ! $submission ) return;

        $posted  = $submission->get_posted_data();
        $form_id = (string) $contact_form->id();

        $email  = sanitize_email( $this->find_value( $posted, array( 'email', 'your-email', 'mail' ) ) );
        $fields = $this->flatten_cf7( $posted );
        $attrs  = WS_Brevo_FC_Sync::map_attributes( $fields );

        WS_Brevo_FC_Sync::contact( $email, $attrs, 0, 'cf7-' . $form_id );
    }

    private function flatten_cf7( array $posted ) {
        $out = array();
        foreach ( $posted as $key => $val ) {
            if ( is_array( $val ) ) $val = implode( ', ', $val );
            $out[ $key ] = (string) $val;
        }
        return $out;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // GRAVITY FORMS
    // hook : gform_after_submission( $entry, $form )
    // ═══════════════════════════════════════════════════════════════════════

    public function handle_gravity( $entry, $form ) {
        $fields  = array();
        $email   = '';

        foreach ( $form['fields'] as $field ) {
            $value = rgar( $entry, (string) $field->id );
            $label = strtolower( str_replace( ' ', '_', $field->label ) );
            $type  = $field->type;

            if ( $type === 'email' && $email === '' ) {
                $email = sanitize_email( $value );
            } elseif ( $type === 'name' ) {
                // Champ composé : inputs séparés
                $fields['firstname'] = sanitize_text_field( rgar( $entry, $field->id . '.3' ) );
                $fields['lastname']  = sanitize_text_field( rgar( $entry, $field->id . '.6' ) );
            } elseif ( $value !== '' ) {
                $fields[ $label ] = sanitize_text_field( $value );
            }
        }

        $attrs = WS_Brevo_FC_Sync::map_attributes( $fields );
        WS_Brevo_FC_Sync::contact( $email, $attrs, 0, 'gf-' . $form['id'] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // WPFORMS
    // hook : wpforms_process_complete( $fields, $entry, $form_data, $entry_id )
    // ═══════════════════════════════════════════════════════════════════════

    public function handle_wpforms( $fields, $entry, $form_data, $entry_id ) {
        $flat   = array();
        $email  = '';

        foreach ( $fields as $field ) {
            $type  = $field['type']  ?? '';
            $value = $field['value'] ?? '';
            $label = strtolower( str_replace( ' ', '_', $field['name'] ?? '' ) );

            if ( $type === 'email' && $email === '' ) {
                $email = sanitize_email( $value );
            } elseif ( $type === 'name' ) {
                $flat['firstname'] = sanitize_text_field( $field['first']  ?? '' );
                $flat['lastname']  = sanitize_text_field( $field['last']   ?? '' );
            } else {
                $flat[ $label ] = sanitize_text_field( (string) $value );
            }
        }

        $attrs = WS_Brevo_FC_Sync::map_attributes( $flat );
        WS_Brevo_FC_Sync::contact( $email, $attrs, 0, 'wpf-' . $form_data['id'] );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ELEMENTOR FORMS PRO
    // hook : elementor_pro/forms/new_record( $record, $handler )
    // ═══════════════════════════════════════════════════════════════════════

    public function handle_elementor( $record, $handler ) {
        $raw     = $record->get( 'fields' );
        $flat    = array();
        $email   = '';
        $form_id = $record->get_form_settings( 'form_name' ) ?: 'elementor';

        foreach ( $raw as $field ) {
            $id    = $field['id']    ?? '';
            $value = $field['value'] ?? '';
            $type  = $field['type']  ?? '';

            if ( $type === 'email' && $email === '' ) {
                $email = sanitize_email( $value );
            } else {
                $flat[ strtolower( $id ) ] = sanitize_text_field( (string) $value );
            }
        }

        $attrs = WS_Brevo_FC_Sync::map_attributes( $flat );
        WS_Brevo_FC_Sync::contact( $email, $attrs, 0, 'el-' . sanitize_key( $form_id ) );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // AVADA / FUSION FORMS
    // hook : avada_form_submit / fusion_form_submit( $fields, $form_id )
    // Guard statique : évite le double-fire si les deux hooks sont actifs.
    // ═══════════════════════════════════════════════════════════════════════

    private static $avada_done = array();

    public function handle_avada( $fields, $form_id ) {
        $key = (string) $form_id;
        if ( isset( self::$avada_done[ $key ] ) ) return;
        self::$avada_done[ $key ] = true;

        $flat  = array();
        $email = '';

        // Avada peut passer un tableau indexé OU associatif selon la version
        if ( ! empty( $fields ) && isset( reset( $fields )['name'] ) ) {
            foreach ( $fields as $f ) {
                $name  = strtolower( $f['name']  ?? '' );
                $value = $f['value'] ?? '';
                if ( in_array( $name, array( 'email', 'your-email', 'mail' ), true ) ) {
                    $email = sanitize_email( $value );
                } else {
                    $flat[ $name ] = sanitize_text_field( (string) $value );
                }
            }
        } else {
            foreach ( $fields as $name => $value ) {
                $name = strtolower( (string) $name );
                if ( in_array( $name, array( 'email', 'your-email', 'mail' ), true ) ) {
                    $email = sanitize_email( (string) $value );
                } else {
                    $flat[ $name ] = sanitize_text_field( (string) $value );
                }
            }
        }

        $attrs = WS_Brevo_FC_Sync::map_attributes( $flat );
        WS_Brevo_FC_Sync::contact( $email, $attrs, 0, 'avada-' . $form_id );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // NINJA FORMS
    // hook : ninja_forms_after_submission( $form_data )
    // ═══════════════════════════════════════════════════════════════════════

    public function handle_ninja( $form_data ) {
        $flat    = array();
        $email   = '';
        $form_id = $form_data['form_id'] ?? 'ninja';

        foreach ( $form_data['fields'] ?? array() as $field ) {
            $type  = $field['type']  ?? '';
            $key   = strtolower( $field['key']   ?? '' );
            $value = $field['value'] ?? '';

            if ( $type === 'email' && $email === '' ) {
                $email = sanitize_email( (string) $value );
            } else {
                $flat[ $key ] = sanitize_text_field( (string) $value );
            }
        }

        $attrs = WS_Brevo_FC_Sync::map_attributes( $flat );
        WS_Brevo_FC_Sync::contact( $email, $attrs, 0, 'ninja-' . $form_id );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // FLUENT FORMS
    // hook : fluentform_after_submission( $insertId, $formData, $form )
    // ═══════════════════════════════════════════════════════════════════════

    public function handle_fluent( $insert_id, $form_data, $form ) {
        $flat    = array();
        $email   = '';
        $form_id = $form->id ?? 'fluent';

        foreach ( $form_data as $key => $value ) {
            $key = strtolower( (string) $key );
            if ( is_array( $value ) ) $value = implode( ', ', $value );
            if ( strpos( $key, 'email' ) !== false && $email === '' ) {
                $email = sanitize_email( (string) $value );
            } else {
                $flat[ $key ] = sanitize_text_field( (string) $value );
            }
        }

        $attrs = WS_Brevo_FC_Sync::map_attributes( $flat );
        WS_Brevo_FC_Sync::contact( $email, $attrs, 0, 'fluent-' . $form_id );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // FORMIDABLE FORMS
    // hook : frm_after_create_entry( $entry_id, $form_id )
    // ═══════════════════════════════════════════════════════════════════════

    public function handle_formidable( $entry_id, $form_id ) {
        if ( ! class_exists( 'FrmEntry' ) ) return;

        $entry  = FrmEntry::getOne( $entry_id, true );
        $flat   = array();
        $email  = '';

        if ( ! empty( $entry->metas ) ) {
            foreach ( $entry->metas as $field_id => $value ) {
                $field = FrmField::getOne( $field_id );
                if ( ! $field ) continue;

                $type = $field->type ?? '';
                $key  = strtolower( sanitize_key( $field->name ) );
                if ( is_array( $value ) ) $value = implode( ', ', $value );

                if ( $type === 'email' && $email === '' ) {
                    $email = sanitize_email( (string) $value );
                } else {
                    $flat[ $key ] = sanitize_text_field( (string) $value );
                }
            }
        }

        $attrs = WS_Brevo_FC_Sync::map_attributes( $flat );
        WS_Brevo_FC_Sync::contact( $email, $attrs, 0, 'frm-' . $form_id );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HELPER — cherche une valeur dans plusieurs clés possibles
    // ═══════════════════════════════════════════════════════════════════════

    private function find_value( array $data, array $keys, $default = '' ) {
        foreach ( $keys as $k ) {
            if ( isset( $data[ $k ] ) && $data[ $k ] !== '' ) return $data[ $k ];
        }
        return $default;
    }
}
