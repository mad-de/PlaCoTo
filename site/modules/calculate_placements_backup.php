<?php

include "resources/times.php";

class student
{
  var $id;
  var $name;
  var $email;
  var $karma;
  var $joker;
  public $deployments = array();
  public $timeframes_unavailable = array();
}

class comp_student
{
  var $id;
  var $name; // remove later and switch to ID only
  var $karma;
  var $used_joker;
  var $alloc_timeframe;
  public $deployments = array();
  var $calc_luck;
}

class placement
{
  var $id;
  var $deployment;
  var $location;
  var $timeframe_begin;
  var $timeframe_end;
  var $places_min;
  var $places_max;
  public $students_alloc = array();
}

function return_students($array)
{
	$i = 0;
	$students = array();
	foreach($array as $orig_student)
	{
		$students[$i] = new student;
		$students[$i] -> id = $orig_student -> id;
		$students[$i] -> karma = $orig_student -> karma;
		$students[$i] -> joker = $orig_student -> joker;
		$students[$i] -> email = $orig_student -> email;
		$students[$i] -> name = $orig_student -> name;
		$students[$i] -> deployments = $orig_student -> deployments;
		$students[$i] -> timeframes_unavailable = $orig_student -> timeframes_unavailable;
		$i++;
	}
	return $students;
}

function return_placements($array)
{
$i = 0;
$placements = array();
foreach($array as $orig_placement)
{
	$placements[$i] = new placement; 
	$placements[$i]->id = $orig_placement->id; 
	$placements[$i]->name = $orig_placement->name; 
	$placements[$i]->deployment = $orig_placement->deployment;
	$placements[$i]->location = $orig_placement->location;
	$placements[$i]->timeframe_begin = $orig_placement->timeframe_begin;
	$placements[$i]->timeframe_end = $orig_placement->timeframe_end;
	$placements[$i]->places_min = $orig_placement->places_min;
	$placements[$i]->places_max = $orig_placement->places_max;
	$i++;
}
return $placements;
}

function sort_by_luck($ind_a, $ind_b)
{
    return ($ind_b->calc_luck - $ind_a->calc_luck);
}

function sort_random()
{
    return rand(-1, 1);
}

function sort_by_open_min_places($ind_a, $ind_b)
{
    return (($ind_b->places_min - count($ind_b->students_alloc)) - ($ind_a->places_min - count($ind_a->students_alloc)));
}

function calculate_luck($student_array)
{
	$lowest_karma = calculate_lowest_karma($student_array);
	// only use positive values
	foreach($student_array as $student)
	{
		$student->new_karma = (($student->karma + abs($lowest_karma) + 1) * 1000); 
	}
	$karma_median = calculate_median($student_array);
	foreach($student_array as $student)
	{
		$student->calc_luck = (mt_rand(sqrt($karma_median), $karma_median) * ($student->karma + abs($lowest_karma) + 1));
	}
	usort($student_array, "sort_by_luck"); 
	return $student_array;
}

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

function calculate_lowest_karma($array)
{	
	return min(return_array_by_key($array, "karma"));
}

function calculate_median($array) 
{
	$array = return_array_by_key($array, "new_karma");
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

// depending on key set matching placement (ID for JOKER, NAME for Priorites)
function matching_placement($current_student, $current_placement, $key, $total_keys)
{
	// JOKER
	if($key == 0) 
	{	
		if(isset($current_student->deployments[$current_placement->deployment][$key]) && $current_student->deployments[$current_placement->deployment][$key] == $current_placement->id && $current_student->joker > 0)
		{ return TRUE; }
		else { return FALSE; }
	}
	// LOCATION
	elseif($key == ($total_keys - 3))
	{
		if(isset($current_student->deployments[$current_placement->deployment][$key]) && $current_student->deployments[$current_placement->deployment][$key] == $current_placement->location)
		{ return TRUE; }
		else 
		{ return FALSE; }
	}
	// REMAINING and min_places fill up
	elseif(($key == ($total_keys - 2)) || ($key == ($total_keys - 1)))
	{
		return TRUE;
	}
	// PRIORITIES
	else
	{
		if(isset($current_student->deployments[$current_placement->deployment][$key]) && $current_student->deployments[$current_placement->deployment][$key] == $current_placement->name)
		{ return TRUE; }
		else 
		{ return FALSE; }
	}
}

function check_timeframes($placement_timeframe_begin, $placement_timeframe_end, $student_timeframes_unavailable)
{
	$result_timeframes = TRUE;
	foreach ($student_timeframes_unavailable as $student_timeframe_unavailable)
	{
		$this_timeframe = explode("::", $student_timeframe_unavailable);
		if(!(($this_timeframe[0] < $placement_timeframe_begin && $this_timeframe[1] < $placement_timeframe_end) || ($this_timeframe[0] > $placement_timeframe_begin && $this_timeframe[1] > $placement_timeframe_end)))
		{
			$result_timeframes = FALSE;
		}
	}
	return $result_timeframes;
}

	// Initialize some vars
$placement_student_count = 0;
$placement_count = 0;

$report_output = "<h1>Report:</h1>";

// fetch results

$wishlist_table = fetch_json_table('wishlist_' . $_GET["id"] . '.json');
if (!($wishlist_table === FALSE))
{
	foreach($wishlist_table as $wishlist) 
	{
		$placement_student[$placement_student_count] = new student;
		$placement_student[$placement_student_count] -> id = $wishlist["ID"];
		$placement_student[$placement_student_count] -> deployments = $wishlist["DEPLOYMENTS"];
		$placement_student[$placement_student_count] -> timeframes_unavailable = $wishlist["TIMEFRAMES_UNAVAILABLE"];
		// Add a custom unavailable timeframe if user has one and reduce karma points
		if(!(empty($wishlist["CUSTOM_TIMEFRAME_UNAVAILABLE"])))
		{
			$placement_student[$placement_student_count] -> timeframes_unavailable[] = $wishlist["CUSTOM_TIMEFRAME_UNAVAILABLE"];
			$placement_student[$placement_student_count] -> karma = get_DEDUCTION_CUSTOM_TIMEFRAME();
		}
		$placement_student_count = $placement_student_count + 1;
	}
}

// Get missing values from the students list
$student_table = fetch_json_table('students.json');
if (!($student_table === FALSE))
{
	foreach($student_table as $this_table_student) 
	{
		foreach($placement_student as $this_placement_student)
		{
			if($this_table_student["ID"] == $this_placement_student->id) 
			{
				$this_placement_student->name = $this_table_student["NAME"];
				$this_placement_student->karma = ($this_placement_student->karma + $this_table_student["KARMA"]);
				$this_placement_student->joker = $this_table_student["JOKER"];
				$this_placement_student->email = $this_table_student["EMAIL"];
			}
		}
	}
}

// insert JOKER at beginning of priority types array
$priority_types[] = "LOCATION";

// Fetch deployments
$deployments = fetch_placement_item($_GET["id"], "DEPLOYMENT");

// Report wishlist
$report_output .= '<h2>Participating students:</h2>';
foreach($placement_student as $report_student)
{
	$report_output .= 'Student ID: ' . $report_student->id . '; Karma (after initial deductions): ' .  $report_student->karma;
	if(!empty($report_student->joker))
	{
		$report_output .= '; Jokers: ' . $report_student->joker . ';';
	}
	if(!empty($report_student->timeframes_unavailable))
	{
	$report_output .= '<br />Unavailable timeframes: ';
		foreach ($report_student->timeframes_unavailable as $report_timeframe)
		{
			$report_exploded_timeframe = array();
			$report_exploded_timeframe = explode('::', $report_timeframe);
			$report_output .= timestamp_to_german_date($report_exploded_timeframe[0]) . '-' . timestamp_to_german_date($report_exploded_timeframe[1]) . '; ';
		}
	}
	if(!empty($report_student->deployments))
	{
		foreach($deployments as $deployment)
		{
			if(!empty($report_student->deployments[$deployment]))
			{
				$report_output .= '<br /> Wishes for ' . $deployment . ': ';
				foreach($priority_types as $key => $value)
				{
					if(!empty($report_student->deployments[$deployment][$key]))
					{
						$report_output .= $value . ': ' . $report_student->deployments[$deployment][$key] . '; ';
					}
				}
			}
		}
	}
	$report_output .= '<br /><br />';
}


$placement_table = fetch_json_table('placement_' . $_GET["id"] . '.json');
$placement_count = 0;
$placements = array();
foreach($placement_table as $orig_placement)
{
	$placements[$placement_count] = new placement; 
	$placements[$placement_count]->id = $orig_placement["ID"]; 
	$placements[$placement_count]->name = $orig_placement["NAME"]; 
	$placements[$placement_count]->deployment = $orig_placement["DEPLOYMENT"];
	$placements[$placement_count]->location = $orig_placement["LOCATION"];
	$placements[$placement_count]->timeframe_begin = $orig_placement["TIMEFRAME_BEGIN"];
	$placements[$placement_count]->timeframe_end = $orig_placement["TIMEFRAME_END"];
	$placements[$placement_count]->places_min = $orig_placement["PLACES_MIN"];
	$placements[$placement_count]->places_max = $orig_placement["PLACES_MAX"];
	$placement_count++;
}
// Report placements
$report_output .= '<h2>Available placements:</h2>';
foreach($deployments as $deployment)
{
	$report_output .= '<b>' . $deployment . '</b><br />';
	foreach($placements as $report_placement)
	{
		if($report_placement->deployment == $deployment)
		{
			$report_output .= 'Name: ' . $report_placement->name . '; Location: ' . $report_placement->location . '; ';
			if(!empty($report_placement->places_min))
			{
				$report_output .= 'Minimum places: ' . $report_placement->places_min;
			}
			$report_output .= ' Maximum places: ' . $report_placement->places_max . ' Time: ' . timestamp_to_german_date($report_placement->timeframe_begin). '-' . timestamp_to_german_date($report_placement->timeframe_end) . '<br />';
		}
	}
	$report_output .= '<br />';
}
// Insert location run at the end of array
$priority_types[] = "FILL PLACES_MIN";
$priority_types[] = "REMAINING";
array_unshift($priority_types, "JOKER");

$report_output .= '<h2>Calculating placements</h2>';

for($iterations = 1; $iterations < (get_ITERATIONS() + 1); $iterations++, $i_placements = array(), $i_placement_student = array())
{
	// use pointers for iterations
	$i_placements = return_placements($placements);
	$i_placement_student = return_students($placement_student);

	// Sort placements randomly
	usort($i_placements, "sort_random");

	$report_output .= '<h3>Iteration ' . $iterations . '</h3>';
	$i_overall_happiness = 0;
	// run depending on priority
	foreach($priority_types as $key => $value)
	{
		$report_output .=  '<b>' . $value . '</b><br />';
		if($key == (count($priority_types) - 3))
		{
			// Sort placements by min_places
			usort($i_placements, "sort_by_open_min_places");
		}
		// karma bonus for remaining round
		if($key == (count($priority_types) - 2))
		{
			$report_output .=  '<i>The following students get a karma bonus of ' . get_BONUS_REMAINING_ROUND() . ': ';
			foreach($i_placement_student as $this_i_placement_student)
			{
				if(!(empty($this_i_placement_student->deployments)))
				{
						$report_output .=  $this_i_placement_student->name . '; '; 
						$this_i_placement_student->karma = ($this_i_placement_student->karma + get_BONUS_REMAINING_ROUND());
				}
			}
			$report_output .= 'Congratulations!</i><br/>';
		}
		foreach($i_placements as $current_placement)
		{
			$this_round_happiness = 0;
			// use number of places to add up to fill min_places in add up round
			if($key == (count($priority_types) - 2))
			{
				if(($current_placement->places_min - count($current_placement->students_alloc)) > 0)
				{ $places_target = ($current_placement->places_min - count($current_placement->students_alloc)); }
				else { $places_target = 0; }
			}	
			else
			{ $places_target = $current_placement->places_max - count($current_placement->students_alloc); }
			// Create POOL
			$current_student_array = array();
			$current_student_count = 0;
			foreach($i_placement_student as $current_student)
			{
				//check if student needs this deployment and if this placement is students current priority + if students timeframes dont collide with placement timeframe
				if(isset($current_student->deployments[$current_placement->deployment]) && matching_placement($current_student, $current_placement, $key, count($priority_types)) && check_timeframes($current_placement->timeframe_begin, $current_placement->timeframe_end, $current_student->timeframes_unavailable))
				{
					// Add to our array
					$current_student_array[$current_student_count] = new comp_student;
					$current_student_array[$current_student_count]->id = $current_student->id;
					$current_student_array[$current_student_count]->name = $current_student->name;
					$current_student_array[$current_student_count]->used_joker = 0;
					$current_student_array[$current_student_count]->alloc_timeframe = "";
					$current_student_array[$current_student_count]->deployments = $current_student->deployments;
					$current_student_array[$current_student_count]->karma = $current_student->karma;
					$current_student_count = $current_student_count + 1;
				}
			}
			$report_output .= $current_placement->name . '. Time: ' . timestamp_to_german_date($report_placement->timeframe_begin). '-' . timestamp_to_german_date($report_placement->timeframe_end) . '; Available places: ' . $places_target;
			$report_output .= '; with ' . count($current_student_array) . ' Students eligable: ';
			foreach($current_student_array as $report_student)
			{
				$report_output .= 'ID:' . $report_student->id . ';';
			}
			// Calculate places

			if (empty($current_student_array))
			{ }
			elseif(($places_target < 1) || ($current_placement->places_max == 0))
			{ $report_output .= '<br />But there are no placements open. Sorry guys!'; }		
			elseif (!(empty($current_student_array)) && !($places_target == 0) && ($places_target >= count($current_student_array)))
			{
				$report_output .= '<br />Enough places for all eligable students. Students allocated: ';
				// Allocate student - delete keys from array
				foreach($current_student_array as $current_student)
				{
					$report_output .= 'ID:' . $current_student->id;
					unset($current_student->deployments[$current_placement->deployment]);
					$current_student->alloc_timeframe = $current_placement->timeframe_begin . '::' . $current_placement->timeframe_end;
					$current_placement->students_alloc[] = $current_student->name;
					if(!($key == (count($priority_types) - 2) || $key == (count($priority_types) - 1)))
					{ $this_round_happiness = ($this_round_happiness + ((count($priority_types) - $key) * 10)); }
					if($key == 0) { $current_student->used_joker = 1; $report_output .= ' <i>. He used a Joker in this round. So his Joker will be subtracted. Goodbye, Joker!</i>'; }
					$report_output .=  ';';
				}
			}
			else
			{
				$report_output .= '<br />Not enough places. Begin calculating luck:';
				// calculate luck
				$current_student_array = calculate_luck($current_student_array);
				// after luck calculation use sorted array to return the lucky winners
				for($i = 0; $places_target > $i; $i++)
				{
					$report_output .= '<br /><i>ID:' . $current_student_array[$i]->id . ' rolled the dice with a karma of ' . $current_student_array[$i]->karma .  '. He got lucky with ' . $current_student_array[$i]->calc_luck . ' points. Congratulations';
					unset($current_student_array[$i]->deployments[$current_placement->deployment]);
					$current_student_array[$i]->alloc_timeframe = $current_placement->timeframe_begin . '::' . $current_placement->timeframe_end;
					if(!($key == (count($priority_types) - 2) || $key == (count($priority_types) - 1)))
					{
						$report_output .= '; His karma will therefore be reduced by ' . abs(get_DEDUCTION_ROLL_PLACEMENT());
						$current_student_array[$i]->karma = ($current_student_array[$i]->karma + get_DEDUCTION_ROLL_PLACEMENT());
						$this_round_happiness = ($this_round_happiness + ((count($priority_types) - $key) * 10));
					}
					$current_placement->students_alloc[] = $current_student_array[$i]->name;
					if($key == 0) { $current_student_array[$i]->used_joker = 1; $report_output .= ' (used a Joker in this round. So his Joker will be subtracted)'; }
					$report_output .=  ';</i>';
				}
				while(count($current_student_array) > $i)
				{
					$report_output .= '<i><br />ID:' . $current_student_array[$i]->id . ' rolled the dice with a karma of ' . $current_student_array[$i]->karma .  '. He was unsuccesful with ' . $current_student_array[$i]->calc_luck . ' points. Sorry, mate.</i>';
					$i++;
				}			
			}
			if(!($this_round_happiness == 0))
			{
				$report_output .= '<br /><i>The overall happiness has increased by ' . $this_round_happiness . ' in this round</i>';
				$i_overall_happiness = ($i_overall_happiness + $this_round_happiness);
			}
			// return values to original array
			foreach($current_student_array as $current_student)
			{
				// return the values to the DB
				foreach($i_placement_student as $this_i_placement_student)
				{	
					if($this_i_placement_student->id == $current_student->id)
					{
						$this_i_placement_student->karma = $current_student->karma;
						$this_i_placement_student->deployments = $current_student->deployments;
						$this_i_placement_student->joker = $this_i_placement_student->joker - $current_student->used_joker;
						if(!($current_student->alloc_timeframe == ""))
						{
							$this_i_placement_student->timeframes_unavailable[] = $current_student->alloc_timeframe;
						}
					}
				}
			}
			$report_output .= '<br />';
		} 
		$report_output .= '<br />';
	}

	$report_output .= '<b>RESULTS:</b>';
	foreach($i_placements as $current_placement)
	{
		$report_output .=  "<br />" . $current_placement->name . " (" . timestamp_to_german_date($report_placement->timeframe_begin). '-' . timestamp_to_german_date($report_placement->timeframe_end) . ")";
		$report_output .=  " Students: ";
		foreach($current_placement->students_alloc as $student_alloc)
		{
			$report_output .=  $student_alloc . "; ";
		}
	}
	$i_missing_placements_output = "<br /><br /><b>MISSING DEPLOYMENTS :(</b><br />";
	$i_missing_placements = FALSE;
	foreach($i_placement_student as $this_i_placement_student)
	{
		if(!(empty($this_i_placement_student->deployments)))
		{
			$i_missing_placements = TRUE;
			foreach($this_i_placement_student->deployments as $missing_deployment => $value)
			{
				$i_missing_placements_output .= 'ID:' . $this_i_placement_student->id . " is missing " . $missing_deployment . "<br />";
			}
		}
	}
	if($i_missing_placements) { $report_output .= $i_missing_placements_output; }

	$report_output .= '<br />The overall happiness is: ' . $i_overall_happiness;
	
	insert_calculation($_GET["id"], $iterations, $i_placement_student, $i_placements);
}
$report_output .= '<h1>Final Results:</h2>';
//print_r($i_placement_student);

/* ENDAUSWERTUNG
$deployments = fetch_placement_item($_GET["id"], "DEPLOYMENT");
foreach($deployments as $deployment)
{
	foreach()
}
*/

$module_output = $report_output;
?>
