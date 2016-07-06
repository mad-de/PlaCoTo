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

?>
