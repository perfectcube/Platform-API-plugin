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
App::uses('ProviderPlug', 'Api.Lib');

/**
 * Api Controller
 *
 * @package Api.Controller
 */
class StatusController extends ApiAppController {

    public $helpers = array('Html','Api.Status');

	public function check($provider=''){
        // result is returned as an array.
        $result = array(
            // data returned from the plugin
            // 'data' => $data,
            // a status code
            // 'status' => $status,
            // a message to send to the view; fail or success or whatever
            // 'message' => $message,
        );

		$plug_root = dirname(dirname(__FILE__)).'/Lib';
		
		// if we have a requeted provider then run it. show a list of availble provider plugins
		$requesting_provider = !empty($provider);
		if($requesting_provider){
			// try and load the providers plugin
			$folder = Inflector::classify($provider);
            $class = $folder.'Plug';
            $file = 'Plugin/'.$folder.'/Plug.php';
			$plugin_path = $plug_root.'/'.$file;
			// debug($file);
			// debug($plug_root);
			// Cake's autoload shit is hard to use. fuck it. lets require_once instead
			// debug(App::paths());
			// App::import('Lib',$plug_id,array('file'=>$file));
			// App::build(array('Lib' => array($plug_root.'/'.$class)));
			// App::uses($class, 'Lib');
			// App::uses('Plugin/'.$class, 'PluginApi');
			// debug(App::objects('Api.Lib'));
			$have_file = Files::exists($plugin_path);
			// default to empty data
            $data = array();
			if($have_file){
				require_once($plugin_path);
				// Does the provider plugin exist?
				$have_provider_plug = class_exists($class);
				if($have_provider_plug){
					$Plugin = new $class();
                    // does the providers plugin impliment the check() method?
					$can_check = method_exists($Plugin,'check');
					if($can_check){
                        // call $Provider->check(); the plugin takes care of its own arguments
						$data = $Plugin->check();
						$status = 200;
						$message = __('%s Status Report',$class);
					}
					else{
						$status = 500;
						$message = __('Missing %s::check() method in %s',$class,$file);
					}
				}
				else{
					$status = 500;
					$message = __('Plugin %s not found in file %s',$class,$file);
				}
			}
			else{
				$status = 500;
				$message = __('Missing plugin %s at path %s',$class,$plugin_path);
			}
		
			$result = array(
				'data' => $data,
				'status' => $status,
				'message' => $message,
			);
		}
		// no provider requested. make a list of providers and return that instead
		else{
			// scan the plugin directory to get a list of folders that have a Plug.php implimented
			$providers = glob("$plug_root/Plugin/*/Plug.php",GLOB_MARK);
			
			$have_providers = (
			    is_array($providers)
                && !empty($providers)
            );

            // default to failed plugin lookup
            $message = strtr(__('No providers found. Please read the plugin implementation doc: !README'),array(
                '!README'=>'Plugins/Api/Lib/Plugin/README.plugin.md'
            ));

            $result = array(
                'data' => null,
                'status' => 404,
                'message' => $message,
            );

			if($have_providers){
			    foreach($providers as $provider){
			        // get the plugin provider name humanized so we can use it in a cakephp url
                    $plugin_name = basename(dirname($provider));
                    $plugin_name = Inflector::underscore($plugin_name);
			        $data[] = strtolower($plugin_name);
                }
                $message = __('Available Providers');
                $result = array(
                    'data' => array(
                        'providers'=>$data,
                        'render'=>array(
                            // the StatusHelper::process() render type that you want to use
                            'type'=>'index',
                            // each link will be passed to this element in the order that you specify a render element
                            'processors'=>array(
                                'provider_link'
                            ),
                        ),
                    ),
                    'status' => 200,
                    'message' => $message,
                );
            }



		}

		// $this->set(compact('status','message','data','html'));
		$this->set(compact('result'));
	}
	
}
