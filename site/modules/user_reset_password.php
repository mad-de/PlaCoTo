<?php

include "resources/email_functions.php";
include "resources/account_functions.php";
include "resources/cryptography_functions.php";

$time_to_reset = 60 * 30;

if(!isset($_GET["step"]))
{
	if(check_use_captcha())
	{ 
		$extra_scripts = "<script src='https://www.google.com/recaptcha/api.js'></script>"; 
		$captcha_html = '<div class="g-recaptcha" data-sitekey="' . $get_RECAPTCHA_PUBLIC_KEY() . '"></div>';
	}
$output_reset_password = <<< EOT
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Reset password</title>
	<link rel="stylesheet" href="css/user_general.css">
{$extra_scripts}
  </head>
  <body>
  <div id="content">
<form action="index.php?act=reset_password&step=submit" method="POST" autocomplete="on">
      <label for="identifier">Enter username or email:<br />
        <input id="identifier" name="identifier">
      </label>
{$captcha_html}
<div class="submit_div"><button type="submit">Submit</button></div>
</form>
</div>
EOT;
}

elseif($_GET["step"] == "submit")
{
	if(!isset($_POST["identifier"]) || (!isset($_POST['g-recaptcha-response']) && $check_use_captcha))
	{
		print '<h2>Please fill out the identifier and solve the provided captcha.</h2>';
	}
	else
	{
		check_captcha($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
		// Check for validity of submitted fields
		if(check_special_characters($_POST["identifier"], get_EMAIL_SPECIAL_CHARS()))
		{
			$error = "You used forbidden special chars.";
		}
		if($error == "")
		{
			// Check if user exists
			if(!(identifier_matches($_POST["identifier"])))
			{
				$error = "Email or Username not found.";
			}	
			else
			{
				$code_string = return_random_hexadecimal(20);
				$user_id = identifier_matches($_POST["identifier"]);
				if(insert_reset_password_code($user_id, $code_string))
				{
					$code_link = get_WEBSITE_URL() . 'index.php?act=reset_password&step=reset&code=' . $code_string;
					$email_output = 'Hello, a password reset was requested. If this has been you, please click <a href="' . $code_link . '">here</a> or paste this into your browsers adress bar: ' . $code_link . '<br /><br />This code will be valid for ' . ($time_to_reset / 60) . ' minutes.';
					if(send_email(fetch_student_by_id($user_id)->EMAIL, "Reset password", $email_output))
					{ $output_reset_password .= " Check your emails to reset your password. You have got " . ($time_to_reset / 60) . " minutes time to reset the password."; }
					else { $error = "I haven't been able to send the email."; }
				}
			}
		}
		if(!($error == ""))
		{ $output_reset_password = $error; }
	}
}

elseif($_GET["step"] == "reset")
{
	if(!isset($_GET["code"]))
	{
		print '<h2>No code was supplied.</h2>';
		// maybe add a form later for manual input...
	}
	else
	{
		if(check_special_characters($_GET["code"], array(' ')))
		{
			$error = "Forbidden special chars detected.";
		}
		if($error == "")
		{
			// Check if code exists in database
			$reset_password_codes = fetch_reset_password_codes();
			foreach ($reset_password_codes as $reset_password_code)
			{
				if($reset_password_code->CODE == $_GET["code"] && ($reset_password_code->TIMESTAMP > (time()-$time_to_reset)))
				{ $return_id = $reset_password_code->ID; }
				elseif (!($reset_password_code->CODE == $_GET["code"]) && $reset_password_code->TIMESTAMP > (time()-$time_to_reset))
				{
					$new_table[$reset_password_code->ID] = new pw_reset_table; 
					$new_table[$reset_password_code->ID] -> ID = $reset_password_code->ID;
					$new_table[$reset_password_code->ID] -> CODE = $reset_password_code->CODE;
					$new_table[$reset_password_code->ID] -> TIMESTAMP = $reset_password_code->TIMESTAMP;
				}
			}
			// clear cache from result and / or old items
			if(!(file_put_contents(get_DB_PATH() . DIRECTORY_SEPARATOR . 'pw_resets.json', json_encode($new_table))))
			{
				$error = "Updating code database did not work.";
			}
			
			if(empty($return_id))
			{
				$error = "Code invalid.";
			}	
			else
			{
				$student_table = fetch_json_table('students.json');
				if (!($student_table === FALSE))
				{
					foreach($student_table as &$this_table_student) 
					{
						if($this_table_student["ID"] == $return_id)
						{
							$new_password = return_random_chars(8);
							$this_table_student["PASSWORD"] = md5($new_password);
							if(file_put_contents(get_DB_PATH() . DIRECTORY_SEPARATOR . 'students.json', json_encode($student_table)))
							{
								$output_reset_password = "A new password has been sent to your email adress.";
								// Email stuff
								$message = 'Hello ' . $this_table_student["NAME"] . '<br /><br />Your new password is: '. $new_password . '<br />Your login (just in case you forgot) is: ' . $this_table_student["LOGIN"] . '<br /><a href="' . get_WEBSITE_URL() . '">Login right away</a>.';
								send_email($this_table_student["EMAIL"], "Your password has been reset" , $message);	
							}
							else { $error = "Updating the database did not work."; }
						}
					}
				}
			}
		}
		if(!($error == ""))
		{ $output_reset_password = $error; }
	}
}

$html_output = $output_reset_password;
?>