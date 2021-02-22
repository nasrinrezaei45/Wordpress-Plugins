<?php

/*
Plugin Name: درگاه پرداخت و کیف پول الکترونیک Shepa.com - افزونه MyCred
Version: 1.0.0
Description: تنظیمات درگاه پرداخت Shepa.com برای افزونه MyCred
Plugin URI: http://shepa.com
Author: Nasrin Rezaei <nasrinrezaei45@gmail.com@gmail.com> 09133239584
Author URI: https://wp-safe.ir/
*/

require_once('class-mycred-gateway-shepacom.php');

add_action( 'init', 'process_mycredit_shepa' );

function process_mycredit_shepa() {

    $check_page_exist = get_page_by_title('mycredit_shepa', 'OBJECT', 'page');
// Check if the page already exists
    if(empty($check_page_exist)) {
        $page_id = wp_insert_post(
            array(
                'comment_status' => 'close',
                'ping_status'    => 'close',
                'post_author'    => 1,
                'post_title'     => ucwords('mycredit_shepa'),
                'post_name'      => strtolower(str_replace(' ', '-', trim('mycredit_shepa'))),
                'post_status'    => 'publish',
                'post_content'   => '[mycred_buy]',
                'post_type'      => 'page',
                'post_parent'    => 'id_of_the_parent_page_if_it_available'
            )
        );
    }
}