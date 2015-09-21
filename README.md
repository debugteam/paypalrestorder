# paypal-rest-orderclass
## works best with the orderobject

I made some traits to make it easier to implement the interfaces. 

The save methods for customer and order must be implemented by you, 
since i don't know your backend :P


## config.inc.php

```php


<?php
	define('PAYPAL_RETURN_URL',BASE_URL.'?page=book&action=paypalreturn');
 	define('PAYPAL_CANCLE_URL',BASE_URL.'?page=book&action=paypalcancle');
	define('PAYPAL_CLIENT_SECRET','fdsgfdgkd345gfdfg321FSddfDasbgndfkbgnWdffk');
 	define('PAYPAL_CLIENT_ID','kjwQohfldasncFSDniandkn24nnrlewfknlsdklnGFnsdw');
 	define('PAYPAL_HELPER_MODE','sandbox');
 	define('PAYPAL_HELPER_LOG_ENABLED',true);
 	define('PAYPAL_HELPER_LOG_FILENAME',LOG_PATH.'paypal.log');
 	define('PAYPAL_HELPER_LOG_LEVEL','DEBUG');
 	define('PAYPAL_HELPER_VALIDATION_LEVEL','log');
 	define('PAYPAL_HELPER_CACHE_ENABLED',true);
 	define('PAYPAL_HELPER_CURLOPT_CONNECTTIMEOUT',30);
 	define('PAYPAL_HELPER_PARTNER_ATTRIBUTION_ID','123123123');
?>

```
:arrow_up:


## create payment link

```php

<?php

 
	include('config.inc.php')
	
	$bookingid = '1';
	$invoicenumber = '1';

	class Order implements \Debugteam\Paypalrest\Interfaces\iOrder {
 
		use \Debugteam\Paypalrest\Traits\tOrder;
 
		public function save() {
			// save order via API or database...
		}

		static public function factory() {
			return new Order();
		} 
 
		public function __construct() {
		}	
  	}


	class Customer implements \Debugteam\Paypalrest\Interfaces\iCustomer {
 
		use \Debugteam\Paypalrest\Traits\tCustomer;
 
		public function save() {
			// save customer via API or database...
		}

		static public function factory() {
			return new Customer();
		} 

		public function __construct() {
		}	
  	}

	class Product implements \Debugteam\Paypalrest\Interfaces\iProduct {
	
		use \Debugteam\Paypalrest\Traits\tCustomer;
 	
		static public function factory() {
			return new Product();
		}

		public function __construct() {
		}	
 	}


	// create order
	$order = Order::factory()
			->setCurrency('EUR')
			->setDescription('Anzahlung auf Hotelbuchung mit Buchungsnummer:'.$bookingid)
			->setHandlingFee(0)
			->setInvoiceNumber($invoicenumber)
			->setShipping(0);

	// add products
	for ($i=0,$cnt<count($data);$i<$cnt;$i++) {
		$order->setProduct(Product::factory()
			->setCurrency($data[$i]['currency'])
			->setDescription($data[$i]['description'])
			->setName($data[$i]['name'])
			->setPrice($data[$i]['price'])
			->setQuantity($data[$i]['quantity'])
			->setSku($data[$i]['sku'])
			->setTax($data[$i]['tax'])
		);
	}

	// create paymentlink - customer clicks it and processes payment
	// we get a paymentid, which gets saved into order object
	$paymentlink = \Debugteam\Paypalrest\PaypalRestOrder::factory()->create_payment_link($order);
	echo $paymentlink;

 ?>

```
:arrow_up:



## Execute the created payment -> PAYPAL_RETURN_URL
 
```php

 <?php

	include('config.inc.php');
 
	// the payment is checked on paypal server with paymentid saved in the order object
	// plus the payers data is recieved and saved in a customer object within the order object
	// If you don't want to save the order/customer data, just return 'foo'; on save methods :P
 	$paymentcompleted = PaypalRestOrder::factory()->execute_payment($order);
 	if ($paymentcompleted->next!==false) {
 		if ($paymentcompleted->next=='completed') {
 			do_stuff_when_payment_completed();
		} else {
			do_stuff_when_payment_pending();
		}
 	}

 ?>

```

:arrow_up: