<?php

header('Content-type: image/jpeg');

error_log("realpath = " . realpath('cylinder.jpg'));
//$image = new Imagick('D:\xampp\htdocs\prindot\plupload2\examples\uploads\cylinder.jpg');
$image = new Imagick(realpath('cylinder.jpg'));

// If 0 is provided as a width or height parameter,
// aspect ratio is maintained
$image->thumbnailImage(100, 0);

echo $image;

?>