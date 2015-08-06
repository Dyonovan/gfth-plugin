<?php
/*
Plugin Name: GFTH Custom Plugin
Version: 1.0
*/
defined('ABSPATH') or die('No script kiddies please!');

define('GFTH_PLUGIN_DIR', plugin_dir_path(__FILE__));
require_once(GFTH_PLUGIN_DIR . '/functions/new_products.php');
require_once(GFTH_PLUGIN_DIR . '/functions/prices_export.php');
require_once(GFTH_PLUGIN_DIR . '/functions/prices_import.php');
require_once(GFTH_PLUGIN_DIR . '/functions/delete_drafts.php');


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
    } else if($_POST['do'] == 'update_prices') {
        if (empty($_FILES['csv']['name']) || $_FILES['csv']['error'] > 0) {
            echo "<script type='text/javascript'> alert('Please select a valid file...')</script>";
            exit;
        }
        $tmpName = $_FILES['csv']['tmp_name'];
        $csvAsArray = array_map('str_getcsv', file($tmpName));

        new prices_import($csvAsArray);

        exit;
    } else if ($_POST['do'] == 'upload_products'){

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

        exit;
    }
    echo "<script type='text/javascript'> alert('Something went wrong...')</script>";
    exit;
}
add_action('wp_ajax_gfth_get_results', 'gfth_process_ajax');

