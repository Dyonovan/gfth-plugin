<?php

class export_table_to_csv
{
    function __construct()
    {
        $filename = 'product-prices';

        $csvFile = $this->generate_csv();

        /*header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . $filename . ".csv\";");
        header("Content-Transfer-Encoding: binary");*/

        $upload_dir = wp_upload_dir();

        $fp = fopen($upload_dir['basedir'] . '/csv-downloads/' . $filename . '.csv' , 'w');

        foreach($csvFile as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);

        echo $upload_dir['baseurl'] . '/csv-downloads/' . $filename . '.csv';
        exit;
    }


    function generate_csv()
    {
        $args = array(
            'posts_per_page' => -1,
            'orderby' => 'title',
            'post_type' => 'product_variation'
        );
        $posts = get_posts($args);
        wp_reset_postdata();

        $parent_id = 0;
        $title = '';
        $csv_array = array();

        //Add Header Titles
        array_push($csv_array, Array('Title', 'SKU', 'Size', 'Price', 'DO-NOT-TOUCH'));

        foreach ($posts as $post) {
            if ($parent_id != $post->post_parent) {
                $title = get_the_title($post->post_parent);
            }

            $line = Array(
                $title,
                get_post_meta($post->ID, '_sku', true),
                get_post_meta($post->ID, 'attribute_pa_size', true),
                get_post_meta($post->ID, '_price', true),
                $post->ID
            );

            array_push($csv_array, $line);
        }
        return $csv_array;
    }
}


