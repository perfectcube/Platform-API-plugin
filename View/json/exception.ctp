<?php
$this->set('success', false);
$code = Configure::read('ResponseObject')->statusCode();

if (Configure::read('debug') > 0) {
	$trace = $error->getTraceAsString();
	$trace = str_replace(WWW_ROOT, 'WWW_ROOT/', $trace);
	$trace = str_replace(CAKE, 'CAKE/', $trace);
	$trace = str_replace(APP, 'APP/', $trace);
	$trace = str_replace(ROOT, 'ROOT/', $trace);
	$trace = str_replace(realpath(WEBROOT_DIR), 'WEBROOT_DIR/', $trace);
}

$this->set('data', compact('code', 'name', 'trace'));
