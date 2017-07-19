<?php

/*
Plugin Name: GFTH Custom Plugin
Description: Custom Plugin for GiftsFromTheHeart.ca
Version: 1.2
Author: James Rogers
Author URI: http://dyonovan.com
*/
defined('ABSPATH') or die('No script kiddies please!');

define('GFTH_PLUGIN_DIR', plugin_dir_path(__FILE__));
require_once(GFTH_PLUGIN_DIR . '/functions/new_products.php');
require_once(GFTH_PLUGIN_DIR . '/functions/prices_export.php');
require_once(GFTH_PLUGIN_DIR . '/functions/prices_import.php');
require_once(GFTH_PLUGIN_DIR . '/functions/delete_drafts.php');
require_once(GFTH_PLUGIN_DIR . '/functions/create_qcode.php');
require_once(GFTH_PLUGIN_DIR . '/functions/create_qcode_import.php');


// Hook for adding admin menus
add_action('admin_menu', 'gfth_add_pages');

function gfth_add_pages()
{
	global $gfth_settings;
	$gfth_settings = add_menu_page(__('Gifts From The Heart', 'gfth'), __('Gifts From The Heart', 'gfth'), 'manage_options', 'gfth', 'gfth_top_page');
}

function gfth_top_page()
{ ?>`
	<div class="wrap">
		<h2><?php echo __('Gifts From The Heart', 'gfth'); ?></h2>

		<h3>Update Product Prices</h3>

		<form id="form_download_prices" class="form_update_prices" method="POST" enctype="multipart/form-data">
			<input id="price_download" type="submit" class="button-primary" name="price_download"
			       value="Download Price CSV"/>
			&nbsp;&nbsp;&nbsp;
			<img src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" id="gfth_loading_download" class="gfth_working waiting"/>
			&nbsp;&nbsp;&nbsp;
			<span id="gfth_loading_download_text" class="gfth_working blink">Working...</span>
		</form>
		<p></p>
		<form id="form_upload_prices" class="form_upload_prices" method="POST">
			<input id="price_file" type="file" name="csv" value="" accept=".csv"/><br>
			<input id="price_upload" type="submit" class="button-primary" name="price_upload" value="Import Price CSV">
			&nbsp;&nbsp;&nbsp;
			<img src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" id="gfth_loading_upload" class="gfth_working waiting"/>
			&nbsp;&nbsp;&nbsp;
			<span id="gfth_loading_upload_text" class="gfth_working blink">Working...</span><br>
		</form>

		<div id="update_prices">

		</div>

		<h3>Mass Add New Products</h3>

		<div>
			<form id="form_add_product" class="form_add_product" method="POST" enctype="multipart/form-data" target="_blank">
				<input id="product_file" type="file" name="csv" value="" accept=".csv"/><br>
				Test CSV?
				<label for="Yes">Yes</label>
				<input type="radio" id="Yes" name="test" value="Yes" checked/>
				<label for="No">No</label>
				<input type="radio" id="No" name="test" value="No"/>
				&nbsp;&nbsp;&nbsp;
				<input id="product_submit" type="submit" class="button-primary" name="testnewproduct"
				       value="Import CSV" />
				&nbsp;&nbsp;&nbsp;
				<img src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" id="gfth_loading" class="gfth_working waiting"/>
				&nbsp;&nbsp;&nbsp;
				<span id="gfth_loading_text" class="gfth_working blink">Working...</span>
			</form>
		</div>

		<div id="importresults">

		</div>

		<h3>Misc Utils</h3>
		<div>
			<form id="form_misc" class="misc" method="POST">
				<input type="submit" id="delete_drafts" class="button-primary" name="delete_drafts" value="Delete Drafts">
				&nbsp;&nbsp;&nbsp;
				<img src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" id="gfth_loading_delete" class="gfth_working waiting"/>
				&nbsp;&nbsp;&nbsp;
				<span id="gfth_loading_delete_text" class="gfth_working blink">Working...</span>
			</form>
			<p>
				<!--<form id="form_fix" class="fix" method="post">-->
				<!--<input type="text" id="child_id"/>-->
				<!--<br><input type="submit" id="fix" class="button-primary" name="fix" value="Fix ID">-->
				<!--</form>-->
			</p>
			<div id="fix_response"
		</div>
        <h3>Create Q-Codes</h3>
        <div>
            <form id="form_qcode" class="qcode" method="post">
                <select name="tags" id="tags">
                    <option value="default">Select Tag....</option>
                    <?php
                        $tags = get_terms('product_tag');
                        foreach($tags as $tag) {
                            echo '<option value="' . $tag->slug . '">' . $tag->slug . '</option>';
                        }
                    ?>
                </select>
                <input type="submit" id="tag_submit" class="button-primary" name="tag_submit" value="Create QCodes">
            </form>
        </div>
        <div id="qcode_response">

        </div>
        <h3>QR-Code from Import</h3>
        <div>
            <form id="form_import_qrcode" class="import_qrcode" method="post">
                <input id="order_file" type="file" name="order_csv" value="" accept=".rpt"/><br>
                <input type="submit" id="qr_order_submit" class="button-primary" name="qr_order_submit" value="Upload Order">
            </form>
        </div>
        <div id="qr_order_response">

        </div>
	</div>
	<?php
}

function gfth_load_scripts($hook)
{
	global $gfth_settings;
	if ($hook != $gfth_settings) return;

	wp_enqueue_script('gfth-ajax', plugin_dir_url(__FILE__) . 'js/gfth-ajax.js', array('jquery'));
	wp_localize_script('gfth-ajax', 'gfth_vars', array('gfth_nonce' => wp_create_nonce('gfth-nonce')));
	wp_enqueue_style('gfth-css', plugin_dir_url(__FILE__) . 'css/gfth-css.css');
}
add_action('admin_enqueue_scripts', 'gfth_load_scripts');

function gfth_template_redirect() {
	if ($_SERVER['REQUEST_URI']=='wp-content/uploads/csv-downloads/product-prices.csv') {
		header("Content-type: application/x-msdownload",true,200);
		header("Content-Disposition: attachment; filename=product-prices.csv");
		header("Pragma: no-cache");
		header("Expires: 0");
		echo 'data';
		exit();
	}
}
add_action('template_redirect','gfth_template_redirect');

function gfth_process_ajax()
{

	if (!isset($_POST['gfth_nonce']) || !wp_verify_nonce($_POST['gfth_nonce'], 'gfth-nonce'))
		wp_die('You do not have permissions for this');

	if ($_POST['do'] == 'price_download') {
		new export_table_to_csv();
		exit;
	} else if($_POST['do'] == 'delete_drafts') {
		new delete_drafts();
		exit;
	} else if ($_POST['do'] == 'fix_id') {
		fix_id($_POST['id']);
		exit;

	} else if($_POST['do'] == 'update_prices') {
		if (empty($_FILES['csv']['name']) || $_FILES['csv']['error'] > 0) {
			echo "<script type='text/javascript'> alert('Please select a valid file...')</script>";
			exit;
		}
		$tmpName = $_FILES['csv']['tmp_name'];
		$csvAsArray = array_map('str_getcsv', file($tmpName));

		new prices_import($csvAsArray);

		exit;
	} else if($_POST['do'] == 'upload_products') {

		if (empty($_FILES['csv']['name']) || $_FILES['csv']['error'] > 0) {
			echo "<script type='text/javascript'> alert('Please select a valid file...')</script>";
			exit;
		}
		$tmpName = $_FILES['csv']['tmp_name'];
		$csvAsArray = array_map('str_getcsv', file($tmpName));

		if ($_POST['test'] == "Yes")
			new import_new_products($csvAsArray, true);
		elseif ($_POST['test'] == "No")
			new import_new_products($csvAsArray, false);
		else
			echo "<script type='text/javascript'> alert('Dyo screwed up...')</script>";
	} else if($_POST['do'] == 'create_qcode') {
	    new create_qcode($_POST['tag']);
    } else if ($_POST['do'] == 'process_qrcode') {
        if (empty($_FILES) || $_FILES['csv']['error'] > 0 ) {
            echo "<script type='text/javascript'> alert('Please select a valid file...')</script>";
            exit;
        }
        $tmpName = $_FILES['csv']['tmp_name'];
        $csvAsArray = array_map('str_getcsv', file($tmpName));

        new create_qcode_import($csvAsArray);
    }
	exit;
}
add_action('wp_ajax_gfth_get_results', 'gfth_process_ajax');

function fix_id($id) {
	$parentid = get_post_ancestors($id);
	$sku = get_post_meta($parentid[0], '_sku', true);
	update_post_meta($parentid[0], '_sku', '');
	update_post_meta($id, '_sku', $sku);
}

//Hook to add reply to field for new order emails
add_filter( 'woocommerce_email_headers', 'mycustom_headers_filter_function', 10, 3);

function mycustom_headers_filter_function( $headers, $object, $order ) {
	if ($object == 'new_order') {
		$headers .= 'Reply-to: '.$order->billing_first_name.' '.$order->billing_last_name.' <'.$order->billing_email.'>' . "\r\n";
	}

	return $headers;
}

/*
 * Fix Redirect after add to cart
 */
//add_filter ('add_to_cart_redirect', 'redirect_to_previousCat');
/*function redirect_to_previousCat( $url ) {
	$product_id = absint( $_REQUEST['add-to-cart'] );
	$product_cat_slug = '';

	$terms = get_the_terms( $product_id, 'product_cat' );
	foreach ( $terms as $term ) {
		$product_cat_slug = $term->slug;
		break;
	}
	if( $product_cat_slug ){
		$url = add_query_arg( 'product_cat', $product_cat_slug, site_url() );
	}
	return $url;
}*/
function redirect_to_current_product() {
	//Get product ID
	if ( isset( $_POST['add-to-cart'] ) ) {
		//$product_id = (int) apply_filters( 'woocommerce_add_to_cart_product_id', $_POST['add-to-cart'] );
		//global $wp;
		//$current_url = home_url( add_query_arg( NULL, NULL ) );
		//$current_url = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
		$url = '';
		return $url;//get_permalink( $product_id );
	}
}
add_filter ('woocommerce_add_to_cart_redirect', 'redirect_to_current_product');
