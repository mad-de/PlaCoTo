<?php
$module_output = 'Your placements:<br /><br />';

$this_student = fetch_student_by_login($_SERVER['PHP_AUTH_USER']);

$placements = fetch_placement_list();

// TODO: If active ü folder exists - Old placements. if active and folder doesnt exist - current placements. if not active, dont show. 
foreach($placements as $act => $placement)
{
	$eligable_groups = array();
	$eligable_groups = explode(";", $placement->GROUPS);
	if((in_array($this_student->GROUP, $eligable_groups)) && ($placement->ACTIVE == 1))
	{
		$module_output .= $placement->NAME;
		if(is_dir(get_DB_PATH() . DIRECTORY_SEPARATOR . 'calculation_' . $placement->ID . DIRECTORY_SEPARATOR))
		{
			$module_output .= '( <a href="index.php?act=data_export&id=' . $placement->ID . '">View placement</a> )<br />';					
		}
		elseif(!(fetch_user_wishes($this_student->ID, $placement->ID) === FALSE))
		{
			$module_output .= '( <a href="index.php?act=edit_choices&id=' . $placement->ID . '">EDIT CHOICES</a> | <a href="#" onClick="MyWindow=window.open(' . "'http://"  . $_SERVER['HTTP_HOST'] . '/index.php?act=show_wishes&id=' . $placement->ID . "','MyWindow',width=350,height=500); return false;" . '">Total wishes</a> )<br />';		
		}
		else
		{
			 $module_output .= '( <a href="index.php?act=enrol&id=' . $placement->ID . '">ENROL</a> | <a href="#" onClick="MyWindow=window.open(' . "'http://"  . $_SERVER['HTTP_HOST'] . '/index.php?act=show_wishes&id=' . $placement->ID . "','MyWindow',width=350,height=500); return false;" . '">Total wishes</a> )<br />';
		}
	}
}
?>
