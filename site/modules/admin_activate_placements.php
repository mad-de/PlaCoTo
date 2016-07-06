<?php

include "resources/times.php";
include "resources/email_functions.php";

// Activate placements
$module_output = "";
$emails_to_students = array();
$emails_to_students_counter = 0;
$placement_list = fetch_placement_list();
$triggered = FALSE;
foreach($placement_list as $placement)
{
	if($_GET["id"] == $placement->ID)
	{ 
		$placement->ACTIVE = 1; 
		$triggered = TRUE;
		$groups_active = explode(";", $placement->GROUPS);
		$placement_name = $placement->NAME;
		$placement_due_date = $placement->DUE_DATE;
		$link = 'http://' . $_SERVER['HTTP_HOST'] . '/index.php?act=enrol&id=' . $_GET["id"];
	}
}
if($triggered === TRUE)
{
	if(file_put_contents(get_DB_PATH() . DIRECTORY_SEPARATOR . 'placements.json', json_encode($placement_list)))
	{
		$module_output .= "placement activated. Preparing emails to Members";
		// EMAIL STUFF
		$students = fetch_json_table('students.json');
		foreach($students as $student)
		{
			if(in_array($student["GROUP"], $groups_active))
			{
				$emails_to_students[$emails_to_students_counter] = new email;
				$emails_to_students[$emails_to_students_counter]->receiver = $student["EMAIL"];
				$emails_to_students[$emails_to_students_counter]->topic = "Placement " . $placement_name . " is now open for enrolment";
				$emails_to_students[$emails_to_students_counter]->message = 'Hello ' . $student["NAME"] . ',<br /><br />There is a new placement you can enrol to: ' . $placement_name . ' Enroling closes at ' . timestamp_to_german_date($placement_due_date) . '.<br /><a href="' . $link . '">Enrol right away</a>';
				$emails_to_students_counter++;
			}			
		}
		if(add_emails($emails_to_students))
		{
			$module_output .= "<br /><br />" . count($emails_to_students) . " Emails saved for sending.<br />";
		}
		else
		{
			$module_output .= "<br />Could not save Emails saved for sending.<br />";
		}	
	}
	else
	{
		$module_output .= "something failed.";
	}
}
?>
