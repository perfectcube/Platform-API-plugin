<?php
/**
 * Api Controller
 *
 * Allows clear cache from Api panel for DebugKit
 *
 * PHP 5
 *
 * Copyright 2010-2012, Marc Ypes, The Netherlands
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     2010-2012 Marc Ypes, The Netherlands
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('ApiAppController', 'Api.Controller');
App::uses('Api', 'Api.Lib');
App::uses('ProviderPlug', 'Api.Lib.Plugin');

/**
 * Api Controller
 *
 * @package Api.Controller
 */
class StatusController extends ApiAppController {

	public function check($provider){
		// Does the provider plugin exist?
		debug(App::paths());
			// try and load the providers plugin
			// does the providers plugin impliment the check() method?
				// call $Provider->check(); the plugin takes care of its own arguments
		// 
	}

}
