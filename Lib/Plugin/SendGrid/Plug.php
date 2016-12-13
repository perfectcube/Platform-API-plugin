<?php 

App::uses('ProviderPlug','Api.Lib');

/**
* Description
*/
class SendGridPlug extends ProviderPlug{
	
 	public function check($options=''){
		
		// sendgrid test
		$now = CakeTime::niceShort(time());
		$from = new SendGrid\Email("PerfectPlan Debugger", "debug@proof.perfectplan.io");
		$subject = sprintf("api/status/check/sendgrid test @ %s",$now);
		$to = new SendGrid\Email("Dan Bryant", "theperfectcube@gmail.com");
		$content = new SendGrid\Content("text/plain", "message body: Api.Status.Check Complete");
		$mail = new SendGrid\Mail($from, $subject, $to, $content);
		$apiKey = getenv('SENDGRID_API_KEY');
		$sg = new \SendGrid($apiKey);
		$response = $sg->client->mail()->send()->post($mail);
		$status = $response->statusCode();
		$headers = $response->headers();
		$response_body = $response->body();
		
		$info = array(
			'status' => $status,
			'headers'=> $headers,
			'response_headers' => $response_body,
		);

        return $info;
    }
}