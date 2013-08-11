<?php

//
// create raw strand data (image + preview) and job-XML
//
// given _REQUEST[]-values:
//
// JID to fetch all necessary data from database
//


require_once('config.inc.php');
require_once('functions.inc.php');


function calcstrand($REQUEST) {
	global $prindot;

	$debuglog = false;
	$debugtimer = false;
	$debuglogfirst = false;
	$debugloglast = false;
	$errorlog = false;
	$debugimage = false;


	if ($debuglogfirst) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") _REQUEST='" . my_print_r($REQUEST) . "'");

	$function = true; // prevent calling included functions below ...:
	require_once($prindot['urls']['logs']);
	require_once($prindot['urls']['jobs']);
	require_once($prindot['urls']['machines']);

	$answer = array();
	$answer['result'] = 'success';

	$action = @$REQUEST['action'];

	try {
		if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") START: " . date("H:i:s"));

		if ($action != 'calcstrand' && $action != 'checkfiles') {
			$answer['result'] = 'error';
			$answer['reason'] = 'Error calcstrand-1 : no or unknown action given';
			goto ende; // TODO: oder throw new Exception()
		}

		if (!isset($REQUEST['JID'])) {
			$answer['result'] = 'error';
			$answer['reason'] = 'Error calcstrand-2 : JID not given';
			goto ende; // TODO: oder throw new Exception()
		}

		$basepath = $prindot['paths']['storage_root_js'];
		$outpath = $prindot['paths']['strands'] . '/';

			// get jobs entry from table
		$server_output = calltarget('jobs', array('action' => 'load', 'JID' => @$REQUEST['JID']));
		$ra = json_decode($server_output, true);
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") calltarget RETURN='" . my_print_r($ra) . "'");
		if ($ra['result'] == 'success') {
			$jv = $ra['value'];
		} else {
			$answer['result'] = 'error';
			$answer['reason'] = 'Error calcstrand-3 : cannot read JID from database';
			goto ende; // TODO: oder throw new Exception()
		}
		if (!isset($jv['JID'])) {
			$answer['result'] = 'error';
			$answer['reason'] = 'Error calcstrand-4 : cannot read JID from database';
			goto ende;
		}
		$JID = $jv['JID'];


// machines konfig holen und mit job-werten vergleichen
		$atos = array('action' => 'load', 'MID' => $jv['MID']);
		$server_output = calltarget('machines', $atos);
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") calltarget RETURN='" . my_print_r(json_decode($server_output, true)) . "'");
		$ra = json_decode($server_output, true);
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") ra : <pre>" . my_print_r($ra) . "</pre>");
		if ($ra['result'] == 'success') {
			$mv = $ra['value'];
//if ($debuglog)	error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") mv : <pre>" . my_print_r($mv) . "</pre><br>");
		} else {
			$answer['result'] = 'error';
			$answer['reason'] = 'Error calcstrand-5 : cannot load MID from machines database (' . @$ra['reason'] . ')';
			goto ende;
		}
//if ($debuglog)	error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") OK<br>");
		if (!isset($mv['MID'])) {
			$answer['result'] = 'error';
			$answer['reason'] = 'Error calcstrand-6 : MID not indb table data ... serious!';
			goto ende;
		}
		$MID = $mv['MID'];

// TODO: Werte validieren (Breite mit Maschinen-Breite uebereinstimmen, Mode passen etc ... ansonsten Fehler schmeissen!!
// ... dann braucht matching nicht in Oberflaeche passieren (damit kann calcstrand unabhaengig von der Oberflaeche gestartet werden!
// auftrags-daten vs. maschinen-daten validieren
// ... jobs:check verwenden
//		if ($jv['perimeter_mm'] != $mv['act_perimeter_mm']) {
//			$answer['result'] = 'error';
//			$answer['reason'] = 'Error calcstrand-7 : job perimeter (' . $jv['perimeter_mm'] . ') doesn\'t match machine perimeter (' . $mv['act_perimeter_mm'] . ')';
//			goto ende;
//		}

		$inname = $jv['input_image'];
		$cylinder_raw_filename = create_strands_raw_filename($jv['JID']);
		$previewfilename = create_strands_preview_filename($jv['JID']);
		$xmlfilename = create_strands_job_filename($jv['JID']);

		$dir = $_SERVER["SCRIPT_FILENAME"]; // oder dirname(__FILE__)
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") dir '" . $dir . "'");
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") dirname(dir) '" . dirname($dir) . "'");
		$indir = dirname($dir) . '/' . $basepath;
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") indir '" . $indir . "'");
		$outdir = dirname($dir) . '/' . $basepath . $outpath;
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") outdir '" . $outdir . "'");

// hier pruefen, ob strang-daten und xml und preview vorhanden sind - wenn ja, dann nach unten springen (progress und status setzen, return=success, reason:"data already available")
//if (false)
		if (file_exists($outdir . $cylinder_raw_filename) && file_exists($outdir . $xmlfilename) && file_exists($outdir . $previewfilename) )
		{
			$flag = true;
		}
		else
		{
			$flag = false;
		}

		if ($action == 'checkfiles')	// check only ... so terminate in either case
		{
			if ($flag)
			{
					$answer['result'] = 'success';
					$answer['value'] = 'strand data already on disk -- nothing to do ... exiting OK';
			}
			else
			{
					$answer['result'] = 'error';
					$answer['reason'] = 'strand data not on disk -- has to be computed ... exiting OK';
			}
			goto ende;
		}

		if ($flag)	// if strand data exists then terminate here
		{
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") all strands files for this job already exists ... skipping calculations and writing ...");
			$answer['value'] = 'strand data already on disk -- nothing to do ... exiting OK';

				// connect job with machine and set status in jobs
			$server_output = calltarget('logs', array('action' => 'status', 'JID' => $JID, 'MID' => $MID, 'status' => JOBSTATUS_CALCSTRAND));	// progress automatically set 0
			$ra = json_decode($server_output, true);
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") calltarget RETURN='" . my_print_r($ra) . "'");
			if ($ra['result'] != 'success') {
				$answer['result'] = 'error';
				$answer['reason'] = 'Error calcstrand-8 : cannot update status in database (' . @$ra['reason'] . ')';
				goto ende;
			}
//
//				// set progress in jobs and machines
//			$server_output = calltarget('logs', array('action' => 'progress', 'JID' => $JID/*, 'MID' => $MID*/, 'progress' => -1));
//			$ra = json_decode($server_output, true);
//			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") calltarget RETURN='" . my_print_r($ra) . "'");
//			if ($ra['result'] != 'success') {
//				$answer['result'] = 'error';
//				$answer['reason'] = 'Error calcstrand-9 : cannot update progress in database (' . @$ra['reason'] . ')';
//				goto ende;
//			}
//
			goto allworkdone;
		}

			// check for other jobs using calcstrand ... (if any then abort!) - only 1 instance allowed !)
			// later there might be a pipeline for doing these things!
		$server_output = calltarget('jobs', array('action' => 'check_calcstrand_jobs'));
		$ra = json_decode($server_output, true);
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") calltarget RETURN='" . my_print_r($ra) . "'");
		if ($ra['result'] == 'error')
		{
			$answer['result'] = 'error';
			if ($REQUEST['JID'] == $ra['value'])	// should not happen ?!
			{
				$answer['reason'] = 'Error calcstrand-32 : ' . 'Dieser Auftrag [#' . $ra['value'] . '] wird schon berechnet. Bitte warten sie auf die Beendigung und starten Sie die Anfrage nochmals.';
			}
			else
			{
				$answer['reason'] = '[c33] ' . 'Es gibt noch einen anderen Auftrag [#' . $ra['value'] . '] der gerade berechnet wird. Bitte warten sie auf dessen Beendigung und starten Sie die Anfrage nochmals.';
			}
			goto ende;
		}

			// connect job with machine and set status in jobs
		$server_output = calltarget('logs', array('action' => 'status', 'JID' => $JID, 'MID' => $MID, 'status' => JOBSTATUS_CALCSTRAND));	// progress automatically set 0
		$ra = json_decode($server_output, true);
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") calltarget RETURN='" . my_print_r($ra) . "'");
		if ($ra['result'] != 'success') {
			$answer['result'] = 'error';
			$answer['reason'] = 'Error calcstrand-30 : cannot update status in database (' . @$ra['reason'] . ')';
			goto ende;
		}
//
//			// set progress in jobs and machines
//		$server_output = calltarget('logs', array('action' => 'progress', 'JID' => $JID/*, 'MID' => $MID*/, 'progress' => -1));
//		$ra = json_decode($server_output, true);
//		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") calltarget RETURN='" . my_print_r($ra) . "'");
//		if ($ra['result'] != 'success') {
//			$answer['result'] = 'error';
//			$answer['reason'] = 'Error calcstrand-31 : cannot update progress in database (' . @$ra['reason'] . ')';
//			goto ende;
//		}
//
		if (!extension_loaded('Imagick')) {
			$answer['result'] = 'error';
			$answer['reason'] = 'Error calcstrand-10 : PHP extension Imagick not loaded';
			goto ende;
		}
		if (!class_exists('Imagick')) {
			$answer['result'] = 'error';
			$answer['reason'] = 'ErrorError calcstrand-11 : PHP class iMagick does not exists';
			goto ende;
		}


//Imagick::setResourceLimit(imagick::RESOURCETYPE_AREA, 4192);
//Imagick::setResourceLimit(imagick::RESOURCETYPE_DISK, 4192);
//Imagick::setResourceLimit(imagick::RESOURCETYPE_FILE, 4192);
//Imagick::setResourceLimit(imagick::RESOURCETYPE_MAP, 4192);
//Imagick::setResourceLimit(imagick::RESOURCETYPE_MEMORY, 4192);

		/* Create new object */
		if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") create new IMagick ...");
		$im = new Imagick();
		if (false) {
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") before IMagick ResourceLimit RESOURCETYPE_UNDEFINED : " . $im->getResourceLimit(imagick::RESOURCETYPE_UNDEFINED));
//$im->setResourceLimit(imagick::RESOURCETYPE_UNDEFINED, 0);
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") after IMagick ResourceLimit RESOURCETYPE_UNDEFINED : " . $im->getResourceLimit(imagick::RESOURCETYPE_UNDEFINED));

			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") before IMagick ResourceLimit RESOURCETYPE_AREA : " . $im->getResourceLimit(imagick::RESOURCETYPE_AREA));
			$im->setResourceLimit(imagick::RESOURCETYPE_AREA, 2048);
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") after IMagick ResourceLimit RESOURCETYPE_AREA : " . $im->getResourceLimit(imagick::RESOURCETYPE_AREA));

			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") before IMagick ResourceLimit RESOURCETYPE_DISK : " . $im->getResourceLimit(imagick::RESOURCETYPE_DISK));
//$im->setResourceLimit(imagick::RESOURCETYPE_DISK, 1024);	// mag er nicht!
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") after IMagick ResourceLimit RESOURCETYPE_DISK : " . $im->getResourceLimit(imagick::RESOURCETYPE_DISK));

			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") before IMagick ResourceLimit RESOURCETYPE_FILE : " . $im->getResourceLimit(imagick::RESOURCETYPE_FILE));
			$im->setResourceLimit(imagick::RESOURCETYPE_FILE, 1);
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") after IMagick ResourceLimit RESOURCETYPE_FILE : " . $im->getResourceLimit(imagick::RESOURCETYPE_FILE));

			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") before IMagick ResourceLimit RESOURCETYPE_MAP : " . $im->getResourceLimit(imagick::RESOURCETYPE_MAP));
			$im->setResourceLimit(imagick::RESOURCETYPE_MAP, 2);
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") after IMagick ResourceLimit RESOURCETYPE_MAP : " . $im->getResourceLimit(imagick::RESOURCETYPE_MAP));

			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") before IMagick ResourceLimit RESOURCETYPE_MEMORY : " . $im->getResourceLimit(imagick::RESOURCETYPE_MEMORY));
			$im->setResourceLimit(imagick::RESOURCETYPE_MEMORY, 3072); // 2147483647
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") after IMagick ResourceLimit RESOURCETYPE_MEMORY : " . $im->getResourceLimit(imagick::RESOURCETYPE_MEMORY));
//die("DEBUG-ABORT");
		}
		if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") readImage ...");
		$im->readImage($indir . $inname);
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ")  ... DONE");
// exception handling throw etc
// TODO: error handling here and below !
//
//$i = $im->getImageProperties("*", true);
//error_log(my_print_r($i));
		$r = $im->getImageResolution();
		if ($r['x'] == 0)
			$r['x'] = 72;
		if ($r['y'] == 0)
			$r['y'] = 72;
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Resolution: " . my_print_r($r) . "<br>");
		$g = $im->getImageGeometry();
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Geometry: " . my_print_r($g) . "<br>");
		$u = $im->getImageUnits();
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Units: " . my_print_r($u) . "<br>"); // 1=dpi 2=lpc
		$o = $im->getImageOrientation(); // see http://www.impulseadventure.com/photo/exif-orientation.html
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Orientation: " . my_print_r($o) . "<br> (topleft=1)<br>"); // undefined (0), topleft (1), topright (2), bottomright (3), bottomleft (4), lefttop (5), righttop (6), rightbottom (7), and leftbottom (8)
		$d = $im->getImageDepth();
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Depth: " . my_print_r($d) . "<br>");
		$c = $im->getImageColorspace();
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Colorspace: " . my_print_r($c) . "<br> (COLORSPACE_GRAY=" . imagick::COLORSPACE_GRAY . ")<br>");
		$t = $im->getImageType();
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Type: " . my_print_r($t) . "<br> (IMGTYPE_GRAYSCALE=" . imagick::IMGTYPE_GRAYSCALE . ")<br>");


		switch ($u) {
			case 1:
				if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") UNITS: DPI<br>");
				$factor = 25.4;
				break;
			case 2:
				if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") UNITS: LPC<br>");
//	$factor = 10.0;	// lpi
				$factor = 25.4; // lpi
				if (!checkset($prindot['hotfix']['imagemagick']['resolution']))
				{
					$r['x'] *= 2.54;
					$r['y'] *= 2.54;
				}
				break;
			default:
				if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") UNITS: unknown<br>");
				$factor = 1.0; // ??
				break;
		}
		$width_mm = $g['width'] / $r['x'] * $factor;
		$height_mm = $g['height'] / $r['y'] * $factor;

		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Width='" . $width_mm . "'mm - Height='" . $height_mm . "'mm<br>");

		// force to grayscale (test only!)
		if ($t != imagick::IMGTYPE_GRAYSCALE) {
//	$im->setImageType(imagick::IMGTYPE_GRAYSCALE);
//	$c = $im->getImageColorspace();
//	if ($debuglog)	echo($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Colorspace: " . my_print_r($c) . "<br>");
//	$t = $im->getImageType();
//	if ($debuglog)	echo($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Type: " . my_print_r($t) . "<br> (IMGTYPE_GRAYSCALE=" . imagick::IMGTYPE_GRAYSCALE . ")<br>");
			$answer['result'] = 'error';
			$answer['reason'] = 'Error calcstrand-12 : only type-grayscale-images are allowed yet';
			goto ende;
		}
		if ($o != imagick::ORIENTATION_TOPLEFT) {
			$answer['result'] = 'error';
			$answer['reason'] = 'Error calcstrand-13 : only orientation-topleft-images are allowed yet';
			goto ende;
		}
		if ($d != 8) {
			$answer['result'] = 'error';
			$answer['reason'] = 'Error calcstrand-14 : only depth-8-images are allowed yet';
			goto ende;
		}
//		if ($c != imagick::COLORSPACE_GRAY) {
//			$answer['result'] = 'error';
//			$answer['reason'] = 'Error 4 : only colorspace-gray-images are allowed yet';
//			goto ende;
//		}
//die("DEBUG-ABORT");

		/*
		  $p = $im->getImagePixelColor(5450, 120);
		  echo("Pixel: " . my_print_r($p->getColor()) . "<br>");
		 */

// TEST to have empty lines
//echo "START rotating: " . date("H:i:s") . "<br>";
//$im->rotateImage(new ImagickPixel(), 90);
		if ($debugimage) {
			if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") START writing: " . date("H:i:s") . "<br>");
			//$im->setImageFormat('png');
			$im->writeimage($outdir . $cylinder_raw_filename . ".1.tif");
		}

// default values to read out image an transform to strand(cylinder)
		$rotate = 90; // ccw 90degree
		$mirror = 0;
//
//now calc with possibly given rotate and mirrot from job description ...
// rotate + rot MOD 360
// mirror xor mir

		$mirror = $mirror ^ $jv['image_mirror'];
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") mirror: " . my_print_r($mirror) . "<br>");
// possible case of $mirror = 3 leads into rotate + 180
		if ($mirror == 3) {
			$mirror = 0;
			$rotate += 180;
		}

		$rotate = ($rotate + $jv['image_rotation']) % 360;
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") rotate: " . my_print_r($rotate) . "<br>");


		if ($mirror == 1) { // horizontal
			if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") START mirror horizontal: " . date("H:i:s") . "<br>");
			$im->flopImage();
		}
		else
		if ($mirror == 2) { // vertical
			if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") START mirror vertical: " . date("H:i:s") . "<br>");
			$im->flipImage();
		}
		if ($debugimage) {
			if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") START writing: " . date("H:i:s") . "<br>");
			//$im->setImageFormat('png');
			$im->writeimage($outdir . $cylinder_raw_filename . ".2.tif");
		}
				$progress = 3;
				$server_output = calltarget('logs', array('action' => 'progress', 'JID' => $JID, 'progress' => $progress));
				if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") progress='" . $progress . "' calltarget RETURN='" . my_print_r(json_decode($server_output, true)) . "'");
				$ra = json_decode($server_output, true);
				if ($ra['result'] != 'success') {
					$answer['result'] = 'error';
					$answer['reason'] = 'Error calcstrand-15 : cannot update progress in database (' . @$ra['reason'] . ')';
					goto ende;
				}
		if ($rotate != 0) {
// Transform input image to fit output specs
// 1. Rotate (map image scanlines to stranddata) to read out horizontal with iterator below ...
			if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") START rotating: " . date("H:i:s") . "<br>");
			$im->rotateImage(new ImagickPixel(), $rotate);
		}
		if ($debugimage) {
			if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") START writing: " . date("H:i:s") . "<br>");
			//$im->setImageFormat('png');
			$im->writeimage($outdir . $cylinder_raw_filename . ".3.tif");
		}
				$progress = 8;
				$server_output = calltarget('logs', array('action' => 'progress', 'JID' => $JID, 'progress' => $progress));
				if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") progress='" . $progress . "' calltarget RETURN='" . my_print_r(json_decode($server_output, true)) . "'");
				$ra = json_decode($server_output, true);
				if ($ra['result'] != 'success') {
					$answer['result'] = 'error';
					$answer['reason'] = 'Error calcstrand-16 : cannot update progress in database (' . @$ra['reason'] . ')';
					goto ende;
				}

		if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") START negating: " . date("H:i:s") . "<br>"); // TODO may depend on interpretion in TIFF ?!
		$im->negateImage(false);
		if ($debugimage) {
			if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") START writing: " . date("H:i:s") . "<br>");
			//$im->setImageFormat('png');
			$im->writeimage($outdir . $cylinder_raw_filename . ".4.tif");
		}

		$r = $im->getImageResolution();
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Resolution: " . my_print_r($r) . "<br>");
		$g = $im->getImageGeometry();
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Geometry: " . my_print_r($g) . "<br>");
		$u = $im->getImageUnits();
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Units: " . my_print_r($u) . "<br>"); // 1=dpi 2=cm (lpc)
		$o = $im->getImageOrientation(); // see http://www.impulseadventure.com/photo/exif-orientation.html
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Orientation: " . my_print_r($o) . "<br>"); // undefined (0), topleft (1), topright (2), bottomright (3), bottomleft (4), lefttop (5), righttop (6), rightbottom (7), and leftbottom (8)

		switch ($u) {
			case imagick::RESOLUTION_PIXELSPERINCH:	// 1
				$factor = 25.4; // dpi
				break;
			case imagick::RESOLUTION_PIXELSPERCENTIMETER: // 2, lpc
//	$factor = 10.0;	// lpi
				$factor = 25.4; // lpi
				if (!checkset($prindot['hotfix']['imagemagick']['resolution']))
				{
					$r['x'] *= 2.54;
					$r['y'] *= 2.54;
				}
				break;
			default:
				$factor = 1.0; // ??
				$answer['result'] = 'error';
				$answer['reason'] = 'Error calcstrand-17 : only depth-8-images are allowed yet';
				goto ende;
				break;
		}
		$width_mm = $g['width'] / $r['x'] * $factor;
		$height_mm = $g['height'] / $r['y'] * $factor;
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Width='" . $width_mm . "'mm - Height='" . $height_mm . "'mm<br>");

		$pixxmm = $width_mm / $g['width'];
		$pixymm = $height_mm / $g['height'];
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Source(Image) pixmm='" . $pixxmm . "'mm - pixymm='" . $pixymm . "'mm<br>");


// scale image to target values
//$pitdpmm = 0.12938;
		$pitdpmm = $jv['pit_dist_perimeter_mm'];
//$pitdtmm = 0.07665;
		$pitdtmm = $jv['pit_dist_horizontal_mm'];
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Target(Image) pitdpmm='" . $pitdpmm . "'mm - pitdtmm='" . $pitdtmm . "'mm<br>");

		$offsetx = (int) ($jv['image_offsetx_mm'] / $pitdpmm + 0.5);
		$trackCount = $jv['track_count'];
		$offsety = (int) ($jv['image_offsety_mm'] / $pitdtmm + 0.5);
		$perimeterPitCount = $jv['perimeter_pit_count'];

		$image_scale_flag = $jv['image_scale_flag'];
		if ($image_scale_flag)
		{
			$scalex = max((int)($width_mm / $pitdpmm + 0.5), 1);
			$scaley = max((int)($height_mm / $pitdtmm + 0.5), 1);
			$scalex_p = $scalex;
			$scaley_p = $scaley;

			if (($scalex != $g['width']) || ($scaley != $g['height'])) {
				if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") scaling (target size): " . $scalex . " x " . $scaley . "<br>");
	//$tx = $g['width'] * scalex;
				if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") START scaling: " . date("H:i:s") . "<br>");
				$im->resizeImage($scalex, $scaley, imagick::FILTER_CATROM, 1, false);
			}
			else {
				if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") NOT scaling (target size is same s input size): " . $scalex . " x " . $scaley . "<br>");
			}
	// ggf image schon croppen, damites auf den zylinder passt ?! (dann braucht nicht weiter unten geprueft zu werden ...)
	// ... obwohl wohl kein bild beschnitten werden sollte ?! (schon fehler beim auftrag!)
		}
		else	// image 1:1 map to strand-pixel
		{
		$scalex = MIN($g['width'], $perimeterPitCount - $offsety);
		$scaley = MIN($g['height'], $trackCount - $offsetx);
			$scalex_p = $g['width'];
			$scaley_p = $g['height'];
		$width_mm = $scalex * $pitdpmm;	// only used here for infos
		$height_mm = $scaley * $pitdtmm;	// only used here for infos

			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") NOT scaling (scale-flag is false [1:1]): " . $scalex . " x " . $scaley . " - " . $width_mm . "mm x " . $height_mm . "mm<br>");
		}
				$progress = 85;
				$server_output = calltarget('logs', array('action' => 'progress', 'JID' => $JID, 'progress' => $progress));
				if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") progress='" . $progress . "' calltarget RETURN='" . my_print_r(json_decode($server_output, true)) . "'");
				$ra = json_decode($server_output, true);
				if ($ra['result'] != 'success') {
					$answer['result'] = 'error';
					$answer['reason'] = 'Error calcstrand-18 : cannot update progress in database (' . @$ra['reason'] . ')';
					goto ende;
				}

		if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") START blobbing: " . date("H:i:s") . "<br>");
// daten schreiben und empty-lines finden
		$tracks = min($scaley + $offsetx, $trackCount); // image may be cropped in width
//$tracks = 200;
// TODO: progress ...
		$emptylines = array();

		$nlpits = $perimeterPitCount - $offsety - $scalex; // Nachlauf: ja! scalex ! , da bild ja gedreht wurde!
		if ($nlpits < 0) { // sollte bei gravure nicht sein, da das bild ja beschnitten wuerde !!
			if ($image_scale_flag)
			{
	// TODO: sollte schon vorher einmal abgeprueft werden (ist hier schon fast zu spaet !))
				$answer['result'] = 'error';
				$answer['reason'] = 'Error calcstrand-19 : image would be cropped (doesnt fit on cylinder perimeter)';
				goto ende;
			}
			else
			{
				// TODO: hier nur warnung ausgeben ?!? (ist ja eh nur develop/test))
				// kann ja nicht meh sein, da das bild oben ja schon ggf. beschnitten wurde
				$nlpits = 0;
			}
		}
// TODO ...
// repeat-X und -Y bei mode groove zulassen ?!? (dann auch in GUI und DB !)


		$whitepixel = 0; // was -1 before negate
// open image for writing!
		$fp = fopen($outdir . $cylinder_raw_filename, 'wb');
		if ($fp == NULL) {
			$answer['result'] = 'error';
			$answer['reason'] = 'Error calcstrand-20 : cannot open raw file for writing';
			goto ende;
		}
		$wp = chr($whitepixel); // white pixel
// create white track
		$wl = ''; // write line buffer
// hier $l mit vorlauf-pixel(wp) (offset-y) fuellen
// TODO: sollte das nicht IMMER genau $perimeterPitCount sein ??? (statt "$scalex + $offsety + $nlpits")
		for ($i = 0; $i < $scalex + $offsety + $nlpits; $i++) {
			$wl .= $wp;
		}
// vorlauf-tracks schreiben
		$rfp = 1;
		for ($i = 0; $i < $offsetx; $i++) {
// TODO: sollte das nicht IMMER genau $perimeterPitCount sein ??? (statt "$scalex + $offsety + $nlpits")
			$rfp = fwrite($fp, $wl, $scalex + $offsety + $nlpits);
		}
		if ($rfp <= 0) {
			$answer['result'] = 'error';
			$answer['reason'] = 'Error calcstrand-21 : while writing raw file';
			goto ende;
		}
		$nltracks = $trackCount - $offsetx - $tracks; // nachlauftracks
		if ($nltracks < 0) { // sollte bei gravure nicht sein, da das bild ja beschnitten wuerde !!
			if ($image_scale_flag)
			{
	// TODO: sollte schon vorher einmal abgeprueft werden (ist hier schon fast zu spaet !))
	// TODO: kommt eh nicht vor, da siehe oben ja (MIN))
				$answer['result'] = 'error';
				$answer['reason'] = 'Error calcstrand-22 : image would be cropped (doesnt fit on cylinder width)';
				goto ende;
			}
			else
			{
				// TODO: hier nur warnung ausgeben ?!? (ist ja eh nur develop/test))
			}
			$nltracks = 0;
		}

//$p10 = (int)($tracks / 10);
		$eachprogress = 2000; // progress each ... lines/tracks
		$progress_factor = 9;	// writing is 9% of whole time
//TODO: bei mode_groove ist die cylinder-breite (trackcount) ausschlaggebend und wuerde repetieren des bildes nach sich ziehen
		for ($y = 0; $y < $tracks; $y++) {
			$pixels = $im->exportImagePixels(0, $y, $scalex, 1, "R", Imagick::PIXEL_CHAR); // Zeile als char array

			$emptyline = true;

			$l = ''; // write line buffer
			// hier $l mit vorlauf-pixel(wp) (offset-y) fuellen
// TODO: statisch vorberechnen (nicht fuer jede zeile neu!)
			for ($i = 0; $i < $offsety; $i++) {
				$l .= $wp;
			}
			foreach ($pixels as $px) {
				$l .= chr($px);
			}
			// hier $l mit nachlauf (bis perimeter voll) pixel(wp) fuellen
// TODO: statisch vorberechnen (nicht fuer jede zeile neu!)
			for ($i = 0; $i < $nlpits; $i++) {
				$l .= $wp;
			}
// TODO: sollte das nicht IMMER genau $perimeterPitCount sein ??? (statt "$scalex + $offsety + $nlpits")
			$rfp = fwrite($fp, $l, $scalex + $offsety + $nlpits); // hier +vorlauf +nachlauf schreiben
			// hier alles ok (vorlauf und nachlauf sind weiss)
			foreach ($pixels as $px) {
				if ($emptyline) {
					if ($px != $whitepixel) { // 255 == "-1", da unsigned char
						$emptyline = false;
						break;
					}
				}
			}
			$emptylines[$y] = $emptyline;

			// progress
			// TODO: besser: einfach progress in db schreiben, wenn sich der wert um mind. 1% geaendert hat und mind. 5 sec. zeit vergangen ist
//	if ((($y + 1) % $p10) == 0)
			if ((($y + 1) % $eachprogress) == 0) {
				// set status and progress
//		$progress = (int)((($y + 1) / $p10) * 10);
				$progressw = (int) ((($y + 1) / $tracks) * $progress_factor);
				$progress = 85 + $progressw;
				if ($progress >= 94)	// prevent 100% here (preview take also some time
					// TODO: possibly change here to factor 50 and before and after each to 25%
					$progress = 94;
				$server_output = calltarget('logs', array('action' => 'progress', 'JID' => $JID, 'progress' => $progress));
				if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") progress='" . $progress . "' calltarget RETURN='" . my_print_r(json_decode($server_output, true)) . "'");
				$ra = json_decode($server_output, true);
				if ($ra['result'] != 'success') {
					$answer['result'] = 'error';
					$answer['reason'] = 'Error calcstrand-23 : cannot update progress in database (' . @$ra['reason'] . ')';
					goto ende;
				}
			}
		}
		if ($rfp <= 0) {
			$answer['result'] = 'error';
			$answer['reason'] = 'Error calcstrand-24 : while writing raw file';
			goto ende;
		}

// nachlauf-tracks schreiben
		$rfp = 1;
		for ($i = 0; $i < $nltracks; $i++) {
// TODO: sollte das nicht IMMER genau $perimeterPitCount sein ??? (statt "$scalex + $offsety + $nlpits")
			$rfp = fwrite($fp, $wl, $scalex + $offsety + $nlpits);
		}
		if ($rfp <= 0) {
			$answer['result'] = 'error';
			$answer['reason'] = 'Error calcstrand-25 : while writing raw file';
			goto ende;
		}
//echo "{" . my_print_r($emptylines) . "} ";
//file.close();
		fclose($fp);

		if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") calculating MD5 of file ...");
		$md5 = md5_file($outdir . $cylinder_raw_filename);

		function MYXN($val) {
			return $val;
		}

		if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") creating XML ...");

// bit of the XML part
		$mode = $jv['mode']; // 1=gravure, 2=groove
		$head_count = $jv['head_count'];

		$targetXml = new DOMDocument("1.0", "UTF-8");

//to have indented output, not just a line
		$targetXml->preserveWhiteSpace = false;
		$targetXml->formatOutput = true;
//$targetXml->clear();

		$XmlCommentString = "prodotHead " . $prindot['info']['version'];
		$Xcomment = $targetXml->createComment($XmlCommentString);
		$targetXml->appendChild($Xcomment);

		$Xjob = $targetXml->createElement("job");
		$Xjob->setAttribute("JID", MYXN($JID));
		$Xjob->setAttribute("MID", MYXN($MID));
		$Xjob->setAttribute("mode", (($mode == 1) ? "gravure" : "groove"));
		$Xjob->setAttribute("perimeter_pit_count", $perimeterPitCount);
		$Xjob->setAttribute("pit_dist_perimeter_mm", MYXN($pitdpmm));
		$Xjob->setAttribute("perimeter_mm", MYXN($perimeterPitCount * $pitdpmm));
		if ($mode == 1) {
			$Xjob->setAttribute("track_count", $trackCount);
		} else {
			$Xjob->setAttribute("track_count", $trackCount);
		}
		$Xjob->setAttribute("width_mm", MYXN($trackCount * $pitdtmm));
		if ($mode == 1) {
			$Xjob->setAttribute("pit_dist_horizontal_mm", MYXN($pitdtmm));
		} else {
			// bei groove als array
		}
		if ($mode == 1) {
			$Xjob->setAttribute("trackoffset_mm", MYXN($pitdpmm / 2.0));
		} else {
			$Xjob->setAttribute("trackoffset_mm", MYXN(0.0));
		}
		$Xjob->setAttribute("cylinder_raw_filename", MYXN($cylinder_raw_filename));
		$Xjob->setAttribute("cylinder_raw_file_md5", MYXN($md5));
		$Xjob->setAttribute("head_count", MYXN($head_count));
		// calc head start and end positions (track#) )here!
		for ($i = 0; $i < $head_count; $i++) {
			$hsv = "headstart_" . ($i + 1); // xml attribute name
			$hs = (int)($jv[$hsv . '_mm'] / $pitdtmm + 0.5);
			$Xjob->setAttribute($hsv, MYXN($hs));

			$hev = "headend_" . ($i + 1);
			$he = (int)($jv[$hev . '_mm'] / $pitdtmm + 0.5);
			$Xjob->setAttribute($hev, MYXN($he));
		}
		$targetXml->appendChild($Xjob);

//		if ($mode == 1)	// NEU: nun auch bei mode groove das array mit rausschreiben
		{
			$flag = -1; // -1=none; 0=filled; 1=empty
			//$count = 0;
			// TODO: pay attention to vorlauf and nachlauf-tracks (which are white!) $flag = 1 and $count=$offsetx
			$count = $offsetx;
			if ($count > 0) {
				$flag = 1;
			}
			for ($i = 0; $i < $scaley; $i++) {
				if ($emptylines[$i] == true) { // empty line
					if ($flag == 0) { // was filled : write imagetrack_count
						if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") imagetrack_count=" . $count . "<br>");
						$Ximagetrack_count = $targetXml->createElement("imagetrack_count");
						$Ximagetrack_countVal = $targetXml->createTextNode($count);
						$Ximagetrack_count->appendChild($Ximagetrack_countVal);
						$Xjob->appendChild($Ximagetrack_count);
						$count = 0;
					}
					++$count;
					$flag = 1;
				}
				else { // filled
					if ($flag == 1) { // was empty : write spacetrack_count
						if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") spacetrack_count=" . $count . "<br>");
						$Xspacetrack_count = $targetXml->createElement("spacetrack_count");
						$Xspacetrack_countVal = $targetXml->createTextNode($count);
						$Xspacetrack_count->appendChild($Xspacetrack_countVal);
						$Xjob->appendChild($Xspacetrack_count);
						$count = 0;
					}
					++$count;
					$flag = 0;
				}
			}
			// nachlauftracks (white)
			if ($nltracks > 0) {
				if ($flag == 0) { // was filled : write imagetrack_count
					if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") imagetrack_count=" . $count . "<br>");
					$Ximagetrack_count = $targetXml->createElement("imagetrack_count");
					$Ximagetrack_countVal = $targetXml->createTextNode($count);
					$Ximagetrack_count->appendChild($Ximagetrack_countVal);
					$Xjob->appendChild($Ximagetrack_count);
					$count = 0;
				}
				$count += $nltracks;
				$flag = 1;
			}
			if ($count > 0) {
				if ($flag == 0) { // was filled : write imagetrack_count
					if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") imagetrack_count=" . $count . "<br>");
					$Ximagetrack_count = $targetXml->createElement("imagetrack_count");
					$Ximagetrack_countVal = $targetXml->createTextNode($count);
					$Ximagetrack_count->appendChild($Ximagetrack_countVal);
					$Xjob->appendChild($Ximagetrack_count);
				}
				else
				if ($flag == 1) { // was empty : write spacetrack_count
					if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") spacetrack_count=" . $count . "<br>");
					$Xspacetrack_count = $targetXml->createElement("spacetrack_count");
					$Xspacetrack_countVal = $targetXml->createTextNode($count);
					$Xspacetrack_count->appendChild($Xspacetrack_countVal);
					$Xjob->appendChild($Xspacetrack_count);
				}
			}
		}

		if ($mode == 2) {	// MODE_GROOVE
			$Xtrackdistancemmarray = $targetXml->createElement("trackdistancemmarray");
			for ($i = 0; $i < $trackCount - 1; $i++) {
				$XtrackdistancemmarrayVal = $targetXml->createTextNode(MYXN($pitdtmm) . ' '); //TODO: static for now - should be repeating given array of values
				$Xtrackdistancemmarray->appendChild($XtrackdistancemmarrayVal);
			}
			$Xjob->appendChild($Xtrackdistancemmarray);
		}

		$targetXml->appendChild($targetXml->createComment("created " . date("Y-m-d H:i:s")));

//if ($debuglog)	error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") <pre>" . htmlentities($targetXml->saveXML()) . "</pre>");
//	$answer['value'] = array('xml' => htmlentities($targetXml->saveXML()));
		file_put_contents($outdir . $xmlfilename, $targetXml->saveXML());
// TODO: no! not needed anymore (filenames are generative)
//		$answer['value'] = array('xmlfilename' => utf8_encode($xmlfilename)/*, 'xml' => utf8_encode($targetXml->saveXML())*/);

// TODO: create preview (make white canvas and place image (scaled down) roughly on it
		if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") START preview: " . date("H:i:s") . "<br>");
		 // new TODO ! (fit cylinder with placed/scaled image onto canvas of max 1000x500 pixel
//$Xjob->setAttribute("perimeter_pit_count", $perimeterPitCount);
//$Xjob->setAttribute("pit_dist_perimeter_mm", MYXN($pitdpmm));
		$z_height_mm = $perimeterPitCount * $pitdpmm;
//$Xjob->setAttribute("track_count", $trackCount);
//$Xjob->setAttribute("pit_dist_horizontal_mm", MYXN($pitdtmm));
		$z_width_mm = $trackCount * $pitdtmm;
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") cylinder width=" . $z_width_mm . "mm height=" . $z_height_mm . "mm");

// TODO: -> config.inc.php
		$maxcanvassizex = 1000;
		$maxcanvassizey = 500;

		$fx = $z_width_mm / $maxcanvassizex;
		$fy = $z_height_mm / $maxcanvassizey;
		$f = max($fx, $fy);
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") fx=" . $fx . " fy=" . $fy . " f=" . $f);

		$targetcanvassizex = max(floor($z_width_mm / $f + 0.5), 1);
		$targetcanvassizey = max(floor($z_height_mm / $f + 0.5), 1);
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") targetcanvassize x=" . $targetcanvassizex . " y=" . $targetcanvassizey);
		$canvas = new Imagick();
//			$canvas->setColorspace(imagick::COLORSPACE_GRAY);
		$canvas->newImage($targetcanvassizex, $targetcanvassizey, new ImagickPixel("white"));
		$canvas->setImageFormat("png");

		if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") preview rotate ...");
		$im->rotateImage(new ImagickPixel(), -90);
				$progress = 96;
				$server_output = calltarget('logs', array('action' => 'progress', 'JID' => $JID, 'progress' => $progress));
				if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") progress='" . $progress . "' calltarget RETURN='" . my_print_r(json_decode($server_output, true)) . "'");
				$ra = json_decode($server_output, true);
				if ($ra['result'] != 'success') {
					$answer['result'] = 'error';
					$answer['reason'] = 'Error calcstrand-26 : cannot update progress in database (' . @$ra['reason'] . ')';
					goto ende;
				}
		if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") preview mirror ...");
		$im->flipImage();
				$progress = 99;
				$server_output = calltarget('logs', array('action' => 'progress', 'JID' => $JID, 'progress' => $progress));
				if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") progress='" . $progress . "' calltarget RETURN='" . my_print_r(json_decode($server_output, true)) . "'");
				$ra = json_decode($server_output, true);
				if ($ra['result'] != 'success') {
					$answer['result'] = 'error';
					$answer['reason'] = 'Error calcstrand-27 : cannot update progress in database (' . @$ra['reason'] . ')';
					goto ende;
				}
		if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") preview negate ...");
		$im->negateImage(false);
		$previewtargetsizex = max(floor($targetcanvassizex * $scaley_p / $trackCount + 0.5), 1);
		$previewtargetsizey = max(floor($targetcanvassizey * $scalex_p / $perimeterPitCount + 0.5), 1);
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") previewtargetsize x=" . $previewtargetsizex . " y=" . $previewtargetsizey);
		if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") preview resize ...");
		$im->resizeImage($previewtargetsizex, $previewtargetsizey, imagick::FILTER_POINT, 1, true);

		$previewoffsetx = $targetcanvassizex * $offsetx / $trackCount;
		$previewoffsety = $targetcanvassizey * $offsety / $perimeterPitCount;
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") previewoffset x=" . $previewoffsetx . " y=" . $previewoffsety);
		if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") preview composite ...");
		$canvas->compositeImage($im, imagick::COMPOSITE_OVER, $previewoffsetx, $previewoffsety);

		$canvas->setImageFormat("png");
		$canvas->setImageOrientation(imagick::ORIENTATION_TOPLEFT);
		$canvas->setImageDepth(8);
		$canvas->setImageColorspace(imagick::COLORSPACE_GRAY);
		$canvas->setImageType(imagick::IMGTYPE_GRAYSCALE);
		if ($debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") preview write ...");
		$canvas->writeimage($outdir . $previewfilename);


// set raw filename in db
// TODO: no! not needed anymore (because filenames are generative))
//		$atos = array('action' => 'setrawfilename', 'JID' => $JID, 'cylinder_raw_filename' => $cylinder_raw_filename);
//		$server_output = calltarget('jobs', $atos);
//		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") calltarget RETURN='" . my_print_r(json_decode($server_output, true)) . "'");
//		$ra = json_decode($server_output, true);
//		if ($ra['result'] != 'success') {
//			$answer['result'] = 'error';
//			$answer['reason'] = 'Error 10 : cannot update rawfilename in jobs database (' . @$ra['reason'] . ')';
//			goto ende;
//		}




// TODO: ggf LOCK flag in machines table, damit nicht waerend verarbeitung die parameter geaendert werden
// GUI: 1) calcstrand, bei success: per AJAX das xml mit "action": "start" an machine (ID hier zurueckgeben in array value:  xml: ..., MID: MID)
// GUI: 2) xml mit action: start an URL von maschine senden und in machines-db in der machine das flag auf running stellen ggf. mit aktueller JID, damit nicht die grosse
//     job-liste auf einen running-eintrag gepollt werden muss (obwohl, ist ja nur ein mal, bis job fertig ist)
// GUI: 3) progress zu aktiven job darstellen (es kann nur einen geben PRO MASCHINE !!! ... wenn also andere Maschine angewaehlt wird, dann gibt es ggf keine progress anzeige
//     progress dann also auch in machine anzeigen (nicht nur bei job !?) : maschine, die gerade laeuft, kann nicht editiert werden!
// : status und progress und JID in machines-table
// : logs.php muss progress selber nach jobs und machines kopieren
// : settings-table wird nicht mehr gebraucht (jobs dienen als settings; klick auf job von admin: [laden][starten] oder wenn running: [laden][beenden] (ggf jweweils mit 2. nachfrage!); bei user: [laden][abbrechen]
// :: oder jobstable mit select (only one) und buttons darunter fuer action !? (oder rechte maustaste mit menue laden, starten, abbrechen ,...) ?!
// hier nicht status und progress in db schreiben ... alles von caller (GUI)
//if ($debuglog)	error_log("<pre>" . htmlentities(my_print_r($answer)) . "</pre>");
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") ENDE: " . date("H:i:s") . "<br>");
	}
	catch (Exception $e) {
		$answer['result'] = 'error';
		$answer['reason'] = 'Error calcstrand-28 : Exception catched: (' . $e . ')';
		goto ende;
	}

allworkdone:
//		$answer['result'] = 'success';
// TODO: set status to : "calcstrand finished ... starting machine"
	$progress = 100;
	if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") progress='" . $progress . "'");
	$atos = array('action' => 'progress', 'JID' => $JID/*, 'MID' => $MID*/, 'progress' => $progress);
	$server_output = calltarget('logs', $atos);
	if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") calltarget RETURN='" . my_print_r(json_decode($server_output, true)) . "'");
	$ra = json_decode($server_output, true);
	if ($ra['result'] != 'success') {
		$answer['result'] = 'error';
		$answer['reason'] = 'Error calcstrand-29 : cannot update progress in database (' . @$ra['reason'] . ')';
		goto ende;
	}

ende:
	if ($debugloglast || $debugtimer) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") _REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
	return json_encode($answer);
}

// function jobs()

if (!isset($function)) {
	echo(calcstrand($_REQUEST));
}

// TODOs:
// --------
// offset x + y von bild auf zylinder
// crop von bild an zylinder
// repetieren von bild auf zylinder
// auffuellen auf zylinder
// progress in db
// json response (ok + error)
// XML daten richtig encoden (zB Umlaute in filename etc ?!)
?>
