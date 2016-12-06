<?php 

App::uses('ProviderPlug','Api.Lib');
/**
* Description
*/
class Rediscloud extends ProviderPlug{
	
 	public function check($options=''){
		$redis = new Redis();
		$host = parse_url(getenv('REDISCLOUD_URL'), PHP_URL_HOST);
		$port = parse_url(getenv('REDISCLOUD_URL'), PHP_URL_PORT);
		$pass = parse_url(getenv('REDISCLOUD_URL'), PHP_URL_PASS);
		$redis->connect($host, $port);
		$have_pass = !is_array($pass) ? true : false;
		if ($have_pass) {
			$redis->auth($pass);
		}
		$info = $redis->info();
		return $info;
	}
	
}