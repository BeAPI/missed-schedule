<?php

/*
  Plugin Name: BEA Missed Schedule
  Plugin URI: http://www.beapi.fr
  Description: Fix <code>Missed Schedule</code> Future Posts Cron Job: find missed schedule posts that match this problem every 1 minute and it republish them correctly fixed 10 items per session.
  Author: Be API
  Author URI: https://beapi.fr
  Version: 0.2
 */

namespace BEAPI\MissedSchedule;

// Standard plugin security, keep this line in place.
defined( 'ABSPATH' ) || die();

// If WP-CRON run on server side, by default, no check missed schedule on front side, otherwise do it
if ( ! defined( 'DISABLE_WP_CRON' ) || true !== DISABLE_WP_CRON ) {
	if ( ! defined( 'ENABLE_SYNC_MISSED_CHECK' ) ) {
		define( 'ENABLE_SYNC_MISSED_CHECK', true );
	}
}

/**
 * Adds a custom cron schedule for every 5 minutes.
 *
 * @param array $schedules An array of non-default cron schedules.
 *
 * @return array Filtered array of non-default cron schedules.
 *
 */
function cron_schedules( $schedules ) {
	$schedules['5-minutes'] = array(
		'interval' => 5 * MINUTE_IN_SECONDS,
		'display'  => 'Every 5 minutes',
	);

	return $schedules;
}

add_filter( 'cron_schedules', __NAMESPACE__ . '\\cron_schedules' );

if ( defined( 'ENABLE_SYNC_MISSED_CHECK' ) && true === ENABLE_SYNC_MISSED_CHECK ) {
	add_action( 'wp_loaded', __NAMESPACE__ . '\\publish_missed_schedule' );
}

function publish_missed_schedule() {
	global $wpdb;

	$missed_posts = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'future' AND post_date <= %s LIMIT 100",
			current_time( 'mysql', false )
		)
	);

	if ( empty( $missed_posts ) ) {
		return;
	}

	foreach ( $missed_posts as $missed_post ) {
		$missed_post = absint( $missed_post );

		// remove_action( 'publish_future_post', 'check_and_publish_future_post' ); TODO: Keep it ?
		wp_publish_post( $missed_post );
		wp_clear_scheduled_hook( 'publish_future_post', array( $missed_post ) );
		// add_action( 'publish_future_post', 'check_and_publish_future_post' ); TODO: Keep it ?
	}
}

add_action( 'bea_missed_scheduled_event', __NAMESPACE__ . '\\publish_missed_schedule' );

if ( ! wp_next_scheduled( __NAMESPACE__ . '\\bea_missed_scheduled_event' ) ) {
	wp_schedule_event( time(), '5-minutes', __NAMESPACE__ . '\\bea_missed_scheduled_event' );
}
