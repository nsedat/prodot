<?php
 
 //echo __DIR__;
 //die();
 
/* Create new object */
$im = new Imagick( 'D:\xampp\htdocs\prindot\plupload2\examples\uploads\cylinder.jpg' );	// OK
$im = new Imagick( __DIR__ . '/' . 'frau_SELogo.tif' );

$i = $im->getImageProperties("*", true);
error_log(print_r($i, true));
$r = $im->getImageResolution();
error_log(print_r($r, true));
$g = $im->getImageGeometry();
error_log(print_r($g, true));
//die();
/* Scale down */
$im->thumbnailImage(200, 200, true);
$im->setImageFormat('jpeg');

/* Display */
header( 'Content-Type: image/jpg' );
echo $im;
 
?>