<?php
/*
Plugin Name: BEA Missed Schedule
Plugin URI: http://www.beapi.fr
Description: Fix <code>Missed Schedule</code> Future Posts Cron Job: find missed schedule posts that match this problem every 1 minute and it republish them correctly fixed 10 items per session.
Author: Be API
Author URI: https://beapi.fr
Version: 0.2
*/

class BEA_Missed_Schedule {
	/**
	 * Delay in minutes
	 */
	const DELAY = 1;

	/**
	 * BEA_Missed_Schedule constructor.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'init' ), 0 );
	}

	/**
	 * Delete option when deactive this plugin
	 */
	public static function deactivation() {
		delete_option( 'bea_missed_schedule' );
	}

	/**
	 * Init an option with as value the expiration delay in seconds, if delay is over, exec the cleanup function
	 *
	 * @return bool
	 */
	public static function init() {
		$last = get_option( 'bea_missed_schedule', false );
		if ( ( false !== $last ) && ( $last > ( time() - ( self::DELAY * 60 ) ) ) ) {
			return false;
		}

		update_option( 'bea_missed_schedule', time() );

		return self::publish_missed_schedule();
	}

	/**
	 * The cleanup function that publish posts if WP missed the schedule
	 *
	 * @return bool
	 */
	public static function publish_missed_schedule() {
		global $wpdb;

		$scheduled_ids = $wpdb->get_col( "SELECT `ID` FROM `{$wpdb->posts}` WHERE( ((`post_date`>0)&&(`post_date`<=CURRENT_TIMESTAMP())) OR ((`post_date_gmt`>0)&&(`post_date_gmt`<=UTC_TIMESTAMP())) )AND `post_status` = 'future' LIMIT 0, 10" );
		if ( false === $scheduled_ids || empty( $scheduled_ids ) ) {
			return false;
		}

		foreach ( $scheduled_ids as $scheduled_id ) {
			if ( ! $scheduled_id ) {
				continue;
			}

			remove_action( 'publish_future_post', 'check_and_publish_future_post' );
			wp_publish_post( $scheduled_id );
			add_action( 'publish_future_post', 'check_and_publish_future_post' );
		}

		return true;
	}
}

add_action( 'plugins_loaded', 'register_bea_missed_schedule' );
function register_bea_missed_schedule() {
	return new BEA_Missed_Schedule();
}

register_deactivation_hook( __FILE__, array( 'BEA_Missed_Schedule', 'deactivation' ) );
