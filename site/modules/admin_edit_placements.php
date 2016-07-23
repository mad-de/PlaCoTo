<?php
include('resources/times.php');

if(!isset($_GET["step"]))
{
	$placements = fetch_placements($_GET["id"]);
	if(!empty($placements))
	{ 
		$output_edit_placements = 'Placements in Placement_list' . $_GET["id"] . '<br />';
	
		$output_edit_placements .= '<form action="admin.php?act=edit_placements&id=' . $_GET["id"] . '&step=submit" method="POST" autocomplete="off">';
		$output_edit_placements .= '<table><tr><th>ESSENTIAL</th><th>ID</th><th>NAME</th><th>DEPLOYMENT</th><th>LOCATION</th><th>BEGIN</th><th>END</th><th>MIN</th><th>MAX</th><th>DELETE</th></tr>';
		$count = 0;
		foreach($placements as $placement)
		{
			if($placement->ESSENTIAL === TRUE) { $this_essential = " checked=\"checked\""; } else { $this_essential = ""; }
			$begin = timestamp_to_german_date($placement->TIMEFRAME_BEGIN);
			$end = timestamp_to_german_date($placement->TIMEFRAME_END);
			$output_edit_placements .= <<< EOT
		  <tr><td><input type="checkbox" name="{$count}::essential"{$this_essential}></td>
		  <td><input id="name" name="{$count}::id" type="hidden" value="{$placement->ID}">{$placement->ID}</td>
	      <td><input id="name" name="{$count}::name" value="{$placement->NAME}"></td>
	      <td><input id="deployment" name="{$count}::deployment" value="{$placement->DEPLOYMENT}" size="9"></td>
	      <td><input id="deployment" name="{$count}::location" value="{$placement->LOCATION}" size="7"></td>
	      <td><input id="timeframe_begin" name="{$count}::timeframe_begin" value="{$begin}" size="7"></td>	 
	      <td><input id="timeframe_end" name="{$count}::timeframe_end" value="{$end}" size="7"></td>	 
	      <td><input id="places_min" name="{$count}::places_min" value="{$placement->PLACES_MIN}" size="2"></td>	 
	      <td><input id="places_max" name="{$count}::places_max" value="{$placement->PLACES_MAX}" size="2"></td>	 
		<td><a href="admin.php?act=edit_placements&step=delete_placement&id={$_GET["id"]}&delete_id={$placement->ID}" />Delete Placement</a></td> 
	</tr>
EOT;
	$count++;
	}
	$count--;
	$output_edit_placements .= <<< EOT
		<input id="name" name="total_num" type="hidden" value="{$count}"></table>
			<a href="admin.php?act=add_placements&id={$_GET["id"]}" />Add placements</a><br />
		   <button type="submit">Submit</button>
	</form>
EOT;
	}
	else
	{
		$output_edit_placements = 'No placements yet.<br /><a href="admin.php?act=add_placements&id=' . $_GET["id"] . '" />Add placements</a><br />';
	}
}
elseif($_GET["step"] == "submit")
{
	$old_placements = fetch_placements($_GET["id"]);

	for ($input_count = 0; $input_count <= $_POST["total_num"]; $input_count++) 
	{
		check_array_special_chars(array($_POST[$input_count . "::id"], $_POST[$input_count . "::name"], $_POST[$input_count . "::deployment"], $_POST[$input_count . "::location"], $_POST[$input_count . "::timeframe_begin"], $_POST[$input_count . "::timeframe_end"], $_POST[$input_count . "::places_min"], $_POST[$input_count . "::places_max"])) or die ("CRITICAL ERROR updating id " . $_POST[$input_count . "::id"]);
		$new_placements[$input_count] = new db_placements;
		$new_placements[$input_count]-> ID = $_POST[$input_count . "::id"];
		if(isset($_POST[$input_count . "::essential"])) { $new_placements[$input_count]-> ESSENTIAL = TRUE; } else { $new_placements[$input_count]-> ESSENTIAL = FALSE; }
		$new_placements[$input_count]-> NAME = $_POST[$input_count . "::name"];
		$new_placements[$input_count]-> DEPLOYMENT = $_POST[$input_count . "::deployment"];
		$new_placements[$input_count]-> LOCATION = $_POST[$input_count . "::location"];
		$new_placements[$input_count]-> TIMEFRAME_BEGIN = german_date_to_timestamp($_POST[$input_count . "::timeframe_begin"]);
		$new_placements[$input_count]-> TIMEFRAME_END = german_date_to_timestamp($_POST[$input_count . "::timeframe_end"]);
		$new_placements[$input_count]-> PLACES_MIN = $_POST[$input_count . "::places_min"];
		$new_placements[$input_count]-> PLACES_MAX = $_POST[$input_count . "::places_max"];
	}
	$output_edit_placements = json_encode($new_placements);

	file_put_contents(get_DB_PATH() . DIRECTORY_SEPARATOR . 'placement_' . $_GET["id"] . '.json', json_encode($new_placements));
}
elseif($_GET["step"] == "delete_placement")
{
	$old_placements = fetch_placements($_GET["id"]);
	$placement_count = 0;
	foreach($old_placements as $this_placement_table) 
	{
		if($this_placement_table->ID != $_GET["delete_id"])
		{
			$new_placements[$placement_count] = new db_placements;
			$new_placements[$placement_count]-> ID = $this_placement_table->ID;
			$new_placements[$placement_count]-> ESSENTIAL = $this_placement_table->ESSENTIAL;
			$new_placements[$placement_count]-> NAME = $this_placement_table->NAME;
			$new_placements[$placement_count]-> DEPLOYMENT = $this_placement_table->DEPLOYMENT;
			$new_placements[$placement_count]-> LOCATION = $this_placement_table->LOCATION;
			$new_placements[$placement_count]-> TIMEFRAME_BEGIN = $this_placement_table->TIMEFRAME_BEGIN;
			$new_placements[$placement_count]-> TIMEFRAME_END = $this_placement_table->TIMEFRAME_END;
			$new_placements[$placement_count]-> PLACES_MIN = $this_placement_table->PLACES_MIN;
			$new_placements[$placement_count]-> PLACES_MAX = $this_placement_table->PLACES_MAX;
			$placement_count++;
		}
	}

	file_put_contents(get_DB_PATH() . DIRECTORY_SEPARATOR . 'placement_' . $_GET["id"] . '.json', json_encode($new_placements));
	
	$output_edit_placements = 'Placement with ID' . $_GET["delete_id"] . ' deleted. <a href="admin.php?act=edit_placements&id=' . $_GET["id"] . '" />Back to the placements overview</a>';
}

$module_output = $output_edit_placements;
?>
