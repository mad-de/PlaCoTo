<?php

function return_array_by_key($array, $key)
{	
	$array_count = 0;
	$new_array = array();
	foreach($array as $item)
	{
		$new_array[$array_count] = $item->$key;
		$array_count++;
	}
	return $new_array;
}

// Function from https://gist.github.com/ischenkodv/262906 all credit goes to ischenkodv
function calculate_median($array, $key) 
{
	$array = return_array_by_key($array, $key);
	//total numbers in array
	$count = count($array);
	// find the middle value, or the lowest middle value
	$middleval = floor(($count-1)/2);
	if($count % 2) 
	{ 
		// odd number, middle is the median
		$median = $array[$middleval];
	} 
	else 
	{ 
		// even number, calculate avg of 2 medians
		$low = $array[$middleval];
		$high = $array[$middleval+1];
		$median = (($low+$high)/2);
	}
	return $median;
}

function calculate_average($array, $key) 
{
	$array = return_array_by_key($array, $key);
	return (array_sum($array) / count($array));
}
?>