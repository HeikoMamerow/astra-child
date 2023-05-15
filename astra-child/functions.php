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
function astra_parent_theme_enqueue(): void {
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

add_filter( 'em_get_scopes', 'my_em_scopes', 1 );
function my_em_scopes( $scopes ): array {
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
 * Shortcodes for advanced notice in menu "Wiederkehrende Veranstaltungen".
 */
@require 'functions/events-manager_shortcode_wiederkehrende-veranstaltungen.php';

/**
 * Shortcodes for advanced notice in menu "Zweigstellen".
 */
@require 'functions/events-manager_shortcode_zweigstellen.php';

/**
 * Shortcodes for advanced notice in menu "Meditationskurse".
 */
@require 'functions/events-manager_shortcode_meditationskurse.php';

/**
 * Options page for Event Manager
 */
@require 'functions/events-manager-options-page.php';

/**
 * Add meta tag on front page for Facebook check.
 */
function facebook_domain_verification(): void {
	if ( is_front_page() ) {
		echo "\n" . '<meta name="facebook-domain-verification" content="jwhc6dx6k85c0pk18096orl3gj0v38" />' . "\n";
	}
}

add_action( 'wp_head', 'facebook_domain_verification' );


/**
 * Add meta tag on front page for Facebook Pixel.
 */
function facebook_pixel(): void {
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
	echo "\n" . '<noscript><img height="1" width="1" alt="" style="display:none"' . "\n";
	echo "\n" . 'src="https://www.facebook.com/tr?id=786499535732236&ev=PageView&noscript=1"' . "\n";
	echo "\n" . '    /></noscript>' . "\n";
	echo "\n" . '<!-- End Meta Pixel Code -->' . "\n";
}

add_action( 'wp_head', 'facebook_pixel' );
