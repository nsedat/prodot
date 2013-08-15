<?php

require_once('config.inc.php');
require_once('functions.inc.php');

//error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") _REQUEST='" . my_print_r($_REQUEST, true));

// test
if (false)
{
	if (checkset($xxx))
	{
		error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ")xxx is settrue");
	}
	else
	{
		error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ")xxx is not set");
	}
	if (checkset($prindot['hotfix']['imagemagick']['colorspace']))
	{
		error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ")prindot['hotfix']['imagemagick']['colorspace'] is settrue");
	}
	else
	{
		error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ")prindot['hotfix']['imagemagick']['colorspace'] is not set");
	}
}

echo json_encode($prindot);

//$x = array("a"=>"b");
//echo json_encode($x);

?>