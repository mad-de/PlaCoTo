<?php

$module_output = 'Placements:<br />';

$placements = fetch_placement_list();

foreach($placements as $act => $placement)
{
$module_output .= $placement->NAME . ': <a href="index.php?act=enrol&id=' . $placement->ID . '">ENROL</a> <a href="index.php?act=edit_choices&id=' . $placement->ID . '">EDIT CHOICES</a><br />';
}

?>