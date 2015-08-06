<?php

/**
 * Created by PhpStorm.
 * User: jrogers
 * Date: 03/08/15
 * Time: 4:50 PM
 */
class prices_import
{
    function __construct($csv) {
        set_time_limit(0);
        wp_suspend_cache_invalidation(true);
        wp_defer_term_counting(true);

        $count = 0;
        $title_count = 0;
        $first_run = true;

        foreach ($csv as $data) {
            // Do nothing with header
            if ($first_run) {
                $first_run = false;
                continue;
            }

            //Check for size in db
            $size = get_term_by('slug', $data[2], 'pa_size');
            if (!$size) {
                echo '<span style="color: #ff0000">Failed on ' .  $data[1] . ' (Finding Size)</span><br>';
                continue;
            }

            //check to see if we need to update title
            $parentid = get_post_ancestors($data[4]);
            if (trim($data[0]) != get_the_title($parentid[0])) {
                $array = array(
                    'ID'            => $parentid[0],
                    'post_title'    => trim($data[0])
                );
                wp_update_post($array);
                $title_count++;
            }

            //Make sure size is attached to parent
            $terms = wp_get_object_terms($parentid, 'pa_size');
            $found = false;
            foreach ($terms as $term) {
                if ($term->slug == $size->slug) {
                    $found = true;
                }
            }
            if (!$found) {
                wp_set_object_terms($parentid[0], $size->term_id, 'pa_size', true);
            }
            //Update Sku, size, price
            update_post_meta($data[4], 'attribute_pa_size', (string)$size->slug);
            update_post_meta($data[4], '_sku', trim($data[1]));
            update_post_meta($data[4], '_price', $data[3]);
            update_post_meta($data[4], '_regular_price', $data[3]);

            $count++;
        }
        echo '<p>Updated ' . $title_count . ' product titles';
        echo '<br>Updated ' . $count . ' product variations';

        wp_suspend_cache_invalidation(false);
        wp_defer_term_counting(false);
    }
}