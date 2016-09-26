<?php
/**
 * Plugin Name: The Events Calendar Extension: Order Events by Publish Date in Event List Widget
 * Description: Make list widgets display events in the order they were published instead of the default "upcoming events" order.
 * Version: 1.0.0
 * Author: Modern Tribe, Inc.
 * Author URI: http://m.tri.be/1971
 * License: GPLv2 or later
 */

defined( 'WPINC' ) or die;

class Tribe__Extension__Order_List_Widget_Events_by_Publish_Date {

    /**
     * The semantic version number of this extension; should always match the plugin header.
     */
    const VERSION = '1.0.0';

    /**
     * Each plugin required by this extension
     *
     * @var array Plugins are listed in 'main class' => 'minimum version #' format
     */
    public $plugins_required = array(
        'Tribe__Events__Main' => '4.2'
    );

    /**
     * Parameters that let you target specific widget instances.
     *
     * @var array
     */
    protected $constraints = array(
		'sidebar_id'   => null,
		'widget_id'    => null,
		'widget_title' => null
	);

    /**
     * The constructor; delays initializing the extension until all other plugins are loaded.
     */
    public function __construct( array $constraints = array() ) {

    	$this->constraints = array_merge( $this->constraints, $constraints );

        add_action( 'plugins_loaded', array( $this, 'init' ), 100 );
    }

    /**
     * Extension hooks and initialization; exits if the extension is not authorized by Tribe Common to run.
     */
    public function init() {

        // Exit early if our framework is saying this extension should not run.
        if ( ! function_exists( 'tribe_register_plugin' ) || ! tribe_register_plugin( __FILE__, __CLASS__, self::VERSION, $this->plugins_required ) ) {
            return;
        }

        add_filter( 'widget_display_callback', array( $this, 'setup' ), 10, 3 );
    }

    /**
     * Set up the ordering overwrites on widget instances.
     *
     * @param array $instance
     * @param object $widget
     * @param array $args
     * @return array
     */
    public function setup( $instance, $widget, $args ) {
		
		// We're interested only in the (advanced or regular) events list widgets.
		$targets = array( 'tribe-events-adv-list-widget', 'tribe-events-list-widget' );
		
		if ( ! in_array( $widget->id_base, $targets ) )
			return $instance;

		// Check for constraints.
		if ( ! $this->constraints_met( $instance, $args ) )
			return $instance;

		// Modify behaviour.
		add_filter( 'tribe_events_list_widget_query_args', array( $this, 'order_by_latest' ) );
		
		return $instance;
	}

	/**
     * For the given widget instance, see if it meets the constraints supplied to target specific widgets.
     *
     * @param array $instance
     * @param array $args
     * @return array
     */
	protected function constraints_met( $instance, $args ) {
		$fail = false;

		// Should only run within a specific sidebar?
		if ( ! is_null( $this->constraints['sidebar_id'] ) && $this->constraints['sidebar_id'] !== $args['id'] )
			$fail = true;

		// Should only run in relation to a specific instance of the widget?
		if ( ! is_null( $this->constraints['widget_id'] ) && $this->constraints['widget_id'] !== $args['widget_id'] )
			$fail = true;

		// Should only run when the widget title is set to something specific?
		if ( ! is_null( $this->constraints['widget_title'] ) && $this->constraints['widget_title'] !== $instance['title'] )
			$fail = true;

		return ! $fail;
	}

	/**
     * Set up the alteration of the orderby parameters on allowed widget instances.
     *
     * @param array $args
     * @return array
     */
	public function order_by_latest( $args ) {

		// Don't interfere in other queries.
		remove_filter( 'tribe_events_list_widget_query_args', array( $this, 'order_by_latest' ) );

		// Tweak the actual orderby clause.
		add_filter( 'posts_orderby', array( $this, 'override_orderby' ), 100 );
		
		return $args;
	}

	/**
     * Perform the alteration of the orderby parameters on allowed widget instances.
     *
     * @param array $args
     * @return array
     */
	public function override_orderby( $orderby_sql ) {
		global $wpdb;

		// Don't interfere in other queries.
		remove_filter( 'posts_orderby', array( $this, 'override_orderby' ), 100 );

		return "$wpdb->posts.post_date DESC, $orderby_sql";
	}
}

/**
 * By itself, the following line will impact *all* list widgets. If you want to
 * impact just *one* widget, specify one or more constraints.
 *
 * Valid constraints include:
 *   - sidebar_id: the ID of the sidebar list widgets in other sidebars will not be affected.
 *   - widget_id: the specific widget ID itself.
 *   - widget_title: if you don't know how to determine the widget/sidebar ID, you can specify the widget title.
 *
 * Example 1:
 *    new Tribe__Extension__Order_List_Widget_Events_by_Publish_Date( array(
 *        'sidebar_id' => 'sidebar-1'
 *    ) );
 *
 * Example 2:
 *    new Tribe__Extension__Order_List_Widget_Events_by_Publish_Date( array(
 *        'widget_title' => 'Newly Added Events!'
 *    ) );
 *
 * Go to http://m.tri.be/194x for more information.
 */
new Tribe__Extension__Order_List_Widget_Events_by_Publish_Date();
