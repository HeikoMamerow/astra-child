<?php
/**
 * Astra-child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package astra-child
 */

/**
 * Enqueue scripts and styles.
 */
function astra_parent_theme_enqueue() {
	wp_enqueue_style( 'astra-child-style', get_stylesheet_directory_uri() . '/style.css' );
}

add_action( 'wp_enqueue_scripts', 'astra_parent_theme_enqueue' );

/**
 * Custom event scope for Event Manager plugin.
 *
 * Source: https://wp-events-plugin.com/tutorials/create-your-own-event-scope/
 */
add_filter( 'em_events_build_sql_conditions', 'my_em_scope_conditions', 1, 2 );
function my_em_scope_conditions( $conditions, $args ) {
	if ( ! empty( $args['scope'] ) && $args['scope'] == 'next7days' ) {
		$start_date          = date( 'Y-m-d', current_time( 'timestamp' ) );
		$end_date            = date( 'Y-m-d', strtotime( '+6 day', current_time( 'timestamp' ) ) );
		$conditions['scope'] = " (event_start_date BETWEEN CAST('$start_date' AS DATE) AND CAST('$end_date' AS DATE)) OR (event_end_date BETWEEN CAST('$end_date' AS DATE) AND CAST('$start_date' AS DATE))";
	}

	return $conditions;
}

add_filter( 'em_get_scopes', 'my_em_scopes', 1, 1 );
function my_em_scopes( $scopes ) {
	$my_scopes = [
		'next7days' => 'next7days',
	];

	return $scopes + $my_scopes;
}

/**
 * Nesting shortcodes in menu.
 * Need it for the Events Manager plugin.
 */
add_filter( 'wp_nav_menu', 'do_shortcode' );
add_filter( 'the_content', 'do_shortcode' );
/**
 * Shortcode for advanced notice in menu.
 * This is an enhancement of the Events Manager plugin.
 */
function em_advanced_notice_func() {

	/*
	 * 1) Find all categories with events inside the scope (next7days).
	 */

	$string       = '';
	$category_ids = [];

	date_default_timezone_set( 'Europe/Berlin' );
	$scope_start       = date( 'Y-m-d' );
	$scope_7days_later = date( 'Y-m-d', strtotime( "+1 week" ) );
	$scope_end         = date( 'Y-m-d', strtotime( "+3 week" ) );

	// ID of parent category Wochenprogramm => 15
	$child_of = 15;

	$args       = [
		'child_of'   => $child_of,
		'type'       => 'event',
		'hide_empty' => 1,
		'taxonomy'   => 'event-categories',
	];
	$categories = get_categories( $args );

	foreach ( $categories as $category ) {

		// Get events within the scope (3 weeks)
		$em_events = EM_Events::get( [
			'hide_empty'  => 1,
			'category'    => $category->cat_ID,
			'recurrences' => 1,
			'orderby'     => "event_start_time",
			'scope'       => $scope_start . "," . $scope_end,
			//'scope'       => "next7days",
		] );

		// Get all categories within the scope.
		foreach ( $em_events as $em_event ) {
			if ( ! isset( $category_id ) ) {
				$category_ids[] = $category->cat_ID;
			};
		}
	}

	/*
	 * 2) Get only events from categories within the scope
	 */

	$args       = [
		'include'    => $category_ids,
		'type'       => 'event',
		'hide_empty' => 1,
		'taxonomy'   => 'event-categories',
	];
	$categories = get_categories( $args );

	foreach ( $categories as $category ) {

		$string .= '<div class="menu-link-flex em-recurring-events-in-menu">';
		$string .= '<div class="menu-link menu-link-day">' . $category->description . '</div>';
		$string .= '<div class="menu-link menu-link-event-list">';

		/*
		 * 2.a Show events from active recurrences.
		 */
		// Get events within the scope (7days)
		$em_events = EM_Events::get( [
			'hide_empty'  => 1,
			'category'    => $category->cat_ID,
			'recurrences' => 1,
			'orderby'     => "event_start_time",
			'scope'       => "next7days",
		] );

		foreach ( $em_events as $em_event ) {
			//$string .= var_dump($em_event);
			//$event .= $em_event->event_attributes->AdvancedNoticeDateStart .' ';
			$string .= '<a class="menu-link-flex" href="' . $em_event->guid . '">';
			$string .= '<span class="menu-link-flex-item2">' . date( 'G:i', strtotime( $em_event->start_time ) ) . '</span> ';
			$string .= '<span class="menu-link-flex-item3">' . $em_event->event_name . '</span>';
			$string .= '</a>';
		}

		/*
		 * 2.b Show events from soon (in the next 3 weeks) starting recurrences.
		 */
		// Get events within the scope (3weeks)
		$em_events = EM_Events::get( [
			'hide_empty'  => 1,
			'category'    => $category->cat_ID,
			'recurrences' => 1,
			'limit'       => 1,
			'orderby'     => "event_start_time",
			'scope'       => $scope_7days_later . "," . $scope_end,
		] );

		foreach ( $em_events as $em_event ) {
			//$string .= var_dump($em_event);
			//$event .= $em_event->event_attributes->AdvancedNoticeDateStart .' ';
			$string .= '<a class="menu-link-flex" href="' . $em_event->guid . '">';
			$string .= '<span class="menu-link-flex-item2">' . date( 'G:i', strtotime( $em_event->start_time ) ) . '</span> ';
			$string .= '<span class="menu-link-flex-item3">' . $em_event->event_name . '</span>';
			$string .= '</a>';
		}

		$string .= '</div>';
		$string .= '</div>';

	}

	return $string;

}

add_shortcode( 'em_advanced_notice', 'em_advanced_notice_func' );
