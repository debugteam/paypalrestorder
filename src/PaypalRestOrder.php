<?php

namespace Debugteam\Paypalrest;

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\PaymentExecution;
use Debugteam\Paypalrest\Interfaces\iOrder;
use Debugteam\Paypalrest\Exceptions\PaypalRestException;


/**
 * @author Jochen Schultz <jschultz@php.net>
 *
 */
class PaypalRestOrder {

	/**
	 * Create the item-list - this will be displayed in the transaction overview
	 *
	 * @param iOrder $order
	 * @return ItemList
	 */
	private function create_item_list(iOrder $order) {
		$products = $order->getProducts();
		foreach($products as $product) {
			if ($product->getQuantity()) {
				$item = new Item();
				$item	->setName($product->getName())
						->setCurrency($product->getCurrency())
						->setDescription($product->getDescription())
						->setQuantity($product->getQuantity())
						->setSku($product->getSku())
						->setTax($product->getTax())
						->setPrice($product->getPrice());
				$items[] = $item;
			}
		}
		$itemList = new ItemList();
		$itemList->setItems($items);
		return $itemList;
	}

	/**
	 * Calculate total price of the order - sum price of all products
	 */
	private function calculate_total() {
		$subtotal = 0;
		for ($i=0,$cnt = count($this->item);$i<$cnt;$i++) {
			$subtotal += ($this->item[$i]->price * $this->item[$i]->quantity);
		}
		return $subtotal;
	}

	/**
	 * Create details of the order
	 *
	 * @param iOrder $order
	 */
	private function create_details(iOrder $order) {
		$details = new Details();
		$details->setShipping($order->getShipping())
				->setTax($order->getTaxTotal())
				->setSubtotal($this->calculate_total($order));
		return $details;
	}

	/**
	 * Create transaction
	 *
	 * @param iOrder $order
	 * @return Transaction
	 */
	private function create_transaction(iOrder $order) {
		$amount = new Amount();
		$amount->setCurrency($order->getCurrency())
				->setTotal($this->calculate_total($order))
				->setDetails($this->create_details($order));
		$transaction = new Transaction();
		$transaction->setAmount($amount)
				->setItemList($this->create_item_list($order))
				->setDescription($order->getDescription())
				->setInvoiceNumber($order->getInvoiceNumber());
		return $transaction;
	}

	/**
	 * Set payment->id in orderobject and call order->save();
	 *
	 * @param iOrder $order
	 * @param type $payment
	 */
	private function save_order(iOrder $order,$payment) {
		$order->setPaymentId($payment->id);
		$order->save();
	}

	/**
	 * Set customer data in customer model if we have data
	 *
	 * @param type $executedpayment
	 * @return \Customer
	 */
	private function create_customer($executedpayment) {
		$customer = new \Customer();
		if (!isset($executedpayment->payer->payer_info)) {
			return $customer;
		}
		$payer_info = $executedpayment->payer->payer_info;
		$customer->setFirstName($payer_info->first_name);
		$customer->setLastName($payer_info->last_name);
		$customer->setEmailAddress($payer_info->email);
		$customer->setAddressStreet($payer_info->line1);
		$customer->setAddressZip($payer_info->postal_code);
		$customer->setAddressCity($payer_info->city);
		$customer->setAddressCountry($payer_info->country_code);
		$customer->setPhone($payer_info->contact_phone);
	}

	/**
	 * Save order completed
	 *
	 * @param iOrder $order
	 * @param Payment $executedpayment
	 */
	private function payment_save_completed(iOrder $order,Payment $executedpayment) {
		$order->setCustomer($this->create_customer($executedpayment));
		$order->setPaymentStatus('Completed');
		$order->setTxnId($executedpayment->transactions[0]->related_resources[0]->sale->id);
		$order->save();
		return 'completed';
	}

	/**
	 * Save order pending
	 *
	 * @param iOrder $order
	 * @param Payment $executedpayment
	 */
	private function payment_save_pending(iOrder $order,Payment $executedpayment) {
		$order->setCustomer($this->create_customer($executedpayment));
		$order->setPaymentStatus('Pending');
		$order->setTxnId($executedpayment->transactions[0]->related_resources[0]->sale->id);
		$order->save();
		return 'pending';
	}

	/**
	 * Save order for observation
	 *
	 * @param iOrder $order
	 * @param Payment $executedpayment
	 */
	private function payment_save_observation(iOrder $order,Payment $executedpayment) {
		$order->setCustomer($this->create_customer($executedpayment));
		$order->setPaymentStatus('Observation');
		$order->setTxnId($executedpayment->transactions[0]->related_resources[0]->sale->id);
		$order->save();
		return false;
	}

	/**
	 * Save order failed
	 *
	 * @param iOrder $order
	 * @param Payment $executedpayment
	 */
	private function payment_save_failed(iOrder $order,Payment $executedpayment) {
		$order->setCustomer($this->create_customer($executedpayment));
		$order->setPaymentStatus('Failed');
		$order->setTxnId($executedpayment->transactions[0]->related_resources[0]->sale->id);
		$order->save();
		return false;
	}

	/**
	 * Save order cancled
	 *
	 * @param iOrder $order
	 * @param Payment $executedpayment
	 */
	private function payment_save_cancled(iOrder $order,Payment $executedpayment) {
		$order->setCustomer($this->create_customer($executedpayment));
		$order->setPaymentStatus('Cancled');
		$order->setTxnId($executedpayment->transactions[0]->related_resources[0]->sale->id);
		$order->save();
		return false;
	}

	/**
	 * save order expired
	 *
	 * @param iOrder $order
	 * @param Payment $executedpayment
	 */
	private function payment_save_expired(iOrder $order,Payment $executedpayment) {
		$order->setCustomer($this->create_customer($executedpayment));
		$order->setPaymentStatus('Expired');
		$order->setTxnId($executedpayment->transactions[0]->related_resources[0]->sale->id);
		$order->save();
		return false;
	}

	/**
	 * Check payment state
	 *
	 * @param iOrder $order
	 * @param Payment $executedpayment
	 * @return string(completed|pending)|false
	 */
	private function check_payment_state(iOrder $order,Payment $executedpayment) {
		switch($executedpayment->state) {
			case('created'):		return $this->payment_save_observation($order,$executedpayment);
			case('approved'):		return $this->payment_save_completed($order,$executedpayment);
			case('failed'):			return $this->payment_save_failed($order,$executedpayment);
			case('canceled'):		return $this->payment_save_cancled($order,$executedpayment);
			case('expired'):		return $this->payment_save_expired($order,$executedpayment);
			case('pending'):		return $this->payment_save_pending($order,$executedpayment);
			case('in_progress'):	return $this->payment_save_pending($order,$executedpayment);
			default:				return $this->payment_save_observation($order,$executedpayment);
		}
	}

	/**
	 * Create payment link
	 *
	 * @param iOrder $order
	 * @return Payment
	 * @todo eventually we should save the paymentlink in the order?
	 * @throws \Debugteam\Paypalrest\Exceptions\PaypalRestException
	 */
    public function create_payment_link(iOrder $order) {
		$payer = new Payer();
		$payer->setPaymentMethod("paypal");
		$payment = new Payment();
		$payment->setIntent("sale")
				->setPayer($payer)
				->setRedirectUrls($this->redirectUrls)
				->setTransactions(array($this->create_transaction($order)));
		try {
			$payment->create($this->PaypalApicontext);
		} catch (\Exception $ex) {
			throw new PaypalRestException('Paypal Zahlung fehlgeschlagen!'.$ex->getMessage().$ex->getTraceAsString().' triggered in line:'.$ex->getLine());
		}
		$this->save_order($order,$payment);
		return $payment->getApprovalLink();
    }

	/**
	 * Execute the payment
	 *
	 * @param iOrder $order
	 * @return \stdClass (->next = paymentstate + ->payment = executedpayment)
	 * @throws \Debugteam\Paypalrest\Exceptions\PaypalRestException
	 */
	public function execute_payment(iOrder $order) {
		$paymentId = filter_input(INPUT_GET, 'paymentId', FILTER_SANITIZE_STRING);
		$payment = Payment::get($paymentId, $this->PaypalApicontext);
		$execution = new PaymentExecution();
		$execution->setPayerId(filter_input(INPUT_GET, 'PayerID', FILTER_SANITIZE_STRING));
		try {
			$result = $payment->execute($execution, $this->PaypalApicontext);
	        try {
		        $executedpayment = Payment::get($paymentId, $this->PaypalApicontext);
			} catch (\Exception $ex) {
				throw new \Exception($ex->getMessage().' result:'.json_encode($result));
			}
		} catch (\Exception $ex) {
			throw new PaypalRestException($ex->getMessage());
		}
		$paymentcompleted = new \stdClass();
		$paymentcompleted->next = $this->check_payment_state($order,$executedpayment);
		$paymentcompleted->payment = $executedpayment;
		return $paymentcompleted;
	}

	/**
	 * Factory method for PaypalRestOrder
	 * 
	 * @return \Debugteam\Paypalrest\class
	 */
	static public function factory() {
		return new PaypalRestOrder();
	}

	/**
	 * Check definition of neccessary constants
	 * 
	 * @throws \Debugteam\Paypalrest\Exceptions\PaypalRestException
	 */
	private function consistancy_check() {
		if (!defined('PAYPAL_CLIENT_ID')) { throw new PaypalRestException('undefined PAYPAL_CLIENT_ID - you need to provide credentials to use the Paypal Rest Api! Go get them here: https://developer.paypal.com/webapps/developer/applications/myapps'); }
		if (!defined('PAYPAL_CLIENT_SECRET')) {	throw new PaypalRestException('undefined PAYPAL_CLIENT_SECRET'); }
		if (!defined('PAYPAL_RETURN_URL')) { throw new PaypalRestException('undefined PAYPAL_RETURN_URL'); }
		if (!defined('PAYPAL_CANCLE_URL')) { throw new PaypalRestException('undefined PAYPAL_CANCLE_URL'); }	
		if (!defined('PAYPAL_HELPER_MODE')) { throw new PaypalRestException('undefined PAYPAL_HELPER_MODE - e.g. `sandbox` or `live`'); }	
		if (!defined('PAYPAL_HELPER_LOG_ENABLED')) { throw new PaypalRestException('undefined PAYPAL_HELPER_LOG_ENABLED e.g. true'); }	
		if (!defined('PAYPAL_HELPER_LOG_FILENAME')) { throw new PaypalRestException('undefined PAYPAL_HELPER_LOG_FILENAME e.g. LOG_PATH.`paypal.log` '); }	
		if (!defined('PAYPAL_HELPER_LOG_LEVEL')) { throw new PaypalRestException('undefined PAYPAL_HELPER_LOG_LEVEL LIVE:`FINE` - DEVELOP: `DEBUG`');}	
		if (!defined('PAYPAL_HELPER_VALIDATION_LEVEL')) { throw new PaypalRestException('undefined PAYPAL_HELPER_VALIDATION_LEVEL e.g. `log`'); }	
		if (!defined('PAYPAL_HELPER_CACHE_ENABLED')) { throw new PaypalRestException('undefined PAYPAL_HELPER_CACHE_ENABLED e.g. true'); }	
		if (!defined('PAYPAL_HELPER_CURLOPT_CONNECTTIMEOUT')) { throw new PaypalRestException('undefined PAYPAL_HELPER_CURLOPT_CONNECTTIMEOUT e.g. 30'); }	
		if (!defined('PAYPAL_HELPER_PARTNER_ATTRIBUTION_ID')) { throw new PaypalRestException('undefined PAYPAL_HELPER_PARTNER_ATTRIBUTION_ID e.g. `123123123`'); }	
	}
	
    public function __construct() {
		$this->consistancy_check();
		$this->PaypalApicontext = Helper\PaypalHelper::getApiContext();
		$this->redirectUrls = new RedirectUrls();
		$this->redirectUrls->setReturnUrl(PAYPAL_RETURN_URL)->setCancelUrl(PAYPAL_CANCLE_URL);
    }

}