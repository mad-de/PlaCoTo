<?php

$placement_table = fetch_json_table('placements.json');
if (!($placement_table === FALSE))
{
	$placement_count = 0;
	foreach($placement_table as $this_placement) 
	{
		$placement_list[$placement_count] = new db_placement_list;
		$placement_list[$placement_count] -> ID = $this_placement["ID"];
		$placement_list[$placement_count] -> NAME = $this_placement["NAME"];
		$placement_list[$placement_count] -> GROUPS = $this_placement["GROUPS"];
		$placement_list[$placement_count] -> DUE_DATE = $this_placement["DUE_DATE"];
		$placement_count++;
	}
}
else { die("can not access Placements Database."); }

$placement_list = fetch_placement_list();
// fetch !1! placement - assume last one in database gets calculated first
foreach($placement_list as $this_placement_list)
{
	if($this_placement_list->DUE_DATE <= date() && !(is_dir(get_DB_PATH() . DIRECTORY_SEPARATOR . 'calculation_' . $this_placement_list->ID . DIRECTORY_SEPARATOR)))
	{ $placement_id = $this_placement_list->ID; }
}
if(!isset($placement_id))
{ die("Nothing to calculate."); }

// load functions and classes
include "config.php";
include "resources/json_functions.php";
include "resources/functions_calculate_placements.php";

// Initialize some vars
$placement_student_count = 0;
$placement_count = 0;
$placement_id = $_GET["id"];

// Create folder first to lock any previous attempts on calculating or DDOSing our script
if(!is_dir(get_DB_PATH() . DIRECTORY_SEPARATOR . 'calculation_' . $placement_id . DIRECTORY_SEPARATOR))
{ mkdir(get_DB_PATH() . DIRECTORY_SEPARATOR . 'calculation_' . $placement_id . DIRECTORY_SEPARATOR, 0777, true); }

$report_output = "<h1>Report:</h1>";

// fetch results

$wishlist_table = fetch_json_table('wishlist_' . $placement_id . '.json');
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

// insert JOKER at beginning of priority types array and Location at the end
array_unshift($priority_types, "JOKER");
$priority_types[] = "LOCATION";

// Fetch deployments
$deployments = fetch_placement_item($placement_id, "DEPLOYMENT");

// Report wishlist
$remove_student_list = array();
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

$placement_table = fetch_json_table('placement_' . $placement_id . '.json');
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
						$report_output .=  'ID:' . $this_i_placement_student->id . '; '; 
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
					$current_student_array[$current_student_count]->id = &$current_student->id;
					$current_student_array[$current_student_count]->name = &$current_student->name;
					$current_student_array[$current_student_count]->joker = &$current_student->joker;
					$current_student_array[$current_student_count]->alloc_timeframe = "";
					$current_student_array[$current_student_count]->deployments = &$current_student->deployments;
					$current_student_array[$current_student_count]->karma = &$current_student->karma;
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
					$current_placement->students_alloc[] = $current_student->id;
					if(!($key == (count($priority_types) - 2) || $key == (count($priority_types) - 1)))
					{ $this_round_happiness = ($this_round_happiness + ((count($priority_types) - $key) * 10)); }
					if($key == 0) { $current_student->joker = ($current_student->joker - 1); $report_output .= ' <i>. He used a Joker in this round. So his Joker will be subtracted. Goodbye, Joker!</i>'; }
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
					// compare id to reset reference
					foreach($current_student_array as $current_student)
					{
						if($current_student_array[$i]->id == $current_student->id)
						{
							unset($current_student->deployments[$current_placement->deployment]);
							$current_student->alloc_timeframe = $current_placement->timeframe_begin . '::' . $current_placement->timeframe_end;
							// Reduce karma points
							if(!($key == (count($priority_types) - 2) || $key == (count($priority_types) - 1)))
							{
								$report_output .= '; His karma will therefore be reduced by ' . abs(get_DEDUCTION_ROLL_PLACEMENT());
								$current_student->karma = ($current_student_array[$i]->karma + get_DEDUCTION_ROLL_PLACEMENT());
								$this_round_happiness = ($this_round_happiness + ((count($priority_types) - $key) * 10));
							}
							// Reduce joker
							if($key == 0) { $current_student->joker = ($current_student->joker - 1); $report_output .= ' (used a Joker in this round. So his Joker will be subtracted)'; }
							// Allocate student
							$current_placement->students_alloc[] = $current_student->id;
							$report_output .=  ';</i>';
						}
					}
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
	
	// put into iteration_describer array
	$iteration_describer[$iterations] = new iteration_describer;
	$iteration_describer[$iterations] -> id = $iterations;
	$iteration_describer[$iterations] -> overall_happiness = $i_overall_happiness;
	
	// Missing Deployments:
	$i_missing_placements_output = "<br /><br /><b><u>Warning:</u> students with unallocated deployments:</b><br />";
	$i_missing_placements = FALSE;
	foreach($i_placement_student as $this_i_placement_student)
	{
		if(!(empty($this_i_placement_student->deployments)))
		{
			$i_missing_placements = TRUE;
			foreach($this_i_placement_student->deployments as $missing_deployment => $value)
			{
				$i_missing_placements_output .= '<br />ID:' . $this_i_placement_student->id . " is missing " . $missing_deployment;
				$iteration_describer[$iterations] -> unallocated_students[$this_i_placement_student->id] =  $missing_deployment;
			}
		}
	}
	if($i_missing_placements) { $report_output .= $i_missing_placements_output; }
	
	// Missing min_places
	$i_missing_min_places_output = '<br /><br /><b><u>Warning:</u> Placements with unallocated minumum places:</b><br />';
	$i_missing_min_places = FALSE;
	foreach($i_placements as $this_i_placement)
	{
		if(!(empty($this_i_placement->places_min)) && (count($this_i_placement->students_alloc) < $this_i_placement->places_min))
		{
			$i_missing_min_places = TRUE;
			$iteration_describer[$iterations] -> unallocated_min_places[$this_i_placement->id] = ($this_i_placement->places_min - count($this_i_placement->students_alloc)) ;
			$i_missing_min_places_output .= $this_i_placement->name . ' (' . timestamp_to_german_date($this_i_placement->timeframe_begin) . '-' . timestamp_to_german_date($this_i_placement->timeframe_end) . ') is missing ' . ($this_i_placement->places_min - count($this_i_placement->students_alloc)) . ' students.<br />';
		}
	}
	if($i_missing_min_places) { $report_output .= $i_missing_min_places_output; }	
	
	$report_output .= '<br />The overall happiness is: ' . $i_overall_happiness;
	
	// Insert tables
	$iteration_describer[$iterations] -> students =  $i_placement_student;
	$iteration_describer[$iterations] -> placements =  $i_placements;
}

// FINAL CALCULATIONS
$report_output .= '<h1>Final Results:</h1>';
$report_output .= '<h2>Calculating the best table</h2>';
$report_output .= "Step 1: I'll try to come up with a table, where all students are allocated.<br />";
// Check if there is a calculation, where all students are allocated
$calc_all_allocated = FALSE;
foreach($iteration_describer as $iteration) { if(empty($iteration->unallocated_students)) { $calc_all_allocated = TRUE; } }
// if there is such a calculation, remove all others
if($calc_all_allocated)
{
	$report_output .= 'There is at least one calculation where all students are allocated. Removing all tables with students un-allocated: ';
	foreach($iteration_describer as $iteration => $value)
	{ if(!(empty($value->unallocated_students))) { $report_output .= $value->id . '; '; unset($iteration_describer[$iteration]); } }
}
else { $report_output .= "<b>HOOOMANZ!</b> I haven't been able to calculate a table, where all students are allocated. :("; }

// Check if there is a calculation where all minimum places are allocated
$report_output .= "<br /><br />Step 2: I'll try to come up with a table, where all students are allocated.<br />";
$calc_min_places_allocated = FALSE;
foreach($iteration_describer as $iteration) { if(empty($iteration->unallocated_min_places)) { $calc_min_places_allocated = TRUE; } }
// if there is such a calculation, remove all others
if($calc_min_places_allocated)
{
	$report_output .= 'There is at least one calculation where all minimum places are allocated. Removing all tables with minimum placements unset: ';
	foreach($iteration_describer as $iteration => $value)
	{ if(!(empty($value->unallocated_min_places))) { $report_output .= $value->id . '; '; unset($iteration_describer[$iteration]); } }
}
else { $report_output .= "<b>HOOOMANZ!</b> I haven`t been able to calculate a table, where all minimum places are allocated. :("; }

// Step 3 order by happiness factor
$report_output .= "<br /><br />Step 3: I'll select the table with the highest happiness factor.<br />";
usort($iteration_describer, "sort_by_happiness");
$report_output .= "Our winner is: " . $iteration_describer[0]->id . " with a happiness factor of " . $iteration_describer[0]->overall_happiness ."<br />Noticeable others:";
for($i = 1; (count($iteration_describer)) > $i; $i++)
{
$report_output .= "<br />Number " . ($i + 1) . " : " . $iteration_describer[$i]->id . " with a happiness factor of " . $iteration_describer[$i]->overall_happiness;
}
// Create a sorted user table
$students_by_id = array();
foreach($student_table as $this_student)
{ $students_by_id[$this_student["ID"]] = $this_student; }
// Adjust placement table for export (replace IDs with name)
foreach($iteration_describer[0]->placements as $this_placement)
{ foreach($this_placement->students_alloc as &$this_student) 
{ $this_student = $students_by_id[$this_student]["NAME"]; unset($this_student);} 
}	

$report_output .= "<br /><br />Step 4: I will upload the calculated placements file for your convenience.";
if(insert_calculation_file($placement_id, "placements", $iteration_describer[0]))
{ $report_output .= 'Success! You can find it here: <a href="index.php?act=data_export&id=' . $placement_id . '">Download xls</a>'; }
else { $report_output .= "<br /><b>HOOOMANZ!</b> I haven`t been able to upload the placement file. :("; }

$report_output .= "<br /><br />Step 5: I will upload the calculated students file for your convenience.";
// Insert Joker and Karma + Joker values into students table
foreach($iteration_describer[0]->students as $this_student)
{
	$students_by_id[$this_student->id]["KARMA"] = $this_student->karma; 
	$students_by_id[$this_student->id]["JOKER"] = $this_student->joker; 
}

if($calc_min_places_allocated && $calc_all_allocated)
{
	if(file_put_contents(get_DB_PATH() . DIRECTORY_SEPARATOR . 'students.json', json_encode($students_by_id)))
	{ $report_output .= "<br />Success! I replaced the old students table with the new one.<br />"; }
	else { $report_output .= "<br /><b>HOOOMANZ!</b> I haven`t been able to replace the students file. :("; }

}
else
{
	$report_output .= "<br />There were errors while calculating. I will save a copy of the calculated students table in the calculation folder without replacing the stundents database.<br />";
	if(insert_calculation_file($placement_id, "students_new", $students_by_id))
	{ $report_output .= "Success! You can find it here: TODO<br />"; }
	else { $report_output .= "<br /><b>HOOOMANZ!</b> I haven`t been able to upload the students file. :("; }
}

$report_output .= '<br /><b>RESULTS:</b>';
foreach($iteration_describer[0]->placements as $current_placement)
{
	$report_output .=  "<br />" . $current_placement->name . " (" . timestamp_to_german_date($report_placement->timeframe_begin). '-' . timestamp_to_german_date($report_placement->timeframe_end) . ")";
	$report_output .=  " Students: ";
	foreach($current_placement->students_alloc as $student_alloc => $allocated_student)
	{
		$report_output .=  $allocated_student . "; ";
	}
}

$report_output .= "<br /><br /><b>Ok, that`s all. I`m done for today.</b>";



$module_output = $report_output;
?>
