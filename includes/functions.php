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

// function local_debug_write($info) {
	
// 	$file = "C:/Users/ronbo/Documents/jim-stuff/tmp/local_debug_" . time() . ".txt";

// 	$msg = serialize($info);

// 	file_put_contents($file, $msg);
// }
