<?php

include "resources/times.php";
include "resources/email_functions.php";

// USE A "TWO FACTOR" SYSTEM TO VALIDIFY USERS PLACE. REQUIRE USER TO BE ENROLLED TO CHECK HIS ELIGABILITY THERE AND CHECK USERS GROUP PERMISSIONS AS WELL

$module_output = '';
$deployment_output = "<b>Select your deployments:</b><br />";
$priorities_output = '';
$location_key_num = count($priority_types) + 1;
$joker_key_num = 0;

$this_student = fetch_student_by_login($_SERVER['PHP_AUTH_USER']);

if(check_for_placement_validity($_GET["id"], $this_student->GROUP))
{	
	$placements = fetch_placements($_GET["id"]);
	$timeframes = calculate_timeframes($placements);
	$deployments = fetch_placement_item($_GET["id"], "DEPLOYMENT");
	$user_wishes = fetch_user_wishes($this_student->ID, $_GET["id"]);
	if(!($user_wishes === FALSE))
	{
		if(isset($_GET["submit"]))
		{
			// UPDATE TIMEFRAMES
			$collect_timeframes_unavailable = array();
			$email_output = "Hello " . $this_student->NAME . ",<br />Your updated wishes look like this: <br /><br />";
			for($timeframe_count = 1; $timeframe_count <= $_POST["timeframe_count"]; $timeframe_count++)
			{
				if(isset($_POST["TIMEFRAME_UNAVAILABLE_" . $timeframe_count]))
				{
					check_post_special_chars($_POST["TIMEFRAME_UNAVAILABLE_" . $timeframe_count]) or die("Critical error reading the timeframe input");
					$collect_timeframes_unavailable[$timeframe_count] = $_POST["TIMEFRAME_UNAVAILABLE_" . $timeframe_count];
				}	
			}
			$submit_timeframes_unavailable = array();
			$submit_timeframes_unavailable_count = 0;
			$email_output .= "Unavailable timeframes:<br />";
			foreach($timeframes as $this_timeframe)
			{
				if(!(in_array(german_date_to_timestamp($this_timeframe->begin) . '::' . german_date_to_timestamp($this_timeframe->end), $collect_timeframes_unavailable)))	
				{
					$submit_timeframes_unavailable[$submit_timeframes_unavailable_count] = german_date_to_timestamp($this_timeframe->begin) . '::' . german_date_to_timestamp($this_timeframe->end);
					$submit_timeframes_unavailable_count++;
					$email_output .= $this_timeframe->begin . " - " . $this_timeframe->end . " <br />";
				}
			}
			// Update custom timeframe
			// Check if dates are valid
			$submit_custom_timeframe_unavailable = "";
			if(!(empty($_POST['custom_timeframe_begin']) && empty($_POST['custom_timeframe_end'])))
			{
				if(is_valid_date($_POST['custom_timeframe_begin']) && is_valid_date($_POST['custom_timeframe_end']) ) 
				{
					$date_difference = floor((german_date_to_timestamp($_POST['custom_timeframe_end']) - german_date_to_timestamp($_POST['custom_timeframe_begin'])) / (60*60*24));
					if($date_difference <= get_COSTUM_TIMEFRAME_MAX() && $date_difference >= 1)
					{ 
						$submit_custom_timeframe_unavailable = german_date_to_timestamp($_POST['custom_timeframe_begin']) . '::' . german_date_to_timestamp($_POST['custom_timeframe_end']);
						$email_output .= $_POST['custom_timeframe_begin'] . " - " . $_POST['custom_timeframe_end'] . "<br />";
					}
					else
					{ 
						$module_output .= ' Your costum date difference is negative or greater than ' . get_COSTUM_TIMEFRAME_MAX() . ' days.<br />'; }	
					}
				else
				{
					$module_output .= ' Your costum date timeframe is not valid.<br />';	
				}
			}
			// Update deployments
			$submitted_deployments = array();
			foreach($deployments as $deployment)
			{
				if(isset($_POST['deploy_' . $deployment]))
				{
					check_post_special_chars($_POST['deploy_' . $deployment]) or die("Critical error reading the deployment input");
					$email_output .= "<br />You are enrolled for deployment " . $deployment . ". Your wishes:<br />";
					$submitted_deployments[$deployment] = new stdClass();
					// JOKER
					if(isset($_POST[$deployment . '::joker']))
					{
						check_post_special_chars($_POST[$deployment . '::joker']) or die("Critical error reading the joker input");
						$submitted_deployments[$deployment]->$joker_key_num = $_POST[$deployment . '::joker'];
						$joker_placement = fetch_placement_by_id($_GET["id"], $_POST[$deployment . '::joker']);
						$email_output .= "You used a Joker for " . $joker_placement->NAME . " (" . timestamp_to_german_date($joker_placement->TIMEFRAME_BEGIN) . " - " . timestamp_to_german_date($joker_placement->TIMEFRAME_END) . ")<br />";
					}
					foreach($priority_types as $key => $value)
					{
						check_post_special_chars($_POST[$key .  '::' . $deployment]) or die("Critical error reading the " . $value . " priorities input");						
						if(!empty($_POST[$key .  '::' . $deployment])) { $email_output .= "Your " . $value . " is " . $_POST[$key .  '::' . $deployment] . "<br />"; }
						$submitted_deployments[$deployment]->$key = $_POST[$key .  '::' . $deployment];	
					}
					// Locations
					check_post_special_chars($_POST[$location_key_num . '::' . $deployment . '::location']) or die("Critical error reading the joker input");
					$submitted_deployments[$deployment]->$location_key_num = $_POST[$location_key_num . '::' . $deployment . '::location'];
					if(!empty($_POST[$location_key_num . '::' . $deployment . '::location'])) { $email_output .= "Your preferred locations is " . $_POST[$location_key_num . '::' . $deployment . '::location'] . "<br />"; }
				}
			}
			insert_wishes($this_student->ID, $_GET["id"], $submitted_deployments, $submit_custom_timeframe_unavailable,  $submit_timeframes_unavailable);
			$module_output .= "Your wishlist is updated.";
			// send Email
			if(send_email($this_student->EMAIL, "Your wishlist has been updated", $email_output))
			{ $module_output .= " And an email has been sent to you for confirmation.<br /><br />"; }
			else { $module_output .= " Email could not been send."; }
			$module_output .= "<br /><br />" . str_replace("<br />", "<br />", $email_output);
		}
		// Choices Output 
		else
		{
			$module_output .= '<div class="launch_wishes"><a href="#" onClick="MyWindow=window.open(' . "'http://"  . $_SERVER['HTTP_HOST'] . '/index.php?act=show_wishes&id=' . $_GET["id"] . "','MyWindow',width=350,height=500); return false;" . '">See total wishes</a></div>';
			// Check if timestamp is already unavailable
			$timeframe_count = 1;
			$timeframes_output = "<b>Timeframes (unselect unavaliable ones):</b><br />";
			foreach($timeframes as $timeframe)
			{
				if(!(empty($user_wishes->TIMEFRAMES_UNAVAILABLE)) && in_array(german_date_to_timestamp($timeframe->begin) . '::' . german_date_to_timestamp($timeframe->end), $user_wishes->TIMEFRAMES_UNAVAILABLE))
				{
					$timeframes_checked = '';
				}
				else
				{
					$timeframes_checked = 'checked="checked"';
				}
				$timeframes_output .= '<input type="checkbox" name="TIMEFRAME_UNAVAILABLE_' . $timeframe_count . '" value="'. german_date_to_timestamp($timeframe->begin) . '::' . german_date_to_timestamp($timeframe->end) . '" ' . $timeframes_checked . '>' . $timeframe->begin . ' until ' . $timeframe->end . '<br />';
				$timeframe_count++;
			}
			$timeframe_count--;
			$timeframes_output .= '<input type="hidden" name="timeframe_count" value="' . $timeframe_count . '">';

			if(!(empty($user_wishes->CUSTOM_TIMEFRAME_UNAVAILABLE)))
			{
				$exploded_custom_timeframe = explode("::", $user_wishes->CUSTOM_TIMEFRAME_UNAVAILABLE);
				$costum_timeframe_begin = timestamp_to_german_date($exploded_custom_timeframe[0]);
				$costum_timeframe_end = timestamp_to_german_date($exploded_custom_timeframe[1]);
			}
			else
			{
				$costum_timeframe_begin = '';
				$costum_timeframe_end = '';	
			}
				
			$custom_timeframe = '<br /><label for="custom_timeframe_begin"><b>Custom unavailable timeframe (max ' . get_COSTUM_TIMEFRAME_MAX() . ' days):</b><br /><input id="custom_timeframe_begin" name="custom_timeframe_begin" size="7" value="' . $costum_timeframe_begin . '"> - <input id="custom_timeframe_end" name="custom_timeframe_end" size="7" value="' . $costum_timeframe_end . '"></label><br /><br />';

			// DEPLOYMENTS
			foreach($deployments as $deployment)
			{
				// Checkboxes for deployments
				if(array_key_exists($deployment, $user_wishes->DEPLOYMENTS) || in_array($deployment, $user_wishes->DEPLOYMENTS))
				{ $checked = 'checked="checked"'; }
				else { $checked = ""; }
				$deployment_output .= '<input type="checkbox" name="deploy_' . $deployment . '" value="TRUE" ' . $checked . '>' . $deployment . '<br />';
				$priorities_output .= '<br /><b>' . $deployment . '</b>';

				// JOKER
				if(!($this_student->JOKER == 0))
				{
					$joker_options =  '<label for="joker">JOKER (use wisely): <select name="' . $deployment . '::joker"  style="color:red"><option value="">NONE</option>';
					foreach($placements as $placement)
					{
						if($placement->DEPLOYMENT == $deployment)
						{
							$this_selected = '';
							foreach($user_wishes->DEPLOYMENTS as $deployment_keys => $deployment_values)
							{
								if(isset($deployment_values[0]) && (($deployment_keys == $deployment) && ($placement->ID == $deployment_values[0])))
									{
										$this_selected = ' selected';
									}	
							}
						$joker_options .=  '<option value="' . $placement->ID . '"' . $this_selected . '>' . $placement->NAME . '(' . timestamp_to_german_date($placement->TIMEFRAME_BEGIN) . '-' . timestamp_to_german_date($placement->TIMEFRAME_END) . ')</option>';
						}
					}
					$joker_options .=  '</select></label>';
					$priorities_output .= '<br />' . $joker_options;
				}
				// Priorities
				foreach($priority_types as $key => $value)
				{
				$used_placements = array();
				$used_placements_count = 0;
				$deployment_options = '<option value="" >NONE</option>';
					foreach($placements as $placement)
					{
						if($placement->DEPLOYMENT == $deployment && !(in_array($placement->NAME, $used_placements)))
						{
							$used_placements[$used_placements_count] = $placement->NAME;
							$this_selected = '';
							foreach($user_wishes->DEPLOYMENTS as $deployment_keys => $deployment_values)
							{
								if(($deployment_keys == $deployment) && ($placement->NAME == $deployment_values[$key]))
									{
										$this_selected = ' selected';
									}	
							}
							$deployment_options .=  '<option value="' . $placement->NAME . '"' . $this_selected . '>' . $placement->NAME . '</option>';
							$used_placements_count++;	
						}
					}
					$priorities_output .= '<br />' . $value . ': <select name="' . $key . '::' . $deployment . '">' . $deployment_options . '</select>';
				}
				// LOCATION
				$location_options =  '<label for="location">Location preference: <select name="' . $location_key_num . '::' . $deployment . '::location"><option value="" >NONE</option>';
				$locations = fetch_placement_item($_GET["id"], "LOCATION");
				//create array to register what locations we already have in our list
				$used_locations = array();
				$used_locations_count = 0;
				foreach($placements as $placement)
				{
					if($placement->DEPLOYMENT == $deployment && !(in_array($placement->LOCATION, $used_locations)))
					{
						$used_locations[$used_locations_count] = $placement->LOCATION;
						$this_selected = '';

						foreach($user_wishes->DEPLOYMENTS as $deployment_keys => $deployment_values)
						{
							if((isset($deployment_values[4])) && ($deployment_keys == $deployment) && ($placement->LOCATION == $deployment_values[4]))
								{
									$this_selected = ' selected';
								}	
						}
					$location_options .=  '<option value="' . $placement->LOCATION . '"' . $this_selected . '>' . $placement->LOCATION . '</option>';
					$used_locations_count++;
					}
				}
				$location_options .=  '</select></label>';

				$priorities_output .= '<br />' . $location_options . '<br />';
			}
			$module_output .= '<form action="index.php?act=edit_choices&id=' . $_GET["id"] . '&submit=TRUE" method="POST" autocomplete="off">';
			$module_output .= $timeframes_output . $custom_timeframe . $deployment_output . $priorities_output . '<div class="submit_div"><button type="submit">Submit</button></div>';
		}
	}
	else
	{
		$module_output .= '<b>You are not enrolled. Please <a href="index.php?act=enrol&id=' . $_GET["id"] . '">ENROL</a> first.</b><br />';
	}
}
else
{
	$module_output .= '<b>Invalid ID or you are not in the right group for this placement.</b><br />';	
}
?>
