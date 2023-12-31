<?php 

	defined('ABSPATH') or die('Direct script access disallowed.');
	// Enqueue Jobs Page Stylesheets and Scripts

	add_action('admin_enqueue_scripts', 'tfinancial_load_admin_resources');

	function tfinancial_load_admin_resources($page) {

			wp_enqueue_style( 'tf-admin-css', TFINANCIAL_PLUGIN_INCLUDES_URL."/style/css/tf-admin.css" );
			wp_enqueue_script( 'tfinancial-admin-js', TFINANCIAL_PLUGIN_INCLUDES_URL . '/js/tf-admin.js', array('jquery', 'jquery-ui-datepicker'), false, true);
			wp_localize_script('tfinancial-admin-js', 'tasteFinancial', 
				array(
					'ajaxurl' => admin_url('admin-ajax.php'),
					'security' => wp_create_nonce( 'tf-admin-ajax-nonce' )
				));
			// wp_enqueue_script( 'tfinancial-venue-select-js', TFINANCIAL_PLUGIN_INCLUDES_URL . '/js/thetfinancial-venue-select.js', $dep_array, false, true);
	}