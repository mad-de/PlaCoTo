<?php

// Set Debugging Level
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

$time_begin = microtime(true); 

include "config.php";
include "resources/json_functions.php";
include "resources/auth.php";

check_user_login(FALSE);
		
$acts = array( 'edit_users' => 'modules/admin_edit_users.php', 
'create_placement' => 'modules/admin_create_placement.php',   
'edit_placement_list' => 'modules/admin_edit_placement_list.php', 
'add_placements' => 'modules/admin_add_placements.php', 
'edit_placements' => 'modules/admin_edit_placements.php', 
'activate_placements' => 'modules/admin_activate_placements.php',
'activate_user' => 'modules/admin_activate_user.php', 
'main' => 'modules/admin_main.php', 
 );
$error = "";

if(isset($_GET["act"]) && array_key_exists($_GET["act"], $acts)) { include($acts[$_GET["act"]]); }
else { include('modules/admin_main.php'); }

include('resources/admin_surroundcode.php');
echo $html_output;
$time_total_runtime = microtime(true) - $time_begin; 
echo "<br /><br />Total calculation time: {$time_total_runtime} seconds.";

?>
