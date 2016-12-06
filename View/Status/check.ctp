<?php 

echo strtr('<h2>!title</h2><div id="api-provider-report">!markup</div>',array(
	'!title'=>$result['message'],
	'!markup'=>$result['view']
));