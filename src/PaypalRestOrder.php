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
			//trigger_error('Paypal Zahlung fehlgeschlagen! Achtung! Dies ist keine Übung!!!');
		}	
		//$approvalUrl = $payment->getApprovalLink();
		//\Debugteam\Baselib\ResultPrinter::printResult("Created Payment Using PayPal. Please visit the URL to Approve.", "Payment", "<a href='$approvalUrl' >$approvalUrl</a>", $request, $payment);
		$this->save_order($order,$custom,$payment->id);
		return $payment;
    }
	
	/**
	 * 
	 * @param type $order
	 */
	private function payment_approved($order,$paymentId) {
		$order->info['payment_status'] = 'Completed'; // die validate-Klasse hat ihr OK gegeben
		$order->txn_id = $paymentId;
		$order->save();
		return 'completed';
	}
	
	private function payment_created() { return 'fup2devmail'; }
	private function payment_failed() { return 'overview'; }
	private function payment_canceled() { return 'overview'; }
	private function payment_expired() { return 'overview'; }
	private function payment_pending() { return 'fup2mail'; }
	private function payment_in_progress() { return 'fup2mail'; }
	private function payment_not_implemented() { return 'fup2devmail'; }

	private function check_payment_state($executedpayment,$order,$paymentId) {
		switch($executedpayment->state) {
			case('created'):		return $this->payment_created();
				break;
			case('approved'):		return $this->payment_approved($order,$paymentId);
				break;
			case('failed'):			return $this->payment_failed();
				break;
			case('canceled'):		return $this->payment_canceled();
				break;
			case('expired'):		return $this->payment_expired();
				break;
			case('pending'):		return $this->payment_pending();
				break;
			case('in_progress'):	return $this->payment_in_progress();
				break;
			default:				return $this->payment_not_implemented();
		}
	}
	
	/**
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
		$paymentcompleted->next = $this->check_payment_state($executedpayment,$order,$paymentId);
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
		
		// need db connection?
		$this->db = new \Debugteam\Baselib\Db;
		if (!$this->db->is_open_con()) {
			$this->db->db_open();
		}
    }

}
