<?php

class import_new_products
{

    function __construct($csv, $test)
    {
        set_time_limit(0);
        wp_suspend_cache_invalidation(true);
        wp_defer_term_counting(true);

        $title = '';
        $first_run = true;
        $post_id = false;
        $bb = 'No';
        $countProduct = 0;
        $countVariation = 0;
        $countProductFailed = 0;
        $countVariationFailed = 0;
        $ids = array();


        echo " Starting import....<br>";

        foreach ($csv as $line) {

            // Do nothing with header
            if ($first_run) {
                $first_run = false;
                continue;
            }

            if ($title != trim($line[0])) {

                //Add product
                $title = trim($line[0]);
                $bb = $line[3];
                $post_id = $this->add_post($line);
                if (!$post_id)
                    $countProductFailed++;
                else
                    $countProduct++;

            } else if ($post_id != false) {

                //Add variation
                list ($varSuccess, $id) = $this->add_variation($title, $line, $post_id, $bb);
                if (!$varSuccess) {
                    $countVariationFailed++;
                } else {
                    $countVariation++;
                    if (!$test)
                        if (!in_array($id, $ids)) {
                            array_push($ids, $id);
                        }
                }
            }
            ob_flush();
            flush();
        }
        if (!$test) {
            foreach ($ids as $post) {
                wp_publish_post($post);
            }
        }


        echo '<p>Total Products ' . $countProduct;
        echo '<br>Total Variations ' . $countVariation;
        if ($countProductFailed > 0)
            echo '<br><span style="color: #ff0000">Failed Products ' . $countProductFailed . '</span>';
        if ($countVariationFailed > 0)
            echo '<br><span style="color: #ff0000">Failed Variations ' . $countVariationFailed . '</span>';

        wp_suspend_cache_invalidation(false);
        wp_defer_term_counting(false);
        exit;
    }

    function add_variation($title, $data, $post_id, $bb)
    {
        echo 'Adding ' . $title . ' - ' . $data[4] . ' Variation...';
        //Add Post data for variation
        $post = array(
            'post_title' => trim($data[0]) . 'Variation-' . $data[2],
            'post_name' => trim($data[0]) . 'Variation-' . $data[2],
            'post_status' => "publish",
            'post_parent' => $post_id,
            'post_type' => 'product_variation',
            'comment_status' => 'closed'
        );

        $new_post_id = wp_insert_post($post);
        if (!$new_post_id) {
            echo '<span style="color: #ff0000">Failed! (Adding Variation)</span><br>';
            return array(false, null);
        } //'is_create_taxonomy_terms' => '1'

        $the_data = Array(
            'pa_size' => Array(
                'name' => 'pa_size',
                'value' => '',
                'is_visible' => '1',
                'is_variation' => '1',
                'is_taxonomy' => '1',
                'is_create_taxonomy_terms' => '1'
            ));

        if ($data[3] == "Yes") {
            $the_data = array_merge($the_data, Array(
                'pa_barnboard-frame' => Array(
                    'name' => 'pa_barnboard-frame',
                    'value' => '',
                    'is_visible' => '1',
                    'is_variation' => '1',
                    'is_taxonomy' => '1',
                    'is_create_taxonomy_terms' => '1'
                )));
        }
        update_post_meta($new_post_id, '_product_attributes', $the_data);

        //Set sizes in both product and variable
        $size = get_term_by('name', $data[4], 'pa_size');
        if (!$size) {
            echo '<span style="color: #ff0000">Failed! (Adding Size)</span><br>';
            return array(false, null);
        }
        wp_set_object_terms($post_id, $size->term_id, 'pa_size', true);

        //Add Meta Attributes
        //update_post_meta($new_post_id, 'attribute_pa_size', (string)$data[4]);
        update_post_meta($new_post_id, 'attribute_pa_size', (string)$size->slug);
        update_post_meta($new_post_id, '_sku', $data[2]);
        update_post_meta($new_post_id, '_price', str_replace('$', '', $data[5]));
        update_post_meta($new_post_id, '_regular_price', str_replace('$', '', $data[5]));
        if ($bb == 'Yes')
            update_post_meta($new_post_id, 'attribute_pa_barnboard-frame', strtolower((string)$data[3]));
        update_post_meta($new_post_id, '_stock_status', 'instock');
        update_post_meta($new_post_id, '_visibility', 'visible');

        echo '<span style="color: #00ff00">Success</span><br>';

        return array(true, $post_id);
    }

    function add_post($data)
    {
        echo 'Adding ' . $data[0] . '...';

        //Check for duplicate Product
        $page = get_page_by_title(trim($data[0]), OBJECT, 'product');
        if (!empty($page)) {
            echo '<span style="color: #ff0000">Failed! (Duplicate Product)</span><br>';
            return false;
        }

        //create post
        $post = array(
            'post_title' => trim($data[0]),
            'post_content' => trim($data[1]),
            'post_status' => "draft",
            'post_excerpt' => $data[1],
            'post_name' => trim($data[0]),
            'post_type' => "product"
        );

        //get Catagory
        $cats = explode('|', $data[7]);
        $cat_array = array();
        foreach ($cats as $cat) {
            $temp = get_term_by('slug', $cat, 'product_cat');
            if (!$temp) {
                echo '<span style="color: #ff0000">Failed! (Getting Catagory <?php echo $cat ?>)</span><br>';
                return false;
            }
            array_push($cat_array, $temp->term_id);
        }

        //Create product in DB
        $new_post_id = wp_insert_post($post);
        if (!$new_post_id) {
            echo '<span style="color: #ff0000">Failed! (Adding product)</span><br>';
            return false;
        }

        //Add Catagory
        $result = wp_set_object_terms($new_post_id, $cat_array, 'product_cat');
        if (!$result) {
            echo '<span style="color: #ff0000">Failed! (Adding catagory)</span><br>';
            return false;
        }

        $result = wp_set_object_terms($new_post_id, 'variable', 'product_type');
        if (!$result) {
            echo '<span style="color: #ff0000">Failed! (Adding product type)</span><br>';
            return false;
        }

        if ($data[5] = 'Yes')
            wp_set_object_terms($new_post_id, array(73, 74), 'pa_barnboard-frame');

        $the_data = Array(
            'pa_size' => Array(
                'name' => 'pa_size',
                'value' => '',
                'is_visible' => '1',
                'is_variation' => '1',
                'is_taxonomy' => '1'
            ));

        if ($data[3] == "Yes") {
            $the_data = array_merge($the_data, Array(
                'pa_barnboard-frame' => Array(
                    'name' => 'pa_barnboard-frame',
                    'value' => '',
                    'is_visible' => '1',
                    'is_variation' => '1',
                    'is_taxonomy' => '1')));
        }
        update_post_meta($new_post_id, '_product_attributes', $the_data);

        update_post_meta($new_post_id, '_visibility', 'visible');

        //Add Tags
        $tags = explode(',', $data[6]);
        wp_set_object_terms($new_post_id, array_map('trim', $tags), 'product_tag');

        //Attach pic
        $upload_dir = wp_upload_dir();
        $filename = $upload_dir['basedir'] . '/gfth-pics/' . $data[8];
        $filetype = wp_check_filetype(basename($filename), null);

        if (empty(trim($data[8])) || !file_exists($filename)) {
            echo '<span style="color: #ff0000">Failed! (Cannot find picture)</span><br>';
        } else {

            if ($filetype['type'] != 'image/jpeg') {
                echo '<span style="color: #ff0000">Failed! (Bad picture)</span><br>';
            } else {
                $attachment = array(
                    'guid' => $upload_dir['baseurl'] . '/gfth-pics/' . basename($filename),
                    'post_mime_type' => $filetype['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );

                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_id = wp_insert_attachment($attachment, $filename, $new_post_id);
                if ($attach_id == 0) {
                    echo '<span style="color: #ff0000">Failed! (Adding picture)</span><br>';
                } else {
                    $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    set_post_thumbnail($new_post_id, $attach_id);
                }
            }
        }
        echo '<span style="color: #00ff00">Success</span><br>';
        return $new_post_id;
    }
}

