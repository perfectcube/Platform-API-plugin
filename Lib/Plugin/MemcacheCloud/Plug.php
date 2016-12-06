<?php 

App::uses('ProviderPlug','Api.Lib');
/**
* Description
*/
class MemcacheCloud extends ProviderPlug{
	
 	public function check($options=''){
		
		$server = getenv('MEMCACHEDCLOUD_SERVERS');
		$user = getenv('MEMCACHEDCLOUD_USERNAME');
		$pass = getenv('MEMCACHEDCLOUD_PASSWORD');
		$host = parse_url($server, PHP_URL_HOST);
		$port = parse_url($server, PHP_URL_PORT);

		// setup memcache connection
		$Memcached = new Memcached();
		$Memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
		$Memcached->addServer($host, $port);
		$Memcached->setSaslAuthData($user, $pass);
		
		// get the server status
		$info['stats'] = $Memcached->getStats();
		$info['data_checks'] = $this->checkIO($Memcached);

		// debug($info);
		return $info;
		
	}
	
	private function checkIO(&$Memcached){
		$status = array();
		// store and retrieve a value
		// setup an intiaal value to store and check the results against
		$seed = time().microtime();
		//key to check object storage
		$in_checksum = Security::hash($seed, 'sha1', true);
		// setup storage names
		$uuid = CakeText::uuid();
		$array_name = strtr('memcachedcloud__api_status__array_!uuid',array('!uuid' => $uuid));
		$string_name = strtr('memcachedcloud__api_status__string_!uuid',array('!uuid' => $uuid));

		//create the values to store
		$in_array = array(
			'value'=>$seed,
			'checksum'=>$in_checksum
		);
		
		$in_string = serialize($in_array);
		$in_string_checksum = Security::hash($in_string, 'sha1', true);

		// store values
		$Memcached->set($string_name,$in_string);
		$event = $this->inspect($Memcached);
		$this->logEvent(__('Store string value'),$event);
		
		$Memcached->set($array_name,$in_array);
		$event = $this->inspect($Memcached);
		$this->logEvent(__('Store array value'),$event);
		
		$all_keys = $Memcached->getAllKeys();
		$event = $this->inspect($Memcached);
		$this->logEvent(__('View all keys'),$event);
		
		// get the values
		$out_string = $Memcached->get($string_name);
		$event = $this->inspect($Memcached);
		$this->logEvent(__('Get string value'),$event);
		
		$out_array = $Memcached->get($array_name);
		$event = $this->inspect($Memcached);
		$this->logEvent(__('Get array value'),$event);
		
		
		$string_match = ($out_string == $in_string) ? true : false;	
		
		// cleaup the memcache storage
		$deleted_string = $Memcached->delete($string_name);
		$event = $this->inspect($Memcached);
		$this->logEvent(__('Delete key [%s]',$string_name),$event);
		
		$deleted_array = $Memcached->delete($array_name);
		$event = $this->inspect($Memcached);
		$this->logEvent(__('Delete key [%s]',$array_name),$event);
		
		// check the array value
		$can_check_array = (
			is_array($out_array)
			&& isset($out_array['value'])
			&& isset($out_array['checksum'])
		) ? true : false;

		$array_match = false;
		if($can_check_array){
			// debug($out_array);
			// our original value created before storing in memcache
			$out_string_checksum = Security::hash($out_array['value'], 'sha1', true);
			// debug($out_string_checksum);
			// our original chcksum created before storing in memcache
			$out_array_checksum = $out_array['checksum'];
			$array_match = ($out_array_checksum === $out_string_checksum) ? true : false;
		}
		
		$status['message'] = __('IO test Sucessful');
		// check for IO failure
		$io_failure = (
			// our inout string and outpu retrieved from memcache dont match
			!$string_match 
			// our inout array and output retrieved from memcache dont match
			|| !$array_match
			// 	// we couldnt delete the string key we used for this storage test
			|| !$deleted_string
			// // we couldnt delete the array key we used for this storage test
			|| !$deleted_array
		) ? true : false;
		
		if ($io_failure) {
			$status = array(
				'message'=>__('IO test Failed. Here are the values we tried to store:'),
				'values' => array(
					'array'=>$in_array,
					'string' =>$in_string,
				),
				
			);
		}
		
		$this->appendLog($status);
		
		return $status;
		
	}
	
	private function inspect(&$Memcached){
		$code = $Memcached->getResultCode();
		$message = $Memcached->getResultMessage();
		return array(
			'code' => $code,
			'message' => $message, 
		);
	}
	
	private function getStatusType($code){
		$constant = array(
			0 => 'Memcached::MEMCACHED_SUCCESS',
			1 => 'Memcached::MEMCACHED_FAILURE',
			2 => 'Memcached::MEMCACHED_HOST_LOOKUP_FAILURE', // getaddrinfo() and getnameinfo() only
			3 => 'Memcached::MEMCACHED_CONNECTION_FAILURE',
			4 => 'Memcached::MEMCACHED_CONNECTION_BIND_FAILURE', // DEPRECATED see MEMCACHED_HOST_LOOKUP_FAILURE
			5 => 'Memcached::MEMCACHED_WRITE_FAILURE',
			6 => 'Memcached::MEMCACHED_READ_FAILURE',
			7 => 'Memcached::MEMCACHED_UNKNOWN_READ_FAILURE',
			8 => 'Memcached::MEMCACHED_PROTOCOL_ERROR',
			9 => 'Memcached::MEMCACHED_CLIENT_ERROR',
			10 => 'Memcached::MEMCACHED_SERVER_ERROR', // Server returns "SERVER_ERROR"
			11 => 'Memcached::MEMCACHED_ERROR', // Server returns "ERROR"
			12 => 'Memcached::MEMCACHED_DATA_EXISTS',
			13 => 'Memcached::MEMCACHED_DATA_DOES_NOT_EXIST',
			14 => 'Memcached::MEMCACHED_NOTSTORED',
			15 => 'Memcached::MEMCACHED_STORED',
			16 => 'Memcached::MEMCACHED_NOTFOUND',
			17 => 'Memcached::MEMCACHED_MEMORY_ALLOCATION_FAILURE',
			18 => 'Memcached::MEMCACHED_PARTIAL_READ',
			19 => 'Memcached::MEMCACHED_SOME_ERRORS',
			20 => 'Memcached::MEMCACHED_NO_SERVERS',
			21 => 'Memcached::MEMCACHED_END',
			22 => 'Memcached::MEMCACHED_DELETED',
			23 => 'Memcached::MEMCACHED_VALUE',
			24 => 'Memcached::MEMCACHED_STAT',
			25 => 'Memcached::MEMCACHED_ITEM',
			26 => 'Memcached::MEMCACHED_ERRNO',
			27 => 'Memcached::MEMCACHED_FAIL_UNIX_SOCKET', // DEPRECATED
			28 => 'Memcached::MEMCACHED_NOT_SUPPORTED',
			29 => 'Memcached::MEMCACHED_BAD_KEY_PROVIDED', /* MEMCACHED_NO_KEY_PROVIDED Deprecated. use MEMCACHED_BAD_KEY_PROVIDED! */
			30 => 'Memcached::MEMCACHED_FETCH_NOTFINISHED',
			31 => 'Memcached::MEMCACHED_TIMEOUT',
			32 => 'Memcached::MEMCACHED_BUFFERED',
			33 => 'Memcached::MEMCACHED_BAD_KEY_PROVIDED',
			34 => 'Memcached::MEMCACHED_INVALID_HOST_PROTOCOL',
			35 => 'Memcached::MEMCACHED_SERVER_MARKED_DEAD',
			36 => 'Memcached::MEMCACHED_UNKNOWN_STAT_KEY',
			37 => 'Memcached::MEMCACHED_E2BIG',
			38 => 'Memcached::MEMCACHED_INVALID_ARGUMENTS',
			39 => 'Memcached::MEMCACHED_KEY_TOO_BIG',
			40 => 'Memcached::MEMCACHED_AUTH_PROBLEM',
			41 => 'Memcached::MEMCACHED_AUTH_FAILURE',
			42 => 'Memcached::MEMCACHED_AUTH_CONTINUE',
			43 => 'Memcached::MEMCACHED_PARSE_ERROR',
			44 => 'Memcached::MEMCACHED_PARSE_USER_ERROR',
			45 => 'Memcached::MEMCACHED_DEPRECATED',
			46 => 'Memcached::MEMCACHED_IN_PROGRESS',
			47 => 'Memcached::MEMCACHED_SERVER_TEMPORARILY_DISABLED',
			48 => 'Memcached::MEMCACHED_SERVER_MEMORY_ALLOCATION_FAILURE',
			49 => 'Memcached::MEMCACHED_MAXIMUM_RETURN', /* Always add new error code before */
			11 => 'Memcached::MEMCACHED_CONNECTION_SOCKET_CREATE_FAILURE', // = MEMCACHED_ERROR
		);
		$value = isset($constant[$code]) ? $constant[$code] : 'Memcached::UNKNOWN_ERROR_CODE';
		return $value;
	}

	protected function logEvent($label,array $event){
		// extract the status label and append it to the event
		$event['const'] = $this->getStatusType($event['code']);
		// push the entry on the stack
        parent::logEvent($label,$event);
	}
	
}