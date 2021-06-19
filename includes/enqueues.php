<?php 


defined('ABSPATH') or die('Direct script access disallowed.');
//* Enqueue Jobs Page Stylesheets and Scripts
// add_action('wp_enqueue_scripts', 'taste_venue_load_resources');

// function taste_venue_load_resources() {

// 	if ( is_single("venue-manager") ) {		
// 		wp_enqueue_style( 'bootstrap-css', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css', array() );

// 		wp_enqueue_style( 'taste-venue-css', TASTE_PLUGIN_INCLUDES_URL."/css/thetaste-venue.css" , array() );
// 		wp_enqueue_script( 'taste-venue-js', TASTE_PLUGIN_INCLUDES_URL . '/js/thetaste-venue.js', array( 'jquery' ), false, true);
// 		//wp_enqueue_script( 'taste-venue-js', TASTE_PLUGIN_INCLUDES_URL . '/js/thetaste-venue.min.js', array( 'jquery' ), false, true);
// 	}
// }

// add_action('wp_enqueue_scripts', 'jl_localize_script');

// function jl_localize_script() {
// 	global $query_flag, $q_string, $social_url_placeholder, $social_title_placeholder;
	
// 	wp_localize_script('jl-js', 'jobListing', array(
// 			'security' => wp_create_nonce('jl_ajax_nonce'),
// 			'ajaxurl' => admin_url( 'admin-ajax.php' ),
// 			'queryFlag' => $query_flag,
// 			'query' => $q_string,
// 			'socialURLPlaceholder' => $social_url_placeholder,
// 			'socialTitlePlaceholder' => $social_title_placeholder,
// 			'indeedFlag'	=> false,
// 		));
// }