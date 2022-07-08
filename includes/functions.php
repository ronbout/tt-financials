<?php
/**
 * 	Common functions for thetaste-venue plugin
 * 	
 * 	9/22/2020	Ron Boutilier
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

// this is the same function as found in taste-venue plugin and would be available,
// but if the plugins ever got separated, I wouldn't want this plugin to error 
function tf_calc_net_payable($product_price, $vat_val, $commission_val, $cnt, $round_flag=true) {
	// if the round flag is set, need to round revenue, commission and VAT 
	// before calculating the payable. Then, eturn the rounded payable
	$grevenue = $cnt * $product_price;
	$commission = ($grevenue / 100) * $commission_val;
	$vat = ($commission / 100) * $vat_val; 
	if ($round_flag) {
		$grevenue = round($grevenue, 2);
		$commission = round($commission, 2);
		$vat = round($vat,2);
	}
	$payable = $grevenue - ($commission + $vat);
	if ($round_flag) {
		$payable = round($payable, 2);
	}
	
	return array(
		'gross_revenue' => $grevenue,
		'commission' => $commission,
		'vat' => $vat,
		'net_payable' => $payable
	);
}

function local_debug_write($info) {
	
	$file = TFINANCIAL_PLUGIN_LOGS_PATH . "/debug/debug_" . time() . ".txt";

	$msg = serialize($info);

	file_put_contents($file, $msg);
}

function trans_build_log_write($log_str) {
	$fname_timestamp = date("Y_m_d_H_i_s");

	$file = TFINANCIAL_PLUGIN_LOGS_PATH . "/trans_build/trans_$fname_timestamp.txt";

	file_put_contents($file, $log_str);
}

function tf_check_query($convert_array=false) {
	// checks and returns the query string if present
	$query_str =  isset($_SERVER['QUERY_STRING']) ? urldecode($_SERVER['QUERY_STRING']) : '';
  if (!$convert_array) {
    return $query_str;
  }
  parse_str($query_str, $query_array);
  return $query_array;
}