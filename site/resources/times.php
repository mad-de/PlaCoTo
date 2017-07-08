<?php

function german_date_to_timestamp($time)
{
	$a = strptime($time, '%d.%m.%Y');
	return mktime(0, 0, 0, $a['tm_mon']+1, $a['tm_mday'], $a['tm_year']+1900);
}

function is_valid_date($time)
{
	if((timestamp_to_german_date(german_date_to_timestamp($time)) == $time) && !(german_date_to_timestamp($time) == 0))
	{ return true; }
	else { return false; }
}

function timestamp_to_german_date($time)
{
	return date('d.m.Y', $time);
}

function cmp($a, $b) 
{
    return (german_date_to_timestamp($a->begin) < german_date_to_timestamp($b->begin)) ? -1 : 1;
}

function calculate_timeframes($placements)
{
	$timeframe_count = 0;
	$timeframes = array();
	foreach($placements as $placement)
	{
		if(empty($timeframes))
		{
			$timeframes[$timeframe_count] = new timeframe;
			$timeframes[$timeframe_count] -> begin = date('d.m.Y', $placement->TIMEFRAME_BEGIN);
			$timeframes[$timeframe_count] -> end = date('d.m.Y', $placement->TIMEFRAME_END);
			$timeframe_count++;
		}
		else
		{
			$timeframe_exists = FALSE;
			foreach($timeframes as $timeframe)
			{
				if(($timeframe->begin == date('d.m.Y', $placement->TIMEFRAME_BEGIN)) && ($timeframe->end == date('d.m.Y', $placement->TIMEFRAME_END)))
				{
					$timeframe_exists = TRUE;
				}
			}
			if(!($timeframe_exists))
			{
				$timeframes[$timeframe_count] = new timeframe;
				$timeframes[$timeframe_count] -> begin = date('d.m.Y', $placement->TIMEFRAME_BEGIN);
				$timeframes[$timeframe_count] -> end = date('d.m.Y', $placement->TIMEFRAME_END);
				$timeframe_count++;				
			}
		}
	}
	return $timeframes;
}

function calculate_timeframe_choices($placements)
{
	$timeframes = array();
	$timepoints = array();
	foreach($placements as $placement)
	{
		if(empty($timepoints) || !in_array($placement->TIMEFRAME_BEGIN, $timepoints)) { $timepoints[] = $placement->TIMEFRAME_BEGIN;}
		if(!in_array($placement->TIMEFRAME_END, $timepoints)) { $timepoints[] = $placement->TIMEFRAME_END;}
	}
	$count_timepoints = count($timepoints);
	$i = 0;
	$setback_counter = 0;
	$timeframe_count = 0;
	asort($timepoints);
	//weird php bug makes switch to date format necessary. Whoever can tell me how that is get's a beer.
	$dates = array();
	foreach($timepoints as $timepoint)
	{
		$dates[] = date('d.m.Y', $timepoint);
	}
	while($count_timepoints > $i)
	{
		if($setback_counter == 0)
		{
			$timeframe_count++;
			$setback_counter++;
			$timeframes[$timeframe_count] = new timeframe;
			if(date("w", german_date_to_timestamp($dates[$i])) == 5)
			{
				$timeframes[$timeframe_count] -> begin = date('d.m.Y', strtotime(date('d.m.Y', german_date_to_timestamp($dates[$i])) . ' - 4 days'));
				$i--;
			}
			else
			{
				$timeframes[$timeframe_count] -> begin = $dates[$i];				
			}

		}
		else
		{
			while(german_date_to_timestamp($dates[$i]) < strtotime($timeframes[$timeframe_count]->begin . ' + 2 days') && $i < $count_timepoints)
			{
				$i++;
			}
			if(isset($dates[$i]))
			{
				$tgif = 0;
				// Check if date is friday, otherwise make it friday	
				while(!((date("w", german_date_to_timestamp($dates[$i]))-$tgif) == 5 ) && !((date("w", german_date_to_timestamp($dates[$i]))-$tgif) == -2))
				{
					$tgif++;
				}
				
				$timeframes[$timeframe_count] -> end = date('d.m.Y', strtotime(date('d.m.Y', german_date_to_timestamp($dates[$i])) . ' - ' . $tgif . ' days'));
				
			}
			$setback_counter = 0;
			// was it a monday, that has been transformed to friday? reduce counter then... 
			if(date("w", german_date_to_timestamp($dates[$i])) == 1 && !((date("w", german_date_to_timestamp($dates[$i]))-$tgif) == (date("w", german_date_to_timestamp($dates[$i])))))
			{
				$i--;
			}				
		}
		if($timeframes[$timeframe_count] -> end == $timeframes[$timeframe_count] -> begin)
		{
			$timeframes[$timeframe_count] -> end = date('d.m.Y', strtotime($timeframes[$timeframe_count]->begin . ' + 5 days'));
		}
		$i++;
	}
	//Fallback: Somehow timeframes don't add up:
	if(!isset($timeframes[$timeframe_count] -> end))
	{
		$timeframes[$timeframe_count] -> end = date('d.m.Y', strtotime($timeframes[$timeframe_count]->begin . ' + 5 days'));
	}
	uasort($timeframes, 'cmp');
	return $timeframes;
}

?>
