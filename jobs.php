<?php

// http://localhost/prindot/jobs.php?action=create
// _REQUEST:
// action=load, save, start(load+POST)
// // db-anzeige (table) mit tablejobs.php per datatables wie tablelogs.php
//			von GUI : ...
//			von Maschine : ...
// JID autoincrement
// create_time autoset
// all other mandatory (even if left blank/0)
require_once('config.inc.php');
require_once('functions.inc.php');
require_once('connect2db.php');

function jobs($REQUEST) {
	global $prindot;
	global $db;

	$debuglog = false;
	$debuglogfirst = false;
	$debugloglast = false;
	$errorlog = false;

	if ($debuglogfirst) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") _REQUEST='" . my_print_r($REQUEST) . "'");

	$function = true; // prevent calling included functions below ...:
	require_once($prindot['urls']['calcstrand']);
//	require_once($prindot['urls']['jobs']);
	require_once($prindot['urls']['machines']);

	$action = @$REQUEST['action'];
	if (isset($REQUEST['action']))
		unset($REQUEST['action']);
	$answer = array();
	$answer['result'] = 'success';
	$answer['reason'] = '';

	$Tj = $prindot['database']['tablenames']['jobs'];
	$Tm = $prindot['database']['tablenames']['machines'];

	switch ($action) {
	case 'load':	// read one specific job from db by GUI
		if (!isset($REQUEST['JID'])) {
			error_result($answer, '[j03] missing jobID parameter');
			goto ende;
		}
		try {
			$sql = "SELECT * FROM `$Tj` WHERE ".sqlkv('JID', $REQUEST['JID']);
			$q = $db->prepare($sql);
			$q->execute();
//			$r = $q->fetchAll(PDO::FETCH_ASSOC);
			$r = $q->fetch(PDO::FETCH_ASSOC);
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "' r='" . my_print_r($r) . "'");
			if ($c == 0) {
				error_result($answer, l10n('[j01] no matching JID in db found', 'j01'));
				goto ende;
			} else {
				$answer['value'] = $r;
			}
		} catch (PDOException $ex) {
			error_result($answer, l10n('[j02] : %1$s', 'j02'), $ex->getMessage());
			if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
			goto ende;
		}
		break;

	case 'setprogress':	// update progress in db jobs table
		if (!isset($REQUEST['JID']) || !isset($REQUEST['progress'])) {
			error_result($answer, '[j03] missing parameter');
			goto ende;
		}
		try {
			$progress = (int)@$REQUEST['progress'];
			$sql = "UPDATE `$Tj` SET ".sqlkv('progress', $progress)." WHERE ".sqlkv('JID', $REQUEST['JID']);
			$q = $db->prepare($sql);
			$q->execute();
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "'");
//			$answer['result'] = 'success';
		} catch (PDOException $ex) {
			error_result($answer, '[j04] ' . $ex->getMessage());
			goto ende;
		}
		break;

	case 'setstatus':	// update status and progress in db jobs table
		if (!isset($REQUEST['JID']) || !isset($REQUEST['status'])) {
			error_result($answer, '[j05] missing parameter');
			goto ende;
		}
		try {
// wenn status=-1/1(running) oder =2,3,4(stopped) dann auch timestamps setzen (start_time, end_time)
// wenn status -1/1 dann progress auch auf 0 setzen
			$status = (int)@$REQUEST['status'];
			switch ($status) {
			case JOBSTATUS_CALCSTRAND:
			case JOBSTATUS_RUNNING:
			case JOBSTATUS_MACHINE_FILETRANSFER:
				$start_time = date("Y-m-d H:i:s");
// TODO: evaluate: setting starttime for each of 3 steps ??? restzeitberechnung verkehrt, wenn startzeit in anderem abschnitt ...
				$progress = 0;
//				$sql = "UPDATE " . $prindot['database']['tablenames']['jobs'] . " SET `status`='" . myescape($status) . "', `start_time`='" . $start_time . "', `progress`='" . $progress . "' WHERE `JID`='" . myescape($REQUEST['JID']) . "'";
				$sql = "UPDATE `$Tj` SET ".sqlkv('status', $status).sqlkv('start_time', $start_time, true).sqlkv('progress', $progress, true)." WHERE ".sqlkv('JID', $REQUEST['JID']);
				$q = $db->prepare($sql);
				$q->execute();
				break;
			case JOBSTATUS_FINISHED: // finished
			case JOBSTATUS_CANCELED: // canceled
			case JOBSTATUS_ERROR: // error
				$end_time = date("Y-m-d H:i:s");
				$sql = "UPDATE `$Tj` SET ".sqlkv('status', $status).sqlkv('end_time', $end_time, true)." WHERE ".sqlkv('JID', $REQUEST['JID']);
				$q = $db->prepare($sql);
				$q->execute();
				break;
			case JOBSTATUS_MACHINE_WAITFORMANUALACTION:
			case JOBSTATUS_NEW:	// new
			default:
				$sql = "UPDATE `$Tj` SET ".sqlkv('status', $status)." WHERE ".sqlkv('JID', $REQUEST['JID']);
				$q = $db->prepare($sql);
				$q->execute();
				break;
			}

			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "'");
//			$answer['result'] = 'success';
			if ($c == 0)
			{
				$answer['reason'] = '...';
			}
			$answer['value'] = $c;	// request may be ok ... but if JID is wrong then c=0 ...
		} catch (PDOException $ex) {
			error_result($answer, '[j06] ' . $ex->getMessage());
			goto ende;
		}
		break;

	case 'fetchall':
// TODO: not used by anybody (ok)
		try {
			$sql = "SELECT * FROM `$Tj`";
			$q = $db->prepare($sql);
			$q->execute();
			$r = $q->fetchAll(PDO::FETCH_ASSOC);
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "'");
			if ($c == 0) {
				error_result($answer, '[j07] no jobs in db found');
			} else {
//				$answer['result'] = 'success';
				$answer['value'] = $r;
			}
		} catch (PDOException $ex) {
			error_result($answer, '[j08] ' . $ex->getMessage());
			goto ende;
		}
		break;

	case 'create':
		if (!isset($REQUEST['name'])) {
			error_result($answer, '[j09] name missing');
			goto ende;
		}
		try {
				// check name before : must be filled out and unique in db
			$sql = "SELECT * FROM `$Tj` WHERE ".sqlkv('name', $REQUEST['name']);
			$q = $db->prepare($sql);
			$q->execute();
			$r = $q->fetch(PDO::FETCH_ASSOC);
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "'");
			if ($c > 0) {
				error_result($answer, '[j10] name already known ... job not saved');
				goto ende;
			}

// TODO: check all? other parameters for validity ?!
			$sql = "INSERT INTO `$Tj` SET ".sqlcreatesetstringfromarray($REQUEST, $prindot['database']['tablekeys']['jobs']);
			$q = $db->prepare($sql);
			$q->execute();
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "'");
			if ($c == 0) {
//				$answer['result'] = 'warning';
				$answer['reason'] = '[j11] only a warning - no updates made - because no changes or unknown MID';
			}
		} catch (PDOException $ex) {
			error_result($answer, '[j12] ' . $ex->getMessage());
			goto ende;
		}

// TODO: hier ggf. schon calcstrand mit aufrufen (?) ... ggf. mit zus. parameter "action2"=>"calcstrand" oder action=create_and_calcstrand
// ... aber ggf. kein fehler, wenn schon ein anderer calcstrand laeuft ... einfach warten oder ignorieren und nichts tun

		break;

	case 'check_calcstrand_jobs':	// check if any other jobs are currently using CALCSTRAND (result: "success"=nein alles ok; "error"=ja ein anderer job benutzt calcstrand (value:nummer)

// check if any other job has status -1 (calcstrand) to avoid running of two processes with heavy computings!
// entweder fehlermeldung ... oder warten ... (besser fehlermeldung ... damit spaeter nocheinmal probiert werden kann ... ansonsten ggf. deadlock, wenn status falsch ... der kann ja manuell zureckgesetzt werden!)
		try {
			$status = JOBSTATUS_CALCSTRAND;
			$sql = "SELECT JID FROM `$Tj` WHERE ".sqlkv('status', $status);
			$q = $db->prepare($sql);
			$q->execute();
			$r = $q->fetch(PDO::FETCH_NUM);	// erster mit nummer reicht
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "' r='" . my_print_r($r) . "'");
			if ($c > 0) {
				error_result($answer, 'ein anderer Auftrag [#' . $r[0] . '] berechnet gerade Strangdaten');
				$answer['value'] = $r[0];
			}
		} catch (PDOException $ex) {
			error_result($answer, '[j36] ' . $ex->getMessage());
			goto ende;
		}

		break;

	case 'calcstrand':
// hier keine jobqueue (verwaltung) ... das muss der aufrufer machen!
		if (!isset($REQUEST['JID'])) {
			error_result($answer, '[j03] missing jobID parameter');
			goto ende;
		}
		$server_output = calltarget('calcstrand', array('action' => 'calcstrand', 'JID' => $REQUEST['JID']));
		$ra = json_decode($server_output, true);
		if ($ra['result'] == 'success') {
			$answer = $ra;
		} else {
//			$answer['reason'] = '[j13] problem while calcstrand : (' . @$ra['reason'] . ')';
			error_result($answer, '[j13] ' . @$ra['reason']);
			goto ende;
		}
		break;

	case 'check':	// check job vs. machine parameter
// job holen
		if (!isset($REQUEST['JID'])) {
			error_result($answer, '[j03] missing jobID parameter');
			goto ende;
		}
		$server_output = calltarget('jobs', array('action' => 'load', 'JID' => $REQUEST['JID']));
		$ra = json_decode($server_output, true);
		if ($ra['result'] != 'success') {
//			$answer['result'] = 'error';
//			$answer['reason'] .= ' Error jobs-14 : problem while ' . $action . ' job (load job data) : (' . @$ra['reason'] . ')';
			error_result($answer, '[j14] ' . @$ra['reason']);
// automatically set job status to error ?!
			$atos = array('action' => 'setstatus', 'JID' => $REQUEST['JID'], 'status' => JOBSTATUS_ERROR);
			$server_output = calltarget('jobs', $atos);
			$ra = json_decode($server_output, true);
			if ($ra['result'] != 'success') {
				error_result_a($answer, ' [j15] ' . @$ra['reason']);
				goto ende;
			}
			goto ende;
		}
		$jv = $ra['value'];
// MID aus job ...
// TODO: wird die MID auch vom jobadmin im job neu gesetzt (oder gilt immer die beim job-definieren angewählte maschine?)
		$MID = $jv['MID'];
// machine[MID] laden
		$server_output = calltarget('machines', array('action' => 'load', 'MID' => $MID));
		$ra = json_decode($server_output, true);
		if ($ra['result'] != 'success') {
			error_result($answer, '[j16] problem while ' . $action . ' job (load machines data) : (' . @$ra['reason'] . ')');
// automatically set job sttaus to error ?!
			$atos = array('action' => 'setstatus', 'JID' => $REQUEST['JID'], 'status' => JOBSTATUS_ERROR);
			$server_output = calltarget('jobs', $atos);
			$ra = json_decode($server_output, true);
			if ($ra['result'] != 'success') {
				error_result_a($answer, ' [j17] problem while ' . $action . ' job (update job status to error) : (' . @$ra['reason'] . ')');
				goto ende;
			}
			goto ende;
		}
		$mv = $ra['value'];

// hier jetzt jv und mv validieren

// irgendein anderer job mit status=-1/1/6/7 mit machine verbunden?
// TODO: differenzieren auf 1 (running ... dann fehler)
// und -1 calcstrand ... nur fehler, wenn noch daten berechnet werden muessen (also vorher einen calcstrand-check machen)
// wichtig, wenn calcstrand unabhaengig von job-start ist
		try {
			$sql = "SELECT JID FROM `$Tj` WHERE ".sqlkv('MID', $MID)." AND (`status`='".JOBSTATUS_CALCSTRAND."' OR `status`='".JOBSTATUS_RUNNING."' OR `status`='".JOBSTATUS_MACHINE_FILETRANSFER."' OR `status`='".JOBSTATUS_MACHINE_WAITFORMANUALACTION."')";	// may use "status IN (...)" instead of "OR"
			$q = $db->prepare($sql);
			$q->execute();
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "'");
			if ($c > 0) {
				$r = $q->fetch(PDO::FETCH_NUM);	// erster mit nummer reicht
				if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") r='" . my_print_r($r) . "'");
				if ($REQUEST['JID'] == $r[0]) {	// should not happen ?!
					error_result_a($answer, '[j18] Dieser Auftrag [#' . $r[0] . '] läuft schon auf der Maschine. Bitte warten sie auf dessen Beendigung oder brechen Sie diesen ab.<br />');
				} else {
					error_result_a($answer, '[j19] Es gibt noch einen anderen laufenden Auftrag [#' . $r[0] . '] auf der Maschine. Bitte warten sie auf dessen Beendigung oder brechen Sie diesen ab.<br />');
				}
			}
		} catch (PDOException $ex) {
			error_result_a($answer, '[j20] ' . $ex->getMessage());
			goto ende;
		}

			// check for other jobs using calcstrand ... (if any then abort!) - only 1 instance allowed !)
			// later there might be a pipeline for doing these things!
		$server_output = calltarget('jobs', array('action' => 'check_calcstrand_jobs'));
		$ra = json_decode($server_output, true);
		if ($ra['result'] == 'error')
		{
			if ($REQUEST['JID'] == $ra['value'])	// should not happen ?!
			{
				error_result_a($answer, '[j37] ' . 'Dieser Auftrag [#' . $ra['value'] . '] wird schon berechnet. Bitte warten sie auf die Beendigung und starten Sie die Anfrage nochmals.');
			}
			else
			{
				error_result_a($answer, '[j38] ' . 'Es gibt noch einen anderen Auftrag [#' . $ra['value'] . '] der gerade berechnet wird. Bitte warten sie auf dessen Beendigung und starten Sie die Anfrage nochmals.');
			}
			goto ende;
		}

			// aggregate errors ... as like in js ! (so reason .= ... with linebreakes

			// check status (0=ok, 4=error) of machine
		if ($mv['status'] != MACHINESTATUS_OK) {	// ==MACHINESTATUS_ERROR
			error_result_a($answer, '[j35] Maschine hat keinen OK-Status gemeldet!<br />');
		}

			// check heartbeat of machine
		$d = strtotime($mv['heartbeat']);
		$now = time();
		if ($now - $d > $prindot['settings']['max_heartbeat_timeout_sec']) {
			error_result_a($answer, '[j21] Maschine hat sich nicht innerhalb der letzten ' . $prindot['settings']['max_heartbeat_timeout_sec'] . ' Sekunden am Server gemeldet! (heartbeat zu alt)<br />');
		}

			// mode moeglich
		if (!($jv['mode'] & $mv['mode'])) {
			error_result_a($answer, '[j22] Modus der Maschine (' . $prindot['settings']['mode'][$mv['mode']] . ') passt nicht zum Job (' . $prindot['settings']['mode'][$jv['mode']] . ')<br />');
		}

			// perimeter_mm == act_perimeter_mm ?? (+/-1mm)
		if (abs($mv['act_perimeter_mm'] - $jv['perimeter_mm']) >= 1.0) {
			error_result_a($answer, '[j23] Umfang des Zylinders (' . $mv['act_perimeter_mm'] . 'mm) passt nicht zum Job (' . $jv['perimeter_mm'] . 'mm)<br />');
		}
			// width_mm <= act_width_mm
		if ($mv['act_width_mm'] < $jv['width_mm']) {
			error_result_a($answer, '[j24] Breite des Zylinders (' . $mv['act_width_mm'] . 'mm) ist kleiner als Breite des Jobs (' . $jv['width_mm'] . 'mm)<br />');
		}
			// head_count == act_head_count (?)
		if ($mv['act_head_count'] != $jv['head_count']) {
			error_result_a($answer, '[j25] Aktuelle Kopfanzahl (' . $mv['act_head_count'] . ') der Maschine entspricht nicht der im Job (' . $jv['head_count'] . ')<br />');
		}

			// kopfpositionen vergleichen ! (ungefaehr)
		for ($i = 1; $i <= $jv['head_count']; $i++) {	// only compare heads used in job
			if (abs($mv["headinitstartmm_{$i}"] - $jv["headstart_{$i}_mm"]) >= 1.0) {
				error_result_a($answer, "[j26] Die Position des Kopfes {$i} (" . $mv["headinitstartmm_{$i}"] . "mm) auf der Maschine passt nicht zu der im Job (" . $jv["headstart_{$i}_mm"] . "mm)<br />");
			}
		}

		break;

	case 'start':	// send start job to machine (via json post)
	case 'stop':	// send stop job to machine (via json post) : both quit the same (stop is without check and calcstrand)
		if (!isset($REQUEST['JID'])) {
			error_result($answer, '[j03] missing jobID parameter');
			if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
			goto ende;
		}
			// MID und URL via jobs(JID) aus machines holen:
		try {
			$sql = "SELECT J.JID, J.MID, M.URL FROM `$Tm` As M INNER JOIN `$Tj` As J ON M.MID=J.MID WHERE J.JID='".myescape($REQUEST['JID'])."'";
			$q = $db->prepare($sql);
			$q->execute();
//			$ra = $q->fetchAll(PDO::FETCH_ASSOC);	// erste ist einzige
			$c = $q->rowCount();
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "'");
			if ($c != 1) {
				error_result($answer, '[j28] Es konnte keine Zieladresse(URL) in der Maschinendatenbank für den Auftrag [#' . $REQUEST['JID'] . '] gefunden werden.<br />');
				goto ende;
			}
			$r = $q->fetch(PDO::FETCH_ASSOC);	// erste ist einzige
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") r='" . my_print_r($r) . "'");
		}
		catch (PDOException $ex) {
			error_result($answer, '[j29] ' . $ex->getMessage());
			goto ende;
		}
		$JID = $r['JID'];
		$MID = $r['MID'];
		$URL = $r['URL'];

		if ($action == 'start') {
				// check (again)
			$server_output = calltarget('jobs', array('action' => 'check', 'JID' => $JID));
			$ra = json_decode($server_output, true);
			if ($ra['result'] == 'success') {
				$answer = $ra;
			} else {
				error_result($answer, '[j30] ' . @$ra['reason']);
				goto ende;
			}

// ?? hier job mit machine verbinden?! (ne ... wird fast als erstes in calcstrand gemacht!

				// calcstrand
			$server_output = calltarget('jobs', array('action' => 'calcstrand', 'JID' => $JID));
			$ra = json_decode($server_output, true);
			if ($ra['result'] == 'success') {
				$answer = $ra;
			} else {
//				$answer['reason'] = 'Error jobs-31 : problem while calcstrand : (' . @$ra['reason'] . ')';
				error_result($answer, '[j31] ' . @$ra['reason']);
				goto ende;
			}
		}

// send start or stop command to target (machine/URL)
		$xmlfilename = create_strands_job_filename($JID);	// not necessary for stop ...
//		$server_output = callcurl_json($URL, ['action' => $action, 'xmlfilename' => $xmlfilename, 'JID' => $JID, 'MID' => $MID]);
		$server_output = callcurl($URL, array('action' => $action, 'xmlfilename' => $xmlfilename, 'JID' => $JID, 'MID' => $MID));	// using HTTP-FORM-POST (for now); JID and MID are not needed in either case (start/stop)
		$ra = json_decode($server_output, true);
		if ($ra['result'] == 'success') {
			$answer = $ra;
		} else {
			error_result($answer, '[j32] problem while ' . $action . ' job on machine ' . $MID  . ' with URL=' . $URL . ': (' . @$ra['reason'] . ')');
// automatically set job status to error ?!
			$server_output = calltarget('jobs', array('action' => 'setstatus', 'JID' => $JID, 'status' => JOBSTATUS_ERROR));
			$ra = json_decode($server_output, true);
			if ($ra['result'] != 'success') {
				error_result_a($answer, ' [j33] problem while ' . $action . ' job (update job status to error) : (' . @$ra['reason'] . ')');
				goto ende;
			}
			goto ende;
		}

		break;

	default:
		error_result($answer, '[j34] unknown or missing action');
		if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
		goto ende;
		break;
	}

ende:
// hier json mit Antwort zurueck
// result: "success" | "error"
// bei "success" wird optional value zurueckgegeben (array)
// bei "error" zusaetzlich noch reason: "blablabl"
	if ($debugloglast) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") action='" . $action . "' _REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
	return json_encode($answer);
}

if (!isset($function)) {
	echo(jobs($_REQUEST));
}
?>
