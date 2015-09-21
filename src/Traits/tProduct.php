<?php

namespace Debugteam\Paypalrest\Traits;

trait tProduct {
	
	private $name = '';
	private $currency = '';
	private $description = '';
	private $quantity = 0;
	private $sku = '';
	private $price = 0;
	private $tax = 0;
	
	public function setName($name) {
		$this->name = $name;
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
	
	public function setQuantity($quantity) {
		$this->quantity = $quantity;
		return $this;
	}
	
	public function setSku($sku) {
		$this->sku = $sku;
		return $this;
	}
	
	public function setPrice($price) {
		$this->price = $price;
		return $this;
	}
	
	public function setTax($tax) {
		$this->tax = $tax;
		return $this;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getCurrency() {
		return $this->currency;
	}
	
	public function getDescription() {
		return $this->description;
	}
	
	public function getQuantity() {
		return trim($this->quantity);
	}
	
	public function getSku() {
		return $this->sku;
	}
	
	public function getPrice() {
		return number_format($this->price,2,'.','');
	}
	
	public function getTax() {
		return $this->tax;
	}
}