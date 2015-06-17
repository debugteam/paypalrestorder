<?php

define("USE_SANDBOX", 1);

/*
 * change this file with pull request to https://github.com/debugteam/paypalrestorder
 *
 */

namespace Debugteam\Paypalrest;

use PayPal\Api\Payment;

class PaypalIPNCheck {

	private function save_order($order,$custom,$paymentid) {
		/* business logic for activation of vouchers */
		$SQL ="UPDATE warenkoerbe SET paypal_token = '".$paymentid."' WHERE paypal_token='".$custom."'";
		$this->db->db_query($SQL,__FILE__,__LINE__);
		$order->warenkorbID = $paymentid;
		$_SESSION['custom'] = $paymentid;
		$order->save();
	}

	/** -- currently unused
	 * recieve paymentinfo via givven $paymentId (customid/paypal_token)
	 *
	 * @param type $paymentId
	 * @return obj $payment
	 */
	public function get_payment($paymentId) {
		try {
			$payment = Payment::get($paymentId, $this->PaypalApicontext);
		} catch (Exception $ex) {
			// NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
			\Debugteam\Baselib\ResultPrinter::printError("Get Payment", "Payment", null, null, $ex);
			exit(1);
		}
		// NOTE: PLEASE DO NOT USE RESULTPRINTER CLASS IN YOUR ORIGINAL CODE. FOR SAMPLE ONLY
		 \Debugteam\Baselib\ResultPrinter::printResult("Get Payment", "Payment", $payment->getId(), null, $payment);
		return $payment;
	}
	
	protected function read_ipn_and_make_request() {
		$raw_post_data = file_get_contents('php://input');
		$raw_post_array = explode('&', $raw_post_data);
		$myPost = array();
		foreach ($raw_post_array as $keyval) {
			$keyval = explode ('=', $keyval);
			if (count($keyval) == 2) {
				$myPost[$keyval[0]] = urldecode($keyval[1]);
			}
		}
		$this->req = 'cmd=_notify-validate';
		if(function_exists('get_magic_quotes_gpc')) {
			$get_magic_quotes_exists = true;
		}
		foreach ($myPost as $key => $value) {
			if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
				$value = urlencode(stripslashes($value));
			} else {
				$value = urlencode($value);
			}
			$this->req .= "&$key=$value";
		}		
	}
	
	protected function curl_connect() {
		$this->ch = curl_init($this->paypal_url);
		if ($this->ch == FALSE) {
			return FALSE;
		}
		curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->req);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($this->ch, CURLOPT_FORBID_REUSE, 1);
		if(DEBUG == true) {
			curl_setopt($this->ch, CURLOPT_HEADER, 1);
			curl_setopt($this->ch, CURLINFO_HEADER_OUT, 1);
		}
		// CONFIG: Optional proxy configuration
		//curl_setopt($this->ch, CURLOPT_PROXY, $proxy);
		//curl_setopt($this->ch, CURLOPT_HTTPPROXYTUNNEL, 1);
		// Set TCP timeout to 30 seconds
		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
		// CONFIG: Please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set the directory path
		// of the certificate as shown below. Ensure the file is readable by the webserver.
		// This is mandatory for some environments.
		//$cert = __DIR__ . "./cacert.pem";
		//curl_setopt($this->ch, CURLOPT_CAINFO, $cert);		
	}
	

	protected function send_answer() {
		$this->res = curl_exec($this->ch);
		if (curl_errno($this->ch) != 0) // cURL error
			{
			if(DEBUG == true) {	
				error_log(date('[Y-m-d H:i e] '). "Can't connect to PayPal to validate IPN message: " . curl_error($this->ch) . PHP_EOL, 3, LOG_FILE);
			}
			curl_close($this->ch);
			exit;
		} else {
				// Log the entire HTTP response if debug is switched on.
				if(DEBUG == true) {
					error_log(date('[Y-m-d H:i e] '). "HTTP request of validation request:". curl_getinfo($this->ch, CURLINFO_HEADER_OUT) ." for IPN payload: $this->req" . PHP_EOL, 3, LOG_FILE);
					error_log(date('[Y-m-d H:i e] '). "HTTP response of validation request: $this->res" . PHP_EOL, 3, LOG_FILE);
				}
				curl_close($this->ch);
		}
		// Inspect IPN validation result and act accordingly
		// Split response headers and payload, a better way for strcmp
		$tokens = explode("\r\n\r\n", trim($this->res));
		$this->res = trim(end($tokens));		
	}
	
	protected function check_payment() {
		if (strcmp ($this->res, "VERIFIED") == 0) {
			// check whether the payment_status is Completed
			// check that txn_id has not been previously processed
			// check that receiver_email is your PayPal email
			// check that payment_amount/payment_currency are correct
			// process payment and mark item as paid.
			// assign posted variables to local variables
			//$item_name = $_POST['item_name'];
			//$item_number = $_POST['item_number'];
			//$payment_status = $_POST['payment_status'];
			//$payment_amount = $_POST['mc_gross'];
			//$payment_currency = $_POST['mc_currency'];
			//$txn_id = $_POST['txn_id'];
			//$receiver_email = $_POST['receiver_email'];
			//$payer_email = $_POST['payer_email'];

			if(DEBUG == true) {
				error_log(date('[Y-m-d H:i e] '). "Verified IPN: $this->req ". PHP_EOL, 3, LOG_FILE);
			}
		} else if (strcmp ($this->res, "INVALID") == 0) {
			// log for manual investigation
			// Add business logic here which deals with invalid IPN messages
			if(DEBUG == true) {
				error_log(date('[Y-m-d H:i e] '). "Invalid IPN: $this->req" . PHP_EOL, 3, LOG_FILE);
			}
		}		
	}
	
	public function check_ipn_execute() {
		$this->curl_connect();
		$this->read_ipn_and_make_request();
		$this->send_answer();
		$this->check_payment();
	}

    public function __construct($clientId='',$clientSecret='') {
		if (empty($clientId)||empty($clientSecret)) {
			trigger_error('Hey developer (future me): you need to provide credentials to use the Paypal Rest Api! Go get them here: https://developer.paypal.com/webapps/developer/applications/myapps');
			exit;
		}
		$this->PaypalApicontext = \Debugteam\Baselib\PaypalHelper::getApiContext($clientId, $clientSecret);

		if(USE_SANDBOX == 1) {
			$this->paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
		} else {
			$this->paypal_url = "https://www.paypal.com/cgi-bin/webscr";
		}		
		
		$this->db = new \Debugteam\Baselib\Db;
		if (!$this->db->is_open_con()) {
			$this->db->db_open();
		}
    }

}