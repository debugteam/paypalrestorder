<?php
namespace Debugteam\Paypalrest\Helper;

class PaypalHelper {
	
	public static function getApiContext($clientId, $clientSecret) {

		// #### SDK configuration
		// Register the sdk_config.ini file as the configuration source.
		//if(!defined("PP_CONFIG_PATH")) {
		//	define("PP_CONFIG_PATH", CONFIG_PATH); // CONFIG_PATH is defined in init.inc.php
		//}

		$apiContext = new \PayPal\Rest\ApiContext(
			new \PayPal\Auth\OAuthTokenCredential(
				$clientId,
				$clientSecret
			)
		);

		$apiContext->setConfig(
			array(
				'mode' => 'sandbox',
				'log.LogEnabled' => true,
				'log.FileName' => LOGFILE_PP,
				'log.LogLevel' => 'DEBUG', // PLEASE USE `FINE` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
				'validation.level' => 'log',
				'cache.enabled' => true,
				// 'http.CURLOPT_CONNECTTIMEOUT' => 30
				// 'http.headers.PayPal-Partner-Attribution-Id' => '123123123'
			)
		);
		return $apiContext;
	}	
	
}