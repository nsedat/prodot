<?php

//if (!isset($_SERVER["REQUEST_SCHEME"]))
//{
//$_SERVER["REQUEST_SCHEME"] = strtok($_SERVER["SERVER_PROTOCOL"], '/');
//}
//
//$logsURL = '' . $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . dirname($_SERVER["SCRIPT_NAME"]) . '/' . $prindot['urls']['logs'];
//if ($debuglog)	error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") logsURL='" . $logsURL . "'");
//$jobsURL = '' . $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . dirname($_SERVER["SCRIPT_NAME"]) . '/' . $prindot['urls']['jobs'];
//if ($debuglog)	error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") jobsURL='" . $jobsURL . "'");
//$machinesURL = '' . $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . dirname($_SERVER["SCRIPT_NAME"]) . '/' . $prindot['urls']['machines'];
//if ($debuglog)	error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") machinesURL='" . $machinesURL . "'");

function callcurl($targetURL, $values)
{
	$debuglog = false;
//	$targetURL = '' . $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . dirname($_SERVER["SCRIPT_NAME"]) . '/' . $prindot['urls'][$target];
	if ($debuglog)	error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ")" . "' : " . "targetURL='" . $targetURL . "' values='" . my_print_r($values));

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $targetURL);
	curl_setopt($ch, CURLOPT_POST, 1);
	//curl_setopt($ch, CURLOPT_POSTFIELDS, "postvar1=value1&postvar2=value2&postvar3=value3");
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($values));
	// receive server response ...
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec($ch);
	if ($server_output === false)
	{
		$server_output = curl_error($ch);
	}
	curl_close($ch);

	return $server_output;
}

function callcurl_json($targetURL, &$values)
{
	$debuglog = false;
//	$targetURL = '' . $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . dirname($_SERVER["SCRIPT_NAME"]) . '/' . $prindot['urls'][$target];
	if ($debuglog)	error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ")" . "' : " . "targetURL='" . $targetURL . "' values='" . my_print_r($values));

//	$json = http_build_query(json_encode($values));
	$json = json_encode($values);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $targetURL);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POST, 1);
	//curl_setopt($ch, CURLOPT_POSTFIELDS, "postvar1=value1&postvar2=value2&postvar3=value3");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	// receive server response ...
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($json)));
	$server_output = curl_exec($ch);
	if ($server_output === false)
	{
		$server_output = curl_error($ch);
	}
	else
	{
//		$jsonStr = file_get_contents("php://input"); // read the HTTP body. (json)
//		$server_output = json_decode($server_output, true);	// convert json string to array
	}
	curl_close($ch);

	return $server_output;
}

function calltargetcurl($target, $values)
{
	global $prindot;
	global $debuglog;
	$targetURL = '' . $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . dirname($_SERVER["SCRIPT_NAME"]) . '/' . $prindot['urls'][$target];
	if ($debuglog)	error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") _REQUEST='" . my_print_r($REQUEST) . "' : " . "targetURL='" . $targetURL . "'");

	return callcurl($targetURL, $values);
}


function my_print_r($val)
{
	$r = array("\r\n", "\r", "\n", "  ", "\t");
	$v = print_r($val, true);
	$mv = str_replace($r, ' ', $v);
	return $mv;
}

// TODO: here and above: use variable parameter lists as in error_result() below!
function calltarget($target, $values)
{
	$debuglog = false;
	if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") calling target='" . $target . "' with values='" . my_print_r($values) . "'...");
	$server_output = $target($values);

// TODO: why not do json_decode(, true) here and give array back ?
	return $server_output;
}

/**
 * checks if variable is defined and set true
 * @param $v - variable to check
 * @return bool
 */
function checkset(&$v)
{
	if (isset($v) && $v == true) {
		return true;
	} else {
		return false;
	}
}

/**
* escapes like mysql_real_escape_string()
*
* @param mixed $str
*/
function myescape($str)
{
	$search=array("\\","\0","\n","\r","\x1a","'",'"');
	$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
	return str_replace($search,$replace,$str);
}

/**
* create SQL set string (`key`='val')
*
* @param mixed $key
* @param mixed &$val
* @param mixed $appendflag
*/
function sqlkv($key, &$val, $appendflag=false)
{
	$s = '';
	if (isset($val)) {
		if ($appendflag) {
			$s .= ", ";
		}
		if (strpos($key, '.') !== false) {	// do not enquote normalized keys (eg. "J.JID")
			$s .= "$key='".myescape($val)."'";
		} else {
			$s .= "`$key`='".myescape($val)."'";
		}
	}
	return $s;
}

/**
* create SQL set string (`key`='val')
* no check of val
*
* @param mixed $key
* @param mixed $val
* @param mixed $appendflag
*/
function sqlkvn($key, $val, $appendflag=false)
{
	$s = '';
	if ($appendflag) {
		$s .= ", ";
	}
	if (strpos($key, '.') !== false) {	// do not enquote normalized keys (eg. "J.JID")
		$s .= "$key='".myescape($val)."'";
	} else {
		$s .= "`$key`='".myescape($val)."'";
	}
	return $s;
}

/**
* create SQL set string by dereferencing array and key
*
* @param mixed $arr
* @param mixed $key
* @param mixed $appendflag
*/
function sqlsetstringA(&$arr, $key, $appendflag=false)
{
	return sqlkv($key, $arr[$key], $appendflag);
}

/**
* create set strings from array
*
* @param mixed $a
* @param mixed $allowedkeys
*/
function sqlcreatesetstringfromarray(&$a, &$allowedkeys)
{
	$sql = '';
	$komma = false;
	foreach ($a as $k => $v)
	{
		if (in_array($k, $allowedkeys))
		{
			$s = sqlsetstringA($a, $k, $komma);
			$sql .= $s;
			if (!$komma && $s != '')
			{
				$komma = true;
			}
		}
	}
	return $sql;
}

/**
* create filename for strands preview with given JID
*
* @param mixed $JID job id
*/
function create_strands_preview_filename($JID)
{
	global $prindot;
	return sprintf($prindot['filemask']['strand'] . $prindot['fileextensions']['strandpreview'], $JID);
}

/**
* create filename for strands raw file with given JID
*
* @param mixed $JID job id
*/
function create_strands_raw_filename($JID)
{
	global $prindot;
	return sprintf($prindot['filemask']['strand'] . $prindot['fileextensions']['strand'], $JID);
}

/**
* create filename for strands xml job file with given JID
*
* @param mixed $JID job id
*/
function create_strands_job_filename($JID)
{
	global $prindot;
	return sprintf($prindot['filemask']['strand'] . $prindot['fileextensions']['jobxml'], $JID);
}

/**
* create answer array as error with given string
*
* @param array of string and possible parameters for reason
*/
function error_result(&$array)
{
	$arg_list = func_get_args();
	array_shift($arg_list);
	$str = array_shift($arg_list);
	$array['result'] = 'error';
	$array['reason'] = vsprintf($str, $arg_list);
}

/**
* append answer &array as error with given string
*
* @param array of string and possible parameters for reason
*/
function error_result_a(&$array)
{
	$arg_list = func_get_args();
	array_shift($arg_list);
	$str = array_shift($arg_list);
	$array['result'] = 'error';
	$array['reason'] .= vsprintf($str, $arg_list);
}

function l10n($default, $key=null)
{
	global $prindot;

	if (($key != null) && (isset($prindot['l10n'][$key]))) {
		return $prindot['l10n'][$key];
	} else {
		return $default;
	}
}

?>
