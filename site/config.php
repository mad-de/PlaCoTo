<?php

// Specify Database path (should be hidden from your users)
define("DB_PATH", "../database");
// Leave public key empty if you don`t want to use a Recaptcha Captcha
define("RECAPTCHA_SECRET_KEY", "");
define("RECAPTCHA_PUBLIC_KEY", "");
define("WEBSITE_NAME", "PlaCoTo");
define("WEBSITE_URL", "example.com");
define("EMAIL_SENDER", "mail@example");
define("COSTUM_TIMEFRAME_MAX", 7);

define("DEDUCTION_ROLL_PLACEMENT", -1);
define("DEDUCTION_CUSTOM_TIMEFRAME", -2);
define("BONUS_REMAINING_ROUND", +3);

define("MAX_RUNTIME", 55);
define("ITERATIONS", 500);
define("ITERATION_MULTIPLIER", 5);

// Permitted special chars
define ("EMAIL_SPECIAL_CHARS", serialize(array('@', '.', '_', '-')));
define ("PLACEMENT_SPECIAL_CHARS", serialize(array(' ', '.', '_', '-', '(', ')', ':', 'Ä', 'ä', 'Ü', 'ü', 'Ö', 'ö', 'ß', '/')));

$DEBUG = FALSE;
$priority_types = array(1 => "PRIORITY 1", 2 => "PRIORITY 2", 3 => "PRIORITY 3");
define ("PRIORITIES_AFFECTING_KARMA", serialize(array('0', '1')));

// DO NOT CHANGE ANYTHING BEYOND THIS LINE

function get_DB_PATH() { return DB_PATH; }
function get_RECAPTCHA_SECRET_KEY() { return RECAPTCHA_SECRET_KEY; }
$get_RECAPTCHA_PUBLIC_KEY = 'fn_get_RECAPTCHA_PUBLIC_KEY';
function fn_get_RECAPTCHA_PUBLIC_KEY() { return RECAPTCHA_PUBLIC_KEY; }
function get_WEBSITE_URL() { return WEBSITE_URL; }
function get_WEBSITE_NAME() { return WEBSITE_NAME; }
function get_EMAIL_SENDER() { return EMAIL_SENDER; }
function get_COSTUM_TIMEFRAME_MAX() { return COSTUM_TIMEFRAME_MAX; }
function get_DEDUCTION_ROLL_PLACEMENT() { return DEDUCTION_ROLL_PLACEMENT; }
function get_DEDUCTION_CUSTOM_TIMEFRAME() { return DEDUCTION_CUSTOM_TIMEFRAME; }
function get_BONUS_REMAINING_ROUND() { return BONUS_REMAINING_ROUND; }
function get_MAX_RUNTIME() { return (MAX_RUNTIME); }
function get_ITERATIONS() { return ITERATIONS; }
function get_ITERATION_MULTIPLIER() { return ITERATION_MULTIPLIER; }
function get_EMAIL_SPECIAL_CHARS() { return unserialize(EMAIL_SPECIAL_CHARS); }
function get_PLACEMENT_SPECIAL_CHARS() { return unserialize(PLACEMENT_SPECIAL_CHARS); }
function get_PRIORITIES_AFFECTING_KARMA() { return unserialize(PRIORITIES_AFFECTING_KARMA); }

?>