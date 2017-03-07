<?php

function check_use_captcha()
{
	$use_captcha = get_RECAPTCHA_SECRET_KEY();
	if(!empty($use_captcha))
	{ return TRUE; }
	else { return FALSE; }
}

function check_captcha($captcha, $ip)
{
	if(check_use_captcha())
	{ 
		$response=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".get_RECAPTCHA_SECRET_KEY()."&response=".$captcha."&remoteip=".$ip);
		$responseKeys = json_decode($response,true);
		if(intval($responseKeys["success"]) !== 1) 
		{
			print 'The script thinks that you are a spammer and we politely ask you to leave.';
			exit;
		} 
	}
}

?>