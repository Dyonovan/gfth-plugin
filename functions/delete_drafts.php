<?php

/**
 * Created by PhpStorm.
 * User: jrogers
 * Date: 03/08/15
 * Time: 4:58 PM
 */
class delete_drafts
{
    function __construct()
    {
        $products = 0;

        //Products
        $args = array(
            'posts_per_page' => -1,
            'orderby' => 'title',
            'post_type' => 'product',
            'post_status' => 'draft'
        );
        $drafts = get_posts($args);
        wp_reset_postdata();
        foreach ($drafts as $draft) {
            wp_delete_post($draft->ID, true);
            $products++;
        }

        echo 'Deleted ' . $products . ' Products from drafts...';
        exit();
    }

}