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

	$file = TFINANCIAL_PLUGIN_LOGS_PATH . "/trans_build/trans.log";

	file_put_contents($file, $log_str, FILE_APPEND);
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

 
function tf_comm_vat_per_payment($payment, $commission_val, $payment_date) {
	// VAT rate is a set amount in Ireland that rarely changes.  It should
	// not be attached to the product.  Due to a temp change in the rate to
	// mitigate the economic damages of covid, this code needs to include that
	// 23% - normal rate   21% rate from 09/01/2020 to 2/28/2021

	$vat_start_date = strtotime("2020-09-01");
	$vat_end_date = strtotime("2021-02-28");
	$pay_date_comp = strtotime($payment_date);

	$vat_val = $pay_date_comp >= $vat_start_date && $pay_date_comp <= $vat_end_date ? 21 : 23;
	$comm_pct = $commission_val / 100;
	$vat_pct = $vat_val / 100;
	$gross = $payment / (1 - $comm_pct - ($comm_pct * $vat_pct));
	$commission = round($gross * $comm_pct, 2);
	$vat = round($commission * $vat_pct, 2);
	// need to recalc the gross to avoid rounding issues 
	$gross = $payment + $commission + $vat;
	return array(
		'pay_gross' => $gross,
		'pay_comm' => $commission,
		'pay_vat' => $vat,
		'vat_val' => $vat_val
	);
}

function tf_payment_status_to_string($p_status) {
	switch($p_status) {
		case 1:
			return "Paid";
			break;
		case 2:
			return "Historical";
			break;
		case 3:
			return "Pending";
			break;
		case 4:
			return "Processing";
			break;
		default:
			return "Invalid Status";
	}
}

function tf_payment_status_string_to_db($p_status) {
	switch($p_status) {
		case "Paid":
			return 1;
			break;
		case 'Historical':
			return 2;
			break;
		case "Pending":
			return 3;
			break;
		case "Processing":
			return 4;
			break;
		default:
			return "Invalid Status";
	}
}
