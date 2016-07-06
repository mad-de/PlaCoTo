<?php
include('resources/times.php');

if(!isset($_GET["step"]))
{
$placement_list = fetch_placement_list();
if(!empty($placement_list))
{ 
	$output_edit_placements = '<form action="admin.php?act=edit_placement_list&step=submit" method="POST" autocomplete="off">';
	$count = 0;
	$groups = fetch_groups();
	foreach($placement_list as $placement)
	{
	$group_options = "";
	$group_count = 0;
	foreach($groups as $current_group)
	{
		if(!(strpos($placement->GROUPS, $current_group->ID) === FALSE))
		{ 
			$group_options .= '<input type="checkbox" name="' . $count . '::' . $group_count . '::group" value="' . $current_group->ID . '" checked="checked">' . $current_group->NAME;
		}
		else
		{ 
			$group_options .= '<input type="checkbox" name="' . $count . '::' . $group_count . '::group" value="' . $current_group->ID . '">' . $current_group->NAME;
		}
		$group_count++;
	}
	$due_date = timestamp_to_german_date($placement->DUE_DATE);
	if($placement->ACTIVE == 0)
	{ 
		$activate_button = '<a href="admin.php?act=activate_placements&id=' . $placement->ID . '" />Activate Placement</a>; '; 
	}
	else
	{
		$activate_button = '<input id="name" name="' . $count . '::activated" type="hidden" value="TRUE">';
	}
	$output_edit_placements .= <<< EOT
		<input id="name" name="{$count}::id" type="hidden" value="{$placement->ID}">
	      <label for="name">ID: {$placement->ID} Name:
		<input id="name" name="{$count}::name" value="{$placement->NAME}">
	      </label>
	$group_options
		      <label for="name">Due Date:
		<input id="name" name="{$count}::due_date" value="{$due_date}">
	      </label>
	{$activate_button}
	<a href="admin.php?act=edit_placement_list&step=delete_placement&id={$placement->ID}" />Delete Placement</a>;  
	<a href="admin.php?act=edit_placements&id={$placement->ID}" />Edit Placements</a>; 
	<br />
EOT;
	$count++;
	}
	$count--;
	$group_count--;
	$output_edit_placements .= <<< EOT
		<input id="name" name="total_num" type="hidden" value="{$count}">
		<input id="name" name="group_count" type="hidden" value="{$group_count}">
	<a href="admin.php?act=create_placement" />Create new placements</a><br />
		   <button type="submit">Submit</button>
	</form>
EOT;
	}
	else
	{
		$output_edit_placements = 'No placements in Database.<br /><a href="admin.php?act=create_placement" />Create new placements</a>';
	}
}
elseif($_GET["step"] == "submit")
{
$old_placements = fetch_placement_list();

for ($input_count = 0; $input_count <= $_POST["total_num"]; $input_count++) 
	{
		is_valid_date($_POST[$input_count . "::due_date"]) or die("Date for ID " . $_POST[$input_count . "::id"] . " is not valid");
		$new_placements_table[$input_count] = new db_placement_list;
	    	$new_placements_table[$input_count]-> ID = $_POST[$input_count . "::id"];
	    	$new_placements_table[$input_count]-> NAME = $_POST[$input_count . "::name"];
			if(isset($_POST[$input_count . "::activated"])) { $new_placements_table[$input_count]-> ACTIVE = 1; }
			else { $new_placements_table[$input_count]-> ACTIVE = 0; }
	    	$new_placements_table[$input_count]-> DUE_DATE = german_date_to_timestamp($_POST[$input_count . "::due_date"]);
		for ($group_count = 0; $group_count <= $_POST["group_count"]; $group_count++) 
		{
			if(isset($_POST[$input_count . "::" . $group_count . "::group"]))
			{ $group_ids[$group_count] = $_POST[$input_count . "::" . $group_count . "::group"]; }
			else
			{ $group_ids[$group_count] = ""; }
		}
		$new_placements_table[$input_count] -> GROUPS = implode( ";" , $group_ids);
	}
$output_edit_placements = json_encode($new_placements_table);

file_put_contents(get_DB_PATH() . DIRECTORY_SEPARATOR . 'placements.json', json_encode($new_placements_table));
}

elseif($_GET["step"] == "delete_placement")
{
	$placement_table = fetch_placement_list();
	$placement_count = 0;
	foreach($placement_table as $this_placement_table) 
	{
		if($this_placement_table->ID != $_GET["id"])
		{
			$new_placements_table[$placement_count] = new db_placement_list;
		    	$new_placements_table[$placement_count]-> ID = $this_placement_table->ID;
		    	$new_placements_table[$placement_count]-> NAME = $this_placement_table->NAME;
		    	$new_placements_table[$placement_count]-> GROUPS = $this_placement_table->GROUPS;
		    	$new_placements_table[$placement_count]-> DUE_DATE = $this_placement_table->DUE_DATE;
		    	$new_placements_table[$placement_count]-> ACTIVE = $this_placement_table->ACTIVE;
			$placement_count++;
		}
	}
file_put_contents(get_DB_PATH() . DIRECTORY_SEPARATOR . 'placements.json', json_encode($new_placements_table));
    if (file_exists(get_DB_PATH() . DIRECTORY_SEPARATOR . 'placement_' . $_GET["id"] . '.json'))
	{
	unlink(get_DB_PATH() . DIRECTORY_SEPARATOR . 'placement_' . $_GET["id"] . '.json');
	}
    if (file_exists(get_DB_PATH() . DIRECTORY_SEPARATOR . 'wishlist_' . $_GET["id"] . '.json'))
	{
	unlink(get_DB_PATH() . DIRECTORY_SEPARATOR . 'wishlist_' . $_GET["id"] . '.json');
	}
	$output_edit_placements = "Placement with ID" . $_GET["id"] . " deleted";
}

$module_output = $output_edit_placements;
?>
