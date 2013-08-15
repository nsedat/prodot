<?php

require_once('config.inc.php');
require_once('functions.inc.php');

$debug = true;

class qqFileUploader {

	public $allowedExtensions = array();
	public $sizeLimit = null;
	public $inputName = 'qqfile';
	public $chunksFolder = 'chunks';
	public $chunksFolderAsSubdir = true;

	public $chunksCleanupProbability = 0.01; // Once in 1000 requests on avg
	public $chunksExpireIn = 3600; // One week

	public $partialsExtension = 'partial';
	public $partialsCleanupProbability = 0.1; // Once in 1000 requests on avg
	public $partialsExpireIn = 3600; // One hour

	// only useful when 64bit OS and files bigger 2GB (elso problems with fseek on 32bit systems ...)
	public $directchunking = false;	// if set true then chunks will be combined drectly in target file (not via chunk files in chunk-dir)

	protected $uploadName = '';

	function __construct(){
		$this->sizeLimit = $this->toBytes(ini_get('upload_max_filesize'));
	}

	/**
	* Get the original filename
	*/
	public function getName(){
		if (isset($_REQUEST['qqfilename']))
			return $_REQUEST['qqfilename'];

		if (isset($_FILES[$this->inputName]))
			return $_FILES[$this->inputName]['name'];
	}

	/**
	* Get the name of the uploaded file
	*/
	public function getUploadName(){
		return $this->uploadName;
	}

	/**
	* Process the upload.
	* @param string $uploadDirectory Target directory.
	* @param string $name Overwrites the name of the file.
	*/
	public function handleUpload($uploadDirectory, $name = null){
		//error_log("_REQUEST: " . print_r($_REQUEST, true));

		if ($this->chunksFolderAsSubdir) {
			$chunksFolder = $uploadDirectory.DIRECTORY_SEPARATOR.$this->chunksFolder;
		} else {
			$chunksFolder = $this->chunksFolder;
		}
		$this->chunksFolder = $chunksFolder;

		if (!file_exists($chunksFolder)){
			mkdir($chunksFolder);
		}

		if (!$this->directchunking) {
			if (is_writable($this->chunksFolder) && 1 == mt_rand(1, 1/$this->chunksCleanupProbability)){
				// Run garbage collection
				$this->cleanupChunks();
			}
		} else {
			if (is_writable($uploadDirectory) && 1 == mt_rand(1, 1/$this->partialsCleanupProbability)){
				// Run garbage collection
				$this->cleanupPartials($uploadDirectory);
			}
		}

		// Check that the max upload size specified in class configuration does not
		// exceed size allowed by server config
		if ($this->toBytes(ini_get('post_max_size')) < $this->sizeLimit ||
			$this->toBytes(ini_get('upload_max_filesize')) < $this->sizeLimit){
			$size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
			return array('error'=>"Server error. Increase post_max_size and upload_max_filesize to ".$size);
		}

		// is_writable() is not reliable on Windows (http://www.php.net/manual/en/function.is-executable.php#111146)
		// The following tests if the current OS is Windows and if so, merely checks if the folder is writable;
		// otherwise, it checks additionally for executable status (like before).

		$isWin = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
		$folderInaccessible = ($isWin) ? !is_writable($uploadDirectory) : ( !is_writable($uploadDirectory) && !is_executable($uploadDirectory) );

		if ($folderInaccessible){
			return array('error' => "Server error. Uploads directory isn't writable" . ((!$isWin) ? " or executable." : "."));
		}

		if(!isset($_SERVER['CONTENT_TYPE'])) {
			return array('error' => "No files were uploaded.");
		} else if (strpos(strtolower($_SERVER['CONTENT_TYPE']), 'multipart/') !== 0){
			return array('error' => "Server error. Not a multipart request. Please set forceMultipart to default value (true).");
		}

		// Get size and name

		$file = $_FILES[$this->inputName];
		$size = $file['size'];

		if ($name === null){
			$name = $this->getName();
		}

		// Validate name

		if ($name === null || $name === ''){
			return array('error' => 'File name empty.');
		}

		// Validate file size

		if ($size == 0){
			return array('error' => 'File is empty.');
		}

		if ($size > $this->sizeLimit){
			return array('error' => 'File is too large.');
		}

		// Validate file extension

		$pathinfo = pathinfo($name);
		$ext = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';

		if($this->allowedExtensions && !in_array(strtolower($ext), array_map("strtolower", $this->allowedExtensions))){
			$these = implode(', ', $this->allowedExtensions);
			return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
		}

		// Save a chunk
		// is coming one after another (not parallel or in unsorted order) ...
		$totalParts = isset($_REQUEST['qqtotalparts']) ? $_REQUEST['qqtotalparts'] : 1;

		if ($totalParts > 0) {

			$partIndex = $_REQUEST['qqpartindex'];
			$partOffset = $_REQUEST['qqpartbyteoffset'];
			$uuid = $_REQUEST['qquuid'];

			if ($this->directchunking) {
				//error_log("handleUpload($uploadDirectory, $name) chunking: START partIndex=$partIndex partOffset=$partOffset (size=$size) of totalPart=$totalParts uuid=$uuid...");
				$success = true;

				//$tmptarget = $this->chunksFolder.DIRECTORY_SEPARATOR.$uuid.".tmp";
				//$tmptarget = $this->getUniqueTargetPath($uploadDirectory, $name.".partial");
				$tmptarget = $uploadDirectory.DIRECTORY_SEPARATOR.$name.'.'.$uuid.'.'.$this->partialsExtension;
				$inputName = $_FILES[$this->inputName]['tmp_name'];
				//			$written = file_put_contents($tmptarget, file_get_contents($inputName), FILE_APPEND);
				// INFO: if chunks where not coming in consecutive order then will have to use infos from _REQUEST-array ... [qqpartindex, qqpartbyteoffset, qqchunksize, qqtotalparts, qqtotalfilesize] to seek into target file and write directly to that position!
				// TODO: because when retry from GUI then appending another chunk here !?
				$written = $this->chunked_copy($inputName, $tmptarget, $partOffset);

				$r = @unlink($_FILES[$this->inputName]['tmp_name']);
				if ($written !== $size)
				{
					error_log("handleUpload($uploadDirectory, $name) ERROR append copy: written=$written !== size=$size with partOffset=$partOffset ...");
					$success = false;
				}
				else
				{
					if ($r === true)
					{
						//					error_log("handleUpload($uploadDirectory, $name) append copy: ...");
						$success = true;
					}
					else
					{
						error_log("handleUpload($uploadDirectory, $name) ERROR while unlink tmp file in append copy: ...");
						$success = false;
					}
				}

				// Last chunk saved successfully (rename to dest filename)
				if ($success && ($totalParts-1 == $partIndex)) {
					$target = $this->getUniqueTargetPath($uploadDirectory, $name);
					$this->uploadName = basename($target);
					$numtry = 5;
					$rtry = 0;
					$sleeptry = 200000;
					while (!$r = @rename($tmptarget, $target))	// sometimes under windows debugger the rename fails ... (because of touch before in getUniqueTargetPath() ?!
					{
						$rtry++;
						if ($rtry >= $numtry)
						{
							break;
						}
						usleep($sleeptry);
					}
					if ($r === true)
					{
						error_log("handleUpload($uploadDirectory, $name) OK last part added ... finished all ...");
						$success = true;
					}
					else
					{
						error_log("handleUpload($uploadDirectory, $name) ERROR WHILE renaming with append copy: ...");
						$success = false;
					}
				}

				if (!$success)
				{
					return array('error' => "error while append copy chunk", 'success'=> false);
				}
				else
				{
					$success = $this->createThumbnails();
					return array("success" => true);
				}


			} else {
				//
				// OLD behavior
				//

				if (!is_writable($chunksFolder) && !is_executable($uploadDirectory)){
					return array('error' => "Server error. Chunks directory isn't writable or executable.");
				}

				$targetFolder = $chunksFolder.DIRECTORY_SEPARATOR.$uuid;

				if (!file_exists($targetFolder)){
					mkdir($targetFolder);
				}

				$target = $targetFolder.'/'.$partIndex;

				$success = move_uploaded_file($_FILES[$this->inputName]['tmp_name'], $target);

				// Last chunk saved successfully
				if ($success AND ($totalParts-1 == $partIndex)){

					$target = $this->getUniqueTargetPath($uploadDirectory, $name);
					$this->uploadName = basename($target);

					$target = fopen($target, 'wb');

					for ($i=0; $i<$totalParts; $i++){
						$chunk = fopen($targetFolder.DIRECTORY_SEPARATOR.$i, "rb");
						stream_copy_to_stream($chunk, $target);
						fclose($chunk);
					}

					// Success
					fclose($target);

					for ($i=0; $i<$totalParts; $i++){
						unlink($targetFolder.DIRECTORY_SEPARATOR.$i);
					}

					rmdir($targetFolder);

					error_log("handleUpload($uploadDirectory, $name) chunking: LAST FINISHED partIndex=$partIndex ...");
					$success = $this->createThumbnails();
					return array("success" => true);

				}

				error_log("handleUpload($uploadDirectory, $name) chunking: THIS FINISHED partIndex=$partIndex ...");
				return array("success" => true);
			}

		} else {
			error_log("handleUpload($uploadDirectory, $name) NO chunking: totalPart=$totalParts...");

			$target = $this->getUniqueTargetPath($uploadDirectory, $name);

			if ($target){
				$this->uploadName = basename($target);

				if (move_uploaded_file($file['tmp_name'], $target)){
					$success = $this->createThumbnails();
					return array('success'=> true);
				}
			}

			return array('error'=> 'Could not save uploaded file.' .
				'The upload was cancelled, or server error encountered');
		}
	}

	protected function fseek64a(&$fh, $offset)
	{
		fseek($fh, 0, SEEK_SET);

		if ($offset <= PHP_INT_MAX) {
			return fseek($fh, $offset, SEEK_SET);
		}

		$t_offset   = PHP_INT_MAX;
		$offset     = $offset - $t_offset;

		while (fseek($fh, $t_offset, SEEK_CUR) === 0) {
			if ($offset > PHP_INT_MAX) {
				$t_offset   = PHP_INT_MAX;
				$offset     = $offset - $t_offset;
			} else if ($offset > 0) {
				$t_offset   = $offset;
				$offset     = 0;
			} else {
				return 0;
			}
		}

		return -1;
	}

	protected function my_fseek($fp, $pos, $first=true) {
		$r = 0;
		// set to 0 pos initially, one-time
		if ($first) {
			$r = fseek($fp, 0, SEEK_SET);
			if ($r == -1) {
				// ERROR
			}
		}

		if ($r == 0) {
			// get pos float value
			$pos = floatval($pos);

			if ($pos <= PHP_INT_MAX) {	// within limits, use normal fseek
				$r = fseek($fp, $pos, SEEK_CUR);
			} else {	// out of limits, use recursive fseek
				$r = fseek($fp, PHP_INT_MAX, SEEK_CUR);
				if ($r == 0) {
					$pos -= PHP_INT_MAX;
					$r = $this->my_fseek($fp, $pos, false);
				} else {
					// ERROR
				}
			}
		}
		return $r;
	}

	protected function chunked_copy($from, $to, $offset=0) {
		$buffer_size = 256*1024;
		$ret = 0;
		$fin = fopen($from, "rb");
		if (!$fin) {
			$ret = -1;
			goto ende;
		}
		if (!file_exists($to)) touch($to);
		$fout = fopen($to, "r+b");
		if (!$fout) {
			$ret = -1;
			goto ende;
		}

		//		$r = $this->fseek64($fout, $offset);
		$r = $this->my_fseek($fout, $offset);
		if ($r == -1) {
			$ret = -1;
			goto ende;
		}
		while (!feof($fin)) {
			$ret += fwrite($fout, fread($fin, $buffer_size));
		}
		//		$r = ftell($fout);
		//		if ($r != $offset + $ret) {
		//error_log("chunked_copy($from, $to, $offset) r=$r != offset=$offset + ret=$ret ...");
		//			$ret = -1;
		//			goto ende;
		//		}

		ende:
		if ($fin)	fclose($fin);
		if ($fout)	fclose($fout);
		//		x
		//$ret= -1;
		return $ret; # return number of bytes written
	}

	/**
	* Returns a path to use with this upload. Check that the name does not exist,
	* and appends a suffix otherwise.
	* @param string $uploadDirectory Target directory
	* @param string $filename The name of the file to use.
	*/
	protected function getUniqueTargetPath($uploadDirectory, $filename)
	{
		// Allow only one process at the time to get a unique file name, otherwise
		// if multiple people would upload a file with the same name at the same time
		// only the latest would be saved.

		if (function_exists('sem_acquire')){
			$lock = sem_get(ftok(__FILE__, 'u'));
			sem_acquire($lock);
		}

		$pathinfo = pathinfo($filename);
		$base = $pathinfo['filename'];
// Clean the fileName for security reasons
$base = preg_replace('/[^\w\._]+/', '_', $base);
		$ext = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';
		$ext = $ext == '' ? $ext : '.' . $ext;

		$unique = $base;
		$suffix = 0;

		// Get unique file name for the file, by appending suffix.
		while (file_exists($uploadDirectory . DIRECTORY_SEPARATOR . $unique . $ext)){
//			$suffix += rand(1, 999);	// random
			$suffix++;	// consecutive
			$unique = $base.'-'.$suffix;
		}

		$result =  $uploadDirectory . DIRECTORY_SEPARATOR . $unique . $ext;

		// Create an empty target file
		if (!touch($result)){
			// Failed
			$result = false;
		}

		if (function_exists('sem_acquire')){
			sem_release($lock);
		}

		return $result;
	}

	/**
	* Deletes all file parts in the chunks folder for files uploaded
	* more than chunksExpireIn seconds ago
	*/
	protected function cleanupChunks(){
		foreach (scandir($this->chunksFolder) as $item){
			if ($item == "." || $item == "..")
				continue;

			$path = $this->chunksFolder.DIRECTORY_SEPARATOR.$item;

			if (!is_dir($path))
				continue;

			if (time() - filemtime($path) > $this->chunksExpireIn){
				$this->removeDir($path);
			}
		}
	}

	/**
	* Deletes all partial in the output folder for files uploaded
	* more than partialsExpireIn seconds ago
	*/
	protected function cleanupPartials($dir){
		foreach (scandir($dir) as $item){
			if ($item == "." || $item == "..")
				continue;
			if (is_dir($item))
				continue;

			$ext = pathinfo($item, PATHINFO_EXTENSION);
			if ($ext == $this->partialsExtension) {
				$fullpathname = $dir.DIRECTORY_SEPARATOR.$item;
				$old = time() - filemtime($fullpathname);
				if ($old > $this->partialsExpireIn) {
					unlink($fullpathname);
				}
			}
		}
	}

	/**
	* Removes a directory and all files contained inside
	* @param string $dir
	*/
	protected function removeDir($dir){
		foreach (scandir($dir) as $item){
			if ($item == "." || $item == "..")
				continue;

			unlink($dir.DIRECTORY_SEPARATOR.$item);
		}
		rmdir($dir);
	}

	/**
	* Converts a given size with units to bytes.
	* @param string $str
	*/
	protected function toBytes($str){
		$val = trim($str);
		$last = strtolower($str[strlen($str)-1]);
		switch($last) {
			case 'g': $val *= 1024;
			case 'm': $val *= 1024;
			case 'k': $val *= 1024;
		}
		return $val;
	}


	protected function createThumbnails() {
		// TODO: create here thumbnails preview info ...
		global $prindot;
		$success = true;

		$fileName = $this->uploadName;
		$targetDir = $prindot['paths']['storage_root_js'];

$filePath = $targetDir . $fileName;
error_log($_SERVER["SCRIPT_NAME"] . " : " . 'filepath:"' . $filePath . '"');

$filePath_thumbnail = $targetDir . $prindot['paths']['thumbnails'] . DIRECTORY_SEPARATOR . $fileName;
error_log($_SERVER["SCRIPT_NAME"] . " : " . 'filePath_thumb:"' . $filePath_thumbnail . '"');

$filePath_preview = $targetDir . $prindot['paths']['previews'] . DIRECTORY_SEPARATOR . $fileName;
error_log($_SERVER["SCRIPT_NAME"] . " : " . 'filePath_preview:"' . $filePath_preview . '"');


		error_log($_SERVER["SCRIPT_NAME"] . " : " . "written file '" . $filePath . "'");
		// TODO: hier thumbnail und preview generieren (und ggf. info.datei mit wichtigen daten, damit nicht immer wieder aufgefragt werden muss )
		error_log($_SERVER["SCRIPT_NAME"] . " : preview : '" . $filePath_preview . "'");
		error_log($_SERVER["SCRIPT_NAME"] . " : thumbnail : '" . $filePath_thumbnail . "'");


		// thumbs and previews

		// TODO: make preview and thumbnail with dedicated php file (same logic as with logs.php etc)
		// ... so previews may be rendered if deleted before

		$filename = $filePath;

		$dir = $_SERVER["SCRIPT_FILENAME"];
		error_log($_SERVER["SCRIPT_NAME"] . " : " . "dir '" . $dir . "'");

		$dir = dirname($dir) . '/';
		error_log($_SERVER["SCRIPT_NAME"] . " : " . "dir '" . $dir . "'");

		//$dir = $dir . $prindot['paths']['storage_root_js'];
		//error_log($_SERVER["SCRIPT_NAME"] . " : " . "dir '" . $dir . "'");

		/* Create new object */
		//$im = new Imagick( 'D:\xampp\htdocs\prindot\plupload2\examples\uploads\cylinder.jpg' );	// OK
		//$im = new Imagick( __DIR__ . '/' . $filename );
		$im = new Imagick($dir . $filename);

		$i = $im->getImageProperties("*", true);
		//error_log(my_print_r($i));
		$r = $im->getImageResolution();
		//error_log(my_print_r($r));
		$g = $im->getImageGeometry();
		//error_log(my_print_r($g));
		//die();
		// PREVIEW
		/* Scale down */
		$im->thumbnailImage($prindot['dimensions']['previews']['width'], $prindot['dimensions']['previews']['height'], true);
		$im->setImageFormat('jpeg');

		/* save */
		$outpath = $dir . $filePath_preview . ".jpg";
		error_log($_SERVER["SCRIPT_NAME"] . " : " . "outpath '" . $outpath . "'");
		@unlink($outpath);
		$im->writeImage($outpath);

		//echo "preview FERTIG (" . $outpath . ")<br>";
		// THUMBNAIL
		/* Scale down */
		$im->thumbnailImage($prindot['dimensions']['thumbnails']['width'], $prindot['dimensions']['thumbnails']['height'], true);
		$im->setImageFormat('jpeg');

		/* save */
		$outpath = $dir . $filePath_thumbnail . ".jpg";
		error_log($_SERVER["SCRIPT_NAME"] . " : " . "outpath '" . $outpath . "'");
		@unlink($outpath);
		$im->writeImage($outpath);

		//echo "thumbnail FERTIG (" . $outpath . ")<br>";
		return $success;
	}

}


$uploader = new qqFileUploader();
$uploader->allowedExtensions = array();	// Specify the list of valid extensions, ex. array("jpeg", "xml", "bmp")
$uploader->sizeLimit = 10 * 1024 * 1024;	// Specify max file size in bytes.
$uploader->inputName = 'qqfile';	// Specify the input name set in the javascript.
$uploader->chunksFolder = 'chunks';	// If you want to use resume feature for uploader, specify the folder to save parts.
$uploader->chunksFolderAsSubdir = true;
$result = $uploader->handleUpload('uploads');	// Call handleUpload() with the name of the folder, relative to PHP's getcwd()
// To save the upload with a specified name, set the second parameter.
// $result = $uploader->handleUpload('uploads/', md5(mt_rand()).'_'.$uploader->getName());

$result['uploadName'] = $uploader->getUploadName();	// To return a name used for uploaded file you can use the following line.

$r =  json_encode($result);
header("Content-Type: text/plain");
error_log($_SERVER["SCRIPT_NAME"] . " : " . __FILE__ . "(" . __LINE__ . ") r='" . my_print_r($r) . "'");
echo $r;


// TODO: dateien loeschen die *.partial sind und aelter als x stunden ...

?>
