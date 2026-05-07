<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Brevo_FC_Loader {

    protected $actions = array();
    protected $filters = array();

    private function add( &$hooks, $hook, $component, $callback, $priority, $accepted_args ) {
        $hooks[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
    }

    public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
    }

    public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
    }

    public function run() {
        foreach ( $this->filters as $h ) {
            add_filter( $h['hook'], array( $h['component'], $h['callback'] ), $h['priority'], $h['accepted_args'] );
        }
        foreach ( $this->actions as $h ) {
            add_action( $h['hook'], array( $h['component'], $h['callback'] ), $h['priority'], $h['accepted_args'] );
        }
    }
}
