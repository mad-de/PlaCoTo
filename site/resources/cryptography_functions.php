<?php

// Return random Hexcode - random_bytes is a PHP 7 function. You can change that to something else for backwards compatibility 
function return_random_hexadecimal($length)
{
	$bytes = random_bytes($length*3);
	$string = bin2hex($bytes);
	return substr($string, rand(0, (strlen($string)-$length)), $length); 
}

// Return random string - random_int is a PHP 7 function. You can change that to something else for backwards compatibility 
function return_random_chars($length)
{
	$code = "";
	$chars = array(1 => "a", 2 => "b", 3 => "c", 4 => "d", 5 => "e", 6 => "f", 7 => "g", 8 => "h", 9 => "i", 10 => "j", 11 => "k",
	12 => "l", 13 => "m", 14 => "n", 15 => "o", 16 => "p", 17 => "q", 18 => "r", 19 => "s", 20 => "t", 21 => "u", 22 => "v", 23 => "w",
	24 => "x", 25 => "y", 26 => "z", 27 => "A", 28 => "B", 29 => "C", 30 => "D", 31 => "E", 32 => "F", 33 => "G", 34 => "H", 35 => "I",
	36 => "J", 37 => "K", 38 => "L", 39 => "M", 40 => "N", 41 => "O", 42 => "P", 43 => "Q", 44 => "R", 45 => "S", 46 => "T", 47 => "U", 
	48 => "V", 49 => "W", 50 => "X", 51 => "Y", 52 => "Z", 53 => "~", 54 => "!", 55 => "@", 56 => "#", 57 => "$", 58 => "%", 59 => "^", 
	60 => "&", 61 => "*", 62 => "(", 63 => ")", 64 => "_", 65 => "-", 65 => "=", 66 => "+", 67 => "{", 68 => "[", 69 => "}", 70 => "]", 
	71 => ":", 72 => ";", 73 => ",", 74 => "?", 75 => ".");	
	$count = 0;
	while ($count < $length)
	{
		$int = random_int(1, 75);
		$code .= $chars[$int];
		$count++;
	}		
	return $code; 
}

?>