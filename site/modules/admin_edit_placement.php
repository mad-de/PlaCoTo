<?php
		$module_output = "HALLO";
	$placements = fetch_placements($id);
	print_r($placements);
	foreach($placements as $this_placement)
	{
		$module_output = $this_placement->ID . '<br />';
	}
?>