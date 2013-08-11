<?php
/* Create new object */
$im = new Imagick();
 
/* Create new checkerboard pattern */
$im->newPseudoImage(100, 100, "pattern:checkerboard");
 
/* Set the image format to png */
$im->setImageFormat('png');
 
/* Fill background area with transparent */
$im->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
 
/* Activate matte */
$im->setImageMatte(true);
 
/* Control points for the distortion */
$controlPoints = array( 10, 10,
                        10, 5,
 
                        10, $im->getImageHeight() - 20,
                        10, $im->getImageHeight() - 5,
 
                        $im->getImageWidth() - 10, 10,
                        $im->getImageWidth() - 10, 20,
 
                        $im->getImageWidth() - 10, $im->getImageHeight() - 10,
                        $im->getImageWidth() - 10, $im->getImageHeight() - 30);
 
/* Perform the distortion */ 
$im->distortImage(Imagick::DISTORTION_PERSPECTIVE, $controlPoints, true);
 
/* Ouput the image */   
header("Content-Type: image/png");
echo $im;
?>