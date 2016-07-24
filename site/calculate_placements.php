<?php
// Set Debugging Level
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

include "config.php";
include "resources/json_functions.php";

$time_begin = microtime(true);
print date('d.m.Y-H:i:s:', time()) . ' Starting script.<br />';
$placement_list = fetch_placement_list();
// fetch !1! placement - assume last one in database gets calculated first
if(empty($placement_list))
{ die("Placement list is empty."); }
$placement_id = "";
foreach($placement_list as $this_placement_list)
{
	if($this_placement_list->DUE_DATE <= time() && !(is_dir(get_DB_PATH() . DIRECTORY_SEPARATOR . 'calculation_' . $this_placement_list->ID . DIRECTORY_SEPARATOR)))
	{ $placement_id = $this_placement_list->ID; $placement_name = $this_placement_list->NAME; }
}
if(empty($placement_id)) { die("Nothing to calculate."); }

ini_set('memory_limit', '-1');
set_time_limit(-1);
ignore_user_abort(true);

// Create folder first to lock any following attempts on calculating or DDOSing our script
if(!is_dir(get_DB_PATH() . DIRECTORY_SEPARATOR . 'calculation_' . $placement_id . DIRECTORY_SEPARATOR))
{ mkdir(get_DB_PATH() . DIRECTORY_SEPARATOR . 'calculation_' . $placement_id . DIRECTORY_SEPARATOR, 0777, true); }

include "resources/functions_calculate_placements.php";

// fetch wishlist & students table
$wishlist_table = fetch_json_table('wishlist_' . $placement_id . '.json');
$student_table = fetch_json_table('students.json');
$placement_table = fetch_json_table('placement_' . $placement_id . '.json');

// Backup students.json
file_put_contents(get_DB_PATH() . DIRECTORY_SEPARATOR . 'calculation_' . $placement_id . DIRECTORY_SEPARATOR . 'students_backup.json', json_encode($student_table)) or die("Backing up students.json failed. Aborting.");

$priority_types[] = "LOCATION";

$placements = get_placements($placement_table);
$deployments = filter_deployments($placements);
$deployment_placements = return_placement_deployments($deployments, $placements);
$wishlist_table = set_priorities_special_deployments($deployment_placements, $wishlist_table, $priority_types);

$placement_student = combine_wishlist_and_student_table($wishlist_table, $student_table);

// insert JOKER at beginning of priority types array
array_unshift($priority_types, "JOKER");
$multiplied_iteration = array();
for($i = 1, $iteration_multiplier = get_ITERATION_MULTIPLIER(), $chunk_output = ""; $iteration_multiplier >= $i && (!(microtime(true) > ($time_begin + get_MAX_RUNTIME())) || get_MAX_RUNTIME() == 0); $i++)
{	
	print date('d.m.Y-H:i:s:', time()) . " Begin chunk " . $i . " (chunk size: " . get_ITERATIONS() . ")<br />";
	$multiplied_iteration[$i] = calculate_chunk($placement_student, $placements, $priority_types, $i); 
	$chunk_output .= '<br />Chunk ' . $i . ' with ' . get_ITERATIONS() . ' iterations. Unallocated students: ' . count($multiplied_iteration[$i]->unallocated_students) . '; Unallocated min placements: ' . count($multiplied_iteration[$i]->unallocated_min_places) . '; Happiness maximum: ' . $multiplied_iteration[$i]->overall_happiness . ';';
}
$result_table = check_chunks($multiplied_iteration);
$result_table->report_output .= '<br /><br />Step 4: Find chunk with highest happiness iteration:' . $chunk_output;

$students_by_id = sort_students_by_id($student_table);
$result_table->placements = replace_id_with_name($result_table->placements, $students_by_id);

$result_table->report_output .= '<br /><br />Step 5: I will upload the calculated placements file for your convenience: <a href="http://' . $_SERVER['HTTP_HOST'] . '/index.php?act=data_export&id=' . $placement_id . '">Download xls</a>';
insert_calculation_file($placement_id, "placements", $result_table) or $result_table->report_output .= "<br /><b>HOOOMANZ!</b> I haven`t been able to upload the placement file. :(";
// Insert Joker and Karma + Joker values into students table
foreach($result_table->students as $this_student)
{
	$students_by_id[$this_student->id]["KARMA"] = $this_student->karma; 
	$students_by_id[$this_student->id]["JOKER"] = $this_student->joker; 
}

$report_file = 'reports' . DIRECTORY_SEPARATOR . 'report_' . $placement_id . '.html';

if(empty($result_table->unallocated_students) && empty($result_table->unallocated_min_places))
{
	$result_table->report_output .= "<br /><br />Step 6: Replacing the old students table with the new one & Sending emails to students."; 
	print date('d.m.Y-H:i:s:', time()) . " Saving updated students.json<br />";
	file_put_contents(get_DB_PATH() . DIRECTORY_SEPARATOR . 'students.json', json_encode($students_by_id)) or die("Replacing the old students table FAILED.");  
	inform_students_via_email($result_table->students, $students_by_id, $result_table->placements, $placement_name, $report_file) or $result_table->report_output .= " ERROR informing students via email."; 
}
else
{
	$result_table->report_output .= "<br />There were errors while calculating. I will save a copy of the calculated students table in the calculation folder without replacing the stundents database.<br />";
	print date('d.m.Y-H:i:s:', time()) . " Errors calculating - Sending emails to admins<br />";
	insert_calculation_file($placement_id, "students_new", $students_by_id) or $result_table->report_output .= "<br /><b>HOOOMANZ!</b> I haven`t been able to upload the students file. :("; 
	send_admin_email("There were errors calculating " . $placement_name , 'Dear Admin,<br /><br />I was unable to calculate a good table for the students. <br />Check the calculation:<br /><a href="http://' . $_SERVER['HTTP_HOST'] . '/' . $report_file . '">Report</a><br /><br />Current report:<br />' . $result_table->report_output);
}			

$time_end = microtime(true) - $time_begin; 
$result_table->report_output .= '<br /><br /><b>RESULTS:</b>' . result_placement_report($result_table->placements) . '<br /><br />A total of ' . (($i - 1) * get_ITERATIONS()) . ' iterations have been calculated.<br /><b>Ok, that`s all. I`m done for today.</b> Calculation took ' . $time_end . ' Seconds';
upload_report($result_table->report_output, $result_table->iteration_output, $report_file);

?>
