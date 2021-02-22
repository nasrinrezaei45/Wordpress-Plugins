<?php
function PDP_load_tpl($template, $params = array(), $type = 'admin')
{
    $template = str_replace('.', '/', $template);
    extract($params);
    $base_path = $type == 'admin' ? PDP_TP_ADMIN : PDP_TP_FRONTEND;
    include $base_path . $template . '.php';
}

function PDP_get_currentUrl()
{
    return $_SERVER['REQUEST_URI'];
}

function pdp_generate_res_num()
{
    return (int)microtime(true);
}

function pdp_persian_number($input)
{
    $persian_numbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $en_numbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($en_numbers, $persian_numbers, $input);
}

function pdp_persian_date($en_date)
{
    if (!function_exists('is_plugin_active')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    if (is_plugin_active('wp-parsidate/wp-parsidate.php') === false) {
        $persian_date = parsidate('Y-m-d', $en_date, 'per');
    } elseif (is_plugin_active('wp-jalali/wp-jalali.php') === false) {
        list($en_date, $en_time) = explode(' ', $en_date);
        list($y, $m, $d) = explode('-', $en_date);
        $persian_date = gregorian_to_jalali($y, $m, $d);
    } elseif (is_plugin_active('wp-parsidate/wp-parsidate.php') === false && is_plugin_active('wp-jalali/wp-jalali.php') === false) {
        $persian_date = parsidate('Y-m-d', $en_date, 'per');
    }
    $result = implode('-', $persian_date) . ' ' . $en_time;
    return pdp_persian_number($result);
}


function pdp_show_form_page()
{
    global $wpdb, $table_prefix, $shepacom;
    $users = wp_get_current_user();
    $hasEr = false;
    $errormessage = [];
    $pdp_option = get_option('pdp_options');
    if (isset($_POST['submit_payment'])) {
        if (
            !isset($_POST['pdp_payment_users_nonce'])
            || !wp_verify_nonce($_POST['pdp_payment_users_nonce'], 'pdp_payment_users')
        ) {
            wp_die('درخواست شما معتبر نمی باشد.');
        }
        $name_user = $_POST['pdp_fullname_payment'];
        $price = $_POST['pdp_price_payment'];
        $email_user = $_POST['pdp_email_payment'];
        $mobile_user = $_POST['pdp_mobile_payment'];
        $description_user = $_POST['pdp_description_payment'];
        if (empty($name_user)) {
            $hasEr = true;
            array_push($errormessage, 'نام پرداخت کننده نمی تواند خالی رها شود');
        }
        if (empty($price)) {
            $hasEr = true;
            array_push($errormessage, 'مبلغ نمی تواند خالی رها شود');
        }
        if (empty($email_user)) {
            $hasEr = true;
            array_push($errormessage, 'ایمیل نمی تواند خالی رها شود');
        }
        if (empty($mobile_user)) {
            $hasEr = true;
            array_push($errormessage, 'موبایل نمی تواند خالی رها شود');
        }
        if (empty($description_user)) {
            $hasEr = true;
            array_push($errormessage, 'توضیحات نمی تواند خالی رها شود');
        }
        if (!$hasEr) {
            $payments_insert_result = $wpdb->insert($table_prefix . 'pdp_payments', [
                'payment_name_user' => $name_user,
                'payment_amount' => $price,
                'payment_user_email' => $email_user,
                'payment_user_mobile' => $mobile_user,
                'payment_user_description' => $description_user,
                'payment_res_num' => pdp_generate_res_num(),
                'payment_created_at' => date('Y-m-d H:i:s'),
                'payment_status' => 0
            ]);

            if ($payments_insert_result) {
                $redirect = home_url('/pdp/verify');
                $amount = $price * 10;
                $payment_id = $wpdb->insert_id;
                $payment_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_prefix}pdp_payments WHERE payment_id=%s", $payment_id));
                $shepacom->api = $pdp_option['api_pay'];
                $shepacom->amount = $amount;
                $shepacom->redirect = $redirect;
                $shepacom->mobile = $mobile_user;
                $shepacom->Description = $description_user;
                $shepacom->PaymentID = $payment_id;
                $shepacom->factorNumber = $payment_item->payment_res_num;
                $result = json_decode($shepacom->Request());
                if ($result->success) {
                    $go = $result->result->url;
                    header("Location: $go");
                } else {
                    echo $result->errorMessage;
                }
            }
        }
    }
    PDP_load_tpl('form', compact('pdp_option', 'hasEr', 'errormessage'), 'frontend');
}


function pdp_verify_payment()
{
    global $wpdb, $table_prefix, $shepacom;
    $success = false;
    $error = false;
    $pdp_option = get_option('pdp_options');
    $payments = $wpdb->get_row($wpdb->prepare(
        "
			SELECT *
			FROM {$table_prefix}pdp_payments
			WHERE payment_res_num=%s
	", $_GET["factorNumber"]));
    $shepacom->api = $pdp_option['api_pay'];
    $shepacom->amount = $payments->payment_amount*10;
    $result = json_decode($shepacom->Verify());
    if ($payments->payment_status==0)
    {
        if ($result && $result->success == 1) {
            $wpdb->update($table_prefix . 'pdp_payments', [
                'payment_ref_num' => $result->result->refid,
                'payment_paid_at' => date('Y-m-d H:i:s'),
                'payment_status' => 1,
            ], [
                'payment_res_num' => $_GET["factorNumber"]
            ], ['%s', '%s', '%d'], ['%d']);
            $payments = $wpdb->get_row(" SELECT * FROM {$table_prefix}pdp_payments WHERE payment_res_num={$result->factorNumber}");
            $date_paid = $payments->payment_paid_at;
            $ref_num = $result->result->refid;
            $price = $result->result->amount;

            do_action('pdp_payment_paid', $payments->payment_id);
            $success = true;
            setcookie("paid", true);
        } else {
            $error = true;
        }
    }else{
        wp_redirect(home_url());
        exit;
    }
    PDP_load_tpl('verify', compact('pdp_option', 'error', 'success', 'date_paid', 'ref_num', 'price'), 'frontend');

}

add_action('pdp_payment_paid', 'pdp_notification_user');

function pdp_notification_user($payment_id)
{
    global $wpdb, $table_prefix, $shepacom;
    $pdp_option = get_option('pdp_options');
    $sms_panel = $pdp_option['panel_payamak']['panel_name'];
    $payment_item = $wpdb->get_row($wpdb->prepare(
        "
			SELECT *
			FROM {$table_prefix}pdp_payments
			WHERE payment_id=%s
	", $payment_id));
    switch ($sms_panel) {
        case 'activepayamak':
            if ($pdp_option['panel_payamak']['active_number_users']) {
				ini_set("soap.wsdl_cache_enabled", "0");
                $client = new SoapClient("http://ippanel.com/class/sms/wsdlservice/server.php?wsdl");
                $sms_message = $pdp_option['panel_payamak']['text_sms_user'];
                $sms_message = str_replace('%name_user%', $payment_item->payment_name_user, $sms_message);
                $sms_message = str_replace('%price_user%', $payment_item->payment_amount, $sms_message);
                $sms_message = str_replace('%tracking_id%', $payment_item->payment_ref_num, $sms_message);
                $user = $pdp_option['panel_payamak']['username_sms'];
                $pass = $pdp_option['panel_payamak']['pass_sms'];
                $fromNum = $pdp_option['panel_payamak']['number_sms'];
                $toNum = array($payment_item->payment_user_mobile);
                $messageContent = $sms_message;
                $op = "send";
                $time = '';
                $client->SendSMS($fromNum, $toNum, $messageContent, $user, $pass, $time, $op);
            }
            if ($pdp_option['panel_payamak']['active_number_admin']) {
				ini_set("soap.wsdl_cache_enabled", "0");
                $client = new SoapClient("http://ippanel.com/class/sms/wsdlservice/server.php?wsdl");
                $sms_message = $pdp_option['panel_payamak']['text_sms_admin'];
                $sms_message = str_replace('%name_user%', $payment_item->payment_name_user, $sms_message);
                $sms_message = str_replace('%price_user%', $payment_item->payment_amount, $sms_message);
                $sms_message = str_replace('%tracking_id%', $payment_item->payment_ref_num, $sms_message);
                $user = $pdp_option['panel_payamak']['username_sms'];
                $pass = $pdp_option['panel_payamak']['pass_sms'];
                $fromNum = $pdp_option['panel_payamak']['number_sms'];
                $admin_number = $pdp_option['panel_payamak']['mobile_number_admin_sms'];
                $toNum = array($admin_number);
                $messageContent = $sms_message;
                $op = "send";
                $time = '';
                $client->SendSMS($fromNum, $toNum, $messageContent, $user, $pass, $time, $op);
            }
            break;
        case 'ippanel':
            if ($pdp_option['panel_payamak']['active_number_users']) {
				ini_set("soap.wsdl_cache_enabled", "0");
                $client = new SoapClient("http://ippanel.com/class/sms/wsdlservice/server.php?wsdl");
                $sms_message = $pdp_option['panel_payamak']['text_sms_user'];
                $sms_message = str_replace('%name_user%', $payment_item->payment_name_user, $sms_message);
                $sms_message = str_replace('%price_user%', $payment_item->payment_amount, $sms_message);
                $sms_message = str_replace('%tracking_id%', $payment_item->payment_ref_num, $sms_message);
                $user = $pdp_option['panel_payamak']['username_sms'];
                $pass = $pdp_option['panel_payamak']['pass_sms'];
                $fromNum = $pdp_option['panel_payamak']['number_sms'];
                $toNum = array($payment_item->payment_user_mobile);
                $messageContent = $sms_message;
                $op = "send";
                $time = '';
                $client->SendSMS($fromNum, $toNum, $messageContent, $user, $pass, $time, $op);
            }
            if ($pdp_option['panel_payamak']['active_number_admin']) {
				ini_set("soap.wsdl_cache_enabled", "0");
                $client = new SoapClient("http://ippanel.com/class/sms/wsdlservice/server.php?wsdl");
                $sms_message = $pdp_option['panel_payamak']['text_sms_admin'];
                $sms_message = str_replace('%name_user%', $payment_item->payment_name_user, $sms_message);
                $sms_message = str_replace('%price_user%', $payment_item->payment_amount, $sms_message);
                $sms_message = str_replace('%tracking_id%', $payment_item->payment_ref_num, $sms_message);
                $user = $pdp_option['panel_payamak']['username_sms'];
                $pass = $pdp_option['panel_payamak']['pass_sms'];
                $fromNum = $pdp_option['panel_payamak']['number_sms'];
                $admin_number = $pdp_option['panel_payamak']['mobile_number_admin_sms'];
                $toNum = array($admin_number);
                $messageContent = $sms_message;
                $op = "send";
                $time = '';
                $client->SendSMS($fromNum, $toNum, $messageContent, $user, $pass, $time, $op);
            }
            break;
        case 'melipayamak':
            if ($pdp_option['panel_payamak']['active_number_users']) {
                $sms_message = $pdp_option['panel_payamak']['text_sms_user'];
                $sms_message = str_replace('%name_user%', $payment_item->payment_name_user, $sms_message);
                $sms_message = str_replace('%price_user%', $payment_item->payment_amount, $sms_message);
                $sms_message = str_replace('%tracking_id%', $payment_item->payment_ref_num, $sms_message);
                $user = $pdp_option['panel_payamak']['username_sms'];
                $pass = $pdp_option['panel_payamak']['pass_sms'];
                $fromNum = $pdp_option['panel_payamak']['number_sms'];
                $toNum = array($payment_item->payment_user_mobile);
                $messageContent = $sms_message;
                try {
                    $client = new SoapClient("http://api.payamak-panel.com/post/Send.asmx?wsdl");
                    $parameters =
                        [
                            'username' => $user,
                            'password' => $pass,
                            'from' => $fromNum,
                            'text' => $messageContent,
                            'isflash' => false,
                            'to' => $toNum
                        ];

                    return $client->SendSimpleSMS2($parameters)->SendSimpleSMS2Result;
                } catch (Exception $e) {
                    return false;
                }
            }
            if ($pdp_option['panel_payamak']['active_number_admin']) {
                $sms_message = $pdp_option['panel_payamak']['text_sms_admin'];
                $sms_message = str_replace('%name_user%', $payment_item->payment_name_user, $sms_message);
                $sms_message = str_replace('%price_user%', $payment_item->payment_amount, $sms_message);
                $sms_message = str_replace('%tracking_id%', $payment_item->payment_ref_num, $sms_message);
                $user = $pdp_option['panel_payamak']['username_sms'];
                $pass = $pdp_option['panel_payamak']['pass_sms'];
                $fromNum = $pdp_option['panel_payamak']['number_sms'];
                $admin_number = $pdp_option['panel_payamak']['mobile_number_admin_sms'];
                $toNum = array($admin_number);
                $messageContent = $sms_message;
                try {
                    $client = new SoapClient("http://api.payamak-panel.com/post/Send.asmx?wsdl");
                    $parameters =
                        [
                            'username' => $user,
                            'password' => $pass,
                            'from' => $fromNum,
                            'text' => $messageContent,
                            'isflash' => false,
                            'to' => $toNum
                        ];

                    return $client->SendSimpleSMS2($parameters)->SendSimpleSMS2Result;
                } catch (Exception $e) {
                    return false;
                }
            }
            break;
        case 'kavenegar':
            header('Content-Type: text/html; charset=utf-8');
            ini_set("soap.wsdl_cache_enabled", "0");
            if ($pdp_option['panel_payamak']['active_number_users']) {
                $sms_message = $pdp_option['panel_payamak']['text_sms_user'];
                $sms_message = str_replace('%name_user%', $payment_item->payment_name_user, $sms_message);
                $sms_message = str_replace('%price_user%', $payment_item->payment_amount, $sms_message);
                $sms_message = str_replace('%tracking_id%', $payment_item->payment_ref_num, $sms_message);
                $user = $pdp_option['panel_payamak']['username_sms'];
                $fromNum = $pdp_option['panel_payamak']['number_sms'];
                $toNum = array($payment_item->payment_user_mobile);
                $messageContent = $sms_message;
                $client = new SoapClient('http://api.kavenegar.com/soap/v1.asmx?WSDL', array(
                    'trace' => 1
                ));
                $apikey = $user;
                $sender = $fromNum;
                $message = $messageContent;
                $params = array(
                    'apikey' => $apikey,
                    'sender' => $sender,
                    'message' => $message,
                    'receptor' => $toNum,
                    'unixdate' => 0,
                    'msgmode' => 1,
                    'status' => "",
                    'statusmessage' => ""
                );
                $client->SendSimpleByApikey($params);
            }
            if ($pdp_option['panel_payamak']['active_number_admin']) {
                $sms_message = $pdp_option['panel_payamak']['text_sms_admin'];
                $sms_message = str_replace('%name_user%', $payment_item->payment_name_user, $sms_message);
                $sms_message = str_replace('%price_user%', $payment_item->payment_amount, $sms_message);
                $sms_message = str_replace('%tracking_id%', $payment_item->payment_ref_num, $sms_message);
                $user = $pdp_option['panel_payamak']['username_sms'];
                $fromNum = $pdp_option['panel_payamak']['number_sms'];
                $admin_number = $pdp_option['panel_payamak']['mobile_number_admin_sms'];
                $toNum = array($admin_number);
                $messageContent = $sms_message;
                $client = new SoapClient('http://api.kavenegar.com/soap/v1.asmx?WSDL', array(
                    'trace' => 1
                ));
                $apikey = $user;
                $sender = $fromNum;
                $message = $messageContent;
                $params = array(
                    'apikey' => $apikey,
                    'sender' => $sender,
                    'message' => $message,
                    'receptor' => $toNum,
                    'unixdate' => 0,
                    'msgmode' => 1,
                    'status' => "",
                    'statusmessage' => ""
                );
                $client->SendSimpleByApikey($params);
            }
            break;
        case 'payamresan':
            header('Content-Type: text/html; charset=utf-8');
            ini_set("soap.wsdl_cache_enabled", "0");
            if ($pdp_option['panel_payamak']['active_number_users']) {
                $sms_message = $pdp_option['panel_payamak']['text_sms_user'];
                $sms_message = str_replace('%name_user%', $payment_item->payment_name_user, $sms_message);
                $sms_message = str_replace('%price_user%', $payment_item->payment_amount, $sms_message);
                $sms_message = str_replace('%tracking_id%', $payment_item->payment_ref_num, $sms_message);
                $user = $pdp_option['panel_payamak']['username_sms'];
                $fromNum = $pdp_option['panel_payamak']['number_sms'];
                $pass = $pdp_option['panel_payamak']['pass_sms'];
                $toNum = array($payment_item->payment_user_mobile);
                $messageContent = $sms_message;
                $client = new \SoapClient('http://sms-webservice.ir/v1/v1.asmx?WSDL');
                $parameters['Username'] = $user;
                $parameters['PassWord'] = $pass;
                $parameters['SenderNumber'] = $fromNum;
                $parameters['RecipientNumbers'] = $toNum;
                $parameters['MessageBodie'] = $messageContent;
                $parameters['Type'] = 1;
                $parameters['AllowedDelay'] = 0;
                $client->SendMessage($parameters);
            }
            if ($pdp_option['panel_payamak']['active_number_admin']) {
                $sms_message = $pdp_option['panel_payamak']['text_sms_admin'];
                $sms_message = str_replace('%name_user%', $payment_item->payment_name_user, $sms_message);
                $sms_message = str_replace('%price_user%', $payment_item->payment_amount, $sms_message);
                $sms_message = str_replace('%tracking_id%', $payment_item->payment_ref_num, $sms_message);
                $user = $pdp_option['panel_payamak']['username_sms'];
                $fromNum = $pdp_option['panel_payamak']['number_sms'];
                $pass = $pdp_option['panel_payamak']['pass_sms'];
                $admin_number = $pdp_option['panel_payamak']['mobile_number_admin_sms'];
                $toNum = array($admin_number);
                $messageContent = $sms_message;
                $client = new \SoapClient('http://sms-webservice.ir/v1/v1.asmx?WSDL');
                $parameters['Username'] = $user;
                $parameters['PassWord'] = $pass;
                $parameters['SenderNumber'] = $fromNum;
                $parameters['RecipientNumbers'] = $toNum;
                $parameters['MessageBodie'] = $messageContent;
                $parameters['Type'] = 1;
                $parameters['AllowedDelay'] = 0;
                $client->SendMessage($parameters);
            }
            break;
        case 'smsir':
            date_default_timezone_set("Asia/Tehran");
            include_once("SendMessage.php");
            if ($pdp_option['panel_payamak']['active_number_users']) {
                $sms_message = $pdp_option['panel_payamak']['text_sms_user'];
                $sms_message = str_replace('%name_user%', $payment_item->payment_name_user, $sms_message);
                $sms_message = str_replace('%price_user%', $payment_item->payment_amount, $sms_message);
                $sms_message = str_replace('%tracking_id%', $payment_item->payment_ref_num, $sms_message);
                $user = $pdp_option['panel_payamak']['username_sms'];
                $fromNum = $pdp_option['panel_payamak']['number_sms'];
                $pass = $pdp_option['panel_payamak']['pass_sms'];
                $toNum = array($payment_item->payment_user_mobile);
                $messageContent = $sms_message;
                $APIKey = $user;
                $SecretKey = $pass;
                $LineNumber = $fromNum;
                $MobileNumbers = $toNum;
                $Messages = array($messageContent);
                @$SendDateTime = date("Y-m-d") . "T" . date("H:i:s");
                $SmsIR_SendMessage = new SmsIR_SendMessage($APIKey, $SecretKey, $LineNumber);
                $SendMessage = $SmsIR_SendMessage->SendMessage($MobileNumbers, $Messages, $SendDateTime);
            }
            if ($pdp_option['panel_payamak']['active_number_admin']) {
                $sms_message = $pdp_option['panel_payamak']['text_sms_admin'];
                $sms_message = str_replace('%name_user%', $payment_item->payment_name_user, $sms_message);
                $sms_message = str_replace('%price_user%', $payment_item->payment_amount, $sms_message);
                $sms_message = str_replace('%tracking_id%', $payment_item->payment_ref_num, $sms_message);
                $user = $pdp_option['panel_payamak']['username_sms'];
                $fromNum = $pdp_option['panel_payamak']['number_sms'];
                $pass = $pdp_option['panel_payamak']['pass_sms'];
                $admin_number = $pdp_option['panel_payamak']['mobile_number_admin_sms'];
                $toNum = array($admin_number);
                $messageContent = $sms_message;
                $APIKey = $user;
                $SecretKey = $pass;
                $LineNumber = $fromNum;
                $MobileNumbers = $toNum;
                $Messages = array($messageContent);
                @$SendDateTime = date("Y-m-d") . "T" . date("H:i:s");
                $SmsIR_SendMessage = new SmsIR_SendMessage($APIKey, $SecretKey, $LineNumber);
                $SendMessage = $SmsIR_SendMessage->SendMessage($MobileNumbers, $Messages, $SendDateTime);
            }
            break;
    }
}