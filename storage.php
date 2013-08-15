<?php

// storage.php
// delete given file (and its preview + thumbnail)


require_once('config.inc.php');
require_once('functions.inc.php');

function storage($REQUEST) {
	global $prindot;

	$debuglog = true;
	$debuglogfirst = true;
	$debugloglast = true;
	$errorlog = true;

	if ($debuglogfirst) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . "(" . __LINE__ . ") _REQUEST='" . my_print_r($REQUEST) . "'");

	$action = @$REQUEST['action'];
	$answer = array();

	switch ($action) {
	case 'delete':
		$targetDir = $prindot['paths']['storage_root_js'];
		$fileName = isset($_REQUEST["filename"]) ? $_REQUEST["filename"] : '*';
// Clean the fileName for security reasons
//		$fileName = preg_replace('/[^\w\._]+/', '_', $fileName);

		$dir = $_SERVER["SCRIPT_FILENAME"];
		error_log($_SERVER["SCRIPT_NAME"] . " : " . "dir '" . $dir . "'");

		$dir = dirname($dir) . '/';
		error_log($_SERVER["SCRIPT_NAME"] . " : " . "dir '" . $dir . "'");

		$filePath = $dir . $targetDir . $fileName;
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . 'filepath:"' . $filePath . '"');

		$filePath_thumbnail = $dir . $targetDir . $prindot['paths']['thumbnails'] . '/' . $fileName . ".jpg";
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . 'filePath_thumb:"' . $filePath_thumbnail . '"');

		$filePath_preview = $dir . $targetDir . $prindot['paths']['previews'] . '/' . $fileName . ".jpg";
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . 'filePath_preview:"' . $filePath_preview . '"');

		$filePath_imageinfo = $dir . $targetDir . $fileName . $prindot['fileextensions']['imageinfo'];
		if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . 'filePath_imageinfo:"' . $filePath_imageinfo . '"');

		if (!file_exists($filePath)) {
			$answer['result'] = 'error';
			$answer['reason'] = 'Warning storage-1 : file not given or doesnt exists';
			if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
		}
		else {
			try {
				$c = unlink($filePath);
				if (file_exists($filePath_thumbnail)) unlink($filePath_thumbnail);
				if (file_exists($filePath_preview)) unlink($filePath_preview);
				if (file_exists($filePath_imageinfo)) unlink($filePath_imageinfo);
				if ($debuglog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . "(" . __LINE__ . ") c='" . my_print_r($c) . "'");
				if ($c != true) {
					$answer['result'] = 'error';
					$answer['reason'] = 'Error storage-2 : file doesnt exists';
					if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
				}
				else {
					$answer['result'] = 'success';
					$answer['value'] = 'file (and preview, thumbnail, info) deleted';
				}
			} catch (PDOException $ex) {
				$answer['result'] = 'error';
				$answer['reason'] = 'Error storage-3 : ' . $ex->getMessage();
				goto ende;
			}
		}
		break;

	default:
		$answer['result'] = 'error';
		$answer['reason'] = 'Error storage-4 : unknown or missing action';
		if ($errorlog) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . "(" . __LINE__ . ") REQUEST='" . my_print_r($REQUEST) . "' answer='" . my_print_r($answer) . "'");
		break;
	}

ende:
	if ($debugloglast) error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . "(" . __LINE__ . ") answer='" . my_print_r($answer) . "'");
	return json_encode($answer);
}

// hier json mit Antwort zurueck
// result: "success" | "error" | "warning"
// bei "success" wird optional value zurueckgegeben (array)
// bei "error" oder "warning" zusaetzlich noch reason: "blablabl"
if (!isset($function)) {
	echo(storage($_REQUEST));
}
?>
