<?php

namespace Debugteam\Paypalrest\Interfaces;

interface iOrder {
	public function setTxnId($txnid);
	public function setPaymentId($paymentid);
	public function setProduct(iProduct $product);
	public function setPaymentStatus($paymentinfo);
	public function setCustomer(iCustomer $customer);
	public function setHandlingFee($handlingfee);
	public function setShipping($shipping);
	public function setCurrency($currency);
	public function setDescription($description);
	public function setInvoiceNumber($invoicenumber);
	public function setTaxTotal($taxtotal);	
	public function getTxnId();		
	public function getPaymentId();	
	public function getProducts();
	public function getPaymentStatus();	
	public function getCustomer();	
	public function getHandlingFee();
	public function getShipping();
	public function getCurrency();
	public function getDescription();
	public function getInvoiceNumber();
	public function getTaxTotal();
	public function save();
}