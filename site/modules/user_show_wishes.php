<?php

include "resources/times.php";

class placement
{
  var $name;
  public $timeframes = array();
  var $places_min;
  var $places_max;
  var $places_wish;
  var $essential;
}

$placements = fetch_placements($_GET["id"]);
$timeframes = calculate_timeframes($placements);
$deployments = fetch_placement_item($_GET["id"], "DEPLOYMENT");
$wishlist_table = fetch_json_table('wishlist_' . $_GET["id"] . '.json');

$module_output = '<div id="center_div"><div class="user_wishes">Total students enrolled: ' . count($wishlist_table) . '<br /><br />'; 

foreach($deployments as $deployment)
{
	$deployment_applications_count = 0;
	$module_output .= '<b>' . $deployment . '</b>';
	$placements_for_deployment = array();
	// count users having this deployment
	foreach($wishlist_table as $user)
	{
		if(array_key_exists($deployment, $user['DEPLOYMENTS']))
		{ $deployment_applications_count++; }
	}	
	foreach($placements as $placement)
	{
		// Generate place if not set yet;
		if(($placement->DEPLOYMENT == $deployment) && (!isset($placements_for_deployment[$placement->NAME])))
		{
			$placements_for_deployment[$placement->NAME] = new placement;	
			$placements_for_deployment[$placement->NAME]->name = $placement->NAME;	
			$placements_for_deployment[$placement->NAME]->essential = $placement->ESSENTIAL;	
			$placements_for_deployment[$placement->NAME]->timeframe[] = timestamp_to_german_date($placement->TIMEFRAME_BEGIN) . '-' . timestamp_to_german_date($placement->TIMEFRAME_END);
			$placements_for_deployment[$placement->NAME]->places_max = $placement->PLACES_MAX;
			$placements_for_deployment[$placement->NAME]->places_min = $placement->PLACES_MIN;
			$places_wish = 0;
			foreach($wishlist_table as $user)
			{
				// count wishes
				$counted = FALSE;
				foreach($priority_types as $key => $value)
				{
					if(!$counted && isset($user['DEPLOYMENTS'][$deployment]) && in_array($placement->NAME, $user['DEPLOYMENTS'][$deployment]))
					{
						$places_wish++;
						$counted = TRUE;
					}
				}
				// count joker
				if(!$counted && isset($user['DEPLOYMENTS'][$deployment][0]) && $user['DEPLOYMENTS'][$deployment][0] == $placement->ID)
				{
					$places_wish++;
				}
			}
			$placements_for_deployment[$placement->NAME]->places_wish = $places_wish;
		}
		elseif(($placement->DEPLOYMENT == $deployment) && (isset($placements_for_deployment[$placement->NAME])))
		{
			$placements_for_deployment[$placement->NAME]->places_max = $placements_for_deployment[$placement->NAME]->places_max + $placement->PLACES_MAX;
			$placements_for_deployment[$placement->NAME]->places_min = $placements_for_deployment[$placement->NAME]->places_min + $placement->PLACES_MIN;
			// count joker
			if(isset($user['DEPLOYMENTS'][$deployment][0]) && $user['DEPLOYMENTS'][$deployment][0] == $placement->ID)
			{
				$placements_for_deployment[$placement->NAME]->places_wish++;
			}
		}
	}
	$placements_output = "";
	$deployment_max_places = 0;
	foreach($placements_for_deployment as $this_placement)
	{
		$deployment_max_places = $deployment_max_places + $this_placement->places_max;
		if($this_placement->essential === TRUE) { $placements_output .= '<u>'; }
		$placements_output .= $this_placement->name . ' (' . $this_placement->places_wish . '/' . $this_placement->places_max . '[' . $this_placement->places_min . '])<br />';
		if($this_placement->essential === TRUE) { $placements_output .= '</u>'; }
	}
	$module_output .= '</b> (' . $deployment_applications_count . ' students / ' . $deployment_max_places . ' places)<br />';
	$module_output .= $placements_output .'<br />';	
}
$module_output .= '<br /><u>Essential placements</u><br />[Minimal places]</div></div>';
?>
