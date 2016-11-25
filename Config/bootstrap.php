<?php
// Empty for now

$HERE = App::path('Plugin', 'Api');

App::build(array(
    'ApiPlug' => array('%s' . $HERE.'/Lib/Plugin' . DS)
), App::REGISTER);
