<?php
$time_begin = microtime(true);
if(isset($_GET["debug"]) || $DEBUG)
{
	// Set Debugging Level
	ini_set('display_errors', 'On');
	error_reporting(E_ALL | E_STRICT); 
}
include "config.php";
include "resources/json_functions.php";
include "resources/auth.php";

$error = "";

// info site without login credentials
if(!(isset($_SERVER['PHP_AUTH_USER'])) && !(isset($_GET["act"]))) { include "modules/landing.php"; }
elseif((isset($_GET["act"])) && ($_GET["act"] == "register_user")) { include "modules/user_register.php"; }
else
{
	check_user_login(TRUE);
	$error = "";

	$acts = array( 'show_users' => 'resources/show_users.php', 
	'main' => 'modules/user_main.php',
	'show_placements' => 'modules/user_show_placements.php', 
	'enrol' => 'modules/user_enrol.php',
	'edit_choices' => 'modules/user_edit_choices.php',
	'show_wishes' => 'modules/user_show_wishes.php',
	'data_export' => 'modules/data_export.php'
	 );

	if(isset($_GET["act"]) && array_key_exists($_GET["act"], $acts)) { include($acts[$_GET["act"]]); }
	else { include('modules/user_main.php'); }
	include('resources/user_surroundcode.php');
}
echo $html_output;
if(isset($_GET["debug"]) || $DEBUG)
{
	$time_total_runtime = microtime(true) - $time_begin; 
	echo "<br /><br />Total calculation time: " . $time_total_runtime . " seconds.";
}
?>