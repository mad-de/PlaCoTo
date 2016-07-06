<?php

include "resources/times.php";

function check_for_empty_field ($field, $value)
{
	if(empty($value))
	{ return "Field " . $field . " can not be empty"; }
	else
	{ return FALSE; }
}

if(!isset($_GET["step"]))
{
	$group_options = "";
	$group_count = 0;
	$groups = fetch_groups();
	foreach($groups as $current_group)
	{
	$group_options .= '<input type="checkbox" name="' . $group_count . '::group" value="' . $current_group->ID . '" checked="checked">' . $current_group->NAME;
		$group_count++;
	}
	$group_count--;
	
$output_create_placement = <<< EOT
<form action="admin.php?act=create_placement&step=submit" method="POST" autocomplete="on">
      <label for="name">Name:
        <input id="name" name="name">
      </label>
   <label for="groups">Group:
      $group_options
  </label>
  <label for="due_date">Due Date:
	<input id="due_date" name="due_date">
  </label>
  <input type="hidden" name="group_count" value="{$group_count}">
	   <button type="submit">Submit</button>
</form>
EOT;
}
elseif($_GET["step"] == "submit")
{
	for ($group_count = 0; $group_count <= $_POST["group_count"]; $group_count++) 
	{
		$group_ids[$group_count] = $_POST[$group_count . "::group"];
	}
	$groups = implode( ";" , $group_ids);
	if(!isset($_POST["name"]))
	{
		$output_create_placement = '<h2>Please fill out all options</h2>';
	}
	else
	{
		// Check for validity of submitted fields
		$check_items = array( 'NAME' => $_POST["name"], 'GROUP' => $groups, 'DUE_DATE' => $_POST["due_date"] );
		foreach($check_items as $key_item => $value_item)
		{
			$check_item_empty = check_for_empty_field($key_item, $value_item);
			if(!($check_item_empty === FALSE)) { $error .= $check_item_empty;} 
		}
		is_valid_date($_POST["due_date"]) or $error .= "Date is not valid";
		$due_date = german_date_to_timestamp($_POST["due_date"]);
		if($error == "")
		{
			$rand_id = rand(10000, 99999);
			insert_new_placement_list($_POST["name"], $groups, $rand_id, $due_date);
			$output_create_placement = "Ok, " . $_POST["name"] . " with groups: " . $groups . " has been entered into our database. ";
			$output_create_placement .= "<a href=\"admin.php?act=add_placements&id={$rand_id}\" />Add placements</a>";
			}
		else
		{
			$output_create_placement = $error; 
		}
	}
}
$module_output = $output_create_placement;
?>
