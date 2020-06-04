<?php
/**
 * Plugin Name:       Missed Schedule
 * Plugin URI:        https://github.com/BeAPI/missed-schedule
 * Description:       Publish future post when the publication date has passed and WordPress fails.
 * Version:           1.0.1
 * Requires at least: 3.9
 * Requires PHP:      5.6
 * Author:            Be API
 * Author URI:        https://beapi.fr
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:
 * Domain Path:
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

/**
 * Check constant before allow sync missed check
 */
if ( defined( 'ENABLE_SYNC_MISSED_CHECK' ) && true === ENABLE_SYNC_MISSED_CHECK ) {
	add_action( 'wp_loaded', __NAMESPACE__ . '\\publish_missed_schedule' );
}

/**
 * Get future posts with an publication date pasted, and publish it !
 *
 * @return void
 */
function publish_missed_schedule() {
	global $wpdb;

	$missed_posts = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'future' AND post_date <= %s ORDER BY post_date ASC LIMIT 10",
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

/**
 * Register also action for WP-CRON
 */
add_action( 'beapi_missed_scheduled_event', __NAMESPACE__ . '\\publish_missed_schedule' );

if ( ! wp_next_scheduled( 'beapi_missed_scheduled_event' ) ) {
	wp_schedule_event( time(), '5-minutes', 'beapi_missed_scheduled_event' );
}
