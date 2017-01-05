<?php

$module_output = 'I am trying to enrol you for deployment ' . $_GET["id"] . '...<br />';

$this_student = fetch_student_by_login($_SERVER['PHP_AUTH_USER']);
if(!($this_student === FALSE))
{
	if(check_for_placement_validity($_GET["id"], $this_student->GROUP))
	{
		$deployments = fetch_placement_item($_GET["id"], "DEPLOYMENT");
		if(insert_new_wishes($this_student->ID, $_GET["id"], $deployments))
		{
			$module_output .= "You are now enrolled. ";		}	
		else
		{
			$module_output .= "You are already enrolled.";
		}	
		$module_output .= '<br />Go on to select your details: <a href="index.php?act=edit_choices&id=' . $_GET["id"] . '">Set priorities and unavailable dates</a>.';
	}
	else
	{
		$module_output .= "This placement doesn`t exist or you are not part of the group.";
	}
}
else
{
	$module_output .= "Your login doesn`t seem to be valid";
}


?>
