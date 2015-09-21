<?php

namespace Debugteam\Paypalrest\Interfaces;

interface iProduct {
	public function setName($name);
	public function setCurrency($currency);
	public function setDescription($description);
	public function setQuantity($quantity);
	public function setSku($sku);
	public function setPrice($price);
	public function setTax($tax);
	public function getName();
	public function getCurrency();
	public function getDescription();
	public function getQuantity();
	public function getSku();
	public function getPrice();	
	public function getTax();	
	static public function factory();	
}
