<?php
/**
 * Shepa payment gateway class.
 *
 * @author   MidyaSoft
 * @package  LearnPress/Shepa/Classes
 * @version  1.0.0
 */

// Prevent loading this file directly
defined('ABSPATH') || exit;

if (!class_exists('LP_Gateway_Shepa')) {
    /**
     * Class LP_Gateway_Shepa
     */
    class LP_Gateway_Shepa extends LP_Gateway_Abstract
    {

        /**
         * @var array
         */
        private $form_data = array();

        /**
         * @var string
         */
        private $sendUrl = 'https://merchant.shepa.com/api/v1/token';
        /**
         * @var string
         */
        private $sendUrlSandbox = 'https://sandbox.shepa.com/api/v1/token';
        /**
         * @var string
         */
        private $verifyUrl = 'https://merchant.shepa.com/api/v1/verify';
        /**
         * @var string
         */
        private $verifyUrlSandbox = 'https://sandbox.shepa.com/api/v1/verify';
        /**
         * @var string
         */
        private $api = null;
        /**  /**
         * @var string
         */
        private $error = null;
        /**
         * @var string
         */
        private $sandbox = "no";

        /**
         * @var array|null
         */
        protected $settings = null;

        /**
         * @var null
         */
        protected $order = null;

        /**
         * @var null
         */
        protected $posted = null;

        /**
         * Request TransId
         *
         * @var string
         */
        protected $transId = null;

        /**
         * LP_Gateway_Shepa constructor.
         */
        public function __construct()
        {
            $this->id = 'shepa';

            $this->method_title = __('Shepa', 'learnpress-shepa');;
            $this->method_description = __('Make a payment with Shepa.com.', 'learnpress-shepa');
            $this->icon = '';

            // Get settings
            $this->title = LP()->settings->get("{$this->id}.title", $this->method_title);
            $this->description = LP()->settings->get("{$this->id}.description", $this->method_description);

            $settings = LP()->settings;

            // Add default values for fresh installs
            if ($settings->get("{$this->id}.enable")) {
                $this->settings = array();
                $this->settings['api'] = $settings->get("{$this->id}.api");
                $this->settings['sandbox'] = $settings->get("{$this->id}.sandbox");
            }

            $this->api = $this->settings['api'];
            if ($settings->get("{$this->id}.sandbox")) {
                $this->api = "sandbox";
                $this->sandbox = "yes";
            }


            if (did_action('learn_press/shepa-add-on/loaded')) {
                return;
            }

            // check payment gateway enable
            add_filter('learn-press/payment-gateway/' . $this->id . '/available', array(
                $this,
                'shepa_available'
            ), 10, 2);

            do_action('learn_press/shepa-add-on/loaded');

            parent::__construct();

            // web hook
            if (did_action('init')) {
                $this->register_web_hook();
            } else {
                add_action('init', array($this, 'register_web_hook'));
            }
            add_action('learn_press_web_hooks_processed', array($this, 'web_hook_process_shepa'));

            add_action("learn-press/before-checkout-order-review", array($this, 'error_message'));
        }

        /**
         * Register web hook.
         *
         * @return array
         */
        public function register_web_hook()
        {
            learn_press_register_web_hook('shepa', 'learn_press_shepa');
        }

        /**
         * Admin payment settings.
         *
         * @return array
         */
        public function get_settings()
        {


            return apply_filters('learn-press/gateway-payment/shepa/settings',
                array(
                    array(
                        'title' => __('Enable', 'learnpress-shepa'),
                        'id' => '[enable]',
                        'default' => 'no',
                        'type' => 'yes-no'
                    ),
                    array(
                        'title' => __('Sandbox', 'learnpress-shepa'),
                        'id' => '[sandbox]',
                        'default' => 'no',
                        'type' => 'yes-no'
                    ),
                    array(
                        'type' => 'text',
                        'title' => __('Title', 'learnpress-shepa'),
                        'default' => __('Shepa.com', 'learnpress-shepa'),
                        'id' => '[title]',
                        'class' => 'regular-text',
                        'visibility' => array(
                            'state' => 'show',
                            'conditional' => array(
                                array(
                                    'field' => '[enable]',
                                    'compare' => '=',
                                    'value' => 'yes'
                                )
                            )
                        )
                    ),
                    array(
                        'type' => 'textarea',
                        'title' => __('Description', 'learnpress-shepa'),
                        'default' => __('Pay with Shepa.com', 'learnpress-shepa'),
                        'id' => '[description]',
                        'editor' => array(
                            'textarea_rows' => 5
                        ),
                        'css' => 'height: 100px;',
                        'visibility' => array(
                            'state' => 'show',
                            'conditional' => array(
                                array(
                                    'field' => '[enable]',
                                    'compare' => '=',
                                    'value' => 'yes'
                                )
                            )
                        )
                    ),
                    array(
                        'title' => __('API', 'learnpress-shepa'),
                        'id' => '[api]',
                        'type' => 'text',
                        'visibility' => array(
                            'state' => 'show',
                            'conditional' => array(
                                array(
                                    'field' => '[enable]',
                                    'compare' => '=',
                                    'value' => 'yes'
                                )
                            )
                        )
                    )
                )
            );
        }

        /**
         * Payment form.
         */
        public function get_payment_form()
        {
            /*ob_start();
            $template = learn_press_locate_template( 'form.php', learn_press_template_path() . '/addons/shepa-payment/', LP_ADDON_PAYIR_PAYMENT_TEMPLATE );
            include $template;*/

            return "";
        }

        /**
         * Error message.
         *
         * @return array
         */
        public function error_message()
        {
//            if (!session_id())
//                session_start();
            if (isset($_SESSION['shepa_error']) && intval($_SESSION['shepa_error']) === 1) {
                $_SESSION['shepa_error'] = 0;
                $template = learn_press_locate_template('payment-error.php', learn_press_template_path() . '/addons/shepa-payment/', LP_ADDON_PAYIR_PAYMENT_TEMPLATE);
                include $template;
            }
        }

        /**
         * @return mixed
         */
        public function get_icon()
        {
            if (empty($this->icon)) {
                //$this->icon = 'http://shepa.com/wp-content/uploads/2019/06/Shepa-WhiteAsset-6@0.5x-e1560956920992.png';
            }

            return parent::get_icon();
        }

        /**
         * Check gateway available.
         *
         * @return bool
         */
        public function shepa_available()
        {

            if (LP()->settings->get("{$this->id}.enable") != 'yes') {
                return false;
            }

            return true;
        }

        /**
         * Get form data.
         *
         * @return array
         */
        public function get_form_data()
        {
            if ($this->order) {
                $user = learn_press_get_current_user();
                $currency_code = learn_press_get_currency();
                if ($currency_code == 'IRR') {
                    $amount = $this->order->order_total;
                } else {
                    $amount = $this->order->order_total * 10;
                }

                $this->form_data = array(
                    'amount' => $amount,
                    'currency' => strtolower(learn_press_get_currency()),
                    'token' => $this->token,
                    'description' => sprintf(__("Charge for %s", "learnpress-shepa"), $user->get_data('email')),
                    'email' => $user->get_data('email'),
                    'customer' => array(
                        'name' => $user->get_data('display_name'),
                        'billing_email' => $user->get_data('email'),
                    ),
                    'errors' => isset($this->posted['form_errors']) ? $this->posted['form_errors'] : ''
                );
            }

            return $this->form_data;
        }

        /**
         * Validate form fields.
         *
         * @return bool
         * @throws Exception
         * @throws string
         */
        public function validate_fields()
        {
            $posted = learn_press_get_request('learn-press-shepa');
            $email = !empty($posted['email']) ? $posted['email'] : "";
            $mobile = !empty($posted['mobile']) ? $posted['mobile'] : "";
            $description = !empty($posted['description']) ? $posted['description'] : "";
            $error_message = array();
            /*if ( !empty( $email ) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message[] = __( 'Invalid email format.', 'learnpress-shepa' );
            }
            if ( !empty( $mobile ) && !preg_match("/^(09)(\d{9})$/", $mobile)) {
                $error_message[] = __( 'Invalid mobile format.', 'learnpress-shepa' );
            }

            if ( $error = sizeof( $error_message ) ) {
                throw new Exception( sprintf( '<div>%s</div>', join( '</div><div>', $error_message ) ), 8000 );
            }*/
            $this->posted = $posted;

            return $error ? false : true;
        }

        /**
         * Shepa.com payment process.
         *
         * @param $order
         *
         * @return array
         * @throws string
         */
        public function process_payment($order)
        {
            $this->order = learn_press_get_order($order);
            $shepa = $this->send();
            if (!empty($shepa)) {
                $json = [
                    'result' => $shepa ? 'success' : 'fail',
                    'redirect' => $shepa ? $this->gatewayUrl : ''
                ];
                return $json;
            }else {
                $response['result'] = 'error';
                $response['messages'] = "<div class=\"learn-press-message error\">". $this->error."</div>";

                learn_press_send_json($response);
                die();
            }

        }

        /**
         * Send.
         *
         * @return bool|object
         */

        public function send()
        {

            $sendUrl = $this->sendUrl;
            if ($this->sandbox == "yes") {
                $sendUrl = $this->sendUrlSandbox;
            }
            if ($this->get_form_data()) {
                $redirect = (get_site_url() . '/?' . learn_press_get_web_hook('shepa') . '=1&order_id=' . $this->order->get_id());
                $params = [
                    "api" => $this->api ,
                    "amount" => $this->form_data['amount'],
                    "callback" => $redirect,
                    "description" => $this->form_data['description'] . $this->order->get_id(),
                    "email" => $this->form_data['email'],
                ];
                if (!empty($mobile)) $params["mobile"] = $mobile;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $sendUrl);
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

                $result = $error ? false : json_decode($response);

                if (!empty($result->success)) {
                    $this->token = $result->result->token;
                    $this->gatewayUrl = $result->result->url;
                    return true;
                } else {
                    $this->error = implode("<br>", $result->error);
                    return false;;
                }

            }
            return false;
        }

        /**
         * Handle a web hook
         *
         */
        public
        function web_hook_process_shepa()
        {
            $this->error =  __('Error connecting to gateway', 'learnpress-shepa');
            $request = $_REQUEST;

            $sendUrl = $this->sendUrl;
            $verifyUrl = $this->verifyUrl;
            if ($this->sandbox == "yes") {
                $sendUrl = $this->sendUrlSandbox;
                $verifyUrl = $this->verifyUrlSandbox;
            }
            if (isset($request['learn_press_shepa']) && intval($request['learn_press_shepa']) === 1) {


                $status = @$_GET['status'];
                if (!empty($status) && $status == "success") {
                    $order = LP_Order::instance($request['order_id']);
                    $currency_code = learn_press_get_currency();
                    if ($currency_code == 'IRR') {

                        $amount = $order->order_total;
                    } else {
                        $amount = $order->order_total * 10;
                    }

                    $token = $_GET['token'];
                    $params = [
                        "api" => $this->api,
                        "token" => $token,
                        "amount" => $amount,
                    ];

                    try {
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $verifyUrl);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Content-Type: application/json',
                        ]);
                        $result = curl_exec($ch);

                        $result = json_decode($result);
                        curl_close($ch);

                    } catch (Exception $ex) {
                        return false;
                    }
                    if (!empty($result->success)) {
                        $this->payment_status_completed($order, $result->result->refid);
                        wp_redirect(esc_url($this->get_return_url($order)));
                        exit();
                    }
                    else {
                        $this->error = implode("<br>", $result->error);
                    }


                }
            }
            learn_press_add_message( $this->error, 'error');
            wp_redirect(esc_url(learn_press_get_page_link('checkout')));
            exit();
        }

        /**
         * Handle a completed payment
         *
         * @param LP_Order
         * @param request
         */
        protected
        function payment_status_completed($order, $refid)
        {

            // order status is already completed
            if ($order->has_status('completed')) {
                exit;
            }

            $this->payment_complete($order, (!empty($refid) ? $refid : ''), __('Payment has been successfully completed', 'learnpress-shepa'));

        }

        /**
         * Handle a pending payment
         *
         * @param LP_Order
         * @param request
         */
        protected
        function payment_status_pending($order, $refid)
        {
            $this->payment_status_completed($order, $refid);
        }

        /**
         * @param LP_Order
         * @param string $txn_id
         * @param string $note - not use
         */
        public
        function payment_complete($order, $refid = '', $note = '')
        {
            $order->payment_complete($refid);
        }
    }
}
