<?php 

/**
* Description
*/
class redistogo extends ProviderPlug{
	
	function __construct($options=array()){
		# code...
	}
	
	public public function check($options=''){
		$redis = new Redis();
		$host = parse_url($_ENV['REDISTOGO_URL'], PHP_URL_HOST);
		$port = parse_url($_ENV['REDISTOGO_URL'], PHP_URL_PORT);
		$pass = parse_url($_ENV['REDISTOGO_URL'], PHP_URL_PASS);
		$redis->connect($host, $port);
		$have_pass = !is_array($pass) ? true : false;
		if ($have_pass) {
			$redis->auth($pass);
		}
		debug($redis->info());
	}
	
}