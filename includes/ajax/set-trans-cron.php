<?php

defined('ABSPATH') or die('Direct script access disallowed.');

function set_trans_cron($cron_on_off, $frequency) {
	if ($cron_on_off) {
		if (in_array($frequency, array('hourly', 'two_hours'))) {
			$start_time = time();
			$t = new DateTime( );
			$t->setTimestamp( $start_time );
			$t->setTimeZone(new DateTimeZone("America/Chicago"));
			$next_run =  $t->format("M jS g:i:s a");
		} else {
			$today = date_create(date('Y-m-d'));
			// $tomorrow = date_add($today, date_interval_create_from_date_string("1 day"));
			$tomorrow_am = $today->format("Y-m-d 00:01");
			$t = new DateTime( $tomorrow_am, new DateTimeZone("America/Chicago"));
			$t->setTimeZone(new DateTimeZone("UTC"));
			$start_time = $t->getTimestamp();
			$t = new DateTime( );
			$t->setTimestamp( $start_time );
			$t->setTimeZone(new DateTimeZone("America/Chicago"));
			$next_run =  $t->format("M jS g:i:s a");
		}
		if ( !wp_next_scheduled( TASTE_TRANS_CRON_HOOK ) ) {
			$result = wp_schedule_event( $start_time, $frequency, TASTE_TRANS_CRON_HOOK);
		}
		if ($result) {
			$ret_array = array(
				'success' => $next_run,
			);
		} else {
			$ret_array = array(
				'error' => "Error setting up Transaction Build Cron Event",
			);
		}
	} else {
		$result = wp_clear_scheduled_hook(TASTE_TRANS_CRON_HOOK);
		if ($result) {
			$ret_array = array(
				'success' => 'Transaction Build Cron Event cancelled',
			);
		} else {
			$ret_array = array(
				'error' => "Error cancelling the Transaction Build Cron Event",
			);
		}
	}
	echo wp_json_encode($ret_array);
	return;
}