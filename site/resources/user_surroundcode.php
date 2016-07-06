<?php

$admin_link = '';
$this_user = fetch_student_by_login($_SERVER['PHP_AUTH_USER']);
$this_username = $this_user->LOGIN;
$this_karma = $this_user->KARMA;
$this_joker = $this_user->JOKER;
if($this_user->STATUS == "ADMIN")
{ $admin_link = ' | <a href="admin.php" target="_blank">AdminCP</a>'; }
$logout_link = '<a href="http://log:out@' . get_WEBSITE_URL() . '/">Logout</a>';
$title = '<title>' . get_WEBSITE_NAME() . '</title>';
if(isset($_GET["act"]) && $_GET["act"] == "show_wishes")
{ $header = ''; }
else
{
	$header = <<< EOT
	<div id="head">
	<a href="index.php"><img class="home_button" src="/images/home.svg" alt="home" ></a> <b>{$this_username}</b> ({$logout_link}{$admin_link})
	<span class="stats">Karma: {$this_karma} Joker: {$this_joker}</span></div>
EOT;
}
$html_output = <<< EOT
<!DOCTYPE html>
<head>
<meta charset="utf-8"/>
{$title}
<link rel="stylesheet" href="css/user_general.css">
</head>
<body>
{$header}
<div id="content">
{$module_output}
</div>
</body>
EOT;

?>
