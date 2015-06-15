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

	protected function create_item_list($order) {
		foreach($order->products as $product) {
			if((trim($product['qty'])!='')) {
				$item = new Item();
				$this->item->setName($product['name'])->setCurrency($order->info['currency'])->setQuantity($product['qty'])->setSku($product['id'])->setPrice(number_format($product['final_price'],2,'.',''));
				$this->item[] = $item;
				var_dump($item);
				exit;
			}
		}
		if ($order->getBearbeitungsgebuehr()) {
			$item = new Item();
			$this->item->setName('Bearbeitungsgebühren')->setCurrency($order->info['currency'])->setQuantity(1)->setSku(9999)->setPrice(number_format($order->getBearbeitungsgebuehr(),2,'.',''));
			$this->item[] = $item;			
		}
		if ($order->getActioncodeDiscount()) {
			$item = new Item();
			$this->item->setName($order->actioncode['description'])->setCurrency($order->info['currency'])->setQuantity(1)->setSku(9998)->setPrice(0 - number_format($order->actioncode['discount'],2,'.',''));
			$this->item[] = $item;
		}
		$itemList = new ItemList();
		$itemList->setItems($this->item);
		return $itemList;
	}
	
	
	protected function calculate_total($itemList) {
		$this->subtotal = 0;
		$this->total = 0;
		for ($i=0,$cnt = count($this->item);$i<$cnt;$i++) {
			$this->subtotal += $this->item;
		}
	}
		
    public function create_payment_link($order,$custom,$orderdesc='Testzahlung für Testartikel') {
		$payer = new Payer();
		$payer->setPaymentMethod("paypal");
		$itemList = $this->create_item_list($order);
		$details = new Details();
		$details->setShipping(0)->setTax(0)->setSubtotal(1);
		$amount = new Amount();
		$amount->setCurrency($order->info['currency'])->setTotal(1)->setDetails($details);
		$transaction = new Transaction();
		$transaction->setAmount($amount)->setItemList($itemList)->setDescription($orderdesc)->setInvoiceNumber($custom);
		$redirectUrls = new RedirectUrls();
		$redirectUrls->setReturnUrl(BASE_URL."danke.php?action=succeededpayment")->setCancelUrl(BASE_URL."overview.php?&action=cancledpayment");
		$payment = new Payment();
		$payment->setIntent("sale")->setPayer($payer)->setRedirectUrls($redirectUrls)->setTransactions(array($transaction));
		$request = clone $payment;
		try {
			$payment->create($this->PaypalApicontext);
		} catch (Exception $ex) {
			\Debugteam\Baselib\ResultPrinter::printError("Created Payment Using PayPal. Please visit the URL to Approve.", "Payment", null, $request, $ex);
			//trigger_error('Paypal Zahlung fehlgeschlagen! Achtung! Dies ist keine Übung!!!');
		}	
		$approvalUrl = $payment->getApprovalLink();
		\Debugteam\Baselib\ResultPrinter::printResult("Created Payment Using PayPal. Please visit the URL to Approve.", "Payment", "<a href='$approvalUrl' >$approvalUrl</a>", $request, $payment);
		return $payment;
    }

	public function execute_payment() {
		$paymentId = $_GET['paymentId'];
		$payment = Payment::get($paymentId, $this->PaypalApicontext);	
		$execution = new PaymentExecution();
		$execution->setPayerId($_GET['PayerID']);		
		try {
			$result = $payment->execute($execution, $this->PaypalApicontext);
			\Debugteam\Baselib\ResultPrinter::printResult("Executed Payment", "Payment", $payment->getId(), $execution, $result);
	        try {
		        $payment = Payment::get($paymentId, $this->PaypalApicontext);
			} catch (Exception $ex) {
				\Debugteam\Baselib\ResultPrinter::printError("Get Payment", "Payment", null, null, $ex);
				exit(1);
				// foo
			}
		} catch (Exception $ex) {		
			\Debugteam\Baselib\ResultPrinter::printError("Executed Payment", "Payment", null, null, $ex);
			exit(1);
		}		
		\Debugteam\Baselib\ResultPrinter::printResult("Get Payment", "Payment", $payment->getId(), null, $payment);
		return $payment;			
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
		// need db connection?
		//$this->db = new \Debugteam\Baselib\Db;
		//if (!$this->db->is_open_con()) {
		//	$this->db->db_open();
		//}
    }

}
