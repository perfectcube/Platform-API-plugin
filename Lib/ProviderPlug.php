<?php 

/**
* Description
*/
class ProviderPlug extends Object{

    protected $log = array();

	// $label is a string that you want to show with the value
    // $event is a keyed array that you want to append log entriy label=>values to. this can be arbitrarily deep
    protected function logEvent($label,array $event){
        // push the entry on the stack
        $this->log[$label] = $event;
    }
    // $to is an array that plugin result entries are bogin pushed into
    protected function appendLog(array &$to){
        $to['log'] = $this->log;
    }

}