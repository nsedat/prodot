<?php

//$SID = session_id();
//if (empty($SID)) {
//	//error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") no SID ... starting new session ...");
//	session_start() or die(basename(__FILE__).'(): Could not start session');
//	$SID = session_id();
//}
////error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") SID='" . $SID . "' _SESSION['db']='" . my_print_r($_SESSION['db']) . "'");
//$_SESSION['sid'] = $SID;

/* configuration for prindot php files (eg. database) */
$prindot['info']['version'] = '1.35 [2013-07-28]';

//$prindot = array();
$prindot['database']['type'] = 'mysql';	// TODO: try mysqlnd (what with the shitty linux compiled php versions ?? (check!)
$prindot['database']['host'] = 'localhost';
$prindot['database']['username'] = 'root';
$prindot['database']['password'] = 'root';
$prindot['database']['name'] = 'prindot';
$prindot['database']['tablenames']['machines'] = 'machines';
$prindot['database']['tablenames']['jobs'] = 'jobs';
$prindot['database']['tablenames']['logs'] = 'logs';
$prindot['database']['tablenames']['settings'] = 'settings';

$prindot['urls']['machines'] = 'machines.php';
$prindot['urls']['jobs'] = 'jobs.php';
$prindot['urls']['logs'] = 'logs.php';
$prindot['urls']['settings'] = 'settings.php';
$prindot['urls']['getimageinfo'] = 'getimageinfo.php';
$prindot['urls']['calcstrand'] = 'calcstrand.php';
$prindot['urls']['storage'] = 'storage.php';
$prindot['urls']['raster'] = 'raster.json';

$prindot['urls']['tablejobs'] = 'tablejobs.php';
$prindot['urls']['tablelogs'] = 'tablelogs.php';

//$prindot['paths']['storage_root_js'] = './plupload2/examples/uploads/';
$prindot['paths']['storage_root_js'] = './uploads/';
/* script for filetree */
$prindot['urls']['filetreescript'] = './jqueryFileTree/connectors/jqueryFileTree.php';
$prindot['urls']['plupload_uploader'] = './upload.php';

$prindot['fileextensions']['imageinfo'] = '.nfo';
$prindot['filemask']['strand'] = 'JID%08d';
$prindot['fileextensions']['strand'] = '.raw';
$prindot['fileextensions']['strandpreview'] = '.png';
$prindot['fileextensions']['jobxml'] = '.job';

$prindot['paths']['thumbnails'] = 'thumbnails';
$prindot['paths']['previews'] = 'previews';
$prindot['paths']['strands'] = 'strands';

$prindot['dimensions']['thumbnails']['width'] = 225;
$prindot['dimensions']['thumbnails']['height'] = 225;
$prindot['dimensions']['previews']['width'] = 800;
$prindot['dimensions']['previews']['height'] = 500;
$prindot['dimensions']['strand_previews']['width'] = 1000;
$prindot['dimensions']['strand_previews']['height'] = 500;




// names generiert mit phpmyadmin export table as csv mit erste zeile als spaltennamen ...
$prindot['database']['tablekeys']['machines'] = array('MID','URL','mode','name','comment','heartbeat','status','JID','min_width_mm','act_width_mm','max_width_mm','min_perimeter_mm','act_perimeter_mm','max_perimeter_mm','min_rpm','act_rpm','max_rpm','min_gouge_hz','act_gouge_hz','max_gouge_hz','act_head_count','max_head_count','HID1','HID2','HID3','HID4','HID5','HID6','HID7','HID8','headinitstartmm_1','headinitstartmm_2','headinitstartmm_3','headinitstartmm_4','headinitstartmm_5','headinitstartmm_6','headinitstartmm_7','headinitstartmm_8'
);

$prindot['database']['tablekeys']['jobs'] = array('JID','MID','name','comment','create_time','mode','perimeter_pit_count','pit_dist_perimeter_mm','perimeter_mm','track_count','width_mm','trackoffset_mm','pit_dist_horizontal_mm','head_count','status','start_time','end_time','progress','input_image','image_rotation','image_mirror','image_scale_flag','image_scale_x','image_scale_y','image_offsetx_mm','image_offsety_mm','headstart_1_mm','headend_1_mm','headstart_2_mm','headend_2_mm','headstart_3_mm','headend_3_mm','headstart_4_mm','headend_4_mm','headstart_5_mm','headend_5_mm','headstart_6_mm','headend_6_mm','headstart_7_mm','headend_7_mm','headstart_8_mm','headend_8_mm');

$prindot['database']['tablekeys']['logs'] = array('LID','JID','MID','HID','timestamp','remoteaddr','type','subtype','description');


define('LOG_TYPE_STATUS', 0);
define('LOG_TYPE_INFO', 1);
define('LOG_TYPE_PROGRESS', 2);
define('LOG_TYPE_WARNING', 3);
define('LOG_TYPE_ERROR', 4);

define('LOG_SUBTYPE_APPLICATION', 0);
define('LOG_SUBTYPE_MACHINE', 1);
define('LOG_SUBTYPE_RASTER', 2);
define('LOG_SUBTYPE_JOB', 3);
define('LOG_SUBTYPE_IMAGE', 4);

// TODO: use job-stati (-1,0,1,2,3,4)
define('JOBSTATUS_CALCSTRAND', -1);
define('JOBSTATUS_NEW', 0);
define('JOBSTATUS_RUNNING', 1);
define('JOBSTATUS_FINISHED', 2);
define('JOBSTATUS_CANCELED', 3);
define('JOBSTATUS_ERROR', 4);
define('JOBSTATUS_NOJOB', 5);
define('JOBSTATUS_MACHINE_FILETRANSFER', 6);
define('JOBSTATUS_MACHINE_WAITFORMANUALACTION', 7);

define('MACHINESTATUS_OK', 0);
define('MACHINESTATUS_ERROR', 4);

$prindot['settings']['autoupdatecheck_timer_sec'] = 600;	// automatically check for updates and refresh page
$prindot['settings']['max_heartbeat_timeout_sec'] = 86400;	// maximum age (in seconds) of heartbeat (to be checked when job starts)
$prindot['settings']['machineprogress_timer_sec'] = 10;	// timer for machine progress updates (show strands image and deterministic calculation)
$prindot['settings']['logtable_timer_sec'] = 3;	// timer for logtable
$prindot['settings']['jobtable_timer_sec'] = 5;	// timer for jobtable
/* NOT USED ANYMORE */$prindot['settings']['showmachinejobprogress_timer_sec'] = 30;	// timer for machine job progress updates (show calcstrand and running status in progressbar)

$prindot['settings']['mode'][1] = 'Gravure';
$prindot['settings']['mode'][2] = 'Groove';
$prindot['settings']['mode'][3] = 'Gravure+Groove';

// TODO: localization (should be in external file)
$prindot['l10n']['text']['maintitle'] = 'PRIN DOT  -  pro line  -  pro dot';
$prindot['l10n']['text']['tab_welcome_str'] = 'Über';
$prindot['l10n']['j01'] = '[j01] keine passende Auftrags-ID in der Datenbank gefunden';
$prindot['l10n']['j02'] = '[j02] : %1$s';

require_once('hotfix.inc.php');


$prindot['result'] = 'success';	// needed to verify success to caller
?>
