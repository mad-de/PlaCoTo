<?php
// Set Debugging Level
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

print date('d.m.Y-H:i:s:', time()) . ' Starting script.<br />';

include "config.php";
include "resources/json_functions.php";
include "resources/email_functions.php";

$emails_fetched = fetch_email_queue();
if(count($emails_fetched) > 0)
{
	print date('d.m.Y-H:i:s:', time()) . ' There are ' . count($emails_fetched) . ' mails in the sending queue.<br />';
	for($i = 1, $failed = 0; $i <= count($emails_fetched); $i++)
	{
		print date('d.m.Y-H:i:s:', time()) . ' Sending Email ' . $i;
		$current_email_queue = array();
		$current_email_queue = fetch_email_queue();
		// run tasks and skip failed emails
		if(send_email($current_email_queue[$failed]->receiver, $current_email_queue[$failed]->topic, $current_email_queue[$failed]->message))
		{ 
			unset($current_email_queue[$failed]);
			if(file_put_contents(get_DB_PATH() . DIRECTORY_SEPARATOR . 'emails.json', json_encode($current_email_queue)))
			{ print ' SUCCES!<br />'; }
			else
			{ die("Failed uploading the email queue after iteration " . $i); }
		}
		else
		{ $failed++; print ' FAILED :(<br />'; }
	}
}
else 
{ print date('d.m.Y-H:i:s:', time()) . ' Nothing to send.<br />'; }
	
print date('d.m.Y-H:i:s:', time()) . ' Script ended.';

?>