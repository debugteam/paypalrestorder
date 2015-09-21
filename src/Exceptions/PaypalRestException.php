<?php

namespace Debugteam\Paypalrest\Exceptions;

class PaypalRestException extends \Debugteam\Baselib\Debug {

	public function __construct($message, $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}

	public function print_debug($f, $l) {
		$this->class = __CLASS__.PHP_EOL.'triggered in '.$f .' '.$l.PHP_EOL;
		parent::print_backtrace();
	}

}