<?php 

defined('ABSPATH') or die('Direct script access disallowed.');

//require_once(plugin_dir_path(__FILE__). 'build_trans_table.php');

function tf_ajax_build_trans() {

	if (!check_ajax_referer('taste-financial-nonce','security', false)) {
		echo '<h2>Security error loading data.  <br>Please Refresh the page and try again.</h2>';
		wp_die();
	}

	$start_date = $_POST['start_date'];

	require_once TFINANCIAL_PLUGIN_INCLUDES.'/build-trans-table.php';
	build_trans_table($start_date);

	wp_die();
}

if ( is_admin() ) {
	add_action('wp_ajax_build_trans','tf_ajax_build_trans');
}