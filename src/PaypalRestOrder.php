<?php
/*
 * change this file with pull request to https://github.com/debugteam/paypalrestorder
 *
 */

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

class PaypalRestOrder {

	private function create_item_list($order) {
		foreach($order->products as $product) {
			if((trim($product['qty'])!='')) {
				$item = new Item();
				$item->setName($product['name'])->setCurrency($order->info['currency'])->setQuantity($product['qty'])->setSku($product['id'])->setPrice(number_format($product['final_price'],2,'.',''));
				$this->item[] = $item;
			}
		}
		if ($order->getBearbeitungsgebuehr()) {
			$item = new Item();
			$item->setName('Bearbeitungsgebühren')->setCurrency($order->info['currency'])->setQuantity(1)->setSku(9999)->setPrice(number_format($order->getBearbeitungsgebuehr(),2,'.',''));
			$this->item[] = $item;
		}
		if ($order->getActioncodeDiscount()) {
			$item = new Item();
			$item->setName($order->actioncode['description'])->setCurrency($order->info['currency'])->setQuantity(1)->setSku(9998)->setPrice(0 - number_format($order->actioncode['discount'],2,'.',''));
			$this->item[] = $item;
		}
		$itemList = new ItemList();
		$itemList->setItems($this->item);
		return $itemList;
	}

	private function calculate_total() {
		$this->subtotal = 0;
		for ($i=0,$cnt = count($this->item);$i<$cnt;$i++) {
			$this->subtotal += ($this->item[$i]->price * $this->item[$i]->quantity);
		}
		$this->total = $this->subtotal;
	}

	private function set_details() {
		$this->details = new Details();
		$this->details->setShipping(0)->setTax(0)->setSubtotal($this->subtotal);
	}

	private function set_transaction($order,$orderdesc,$custom,$itemList) {
		$amount = new Amount();
		$amount->setCurrency($order->info['currency'])->setTotal($this->total)->setDetails($this->details);
		$this->transaction = new Transaction();
		$this->transaction->setAmount($amount)->setItemList($itemList)->setDescription($orderdesc)->setInvoiceNumber($custom);
	}

	private function save_order($order,$custom,$paymentid) {
		$SQL ="UPDATE warenkoerbe SET paypal_token = '".$paymentid."' WHERE paypal_token='".$custom."'";
		$this->db->db_query($SQL,__FILE__,__LINE__);
		$SQL ="UPDATE warenkorbhistory SET paypal_token = '".$paymentid."' WHERE paypal_token='".$custom."'";
		$this->db->db_query($SQL,__FILE__,__LINE__);
		$order->warenkorbID = $paymentid;
		$_SESSION['custom'] = $paymentid;
		$order->save();
	}

    public function create_payment_link($order,$custom,$orderdesc='Testzahlung für Testartikel') {
		$payer = new Payer();
		$payer->setPaymentMethod("paypal");
		$itemList = $this->create_item_list($order);
		$this->calculate_total();
		$this->set_details();
		$this->set_transaction($order,$orderdesc,$custom,$itemList);
		$payment = new Payment();
		$payment->setIntent("sale")->setPayer($payer)->setRedirectUrls($this->redirectUrls)->setTransactions(array($this->transaction));
		//$request = clone $payment;
		try {
			$payment->create($this->PaypalApicontext);
		} catch (Exception $ex) {
		//	\Debugteam\Baselib\ResultPrinter::printError("Created Payment Using PayPal. Please visit the URL to Approve.", "Payment", null, $request, $ex);
			trigger_error('Paypal Zahlung fehlgeschlagen!'.$ex->getMessage().$ex->getTraceAsString().' triggered in line:'.$ex->getLine());
		}
		//$approvalUrl = $payment->getApprovalLink();
		//\Debugteam\Baselib\ResultPrinter::printResult("Created Payment Using PayPal. Please visit the URL to Approve.", "Payment", "<a href='$approvalUrl' >$approvalUrl</a>", $request, $payment);
		$this->save_order($order,$custom,$payment->id);
		return $payment;
    }

	private function payment_save_completed($order,$executedpayment) {
		$order->info['payment_status'] = 'Completed';
		$order->txn_id = $executedpayment->transactions[0]->related_resources[0]->sale->id;
		$order->customer['first_name']		=  $executedpayment->payer->payer_info->first_name;
		$order->customer['last_name']		=  $executedpayment->payer->payer_info->last_name;
		$order->customer['email_address']	=  $executedpayment->payer->payer_info->email;
		//$order->customer['phone']			=  $this->keyarray['contact_phone'];
		$order->customer['phone']			=  '';
		$order->customer['address_street']	=  $executedpayment->payer->payer_info->shipping_address->line1;
		$order->customer['address_zip']		=  $executedpayment->payer->payer_info->shipping_address->postal_code;
		$order->customer['address_city']	=  $executedpayment->payer->payer_info->shipping_address->city;
		$order->customer['address_country']	=  $executedpayment->payer->payer_info->shipping_address->country_code;
		$order->save();
	}

	/*
	 * Payment status "Pending" will be rechecked by IPN
	 */	
	private function payment_save_pending($order,$executedpayment) {
		$order->info['payment_status'] = 'Pending';
		$order->txn_id = $executedpayment->transactions[0]->related_resources[0]->sale->id;
		$order->customer['first_name']		=  $executedpayment->payer->payer_info->first_name;
		$order->customer['last_name']		=  $executedpayment->payer->payer_info->last_name;
		$order->customer['email_address']	=  $executedpayment->payer->payer_info->email;
		//$order->customer['phone']			=  $this->keyarray['contact_phone'];
		$order->customer['phone']			=  '';
		$order->customer['address_street']	=  $executedpayment->payer->payer_info->shipping_address->line1;
		$order->customer['address_zip']		=  $executedpayment->payer->payer_info->shipping_address->postal_code;
		$order->customer['address_city']	=  $executedpayment->payer->payer_info->shipping_address->city;
		$order->customer['address_country']	=  $executedpayment->payer->payer_info->shipping_address->country_code;
		$order->save();
	}

	/*
	 * for this cases we will need manual inspection... therefor we need some kind of notification like email to debugteam
	 */
	private function payment_save_observation($order,$executedpayment) {
		$order->info['payment_status'] = 'Observation';
		$order->txn_id = $executedpayment->transactions[0]->related_resources[0]->sale->id;
		$order->customer['first_name']		=  $executedpayment->payer->payer_info->first_name;
		$order->customer['last_name']		=  $executedpayment->payer->payer_info->last_name;
		$order->customer['email_address']	=  $executedpayment->payer->payer_info->email;
		//$order->customer['phone']			=  $this->keyarray['contact_phone'];
		$order->customer['phone']			=  '';
		$order->customer['address_street']	=  $executedpayment->payer->payer_info->shipping_address->line1;
		$order->customer['address_zip']		=  $executedpayment->payer->payer_info->shipping_address->postal_code;
		$order->customer['address_city']	=  $executedpayment->payer->payer_info->shipping_address->city;
		$order->customer['address_country']	=  $executedpayment->payer->payer_info->shipping_address->country_code;
		$order->paypalpayment				=  $executedpayment;
		$order->save();
	}

	/**
	 *
	 * @param type $order
	 */
	private function payment_approved($order,$executedpayment) {
		$this->payment_save_completed($order,$executedpayment);
		return 'completed';
	}

	private function payment_created($order,$executedpayment) {
		$this->payment_save_observation($order,$executedpayment);
		return 'fup2devmail';
	}

	private function payment_failed($order,$executedpayment) {
		return 'overview';
	}

	private function payment_canceled($order,$executedpayment) {
		return 'overview';
	}
	
	private function payment_expired($order,$executedpayment) {
		return 'overview';
	}

	private function payment_pending($order,$executedpayment) {
		$this->payment_save_pending($order,$executedpayment);
		return 'fup2mail';
	}

	private function payment_in_progress($order,$executedpayment) {
		$this->payment_save_pending($order,$executedpayment);
		return 'fup2mail';
	}

	private function payment_not_implemented($order,$executedpayment) {
		$this->payment_save_observation($order,$executedpayment);
		return 'fup2devmail';
	}

	private function check_payment_state($executedpayment,$order) {
		switch($executedpayment->state) {
			case('created'):		return $this->payment_created($order,$executedpayment);
				break;
			case('approved'):		return $this->payment_approved($order,$executedpayment);
				break;
			case('failed'):			return $this->payment_failed($order,$executedpayment);
				break;
			case('canceled'):		return $this->payment_canceled($order,$executedpayment);
				break;
			case('expired'):		return $this->payment_expired($order,$executedpayment);
				break;
			case('pending'):		return $this->payment_pending($order,$executedpayment);
				break;
			case('in_progress'):	return $this->payment_in_progress($order,$executedpayment);
				break;
			default:				return $this->payment_not_implemented($order,$executedpayment);
		}
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

	public function execute_payment($order) {
		$paymentId = $_GET['paymentId'];
		$payment = Payment::get($paymentId, $this->PaypalApicontext);
		$execution = new PaymentExecution();
		$execution->setPayerId($_GET['PayerID']);
		try {
			$result = $payment->execute($execution, $this->PaypalApicontext);
			#\Debugteam\Baselib\ResultPrinter::printResult("Executed Payment", "Payment", $payment->getId(), $execution, $result);
	        try {
		        $executedpayment = Payment::get($paymentId, $this->PaypalApicontext);
			} catch (Exception $ex) {
				#\Debugteam\Baselib\ResultPrinter::printError("Get Payment", "Payment", null, null, $ex);
				exit('failed');
				// foo
			}
		} catch (Exception $ex) {
			#\Debugteam\Baselib\ResultPrinter::printError("Executed Payment", "Payment", null, null, $ex);
			exit('failed');
		}
		#\Debugteam\Baselib\ResultPrinter::printResult("Get Payment", "Payment", $payment->getId(), null, $payment);
		$paymentcompleted = new \stdClass();
		$paymentcompleted->next = $this->check_payment_state($executedpayment,$order);
		$paymentcompleted->payment = $executedpayment;
		return $paymentcompleted;
	}

	public function failed_payment() {
		\Debugteam\Baselib\ResultPrinter::printResult("User Cancelled the Approval", null);
		exit;
	}

    public function __construct($clientId='',$clientSecret='') {
		if (empty($clientId)||empty($clientSecret)) {
			trigger_error('Hey developer (future me): you need to provide credentials to use the Paypal Rest Api! Go get them here: https://developer.paypal.com/webapps/developer/applications/myapps');
			exit;
		}
		$this->PaypalApicontext = \Debugteam\Baselib\PaypalHelper::getApiContext($clientId, $clientSecret);
		$this->redirectUrls = new RedirectUrls();
		$this->redirectUrls->setReturnUrl(BASE_URL."danke.php?action=succeededpayment")->setCancelUrl(BASE_URL."overview.php?&action=cancledpayment");

		$this->db = new \Debugteam\Baselib\Db;
		if (!$this->db->is_open_con()) {
			$this->db->db_open();
		}
    }

}
