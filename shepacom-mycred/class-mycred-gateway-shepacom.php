<?php

add_action('plugins_loaded', 'mycred_shepacom_plugins_loaded');

function mycred_shepacom_plugins_loaded()
{
    add_filter('mycred_setup_gateways', 'Add_shepacom_to_Gateways');

    function Add_shepacom_to_Gateways($installed)
    {
        $installed['shepacom'] = [

            'title'    => get_option('shepacom_name') ? get_option('shepacom_name') : 'درگاه پرداخت و کیف پول الکترونیک shepa.com',
            'callback' => ['myCred_shepacom']
        ];

        return $installed;
    }

    add_filter('mycred_buycred_refs', 'Add_shepacom_to_Buycred_Refs');

    function Add_shepacom_to_Buycred_Refs($addons)
    {
        $addons['buy_creds_with_shepacom'] = __('Buy Cred Purchase (shepa.com)', 'mycred');

        return $addons;
    }

    add_filter('mycred_buycred_log_refs', 'Add_shepacom_to_Buycred_Log_Refs');

    function Add_shepacom_to_Buycred_Log_Refs($refs)
    {
        $shepacom = ['buy_creds_with_shepacom'];

        return $refs = array_merge($refs, $shepacom);
    }
}

spl_autoload_register('mycred_shepacom_plugin');

function mycred_shepacom_plugin()
{


    if (!class_exists('myCRED_Payment_Gateway')) {
        return;
    }

    if (!class_exists('myCred_shepacom')) {

        class myCred_shepacom extends myCRED_Payment_Gateway
        {

            function __construct($gateway_prefs)
            {
                $types = mycred_get_types();
                $default_exchange = [];
                foreach ($types as $type => $label) {

                    $default_exchange[$type] = 1000;
                }
                parent::__construct([

                    'id'       => 'shepacom',
                    'label'    => get_option('shepacom_name') ? get_option('shepacom_name') : 'درگاه پرداخت و کیف پول الکترونیک shepa.com',
                    'defaults' => [

                        'shepacom_api'   => null,
                        'shepacom_name'  => 'درگاه پرداخت و کیف پول الکترونیک shepa.com',
                        'currency'    => 'ریال',
                        'exchange'    => $default_exchange,
                        'item_name'   => __('Purchase of myCRED %plural%', 'mycred'),
                        'mobile'      => null,
                        'description' => null
                    ]
                ], $gateway_prefs);
                if(!empty($_REQUEST['amount'])){
                    $this->buy();
                }
                if(!empty($_REQUEST["token"]) && !empty($_REQUEST["status"])){

                    $this->process();
                }
            }

            public function shepacom_Iranian_currencies($currencies)
            {
                unset($currencies);

                $currencies['ریال'] = 'ریال';
                $currencies['تومان'] = 'تومان';

                return $currencies;
            }

            function preferences()
            {
                add_filter('mycred_dropdown_currencies', [$this, 'shepacom_Iranian_currencies']);

                $prefs = $this->prefs;
                ?>

                <label class="subheader" for="<?php echo $this->field_id('shepacom_api'); ?>"><?php _e('API Key', 'mycred'); ?></label>
                <ol>
                    <li>
                        <div class="h2">
                            <input id="<?php echo $this->field_id('shepacom_api'); ?>" name="<?php echo $this->field_name('shepacom_api'); ?>" type="text" value="<?php echo $prefs['shepacom_api']; ?>" class="long"/>
                        </div>
                    </li>
                </ol>

                <label class="subheader" for="<?php echo $this->field_id('shepacom_name'); ?>"><?php _e('Title', 'mycred'); ?></label>
                <ol>
                    <li>
                        <div class="h2">
                            <input id="<?php echo $this->field_id('shepacom_name'); ?>" name="<?php echo $this->field_name('shepacom_name'); ?>" type="text" value="<?php echo $prefs['shepacom_name'] ? $prefs['shepacom_name'] : 'درگاه پرداخت و کیف پول الکترونیک shepa.com'; ?>" class="long"/>
                        </div>
                    </li>
                </ol>

                <label class="subheader" for="<?php echo $this->field_id('currency'); ?>"><?php _e('Currency', 'mycred'); ?></label>
                <ol>
                    <li>
                        <?php $this->currencies_dropdown('currency', 'mycred-gateway-shepacom-currency'); ?>
                    </li>
                </ol>

                <label class="subheader" for="<?php echo $this->field_id('item_name'); ?>"><?php _e('Item Name', 'mycred'); ?></label>
                <ol>
                    <li>
                        <div class="h2">
                            <input id="<?php echo $this->field_id('item_name'); ?>" name="<?php echo $this->field_name('item_name'); ?>" type="text" value="<?php echo $prefs['item_name']; ?>" class="long"/>
                        </div>
                        <span class="description"><?php _e('Description of the item being purchased by the user.', 'mycred'); ?></span>
                    </li>
                </ol>

                <label class="subheader"><?php _e('Exchange Rates', 'mycred'); ?></label>
                <ol>
                    <li>
                        <?php $this->exchange_rate_setup(); ?>
                    </li>
                </ol>
                <?php
            }

            public function sanitise_preferences($data)
            {
                $new_data['shepacom_api'] = sanitize_text_field($data['shepacom_api']);
                $new_data['shepacom_name'] = sanitize_text_field($data['shepacom_name']);
                $new_data['currency'] = sanitize_text_field($data['currency']);
                $new_data['item_name'] = sanitize_text_field($data['item_name']);
                $new_data['mobile'] = sanitize_text_field($data['mobile']);
                $new_data['description'] = sanitize_text_field($data['description']);

                if (isset($data['exchange'])) {

                    foreach ((array)$data['exchange'] as $type => $rate) {

                        if ($rate != 1 && in_array(substr($rate, 0, 1), ['.', ','])) {

                            $data['exchange'][$type] = (float)'0' . $rate;
                        }
                    }
                }

                $new_data['exchange'] = $data['exchange'];

                update_option('shepacom_name', $new_data['shepacom_name']);

                return $data;
            }

            public function buy()
            {

                if (!isset($this->prefs['shepacom_api']) || empty($this->prefs['shepacom_api'])) {

                    wp_die(__('Please setup this gateway before attempting to make a purchase!', 'mycred'));
                }

                $type = $this->get_point_type();
                $mycred = mycred($type);

                $amount = $mycred->number($_REQUEST['amount']);
                $amount = abs($amount);
                $cost = $this->get_cost($amount, $type);

                $to = $this->get_to();
                $from = $this->current_user_id;

                if (isset($_REQUEST['revisit'])) {

                    $payment = strtoupper($_REQUEST['revisit']);

                    $this->transaction_id = $payment;

                } else {

                    $post_id = $this->add_pending_payment([$to, $from, $amount, $cost, $this->prefs['currency'], $type]);
                    $payment = get_the_title($post_id);

                    $this->transaction_id = $payment;
                }

                $item_name = str_replace('%number%', $amount, $this->prefs['item_name']);
                $item_name = $mycred->template_tags_general($item_name);

                $from_user = get_userdata($from);

                if (extension_loaded('curl')) {

                    $api_key = $this->prefs['shepacom_api'];
                    //$callback = add_query_arg('payment_id', $this->transaction_id, $this->callback_url());
                    $amount = ($this->prefs['currency'] == 'ریال') ? $cost : ($cost * 10);
                    $amount = intval(str_replace(',', '', $amount));
                    $mobile = $this->prefs['mobile'];
                    $description = $this->prefs['description'];
                    $params = [
                        'api'          => $api_key,
                        'amount'       => $amount,
                        'callback'     => site_url()."/mycredit_shepa?p_id=".$this->transaction_id,
                        'description'  => $description
                    ];

                    if(!empty($this->prefs['mobile'])) {
                        $params["mobile"] = $this->prefs['mobile'];
                    }
                    if(!empty($this->prefs['email'])) {
                        $params["email"] = $this->prefs['email'];
                    }
                    $url = "https://merchant.shepa.com/api";
                    if($api_key == "sandbox") {
                        $url = "https://sandbox.shepa.com/api";
                    }
                    $result = $this->common($url.'/v1/token', $params);
                    if (!empty($result->success)) {

                        $message = 'شماره تراکنش ' . $result->result->token;

                        $this->log_call($payment, [__($message, 'mycred')]);

                        $gateway_url = $result->result->url;

                        wp_redirect($gateway_url);
                        exit;

                    } else {

                        $message = 'در ارتباط با وب سرویس shepa.com خطایی رخ داده است';
                        $message = !empty($result->error) ? implode("<br>",$result->error) : $message;

                        $this->log_call($payment, [__($message, 'mycred')]);

                        wp_die($message);
                        exit;
                    }

                } else {

                    $message = 'تابع cURL در سرور فعال نمی باشد';

                    $this->log_call($payment, [__($message, 'mycred')]);

                    wp_die($message);
                    exit;
                }
            }

            public function process()
            {

                $fault = false;

                if (!empty($_GET['status']) && $_GET['status'] == "success") {

                    $pending_post_id = sanitize_text_field($_REQUEST['p_id']);
                    $org_pending_payment = $pending_payment = $this->get_pending_payment($pending_post_id);

                    if ( !empty($_GET['p_id'])) {

                        if (is_object($pending_payment)) {

                            $pending_payment = (array)$pending_payment;
                        }
                        if ($pending_payment !== false) {

                            $status = sanitize_text_field($_GET['status']);
                            $token = sanitize_text_field($_GET['token']);
                            $message = sanitize_text_field($_GET['message']);
                            $r = mycred_get_post_meta( $pending_post_id , "cost");
                            $cost = $r[0];
                            $amount = ($this->prefs['currency'] == 'ریال') ? $cost : ($cost * 10);
                            $amount = intval(str_replace(',', '', $amount));
                            if (isset($status) && $status == "success") {

                                $api_key = $this->prefs['shepacom_api'];

                                $params = [

                                    'api'   => $api_key,
                                    'token' => $token,
                                    'amount' => $amount
                                ];
                                print_r($params);
                                $url = "https://merchant.shepa.com/api";
                                if($api_key == "sandbox") {
                                    $url = "https://sandbox.shepa.com/api";
                                }
                                $result = $this->common($url.'/v1/verify', $params);


                                if (!empty($result->success)) {

                                    $card_number = isset($result->result->card_pan) ? sanitize_text_field($result->result->card_pan) : 'Null';

                                    $cost = (str_replace(',', '', $pending_payment['cost']));
                                    $cost = (int)$cost;

                                    $amount = ($this->prefs['currency'] == 'ریال') ? $cost : ($cost * 10);

                                    if ($amount == $result->result->amount) {

                                        if ($this->complete_payment($org_pending_payment, $token)) {

                                            $message = 'تراکنش شماره ' . $result->result->transaction_id . ' با موفقیت انجام شد. شماره کارت پرداخت کننده ' . $card_number;

                                            $this->log_call($pending_post_id, [__($message, 'mycred')]);
                                            //$this->trash_pending_payment($pending_post_id);

                                            wp_redirect($this->get_thankyou());
                                            exit;

                                        } else {

                                            $fault = true;
                                            $message = 'در حین تراکنش خطای نامشخصی رخ داده است';

                                            $this->log_call($pending_post_id, [__($message, 'mycred')]);
                                        }

                                    } else {

                                        $fault = true;
                                        $message = 'رقم تراكنش با رقم پرداخت شده مطابقت ندارد';

                                        $this->log_call($pending_post_id, [__($message, 'mycred')]);
                                    }

                                } else {

                                    $fault = true;
                                    $message = 'در ارتباط با وب سرویس shepa.com و بررسی تراکنش خطایی رخ داده است';
                                    $message = isset($result->errorMessage) ? $result->errorMessage : $message;

                                    $this->log_call($pending_post_id, [__($message, 'mycred')]);
                                }

                            } else {

                                $fault = true;

                                if ($message) {

                                    $this->log_call($pending_post_id, [__($message, 'mycred')]);

                                } else {

                                    $message = 'تراكنش با خطا مواجه شد و یا توسط پرداخت کننده کنسل شده است';

                                    $this->log_call($pending_post_id, [__($message, 'mycred')]);
                                }
                            }

                        } else {

                            $fault = true;
                            $message = 'در حین تراکنش خطای نامشخصی رخ داده است';

                            $this->log_call($pending_post_id, [__($message, 'mycred')]);
                        }

                    } else {

                        $fault = true;
                        $message = 'اطلاعات ارسال شده مربوط به تایید تراکنش ناقص و یا غیر معتبر است';
                        die($message);
                        $this->log_call($pending_post_id, [__($message, 'mycred')]);
                    }

                } else {

                    $fault = true;

                    wp_redirect($this->get_cancelled(''));
                    exit;
                }

                if ($fault) {

                    wp_redirect($this->get_cancelled(''));
                    exit;
                }
            }

            public function returning()
            {
                if (isset($_REQUEST['payment_id']) && isset($_REQUEST['mycred_call']) && $_REQUEST['mycred_call'] == 'shepacom') {

                    // Returning Actions
                }
            }

            private static function common($url, $params)
            {
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

                $response = curl_exec($ch);
                $error = curl_errno($ch);

                curl_close($ch);

                $output = $error ? false : json_decode($response);

                return $output;
            }
        }
    }

}
