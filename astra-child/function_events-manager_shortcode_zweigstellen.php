<?php
/**
 * Shortcodes for advanced notice in menu "Zweigstellen".
 * This is an enhancement of the Events Manager plugin.
 */

function em_menu_zweigstellen_func() {

	$string         = '';
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
		'category'    => 'zweigstellen',
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
		'category'    => 'zweigstellen',
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

add_shortcode( 'em_menu_zweigstellen', 'em_menu_zweigstellen_func' );
