<?php 

App::uses('ProviderPlug','Api.Lib');
/**
* Description
*/
class Rediscloud extends ProviderPlug{
	
 	public function check($options=''){
 	    // $options += array();
        // default to connection failure as info
        $info['info'] = __('RedisCloud Connection Failed. Check your connection host, port, and password');

        $redis = new Redis();
		$host = parse_url(getenv('REDISCLOUD_URL'), PHP_URL_HOST);
		$port = parse_url(getenv('REDISCLOUD_URL'), PHP_URL_PORT);
		$pass = parse_url(getenv('REDISCLOUD_URL'), PHP_URL_PASS);
		$connected = $redis->connect($host, $port);

		if($connected){

            $have_pass = !is_array($pass) ? true : false;
            if ($have_pass) {
                $info['info'] = __('RedisCloud Login Failed. Check your connection password');
                $logged_in = $redis->auth($pass);
                if($logged_in){
                    //  get the server info
                    $info['info'] = $redis->info();

                    // write a value into the database
                    $now = microtime(true);
                    $string_value = md5($now);
                    $test_key = 'api:check:string';
                    $could_set = $redis->set($test_key,$string_value);
                    $this->logEvent(__('Set key [%s] with value [%s]',$test_key,$string_value),array(
                        'result'=>$could_set
                    ));
                    // read a value form the database
                    $retrieved_value = $redis->get($test_key);
                    $this->logEvent(__('Retrieved value [%s] from keystore with key [%s]',$retrieved_value,$test_key),array(
                        'retrieved'=>$retrieved_value
                    ));

                    // get all available keys
                    $keys = $redis->keys('*');
                    $info['keys'] = $keys;
                    $have_keys = (
                        is_array($keys)
                        && !empty($keys)
                    ) ? true : false;
                    // if you have keys then get their values
                    if($have_keys){
                        foreach ($keys as $key){
                            $value = $redis->get($key);
                            $info[$key][] = $value;
                            // fixme: remove this debugging break so we can get all lines
                        }
                    }

                    // delete all test keys from the database
                    $deleted_count = $redis->delete($test_key);
                    $this->logEvent(__('Deleting test key [%s]',$test_key),array(
                       'keys_deleted'=>$deleted_count
                    ));

                    $this->appendLog($info);
                }
            }
        }

		return $info;
	}
	
}