<?php
/**
 * Plugin Name: The Events Calendar — Order Events by Publish Date in Event List Widget
 * Description: Make list widgets display events in the order they were published instead of the default "upcoming events" order.
 * Version: 1.0.0
 * Author: Modern Tribe, Inc.
 * Author URI: http://m.tri.be/1x
 * License: GPLv2 or later
 */
 
defined( 'WPINC' ) or die;

class Tribe__Order_List_Widget_Events_by_Publish_Date {
	
	protected $constraints = array(
		'sidebar_id'   => null,
		'widget_id'    => null,
		'widget_title' => null
	);

	public function __construct( array $constraints = array() ) {
		$this->constraints = array_merge( $this->constraints, $constraints );
		add_filter( 'widget_display_callback', array( $this, 'setup' ), 10, 3 );
	}

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

	public function order_by_latest( $args ) {

		// Don't interfere in other queries.
		remove_filter( 'tribe_events_list_widget_query_args', array( $this, 'order_by_latest' ) );

		// Tweak the actual orderby clause.
		add_filter( 'posts_orderby', array( $this, 'override_orderby' ), 100 );
		
		return $args;
	}

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
 *    new Tribe__Order_List_Widget_Events_by_Publish_Date( array(
 *        'sidebar_id' => 'sidebar-1'
 *    ) );
 *
 * Example 2:
 *    new Tribe__Order_List_Widget_Events_by_Publish_Date( array(
 *        'widget_title' => 'Newly Added Events!'
 *    ) );
 *
 * Go to http://m.tri.be/194x for more information.
 */

new Tribe__Order_List_Widget_Events_by_Publish_Date();
