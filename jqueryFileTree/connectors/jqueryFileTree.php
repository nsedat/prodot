<?php
//
// jQuery File Tree PHP Connector
//
// Version 1.01
//
// Cory S.N. LaViska
// A Beautiful Site (http://abeautifulsite.net/)
// 24 March 2008
//
// History:
//
// 1.01 - updated to work with foreign characters in directory/file names (12 April 2008)
// 1.00 - released (24 March 2008)
//
// Output a list of files for jQuery File Tree
//

$_POST['dir'] = urldecode($_POST['dir']);

//error_log("dir='" . $_POST['dir'] . "'");
$root = $_SERVER["DOCUMENT_ROOT"] . '/';
//error_log("root='" . $root . "'");

//error_log("_SERVER['REQUEST_URI'] ='" . $_SERVER['REQUEST_URI'] . "'");

//error_log("_POST='" . print_r($_POST, true) . "'");
//error_log("_SERVER='" . print_r($_SERVER, true) . "'");
if( file_exists($root . $_POST['dir']) ) {
	$files = scandir($root . $_POST['dir']);
	natcasesort($files);
	if( count($files) > 2 ) { /* The 2 accounts for . and .. */
		echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
		// All dirs
		foreach( $files as $file ) {
			if( file_exists($root . $_POST['dir'] . $file)
					&& $file != '.'
					&& $file != '..'
					&& is_dir($root . $_POST['dir'] . $file) )
			{
				if ($file != "previews" && $file != "thumbnails" && $file != "tmp" && $file != "strands" && $file != "chunks")
// TODO: filter dynamically with config paths!
				{
				//echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir'] . $file) . "/\">" . htmlentities($file) . "</a></li>";
				echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($file) . "/\">" . htmlentities($file) . "</a></li>";

				}
			}
		}
		// All files
		foreach( $files as $file ) {
			if( file_exists($root . $_POST['dir'] . $file) && $file != '.' && $file != '..'  && !is_dir($root . $_POST['dir'] . $file) ) {
// TODO: filter dynamically with config extensions!
                $pp = pathinfo($file);
                if ($pp['extension'] == 'nfo')
                {
                    continue;
                }
                if ($pp['extension'] == 'gitignore')
                {
                    continue;
                }
				$ext = preg_replace('/^.*\./', '', $file);
				//echo "<li class=\"file ext_$ext\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir'] . $file) . "\">" . htmlentities($file) . "</a></li>";
				echo "<li class=\"file ext_$ext\"><a href=\"#\" rel=\"" . htmlentities($file) . "\">" . htmlentities($file) . "</a></li>";
			}
		}
		echo "</ul>";
	}
}

?>