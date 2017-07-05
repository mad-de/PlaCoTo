<?php

include "resources/email_functions.php";
include "resources/account_functions.php";

function check_for_empty_field ($field, $value)
{
	if(empty($value))
	{
		return "Field " . $field . " can not be empty";
	}
	else
	{
		return FALSE;
	}
}


$extra_scripts = "";
$captcha_html = "";

if(!isset($_GET["step"]))
{
	$group_options = "";
	$groups = fetch_groups();
	foreach($groups as $current_group)
	{
		$group_options .= '<option value="' . $current_group->ID . '">' . $current_group->NAME . '</option>';
	}
	if(check_use_captcha())
	{ 
		$extra_scripts = "<script src='https://www.google.com/recaptcha/api.js'></script>"; 
		$captcha_html = '<div class="g-recaptcha" data-sitekey="' . $get_RECAPTCHA_PUBLIC_KEY() . '"></div>';
	}
$output_create_new_user = <<< EOT
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Registration</title>
	<link rel="stylesheet" href="css/user_general.css">
{$extra_scripts}
  </head>
  <body>
  <div id="content">
<form action="index.php?act=register_user&step=submit" method="POST" autocomplete="on">
      <label for="name">Name:<br />
        <input id="name" name="name">
      </label>
<br />
      <label for="login">Login:<br />
        <input id="name" name="login">
      </label>
<br />
	  <label for="passwort">Passwort:<br />
        <input id="name" name="password" type="password">
      </label>
<br />
      <label for="email">E-mail:<br />
        <input id="name" name="email">
      </label>
<br />
<label for="group">Group:<br />
    <select name="group">
      {$group_options}
    </select>
  </label>
{$captcha_html}
<div class="submit_div"><button type="submit">Submit</button></div>
</form>
</div>
EOT;
}
elseif($_GET["step"] == "submit")
{
	if(!isset($_POST["name"]) || !isset($_POST["login"]) || !isset($_POST["password"]) || !isset($_POST["email"]) || !isset($_POST["group"]) || (!isset($_POST['g-recaptcha-response']) && $check_use_captcha))
	{
		print '<h2>Please fill out all options and solve the provided captcha.</h2>';
	}
	else
	{
		check_captcha($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
		// Check for validity of submitted fields
		$check_items = array( 'NAME' => $_POST["name"], 'LOGIN' => $_POST["login"], 'PASSWORD' => $_POST["password"], 'EMAIL' => $_POST["email"], 'GROUP' => $_POST["group"] );
		foreach($check_items as $key_item => $value_item)
		{
			$check_item_empty = check_for_empty_field($key_item, $value_item);
			if((($key_item == 'NAME') && (check_special_characters($value_item, array(' ')))) || (($key_item == 'LOGIN') && (check_special_characters($value_item, array('.')))) || (($key_item == 'EMAIL') && (check_special_characters($value_item, get_EMAIL_SPECIAL_CHARS()))))
			{
				$error = "You used forbidden special chars in field ". $key_item;
			}
			if(!($check_item_empty === FALSE)) { $error .= $check_item_empty;} 
			else
			{
				if($key_item != "PASSWORD" && $key_item != "GROUP")
				{
					$check_name_for_duplicate = check_for_duplicate($key_item, $value_item, fetch_students());
					if(!($check_name_for_duplicate === FALSE)) { $error .= $check_name_for_duplicate;} 
				}
				if($key_item == "EMAIL")
				{
					if ((strpos($value_item, '@') == FALSE) || (strpos($value_item, '.') == FALSE)) { $error .= "The email adress doesn`t seem to be valid."; }
				}
			}
		}
		if($error == "")
		{
			$new_id = insert_new_student(utf8_encode($_POST["name"]), utf8_encode($_POST["login"]), md5($_POST["password"]), utf8_encode($_POST["email"]),  $_POST["group"]);
			if(!$new_id === FALSE)
			{
				$output_create_new_user = "Ok, " . $_POST["name"] . " your login " . $_POST["login"] . " has been entered into our database, but an administrator still has to verify it.";
				// send emails to admins
				$groups = fetch_groups();
				foreach($groups as $current_group) { if($current_group->ID  == $_POST["group"]) { $group_name = $current_group->NAME; } }
				$message = "Dear Admin,<br /><br />we have a new registration: <br />Name: " . $_POST["name"] . "<br />Email: " . $_POST["email"] . "<br />Group applied to: " . $group_name .  '<br /><br /><a href="' . get_WEBSITE_URL() . 'admin.php?act=activate_user&id=' . $new_id . '">Activate user</a>';
				send_admin_email("Please verify registration of user " . $_POST["name"] , $message);
			}
			else
			{
				$output_create_new_user = "Error updating the database. Please contact an administrator.";
			}
		}
		else
		{
			$output_create_new_user = $error; 
		}
	}
}
$html_output = $output_create_new_user;
?>
