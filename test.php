<?php
	$x = '2013-07-14 18:20:00';
	$progress = 90;

	$now = time();
	echo "time=" . time() . "<br>";

	$pasttime_s = $now - strtotime($x);

	echo("runtime_s=" . $pasttime_s . "<br>");

	$calctime_s = $pasttime_s * (100) / $progress;

	echo("calctime_s=" . $calctime_s . "<br>");

	$lefttime_s = $pasttime_s * (100 - $progress) / $progress;

	echo("lefttime_s=" . $lefttime_s . "<br>");

?>
