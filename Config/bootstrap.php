<?php
// Empty for now

$HERE = dirname(__FILE__);
App::build(array(
    'ApiPlug' => array($HERE.'/Lib/Plugin' . DS)
), App::REGISTER);
