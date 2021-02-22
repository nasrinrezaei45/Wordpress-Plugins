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
        return $this->curl_post('https://pay.ir/pg/send', [
            'api'          => $this->api,
            'amount'       => $this->amount,
            'redirect'     => $this->redirect,
            'mobile'       => $this->mobile,
            'factorNumber' => $this->factorNumber,
            'description'  => $this->Description,
            'resellerId'   => '1000123135'
        ]);
	}

	/**
	 * Verify Payment
	 *
	 * @param  Not param
	 * @return Status verify
	 */
	public function Verify() {

        return $this->curl_post('https://pay.ir/pg/verify', [
            'api' 	=> $this->api,
            'token' => $this->Token,
        ]);


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
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
	}
}
