<?php
 
 //echo __DIR__;
 //die();

$basepath = '/plupload2/examples/uploads/';
$previewpath = './previews/';
$thumbpath = './thumbnails/';

$filename = 'frau_SELogo.tif';

$dir = $_SERVER["SCRIPT_FILENAME"];
error_log($_SERVER["SCRIPT_NAME"] . " : " . "dir '" . $dir . "'");

$dir = dirname($dir) . '/';
error_log($_SERVER["SCRIPT_NAME"] . " : " . "dir '" . $dir . "'");

//$dir = $dir . $basepath;
//error_log($_SERVER["SCRIPT_NAME"] . " : " . "dir '" . $dir . "'");

/* Create new object */
//$im = new Imagick( 'D:\xampp\htdocs\prindot\plupload2\examples\uploads\cylinder.jpg' );	// OK
$im = new Imagick( __DIR__ . '/' . $filename );
$im = new Imagick( $dir . $filename );

$i = $im->getImageProperties("*", true);
error_log(print_r($i, true));
$r = $im->getImageResolution();
error_log(print_r($r, true));
$g = $im->getImageGeometry();
error_log(print_r($g, true));
//die();

// PREVIEW
/* Scale down */
$im->thumbnailImage(1024, 1024, true);
$im->setImageFormat('jpeg');

/* save */
$outpath = $dir . $previewpath . $filename . ".jpg";
$im->writeImage($outpath);

echo "preview FERTIG (" . $outpath . ")<br>";

// THUMBNAIL
/* Scale down */
$im->thumbnailImage(200, 200, true);
$im->setImageFormat('jpeg');

/* save */
$outpath = $dir . $thumbpath . $filename . ".jpg";
$im->writeImage($outpath);

echo "thumbnail FERTIG (" . $outpath . ")<br>";
 
?>