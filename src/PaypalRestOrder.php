<?php

namespace Debugteam\PaypalRestOrderClass;

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

	
	protected function create_item_list() {
		$item1 = new Item();
		$item1->setName('Testartikel')
			->setCurrency('EUR')
			->setQuantity(1)
			->setSku("123123") // Similar to `item_number` in Classic API
			->setPrice(1);
		
		$itemList = new ItemList();
		$itemList->setItems(array($item1));
		return $itemList
	}
	
	protected function calculate_totals() {
		
	}
	
	protected function set_defaults() {
		
	}
	
	
    public function create_payment_link($order) {
		
		$payer = new Payer();
		$payer->setPaymentMethod("paypal");
		
		
		$details = new Details();
		$details->setShipping(0)
			->setTax(0)
			->setSubtotal(1);
		
		$amount = new Amount();
		$amount->setCurrency("EUR")
			->setTotal(1)
			->setDetails($details);
		
		$transaction = new Transaction();
		$transaction->setAmount($amount)
			->setItemList($itemList)
			->setDescription("Testzahlung für Testartikel")
			->setInvoiceNumber(uniqid());
		
		$redirectUrls = new RedirectUrls();
		$redirectUrls->setReturnUrl(BASE_URL."/index.php?page=paypaltest&action=succeededpayment")
			->setCancelUrl(BASE_URL."/index.php?page=paypaltest&action=failedpayment");
		
		$payment = new Payment();
		$payment->setIntent("sale")
			->setPayer($payer)
			->setRedirectUrls($redirectUrls)
			->setTransactions(array($transaction));
		
		$request = clone $payment;
		
		try {
			$payment->create($this->PaypalApicontext);
		} catch (Exception $ex) {
			Debugteam\Baselib\ResultPrinter::printError("Created Payment Using PayPal. Please visit the URL to Approve.", "Payment", null, $request, $ex);
			exit(1);
		}	
		
		$approvalUrl = $payment->getApprovalLink();
		
		Debugteam\Baselib\ResultPrinter::printResult("Created Payment Using PayPal. Please visit the URL to Approve.", "Payment", "<a href='$approvalUrl' >$approvalUrl</a>", $request, $payment);
		
		return $payment;

    }

	public function execute_payment() {
		$paymentId = $_GET['paymentId'];
		$payment = Payment::get($paymentId, $this->PaypalApicontext);	
		$execution = new PaymentExecution();
		$execution->setPayerId($_GET['PayerID']);		
		try {
			$result = $payment->execute($execution, $this->PaypalApicontext);
			Debugteam\Baselib\ResultPrinter::printResult("Executed Payment", "Payment", $payment->getId(), $execution, $result);
	        try {
		        $payment = Payment::get($paymentId, $this->PaypalApicontext);
			} catch (Exception $ex) {
				Debugteam\Baselib\ResultPrinter::printError("Get Payment", "Payment", null, null, $ex);
				exit(1);
			}
		} catch (Exception $ex) {		
			Debugteam\Baselib\ResultPrinter::printError("Executed Payment", "Payment", null, null, $ex);
			exit(1);
		}		
		Debugteam\Baselib\ResultPrinter::printResult("Get Payment", "Payment", $payment->getId(), null, $payment);
		return $payment;			
	}
	
	public function failed_payment() {
		Debugteam\Baselib\ResultPrinter::printResult("User Cancelled the Approval", null);
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