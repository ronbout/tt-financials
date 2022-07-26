<?php
/*
Template Name: Build Transaction Table
*/

/**
 *  Date:  8/15/2021
 * 	Author: Ron Boutilier
 */
defined('ABSPATH') or die('Direct script access disallowed.');

?>
<!DOCTYPE HTML>

<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<script src="https://code.jquery.com/jquery-3.5.1.min.js"
		integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0="
		crossorigin="anonymous">
	</script>
	<script src="<?php echo TFINANCIAL_PLUGIN_INCLUDES_URL?>/js/build-trans-table-bulk.js"></script>
	<style>
		* {
			margin: 8px;
			padding: 8px;
		}
		.entry {
			width: 500px;
			margin: auto;
			height: 200px;
			border: 2px solid black;
			display: flex;
		}
		#results {
			width: 500px;
			margin: auto;
			height: 400px;
			border: 2px solid black;
		}
	</style>
</head>
<body>
	<?php 
		echo "
					<script>
						let tasteFinancial = {}
						tasteFinancial.ajaxurl = '". admin_url( 'admin-ajax.php' ) . "'
						tasteFinancial.security = '" . wp_create_nonce('tf-admin-ajax-nonce') . "'
					</script>
				";
		?>
	<header>Dummy page to test transaction table build</header>

	<main>
		<div class="entry">
			<div><input id="start-date" type="date" value="2020-01-01"></div>
			<div><button data-page="test-trans-bulk" id="run-build-trans" type="button">Run</button>	</div>
		</div>
		<div id="results">Results here</div>
	</main>

</body>