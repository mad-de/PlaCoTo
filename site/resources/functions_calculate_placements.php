<?php

// load functions and classes
include "resources/times.php";
include "resources/email_functions.php";
include "resources/times.php";
include "resources/math.php";

class student
{
  var $id;
  var $name;
  var $email;
  var $karma;
  var $joker;
  public $deployments = array();
  public $deployment_deduction = array();
  public $timeframes_unavailable = array();
}

class comp_student
{
  var $id;
  var $name; // remove later and switch to ID only
  var $karma;
  var $used_joker;
  public $timeframes_unavailable = array();
  public $deployments = array();
  public $deployment_deduction = array();
  var $calc_luck;
}

class placement
{
  var $id;
  var $name;
  var $deployment;
  var $location;
  var $timeframe_begin;
  var $timeframe_end;
  var $places_min;
  var $places_max;
  public $students_alloc = array();
}
class deployment
{
  public $placements = array();
}

class iteration_describer
{
  var $id;
  var $overall_happiness;
  public $unallocated_students = array();
  var $num_unallocated_students;
  public $unallocated_min_places = array();
  var $num_unallocated_min_places;
  public $students = array();
  public $placements = array();
  var $report_output;
  var $iteration_output;
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
		$deployment_deduction = array();
		foreach($students[$i]->deployments as $deployment => $value)
		{
			$deployment_deduction[$deployment] = new stdClass();
			$deployment_deduction[$deployment] = get_PRIORITIES_AFFECTING_KARMA();
		}
		$students[$i] -> deployment_deduction = $deployment_deduction;
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

function sort_by_happiness($ind_a, $ind_b)
{
    return ($ind_b->overall_happiness - $ind_a->overall_happiness);
}

function calculate_luck($student_array)
{
	$lowest_karma = calculate_lowest_karma($student_array);
	// only use positive values
	foreach($student_array as $student)
	{
		$student->new_karma = (($student->karma + abs($lowest_karma) + 1) * 1000); 
	}
	$karma_median = calculate_median($student_array, "new_karma");
	foreach($student_array as $student)
	{
		$student->calc_luck = (mt_rand(sqrt($karma_median), $karma_median) * ($student->karma + abs($lowest_karma) + 1));
	}
	usort($student_array, "sort_by_luck"); 
	return $student_array;
}

function calculate_lowest_karma($array)
{	
	return min(return_array_by_key($array, "karma"));
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
function combine_wishlist_and_student_table($wishlist_table, $student_table)
{
	$placement_student_count = 0;
	if (!($wishlist_table === FALSE))
	{
		foreach($wishlist_table as $wishlist) 
		{
			$placement_student[$placement_student_count] = new student;
			$placement_student[$placement_student_count] -> id = $wishlist["ID"];
			$placement_student[$placement_student_count] -> deployments = $wishlist["DEPLOYMENTS"];
			$placement_student[$placement_student_count] -> karma = 0;
			// if user has not choosen any priorities for a deployment, increase karma points
			foreach($placement_student[$placement_student_count]->deployments as $deployment)
			{
				$no_wishes = TRUE;
				foreach($deployment as $key => $value)
				{
					if(!empty($value))
					{ $no_wishes = FALSE; }
				}
				if($no_wishes)
				{
					$placement_student[$placement_student_count] -> karma = ($placement_student[$placement_student_count]->karma + get_BONUS_NO_WISHES());					
				}
			}
			$placement_student[$placement_student_count] -> timeframes_unavailable = $wishlist["TIMEFRAMES_UNAVAILABLE"];
			// Add a custom unavailable timeframe if user has one and reduce karma points
			if(!(empty($wishlist["CUSTOM_TIMEFRAME_UNAVAILABLE"])))
			{
				$placement_student[$placement_student_count] -> timeframes_unavailable[] = $wishlist["CUSTOM_TIMEFRAME_UNAVAILABLE"];
				$placement_student[$placement_student_count] -> karma = ($placement_student[$placement_student_count]->karma + get_DEDUCTION_CUSTOM_TIMEFRAME());
			}
			$placement_student_count = $placement_student_count + 1;
		}
	}

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
	return $placement_student;
}

function get_placements($placement_table)
{
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
	return $placements;
}
function filter_deployments($placements)
{
	$deployments = array();
	$deployment_count = 0;
	foreach($placements as $placement)
	{
		$in_database = FALSE;
		foreach($deployments as $deployment)
		{
			if($placement->deployment == $deployment) { $in_database = TRUE; }
		}
		if($in_database == FALSE)
		{
			$deployments[$deployment_count] = $placement->deployment;
			$deployment_count++;
		}
	}
	return $deployments;
}
function inform_students_via_email($students, $students_by_id, $placements, $placement_name, $report_file)
{
	// Prepare emails to inform students about the succesful calcluation
	$emails_to_students = array();
	$emails_to_students_counter = 0;
	foreach($students as $this_email_student)
	{ 
		$emails_to_students[$emails_to_students_counter] = new email;
		$emails_to_students[$emails_to_students_counter]->receiver = $students_by_id[$this_email_student->id]["EMAIL"];
		$emails_to_students[$emails_to_students_counter]->topic = "Your placements for " . $placement_name;
		$this_message = "Hello " . $students_by_id[$this_email_student->id]["NAME"] . "<br /><br />Results are out for " . $placement_name . ":";
		foreach($placements as $this_placement)
		{ 
			if(in_array($students_by_id[$this_email_student->id]["NAME"], $this_placement->students_alloc))		
			{ $this_message .= "<br />You were allocated to " . $this_placement->name . " from " . timestamp_to_german_date($this_placement->timeframe_begin) . " - " . timestamp_to_german_date($this_placement->timeframe_end);} 
		}	
	$this_message .= '<br /><br />Your new karma is: ' . $students_by_id[$this_email_student->id]["KARMA"] . '<br />How was this calculated? Take a look at the <a href="http://' . $_SERVER['HTTP_HOST'] . '/' . $report_file . '">Report</a>. Your ID is ' . $this_email_student->id;
		$emails_to_students[$emails_to_students_counter]->message = $this_message;
		$emails_to_students_counter++;
	}	
	if(add_emails($emails_to_students))
	{
		print date('d.m.Y-H:i:s:', time()) . ' ' . count($emails_to_students) . ' Emails saved for sending.<br />';
		return TRUE;
	}
	else { return FALSE; }
}
function sort_students_by_id($student_table)
{
	$students_by_id = array();
	foreach($student_table as $this_student)
	{ $students_by_id[$this_student["ID"]] = $this_student; }
	return $students_by_id;
}
function set_first_priority_for_no_choice_deployments($deployment_placements, $placement_student)
{
	foreach($deployment_placements as $deployment_placement => $value)
	{
		if(count($value->placements) == 1)
		{
			foreach($placement_student as &$current_student)
			{
				if(array_key_exists($deployment_placement, $current_student["DEPLOYMENTS"]))
				{
					$current_student["DEPLOYMENTS"][$deployment_placement][1] = $value->placements[0];
				}
				unset($current_student);
			}
		}
	}
	return $placement_student;
}
function replace_id_with_name($placements, $students_by_id)
{
	// sort them randomly to make it harder to backtrace student IDs
	foreach($placements as &$this_placement)
	{ usort($this_placement->students_alloc, "sort_random"); unset($this_placement); }
	// Adjust placement table for export (replace IDs with name)
	foreach($placements as $this_placement)
	{ foreach($this_placement->students_alloc as &$this_student) 
		{ $this_student = $students_by_id[$this_student]["NAME"]; unset($this_student);} }	
	return $placements;
}
function upload_report($report_output, $this_iteration_output, $report_file)
{
	$report_output = str_replace('%ITERATION_REPORT%', $this_iteration_output, $report_output);
	if(!is_dir('reports' . DIRECTORY_SEPARATOR))
	{ mkdir('reports' . DIRECTORY_SEPARATOR, 0777, true); }
	if(file_put_contents($report_file, $report_output))
	{ print  date('d.m.Y-H:i:s:', time()) . ' Report written to <a href="' . $report_file . '">' . $report_file . '</a><br />'; }
	else { print  date('d.m.Y-H:i:s:', time()) . ' Error writing report.<br />'; }
}	
function result_placement_report($placements)
{
	$report = "";
	foreach($placements as $current_placement)
	{
		$report .=  "<br />" . $current_placement->name . " (" . timestamp_to_german_date($current_placement->timeframe_begin). '-' . timestamp_to_german_date($current_placement->timeframe_end) . ")";
		$report .=  " Students: ";
		foreach($current_placement->students_alloc as $student_alloc => $allocated_student)
		{
			$report .=  $allocated_student . "; ";
		}
	}
	return $report;
}		
function return_placement_deployments($deployments, $placements)
{
	foreach($deployments as $deployment)
	{
		$deployment_placements[$deployment] = new deployment;
		foreach($placements as $placement)
		{
			if($placement->deployment == $deployment && !in_array($placement->name ,$deployment_placements[$deployment]->placements))
			{ $deployment_placements[$deployment] -> placements[] = $placement->name; }
		}
	}
	return $deployment_placements;
}
function calculate_chunk($placement_student, $placements, $priority_types, $chunk_num)
{
	$deployments = filter_deployments($placements);
	$deployment_placements = return_placement_deployments($deployments, $placements);
	$report_output = '<h1>Report (Start: ' . date('d.m.Y-H:i:s', time()) . ')</h1>';

	// Report wishlist
	$remove_student_list = array();
	$report_output .= '<h2>Participating students:</h2>';
	foreach($placement_student as $report_student)
	{
		$report_output .= 'Student ID: ' . $report_student->id . '; Karma (after initial calculations): ' .  $report_student->karma;
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

	$report_output .= '<h2>Calculating placement</h2>';

	$iteration_output = array();

	for($iterations = 1; $iterations < (get_ITERATIONS() + 1); $iterations++, $i_placements = array(), $i_placement_student = array())
	{
		$iteration_output[$iterations] = "";
		// use pointers for iterations
		$i_placements = return_placements($placements);
		$i_placement_student = return_students($placement_student);
		
		// Sort placements randomly
		usort($i_placements, "sort_random");

		$iteration_output[$iterations] .= '<h3>Iteration ' . ((($chunk_num - 1) * get_ITERATIONS()) + $iterations) . ' (Chunk ' . $chunk_num . '; Iteration ' . $iterations . ')' . '</h3>';
		$i_overall_happiness = 0;
		// run depending on priority
		foreach($priority_types as $key => $value)
		{
			$iteration_output[$iterations] .=  '<b>' . $value . '</b><br />';
			if($key == (count($priority_types) - 3))
			{
				// Sort placements by min_places
				usort($i_placements, "sort_by_open_min_places");
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
						$current_student_array[$current_student_count]->timeframes_unavailable = &$current_student->timeframes_unavailable;
						$current_student_array[$current_student_count]->deployments = &$current_student->deployments;
						$current_student_array[$current_student_count]->deployment_deduction = &$current_student->deployment_deduction;
						$current_student_array[$current_student_count]->karma = &$current_student->karma;
						$current_student_count = $current_student_count + 1;
					}
				}
				$iteration_output[$iterations] .= $current_placement->name . '. Time: ' . timestamp_to_german_date($current_placement->timeframe_begin). '-' . timestamp_to_german_date($current_placement->timeframe_end) . '; Available places: ' . $places_target;
				$iteration_output[$iterations] .= '; with ' . count($current_student_array) . ' Students eligable: ';
				foreach($current_student_array as $report_student)
				{
					$iteration_output[$iterations] .= 'ID:' . $report_student->id . ';';
				}
				// Calculate places

				if (empty($current_student_array))
				{ }
				elseif(($places_target < 1) || ($current_placement->places_max == 0))
				{ 
					$iteration_output[$iterations] .= '<br />But there are no placements open. Sorry guys!';
					if(in_array($key, get_PRIORITIES_AFFECTING_KARMA()) && !(count($deployment_placements[$current_placement->deployment]->placements) == 1))
					{
						foreach($current_student_array as $current_student)
						{
							if(in_array($key, $current_student->deployment_deduction[$current_placement->deployment]))
							{
								$iteration_output[$iterations] .= '<br /><i>ID:' . $current_student->id . ' gets a karma bonus of ' . get_BONUS_ROLL_PLACEMENT() . '</i>';
								$current_student->karma = ($current_student->karma + get_BONUS_ROLL_PLACEMENT());
								unset($current_student->deployment_deduction[$current_placement->deployment][$key]);
							}
						}
					}
				}		
				elseif (!(empty($current_student_array)) && !($places_target == 0) && ($places_target >= count($current_student_array)) && (!($key == (count($priority_types) - 2)) || ($places_target == count($current_student_array))) && (!($key == (count($priority_types) - 1)) || ($current_placement->places_min <= (count($current_placement->students_alloc) + count($current_student_array))) || ($current_placement->places_min <= count($current_student_array))))
				{
					$iteration_output[$iterations] .= '<br />Enough places for all eligable students. Students allocated: ';
					// Allocate student - delete keys from array
					foreach($current_student_array as $current_student)
					{
						$iteration_output[$iterations] .= 'ID:' . $current_student->id;
						unset($current_student->deployments[$current_placement->deployment]);
						$current_student->timeframes_unavailable[] = $current_placement->timeframe_begin . '::' . $current_placement->timeframe_end;
						$current_placement->students_alloc[] = $current_student->id;
						if(!($key == (count($priority_types) - 2) || $key == (count($priority_types) - 1)))
						{ $this_round_happiness = ($this_round_happiness + ((count($priority_types) - $key) * 10)); }
						if($key == 0) { $current_student->joker = ($current_student->joker - 1); $iteration_output[$iterations] .= ' <i>. He used a Joker in this round. So his Joker will be subtracted. Goodbye, Joker!;</i> '; }
						else
						{
							if(in_array($key, get_PRIORITIES_AFFECTING_KARMA()) && !(count($deployment_placements[$current_placement->deployment]->placements) == 1))
							{
								if(in_array($key, $current_student->deployment_deduction[$current_placement->deployment]))
								{
									$iteration_output[$iterations] .= '; His karma will therefore be reduced by ' . abs(get_DEDUCTION_ROLL_PLACEMENT());
									$current_student->karma = ($current_student->karma + get_DEDUCTION_ROLL_PLACEMENT());
								}
								else
								{							
									$iteration_output[$iterations] .= '; He already got a karma bonus in this round. His karma will therefore be reduced by ' . (2 * abs(get_DEDUCTION_ROLL_PLACEMENT()));
									$current_student->karma = ($current_student->karma + (2 * get_DEDUCTION_ROLL_PLACEMENT()));
								}	
								$iteration_output[$iterations] .= '; ';
							}
						}
					}
				}
				elseif($places_target < count($current_student_array))
				{
					$iteration_output[$iterations] .= '<br />Not enough places. Begin calculating luck:';
					// calculate luck
					$current_student_array = calculate_luck($current_student_array);
					// after luck calculation use sorted array to return the lucky winners
					for($i = 0; $places_target > $i; $i++)
					{
						$iteration_output[$iterations] .= '<br /><i>ID:' . $current_student_array[$i]->id . ' rolled the dice with a karma of ' . $current_student_array[$i]->karma .  '. He got lucky with ' . $current_student_array[$i]->calc_luck . ' points. Congratulations';
						// compare id to reset reference
						foreach($current_student_array as $current_student)
						{
							if($current_student_array[$i]->id == $current_student->id)
							{
								unset($current_student->deployments[$current_placement->deployment]);
								$current_student->timeframes_unavailable[] = $current_placement->timeframe_begin . '::' . $current_placement->timeframe_end;
								if(!($key == (count($priority_types) - 2) || $key == (count($priority_types) - 1)))
								{ $this_round_happiness = ($this_round_happiness + ((count($priority_types) - $key) * 10)); }
								// Reduce joker
								if($key == 0) { $current_student->joker = ($current_student->joker - 1); $iteration_output[$iterations] .= ' (used a Joker in this round. So his Joker will be subtracted)'; }
								else
								{
									// Reduce karma points							
									if(in_array($key, get_PRIORITIES_AFFECTING_KARMA()) && !(count($deployment_placements[$current_placement->deployment]->placements) == 1))
									{
										if(in_array($key, $current_student->deployment_deduction[$current_placement->deployment]))
											{
												$iteration_output[$iterations] .= '; His karma will therefore be reduced by ' . abs(get_DEDUCTION_ROLL_PLACEMENT());
												$current_student->karma = ($current_student->karma + get_DEDUCTION_ROLL_PLACEMENT());
											}
											else
											{							
												$iteration_output[$iterations] .= '; He already got a karma bonus in this round. His karma will therefore be reduced by ' . (2 * abs(get_DEDUCTION_ROLL_PLACEMENT()));
												$current_student->karma = ($current_student->karma + (2 * get_DEDUCTION_ROLL_PLACEMENT()));
											}	
										}
								}
								// Allocate student
								$current_placement->students_alloc[] = $current_student->id;
								$iteration_output[$iterations] .=  ';</i>';
							}
						}
					}
					while(count($current_student_array) > $i)
					{
						$iteration_output[$iterations] .= '<i><br />ID:' . $current_student_array[$i]->id . ' rolled the dice with a karma of ' . $current_student_array[$i]->karma .  '. He was unsuccesful with ' . $current_student_array[$i]->calc_luck . ' points. Sorry, mate.';
						if(in_array($key, get_PRIORITIES_AFFECTING_KARMA()) && !(count($deployment_placements[$current_placement->deployment]->placements) == 1))
							{
								if(in_array($key, $current_student_array[$i]->deployment_deduction[$current_placement->deployment]))
								{
									$iteration_output[$iterations] .= ' But he will get a Karma bonus of ' . get_BONUS_ROLL_PLACEMENT() . '; ';
									$current_student_array[$i]->karma = ($current_student_array[$i]->karma + get_BONUS_ROLL_PLACEMENT());
									unset($current_student_array[$i]->deployment_deduction[$current_placement->deployment][$key]);
								}	
							}
						$iteration_output[$iterations] .= '</i>';
						$i++;
					}			
				}
				else
				{
					$iteration_output[$iterations] .= '<br />Not enough students to fill minimum places - skipping this round.';
				}
				if(!($this_round_happiness == 0))
				{
					$i_overall_happiness = ($i_overall_happiness + $this_round_happiness);
					$iteration_output[$iterations] .= '<br /><i>The overall happiness has increased by ' . $this_round_happiness . ' in this round and is now ' . $i_overall_happiness . '</i>';
				}
				$iteration_output[$iterations] .= '<br />';
			} 
			$iteration_output[$iterations] .= '<br />';
		}

		$iteration_output[$iterations] .= '<b>RESULTS:</b>';
		foreach($i_placements as $current_placement)
		{
			$iteration_output[$iterations] .=  "<br />" . $current_placement->name . " (" . timestamp_to_german_date($current_placement->timeframe_begin). '-' . timestamp_to_german_date($current_placement->timeframe_end) . ")";
			$iteration_output[$iterations] .=  " Students: ";
			foreach($current_placement->students_alloc as $student_alloc)
			{
				$iteration_output[$iterations] .=  $student_alloc . "; ";
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
		if($i_missing_placements) 
		{
			$iteration_describer[$iterations] -> num_unallocated_students = count($iteration_describer[$iterations]->unallocated_students);
			$iteration_output[$iterations] .= $i_missing_placements_output; 
		}
		
		// Missing min_places
		$i_missing_min_places_output = '<br /><br /><b><u>Warning:</u> Placements with unallocated minumum places:</b><br />';
		$i_missing_min_places = FALSE;
		foreach($i_placements as $this_i_placement)
		{
			if(!(empty($this_i_placement->places_min)) && !(empty($this_i_placement->students_alloc)) && (count($this_i_placement->students_alloc) < $this_i_placement->places_min))
			{
				$i_missing_min_places = TRUE;
				$iteration_describer[$iterations] -> unallocated_min_places[$this_i_placement->id] = ($this_i_placement->places_min - count($this_i_placement->students_alloc)) ;
				$i_missing_min_places_output .= $this_i_placement->name . ' (' . timestamp_to_german_date($this_i_placement->timeframe_begin) . '-' . timestamp_to_german_date($this_i_placement->timeframe_end) . ') is missing ' . ($this_i_placement->places_min - count($this_i_placement->students_alloc)) . ' students.<br />';
			}
		}
		if($i_missing_min_places) 
		{ 
			$iteration_output[$iterations] .= $i_missing_min_places_output; 
			$iteration_describer[$iterations] -> num_unallocated_min_places = count($iteration_describer[$iterations]->unallocated_min_places);
		}	
		
		$iteration_output[$iterations] .= '<br />The overall happiness is: ' . $i_overall_happiness;
		
		// Insert tables
		$iteration_describer[$iterations] -> students =  $i_placement_student;
		$iteration_describer[$iterations] -> placements =  $i_placements;
	}

	// FINAL CALCULATIONS
	$report_output .= '%ITERATION_REPORT%<h1>Chunk Results:</h1>';
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
	else 
	{ 
		$report_output .= "<b>HOOOMANZ!</b> I haven't been able to calculate a table, where all students are allocated. :(. Removing iterations with more than minimal unallocated students. "; 
		$minimal_unallocated_students = min(return_array_by_key($iteration_describer, "unallocated_students"));
		foreach($iteration_describer as $iteration => $value)
		{
			if( $value->unallocated_students > $minimal_unallocated_students)
			{ $report_output .= $value->id . '; '; unset($iteration_describer[$iteration]); }
		}	
	}

	// Check if there is a calculation where all minimum places are allocated
	$report_output .= "<br /><br />Step 2: I'll try to come up with a table, where all minimum placements are allocated.<br />";
	$calc_min_places_allocated = FALSE;
	foreach($iteration_describer as $iteration) { if(empty($iteration->unallocated_min_places)) { $calc_min_places_allocated = TRUE;  } }
	// if there is such a calculation, remove all others
	if($calc_min_places_allocated)
	{
		$report_output .= 'There is at least one calculation where all minimum places are allocated. Removing all tables with minimum placements unset: ';
		foreach($iteration_describer as $iteration => $value)
		{ if(!(empty($value->unallocated_min_places))) { $report_output .= $value->id . '; '; unset($iteration_describer[$iteration]); } }
	}
	else 
	{ 
		$report_output .= "<b>HOOOMANZ!</b> I haven`t been able to calculate a table, where all minimum places are allocated. :(. Deleting all iterations with more than the minimum unallocated students: "; 
		$minimal_unallocated_min_places = min(return_array_by_key($iteration_describer, "unallocated_min_places"));
		foreach($iteration_describer as $iteration => $value)
		{
			if( $value->unallocated_min_places > $minimal_unallocated_min_places)
			{ $report_output .= $value->id . '; '; unset($iteration_describer[$iteration]); }
		}
	}

	// Step 3 order by happiness factor
	$report_output .= "<br /><br />Step 3: I'll select the table with the highest happiness factor.<br />";
	usort($iteration_describer, "sort_by_happiness");
	$report_output .= "Our winner is: " . $iteration_describer[0]->id . " with a happiness factor of " . $iteration_describer[0]->overall_happiness ."<br />Noticeable others:";
	for($i = 1; (count($iteration_describer)) > $i; $i++)
	{
	$report_output .= "<br />Number " . ($i + 1) . " : " . $iteration_describer[$i]->id . " with a happiness factor of " . $iteration_describer[$i]->overall_happiness;
	}
	$iteration_describer[0]->report_output = $report_output;
	$iteration_describer[0]->iteration_output = $iteration_output[$iteration_describer[0]->id];
	return $iteration_describer[0];
}

function check_chunks($multiplied_iteration)
{
	$calc_all_allocated = FALSE;
	foreach($multiplied_iteration as $iteration) { if(empty($iteration->unallocated_students)) { $calc_all_allocated = TRUE; } }
	// if there is such a calculation, remove all others
	if($calc_all_allocated)
	{
		foreach($multiplied_iteration as $iteration => $value)
		{ if(!(empty($value->unallocated_students))) { unset($multiplied_iteration[$iteration]); } }
	}
	else
	{
		$minimal_unallocated_students = min(return_array_by_key($multiplied_iteration, "unallocated_students"));
		foreach($multiplied_iteration as $iteration => $value)
		{
			if( $value->unallocated_students > $minimal_unallocated_students)
			{ unset($multiplied_iteration[$iteration]); }
		}
	}		

	// Check if there is a calculation where all minimum places are allocated
	$calc_min_places_allocated = FALSE;
	foreach($multiplied_iteration as $iteration) { if(empty($iteration->unallocated_min_places)) { $calc_min_places_allocated = TRUE; } }
	// if there is such a calculation, remove all others
	if($calc_min_places_allocated)
	{
		foreach($multiplied_iteration as $iteration => $value)
		{ if(!(empty($value->unallocated_min_places))) { unset($multiplied_iteration[$iteration]); } }
	}
	else
	{
		$minimal_unallocated_min_places = min(return_array_by_key($multiplied_iteration, "unallocated_min_places"));
		foreach($multiplied_iteration as $iteration => $value)
		{
			if( $value->unallocated_min_places > $minimal_unallocated_min_places)
			{ unset($multiplied_iteration[$iteration]); }
		}
	}	
	usort($multiplied_iteration, "sort_by_happiness");
	return $multiplied_iteration[0];
}

?>
