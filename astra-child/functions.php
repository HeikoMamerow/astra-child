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
	if ( ! empty( $args['scope'] ) && $args['scope'] === 'next7days' ) {
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

	$string         = '';
	$event          = [];
	$recurrence_ids = [];

	// Set to german language
	//setlocale (LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');
	setlocale( LC_ALL, 'de_DE' );

	// Set to german time zone
	date_default_timezone_set( 'Europe/Berlin' );

	$scope_today         = date( 'Y-m-d' );
	$scope_6days_later   = date( 'Y-m-d', strtotime( "+6 day" ) );
	$scope_7days_later   = date( 'Y-m-d', strtotime( "+7 day" ) );
	$scope_182days_later = date( 'Y-m-d', strtotime( "+182 day" ) );

	// Get events within the scope (today + 6 days)
	$em_events = EM_Events::get( [
		'hide_empty'  => 1,
		'recurrences' => 1,
		'orderby'     => "event_start_date,event_start_time",
		'scope'       => $scope_today . "," . $scope_6days_later,
	] );

	// Set all event data in array.
	foreach ( $em_events as $em_event ) {
		$events[] = [
			'day_number'    => date( 'N', strtotime( $em_event->start_date ) ),
			'day'           => strftime( '%a', strtotime( $em_event->start_date ) ),
			'timestamp'     => strtotime( $em_event->start_date ),
			'start_time'    => date( 'G:i', strtotime( $em_event->start_time ) ),
			'recurrence_id' => $em_event->recurrence_id,
			'guid'          => $em_event->guid,
			'event_name'    => $em_event->event_name,
		];
	}

	// Get array of recurrende_ids.
	foreach ( $events as $event ) {
		$recurrence_ids[] = $event['recurrence_id'];
	}

	// Get events within the next scope (7 - 21 days)
	$em_events_7_to_21 = EM_Events::get( [
		'hide_empty'  => 1,
		'recurrences' => 1,
		'orderby'     => "event_start_date,event_start_time",
		'scope'       => $scope_7days_later . "," . $scope_182days_later,
	] );


	foreach ( $em_events_7_to_21 as $em_event_7_to_21 ) {

		// We want recurrences from the first week.
		if ( ! in_array( $em_event_7_to_21->recurrence_id, $recurrence_ids ) ) {

			// Beware we can have still multiple events from one recurrence.
			// Add this recurrence to the check and prevent later in the loop.
			$recurrence_ids[] = $em_event_7_to_21->recurrence_id;

			$events[] = [
				'day_number'    => date( 'N', strtotime( $em_event_7_to_21->start_date ) ),
				'day'           => strftime( '%a', strtotime( $em_event_7_to_21->start_date ) ),
				'timestamp'     => strtotime( $em_event_7_to_21->start_date ),
				'start_time'    => date( 'G:i', strtotime( $em_event_7_to_21->start_time ) ),
				'recurrence_id' => $em_event_7_to_21->recurrence_id,
				'guid'          => $em_event_7_to_21->guid,
				'event_name'    => $em_event_7_to_21->event_name,
			];

		}

	}

	// First sort by day then by time and then by timestamp.
	$day_number = array_column( $events, 'day_number' );
	$start_time = array_column( $events, 'start_time' );
	$timestamp  = array_column( $events, 'timestamp' );
	array_multisort( $day_number, SORT_ASC, $start_time, SORT_ASC, $timestamp, SORT_ASC, $events );

	// Marker value for the start in the loop
	$event_day = 'start';

	foreach ( $events as $event ) {

		// Need special markup for the first loop.
		if ( $event_day === 'start' ) {
			$string .= '<div class="menu-link-flex em-recurring-events-in-menu">';
			$string .= '<div class="menu-link menu-link-day">' . $event['day'] . '</div>';
			$string .= '<div class="menu-link menu-link-event-list">';
			// Need special markup for new weekday in the loop.
		} elseif ( $event_day != $event['day'] ) {
			$string .= '</div>'; // .menu-link-event-list
			$string .= '</div>'; // .menu-link-flex

			$string .= '<div class="menu-link-flex em-recurring-events-in-menu">';
			$string .= '<div class="menu-link menu-link-day">' . $event['day'] . '</div>';
			$string .= '<div class="menu-link menu-link-event-list">';
		}

		$string .= '<a class="menu-link-flex" href="' . $event['guid'] . '">';
		$string .= '<span class="menu-link-flex-item2">' . date( 'G:i', strtotime( $event['start_time'] ) ) . '</span> ';
		$string .= '<span class="menu-link-flex-item3">' . $event['event_name'] . '</span>';
		$string .= '</a>';

		$event_day = $event['day'];
	}

	$string .= '</div>'; // .menu-link-event-list
	$string .= '</div>'; // .menu-link-flex

	return $string;

}

add_shortcode( 'em_advanced_notice', 'em_advanced_notice_func' );


/**
 * Add meta tag on front page for Facebook check.
 */
function facebook_domain_verification() {
	if ( is_front_page() ) {
		echo "\n" . '<meta name="facebook-domain-verification" content="jwhc6dx6k85c0pk18096orl3gj0v38" />' . "\n";
	}
}

add_action( 'wp_head', 'facebook_domain_verification' );


/**
 * Add meta tag on front page for Facebook Pixel.
 */
function facebook_pixel() {
	if ( is_front_page() ) {
		echo "\n" . '<!-- Meta Pixel Code -->' . "\n";
		echo "\n" . '<script>' . "\n";
		echo "\n" . '!function(f,b,e,v,n,t,s)';
		echo "\n" . '{if(f.fbq)return;n=f.fbq=function(){n.callMethod?';
		echo "\n" . '	n.callMethod.apply(n,arguments):n.queue.push(arguments)};';
		echo "\n" . '	if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version=\'2.0\';';
		echo "\n" . '	n.queue=[];t=b.createElement(e);t.async=!0;';
		echo "\n" . '	t.src=v;s=b.getElementsByTagName(e)[0];';
		echo "\n" . '	s.parentNode.insertBefore(t,s)}(window, document,\'script\',';
		echo "\n" . '\'https://connect.facebook.net/en_US/fbevents.js\');';
		echo "\n" . 'fbq(\'init\', \'786499535732236\');';
		echo "\n" . 'fbq(\'track\', \'PageView\');' . "\n";
		echo "\n" . '</script>' . "\n";
		echo "\n" . '<noscript><img height="1" width="1" style="display:none"' . "\n";
		echo "\n" . 'src="https://www.facebook.com/tr?id=786499535732236&ev=PageView&noscript=1"' . "\n";
		echo "\n" . '    /></noscript>' . "\n";
		echo "\n" . '<!-- End Meta Pixel Code -->' . "\n";
	}
}

add_action( 'wp_head', 'facebook_pixel' );
