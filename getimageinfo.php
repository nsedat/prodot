<?php

require_once('config.inc.php');
require_once('functions.inc.php');

function getimageinfo($REQUEST)
{
	global $prindot;

	$debuglog = false;
	$debuglogfirst = false;
	$debugloglast = false;
	$errorlog = false;
	$debugimage = false;

	$infofileextension = $prindot['fileextensions']['imageinfo'];

	$answer = array();
	$answer['result'] = 'success';

//$targetDir = ini_get("upload_tmp_dir") . DIRECTORY_SEPARATOR . "plupload";
	$targetDir = $prindot['paths']['storage_root_js'];

	if ($debuglogfirst) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ")  [getimageinfo] : " . 'REQUEST:"' . my_print_r($REQUEST) . '"');

// TODO: use "$action" is in other php sources !

	$fileName = @$REQUEST["name"];
	if ($fileName == '') {
		$answer['result'] = 'error';
		$answer['reason'] = 'Error getimageinfo-1 : file name empty';
		goto ende;
	}

	if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ")  : " . 'fileName:"' . $fileName . '"');

// Clean the fileName for security reasons
//$fileName = preg_replace('/[^\w\._]+/', '_', $fileName);

	$filePath = $targetDir . $fileName;
	if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ")  : " . 'filepath:"' . $filePath . '"');

	try {
		if (file_exists($filePath)) {
			$filename = $filePath;
			$filenameinfo = $filePath . $infofileextension;
			if (file_exists($filenameinfo)) {
				$jsonstring = file_get_contents($filenameinfo);
				if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ")  : " . 'jsonstring:"' . $jsonstring . '"');
				$answerj = json_decode($jsonstring, true);
				if (is_array($answerj)) {
					$answer = $answerj;
					goto ende;
				}
			}

			$dir = $_SERVER["SCRIPT_FILENAME"];
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ")  : " . "dir '" . $dir . "'");

			$dir = dirname($dir) . '/';
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ")  : " . "dir '" . $dir . "'");

			//$dir = $dir . $prindot['paths']['storage_root_js'];
			//error_log($_SERVER["SCRIPT_NAME"] . " : " . "dir '" . $dir . "'");


			if (!extension_loaded('Imagick')) {
				$answer['result'] = 'error';
				$answer['reason'] = 'Error getimageinfo-2 : PHP extension Imagick not loaded';
				goto ende;
			}
			if (!class_exists('Imagick')) {
				$answer['result'] = 'error';
				$answer['reason'] = 'Error getimageinfo-3 : PHP class iMagick does not exists';
				goto ende;
			}
			/* Create new object */
			//$im = new Imagick( 'D:\xampp\htdocs\prindot\plupload2\examples\uploads\cylinder.jpg' );	// OK
			//$im = new Imagick( __DIR__ . '/' . $filename );
			$im = new Imagick($dir . $filename);

//	$i = $im->getImageProperties("*", true);
//	error_log(my_print_r($i));


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

			// force to grayscale (test only!)
			if ($t != imagick::IMGTYPE_GRAYSCALE) {
//	$im->setImageType(imagick::IMGTYPE_GRAYSCALE);
//	$c = $im->getImageColorspace();
//	if ($debuglog)	echo($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Colorspace: " . my_print_r($c) . "<br>");
//	$t = $im->getImageType();
//	if ($debuglog)	echo($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Type: " . my_print_r($t) . "<br> (IMGTYPE_GRAYSCALE=" . imagick::IMGTYPE_GRAYSCALE . ")<br>");
				$answer['result'] = 'error';
				$answer['reason'] = 'Error getimageinfo-4 : only type-grayscale-images are allowed yet';
				goto ende;
			}
			if ($o != 0 && $o != imagick::ORIENTATION_TOPLEFT) {
				$answer['result'] = 'error';
				$answer['reason'] = 'Error getimageinfo-5 : only orientation-topleft-images are allowed yet';
				goto ende;
			}
			if ($d != 8) {
				$answer['result'] = 'error';
				$answer['reason'] = 'Error getimageinfo-6 : only depth-8-images are allowed yet';
				goto ende;
			}
//			if ($c != imagick::COLORSPACE_GRAY) {
//				$answer['result'] = 'error';
//				$answer['reason'] = 'Error 4 : only colorspace-gray-images are allowed yet';
//				goto ende;
//			}

			switch ($u) {
				case 1:
					$factor = 25.4; // dpi
					break;
				case 2: // lpc
//	$factor = 10.0;	// lpi
					$factor = 25.4; // lpi
					if (!checkset($prindot['hotfix']['imagemagick']['resolution']))
					{
						$r['x'] *= 2.54;
						$r['y'] *= 2.54;
					}
					break;
				default:	// force DPI yet !?! (because of strands-previews (png without dpi and units))
					// TODO: possibly give an "relaxed=true" here in call
					if (checkset($REQUEST['relaxed']))
					{
						$factor = 25.4; // dpi
					}
					else
					{
						$answer['result'] = 'error';
						$answer['reason'] = 'Error getimageinfo-7 : only units dpi and lpc are allowed yet';
						goto ende;
					}
					break;
			}
			$g['width_mm'] = $g['width'] / $r['x'] * $factor;
			$g['height_mm'] = $g['height'] / $r['y'] * $factor;
			if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") Width='" . $g['width_mm'] . "'mm - Height='" . $g['height_mm'] . "'mm<br>");

//        $answer['value'] = array('res' => $r, 'size' => $g);
			$answer['value']['res'] = $r;
			$answer['value']['size'] = $g;
			$answer['value']['name'] = $fileName;

			// TODO: hier answer als json file speichern {filename.ext}.nfo
			// und weiter oben schauen, ob *.nfo vorhanden und dann ggf. nur das lesen und zurueckgeben
			$jsonstring = json_encode($answer);
			file_put_contents($filenameinfo, $jsonstring);
		} else {
			$answer['result'] = 'error';
			$answer['reason'] = 'Error getimageinfo-8 : file not found';
			goto ende;
		}
	} catch (Exception $e) {
		$answer['result'] = 'error';
		$answer['reason'] = 'Error getimageinfo-9 : Exception found: ' . $e->getMessage();
		goto ende;
	}

	ende:
	$jsonstring = json_encode($answer);
	if ($debugloglast) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer, true));
	return $jsonstring;
}


// hier json mit Antwort zurueck
// result: "success" | "error" | "warning"
// bei "success" wird optional value zurueckgegeben (array)
// bei "error" oder "warning" zusaetzlich noch reason: "blablabl"
if (!isset($function)) {
	echo(getimageinfo($_REQUEST));
}

?>
