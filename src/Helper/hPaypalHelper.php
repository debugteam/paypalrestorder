<?php
namespace Debugteam\Paypalrest\Helper;

class hPaypalHelper {
	
	public static function getApiContext() {

		// #### SDK configuration
		// Register the sdk_config.ini file as the configuration source.
		//if(!defined("PP_CONFIG_PATH")) {
		//	define("PP_CONFIG_PATH", CONFIG_PATH); // CONFIG_PATH is defined in init.inc.php
		//}

		$apiContext = new \PayPal\Rest\ApiContext(
			new \PayPal\Auth\OAuthTokenCredential(
				PAYPAL_CLIENT_ID,
				PAYPAL_CLIENT_SECRET
			)
		);

		$apiContext->setConfig(
			array(
				'mode' => PAYPAL_HELPER_MODE,
				'log.LogEnabled' => PAYPAL_HELPER_LOG_ENABLED,
				'log.FileName' => PAYPAL_HELPER_LOG_FILENAME,
				'log.LogLevel' => PAYPAL_HELPER_LOG_LEVEL,
				'validation.level' => PAYPAL_HELPER_VALIDATION_LEVEL,
				'cache.enabled' => PAYPAL_HELPER_CACHE_ENABLED,
				// 'http.CURLOPT_CONNECTTIMEOUT' => PAYPAL_HELPER_CURLOPT_CONNECTTIMEOUT
				// 'http.headers.PayPal-Partner-Attribution-Id' 
				// => PAYPAL_HELPER_PARTNER_ATTRIBUTION_ID
			)
		);
		return $apiContext;
	}	

}
