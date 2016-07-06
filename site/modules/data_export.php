<?php

include "resources/times.php";

function sort_by_deployment($ind_a, $ind_b)
{
	return strcmp($ind_a["deployment"], $ind_b["deployment"]);
}

if(is_dir(get_DB_PATH() . DIRECTORY_SEPARATOR . 'calculation_' . $_GET["id"] . DIRECTORY_SEPARATOR))
{
	// filename for download
	$filename = "table_" . $_GET["id"] . ".xls";

	$xls_output = header("Content-Disposition: attachment; filename=\"$filename\"");
	$xls_output .= header("Content-Type: application/vnd.ms-excel");

	$fetch_calculation = fetch_json_table(get_DB_PATH() . DIRECTORY_SEPARATOR . 'calculation_' . $_GET["id"] . DIRECTORY_SEPARATOR . "placements" . '.json');
	
	// Check if there are conflicts to be resolved. If so, don`t print to users, admin only.
	if(!(empty($fetch_calculation["unallocated_min_places"])) || !(empty($fetch_calculation["unallocated_students"])))
	{ check_user_login(FALSE); }
	
	$calculation_table = $fetch_calculation["placements"];
	// sort by deployment
	usort($calculation_table, "sort_by_deployment");

	// Print deployments
	$deployment = "";
	foreach($calculation_table as $placements)
	{
		if (!($deployment == $placements["deployment"]))
		{
			$deployment = $placements["deployment"];
			$xls_output .= $placements["deployment"] . "\t";
		}
		else
		{
			$xls_output .= "\t";
		}
	}
	$xls_output .= "\r\n";

	// Print clinic names
	foreach($calculation_table as $placements)
	{
		$xls_output .= $placements["name"] . "\t";
	}
	$xls_output .= "\r\n";

	// Print clinic dates
	foreach($calculation_table as $placements)
	{
		$xls_output .= timestamp_to_german_date($placements["timeframe_begin"]) . " - " . timestamp_to_german_date($placements["timeframe_end"]) . "\t";
	}
	$xls_output .= "\r\n";

	// Print placement names
	for($i = 0; $i < 10; $i++)
	{
		foreach($calculation_table as $placements)
		{
			if(!(empty($placements["students_alloc"][$i])))
			{
				$xls_output .= $placements["students_alloc"][$i] . "\t";			
			}
			else
			{
				$xls_output .= "\t";
			}
		}
		$xls_output .= "\r\n";
	}

	print $xls_output;
}
else
{
	die("This file doesnt exist (yet).");
}
// Exit here so nothing else gets rendered.
exit;

?>