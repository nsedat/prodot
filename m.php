<?php

//
// dummy machine to receive job start/stop commands
// will receive POST-Array or JSON with:
// "action" => "start"
// "xmlfilename" => filename of the xml job file (path is part of your config)
//
// or (to cancel a running job):
// "action" => "stop"
// "JID" => "25" (the specified JobID from started xml - running job)
// "MID" => "123-abcde-765" (the MID to validate)
//
// also implements init, update, setstatus and heartbeat
//
// should return JSON encoded "result":"success" (or in case of an error "result":"error", "reason":"blabla") [for external start/stop calls]
//
// @date: 2013-07-24
//

require_once('config.inc.php');	// load all defaults
require_once('functions.inc.php');	// load some helper functions

	// debug logging to php error_log ...
$debuglog = false;
$debuglogfirst = true;
$debugloglast = true;


	//
	// Inits
	//
if (!isset($_SERVER["REQUEST_SCHEME"]))
{	// get/define request scheme (http/https)
	$_SERVER["REQUEST_SCHEME"] = strtok($_SERVER["SERVER_PROTOCOL"], '/');
}
$baseURLpath = '' . $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . dirname($_SERVER["SCRIPT_NAME"]) . '/' ;
$logsURL = $baseURLpath . $prindot['urls']['logs'];	// (job)status and progress is only send by logs (NOT jobs or machines)
$machinesURL = $baseURLpath . $prindot['urls']['machines'];	// for init, heartbeat, update, setstatus to machines database table
$path = $prindot['paths']['storage_root_js'] . '/' . $prindot['paths']['strands'] . '/';	// path to strands- and xmljob-file
$answer = array();	// init empty answer-array (for json)
$HID = 0; // no heads involved here for our demonstration ...
$myMID = '1234-56-7890-abc';	// ID of this machine (static or read from hardware in real life)
$thisURL = $baseURLpath . basename($_SERVER["SCRIPT_NAME"]);	// URL of this machine connector


	// 0: fetch Query(as POST/GET or JSON)
if (!isset($_REQUEST['action']))	// not HTTP-X-FORM-POST ... try JSON:
{
	$jsonStr = file_get_contents("php://input"); // read the HTTP body. (json)
	$json = json_decode($jsonStr, true);	// convert json string to array
	if ($debuglogfirst) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") jsonStr='" . my_print_r($jsonStr) . " json='" . my_print_r($json) . "'");
	$_REQUEST = $json;	// fake request
}
$action = @$_REQUEST['action'];
if ($debuglogfirst) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") _REQUEST='" . my_print_r($_REQUEST) . "'");


	// big action switch
try {
	switch ($action)
	{
	case 'init':	// called from hardware/machine on startup: machine starts up and send initialisation to server
			// creates (or updates if MID already exists) an entry in database which hold the config of that machine

			// 1: send init to database
		$server_output = callcurl($machinesURL, array('action' => 'init', 'MID' => $myMID, 'URL' => $thisURL));	// MID and URL are mandatory all other are optional (see config.inc.php: $prindot['database']['tablekeys']['machines'] ... heartbeat and JID will be ignored on setting values) - implicitly does an heartbeat; resetting a possibly somehow "running" job to status 0="new"

			// 2: do check of $server_output (['result'] != "success" ... only a simple implementation here
		$ra = json_decode($server_output, true);
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") callcurl RETURN='" . my_print_r($ra) . "'");
		if ($ra['result'] != 'success') {
			// 3: do some error handling here !
			// leave a log comment as like as :
			$server_output = callcurl($logsURL, array('action' => 'create', 'JID' => 0, 'MID' => $myMID, 'HID' => $HID, 'type' => LOG_TYPE_ERROR, 'subtype' => LOG_SUBTYPE_MACHINE, 'description' => 'machine can\'t do init on database ... ' . $ra['reason']));
			// but this is only for demonstration
			// and ... by the way : the output from that call may have also errors within ...
		}

		break;	// no output/answer here because its not called from outside (server/GUI) - do your own message/error handling

	case 'heartbeat':	// called by some timer: machine should send a heartbeat from time to time to database (at least every x seconds - see config.inc.php: $prindot['settings']['max_heartbeat_timeout_sec'] ... but in real life 1-2 times per minute)
			// updates database machine heartbeat entry

			// 1: send heartbeat request to database
		$server_output = callcurl($machinesURL, array('action' => 'heartbeat', 'MID' => $myMID, 'status' => MACHINESTATUS_OK));	// MID is mandatory - no other values are needed; status is optional with two choices: 0=OK, 4=ERROR(which prevents sending a job to machine) [see config.inc.php for defines] if no status if given than implicitly using 0=ok
			// don't forget do do some error handling here !

		break;	// no output/answer here because its not called from outside (server/GUI) - do your own message/error handling

	case 'update':	// called from hardware/machine on change of values
			// updates entries in database which hold the config of that machine

			// 1: send update to database
		$server_output = callcurl($machinesURL, array('action' => 'update', 'MID' => $myMID, 'mode' => 3));	// MID is mandatory all other are optional (see config.inc.php: $prindot['database']['tablekeys']['machines'] ... heartbeat and JID will be ignored on setting values) - implicitly does an heartbeat; BTW: mode=3 means gravure(1) and groove(2) capabilities
			// don't forget do do some error handling here !

		break;	// no output/answer here because its not called from outside (server/GUI) - do your own message/error handling

	case 'setstatus':	// called from hardware/machine on change of machine status (OK or error)
			// updates status entry in database

			// 1: send status to database
		$server_output = callcurl($machinesURL, array('action' => 'setstatus', 'MID' => $myMID, 'status' => MACHINESTATUS_OK));	// MID is mandatory , status can be oe of two choices (0=OK, 4=ERROR see config.inc.php) [if not given/set than implicitly setting to 0=OK] - implicitly does an heartbeat
			// don't forget do do some error handling here !

			// alternatively the same can be done by using the target "logs" instead of "machines" and using action=setmachinestatus
//		$server_output = callcurl($logsURL, array('action' => 'setmachinestatus', 'MID' => $myMID, 'status' => MACHINESTATUS_OK));	// MID is mandatory , status can be oe of two choices (0=OK, 4=ERROR see config.inc.php) [if not given/set than implicitly setting to 0=OK] - implicitly does an heartbeat

		break;	// no output/answer here because its not called from outside (server/GUI) - do your own message/error handling

	case 'start':	// called from server: action to start a job
			// 1: check needed parameter
		if (!isset($_REQUEST['xmlfilename']))
		{
			send_answer(array('result' => 'error', 'reason' => 'Error m-1 : xmlfilename entry missing'), true);
		}

			// 2: fetch necessary values from xml-job-file (to have the logging here at least)
		$sxml = @simplexml_load_file($path . $_REQUEST['xmlfilename']);	// load XML from file
		if ($sxml == false)
		{
			send_answer(array('result' => 'error', 'reason' => 'Error m-2 : xmlfilename does not exists (cannot be read as XML)'), true);
		}
		$atts_array = (array)$sxml->attributes();	// fetch all attributes from XML as PHP-Array
		$atts_array = $atts_array['@attributes'];
		$JID = $atts_array['JID'];	// job ID from job-ticket
		$MID = $atts_array['MID'];	// you should know yourself (and compare with given ID)
			// 3: do validating of xml entries ... (most omitted here for simplicity ...)
		if ($myMID != $MID)
		{
			send_answer(array('result' => 'error', 'reason' => 'Error m-3 : wrong MID'), true);
		}

			// 4: log status to "running" (automatically sets progress to "0")
		$server_output = callcurl($logsURL, array('action' => 'status', 'JID' => $JID, 'status' => JOBSTATUS_RUNNING));	// see defines in config.inc.php; remember: 1 is "running"
			// don't forget do do some error handling here !

			// 5: send back acceptance of job and terminate output here
			// all fine and ready to process ... so send back success and terminate ... (processing/gravure of data should be on other thread)
		send_answer(array('result' => 'success'));

		process();	// see here for example progress, status and error-handling!

		break;

	case 'stop':	// called from server: action to stop a job
		$JID = @$_REQUEST['JID'];	// should be verified with actual known job-id (if not then send back error ?!)
		$MID = @$_REQUEST['MID'];	// you should know yourself (and may be compare with given ID)
			// alternatively you may take the known actual JID (and MID of course) to stop the current job - so JID and MID are not necessarily needed here !

			// 1: do JID and MID compare with status ... (omitted here for simplicity ...)

			// 2: actually not really cancelling the possibly running job here (for simplicity ...) nor checking if a job is running ...

			// 3: doing all the aborting stuff here (release memory, delete files, stopping machine etc ...) and finally:

			// 4: log status to "canceled" (if not set here ... it may/should be done by caller also for safety reasons ... otherwise no new job will be started on a machine with running status)
		$server_output = callcurl($logsURL, array('action' => 'status', 'JID' => $JID, 'status' => JOBSTATUS_CANCELED));	// see defines in config.inc.php; remember: 3 is "canceled"
			// don't forget do do some error handling here !

			// 5: add a log comment as like as :
		$server_output = callcurl($logsURL, array('action' => 'create', 'JID' => $JID, 'MID' => $MID, 'HID' => $HID, 'type' => LOG_TYPE_WARNING, 'subtype' => LOG_SUBTYPE_MACHINE, 'description' => 'machine has canceled its work on request ...'));
			// don't forget do do some error handling here !

			// 5: send back success of stopping request ('reason' is optional)
		send_answer(array('result' => 'success', 'reason' => 'successfully aborted job on machine'), true);
		break;

	default:
			// no error handling here (for simplicity ...)
		send_answer(array('result' => 'error', 'reason' => 'Error m-4 : unknown action'), true);
		break;
	}
}
catch (Exception $e) {
		// no error handling here (for simplicity ...)
	send_answer(array('result' => 'error', 'reason' => 'Error m-5 : Exception catched: (' . $e->getMessage() . ')'), true);
}


/**
* demonstration of progress indication
*   setting of status messages
*   and error handling
* should be done in a separate process/thread : ...
*
*/
function process()
{
	global $MID, $JID, $HID, $logsURL;	// for simplicity

		// optionally add a log comment as like as :
		//  some predefined types : LOG_TYPE_STATUS = 0; LOG_TYPE_INFO = 1; LOG_TYPE_PROGRESS = 2; LOG_TYPE_WARNING = 3; LOG_TYPE_ERROR = 4;
		//  subtypes are free to choose ... but "1" is used/defined to have relevance for machine
	$server_output = callcurl($logsURL, array('action' => 'create', 'JID' => $JID, 'MID' => $MID, 'HID' => $HID, 'type' => LOG_TYPE_STATUS, 'subtype' => LOG_SUBTYPE_MACHINE, 'description' => 'machine has started its work on job ...'));
		// don't forget do do some error handling here !

		// optional : set status to "load data" (automatically sets progress to "0")
	$server_output = callcurl($logsURL, array('action' => 'status', 'JID' => $JID, 'status' => JOBSTATUS_MACHINE_FILETRANSFER));	// see defines in config.inc.php;
		// don't forget do do some error handling here !

		// load strand data here (and optionally do progress indication ... like below)
	sleep(5);	// fake some work

		// optional : set status to "need user manual action" (automatically sets progress to "0")
	$server_output = callcurl($logsURL, array('action' => 'status', 'JID' => $JID, 'status' => JOBSTATUS_MACHINE_WAITFORMANUALACTION));	// see defines in config.inc.php;
		// don't forget do do some error handling here !
	// wait for status change (user did his manual action ...)
	sleep(15);	// fake some work

		// set status to "running" (automatically sets progress to "0")
	$server_output = callcurl($logsURL, array('action' => 'status', 'JID' => $JID, 'status' => JOBSTATUS_RUNNING));	// see defines in config.inc.php; remember: 1 is "running"
		// don't forget do do some error handling here !

		// fake some processing with progress indication
	$steps = 33;	// will ead in 99 seconds of "work"
	for ($i= 0; $i<$steps; $i++)
	{
			// 1: log some progress
		$progress = $i * (100 / $steps);
		$server_output = callcurl($logsURL, array('action' => 'progress', 'JID' => $JID, 'progress' => $progress));
			// don't forget do do some error handling here !

		sleep(3);	// fake some work

			// in case of and error this should be stated to logs and terminate:
		$error = false;	// not now (only for testing)
		//			if ($i == 10)	$error = true;	// testing
		if ($error)
		{
			// doing all your aborting stuff here and finally:

				// 2: set status to "error"
			$server_output = callcurl($logsURL, array('action' => 'status', 'JID' => $JID, 'status' => JOBSTATUS_ERROR));	// see defines in config.inc.php; remember: e is "error"
				// don't forget do do some error handling here !
				// no sending of an error message by json ... because all answers are send beforehand ... do your own error handling

				// 3: add a log comment as like as :
			$server_output = callcurl($logsURL, array('action' => 'create', 'JID' => $JID, 'MID' => $MID, 'HID' => $HID, 'type' => LOG_TYPE_ERROR, 'subtype' => LOG_SUBTYPE_MACHINE, 'description' => 'machine has error while doing job ...'));
				// don't forget do do some error handling here !
			die();
		}
	}

		// 4: log some progress (here the final 100%)
	$progress = 100;
	$server_output = callcurl($logsURL, array('action' => 'progress', 'JID' => $JID, 'progress' => $progress));
		// don't forget do do some error handling here !

	sleep(3);	// fake some work (eg. cleanup etc)

		// 5: log status to "finished"
	$server_output = callcurl($logsURL, array('action' => 'status', 'JID' => $JID, 'status' => JOBSTATUS_FINISHED));	// see defines in config.inc.php; remember: 2 is "finished"
		// don't forget do do some error handling here !

		// 6: finally add a log comment as like as :
	$server_output = callcurl($logsURL, array('action' => 'create', 'JID' => $JID, 'MID' => $MID, 'HID' => $HID, 'type' => LOG_TYPE_STATUS, 'subtype' => LOG_SUBTYPE_MACHINE, 'description' => 'machine has finished its assigned job sucessfully ...'));
		// don't forget do do some error handling here !
}

/**
* send back answer (as JSON encoded content in answer of request) and terminated output for caller
* (this is only done here because we're not doing a real new process/thread for the work ...)
*
* @param array $answer with "result" => "success" | "result" => "error", "reason" => "..."
* @param boolean $die : if set true then execute "die()"; default is false
*/
function send_answer($answer, $die=false)
{
	global $debugloglast;
	if ($debugloglast) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") _REQUEST='" . my_print_r($_REQUEST) . "' answer='" . my_print_r($answer) . "'");

	ignore_user_abort(true);
	set_time_limit(0);
//	$answer['reason'] .= " REQUEST=" . my_print_r($_REQUEST);
	$json = json_encode($answer);
	header('Content-Type: text/javascript; charset=UTF-8');	// to prevent warning in javascript console (chrome)
	header("Connection: close", true);
	header("Content-Length: " . strlen($json), true);
	if (isset($_REQUEST['callback']))	// jsonp ...
	{
		$callback = @$_REQUEST['callback']; // adding callback is necessary because of jsonp (json with possibly different host) - see JSON-Spec
		echo $callback . '(' . $json . ')';
	}
	else
	{
		echo $json;
	}
	$x = @ob_get_flush();
	@flush();
	//fastcgi_finish_request(); // important when using php-fpm!

	if ($die)
	{
		die();
	}
}

?>