<?php
// BASIC AUTH from database file

// check login
function check_user_login($allow_user)
{
	$user_table = fetch_json_table('students.json');
	if ($user_table === FALSE)
	{
		print "problem with the database";
		exit;
	}
	else
	{		
		$users = array();
		foreach($user_table as $user_result) 
		{
			// only load admins if allow_user is set to false
			if(($user_result["STATUS"] == "ADMIN") || ($user_result["STATUS"] == "USER" && $allow_user === TRUE ) )
			{
			$users[$user_result["LOGIN"]] = $user_result["PASSWORD"];
			}
		}
		if(!isset($_SERVER['PHP_AUTH_USER'])) 
		{
			header("WWW-Authenticate: Basic realm=\"Private Area\"");
			header("HTTP/1.0 401 Unauthorized");
			print "<br /><b>ERROR:</b> Protected area. You need a username and password.";
			exit;
		} 
		else 
		{
			if(!((array_key_exists($_SERVER['PHP_AUTH_USER'], $users)) && (md5($_SERVER['PHP_AUTH_PW']) == $users[$_SERVER['PHP_AUTH_USER']]))) 
			{
				header("WWW-Authenticate: Basic realm=\"Private Area\"");
				header("HTTP/1.0 401 Unauthorized");
				print "<br /><b>ERROR:</b> Password or username ist wrong, or you are not an admin.";
				exit;
			}
		}
	}
}

?>
