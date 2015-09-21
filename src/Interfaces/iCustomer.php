<?php

namespace Debugteam\Paypalrest\Interfaces;

interface iCustomer {
	public function setFirstName($firstname);
	public function setLastName($lastname);
	public function setEmailAddress($email);
	public function setPhone($phone);
	public function setAddressStreet($adressstreet);
	public function setAddressZip($adresszip);
	public function setAddressCity($city);
	public function setAddressCountry($country);
	public function getFirstName();
	public function getLastName();
	public function getEmailAddress();
	public function getPhone();
	public function getAddressStreet();
	public function getAddressZip();
	public function getAddressCity();
	public function getAddressCountry();	
	public function save();
	static public function factory();
}