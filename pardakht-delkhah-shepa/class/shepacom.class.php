<?php
/**
 * shepacom
 *
 * shepacom getway class
 *
 * @copyright	(c) 2012 Theme Lavin
 * @version		1.0
 */
Class shepacom {
	/**
	 * shepacom API
	 *
	 * @var integer
	 */
	public $api;

	/**
	 * shepacom getway amount
	 *
	 * @var integer
	 */
	public $amount;

	/**
	 * shepacom redirect payment
	 *
	 * @var integer
	 */
	public $redirect;

	/**
	 *  shepacom mobile
	 *
	 * @var string
	 */
	public $mobile;

	/**
	 * Factor Number
	 *
	 * @var integer
	 */
	public $factorNumber;


	/**
	 * Description for payment operations
	 *
	 * @var string
	 */
	public $Description;

	/**
	 * Payment ID for payment operations
	 *
	 * @var string
	 */
	public $PaymentID;

    /**
	 * Payment ID for payment operations
	 *
	 * @var string
	 */
	public $Token;


	/**
	 * Constructors
	 */
	public function __construct() {

	}

	/**
	 * Request for payment transactions
	 *
	 * @param  Not param
	 * @return Status request
	 */
	public function Request() {
	    $url = "https://merchant.shepa.com/api";
	    if($this->api == "sandbox") {
            $url = "https://sandbox.shepa.com/api";
        }
        $data = [
            'api'          => $this->api,
            'amount'       => $this->amount,
            'callback'     => $this->redirect."?factorNumber=".$this->factorNumber,
            'email'       => $this->email,
            'description'  => $this->Description,
        ];
        if (!empty($this->mobile)) {
            $data['mobile'] = $this->mobile;
        }
        return $this->curl_post($url."/v1/token", $data );

	}

	/**
	 * Verify Payment
	 *
	 * @param  Not param
	 * @return Status verify
	 */
	public function Verify() {

	   if(!empty($_GET["status"]) && $_GET["status"] == "success") {
           $url = "https://merchant.shepa.com/api";
           if($this->api == "sandbox") {
               $url = "https://sandbox.shepa.com/api";
           }
           $data = [
               'api' => $this->api,
               'token' => $_GET["token"],
               'amount' => $this->amount,
           ];
           return $this->curl_post($url.'/v1/verify', $data);
       }

    }

    /**
     * curl post Payment
     *
     * @param  Not param
     * @return Status verify
     */
	public function curl_post($url, $params) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
	}
}
