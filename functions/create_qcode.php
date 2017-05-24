<?php

/**
 * Created by PhpStorm.
 * User: James
 * Date: 5/17/2017
 * Time: 12:57 PM
 */

/**
 * TODO Check if tag has been selected
 *
 */
class create_qcode
{
    function __construct($tag)
    {
        ob_start();

        include(GFTH_PLUGIN_DIR . '/functions/phpqrcode/qrlib.php');

        if (!file_exists(get_home_path() . 'QRCodes')) {
            mkdir(get_home_path() . 'QRCodes');
        }
        $tempDir = get_home_path() . 'QRCodes/';

        $output_list = "";

        //Delete existing files
        $files = glob($tempDir . '*');
        foreach($files as $file){ // iterate files
            if(is_file($file))
                unlink($file); // delete file
        }

        //get all products with a given tag(slug)
        $args = array(
            'post-type' => 'product_variation',
            'product_tag' => $tag,
            'post_status' => 'publish',
            'posts_per_page' => -1
            //'orderby' => 'parent'
        );
        $products = new WP_Query($args);

        //loop through posts
        if ($products->have_posts()) {
            foreach ($products->posts as $post) {
                //echo '<br><br>' . $post->post_title . '<br>';
                $var_main = new WC_Product_Variable($post->ID);
                $variations = $var_main->get_available_variations();
                $temp = array();
                $output_list .= "\n\n" . strip_tags($post->post_title) . "\n";
                foreach ($variations as $var) {
                    $temp[] = array(
                        'BB' => (substr($var['sku'], -2) === 'BB' ? 1 : 0),
                        'Sku' => $var['sku'],
                        'Size' => $var['attributes']['attribute_pa_size'],
                        'Price' => $var['display_price']
                    );
                }
                $sorted = $this->array_orderby($temp, 'BB', SORT_ASC, 'Price', SORT_ASC);

                foreach ($sorted as $ind) {
                    $output_list .= $ind['Sku'] . ' - ' . $ind['Size'] . ' - ' . $ind['Price'] . "\n";
                }

                $temp_array = array(
                    'Title' => strip_tags($post->post_title),
                    'Variations' => $sorted
                );

                $json = json_encode($temp_array);

                $filename = strip_tags($post->post_title) . '.png';
                QRcode::png($json, $tempDir.$filename, QR_ECLEVEL_M);
            }

            wp_reset_postdata();
        }

        //Output text file
        file_put_contents($tempDir . $tag . '.txt', $output_list);

        //Create zip
        $zipname = $tag . '.zip';
        $zip = new ZipArchive();

        //TODO check if exists if it does OVERWRITE else CREATE
        $res = $zip->open($tempDir . $zipname, ZipArchive::CREATE);

        if ($handle = opendir($tempDir . '.')) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != ".." && !strstr($entry,'.zip')) {
                    $zip->addFile($tempDir . $entry, $entry);
                }
            }
            closedir($handle);
        }

        $zip->close();

        //Download zip
        echo get_site_url() . '/QRCodes/' . $zipname;

        ob_flush();
        exit();
    }

    function array_orderby()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }
}