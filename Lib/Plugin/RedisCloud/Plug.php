<?php 

App::uses('ProviderPlug','Api.Lib');

/**
* Description
*/
class RedisCloudPlug extends ProviderPlug{
	
 	public function check($options=''){
		
		$now = CakeTime::niceShort(time());
		$event = array(
			'now' => $now,  
		);
		$entry = print_r($event,true);
	    CakeLog::write('debug',"CakeLog::write() wrote from api/status/check: ".$entry);
		error_log("error_log() wrote rom api/status/check: ".$entry);
		
        // $options += array();
        // default to connection failure as info
        $info['info'] = __('RedisCloud Connection Failed. Check your connection host, port, and password');

        $redis = new Redis();
        $host = parse_url(getenv('REDISCLOUD_URL'), PHP_URL_HOST);
        $port = parse_url(getenv('REDISCLOUD_URL'), PHP_URL_PORT);
        $pass = parse_url(getenv('REDISCLOUD_URL'), PHP_URL_PASS);
        $connected = $redis->connect($host, $port);

        if ($connected) {

            $have_pass = !is_array($pass) ? true : false;
            if ($have_pass) {
                $info['info'] = __('RedisCloud Login Failed. Check your connection password');
                $logged_in = $redis->auth($pass);
                if ($logged_in) {
                    //  get the server info
                    $info['info'] = $redis->info();

                    // write a value into the database
                    $now = microtime(true);
                    $string_value = md5($now);
                    $test_key = 'api:check:string';
                    $could_set = $redis->set($test_key, $string_value);
                    if (!$could_set) {
                        $this->logEvent(__('Redis::set() Failed', $test_key), array(
                            'result' => __('$redis->set("%s") returned [%s]', $string_value, $could_set)
                        ));
                    }

                    // read a value form the database
                    $retrieved_value = $redis->get($test_key);

                    $string_retrival_failure = ($retrieved_value != $string_value) ? true : false;
                    $message = __('String retrival using key [%s] failed. Expected [%s] but got [%s]', $test_key, $string_value, $retrieved_value);
                    if (!$string_retrival_failure) {
                        $message = __("String retrieval of value [%s] worked", $retrieved_value);
                    }
                    $this->logEvent(__('Redis::set() => Redis::get()'), array(
                        'Message' => $message
                    ));

                    // get all available keys
                    $keys = $redis->keys('*');
                    $info['Have Keys'] = $keys;
                    $have_keys = (
                        is_array($keys)
                        && !empty($keys)
                    ) ? true : false;
                    // if you have keys then get their values
                    if ($have_keys) {
                        foreach ($keys as $key) {
                            $value = $redis->get($key);
                            $info['Have Values'][$key] = $value;
                        }
                    }

                    // delete all test keys from the database
                    $deleted_count = $redis->delete($test_key);
                    $this->logEvent(__('Key Delete'), array(
                        'keys_deleted' => __('test key [%s] deletion [%s]', $test_key, $deleted_count)
                    ));

                    $this->appendLog($info);
                }
            }
        }

        return $info;
    }
}