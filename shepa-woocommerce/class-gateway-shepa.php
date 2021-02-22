<?php

if (!defined('ABSPATH'))
    exit;


function Load_Shepa_Payment_Gateway()
{
    if (class_exists('WC_Payment_Gateway') && !class_exists('WC_SHEPA') && !function_exists('Woocommerce_Add_Shepa_Gateway')) {
        add_filter('woocommerce_payment_gateways', 'Woocommerce_Add_Shepa_Gateway');
        function Woocommerce_Add_Shepa_Gateway($methods)
        {
            $methods[] = 'WC_SHEPA';
            return $methods;
        }

        add_filter('woocommerce_currencies', 'add_IR_currency_shepa');

        if (!function_exists('add_IR_currency')) {
            function add_IR_currency_shepa($currencies)
            {
                $currencies['IRR'] = __('ریال', 'woocommerce');
                $currencies['IRT'] = __('تومان', 'woocommerce');
                return $currencies;
            }
        }

        add_filter('woocommerce_currency_symbol', 'add_IR_currency_symbol_shepa', 10, 2);
        if (!function_exists('add_IR_currency_symbol_shepa')) {
            function add_IR_currency_symbol_shepa($currency_symbol, $currency)
            {
                switch ($currency) {
                    case 'IRR':
                        $currency_symbol = 'ریال';
                        break;
                    case 'IRT':
                        $currency_symbol = 'تومان';
                        break;
                }
                return $currency_symbol;
            }
        }


        class WC_SHEPA extends WC_Payment_Gateway
        {
            protected $base_url = 'http://merchant.shepa.com/';
            protected $base_url_sandbox = 'https://sandbox.shepa.com/';

            public function __construct()
            {

                $this->id = 'WC_SHEPA';
                $this->method_title = __('پرداخت امن شپا', 'woocommerce');
                $this->method_description = __('تنظیمات درگاه پرداخت شپا برای افزونه فروشگاه ساز ووکامرس', 'woocommerce');
                $this->icon = apply_filters('WC_SHEPA_logo', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/images/logo.png');
                $this->has_fields = false;

                $this->render_form_field();
                $this->init_settings();

                $this->title = $this->settings['title'];
                $this->description = $this->settings['description'];
                $this->api_sandbox = $this->settings['api_sandbox'];


                $this->api_key = $this->settings['api_key'];

                $this->success_massage = $this->settings['success_massage'];
                $this->failed_massage = $this->settings['failed_massage'];

                if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>='))
                    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
                else
                    add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));

                add_action('woocommerce_receipt_' . $this->id . '', array($this, 'Send_to_Shepa_Gateway'));
                add_action('woocommerce_api_' . strtolower(get_class($this)) . '', array($this, 'Return_from_Shepa_Gateway'));


            }


            public function admin_options()
            {
                parent::admin_options();
            }

            public function render_form_field()
            {
                $this->form_fields = apply_filters('WC_SHEPA_Config', array(
                        'base_confing' => array(
                            'title' => __('تنظیمات پایه ای', 'woocommerce'),
                            'type' => 'title',
                            'description' => '',
                        ),
                        'enabled' => array(
                            'title' => __('فعالسازی/غیرفعالسازی', 'woocommerce'),
                            'type' => 'checkbox',
                            'label' => __('فعالسازی درگاه شپا', 'woocommerce'),
                            'description' => __('برای فعالسازی درگاه پرداخت شپا باید چک باکس را تیک بزنید', 'woocommerce'),
                            'default' => 'yes',
                            'desc_tip' => true,
                        ),
                        'title' => array(
                            'title' => __('عنوان درگاه', 'woocommerce'),
                            'type' => 'text',
                            'description' => __('عنوان درگاه که در طی خرید به مشتری نمایش داده میشود', 'woocommerce'),
                            'default' => __('پرداخت امن شپا', 'woocommerce'),
                            'desc_tip' => true,
                        ),
                        'description' => array(
                            'title' => __('توضیحات درگاه', 'woocommerce'),
                            'type' => 'text',
                            'desc_tip' => true,
                            'description' => __('توضیحاتی که در طی عملیات پرداخت برای درگاه نمایش داده خواهد شد', 'woocommerce'),
                            'default' => __('پرداخت امن به وسیله کلیه کارت های عضو شتاب از طریق درگاه شپا', 'woocommerce')
                        ),
                        'account_confing' => array(
                            'title' => __('تنظیمات حساب شپا', 'woocommerce'),
                            'type' => 'title',
                            'description' => '',
                        ),
                        'api_key' => array(
                            'title' => __('api key', 'woocommerce'),
                            'type' => 'text',
                            'description' => __('کد api درگاه شپا', 'woocommerce'),
                            'default' => '',
                            'desc_tip' => true
                        ),
                        'payment_confing' => array(
                            'title' => __('تنظیمات عملیات پرداخت', 'woocommerce'),
                            'type' => 'title',
                            'description' => '',
                        ),
                        'success_massage' => array(
                            'title' => __('پیام پرداخت موفق', 'woocommerce'),
                            'type' => 'textarea',
                            'description' => __('متن پیامی که میخواهید بعد از پرداخت موفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {transaction_id} برای نمایش کد رهگیری (توکن) شپا استفاده نمایید .', 'woocommerce'),
                            'default' => __('با تشکر از شما . سفارش شما با موفقیت پرداخت شد .', 'woocommerce'),
                        ),
                        'failed_massage' => array(
                            'title' => __('پیام پرداخت ناموفق', 'woocommerce'),
                            'type' => 'textarea',
                            'description' => __('متن پیامی که میخواهید بعد از پرداخت ناموفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {fault} برای نمایش دلیل خطای رخ داده استفاده نمایید . این دلیل خطا از سایت شپا ارسال میگردد .', 'woocommerce'),
                            'default' => __('پرداخت شما ناموفق بوده است . لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید .', 'woocommerce'),
                        ),
                    )
                );
            }

            public function process_payment($order_id)
            {

                $order = new WC_Order($order_id);
                return array(
                    'result' => 'success',
                    'redirect' => $order->get_checkout_payment_url(true)
                );
            }

            /**
             * @param $action (PaymentRequest, )
             * @param $params string
             *
             * @return mixed
             */
            public function SendRequestToShepa($params)
            {
                
                $url = "http://merchant.shepa.com/api/v1/token";
                if (!empty($this->api_sandbox)) {
                    $url = "https://sandbox.shepa.com/v1/token";
                }
                try {
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Shepa Rest Api v1');
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($params)
                    ));
                    $result = curl_exec($ch);
					
                    return json_decode($result);
                } catch (Exception $ex) {
                    return false;
                }
            }

            public function Send_to_Shepa_Gateway($order_id)
            {
                global $woocommerce;

                $woocommerce->session->order_id_shepa = $order_id;
                $order = new WC_Order($order_id);
                $currency = $order->get_order_currency();
                $currency = apply_filters('WC_SHEPA_Currency', $currency, $order_id);

                $Amount = intval($order->order_total);

                $Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_before_check_currency', $Amount, $currency);
                if (strtolower($currency) == strtolower('IRT') ||
                    strtolower($currency) == strtolower('TOMAN') ||
                    strtolower($currency) == strtolower('Iran TOMAN') ||
                    strtolower($currency) == strtolower('Iranian TOMAN') ||
                    strtolower($currency) == strtolower('Iran-TOMAN') ||
                    strtolower($currency) == strtolower('Iranian-TOMAN') ||
                    strtolower($currency) == strtolower('Iran_TOMAN') ||
                    strtolower($currency) == strtolower('Iranian_TOMAN') ||
                    strtolower($currency) == strtolower('تومان') ||
                    strtolower($currency) == strtolower('تومان ایران')
                )
                    $Amount = $Amount * 1;
                else if (strtolower($currency) == strtolower('IRHT'))
                    $Amount = $Amount * 1000;
                else if (strtolower($currency) == strtolower('IRHR'))
                    $Amount = $Amount * 100;
//                else if (strtolower($currency) == strtolower('IRR'))
//                    $Amount = $Amount / 10;


                $Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_after_check_currency', $Amount, $currency);
                $Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_irt', $Amount, $currency);
                $Amount = apply_filters('woocommerce_order_amount_total_Shepa_gateway', $Amount, $currency);

                $api_key = $this->api_key;
                $CallbackUrl = add_query_arg('wc_order', $order_id, WC()->api_request_url('WC_SHEPA'));

                $products = array();
                $order_items = $order->get_items();
                foreach ((array)$order_items as $product) {
                    $products[] = $product['name'] . ' (' . $product['qty'] . ') ';
                }
                $products = implode(' - ', $products);

                $Description = 'خرید به شماره سفارش : ' . $order->get_order_number() . ' | خریدار : ' . $order->billing_first_name . ' ' . $order->billing_last_name . ' | محصولات : ' . $products;
                $Mobile = get_post_meta($order_id, '_billing_phone', true) ? get_post_meta($order_id, '_billing_phone', true) : '-';
                $Email = $order->billing_email;
                $Paymenter = $order->billing_first_name . ' ' . $order->billing_last_name;
                $ResNumber = intval($order->get_order_number());

                $Email = !filter_var($Email, FILTER_VALIDATE_EMAIL) === false ? $Email : '';
                $Mobile = preg_match('/^09[0-9]{9}/i', $Mobile) ? $Mobile : '';


                $data = array(
                    'api' => $this->api_key,
                    'amount' => $Amount,
                    'callback' => $CallbackUrl, 'description' => $Description,
                    'email' => $Email,
                );
                if (!empty($Mobile)) {
                    $data['mobile'] = $Mobile;
                }

                $result = $this->common('token', $data);

                if ($result === false) {
                    echo "cURL Error #:" . $result;
                } else {
                    if (!empty($result->success)) {

                        wp_redirect($result->result->url);
                        exit;
                    } else {
                        $Message = ' تراکنش ناموفق بود <br>';
                        if(!empty( $result->error)) $Message .= implode("<br>", $result->error);
                        $Fault = '';
                    }
                }

                if (!empty($Message) && $Message) {

                    $Note = sprintf(__('خطا در هنگام ارسال به بانک : %s', 'woocommerce'), $Message);
                    $Note = apply_filters('WC_SHEPA_Send_to_Gateway_Failed_Note', $Note, $order_id, $Fault);
                    $order->add_order_note($Note);


                    $Notice = sprintf(__('در هنگام اتصال به بانک خطای زیر رخ داده است : <br/>%s', 'woocommerce'), $Message);
                    $Notice = apply_filters('WC_SHEPA_Send_to_Gateway_Failed_Notice', $Notice, $order_id, $Fault);
                    if ($Notice)
                        wc_add_notice($Notice, 'error');

                    do_action('WC_SHEPA_Send_to_Gateway_Failed', $order_id, $Fault);
                }
            }


            public function Return_from_Shepa_Gateway()
            {

                $status = isset($_GET['status']) ? $_GET['status'] : '';

                global $woocommerce;
                if (isset($_GET['wc_order']))
                    $order_id = $_GET['wc_order'];
                else {
                    $order_id = $woocommerce->session->order_id_shepa;
                    unset($woocommerce->session->order_id_shepa);
                }

                if ($order_id) {

                    $order = new WC_Order($order_id);
                    $currency = $order->get_order_currency();
                    $currency = apply_filters('WC_SHEPA_Currency', $currency, $order_id);

                    if ($order->status != 'completed') {
                        if ($status == "success") {
                            $Amount = intval($order->order_total);
                            $Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_before_check_currency', $Amount, $currency);
                            if (strtolower($currency) == strtolower('IRT') || strtolower($currency) == strtolower('TOMAN') || strtolower($currency) == strtolower('Iran TOMAN') || strtolower($currency) == strtolower('Iranian TOMAN') || strtolower($currency) == strtolower('Iran-TOMAN') || strtolower($currency) == strtolower('Iranian-TOMAN') || strtolower($currency) == strtolower('Iran_TOMAN') || strtolower($currency) == strtolower('Iranian_TOMAN') || strtolower($currency) == strtolower('تومان') || strtolower($currency) == strtolower('تومان ایران')
                            )
                                $Amount = $Amount * 1;
                            else if (strtolower($currency) == strtolower('IRHT'))
                                $Amount = $Amount * 1000;
                            else if (strtolower($currency) == strtolower('IRHR'))
                                $Amount = $Amount * 100;
//                            else if (strtolower($currency) == strtolower('IRR'))
//                                $Amount = $Amount / 10;


                            $data = [
                                'api' => $this->api_key,
                                "amount" => $Amount,
                                "token" => @$_GET['token']
                            ];
                            $result = $this->common('verify', $data);

                            if (!empty($result->success)) {
                                $status = 'completed';
                                $Transaction_ID = $result->result->refid;
                                $Fault = '';
                                $Message = '';
                                //                           } elseif ($result['status'] == 101) {
                                //                               $Message = 'این تراکنش قبلا تایید شده است';
//die($Message );
                                //                               $Notice = wpautop(wptexturize($Message));
                                //                               wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
                                //                               exit;
                            } else {
                                $status = 'failed';
                                $Fault = $result->success;
                                $Message = ' تراکنش ناموفق بود <br>';
                                if(!empty($result->error)) $Message .=  implode("<br>", $result->error);
                                    exit($Message);
                            }
                        } else {
                            $status = 'failed';
                            $Fault = '';
                            $Message = 'تراکنش انجام نشد .';
                        }

                        if ($status == 'completed' && isset($Transaction_ID) && $Transaction_ID != 0) {
                            update_post_meta($order_id, '_transaction_id', $Transaction_ID);
                            $order->payment_complete($Transaction_ID);
                            $woocommerce->cart->empty_cart();
                            $Note = sprintf(__('پرداخت موفقیت آمیز بود .<br/> کد رهگیری : %s', 'woocommerce'), $Transaction_ID);
                            $Note = apply_filters('WC_SHEPA_Return_from_Gateway_Success_Note', $Note, $order_id, $Transaction_ID);
                            if ($Note)
                                $order->add_order_note($Note, 1);
                            $Notice = wpautop(wptexturize($this->success_massage));
                            $Notice = str_replace("{transaction_id}", $Transaction_ID, $Notice);
                            $Notice = apply_filters('WC_SHEPA_Return_from_Gateway_Success_Notice', $Notice, $order_id, $Transaction_ID);
                            if ($Notice)
                                wc_add_notice($Notice, 'success');
                            do_action('WC_SHEPA_Return_from_Gateway_Success', $order_id, $Transaction_ID);
                            wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
                            exit;
                        } else {


                            $tr_id = ($Transaction_ID && $Transaction_ID != 0) ? ('<br/>توکن : ' . $Transaction_ID) : '';

                            $Note = sprintf(__('خطا در هنگام بازگشت از بانک : %s %s', 'woocommerce'), $Message, $tr_id);

                            $Note = apply_filters('WC_SHEPA_Return_from_Gateway_Failed_Note', $Note, $order_id, $Transaction_ID, $Fault);
                            if ($Note)
                                $order->add_order_note($Note, 1);

                            $Notice = wpautop(wptexturize($this->failed_massage));

                            $Notice = str_replace("{transaction_id}", $Transaction_ID, $Notice);

                            $Notice = str_replace("{fault}", $Message, $Notice);
                            $Notice = apply_filters('WC_SHEPA_Return_from_Gateway_Failed_Notice', $Notice, $order_id, $Transaction_ID, $Fault);
                            if ($Notice)
                                wc_add_notice($Notice, 'error');

                            do_action('WC_SHEPA_Return_from_Gateway_Failed', $order_id, $Transaction_ID, $Fault);

                            wp_redirect($woocommerce->cart->get_checkout_url());
                            exit;
                        }
                    } else {


                        $Transaction_ID = get_post_meta($order_id, '_transaction_id', true);

                        $Notice = wpautop(wptexturize($this->success_massage));

                        $Notice = str_replace("{transaction_id}", $Transaction_ID, $Notice);

                        $Notice = apply_filters('WC_SHEPA_Return_from_Gateway_ReSuccess_Notice', $Notice, $order_id, $Transaction_ID);
                        if ($Notice)
                            wc_add_notice($Notice, 'success');


                        do_action('WC_SHEPA_Return_from_Gateway_ReSuccess', $order_id, $Transaction_ID);

                        wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
                        exit;
                    }
                } else {


                    $Fault = __('شماره سفارش وجود ندارد .', 'woocommerce');
                    $Notice = wpautop(wptexturize($this->failed_massage));
                    $Notice = str_replace("{fault}", $Fault, $Notice);
                    $Notice = apply_filters('WC_SHEPA_Return_from_Gateway_No_Order_ID_Notice', $Notice, $order_id, $Fault);
                    if ($Notice)
                        wc_add_notice($Notice, 'error');

                    do_action('WC_SHEPA_Return_from_Gateway_No_Order_ID', $order_id, $Transaction_ID, $Fault);

                    wp_redirect($woocommerce->cart->get_checkout_url());
                    exit;
                }
            }

            public function common($key, $params)
            {

                $url = ($params["api"] != 'sandbox') ? $this->base_url . 'api/v1/' . $key : $this->base_url_sandbox . 'api/v1/' . $key;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                ]);
                $response = curl_exec($ch);
                $error = curl_errno($ch);
                curl_close($ch);
                $output = $error ? false : json_decode($response);

                return $output;
            }

            public function getRedirectTOGateway()
            {
                $redirect = ($this->settings['sandbox'] == 'no') ? $this->base_url . 'api/v1/%s' : $this->base_url_sandbox . 'api/v1/%s';
                return $redirect;
            }


        }

    }
}

add_action('plugins_loaded', 'Load_Shepa_Payment_Gateway', 0);