<?php
// Empty for now

$HERE = dirname(__FILE__);
App::build(array(
    'PluginApi' => array(
		$HERE.'/Lib/Plugin/',
		$HERE.'/Lib/'	
	)
), App::REGISTER);
