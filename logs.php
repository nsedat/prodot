<?php

// http://localhost/prindot/logs.php?action=create
// _REQUEST:
// action=create
//			von GUI : ...
//			von Maschine : ...
//	{"LID": 1,"JID": 1,"MID": "1234-56-7890-abc","HID": 0,"timestamp": "2013-03-06 16:42:49","type": 0,"subtype": 0,"description": ""}
// LID autoincrement
// timestamp autoset
// all other mandatory (even if left blank/0)

require_once('config.inc.php');
require_once('functions.inc.php');
require_once('connect2db.php');

function logs($REQUEST) {
	global $prindot;
	global $db;

	$debuglog = false;
	$debuglogfirst = false;
	$debugloglast = false;
	$errorlog = false;

	if ($debuglogfirst) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") _REQUEST='" . my_print_r($REQUEST) . "'");

	$function = true; // prevent calling included functions below ...:
	require_once($prindot['urls']['jobs']);
	require_once($prindot['urls']['machines']);

	$action = @$REQUEST['action'];
	if (isset($REQUEST['action']))
		unset($REQUEST['action']);
	$answer = array();
	$answer['result'] = 'success';

	switch ($action)
	{
	case 'create':	// create log entry by GUI or backend
		if (!isset($REQUEST['JID']) || !isset($REQUEST['MID']) || !isset($REQUEST['HID']) || !isset($REQUEST['type']) || !isset($REQUEST['subtype']) || !isset($REQUEST['description'])) {
			$answer['result'] = 'error';
			$answer['reason'] = 'Error logs-1 : missing parameter';
			if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
			goto ende;
		}

		try {
			$remoteaddr = $_SERVER["REMOTE_ADDR"];
			$remoteport = $_SERVER["REMOTE_PORT"];
			if ($remoteport != "80") {
				$remoteaddr .= ":" . $remoteport;
			}
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") remoteaddr='" . $remoteaddr . "'");
			$REQUEST['remoteaddr'] = $remoteaddr;
			if (isset($REQUEST['timestamp']))	unset($REQUEST['timestamp']);
			$sql = "INSERT INTO `" . $prindot['database']['tablenames']['logs'] . "` SET ";
			$sql .= sqlcreatesetstringfromarray($REQUEST, $prindot['database']['tablekeys']['logs']);
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") sql='" . my_print_r($sql) . "'");
			$q = $db->prepare($sql);
			$q->execute();
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "'");
			if ($c == 0) {
				$answer['result'] = 'error';
				$answer['reason'] = 'Error logs-2 : no log entry written - serious ...';
				if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
				goto ende;
			}
		} catch (PDOException $ex) {
			$answer['result'] = 'error';
			$answer['reason'] = 'Error logs-3 : fetched exception : ' . $ex->getMessage();
			if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
			goto ende;
		}
		break;

	case 'status':
		if (isset($REQUEST['JID']) && $REQUEST['JID'] != '') {
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") call jobs with status");
			$atos = array('action' => 'setstatus', 'JID' => @$REQUEST['JID'], 'status' => @$REQUEST['status']);
			$server_output = calltarget('jobs', $atos);
			$ra = json_decode($server_output, true);
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") calltarget RETURN='" . my_print_r($ra) . "'");
			if ($ra['result'] != 'success') {
				$answer['result'] = 'error';
				$answer['reason'] = 'Error logs-4 : cannot update status in jobs database (' . @$ra['reason'] . ')';
				if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
				goto ende;
			}
		}
// JID in machine MID setzen (verbinden)
// nur von calcstrand aus !
if (@$REQUEST['status'] == JOBSTATUS_CALCSTRAND)
{
		if (isset($REQUEST['MID']) && $REQUEST['MID'] != '') {
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") call machines set jid");
			$atos = array('action' => 'setjid', 'MID' => @$REQUEST['MID'], 'JID' => @$REQUEST['JID']);
			$server_output = calltarget('machines', $atos);
			$ra = json_decode($server_output, true);
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") calltarget RETURN='" . my_print_r($ra) . "'");
			if ($ra['result'] != 'success') {
				$answer['result'] = 'error';
				$answer['reason'] = 'Error logs-5 : cannot set JID in machines database (' . @$ra['reason'] . ')';
				if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
				goto ende;
			}
		}
}

		break;

	case 'setmachinestatus':
		if (isset($REQUEST['MID']) && $REQUEST['MID'] != '') {
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") call machines with status");
			$server_output = calltarget('machines', array('action' => 'setstatus', 'MID' => @$REQUEST['MID'], 'status' => @$REQUEST['status']));
			$ra = json_decode($server_output, true);
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") calltarget RETURN='" . my_print_r($ra) . "'");
			if ($ra['result'] != 'success') {
				$answer['result'] = 'error';
				$answer['reason'] = 'Error logs-9 : cannot update status in machines database (' . @$ra['reason'] . ')';
				if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
				goto ende;
			}
		}

		break;

	case 'progress':
		if (isset($REQUEST['JID']) && $REQUEST['JID'] != '') {
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") call jobs with progress");
			$atos = array('action' => 'setprogress', 'JID' => @$REQUEST['JID'], 'progress' => @$REQUEST['progress']);
			$server_output = calltarget('jobs', $atos);
			$ra = json_decode($server_output, true);
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") calltarget RETURN='" . my_print_r($ra) . "'");
			if ($ra['result'] != 'success') {
				$answer['result'] = 'error';
				$answer['reason'] = 'Error logs-6 : cannot update progress in jobs database (' . @$ra['reason'] . ')';
				if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
				goto ende;
			}
		}
		break;

	default:
		$answer['result'] = 'error';
		$answer['reason'] = 'Error logs-8 : unknown or missing action';
		if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
		goto ende;
		break;
	}

ende:
	if ($debugloglast) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") _REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
	return json_encode($answer);
}


// hier json mit Antwort zurueck
// result: "success" | "error" | "warning"
// bei "success" wird optional value zurueckgegeben (array)
// bei "error" oder "warning" zusaetzlich noch reason: "blablabl"
//if (!isset($function) || ($function == false)) {
if (!isset($function)) {
	echo(logs($_REQUEST));
}
?>
