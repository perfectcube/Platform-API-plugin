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

	// how many levels has the arrayToTable renderer recursed? we us this to only render a caption if the table has rendered one level deep or more
	private $render_flags = array(
		'arraytotable'=>array(
			'recursion'=>false,
			'level'=>1
		)
	);

	public function check($provider){
		$data = array();
		$status = 500;
		$view = '';
		$message = '';

		$plug_root = dirname(dirname(__FILE__)).'/Lib';
		// try and load the providers plugin
		$class = Inflector::classify($provider);
		$file = 'Plugin/'.$class.'/Plug.php';
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
		$message = __('API Check for %s failed',$class);
		if($have_file){
			require_once($plugin_path);
			// Does the provider plugin exist?
			$have_provider_plug = class_exists($class);
			if($have_provider_plug){
				$Plugin = new $class();
				$can_check = method_exists($Plugin,'check');
				if($can_check){
					
					$data = $Plugin->check();
					//FIXME: wire in Api/Lib/Plugin/{PlugName}/View here
					$view = $this->arrayToTable($data);
					$status = 200;
					$message = __('%s API Report',$class);
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
			'view' => $view,
			'message' => $message,
		);
		
			// does the providers plugin impliment the check() method?
				// call $Provider->check(); the plugin takes care of its own arguments
		// $this->set(compact('status','message','data','html'));
		$this->set(compact('result'));
	}

	private function arrayToTable($data,$options=array()){
		
		// $mssage = __('running StatusController::arrayToTable() with value %s',print_r($data,true));
		// CakeLog::write('error',$mssage);

		$options += array(
			'title' => '', // set this to have a table caption
			'row_label'=>'th'
		);
		$html = '';
		$can_render_table = (
			is_array($data) 
			&& !empty($data)
		) ? true : false;
			
		if($can_render_table){
			// start table tag markup
			$html .= '<table  cellpadding="0" cellspacing="0" class="table table-striped">';
			// do we need to add a caption?
			$have_caption = (!empty($options['title'])) ? true : false;
			$need_caption = ($this->render_flags['arraytotable']['recursion'] === true) ? true : false;
			if($have_caption && $need_caption){
				$html .= sprintf('<caption class="small">%s</caption>',$options['title']);
			}
			
			foreach($data as $label => $value){
				$is_data = is_array($value);
				// if the value is itself an array then decend into it and create a table
				// $mssage = __('StatusController::arrayToTable() rendering with value %s',print_r(array(
				// 	'$data'=>$data,
				// 	'$label'=>$label,
				// 	'$value'=>$value,
				// ),true));
				// CakeLog::write('error',$mssage);
				// do we need to recures into the $data? override value if we do
				if ($is_data) {
					$this->render_flags['arraytotable']['level'] += 1;
					$this->render_flags['arraytotable']['recursion'] = true;
					$value = $this->arrayToTable($value,array(
						'title'=>$label,
						'row_label' => 'th',
					));
				}else{
					//FIXME: this is not setting the proper colspan level. instead we need to pass down a colspan level or autodetects a recursion level based on autodetecting $this->render_flags[$label][$value][$label][$value] depth
					$this->render_flags['arraytotable']['level'] = 1;
				}
				$need_label = !$is_data;
				
				$colspan = '';
				// FIXME: remove || true debuggong statement
				$need_colspan = ($this->render_flags['arraytotable']['level'] > 0 || true) ? true : false;
				if($need_colspan){
					$colspan = sprintf(' colspan="%s"',$this->render_flags['arraytotable']['level']);
				}
				
				$html .= strtr("<tr>\n\t!label\n\t<td!colspan>!value</td>\n</tr>",array(
					'!label'=>($need_label) ? strtr('<!type!colspan>!label</!type>',array(
						'!type'=>$options['row_label'],
						'!label'=>$label,
						'!colspan'=>$colspan
					)) : '',
					'!value'=>$value,
					'!colspan'=>$colspan,
				));
			}
			// finish table tag markup
			$html .= '</table>';
		}
		return $html;
	}
	
}
