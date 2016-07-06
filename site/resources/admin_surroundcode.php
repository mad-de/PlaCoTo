<?php


include "resources/math.php";
$calc_students = fetch_students();
$median_karma = calculate_median($calc_students, "KARMA");
$average_karma = calculate_average($calc_students, "KARMA");

$html_output = <<< EOT
	<!DOCTYPE html>
<head>
<meta charset="utf-8"/>
</head>
<body>
<p>Hello {$_SERVER['PHP_AUTH_USER']}. Median Karma: {$median_karma}; Average Karma: {$average_karma}</p>
{$module_output}
</body>
EOT;

?>