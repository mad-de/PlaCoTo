<?php

include "resources/email_functions.php";

$student_table = fetch_json_table('students.json');
$success = FALSE;
if (!($student_table === FALSE))
{
	foreach($student_table as &$this_table_student) 
	{
		if($this_table_student["ID"] == $_GET["id"])
		{
			if(!($this_table_student["STATUS"] == "USER"))
			{
				$this_table_student["STATUS"] = "USER";
				if(file_put_contents(get_DB_PATH() . DIRECTORY_SEPARATOR . 'students.json', json_encode($student_table)))
				{
					$module_output = "User succesfully activated.";
					// Email stuff
					$message = 'Hello ' . $this_table_student["NAME"] . '<br /><br />Welcome to the placement coordination tool.<br /> You can begin checking for placements right away. Just <a href="http://' . $_SERVER['HTTP_HOST'] . '">Login</a>';
					send_email($this_table_student["EMAIL"], "Your account was activated" , $message);	
					$success = TRUE;
				}
				else { $module_output = "Updating the database did not work."; }
			}
			else { $module_output = "User has already been activated."; $success = TRUE; }
		}
	}
}

if(!$success)
{
	$module_output = "There was an error activating the user. The user was not activated.";
}

?>
