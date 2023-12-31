<?php
/**
 *  build-trans-bulk-cron.php
 * 
 *  routine called by wp cron to run the nightly
 *  build_trans_table_bulk() routine
 * 
 *  it will set the date to 2020-01-01, so if any changes are
 *  made to old orders, they will be captured into the trans table
 * 
 *  07/05/2022  Ronald Boutiier
 * 
 */

defined('ABSPATH') or die('Direct script access disallowed.');

require_once TFINANCIAL_PLUGIN_INCLUDES.'/build-trans-table-bulk.php';
$start_date = get_option('tf_financials_trans_start_date', '2020-01-01');

build_trans_table_bulk($start_date, true);
