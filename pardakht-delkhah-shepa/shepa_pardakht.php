<?php
/*
Plugin Name:افزونه پرداخت دلخواه شپا
Version: 1.0
Description: افزونه پرداخت دلخواه درگاه پی طراحی شده توسط نسرین رضایی
Plugin URI: http://shepa.com
Author: Nasrin Rezaei <nasrinrezaei45@gmail.com@gmail.com> 09133239584
Author URI: https://wp-safe.ir/
*/
defined('ABSPATH') || die("accese dined!");
define('PDP_DIR', plugin_dir_path(__FILE__));
define('PDP_URL', plugin_dir_url(__FILE__));
define('PDP_INC', PDP_DIR . 'inc/');
define('PDP_ADMIN', PDP_DIR . 'admin/');
define('PDP_ASSETS', PDP_URL . 'assets/');
define('PDP_TP_ADMIN', PDP_DIR . 'template/admin/');
define('PDP_TP_FRONTEND', PDP_DIR . 'template/frontend/');
include PDP_ADMIN . 'admin.php';
include PDP_INC . 'functions.php';
include_once('class/shepacom.class.php');
register_activation_hook(__FILE__, 'pdp_Activations_plugin');
register_deactivation_hook(__FILE__, 'pdp_Deactivation_plugin');


function pdp_Activations_plugin()
{
    global $wpdb, $table_prefix;
    $pdp_option = get_option('pdp_options');
    $pdp_payment = 'CREATE TABLE IF NOT EXISTS `' . $table_prefix . 'pdp_payments` (
                  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
                  `payment_name_user` varchar(250) COLLATE utf8_persian_ci NOT NULL,
                  `payment_amount` int(11) NOT NULL,
                  `payment_user_email` varchar(100) COLLATE utf8_persian_ci NOT NULL,
                  `payment_user_mobile` varchar(11) COLLATE utf8_persian_ci NOT NULL,
                  `payment_user_description` text COLLATE utf8_persian_ci NOT NULL,
                  `payment_res_num` varchar(60) COLLATE utf8_persian_ci NOT NULL,
                  `payment_ref_num` varchar(60) COLLATE utf8_persian_ci NOT NULL,
                  `payment_created_at` datetime NOT NULL,
                  `payment_paid_at` datetime NOT NULL,
                  `payment_status` tinyint(4) NOT NULL,
                   PRIMARY KEY  (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_persian_ci;';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($pdp_payment);
    if (empty($pdp_option['url_payment']))
        $pdp_option['url_payment'] = 'payment';
    update_option('pdp_options', $pdp_option);
}

function pdp_Deactivation_plugin()
{

}

$shepacom = new shepacom;

add_action('init', 'pdp_show_form_pardakht');

function pdp_show_form_pardakht()
{
    $current_url = PDP_get_currentUrl();
    $pdp_option = get_option('pdp_options');
    if (strpos($current_url, $pdp_option['url_payment'])) {
        pdp_show_form_page();
        exit;
    }
}

add_action('parse_request', 'pdp_url_verify');

function pdp_url_verify()
{
    $current_url = pdp_get_currentUrl();
    $res = preg_match(
        '/pdp\/verify/',
        $current_url,
        $matches);
    if ($res) {
        pdp_verify_payment();
        exit;
    }
}
