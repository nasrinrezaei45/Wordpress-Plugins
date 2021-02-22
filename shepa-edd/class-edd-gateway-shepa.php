<?php

if (!function_exists('edd_rial')) {
    function edd_rial($formatted, $currency, $price)
    {
        return $price . ' ریال';
    }
}
add_filter('edd_rial_currency_filter_after', 'edd_rial', 10, 3);
@session_start();
function edd_common($key, $params, $options)
{
    $url = ($options['shepa_sandbox'] == 'no') ? 'https://merchant.shepa.com/api/v1/' . $key : 'https://sandbox.shepa.com/api/v1/'. $key;
    $params["api"] = ($options == 'no') ? $options["shepa_api"] : 'sandbox';

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

function edd_go_to_gateway($url)
{

    wp_redirect($url);

    exit();
}

function shepa_edd_rial($formatted, $currency, $price)
{
    return $price . ' ریال';
}

function add_shepa_gateway($gateways)
{
    $gateways['shepa'] = array(
        'admin_label' => 'درگاه پرداخت و کیف پول الکترونیک shepa.com',
        'checkout_label' => 'درگاه پرداخت و کیف پول الکترونیک shepa.com'
    );

    return $gateways;
}

add_filter('edd_payment_gateways', 'add_shepa_gateway');

function shepa_cc_form()
{
    return;
}

add_action('edd_shepa_cc_form', 'shepa_cc_form');

function shepa_process($purchase_data)
{
    global $edd_options;
    $payment_data = array(
        'price' => $purchase_data['price'],
        'date' => $purchase_data['date'],
        'user_email' => $purchase_data['post_data']['edd_email'],
        'purchase_key' => $purchase_data['purchase_key'],
        'currency' => $edd_options['currency'],
        'downloads' => $purchase_data['downloads'],
        'cart_details' => $purchase_data['cart_details'],
        'user_info' => $purchase_data['user_info'],
        'status' => 'pending'
    );
    $payment = edd_insert_payment($payment_data);
    if ($payment) {
        delete_transient('edd_shepa_record');
        set_transient('edd_shepa_record', $payment);
        $_SESSION['edd_shepa_record'] = $payment;
        if (extension_loaded('curl')) {
            $api_key = $edd_options['shepa_api'];
            if ($edd_options['currency'] == 'IRT' || $edd_options['currency'] == 'toman' || $edd_options['currency'] == 'irt') {
                $amount = intval($payment_data['price']) * 10;
            } else {
                $amount = intval($payment_data['price']);
            }
            $callback = add_query_arg('verify', 'shepa', get_permalink($edd_options['success_page']));
            $callback .= '&factorNumber=' . $payment;
            $description = 'پرداخت صورت حساب ' . $purchase_data['purchase_key'];
            $params = array(
                'api' => $api_key,
                'amount' => $amount,
                'callback' => ($callback),
                'description' => $description
            );
            if(!empty($payment_data['mobile'])) $params["mobile"] = $payment_data['mobile'];
            if(!empty($payment_data['user_email'])) $params["email"] = $payment_data['user_email'];
            $result = edd_common('token', $params, $edd_options);
            if (!empty($result->success)) {
                $message = 'شماره تراکنش ' . $result->result->token;
                edd_insert_payment_note($payment, $message);
                edd_go_to_gateway($result->result->url);
                exit;
            } else {
                $message = 'در ارتباط با وب سرویس shepa.com خطایی رخ داده است';
                $message = !empty($result->error) ? implode("<br>", $result->error) : $message;
                edd_insert_payment_note($payment, $message);
                wp_die($message);
                exit;
            }
        } else {
            $message = 'تابع cURL در سرور فعال نمی باشد';
            edd_insert_payment_note($payment, $message);
            wp_die($message);
            exit;
        }

    } else {
        edd_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['edd-gateway']);
    }
}

add_action('edd_gateway_shepa', 'shepa_process');

function shepa_verify()
{
    global $edd_options;

    $payment_id = isset($_SESSION['edd_shepa_record']) ? $_SESSION['edd_shepa_record'] : null;


    if ($payment_id != null && isset($_GET['verify']) && $_GET['verify'] == 'shepa'
        && isset($_GET['status']) && isset($_GET['token'])) {

        $status = sanitize_text_field($_GET['status']);
        $token = sanitize_text_field($_GET['token']);

        if (isset($status) && $status == "success" && !empty($_SESSION['edd_shepa_record'])) {
            $api_key = $edd_options['shepa_api'];

            if ($edd_options['currency'] == 'IRT' || $edd_options['currency'] == 'toman' || $edd_options['currency'] == 'irt') {
                $amount = intval(edd_get_payment_amount($payment_id)) * 10;
            } else {
                $amount = intval(edd_get_payment_amount($payment_id));
            }
            $params = array(
                'api' => $api_key,
                'token' => $token,
                'amount' => $amount,
            );

            $result = edd_common('verify', $params, $edd_options);
            if (!empty($result->success)) {

                $card_number = isset($result->result->card_pan) ? $result->result->card_pan : null;


                if ($amount == $result->result->amount) {
                    $message = 'تراکنش شماره ' . $result->result->refid . ' با موفقیت انجام شد. شماره کارت پرداخت کننده ' . $card_number;
                    edd_insert_payment_note($payment_id, $message);
                    edd_update_payment_status($payment_id, 'publish');
                    edd_empty_cart();
                    edd_send_to_success_page();
                } else {
                    $message = implode("<br>", $result->error);
                    edd_insert_payment_note($payment_id, $message);
                    edd_update_payment_status($payment_id, 'failed');
                    edd_empty_cart();
                    wp_redirect(get_permalink($edd_options['failure_page']));
                    exit;
                }

            } else {
                $message = 'در ارتباط با وب سرویس shepa.com و بررسی تراکنش خطایی رخ داده است';
                $message = isset($result->msg) ? $result->msg : $message;
                edd_insert_payment_note($payment_id, $message);
                edd_update_payment_status($payment_id, 'failed');
                edd_empty_cart();
                wp_redirect(get_permalink($edd_options['failure_page']));
                exit;
            }
        } else {
            if (isset($message)) {
                edd_insert_payment_note($payment_id, $message);
                edd_update_payment_status($payment_id, 'failed');
            } else {
                $message = 'تراكنش با خطا مواجه شد و یا توسط پرداخت کننده کنسل شده است';
                edd_insert_payment_note($payment_id, $message);
                edd_update_payment_status($payment_id, 'failed');
            }
            edd_empty_cart();
            wp_redirect(get_permalink($edd_options['failure_page']));
            exit;
        }
    }
}

add_action('init', 'shepa_verify');

function shepa_settings($settings)
{
    $shepa_options = array(
        array(

            'id' => 'shepa_settings',
            'type' => 'header',
            'name' => 'تنظیمات درگاه پرداخت shepa.com'
        ),
        array(
            'id' => 'shepa_api',
            'type' => 'text',
            'name' => 'کلید API',
            'desc' => null
        ),
        array(
            'id' => 'shepa_sandbox',
            'type' => 'checkbox',
            'name' => 'فعال سازی حالت تست درگاه',
            'desc' => 'برای استفاده از درگاه آزمایشی شپا باید چک باکس را تیک بزنید.'
        )
    );

    return array_merge($settings, $shepa_options);
}

add_filter('edd_settings_gateways', 'shepa_settings');
