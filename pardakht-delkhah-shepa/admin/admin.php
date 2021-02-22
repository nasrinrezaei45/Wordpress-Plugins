<?php
function wpdocs_register_my_custom_menu_page()
{
    add_menu_page(
        ' پرداخت دلخواه شپا',
        'پرداخت دلخواه شپا',
        'manage_options',
        'pardakt_delkhah_pdp',
        'pardakt_delkhah_pay_show'
    );
    add_submenu_page(
        'pardakt_delkhah_pdp',
        'تنظیمات',
        'تنظیمات',
        'manage_options',
        'option_pdp',
        'pdp_option_pay_show'
    );
    add_submenu_page(
        'pardakt_delkhah_pdp',
        'راهنما',
        'راهنما',
        'manage_options',
        'help_pdp',
        'pdp_help_pay_show'
    );
}

add_action('admin_menu', 'wpdocs_register_my_custom_menu_page');

function pardakt_delkhah_pay_show()
{
    global $wpdb, $table_prefix;
    $pdp_option = get_option('pdp_options');
    $search = "WHERE 1 ";
    if (isset($_GET['query']) && !empty ($_GET['query'])) {
        $query = $wpdb->_escape($_GET['query']);
        $search .= "AND payment_ref_num LIKE '%{$query}%'";
    }
    $payment_items = $wpdb->get_results("SELECT * FROM {$table_prefix}pdp_payments {$search}");
    $total = $wpdb->get_results("SELECT sum(payment_amount) as result_value FROM {$table_prefix}pdp_payments");
    $total_mon = $wpdb->get_results("SELECT payment_paid_at,sum(payment_amount) as total_mon FROM {$table_prefix}pdp_payments GROUP BY payment_paid_at ORDER BY payment_paid_at");

    PDP_load_tpl('payments', compact('payment_items','total','total_mon'));
}

function pdp_option_pay_show()
{
    $settings = get_option('pdp_options');
    if (isset($_POST['pdp_save_option'])) {
        if (isset ($_GET['tab'])) {
            $tab = $_GET['tab'];
        } else {
            $tab = 'general';
        }


        switch ($tab) {
            case 'general' :
                $settings['api_pay'] = isset($_POST['pdp_api_pay']) ? $_POST['pdp_api_pay'] : '';
                $settings['url_payment'] = isset($_POST['pdp_url_payment']) ? $_POST['pdp_url_payment'] : '';
                break;
            case 'forms' :
                $settings['description_form'] = $_POST['pdp_description_form'];
                $settings['description_verify'] = $_POST['pdp_description_verify'];
                $settings['color_btn_payment'] = isset($_POST['pdp_color_btn_payment']) ? $_POST['pdp_color_btn_payment'] : '39b54a';
                $settings['title_form_payment'] = $_POST['pdp_title_form_payment'];
                $settings['txt_btn_payment'] = isset($_POST['pdp_txt_btn_payment']) ? $_POST['pdp_txt_btn_payment'] : 'پرداخت وجه';
                $settings['hidden_description'] = $_POST['pdp_hidden_description'];
                break;
            case 'notifications' :
                $settings['panel_payamak'] = [
                    'panel_name' => $_POST['pdp_panel_payamak'],
                    'username_sms' => $_POST['pdp_username_sms'],
                    'pass_sms' => $_POST['pdp_pass_sms'],
                    'number_sms' => $_POST['pdp_number_sms'],
                    'mobile_number_admin_sms' => $_POST['pdp_mobile_number_admin_sms'],
                    'active_number_users' => $_POST['pdp_active_number_users'],
                    'active_number_admin' => $_POST['pdp_active_number_admin'],
                    'text_sms_user' => $_POST['pdp_text_sms_user'],
                    'text_sms_admin' => $_POST['pdp_text_sms_admin'],
                ];
                break;
        }

        update_option('pdp_options', $settings);
    }
    PDP_load_tpl('settings');
}

function pdp_help_pay_show()
{
    PDP_load_tpl('help');
}