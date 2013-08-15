<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Fine Uploader with Optional jQuery Wrapper demo</title>
		<link href="fineuploader-3.7.1.css" rel="stylesheet">
		<link href="jquery-ui-themes/base/jquery-ui.css" rel="stylesheet">
		<link href="gridinator.css" rel="stylesheet">
		<link href="custom.css" rel="stylesheet">
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
		<script src="jquery-1.9.1.js"></script>
		<script src="jquery-ui.min.js"></script>
		<script src="jquery.fineuploader-3.7.1.js"></script>
	</head>

	<body>
		<script>
			$(function() {
				$( "#tabs" ).tabs({ heightStyle: "auto" });
			});
		</script>

		<div id="wrapper" class="wrapper">
			<div id="inner-wrapper" class="inner-wrapper">
				<div class="twelve-col">
					<div id="tabs">
						<ul>
							<li><a href="#tabs-1">Demo</a></li>
						</ul>
						<div id="tabs-1">
							<div class="seven-col ui-corner-all ui-widget-content myborder">
								<section id="test" class="fubox">
									<h3>Datei zum Server hochladen ...</h3>
									<br>
									<div id="fu"></div>
									<!--									<div id="removeUpload" class="btn">remove uploads</div>-->
									<br />
								</section>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>


		<script>
			var manualuploader = $('#fu').fineUploader({
				request: {
					endpoint: 'fuuploader.php'
				},
				retry: {
					enableAuto: true,
					maxAutoAttempts: 9,
					autoAttemptDelay: 10
				},
				chunking: {
					enabled: true,
					partSize: 10485760	// 10MB
				},
				autoUpload: true,
				multiple: false,
				validation: {
					itemLimit: 1
				},
				resume: {
					enabled: false
				},

        text: {
			uploadButton: '<div class="uploadButtonText"><i class="icon-plus icon-white"></i>Dateien auswählen oder hierhin ziehen</div>',
            cancelButton: 'Abbrechen',
            retryButton: 'Wiederholen',
            deleteButton: 'Löschen',
            failUpload: 'fehlgeschlagen',
            dragZone: 'Dateien hierhin ziehen und loslassen zum hochladen',
            dropProcessing: 'Verarbeite Dateien ...',
            formatProgress: "{percent}% von {total_size}",
            waitingForResponse: "verarbeite ... bitte warten ..."
        },

        messages: {
        	typeError: '{file} has an invalid extension. Valid extension(s): {extensions}.',
        	sizeError: '{file} is too large, maximum file size is {sizeLimit}.',
        	minSizeError: '{file} is too small, minimum file size is {minSizeLimit}.',
        	emptyError: '{file} is empty, please select files again without it.',
        	noFilesError: 'No files to upload.',
        	onLeave: 'The files are being uploaded, if you leave now the upload will be cancelled.',
        	tooManyFilesError: 'You may only drop one file',
        	unsupportedBrowser: 'Unrecoverable error - this browser does not permit file uploading of any kind.'
		},

      template: '<div class="qq-uploader">' +
            '<div id="fuUploadDropArea" class="qq-upload-drop-area"><span>{dragZoneText}</span></div>' +
            '<div id="fuUploadButton" class="qq-upload-button"><div>{uploadButtonText}</div></div>' +
            '<span class="qq-drop-processing"><span>{dropProcessingText}</span><span class="qq-drop-processing-spinner"></span></span>' +
            '<div id="upload-list">' + '<ul class="qq-upload-list"></ul>' + '</div>' +
            '</div>',

        callbacks: {
            onSubmit: function(id, name){console.log("onSubmit id=" + id + " name=" + name);},
            onComplete: function(id, name, response){console.log("onSubmit id=" + id + " name=" + name);}
		},

				debug: false	// prints on javascript console for debugging only
			});

			$('#fu').on('complete', function(event, id, name, response) {
//$('#fuUploadDropArea').show();
				console.log("completed ... hide and reset in a few seconds!");
				setTimeout(function() {
//					$('#fu').hide('fade', '', 800, function() {manualuploader.fineUploader('reset');});
					$('#upload-list').hide('fade', '', 800, function() {$('#fuUploadButton').show('fade', 800, function() {manualuploader.fineUploader('reset');});});
//					manualuploader.fineUploader('reset');
//					$('#fu').show(0);
					},3000);
			});
			$('#fu').on('submit', function(event, id, name, response) {
$('#fuUploadButton').hide();
				console.log("submit ...");
//					manualuploader.fineUploader('reset');
//				$('#upload-list').hide('fade', '', 800, function() {;});
			});
			$('#fu').on('submitted', function(event, id, name, response) {
				console.log("submitted ...");
//					manualuploader.fineUploader('reset');
//				$('#upload-list').hide('fade', '', 800, function() {;});
//$('#fuUploadDropArea').hide();
//$('#fuUploadButton').hide('fade', '', 800);
			});

			$('#fu').on('autoretry', function(event, id, name, response) {
				console.log("autoretry ...");
//					manualuploader.fineUploader('reset');
//				$('#upload-list').hide('fade', '', 800, function() {;});
			});

			$('#fu').on('retry', function(event, id, name, response) {
				console.log("retry ...");
//					manualuploader.fineUploader('reset');
//				$('#upload-list').hide('fade', '', 800, function() {;});
			});

			$('#fu').on('cancel', function(event, id, name, response) {
				$('#upload-list').hide('fade', '', 100, function() {$('#fuUploadButton').show('fade', 800, function() {manualuploader.fineUploader('reset');});});
				console.log("cancel ...");
//					manualuploader.fineUploader('reset');
//				$('#upload-list').hide('fade', '', 800, function() {;});
			});

			$('#fu').on('error', function(event, id, name, response) {
				console.log("error ...");
//					manualuploader.fineUploader('reset');
//				$('#upload-list').hide('fade', '', 800, function() {;});
			});

			$('#fu').on('uploadchunk', function(event, id, name, response) {
				console.log("uploadchunk ...");
//					manualuploader.fineUploader('reset');
//				$('#upload-list').hide('fade', '', 800, function() {;});
			});

			$('#removeUpload').click(function() {
				console.log("click");
				manualuploader.fineUploader('reset');
			});

			// TODO: delete *.partial file on error ... (send event to php ??? )


		</script>

	</body>
</html>
