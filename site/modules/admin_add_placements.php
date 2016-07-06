<?php
include "resources/times.php";
$output_add_placements = "";

function create_dropdowns($id, $field, $field_descr)
{
	$dropdown = "<label for=\"location\">" . $field_descr . ":<select name=\"" . $field_descr . "\">";
	$items = fetch_placement_item($id, $field);
	foreach ($items as $item)
	{
		$dropdown .= '<option value="' . $item . '">' . $item . '</option>';
	}
	$dropdown .= '<option value="OTHER">OTHER (write:)</option></select></label>';
	$dropdown .= '<label for="other_' . $field_descr . '"><input id="name" name="other_' . $field_descr . '" value=""></label>';
	return $dropdown;
}

if(isset($_GET["id"]))
{
	if(isset($_GET["submit"]))
	{
		if((!is_numeric($_POST["places_min"]) && !empty($_POST["places_min"])) || (!is_numeric($_POST["places_max"]) && !empty($_POST["places_max"])))
		{ die("Places min / max is not a number"); }
		$output_add_placements .= 'Placement succesfully added. You can go to the <a href="admin.php?act=edit_placements&id=' . $_GET["id"] . '" />Placements overview</a> or add another one:<br /><br />';
		if(!(isset($_POST["timeframe"])) || $_POST["timeframe"] == "OTHER")
		{
			if(!is_valid_date($_POST["begin"]) || !is_valid_date($_POST["end"])) { die("Dates are not valid."); }
			$placement_begin = german_date_to_timestamp($_POST["begin"]);
			$placement_end = german_date_to_timestamp($_POST["end"]);
		}
		else
		{
			$timeframe_explode = array();
			$timeframe_explode = explode('::', $_POST["timeframe"]);
			$placement_begin = $timeframe_explode[0];
			$placement_end = $timeframe_explode[1];
		}

		if($_POST["deployment"] == "OTHER") 
		{
			check_post_special_chars($_POST["other_deployment"]) or die("Error updating the deployment");
			$submitted_deployment = $_POST["other_deployment"]; 
		}
		else { $submitted_deployment = $_POST["deployment"]; }
		if($_POST["name"] == "OTHER") 
		{ 
			check_post_special_chars($_POST["other_name"]) or die("Error updating the name");	
			$submitted_name = $_POST["other_name"]; 
		}
		else { $submitted_name = $_POST["name"]; }
		if($_POST["location"] == "OTHER") 
		{ 
			check_post_special_chars($_POST["other_location"]) or die("Error updating the location");		
			$submitted_location = $_POST["other_location"]; 
		}
		else { $submitted_location = $_POST["location"]; }

		insert_new_placement($_GET["id"], $submitted_name, $submitted_deployment, $submitted_location, $placement_begin, $placement_end, $_POST["places_min"], $_POST["places_max"]);
	}
	
	$placements = fetch_placements($_GET["id"]);
	if(!empty($placements))
	{
		// CREATE dropdowns from strings
		$deployment = create_dropdowns($_GET["id"], "DEPLOYMENT", "deployment");
		$name = create_dropdowns($_GET["id"], "NAME", "name");
		$locations = create_dropdowns($_GET["id"], "LOCATION", "location");
		
		// CREATE Timeframe dropdown
		$timeframes = calculate_timeframes($placements);
		$timeframe_dropdown = "<label for=\"timeframe\">Timeframe:<select name=\"timeframe\">";
		foreach($timeframes as $this_timeframe)
		{
				$timeframe_dropdown .= '<option value="' . german_date_to_timestamp($this_timeframe->begin) . '::' . german_date_to_timestamp($this_timeframe->end) . '">'. $this_timeframe->begin . '-' . $this_timeframe->end . '</option>';
		}
		$timeframe_dropdown .= '<option value="OTHER">OTHER (write:)</option></select></label>';
	}
	else
	{
		$name = '<label for="name">Name: <input id="name" name="name" value=""></label>';
		$locations = '<label for="location">Location: <input id="name" name="location" value=""></label>';
		$deployment = <<< EOT
		<label for="end">Deployment:
        <input id="name" name="deployment" value="">
      </label>
EOT;
	$timeframe_dropdown = "";
	}

	$output_add_placements .= <<< EOT
	Enter details for clinic placement:
	<form action="admin.php?act=add_placements&submit=TRUE&id={$_GET["id"]}" method="POST" autocomplete="off">
	{$name}
	{$deployment}
	{$locations}
	{$timeframe_dropdown}
      <label for="begin">Begin:
        <input id="name" name="begin" value="">
      </label>
      <label for="end">End:
        <input id="name" name="end" value="">
      </label>
      <label for="end">Places (min):
        <input id="name" name="places_min" value="">
      </label>	  
      <label for="end">Places (max):
        <input id="name" name="places_max" value="">
      </label>	  	  
<br />
	   <button type="submit">Submit</button>
</form>
EOT;
}
$module_output = $output_add_placements;
?>
