<?php

namespace Debugteam\Paypalrest\Traits;

trait tCustomer {

	private $firstname = '';
	private $lastname = '';
	private $email = '';
	private $phone = '';
	private $adressstreet = '';
	private $adresszip = '';
	private $city = '';
	private $country = '';

	public function setFirstName($firstname) {
		$this->firstname = $firstname;
		return $this;
	}
	
	public function setLastName($lastname) {
		$this->lastname = $lastname;
		return $this;
	}
	
	public function setEmailAddress($email) {
		$this->email = $email;
		return $this;
	}
	
	public function setPhone($phone){ 
		$this->phone = $phone;
		return $this;
	}
	
	public function setAddressStreet($adressstreet){ 
		$this->adressstreet = $adressstreet;
		return $this;
	}
	
	public function setAddressZip($adresszip){ 
		$this->adresszip = $adresszip;
		return $this;
	}
	
	public function setAddressCity($city){ 
		$this->city = $city;
		return $this;
	}
	
	public function setAddressCountry($country){ 
		$this->country = $country;
		return $this;
	}
	
	public function getFirstName(){ 
		return $this->firstname; 
	}
	
	public function getLastName(){ 
		return $this->lastname; 
	}
	
	public function getEmailAddress(){ 
		return $this->email; 
	}
		
	public function getPhone(){ 
		return $this->phone; 
	}
	
	public function getAddressStreet(){ 
		return $this->adressstreet; 
	}
	
	public function getAddressZip(){ 
		return $this->adresszip; 
	}

	public function getAddressCity(){ 
		return $this->city; 
	}
	
	public function getAddressCountry(){ 
		return $this->country; 
	}
}