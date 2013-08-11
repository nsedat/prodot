<?php

	// connect to DB
if (!isset($db)) {
	global $db;
	//	error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") database connection NOT exists .. try to connect");
	$pdo_attrs = array(PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => true);
	try {
		$db = @new PDO($prindot['database']['type'] . ':host=' . $prindot['database']['host'] . ';dbname=' . $prindot['database']['name'] . ';charset=utf8', $prindot['database']['username'], $prindot['database']['password'], $pdo_attrs);
	} catch (PDOException $e) {
		error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") error while connection to DB with message:='" . my_print_r($e->getMessage()) . "'");
		die(json_encode(array('result' => 'error', 'reason' => 'Error while connection to database with message: ' . my_print_r($e->getMessage()))));
	}
} else {
	//	error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . " : " . __FUNCTION__ . "(" . __LINE__ . ") database connection ALREADY exists");
}

?>
