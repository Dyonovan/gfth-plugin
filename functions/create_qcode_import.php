<?php

/**
 * Created by PhpStorm.
 * User: James
 * Date: 7/17/2017
 * Time: 1:16 PM
 */
class create_qcode_import
{

    function __construct($csv)
    {
        $timeout = 600;
        if( !ini_get( 'safe_mode' ) )
            @set_time_limit( $timeout );

        @ini_set( 'memory_limit', WP_MAX_MEMORY_LIMIT );
        @ini_set( 'max_execution_time', $timeout );

        setlocale(LC_MONETARY, 'en_US.UTF-8');

        ob_start();

        include(GFTH_PLUGIN_DIR . '/functions/phpqrcode/qrlib.php');

        if (!file_exists(get_home_path() . 'QRCodes')) {
            mkdir(get_home_path() . 'QRCodes');
        }
        $tempDir = get_home_path() . 'QRCodes/';

        //Delete existing files
        $files = glob($tempDir . '*');
        foreach($files as $file){ // iterate files
            if(is_file($file))
                unlink($file); // delete file
        }

        $output_list = "";
        $error_count = 0;
        $good_count = 0;

        foreach($csv as $record) {
            /*$product_id = wc_get_product_id_by_sku($record[1]);
            $parent = wp_get_post_parent_id($product_id);*/
            $post = get_post(wp_get_post_parent_id(wc_get_product_id_by_sku($record[1])));
            if (!is_null($post)) {
                $var_main = new WC_Product_Variable($post->ID);
                $variations = $var_main->get_available_variations();

                $temp = array();
                $output_list .= "\n\n" . strip_tags($post->post_title) . "\n";
                foreach ($variations as $var) {
                    $temp[] = array(
                        'BB' => (substr($var['sku'], -2) === 'BB' ? 1 : 0),
                        'Sku' => $var['sku'],
                        'Size' => $var['attributes']['attribute_pa_size'],
                        'Price' => money_format('%.2n',($var['display_price'] + 0.05) / 2)
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
                $filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename);
                QRcode::png($json, $tempDir . $filename, QR_ECLEVEL_M);
                $good_count += 1;
            } else {
                $output_list .= "\n\nMISSING " . strip_tags($record[0]) . "\n";
                $error_count += 1;
            }
        }

        $output_list .= "\n\nTotal From Order: " . count($csv);
        $output_list .= "\nTotal Created: " . $good_count;
        $output_list .= "\nTotal Missing: " . $error_count . "\n\n";

        //Output text file
        file_put_contents($tempDir . 'qrcodes.txt', $output_list);

        //Create zip
        $date = date('m-d-Y-h-i-s-a', time());
        $zipname = $date . '-qrcodes.zip';
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