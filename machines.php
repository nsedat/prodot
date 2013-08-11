<?php

// http://localhost/prindot/machines.php?action=read
// _REQUEST:
// action={fetchall|write}
// action=fetchall liest ALLE machines aus (von GUI)
// action=write schreibt maschinendaten zurueck:
//			von GUI : ist ein update mit bekanntem MID
//			von Maschine : ist ein Update mit bekanntem MID
//			von Maschine : ist eine neue Maschine, wenn MID unbekannt

require_once('config.inc.php');
require_once('functions.inc.php');
require_once('connect2db.php');

//DebugBreak('1:7869;d=1,p=17,c=1');

function machines($REQUEST) {
	global $prindot;
	global $db;

	$debuglog = false;
	$debuglogfirst = false;
	$debugloglast = false;
	$errorlog = false;

	if ($debuglogfirst) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") _REQUEST='" . my_print_r($REQUEST) . "'");

	$function = true; // prevent calling included functions below ...:
	require_once($prindot['urls']['getimageinfo']);

	$action = @$REQUEST['action'];
	$answer = array();
	$answer['result'] = 'success';
	$answer['reason'] = '';

	$Tm = $prindot['database']['tablenames']['machines'];
	$Tj = $prindot['database']['tablenames']['jobs'];

	if (!isset($REQUEST['MID']) && ($action != 'fetchall')) {
		error_result($answer, '[m01] MID missing');
		$action = 'unset';
		goto ende;
	}

	// read all machines from db by GUI
	switch ($action) {
	case 'fetchall':
		try {
			$sql = "SELECT * FROM `$Tm`";
			$q = $db->prepare($sql);
			$q->execute();
			$r = $q->fetchAll(PDO::FETCH_ASSOC);
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "'");
			if ($c == 0) {
				error_result($answer, '[m02] no machines in db found');
			} else {
				$answer['value'] = $r;
			}
		} catch (PDOException $ex) {
			error_result($answer, '[m03] ' . $ex->getMessage());
			if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
		}
		break;

	case 'load':
		try {
			$sql = "SELECT * FROM `$Tm` WHERE ".sqlkv('MID', $REQUEST['MID']);
//			$sql = "SELECT * FROM `$Tm` WHERE MID='" . myescape($REQUEST['MID']) . "'";
			$q = $db->prepare($sql);
			$q->execute();
			$r = $q->fetch(PDO::FETCH_ASSOC);
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "' r='" . my_print_r($r) . "'");
			if ($c == 0) {
				error_result($answer, '[m04] no machines in db found');
			} else {
				$answer['value'] = $r;
			}
		} catch (PDOException $ex) {
			error_result($answer, '[m05] ' . $ex->getMessage());
			if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
		}
		break;

		// write back specific machine settings from GUI (or machine) (may changes parameters)
	case 'update':
		try {
				// validate given parameters
			if (isset($REQUEST['status']) && $REQUEST['status'] != MACHINESTATUS_OK && $REQUEST['status'] != MACHINESTATUS_ERROR) {
				error_result($answer, '[m06] unknown status given ...');
				break;
			}
			$REQUEST['heartbeat'] = date("Y-m-d H:i:s");	// set heartbeat to get rowCount() = 1 (else 0 when no changes with same values)
			if (isset($REQUEST['JID']))	// does not allow setting of JID here
				unset($REQUEST['JID']);
			$sql = "UPDATE `$Tm` SET ".sqlcreatesetstringfromarray($REQUEST, $prindot['database']['tablekeys']['machines'])." WHERE ".sqlkv('MID', $REQUEST['MID']);
			$q = $db->prepare($sql);
			$q->execute();
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "'");
			if ($c == 0) {
				//$answer['result'] = 'warning';
				$answer['reason'] = 'only a warning - no updates made - because no changes or unknown MID';
			}
		} catch (PDOException $ex) {
			error_result($answer, '[m07] ' . $ex->getMessage());
			if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
		}
		break;

		// MID; status
	case 'setstatus':
		try {
			if (@$REQUEST['status'] != MACHINESTATUS_OK && @$REQUEST['status'] != MACHINESTATUS_ERROR) {
				error_result($answer, '[m08] unknown status given ...');
				break;
			}
				// not status given will set to '0' (= MACHINESTATUS_OK)
			$sql = "UPDATE `$Tm` SET ".sqlkv('status', @$REQUEST['status'])." WHERE ".sqlkv('MID', $REQUEST['MID']);
			$q = $db->prepare($sql);
			$q->execute();
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "'");
			if ($c == 0) {
//				$answer['result'] = 'warning';
				$answer['reason'] = 'only a warning - no updates made - because no changes or unknown MID';
			}
		} catch (PDOException $ex) {
			error_result($answer, '[m09] ' . $ex->getMessage());
			if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
		}
		break;

		// MID; JID
	case 'setjid':
// JID in machines setzen (wird als rueckbezug von getpreview, getjobinfos benoetigt!
		if (!isset($REQUEST['JID'])) {
			error_result($answer, '[m22] missing jobID parameter');
			goto ende;
		}
		try {
			$REQUEST['heartbeat'] = date("Y-m-d H:i:s");	// set heartbeat to get rowCount() = 1 (else 0 when no changes with same values)
//			$sql = "UPDATE `$Tm` AS M
//					INNER JOIN `$Tj` AS J
//					ON J.JID='" . myescape(@$REQUEST['JID']) . "'
//					SET M.JID=J.JID
//					WHERE M.MID='" . myescape($REQUEST['MID']) . "'";
			$sql = "UPDATE `$Tm` AS M
					INNER JOIN `$Tj` AS J
					ON ".sqlkv('J.JID', $REQUEST['JID'])."
					SET M.JID=J.JID
					".sqlkv('heartbeat', $REQUEST['heartbeat'], true)."
					WHERE ".sqlkv('M.MID', $REQUEST['MID']);
// count row == 1 if ok or 0 if JID not existent or already connected
//			$sql = "UPDATE " . $prindot['database']['tablenames']['machines'] . " SET " .
//					"`JID`='" . @$REQUEST['JID'] . "'" .
//					" WHERE `MID`='" . $REQUEST['MID'] . "'";
			$q = $db->prepare($sql);
			$q->execute();
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "'");
			if ($c == 0) {
//				$answer['result'] = 'warning';
//				$answer['reason'] = 'only a warning - no updates made - because no changes or unknown MID/JID';
				error_result($answer, '[m23] no updates made - unknown MID/JID');
			}
		} catch (PDOException $ex) {
			error_result($answer, '[m10] ' . $ex->getMessage());
			if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
		}
		break;

		// from machine/backend (should be called only once when starting)
		// may creates a new machine with settings
	case 'init':
		try {
			// URL is mandatory
			if (!isset($REQUEST['URL'])) {
				error_result($answer, '[m11] URL missing');
				$action = 'unset';
				goto ende;
			}
				// validate given parameters
			if (isset($REQUEST['status']) && $REQUEST['status'] != MACHINESTATUS_OK && $REQUEST['status'] != MACHINESTATUS_ERROR) {
				error_result($answer, '[m12] unknown status given ...');
				break;
			}
			$REQUEST['heartbeat'] = date("Y-m-d H:i:s");	// set heartbeat to get rowCount() = 1 (else 0 when no changes with same values)
			if (isset($REQUEST['JID']))	// does not allow setting of JID here
				unset($REQUEST['JID']);
// set heartbeat to get rowCount() = 1 (else 0 when no changes with same values)
			$sql = "INSERT INTO `$Tm` SET ";
			$sqlx = sqlcreatesetstringfromarray($REQUEST, $prindot['database']['tablekeys']['machines']);
			$sql .= $sqlx;
			$sql .= " ON DUPLICATE KEY UPDATE ";
			$sql .= $sqlx;
			$q = $db->prepare($sql);
			$q->execute();
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "'");
			if ($c == 0) {
//				$answer['result'] = 'warning';
//				$answer['reason'] = 'only a warning - no updates made - because no changes or unknown MID';
				error_result($answer, '[m24] no updates made - unknown MID/JID');
			}

				// set status to "canceled" of connected running job (if one)
//			$sql = "UPDATE `$Tj` AS J
//				INNER JOIN `$Tm` AS M
//				ON M.JID = J.JID
//				SET J.status=" . JOBSTATUS_CANCELED . "
//				WHERE M.MID='" . myescape($REQUEST['MID']) . "'
//				AND (J.status=" . JOBSTATUS_CALCSTRAND . " OR J.status=" . JOBSTATUS_RUNNING . " OR J.status=" . JOBSTATUS_MACHINE_FILETRANSFER . " OR J.status=" . JOBSTATUS_MACHINE_WAITFORMANUALACTION . ")";	// update status of job from machine (only if some running like status - not new/error/finished etc)
			$sql = "UPDATE `$Tj` AS J
				INNER JOIN `$Tm` AS M
				ON M.JID = J.JID
				SET ".sqlkvn('J.status', JOBSTATUS_CANCELED)."
				WHERE ".sqlkvn('M.MID', $REQUEST['MID'])."
				AND (".sqlkvn('J.status', JOBSTATUS_CALCSTRAND)." OR ".sqlkvn('J.status', JOBSTATUS_RUNNING)." OR ".sqlkvn('J.status', JOBSTATUS_MACHINE_FILETRANSFER)." OR ".sqlkvn('J.status', JOBSTATUS_MACHINE_WAITFORMANUALACTION).")";	// update status of job from machine (only if some running like status - not new/error/finished etc)
			$q = $db->prepare($sql);
			$q->execute();
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "'");
			if ($c == 0) {
//				$answer['result'] = 'warning';
				$answer['reason'] .= ' info: no update on job made';
			}


		} catch (PDOException $ex) {
			error_result($answer, '[m13] ' . $ex->getMessage());
			if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
		}
		break;

// heartbeat from machine (should be send every minute or so )
	case 'heartbeat':
// TODO: allow setting of (machine-)status on heartbeat
// also on normal init and update
		$status = '';
		if (isset($REQUEST['status'])) {
			if (($REQUEST['status'] != MACHINESTATUS_OK) && ($REQUEST['status'] != MACHINESTATUS_ERROR)) {
				error_result($answer, '[m14] unknown status given ...');
				break;
			}
			$status = ", `status`='" . $REQUEST['status'] . "'";
		}
		try {
			$ts = date("Y-m-d H:i:s");
			$sql = "UPDATE " . $Tm . " SET `heartbeat`='" . $ts . "' $status WHERE MID='" . myescape($REQUEST['MID']) . "'";
			$q = $db->prepare($sql);
			$q->execute();
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "'");
		} catch (PDOException $ex) {
			error_result($answer, '[m14] ' . $ex->getMessage());
			if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
		}
		break;

// get actual preview with associated JID
	case 'getpreview':
		try {
			$sql = "SELECT JID FROM `" . $Tm . "` WHERE MID='" . myescape($REQUEST['MID']) . "'";
			$q = $db->prepare($sql);
			$q->execute();
			$r = $q->fetch(PDO::FETCH_ASSOC);
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "' r='" . my_print_r($r) . "'");
			if ($c == 0) {
				error_result($answer, '[m15] no JID in MID in db found');    // not a real error because if no job on new machine ?! (or deleted strands-file)
			}
			else {
				// fetch size of image here! (in javascript not possible because of using canvas ...)
				if ($r['JID'] != '') {
					$preview = create_strands_preview_filename($r['JID']);
					$r['preview'] = $preview;
					$atos = array('name' => $prindot['paths']['strands'] . '/' . $preview);
					if (checkset($REQUEST['relaxed'])) {
						$atos['relaxed'] = true;
					}
					$server_output = calltarget('getimageinfo', $atos);
					$ra = json_decode($server_output, true);
					if ($ra['result'] == 'success') {
						$r['size'] = $ra['value']['size'];
						$answer['value'] = $r;
					} else {
						error_result($answer, '[m16] ' . $ra['reason']);
					}
				} else {
					error_result($answer, '[m17] no preview for MID in db found');   // not a real error because if no job on new machine ?! (or deleted strands-file)
				}
			}
		} catch (PDOException $ex) {
			error_result($answer, '[m18] ' . $ex->getMessage());
			if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
		}
		break;

// getstart_time, progress and status from associated JID (to do the deterministic calculations)
	case 'getjobinfos':
		try {
			$sql = "SELECT J.*" .
					" FROM `" . $Tm . "` As M INNER JOIN `" . $Tj . "` As J" .
					" ON M.JID = J.JID" .
					" WHERE M.MID='" . myescape($REQUEST['MID']) . "'";
			$q = $db->prepare($sql);
			$q->execute();
			$r = $q->fetch(PDO::FETCH_ASSOC);
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "' r='" . my_print_r($r) . "'");
			if ($c == 0) {
				error_result($answer, '[m19] no JID in MID in db found');   // not a real error because if no job on new machine ?! (or deleted strands-file)
			}
			else {
				// fetch size of image here! (in javascript not possible because of using canvas ...)
				$r['preview'] = '';
				$r['size']['width'] = 0;
				$r['size']['height'] = 0;
				if ($r['JID'] != '') {
					$preview = create_strands_preview_filename($r['JID']);
					$r['preview'] = $preview;
					$atos = array('name' => $prindot['paths']['strands'] . '/' . $preview);
					if (checkset($REQUEST['relaxed'])) {
						$atos['relaxed'] = true;
					}
					$server_output = calltarget('getimageinfo', $atos);
					$ra = json_decode($server_output, true);
					if ($ra['result'] == 'success') {
						$r['size'] = $ra['value']['size'];
//						$answer['value'] = $r;
					}
//					else{
//						$answer = $ra;
//					}
				}
				$answer['value'] = $r;
				$answer['value']['start_time_s'] = strtotime($r['start_time']);

				// restzeit (sekunden) auch in hier berechnen (wg. unterschiedlicher zeit server vs. lokal browser)
				$now = time();
				$x = $r['start_time'];	// '2013-07-14 18:20:00';
				$progress = $r['progress'];	// 90;
				$pasttime_s = $now - strtotime($x);
				if ($progress > 0)
				{
					$calctime_s = (int)($pasttime_s * 100 / $progress);
					$lefttime_s = (int)($pasttime_s * (100 - $progress) / $progress);
				}
				else
				{
					$calctime_s = -1;
					$lefttime_s = -1;	// calculated left time from now on
				}
				$answer['value']['pasttime_s'] = $pasttime_s;	// running secondes until now
				$answer['value']['calctime_s'] = $calctime_s;	// calculated run time from start
				$answer['value']['lefttime_s'] = $lefttime_s;	// calculated left time from now on
			}
		} catch (PDOException $ex) {
			error_result($answer, '[m20] ' . $ex->getMessage());
			if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
		}
		break;

	default:
		error_result($answer, '[m21] unknown or missing action');
		if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
		goto ende;
		break;
	}

	ende:
// hier json mit Antwort zurueck
// result: "success" | "error"
// bei "success" wird optional value zurueckgegeben (array)
// bei "error" zusaetzlich noch reason: "blablabl"
	if ($debugloglast) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") _REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
	return json_encode($answer);
} // function machines()

if (!isset($function)) {
	echo(machines($_REQUEST));
}
?>
