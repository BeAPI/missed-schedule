<?php
/**
 * Plugin Name:       Missed Schedule
 * Plugin URI:        https://github.com/BeAPI/missed-schedule
 * Description:       Publish future posts when their publication date has passed and WordPress failed to do it.
 * Version:           1.1.0
 * Requires at least: 4.9
 * Requires PHP:      7.4
 * Author:            Be API
 * Author URI:        https://beapi.fr
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       beapi-missed-schedule
 */

namespace BEAPI\MissedSchedule;

// Standard plugin security, keep this line in place.
defined( 'ABSPATH' ) || die();

const CRON_HOOK     = 'beapi_missed_scheduled_event';
const CRON_SCHEDULE = 'beapi-missed-schedule';
const SYNC_LOCK_KEY = 'beapi_missed_schedule_sync_lock';

/*
 * When WP-Cron is spawned by page loads, it is only as reliable as the traffic: also check on
 * page loads. When WP-Cron is disabled, a system cron runs `wp cron event run` and the WP-Cron
 * event below is enough.
 */
if ( ! defined( 'DISABLE_WP_CRON' ) || true !== DISABLE_WP_CRON ) {
	if ( ! defined( 'ENABLE_SYNC_MISSED_CHECK' ) ) {
		define( 'ENABLE_SYNC_MISSED_CHECK', true );
	}
}

/**
 * Add the cron schedule used by the missed schedule event.
 *
 * @param array<string, array{interval: int, display: string}> $schedules Non-default cron schedules.
 *
 * @return array<string, array{interval: int, display: string}>
 */
function cron_schedules( $schedules ) {
	/**
	 * Filter the interval, in seconds, between two WP-Cron checks for missed posts.
	 *
	 * @param int $interval Interval in seconds. Default 5 minutes.
	 */
	$interval = (int) apply_filters( 'beapi_missed_schedule_interval', 5 * MINUTE_IN_SECONDS );

	$schedules[ CRON_SCHEDULE ] = array(
		'interval' => max( MINUTE_IN_SECONDS, $interval ),
		'display'  => __( 'Missed Schedule check', 'beapi-missed-schedule' ),
	);

	return $schedules;
}

add_filter( 'cron_schedules', __NAMESPACE__ . '\\cron_schedules' );

/**
 * Schedule the WP-Cron event once.
 *
 * @return void
 */
function schedule_event() {
	if ( wp_next_scheduled( CRON_HOOK ) ) {
		return;
	}

	wp_schedule_event( time(), CRON_SCHEDULE, CRON_HOOK );
}

add_action( 'init', __NAMESPACE__ . '\\schedule_event' );
add_action( CRON_HOOK, __NAMESPACE__ . '\\publish_missed_schedule' );

if ( defined( 'ENABLE_SYNC_MISSED_CHECK' ) && true === ENABLE_SYNC_MISSED_CHECK ) {
	add_action( 'wp_loaded', __NAMESPACE__ . '\\sync_check' );
}

/**
 * Run the check from a regular request, at most once per throttle window.
 *
 * The lock is a transient, so it lives in the object cache when the site has one and falls back
 * to the options table otherwise. Either way it is cheaper than scanning the posts table on
 * every request.
 *
 * @return void
 */
function sync_check() {
	if ( wp_doing_cron() ) {
		return;
	}

	/**
	 * Filter the minimum delay, in seconds, between two synchronous checks.
	 *
	 * Return 0 to check on every request.
	 *
	 * @param int $throttle Delay in seconds. Default 1 minute.
	 */
	$throttle = (int) apply_filters( 'beapi_missed_schedule_sync_throttle', MINUTE_IN_SECONDS );

	if ( $throttle > 0 ) {
		if ( false !== get_transient( SYNC_LOCK_KEY ) ) {
			return;
		}

		set_transient( SYNC_LOCK_KEY, time(), $throttle );
	}

	publish_missed_schedule();
}

/**
 * Get the IDs of the future posts whose publication date has passed.
 *
 * The query filters on post type so MySQL can use the `type_status_date` index of the posts
 * table, and compares GMT dates like WordPress core does in `check_and_publish_future_post()`.
 *
 * @return int[] Post IDs, oldest first.
 */
function get_missed_post_ids() {
	global $wpdb;

	/**
	 * Filter the maximum number of posts published by one check.
	 *
	 * @param int $limit Maximum number of posts. Default 10.
	 */
	$limit = (int) apply_filters( 'beapi_missed_schedule_limit', 10 );
	if ( $limit < 1 ) {
		return array();
	}

	$post_types = array_values( get_post_types() );
	if ( empty( $post_types ) ) {
		return array();
	}

	$placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
	$values       = array_merge( $post_types, array( current_time( 'mysql', true ), $limit ) );

	$post_ids = $wpdb->get_col(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $placeholders only holds %s markers.
			"SELECT ID FROM {$wpdb->posts} WHERE post_type IN ({$placeholders}) AND post_status = 'future' AND post_date_gmt <= %s ORDER BY post_date_gmt ASC LIMIT %d",
			$values
		)
	);

	if ( ! is_array( $post_ids ) ) {
		return array();
	}

	return array_map( 'absint', $post_ids );
}

/**
 * Publish the future posts whose publication date has passed.
 *
 * Reuses the core callback of the `publish_future_post` event so the status and date checks stay
 * the same as a normal scheduled publication, then drops the orphaned single event.
 *
 * @return void
 */
function publish_missed_schedule() {
	foreach ( get_missed_post_ids() as $post_id ) {
		check_and_publish_future_post( $post_id );

		if ( 'publish' !== get_post_status( $post_id ) ) {
			continue;
		}

		wp_clear_scheduled_hook( 'publish_future_post', array( $post_id ) );
	}
}
