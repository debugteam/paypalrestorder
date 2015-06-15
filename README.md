# paypal-rest-orderclass
## works best with the orderobject

```php

<?php

if ($paymenttype=='paypal') {

	$clientId='';
	$clientSecret='';
	$paypalrestorder = new PaypalRestOrder($clientId,$clientSecret);
	$orderlink = $paypalrestorder->create_payment_link($order);

}

?>

```

:arrow_up: