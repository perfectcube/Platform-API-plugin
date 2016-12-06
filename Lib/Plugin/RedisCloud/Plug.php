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
		$info['info'] = $redis->info();
		$keys = $redis->keys('*');
		$info['keys'] = $keys;
		$have_keys = (
		    is_array($keys)
            && !empty($keys)
        ) ? true : false;

		if($have_keys){
           foreach ($keys as $key){
               $value = $redis->get($key);
               $info[$key][] = $value;
               // fixme: remove this debugging break so we can get all lines
           }
        }

		return $info;
	}
	
}