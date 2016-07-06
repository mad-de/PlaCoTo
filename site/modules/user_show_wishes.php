<?php

include "resources/times.php";

class placement
{
  var $name;
  public $timeframes = array();
  var $places_max;
  var $places_wish;
}

$placements = fetch_placements($_GET["id"]);
$timeframes = calculate_timeframes($placements);
$deployments = fetch_placement_item($_GET["id"], "DEPLOYMENT");
$wishlist_table = fetch_json_table('wishlist_' . $_GET["id"] . '.json');

$module_output = 'Total students competing: ' . count($wishlist_table) . '<br /><br />'; 

foreach($deployments as $deployment)
{
	$module_output .= '<b>' . $deployment . '</b><br />';
	$placements_for_deployment = array();
	foreach($placements as $placement)
	{
		// Generate place if not set yet;
		if(($placement->DEPLOYMENT == $deployment) && (!isset($placements_for_deployment[$placement->NAME])))
		{
			$placements_for_deployment[$placement->NAME] = new placement;	
			$placements_for_deployment[$placement->NAME]->name = $placement->NAME;	
			$placements_for_deployment[$placement->NAME]->timeframe[] = timestamp_to_german_date($placement->TIMEFRAME_BEGIN) . '-' . timestamp_to_german_date($placement->TIMEFRAME_END);
			$placements_for_deployment[$placement->NAME]->places_max = $placement->PLACES_MAX;
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
			// count joker
			if(isset($user['DEPLOYMENTS'][$deployment][0]) && $user['DEPLOYMENTS'][$deployment][0] == $placement->ID)
			{
				$placements_for_deployment[$placement->NAME]->places_wish++;
			}
		}
	}
	foreach($placements_for_deployment as $this_placement)
	{
		$module_output .= $this_placement->name . ' (' . $this_placement->places_wish . '/' . $this_placement->places_max . ')<br />';
	}
	$module_output .= '<br />';	
}
?>
