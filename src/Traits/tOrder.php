<?php

namespace Debugteam\Paypalrest\Models;

trait tOrder {
	
	private $customer = false;
	private $txnid = '';
	private $products = false;
	private $paymentid = '';
	private $paymentstatus = '';
	private $handlingfee = '';
	private $shipping = '';
	private $currency = '';
	private $description = '';
	private $invoicenumber = '';
	private $taxtotal = '';
	
	public function setTxnId($txnid) {
		$this->txnid = $txnid;
		return $this;
	}
	
	public function setPaymentId($paymentid) {
		$this->paymentid = $paymentid;
	}
	
	public function setProduct(iProduct $product) {
		$this->products[] = $product;
		return $this;
	}
	
	public function setPaymentStatus($paymentstatus) {
		$this->paymentstatus = $paymentstatus;
		return $this;
	}
	
	public function setCustomer(iCustomer $customer) {
		$this->customer = $customer;
		return $this;
	}
	
	public function setHandlingFee($handlingfee) {
		$this->handlingfee = $handlingfee;
		return $this;
	}
	
	public function setShipping($shipping) {
		$this->shipping = $shipping;
		return $this;
	}
	
	public function setCurrency($currency) {
		$this->currency = $currency;
		return $this;
	}
	
	public function setDescription($description) {
		$this->description = $description;
		return $this;
	}
	
	public function setInvoiceNumber($invoicenumber) {
		$this->invoicenumber = $invoicenumber;
		return $this;
	}
	
	public function setTaxTotal($taxtotal) {
		$this->taxtotal = $taxtotal;
		return $this;
	}	
	
	public function getTxnId() {
		return $this->txnid;
	}
	
	public function getPaymentId() {
		return $this->paymentid;
	}
	
	public function getProducts() {
		if ($this->products===false) {
			return \Product::factory();
		}
		return $this->products;
	}

	public function getPaymentStatus() {
		return $this->paymentstatus;
	}
	
	public function getCustomer() {
		if ($this->customer===false) {
			return \Customer::factory();
		}		
		return $this->customer;
	}	
	
	public function getHandlingFee() {
		return $this->handlingfee;
	}
	
	public function getShipping() {
		return $this->shipping;
	}
	
	public function getCurrency() {
		return $this->currency;
	}

	public function getDescription() {
		return $this->description;
	}
	
	public function getInvoiceNumber() {
		return $this->invoicenumber;
	}
	
	public function getTaxTotal() {
		return $this->taxtotal;
	}
}