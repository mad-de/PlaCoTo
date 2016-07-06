<?php

class email
{
	var $receiver;
	var $topic;
	var $message;
}

// email functions
function fetch_email_queue()
{
	$emails_table = fetch_json_table('emails.json');
	if (!($emails_table === FALSE))
	{
	$emails_count = 0;
		foreach($emails_table as $this_email) 
		{
			$emails[$emails_count] = new email;
			$emails[$emails_count] -> receiver = $this_email["receiver"];
			$emails[$emails_count] -> topic = $this_email["topic"];
			$emails[$emails_count] -> message = '<html><body>' . $this_email["message"] . '</body></html>';
			$emails_count++;
		}
		return $emails;
	}
}

function send_email($receiver, $topic, $message)
{
$header  = 'MIME-Version: 1.0' . "\r\n";
$header .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
$header .= 	'From: '. get_EMAIL_SENDER() . "\r\n" .
    'Reply-To: '. get_EMAIL_SENDER() . "\r\n";
return mail($receiver, $topic, $message, $header);
}

function send_admin_email($topic, $message)
{
	$user_table = fetch_json_table('students.json');
	foreach($user_table as $user_result) { if($user_result["STATUS"] == "ADMIN") { send_email($user_result["EMAIL"], $topic , $message); } }
}
function add_emails($array)
{
	$email_list = fetch_json_table("emails.json");
	if($email_list === FALSE) { $email_list = $array; }
	else 
	{ foreach($array as $email) { $email_list[] = $email; } }
	return file_put_contents(get_DB_PATH() . DIRECTORY_SEPARATOR . 'emails.json', json_encode($email_list));
}
?>