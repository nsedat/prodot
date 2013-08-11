//////////////////////////////////////////////////
var VERSION = "<br><br>info@prindot.de";
var COPYRIGHT = "(c) 2013 by PRIN DOT GmbH<br>Schnackenburgallee 119 b<br>D-22525 Hamburg";

var LOG_TYPE_STATUS = 0;
var LOG_TYPE_INFO = 1;
var LOG_TYPE_PROGRESS = 2;
var LOG_TYPE_WARNING = 3;
var LOG_TYPE_ERROR = 4;

var LOG_SUBTYPE_APPLICATION = 0;
var LOG_SUBTYPE_MACHINE = 1;
var LOG_SUBTYPE_RASTER = 2;
var LOG_SUBTYPE_JOB = 3;
var LOG_SUBTYPE_IMAGE = 4;

// same defines as in config.inc.php
var JOBSTATUS_CALCSTRAND = -1;
var JOBSTATUS_NEW = 0;
var JOBSTATUS_RUNNING = 1;
var JOBSTATUS_FINISHED = 2;
var JOBSTATUS_CANCELED = 3;
var JOBSTATUS_ERROR = 4;
var JOBSTATUS_NOJOB = 5;
var JOBSTATUS_MACHINE_FILETRANSFER = 6;
var JOBSTATUS_MACHINE_WAITFORMANUALACTION = 7;

var MACHINESTATUS_OK = 0;
var MACHINESTATUS_ERROR = 4;

//////////////////////////////////////////////////
// LOAD OTHER RESOURCES BEFORE:
jQuery.cachedScript = function (url, options) {
	// allow user to set any option except for dataType, cache, and url
	options = $.extend(options || {}, {
		dataType: "script",
		cache: true,
		async: false,
		url: url
	});

	// Use $.ajax() since it is more flexible than $.getScript
	// Return the jqXHR object so we can chain callbacks
	return jQuery.ajax(options);
};

/**
 *
 * @param {type} url
 * @returns {undefined}
 */
function loadjs(url) {
	$.cachedScript(url).always(function (script, textStatus) {
		console.log("loaded script : '" + url + "' : '" + textStatus + "'");
	});
}

/**
 *
 * @param {type} url
 * @param {type} option
 * @returns {undefined}
 */
function loadcss(url, option) {
	$('head').append($('<link rel="stylesheet" type="text/css" ' + option + '/>').attr('href', url));
	console.log("loaded stylesheet : '" + url + "'");
}

loadcss('./gridinator_1112.css', 'media="screen"');

loadjs('./jquery-ui/ui/jquery-ui.js');
loadcss('./jquery-ui/themes/base/jquery-ui.css', 'id="theme"');	// option necessary for theme switcher!

//		//$.cachedScript("./DataTables/media/js/jquery.dataTables.js").done(function(script, textStatus) { console.log( script + " : " + textStatus ); });
loadjs('./DataTables/media/js/jquery.dataTables.js');
//loadjs('./DataTables/extras/TableTools/media/js/TableTools.js');
//loadjs('./DataTables/extras/TableTools/media/js/ZeroClipboard.js');
//		//$.getScript('./DataTables/media/js/jquery.dataTables.js');
//		//document.createStyleSheet('./DataTables/media/css/demo_table.css');
//		//$('head').append( $('<link rel="stylesheet" type="text/css" />').attr('href', './DataTables/media/css/demo_table.css') );
//loadcss('./DataTables/media/css/demo_table.css');
loadcss('./DataTables/media/css/demo_page.css');
loadcss('./DataTables/media/css/demo_table_jui.css');
//$.cachedScript("./DataTables/media/css/demo_table.css").done(function(script, textStatus) { console.log( script + " : " + textStatus ); });

loadjs('./plupload2/js/plupload.full.min.js');
loadjs('./plupload2/js/jquery.ui.plupload/jquery.ui.plupload.js');
loadjs('./plupload2/js/i18n/de.js');
loadcss('./plupload2/js/jquery.ui.plupload/css/jquery.ui.plupload.css');

loadjs('./jCanvas/jcanvas.js');

loadjs('./alertify/lib/alertify.js');
loadcss('./alertify/themes/alertify.core.css');
loadcss('./alertify/themes/alertify.default.css');

loadjs('./jquery-cookie/jquery.cookie.js');

loadjs('./jqueryFileTree/jqueryFileTree.js');
loadcss('./jqueryFileTree/jqueryFileTree.css');

loadjs('./jQuery-Validation-Engine/js/languages/jquery.validationEngine-de.js');
loadjs('./jQuery-Validation-Engine/js/jquery.validationEngine.js');
loadcss('./validationEngine.jquery.css');
loadcss('./validationEngine.template.css');

loadjs('./jquery.timers.js');

loadjs('./jquery.blockUI.js');

loadcss('./extra.css', 'media="screen"');
loadcss('./tabs.css');
loadcss('./my.css');

//////////////////////////////////////////////////
// INIT AND LOAD CONFIG
function getAbsolutePath() {
	var loc = window.location;
	var pathName = loc.pathname.substring(0, loc.pathname.lastIndexOf('/') + 1);
	return pathName;
}
var prindot_protocol = window.location.protocol;
var prindot_hostname = window.location.hostname;
var prindot_port = window.location.port;
var prindot_pathname = getAbsolutePath();
var prindot_admin = false;

// should be for admins: "localhost" or "127.0.0.1"
// TODO: load list of IPs from config to allow additionally! ?
if (prindot_hostname === "localhost" || prindot_hostname === "127.0.0.1") {
	prindot_admin = true;
}

var phpconfig = new Array();

/**
 * dump array etc as like as in php
 *
 * @param {type} arr
 * @param {type} level
 * @returns {String}
 */
function print_r(arr, level) {
	var dumped_text = "";
	if (!level)
		level = 0;

//The padding given at the beginning of the line.
	var level_padding = "";
	for (var j = 0; j < level + 1; j++)
		level_padding += "    ";

	if (typeof(arr) === 'object') { //Array/Hashes/Objects
		for (var item in arr) {
			var value = arr[item];

			if (typeof(value) === 'object') { //If it is an array,
				dumped_text += level_padding + "'" + item + "' {\n";
				dumped_text += print_r(value, level + 1);
				dumped_text += " }\n";
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
			}
		}
	} else { //Stings/Chars/Numbers etc.
		dumped_text = "===>" + arr + "<===(" + typeof(arr) + ")";
	}
	return dumped_text;
}

/**
 * fetches config from php
 * stores data in global array "phpconfig"
 * @param {array} dataarray (not used yet)
 * @returns -
 *
 */
function callgetphpconfigviaajax(dataarray) {
	$.ajax({
		type: "POST",
		dataType: 'json',
		url: "getphpconfig.php",
		data: dataarray,
		async: false,
		success: function (result) {
//			console.log(print_r(result));
		if (result['result'] === 'success') {
//			console.log("callgetphpconfigviaajax: ok");
			phpconfig = result;
		}
		else
		{
//			console.log("callgetphpconfigviaajax: ERROR");
//window.location = 'about:blank';
//document.getElementsByTagName('html')[0].innerHTML = '';
var i = document.childNodes.length-1;

while(i >=0 ) {
  document.removeChild(document.childNodes[i--]);
}
  alert("cannot read phpconfig ...");

//			$.blockUI({message: "cannot read phpconfig ...", title: "Error", timeout: 0});
//			echo("cannot read phpconfig ...");
			throw '';
//			writelog(0, 0, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_APPLICATION, "cannot read phpconfig");
//			myerror("cannot read phpconfig ...");
		}
			//alertify.log(result);
			//$("#phpconfiginfos").text("read");
//						$("#phpconfiginfos").text(print_r(phpconfig));
//						alertify.log(print_r(phpconfig));
//						var key;
//						for (key in result) {
//							alertify.log ("Schlüssel " + key + " mit Wert " + result[key]);
//						}
//						alertify.log("result-urls-test=" + phpconfig['urls']['test']);
		}
	});
}

// load config as soon as possible
callgetphpconfigviaajax({action: "get"});

/**
* test only
*
*/
$(function () {
//	$("#phpconfiginfos").text(print_r(phpconfig));
//	alertify.log(print_r(phpconfig));
});



//////////////////////////////////////////////////
// TABS:
$(function () {
	$("#tabs").tabs({
		//	  event: "mouseover",
		//					fx: {opacity: 'toggle'},
		//disabled: [4],
		show: {effect: "fadeIn", duration: 400},
		hide: {effect: "fadeOut", duration: 200}
//		hide: {effect: "drop",  direction: "down", duration: 200}
	});
});


//////////////////////////////////////////////////
// THEME SWITCHER (not used anymore)
// Initialize the theme switcher:
$(function () {
	'use strict';
	$('#theme-switcher').change(function () {
		var theme = $('#theme');
		theme.prop(
			'href',
			theme.prop('href').replace(
				/[\w\-]+\/jquery-ui.css/,
				$(this).val() + '/jquery-ui.css'
			)
		);
		//alertify.log("theme switched", "success", 2000);
	});
});



//////////////////////////////////////////////////
// COPYRIGHT+VERSION:
var actual_version;
function show_copyright()
{
	actual_version = phpconfig['info']['version'];
	versiontext = "Version : " + actual_version + VERSION;
	$("#versiontext").html(versiontext);
	copyrighttext = COPYRIGHT;
	$("#copyrighttext").html(copyrighttext);

	// TODO: hier timer (10 min) der die config liest und die version vergleicht ... bei unterschied : refresh der seite
	$(document).stopTime('autoupdatecheck_timer');
	$(document).everyTime(phpconfig['settings']['autoupdatecheck_timer_sec'] * 1000, 'autoupdatecheck_timer', function () {
// TODO: hier function draus machen
// TODO: und ajax call an PHP mit aufforderung dbupdate.sql auszufuehren! (logs.php)
//		console.log("checking for new version ...");
		callgetphpconfigviaajax({action: "get"});
		if (actual_version != phpconfig['info']['version'])
		{
//			console.log("new version found ... refreshing!");
//			confirm("new version found ... refreshing!!");
			writelog(0, 0, 0, LOG_TYPE_INFO, LOG_SUBTYPE_APPLICATION, 'new version ' + phpconfig['info']['version'] + ' found (old was ' + actual_version + ') ... application refreshed');
			location.reload(true);
		}
	});

}

$(function () {
	show_copyright();
});



//////////////////////////////////////////////////
// PROTOTYPE for l10n (localization)
var l10n_text = new Array;
function init_l10n()
{
	l10n_text = {
		maintitle: phpconfig['l10n']['text']['maintitle'],
		//tab_welcome_str: '&Uuml;ber'
		tab_welcome_str: phpconfig['l10n']['text']['tab_welcome_str']	// geht auch ohne escaping !?! (wg. HTML header charset UTF-8 und editor hier auch UTF-8)
	};
}
function unescp(x) {
	return $('<div/>').html(x).text();
}
var l10n_attr_src = {
	prindotlogosmall: 'images/prindotlogosmall.png',
	prindotlogoa: 'images/prindotlogoa.png'
};
//var l10n_text = {
//	maintitle: 'PRIN DOT  -  pro line  -  pro dot',
//	//tab_welcome_str: '&Uuml;ber'
//	tab_welcome_str: 'Über'	// geht auch ohne escaping !?! (wg. HTML header charset UTF-8 und editor hier auch UTF-8)
//};
var l10n_attr_title = {
	machineselector: 'Hier werden keine NEUEN Maschinen definiert, sondern nur gemeldete mit weiteren Werten angereichert und ver&auml;ndert. (... und zur Produktion ausgewählt)'
};
var l10n = {
	deletestoragefile_success: 'Datei wurde gel&ouml;scht!',
	deletestoragefile_error: 'Datei wurde nicht gel&ouml;scht!'
};
$(document).ready(function () {
	init_l10n();
	// TEST: dynamically set attributes in HTML after loaded
	//$('#prindotlogosmall').attr('src', l10n_attr_src['prindotlogosmall']);
	//$('#prindotlogoa').attr('src', l10n_attr_src['prindotlogoa']);
	// oder: nur die ersetzen, die im array definiert sind:
	$.each(l10n_attr_src, function (i, v) {
		$('#' + i).attr('src', v);
	});
	$('#maintitle').text(l10n_text['maintitle']);
	//$('#tab_welcome_str').html('&Uuml;ber');
//				$('#tab_welcome_str').text(unescp('&Uuml;ber'));
	$('#tab_welcome_str').text(l10n_text['tab_welcome_str']);
	$('#machine_selector_legend').html('Maschine ausw&auml;hlen');
	$('#machine_describe_legend').html('Maschine beschreiben');
	$('#machineselector').attr('title', unescp(l10n_attr_title['machineselector']));
	//$('#machineselector').attr('title', $('#machineselector').attr('title') + unescp("&auml;"));
	$('#prindot_machine_name_str').text(unescp('Name :'));
	/// ... usw.
});


//////////////////////////////////////////////////
// WRITE LOG
/**
 *
 * @param {type} JID
 * @param {type} MID
 * @param {type} HID
 * @param {type} type (0=status, 1=info, 2=progress, 3=warning, 4=error)
 * @param {type} subtype
 * @param {type} description
 * @returns {undefined}
 */
function writelog(JID, MID, HID, type, subtype, description) {
	JID = JID || 0;
	MID = MID || 0;
	HID = HID || 0;
	type = type || 0;
	subtype = subtype || 0;
	description = description || '';
	var action = {action: "create"};
	writelog_r = $.ajax({
		type: "POST",
		dataType: 'json',
		url: phpconfig['urls']['logs'],
		data: $.extend({}, action, {JID: JID, MID: MID, HID: HID, type: type, subtype: subtype, description: description})
	});
	writelog_r.done(function (result) {
//					alertify.success("writelog() : " + print_r(result));
	});
	writelog_r.fail(function (jqXHR, textStatus) {
		alertify.error("writelog() Request failed: " + textStatus);
// TODO: meldung in db-logs schreiben ... hmpf
		alertify.error('ERROR: cannot write to logs table in database', 0);
	});

// TODO: neee ... alerts nicht hier, sondern im refresh der logtable (mit cookie seit dem letzten logdisplay etc)
	if (type === LOG_TYPE_ERROR) {
		alertify.error(description, 0);
	}
}

//////////////////////////////////////////////////
// TOOLTIP INIT
$(function () {
	$(document).tooltip({
		position: {
//			my: "left bottom",
//			at: "left top-3"}
//					,track: true
			my: "left top",
			at: "left bottom",
			of: "#howto"
		}
	});
});

//////////////////////////////////////////////////
// ALERTIFY
$(function () {
	(function () {
		var proxied = window.alert;
		// fetch alerts from outside
		window.alert = function () {
			// do something here
			writelog(0, 0, 0, LOG_TYPE_WARNING, LOG_SUBTYPE_APPLICATION, "catched foreign alert: " + arguments[0]);
			alertify.log("catched foreign alert: " + arguments[0]);
			//return proxied.apply(this, arguments);
			return false;
		};
	})();
});
$(function () {
//	alert("test-x");	// TEST: grab normal alerts into alertify (works well!)
});

/**
 * show error alert for 60 seconds with additional timestamp
 * @param msg
 */
function myerror(msg) {
	var dn = new Date();
	alertify.error('<b>[' + dn.toLocaleString() + ']</b></br> ' + msg, 60000);
}

//////////////////////////////////////////////////
// SELECT, PREVIEW AND DELETE IMAGE
var prindot_selectedfile_infos = new Array();
var prindot_selectedfile = '';
var fileTreeSelect_selectedfile = '';

function show_selectedfile_infos() {
//TODO : hier wg. rotate +/-90 grad die werte in org speichern und jeweils richtig ausgeben (die Verwendung in der auftragsdefinition ("breite uebernehmen") entsprechend anpassen !
var tmp;
	var resx = parseFloat(prindot_selectedfile_infos['res']['x']);
	var resy = parseFloat(prindot_selectedfile_infos['res']['y']);
	if (prindot_imagerotate === 90 || prindot_imagerotate === 270) {tmp=resx; resx=resy; resy=tmp;}
	var width = parseInt(prindot_selectedfile_infos['size']['width']);
	var height = parseInt(prindot_selectedfile_infos['size']['height']);
	if (prindot_imagerotate == 90 || prindot_imagerotate == 270) {tmp=width; width=height; height=tmp;}
	var width_mm = Math.round(parseFloat(prindot_selectedfile_infos['size']['width_mm']) * 100.0) / 100.0;
	var height_mm = Math.round(parseFloat(prindot_selectedfile_infos['size']['height_mm']) * 100.0) / 100.0;
	if (prindot_imagerotate == 90 || prindot_imagerotate == 270) {tmp=width_mm; width_mm=height_mm; height_mm=tmp;}
	var pixel_width_mm = Math.round(parseFloat(prindot_selectedfile_infos['size']['width_mm']) / parseInt(prindot_selectedfile_infos['size']['width']) * 10000.0) / 10000.0;
	var pixel_height_mm = Math.round(parseFloat(prindot_selectedfile_infos['size']['height_mm']) / parseInt(prindot_selectedfile_infos['size']['height']) * 10000.0) / 10000.0;
	if (prindot_imagerotate == 90 || prindot_imagerotate == 270) {tmp=pixel_width_mm; pixel_width_mm=pixel_height_mm; pixel_height_mm=tmp;}
	$('#selectedfileinfo').html("" + prindot_selectedfile + " : "  + width_mm + " x " + height_mm + " mm" + "<br />" + width + " x " + height + " pixel mit " + resx + " x " + resy + " dpi" + "<br />" + "Pixelgr&ouml;&szlig;e: " + pixel_width_mm + " x " + pixel_height_mm + " mm");
	$('#selectedfileinfob').html("" + prindot_selectedfile + " : "  + width_mm + " x " + height_mm + " mm" + "<br />" + width + " x " + height + " pixel mit " + resx + " x " + resy + " dpi" + "<br />" + "Pixelgr&ouml;&szlig;e: " + pixel_width_mm + " x " + pixel_height_mm + " mm");
}
/**
 * get info (width dpi etc) about given filen (image)
 *
 * @param {type} filename
 * @returns {undefined}
 */
function getimageinfo(filename) {
	getimageinfo_r = $.ajax({
		type: "POST",
		dataType: 'json',
		url: phpconfig['urls']['getimageinfo'],
		data: {name: filename}
	});
	getimageinfo_r.done(function (result) {
		if (result['result'] === 'success') {
			prindot_selectedfile_infos = result['value'];
			//alertify.success(print_r(prindot_selectedfile_infos));
			show_selectedfile_infos();
			alertify.log("Wird zur Produktion genommen: " + prindot_selectedfile_infos['name'] + " ", "success");
			$("#settings_form").children().prop('disabled', false);
			//$("#job_settings").hide("slide", {}, 600);
			$("#settings_form").show();
			$("#tabs").tabs("enable", 3);
			$("#tabs").tabs("option", "active", 3);
		}
		else {
			// TODO log und selectedfile ungueltig und tabs sperren
			alertify.error("cannot use image because : " + result['reason']);
			$('#selectedfileinfo').text("" + result['reason']);
			$('#selectedfileinfob').text("" + result['reason']);
			prindot_selectedfile = '';
			$("#settings_form").children().prop('disabled', true);
			$("#settings_form").hide();
			$("#tabs").tabs("disable", 3);
		}
	});
	getimageinfo_r.fail(function (jqXHR, textStatus) {
		myerror("Request failed: " + textStatus);
		$('#selectedfileinfo').text("Request failed: " + textStatus);
		$('#selectedfileinfob').text("Request failed: " + textStatus);
		prindot_selectedfile = '';
		$("#settings_form").children().prop('disabled', true);
		$("#settings_form").hide();
		$("#tabs").tabs("disable", 3);
	});
}

/**
 * show file tree and handle selection
 *
 * @returns {undefined}
 */
function fileTreeSelect() {
	$('#fileTreeSelect').fileTree({
		//root: './prindot/plupload2/examples/uploads/',
		root: prindot_pathname + phpconfig['paths']['storage_root_js'],
		script: phpconfig['urls']['filetreescript'],
		folderEvent: 'click',
		expandSpeed: 750,
		collapseSpeed: 350,
		multiFolder: false,
		height: 300
	}, function (file) {
		prindot_selectedfile = file;
		fileTreeSelect_selectedfile = file;	// used for delete operation only
		thumbnail = phpconfig['paths']['storage_root_js'] + phpconfig['paths']['thumbnails'] + '/' + prindot_selectedfile + '.jpg';
		$('#selectedfilesrc').attr('src', thumbnail);
		$('#selectedfilesrcb').attr('src', thumbnail);
		getimageinfo(prindot_selectedfile);

//		if (prindot_selectedfile != '')
//		{
//			alertify.log("Wird zur Produktion genommen: " + file + " ", "success");
//			$("#settings_form").children().prop('disabled', false);
//			//$("#job_settings").hide("slide", {}, 600);
//			$("#settings_form").show();
//			$("#tabs").tabs("enable", 3);
//		}

//$("#tabs").tabs("option", {disable 3});
	});
};

/**
 * delete given file (image) from filesystem
 * including thumbnails and previews
 *
 * @param {type} filename
 * @returns {undefined}
 */
function deletestoragefile(filename) {
	deletestoragefile_r = $.ajax({
		type: "POST",
		dataType: 'json',
		url: phpconfig['urls']['storage'],
		data: {action: 'delete', filename: filename}
	});
	deletestoragefile_r.done(function (result) {
		if (result['result'] === 'success') {
			alertify.log(l10n['deletestoragefile_success'] + ' : ' + filename);
//TODO writelog()
			writelog(0, 0, 0, LOG_TYPE_INFO, LOG_SUBTYPE_IMAGE, l10n['deletestoragefile_success'] + ' : ' + filename);
			prindot_selectedfile = '';
			fileTreeSelect_selectedfile = '';
//					$(document).oneTime(1000, 'filetreetimer', function() {	// damit auch die 100% noch dargestellt werden
			fileTreeSelect();
//					});
			// und thumbnail zuruecksetzen
			$('#selectedfilesrc').attr('src', noimage);
			$('#selectedfilesrcb').attr('src', noimage);
			// und auftrags-tab-deaktivieren (bzw. form)
			$("#tabs").tabs("disable", 3);
//$("#tabs").tabs("option", {disable 3});
//$("#tabs").tabs("option", {active: 1});
		}
		else {
//			alertify.error(l10n['deletestoragefile_error'] + ' : ' + prindot_selectedfile);
			writelog(0, 0, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_IMAGE, l10n['deletestoragefile_error'] + ' : ' + filename);
		}
	});
	deletestoragefile_r.fail(function (jqXHR, textStatus) {
//		alertify.error("Request failed: " + textStatus);
//		alertify.error(l10n['deletestoragefile_error'] + ' : ' + prindot_selectedfile);
		writelog(0, 0, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_IMAGE, l10n['deletestoragefile_error'] + ' : ' + filename);
	});
}


var noimage = "./images/noimage.png";

$(function () {
	$("#tabs").tabs("disable", 3);	// disable tab 3 ("Auftrag" by default - will be enabled when selected an image

	/**
	 * show preview when clicked on thumbnail
	 */
	$("#selectedfilepreview").click(function () {
		if (prindot_selectedfile !== '') {
			preview = phpconfig['paths']['storage_root_js'] + phpconfig['paths']['previews'] + '/' + prindot_selectedfile + '.jpg';
			alertify.set({labels: {ok: "Ok"}});
//			alertify.alert('<div><h3>Datei preview : ' + prindot_selectedfile + '?</h3><img style="border:4px red solid" src="' + preview + '"/><br/></div>');
			alertify.alert('<div><h3>Datei preview : ' + prindot_selectedfile + '?</h3><img class="redwideborder" src="' + preview + '"/><br/></div>');
		}
	});
	$("#selectedfilepreviewb").click(function () {
		if (prindot_selectedfile !== '') {
			preview = phpconfig['paths']['storage_root_js'] + phpconfig['paths']['previews'] + '/' + prindot_selectedfile + '.jpg';
			alertify.set({labels: {ok: "Ok"}});
//			alertify.alert('<div><h3>Datei preview : ' + prindot_selectedfile + '?</h3><img style="border:4px red solid" src="' + preview + '"/><br/></div>');
			alertify.alert('<div><h3>Datei preview : ' + prindot_selectedfile + '?</h3><img class="redwideborder" src="' + preview + '"/><br/></div>');
		}
	});

	$("#buttondeleteimage").click(function () {
		if (fileTreeSelect_selectedfile !== '') {
			thumbnail = phpconfig['paths']['storage_root_js'] + phpconfig['paths']['thumbnails'] + '/' + fileTreeSelect_selectedfile + '.jpg';
			//preview = phpconfig['paths']['storage_root_js'] + phpconfig['paths']['previews'] + '/' + file + '.jpg';
			alertify.set({labels: {ok: "L&ouml;schen", cancel: "nicht l&ouml;schen"}});
			alertify.set({buttonFocus: "cancel"}); // "none", "ok", "cancel"
			alertify.confirm('<h3>Datei l&ouml;schen : ' + fileTreeSelect_selectedfile + '?</h3><img src="' + thumbnail + '"/><br/>', function (e) {
				if (e) {
					//alertify.success("You've clicked OK");
					//alertify.log("Wird gel&ouml;scht: " + prindot_selectedfile + " ...");
					// call an storage.php mit loeschen von file (+thumb +preview)
					deletestoragefile(fileTreeSelect_selectedfile);
				} else {
					//alertify.log("Abgebrochen");
				}
			});
		}
		else {
			//alertify.log("kein bild");
		}
	});
});

// Initialize jqueryFileTree and update button
$(function () {
	fileTreeSelect();
	$("#fileTreeSelectUpdateButton").click(function () {
		fileTreeSelect();
	});
});


//////////////////////////////////////////////////
// READ AND WRITE MACHINES
// array to hold actual job definition to be saved to db (or read beforehand from db into this array)
var prindot_job = new Array();

// TODO: liste im tab loeschen
var prindot_machines = new Array();
function resetallmachines() {
	$("#machineselector").children().remove();
	prindot_machines = new Array();
}
function readallmachines() {
	readallmachines_r = $.ajax({
		type: "POST",
		dataType: 'json',
		url: phpconfig['urls']['machines'],
		data: {action: "fetchall"}
	});
	readallmachines_r.done(function (result) {
		if (result['result'] === 'success')
		{
			// hier die result-liste in maschinen-tab-auswahl-liste eintragen (und vorselektierte (ggf. letzte aus cookie) die werte uebertragen in felder
			prindot_machines.length = 0;
			for (id in result['value']) {
				prindot_machines[result['value'][id]['MID']] = result['value'][id];
			}
			for (MID in prindot_machines) {
				$("#machineselector").append('<option value="' + MID + '">' + prindot_machines[MID]['name'] + '</option>');
			}
			// den aktiven ggf. aus Cookie laden
			selected_machine = $.cookie('selected_machine');
			if (selected_machine === undefined) {
				//		alertify.log("cookie undefined! ... defining last machine");
				selected_machine = MID;
				$.cookie('selected_machine', selected_machine);
			}
			else {
				//		alertify.log("cookie = " + selected_machine + " selecting");
				//		$.removeCookie('selected_machine');
			}
			// TODO: pruefen, ob cookie ueberhaupt mit einem aus der liste uebereinstimmt ... sonst ersten selected setzen
			$("#machineselector option[value='" + selected_machine + "']").attr('selected', true);
			// DONE: funktion bauen, die die werte aus der selektion (oder ersten maschine) liest und unten alles ausfuellt
			$("#machineselector").change();
			writelog(0, 0, 0, LOG_TYPE_INFO, LOG_SUBTYPE_MACHINE, 'successfully read machines from database');
		}
		else
		{
			writelog(0, 0, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_MACHINE, 'ERROR reading machines from database (result!=success)');
		}
	});
	readallmachines_r.fail(function (jqXHR, textStatus) {
		writelog(0, 0, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_MACHINE, 'ERROR reading machines from database (ajax failed)');
		//alertify.error("Request failed: " + textStatus);
	});
}

function writemachine(MID) {
	var action = {action: "update"};
	writemachine_r = $.ajax({
		type: "POST",
		dataType: 'json',
		url: phpconfig['urls']['machines'],
		data: $.extend({}, action, prindot_machines[MID])
	});
	writemachine_r.done(function (result) {
		if (result['result'] === 'success')
		{
			alertify.success('successfully update machine "' + prindot_machines[MID]['name'] + '" [' + MID + '] in database');
			writelog(0, 0, 0, LOG_TYPE_INFO, LOG_SUBTYPE_MACHINE, 'successfully update machine "' + prindot_machines[MID]['name'] + '" [' + MID + '] in database');
		}
		else
		{
			writelog(0, 0, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_MACHINE, 'error while update machine "' + prindot_machines[MID]['name'] + '" [' + MID + '] in database (result!=success)');
		}
	});
	writemachine_r.fail(function (jqXHR, textStatus) {
		//		alertify.error('error while update machine "' + prindot_machines[MID]['name'] + '" [" + MID + "]in database' + ' - Request failed: ' + textStatus);
		writelog(0, 0, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_MACHINE, 'error while update machine "' + prindot_machines[MID]['name'] + '" [' + MID + '] in database (' + textStatus + ', ajax failed)');
	});
}

/**
* doc ready: load machines
*
*/
$(function () {
	resetallmachines();
	readallmachines();
});


//////////////////////////////////////////////////
// READ RASTER
var prindot_rasters = new Array();
function resetallraster() {
	$("#rasterselector").children().remove();
	prindot_rasters = new Array();
}
function readallraster() {
	readallraster_r = $.getJSON(
		phpconfig['urls']['raster']
	);
	readallraster_r.done(function (result) {
		if (result['result'] === 'success')
		{
			prindot_rasters.length = 0;
			j = 0;
			prindot_rasters[j] = {'name': '-', 'dx': 0, 'dy': 0};
			for (i in result['raster']) {
				++j;
				//console.log("i=" + i + " : " + print_r(result['raster'][i]));
				prindot_rasters[j] = result['raster'][i];
			}
			for (i in prindot_rasters) {
				$("#rasterselector").append('<option value="' + i + '">' + prindot_rasters[i]['name'] + '</option>');
			}
			prindot_raster = 0;
			$("#rasterselector option[value='" + prindot_raster + "']").attr('selected', true);
			$("#rasterselector").change();
			writelog(0, 0, 0, LOG_TYPE_INFO, LOG_SUBTYPE_RASTER, 'successfully read ' + prindot_rasters.length + ' raster settings from file');
		}
		else
		{
			writelog(0, 0, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_RASTER, 'ERROR reading raster settings from file (result!=success)');
		}
	});
	readallraster_r.fail(function (jqXHR, textStatus) {
		//alertify.error("Request failed: " + textStatus);
//		alertify.error("ERROR reading raster settings from file");
		writelog(0, 0, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_RASTER, 'ERROR reading raster settings from file (ajax failed)');
	});
}

/**
* doc ready: load raster
*
*/
$(function () {
	resetallraster();
	readallraster();
});


//////////////////////////////////////////////////
// CREATE JOB
/**
 * create job into database
 * @returns {undefined}
 */
function createjob() {
	var action = {action: "create"};
	createjob_r = $.ajax({
		type: "POST",
		dataType: 'json',
		url: phpconfig['urls']['jobs'],
		data: $.extend({}, action, prindot_job)
	});
	createjob_r.done(function (result) {
		if (result['result'] === 'success') {
			alertify.success('sucessfully created job in database');
			writelog(0, 0, 0, LOG_TYPE_INFO, LOG_SUBTYPE_JOB, 'sucessfully created job (' + prindot_job['name'] + ') in database');
			// hier dann tab ausgrauen, bild zuruecksetzen und aktuellen tab  auf jobtable setzen
			prindot_selectedfile = '';
			$('#selectedfilesrc').attr('src', noimage);
			$('#selectedfilesrcb').attr('src', noimage);
			$("#settings_form").children().prop('disabled', true);
			$("#settings_form").hide();
			$("#job_settings").hide();
			$("#tabs").tabs("option", "active", 4);
			$("#tabs").tabs("disable", 3);
		}
		else {
//			alertify.error('error while creating job in database : ' + result['reason'], 0);
			// !! writelog with LOG_TYPE_ERROR will display same message as alertify to stay
			writelog(0, 0, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_JOB, 'error while creating job in database : ' + result['reason']);
		}
	});
	createjob_r.fail(function (jqXHR, textStatus) {
//		alertify.error('error while creating job in database : Request failed: ' + textStatus, 0);
		writelog(0, 0, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_JOB, 'error while creating job in database : Request failed: ' + textStatus);
	});
}

//////////////////////////////////////////////////
// LOGTABLE
var LogTable;
var asInitVals = new Array();
function init_logtable() {
	LogTable = $('#logtable').dataTable({
		"bProcessing": true,
		"bServerSide": true,
		"sAjaxSource": phpconfig['urls']['tablelogs'],
		"iDisplayLength": 20,
		"aLengthMenu": [
			[3, 10, 20],
			[3, 10, 20]
		],
		"aaSorting": [
			[0, 'desc']
		],
		"aoColumnDefs": [
			{"aTargets": [0], "mRender": function (data, type, full) {
				//return '# <b>"' + data + '"</b>';
				return '<i>' + data + '</i>';
			}},
			{"aTargets": [1], "mRender": function (data, type, full) {	// JID == 0
				if (parseInt(data) == 0)	return '-';
				return data;
			}},
			{"aTargets": [2], "mRender": function (data, type, full) {	// MID == 0
				if (parseInt(data) == 0)	return '-';
				if (prindot_machines[data])
					data = '' + prindot_machines[data]['name'] + '';
				return data;
			}},
			{"aTargets": [3], "mRender": function (data, type, full) {	// HID == 0
				if (parseInt(data) == 0)	return '-';
				return data;
			}},
			{"sWidth": "50%", "aTargets": [4], "mRender": function (data, type, full) {	// timestamp
				if (data !== null) {
					return data.replace(' ', '&nbsp;');
				} else {
					return data;
				}
			}},
			{"aTargets": [5], "mRender": function (data, type, full) {	// address
//				data = data.replace('::1', 'localhost');
				if (data.substring(0,3) == "::1")
				{
					return '(local)';
				}
				else
				{
					return data.slice(0, data.lastIndexOf(':'));
				}
			}},
			{"aTargets": [6], "mRender": function (data, type, full) {
				switch (parseInt(data)) {
					case LOG_TYPE_STATUS:
						return 'Status';
						break;
					case LOG_TYPE_INFO:
						return 'Info';
						break;
					case LOG_TYPE_PROGRESS:	// never used
						return 'Fortschritt';
						break;
					case LOG_TYPE_WARNING:
						return 'Warnung';
						break;
					case LOG_TYPE_ERROR:
						return 'Fehler';
						break;
					default:
						return 'unbekannt : ' + data;
						break;
				}
			}
			},
			{"aTargets": [7], "mRender": function (data, type, full) {
				switch (parseInt(data)) {
					case LOG_SUBTYPE_APPLICATION:
						return 'GUI';
						break;
					case LOG_SUBTYPE_MACHINE:
						return 'Maschine';
						break;
					case LOG_SUBTYPE_RASTER:
						return 'Raster';
						break;
					case LOG_SUBTYPE_JOB:
						return 'Auftrag';
						break;
					case LOG_SUBTYPE_IMAGE:
						return 'Bild';
						break;
					default:
						return 'unbekannt : ' + data;
						break;
				}
			}
			}
		],
		"aoColumns": [
			{"mData": 'LID'},
			{"mData": 'JID'},
			{"mData": 'MID'},
			{"mData": 'HID'},
			{"sWidth": "140px", "mData": 'timestamp'},
			{"mData": 'remoteaddr'},
			{"mData": 'type'},
			{"mData": 'subtype'},
			{"mData": 'description'}
    	],
		"oLanguage": {
			"sProcessing": "Bitte warten...",
			"sLengthMenu": "_MENU_ Einträge anzeigen",
			"sZeroRecords": "Keine Einträge vorhanden.",
			"sInfo": "_START_ bis _END_ von _TOTAL_ Einträgen",
			"sInfoEmpty": "0 bis 0 von 0 Einträgen",
			"sInfoFiltered": "(gefiltert von _MAX_  Einträgen)",
			"sInfoPostFix": "",
			"sSearch": "alles durchsuchen",
			"sUrl": "",
			"oPaginate": {
				"sFirst": "Erster",
				"sPrevious": "Zurück",
				"sNext": "Nächster",
				"sLast": "Letzter"
			}
		},
		"sScrollY": 400,
		"bJQueryUI": true,
		"sPaginationType": "full_numbers"
	});

	LogTable.fnSetColumnVis(5, false, false);	// spalte ausblenden
	LogTable.fnAdjustColumnSizing(true);
	/*
	* Support functions to provide a little bit of 'user friendlyness' to the textboxes in
	* the footer
	*/
	$("tfoot input").keyup(function () {
//		var dt1_offset = 0;
//		var dt1_end = 41;
//		var dt2_offset = 42;
//		var dt2_end = 50;
		var dt1_offset = -1;
		var dt1_end = -1;
		var dt2_offset = 0;
		var dt2_end = 8;
		i = $("tfoot input").index(this);
		console.log("keyup i=" + i);
		if (i >= dt1_offset && i <= dt1_end) {
			console.log("keyup ... jobtable");
			JobTable.fnFilter( this.value, i-dt1_offset );
		} else
		if (i >= dt2_offset && i <= dt2_end) {
			console.log("keyup ... logtable");
			LogTable.fnFilter( this.value, i-dt2_offset );
		}
	});

	$("tfoot input").each(function (i) {
		if ($(this).hasClass("dt1")) {
			asInitVals[i] = this.value;
		} else
		if ($(this).hasClass("dt2")) {
			asInitVals[i] = this.value;
		}
	});

	$("tfoot input").focus(function () {
		if ($(this).hasClass("search_init1")) {
			$(this).removeClass("search_init1");
			this.value = "";
		} else
		if ($(this).hasClass("search_init2")) {
			$(this).removeClass("search_init2");
			this.value = "";
		}
	});

	$("tfoot input").blur(function (i) {
		if ($(this).hasClass("dt1")) {
			$(this).addClass("search_init1");
			this.value = asInitVals[$("tfoot input").index(this)];
		} else
		if ($(this).hasClass("dt2")) {
			$(this).addClass("search_init2");
			this.value = asInitVals[$("tfoot input").index(this)];
		}
	});
}

$(function () {
	init_logtable();
	$("#logsUpdateButton").button({icons: {primary: 'ui-icon-arrowrefresh-1-s'}});
	$("#logsUpdateButton").click(function () {
		LogTable.fnDraw(false);
	});
	$('#logtable tbody').on('click', 'tr', function () {
		var sTitle;
		//var nTds = $('td', this);
		var d = LogTable.fnGetData(this);
		//sTitle = 'TR SELECTED : (' + $(nTds[0]).text() + ') ' + $(nTds[2]).text() + ' date: ' + $(nTds[4]).text() + ' description ' + $(nTds[7]).text();
		//sTitle = 'TR SELECTED : (' + $(nTds[0]).text() + ') (' + $(nTds[1]).text() + ') (' + $(nTds[2]).text() + ') (' + $(nTds[3]).text() + ') (' + $(nTds[4]).text() + ') (' + $(nTds[5]).text() + ') (' + $(nTds[6]).text() + ') (' + $(nTds[7]).text() + ')';
		sTitle = 'TR SELECTED : (' + print_r(d) + ')';
//		alertify.log(sTitle);
// WORKS: should be timed (setinterval oder so aehnlich)
//		LogTable.fnDraw(true);	// true: auto sort/filter/scroll ... vielleicht als checkbox unter table! (ebenso wie enable/disable auto-reload)
//LogTable.fnSetColumnVis( 4, false );	// spalte ausblenden
//		$(document).stopTime('logtimer');
	});
	$(document).stopTime('logtable_timer');
	$(document).everyTime(phpconfig['settings']['logtable_timer_sec'] * 1000, 'logtable_timer', function () {
		var active = $( "#tabs" ).tabs( "option", "active" );
		if (active === 6)
		{
			console.log("LogTable.fnDraw from logtable_timer");
			LogTable.fnDraw(false);
		}
	});
});

//////////////////////////////////////////////////
// JOBTABLE and JOBFUNCTIONS
var JobTable;
function init_jobtable() {
	JobTable = $('#jobtable').dataTable({
		"bProcessing": true,
		"bServerSide": true,
		"sAjaxSource": phpconfig['urls']['tablejobs'],
		"iDisplayLength": 10,
		"aLengthMenu": [
			[3, 5, 10],
			[3, 5, 10]
		],
		"aaSorting": [
			[0, 'desc']
		],
		"aoColumnDefs": [
			{"aTargets": [0], "mRender": function (data, type, full) {	// "JID"
				//return '# <b>"' + data + '"</b>';
				return '#' + data + '';
			}},
			{"aTargets": [1], "mRender": function (data, type, full) {	// "MID" statt MID den Namen der Maschine anzeigen
				if (prindot_machines[data] === undefined) {
					return "undefined";
				}
				else {
					return prindot_machines[data]['name'];
				}
			}},
			{"aTargets": [5], "mRender": function (data, type, full) {	// "mode"
				switch (parseInt(data)) {
					case 0:
						//return 'unknown';
						return '?';
						break;
					case 1:
						//return 'GRAVUR';
						return ':::';
						break;
					case 2:
						//return 'RILLE';
						return '|||';
						break;
					default:
						return data;
						break;
				}
			}},
			{"aTargets": [7], "mRender": function (data, type, full) {	// "pit_dist_perimeter_mm" werte mit vielen Nachkommastellen kuerzen
				return Math.round(data * 100000) / 100000;
			}},
			{"aTargets": [12], "mRender": function (data, type, full) {	// "trackdistance_mm" werte mit vielen Nachkommastellen kuerzen
				return Math.round(data * 100000) / 100000;
			}},
			{"aTargets": [14], "mRender": function (data, type, full) {	// "status"
				switch (parseInt(data)) {
					case JOBSTATUS_CALCSTRAND:
						return 'Daten berechnen';
						break;
					case JOBSTATUS_NEW:
						return 'neu';
						break;
					case JOBSTATUS_RUNNING:
						return 'Maschine l&auml;uft';
						break;
					case JOBSTATUS_FINISHED:
						return 'beendet';
						break;
					case JOBSTATUS_CANCELED:
						return 'abgebrochen';
						break;
					case JOBSTATUS_ERROR:
						return 'FEHLER';
						break;
					case JOBSTATUS_NOJOB:
						return 'kein Auftrag';
						break;
					case JOBSTATUS_MACHINE_FILETRANSFER:
						return 'Maschine lädt Daten';
						break;
					case JOBSTATUS_MACHINE_WAITFORMANUALACTION:
						return 'Maschine wartet auf manuellen Eingriff';
						break;
					default:
						return data;
						break;
				}
			}},
			{"aTargets": [21], "mRender": function (data, type, full) {	// "skalierung/1:1"
				if (parseInt(data) == 1) {
					return 'Skalierung';
				} else {
					return '1:1';
				}
			}}
		],
		"oLanguage": {
			"sProcessing": "Bitte warten...",
			"sLengthMenu": "_MENU_ Einträge anzeigen",
			"sZeroRecords": "Keine Einträge vorhanden.",
			"sInfo": "_START_ bis _END_ von _TOTAL_ Einträgen",
			"sInfoEmpty": "0 bis 0 von 0 Einträgen",
			"sInfoFiltered": "(gefiltert von _MAX_  Einträgen)",
			"sInfoPostFix": "",
			"sSearch": "Suchen",
			"sUrl": "",
			"oPaginate": {
				"sFirst": "Erster",
				"sPrevious": "Zurück",
				"sNext": "Nächster",
				"sLast": "Letzter"
			}
		},
		"sScrollX": "100%",
//        "sScrollXInner": "110%",
		"bScrollCollapse": false,
		"aoColumns": [
			{"mData": 'JID'},
			{"mData": 'MID'},
			{"mData": 'name'},
			{"mData": 'comment'},
			{"sWidth": "140px", "mData": 'create_time'},
			{"mData": 'mode'},
			{"mData": 'perimeter_pit_count'},
			{"mData": 'pit_dist_perimeter_mm'},
			{"mData": 'perimeter_mm'},
			{"mData": 'track_count'},
			{"mData": 'width_mm'},
			{"mData": 'trackoffset_mm'},
			{"mData": 'pit_dist_horizontal_mm'},
			{"mData": 'head_count'},
			{"mData": 'status'},
			{"mData": 'start_time'},
			{"mData": 'end_time'},
			{"mData": 'progress'},
			{"mData": 'input_image'},
			{"mData": 'image_rotation'},
			{"mData": 'image_mirror'},
			{"mData": 'image_scale_flag'},
			{"mData": 'image_scale_x'},
			{"mData": 'image_scale_y'},
			{"mData": 'image_offsetx_mm'},
			{"mData": 'image_offsety_mm'},
			{"mData": 'headstart_1_mm'},
			{"mData": 'headend_1_mm'},
			{"mData": 'headstart_2_mm'},
			{"mData": 'headend_2_mm'},
			{"mData": 'headstart_3_mm'},
			{"mData": 'headend_3_mm'},
			{"mData": 'headstart_4_mm'},
			{"mData": 'headend_4_mm'},
			{"mData": 'headstart_5_mm'},
			{"mData": 'headend_5_mm'},
			{"mData": 'headstart_6_mm'},
			{"mData": 'headend_6_mm'},
			{"mData": 'headstart_7_mm'},
			{"mData": 'headend_7_mm'},
			{"mData": 'headstart_8_mm'},
			{"mData": 'headend_8_mm'}
		],
		"fnDrawCallback": function (oSettings) { // REMOVED: add title for each line
// TODO: possibly here: check/select line if was selected before refresh ?!
//alertify.log( 'DataTables has redrawn the table : ' + print_r(1) );
//			$('#jobtable tbody tr').each(function() {
//				var sTitle;
//				var nTds = $('td', this);
//				var sJID = $(nTds[0]).text();
//				sTitle = "olla : " + sJID;
//				var d = JobTable.fnGetData(this);
//				sTitle = 'TR : (' + print_r(d) + ')';
//				sTitle = d['comment'];
//				this.setAttribute('title', sTitle);
//			});
		},
		"bDeferRender": true,
		"sScrollY": 330,
		"bJQueryUI": true,
		"sPaginationType": "full_numbers",
		"sDom": 'T<"clear">lfrtip',
//		"oTableTools": {
//				"sRowSelect": "single",
//				"sSelectedClass": "row_selected"
//				}

	});

//	JobTableTools = new TableTools(JobTable, {
//				"sRowSelect": "single",
//				"sSelectedClass": "row_selected",
//				"fnRowSelected": function ( nodes ) {
//                alert( 'The row with ID '+nodes[0].id+' was selected' );
//            }
//	});
//	$('#jobtable').before( JobTableTools.dom.container );
}
/**
 * Toggle less/all job table entries
 * and change text/icon of button
 * @param show {true|false}
 */
function toggle_jobtable_columns(show) {
	if (show === undefined) {
//					alertify.log("toggle_jobtable_columns show undefined");
		var bVis = JobTable.fnSettings().aoColumns[37].bVisible;	// true oder false fuer spalte 37
		bVis = bVis ? false : true;
	}
	else {
		bVis = show;
	}
	if (bVis === true) {
		$("#jobs_showall").button("option", "icons", {primary: 'ui-icon-minus'});
	}
	else {
		$("#jobs_showall").button("option", "icons", {primary: 'ui-icon-plus'});
	}
	JobTable.fnSetColumnVis(41, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(40, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(39, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(38, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(37, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(36, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(35, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(34, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(33, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(32, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(31, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(30, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(29, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(28, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(27, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(26, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(25, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(24, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(23, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(22, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(21, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(20, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(19, bVis, false);	// spalte ausblenden
	JobTable.fnSetColumnVis(11, bVis, false);	// Spurversatz
	JobTable.fnSetColumnVis(9, bVis, false);	// Spuranzahl
	JobTable.fnSetColumnVis(6, bVis, false);	// Naepfchen Umfang Anzahl
	JobTable.fnSetColumnVis(3, bVis, false);	// Info : false not redraw ... sonst wueder jedemal ein redraw gemacht ... (also ca. 30 x) !!)

//	JobTable.fnSetColumnVis(1, true, true);	// Info
//	JobTable.fnDraw(false);
	JobTable.fnAdjustColumnSizing(true);
}

/**
* reset job status to given one in case of an unrecoverable error or ...
*
* @param JID
* @param MID
* @param status
*/
function set_job_status(JID, MID, status)
{
	logstatus_r = $.ajax({
		type: "POST",
		dataType: 'json',
		url: phpconfig['urls']['logs'],
		data: ({action: "status", JID: JID, MID: MID, status: status})
	});
	logstatus_r.then(function () {
		logprogress_r = $.ajax({
			type: "POST",
			dataType: 'json',
			url: phpconfig['urls']['logs'],
			data: ({action: "progress", JID: JID, MID: MID, progress: 0})
		});
		logprogress_r.then(function () {
			$(document).oneTime(2000, 'jobtable_timer', function () {	// damit auch die 0% noch dargestellt werden
				JobTable.fnDraw(false);
			});
		});
	});

//		writelog(JID, MID, 0, LOG_TYPE_WARNING, LOG_SUBTYPE_JOB, "job  (" + JID + ") canceled by user");
}

/**
 * start job ... send command to machine (via jobs.php)
 * @param {int} JID
 * @param {string} MID
 * @returns -
 */
function startjob(JID, MID) {
	startjob_r = $.ajax({
		type: "POST",
		dataType: 'json',
		url: phpconfig['urls']['jobs'],
		data: {action: "start", JID: JID}
	});
	startjob_r.done(function (result) {
		if (result['result'] === 'success') {
			writelog(JID, MID, 0, LOG_TYPE_STATUS, LOG_SUBTYPE_JOB, "successfully send job (" + JID + ") to machine '" + prindot_machines[MID]['name'] + "' [" + MID + "]");
//			alertify.success("successfully send job (" + JID + ") to machine '" + prindot_machines[MID]['name'] + "' [" + MID + "] : " + print_r(result));
			alertify.success("Auftrag (" + JID + ") erfolgreich an Maschine '" + prindot_machines[MID]['name'] + "' [" + MID + "] gesendet");
		}
		else
		{
//			writelog(JID, MID, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_JOB, "Error received for job (" + JID + ") by machine '" + prindot_machines[MID]['name'] + "' [" + MID + "] : " + result['reason']);
			writelog(JID, MID, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_JOB, "Error beim starten des Auftrages #" + JID + " auf der Maschine '" + prindot_machines[MID]['name'] + "' [" + MID + "] : " + result['reason']);
		}
	});
	startjob_r.fail(function (jqXHR, textStatus) {
//		alertify.error("Error sending job (" + JID + ") to machine '" + prindot_machines[MID]['name'] + "' [" + MID + "] : " + textStatus);
		writelog(JID, MID, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_JOB, "Error sending job (" + JID + ") to machine '" + prindot_machines[MID]['name'] + "' [" + MID + "] : " + textStatus + " :: XMLFILENAME=" + XMLFILENAME);
		set_job_status(JID, MID, JOBSTATUS_ERROR);
	});
	startjob_r.then(function () {
console.log("startjob() ... then ...");
// TODO: and start after 2 sec or so (to give chance to set status correctly by calcstrand)
		$(document).oneTime(2000, 'handle_machine_progress_one_timer', function () {	// damit auch die 100% noch dargestellt werden
			handle_machine_progress();	// show actual progress
		});
	});
// TODO: start_time in jobs-table aktualisieren
}

/**
* stop/abort job ... send command to machine (via jobs.php)
*
* @param JID
* @param MID
*/
function stopjob(JID, MID) {
	if (true)	// sollte nun von machine/jobs uebernommen werden!
// bzw. sollte das innerhalb von jobs.php abgewickelt werden !? (ok ist jetzt)
// ebenso im startjob() oben ?! (fehler: job automatisch auf Fehler setzen)
	{
		set_job_status(JID, MID, JOBSTATUS_CANCELED);
	}
//	var action = {action: "stop"};
//	URL = prindot_machines[MID]['URL'];
//	stopjob_r = $.ajax({
//		type: "POST",
//		dataType: 'jsonp',
//		url: URL,
//		data: $.extend({}, action, {JID: JID})
//	});
	stopjob_r = $.ajax({
		type: "POST",
		dataType: 'json',
		url: phpconfig['urls']['jobs'],
		data: {action: "stop", JID: JID, MID: MID}
	});
	stopjob_r.done(function (result) {
		if (result['result'] === 'success') {
			writelog(JID, MID, 0, LOG_TYPE_STATUS, LOG_SUBTYPE_JOB, "succesfully send abort job (" + JID + ") to machine '" + prindot_machines[MID]['name'] + "' [" + MID + "]");
			alertify.success("succesfully send abort job (" + JID + ") to machine '" + prindot_machines[MID]['name'] + "' [" + MID + "] : " + print_r(result));
		}
		else
		{
			writelog(JID, MID, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_JOB, "Error sending abort job (" + JID + ") to machine '" + prindot_machines[MID]['name'] + "' [" + MID + "]");
		}
	});
	stopjob_r.fail(function (jqXHR, textStatus) {
		writelog(JID, MID, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_JOB, "Error sending abort job (" + JID + ") to machine '" + prindot_machines[MID]['name'] + "' [" + MID + "]");
//		alertify.error("Error sending abort job (" + JID + ") to machine '" + prindot_machines[MID]['name'] + "' [" + MID + "] : " + textStatus);
		set_job_status(JID, MID, JOBSTATUS_ERROR);
	});
	stopjob_r.always(function () {
//		alertify.log("calcjob() stopping refresh timer");
// TEST, damit job ende!:
//		$(document).stopTime('jobtable_timer');	// deaktiviere timer refresh
	});
}

/**
 * calculate job ... calc strands and XML (not used yet ... but may be to calc strands before starting the job - possibly directly after creating a job)
 * @param {type} JID
 * @param {type} MID
 * @returns {undefined}
 */
function calcjob(JID, MID) {
	writelog(JID, MID, 0, LOG_TYPE_STATUS, LOG_SUBTYPE_JOB, "calculating job  (" + JID + ") ...");
//	var action = {action: "calcstrand"};
	calcjob_r = $.ajax({
		type: "POST",
		dataType: 'json',
//		url: phpconfig['urls']['calcstrand'],
		url: phpconfig['urls']['jobs'],
		data: {action: 'calcstrand', JID: JID}
	});
	calcjob_r.done(function (result) {
		if (result['result'] === 'success') {
			writelog(JID, MID, 0, LOG_TYPE_STATUS, LOG_SUBTYPE_JOB, "successfully calculated job (" + JID + ")");
			alertify.success("calcjob(" + JID + ", " + MID + ") : " + print_r(result));
//				// start job if success
//			startjob(JID, MID);
		}
		else
		{
			writelog(JID, MID, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_JOB, "Error calculating job (" + JID + ") : " + result['reason']);
//			alertify.error("ERROR: calcjob(" + JID + ", " + MID + ") : " + print_r(result), 0);
		}
	});
	calcjob_r.fail(function (jqXHR, textStatus) {
		writelog(JID, MID, 0, LOG_TYPE_ERROR, LOG_SUBTYPE_JOB, "Error calculating job (" + JID + ") : " + result['reason']);
//		alertify.error("ERROR: calcjob(" + JID + ", " + MID + ") : " + print_r(textStatus));
		set_job_status(JID, MID, JOBSTATUS_ERROR);
	});
//				calcjob_r.always(function() {
//				});
	calcjob_r.then(function () {
console.log("calcjob() ... then ...");
// TODO: and start after 2 sec or so (to give chance to set status correctly by calcstrand)
		$(document).oneTime(2000, 'handle_machine_progress_one_timer', function () {	// damit auch die 100% noch dargestellt werden
			handle_machine_progress();	// show actual progress
		});
//		alertify.log("calcjob() stopping refresh timer");
// TODO: TEST, damit job ende!: sollte aber von ausserhalb kommen!
//		var action = {action: "status"};
//		logprogress_r = $.ajax({
//			type: "POST",
//			dataType: 'json',
//			url: phpconfig['urls']['logs'],
//			data: $.extend({}, action, {JID: JID, MID: MID, status: 2})
//		});
//		$(document).stopTime('jobtable_timer');	// deaktiviere timer refresh
//		$(document).oneTime(5000, 'jobtable_timer', function () {	// damit auch die 100% noch dargestellt werden
//			if (!jobtable_rowselected)
//			{
//				JobTable.fnDraw(false);
//			}
//		});
//
// TODO: eigentlich abhaengig von status:
//		writelog(JID, MID, 0, LOG_TYPE_STATUS, LOG_SUBTYPE_JOB, "finished job (" + JID + ")");
	});
}

$(function () {
	init_jobtable();

		// default with "+"
	$("#jobs_showall").button({icons: {primary: 'ui-icon-plus'}});
		// default to less
	toggle_jobtable_columns(false);

	$("#jobs_showall").click(function () {
//							if (this.checked)
//							{
//								alertify.log("show all");
//							}
//							else
//							{
//								alertify.log("show NOT all");
//							}
		toggle_jobtable_columns(this.checked);
	});
});

//////////////////////////////////////////////////
// ADMIN USER HANDLING
// switch elements and functions depending on admin/user status
function switch_theme(tname) {
	var theme = $('#theme');
	theme.prop(
		'href',
		theme.prop('href').replace(
			/[\w\-]+\/jquery-ui.css/,
			tname + '/jquery-ui.css'
		)
	);
	//alertify.log("theme switched", "success", 2000);
}
function adminuserhandling() {
	$("#prindot_machine_name").prop('disabled', !prindot_admin);
	$("#prindot_machine_comment").prop('disabled', !prindot_admin);
	$("#prindot_machine_actheadcount").prop('disabled', !prindot_admin);
	$("#prindot_machine_head1startmm").prop('disabled', !prindot_admin);
	$("#prindot_machine_head2startmm").prop('disabled', !prindot_admin);
	$("#prindot_machine_actcylinderwidth").prop('disabled', !prindot_admin);
	$("#prindot_machine_actcylinderperimeter").prop('disabled', !prindot_admin);
	$("#prindot_machine_actenginerpm").prop('disabled', !prindot_admin);
	$("#prindot_machine_actgougefreq").prop('disabled', !prindot_admin);
	$("#prindot_save_machine").prop('disabled', !prindot_admin);
	if (prindot_admin) {
		$("#prindot_save_machine").show();
		$("#prindot_machine_name_label").show();
//		$("#logview").show();
		switch_theme('black-tie');
	}
	else {
		$("#prindot_save_machine").hide();
		$("#prindot_machine_name_label").hide();
//		$("#logview").hide();
		switch_theme('base');
	}
}
$(document).ready(function () {
	adminuserhandling();
});

//////////////////////////////////////////////////
// MACHINE FORMULAR VALIDATION TIMER PREVIEW (tab-machine)
//							$("#prindot_machine_name").val("hallo");
//							$("#prindot_machine_comment").val("oha soso");
//							$("#prindot_machine_maxheadcount").val("2");
//							$("#prindot_machine_actheadcount").val("1");
//							$("#prindot_machine_mincylinderwidth").val("300");
//							$("#prindot_machine_actcylinderwidth").val("1600");
//							$("#prindot_machine_maxcylinderwidth").val("2500");
//							$("#prindot_machine_mincylinderperimeter").val("100");
//							$("#prindot_machine_actcylinderperimeter").val("880");
//							$("#prindot_machine_maxcylinderperimeter").val("1400");
//							$("#prindot_machine_minenginerpm").val("3");
//							$("#prindot_machine_actenginerpm").val("6");
//							$("#prindot_machine_maxenginerpm").val("12");
//							$("#prindot_machine_mingougefreq").val("50");
//							$("#prindot_machine_actgougefreq").val("2000");
//							$("#prindot_machine_maxgougefreq").val("5000");
//							$("#machineselector option:selected").text("hallo");

/**
 * validation engine : check cylinder width
 * @param field
 * @param rules
 * @param i
 * @param options
 * @returns {string}
 */
function check_act_cylinderwidth(field, rules, i, options) {
	val = parseInt(field.val(), 10);
	min = parseInt($("#prindot_machine_mincylinderwidth").val(), 10);
	max = parseInt($("#prindot_machine_maxcylinderwidth").val(), 10);
	if (val < min) {
		return "Wert nicht groesser Minimum(" + min + ")";
	}
	else if (val > max) {
		return "Wert nicht kleiner Maximum (" + max + ")";
	}
	else {
		//return "";
	}
}

var prindot_machine = "unselected";
// Initialize the machine selector:
$(function () {
	'use strict';
	$('#machineselector').change(function () {
// TODO: alten werte zurueckspeichern (ggf. vorher fragen, ob ueberschreiben ... ) wenn nicht init
		//					prindot_machine = $('#machineselector').val();
//									prindot_machine = $('#machineselector').val() + " : " + $('#machineselector option:selected').text();
		prindot_machine = $('#machineselector').val();
//									alertify.log("selected machine: '" + prindot_machine + "'", "success", 2000);
//		writelog(0, prindot_machine, 0, LOG_TYPE_STATUS, LOG_SUBTYPE_MACHINE, 'selected machine "' + prindot_machines[prindot_machine]['name'] + '" [' + prindot_machine + ']');
		writelog(0, prindot_machine, 0, LOG_TYPE_STATUS, LOG_SUBTYPE_MACHINE, "selected machine '" + prindot_machines[prindot_machine]['name'] + "' [" + prindot_machine + "]");
		//					alert($('#machineselector').val());
		//alert(prindot_machine);
		// set machine name in form
//									$("#prindot_machine_name").val($('#machineselector option:selected').text());
		//TODO: hier dann noch die weiteren Werte setzen ...
		$("#prindot_machine_name").val(prindot_machines[prindot_machine]['name']);
		$("#prindot_machine_comment").val(prindot_machines[prindot_machine]['comment']);
		$("#prindot_machine_maxheadcount").val(prindot_machines[prindot_machine]['max_head_count']);
		$("#prindot_machine_actheadcount").val(prindot_machines[prindot_machine]['act_head_count']);
// TODO: hier jetzt doch wieder die Kopfpositionen definieren lassen !
		$("#prindot_machine_head1startmm").val(prindot_machines[prindot_machine]['headinitstartmm_1']);
		$("#prindot_machine_head2startmm").val(prindot_machines[prindot_machine]['headinitstartmm_2']);
		$("#prindot_machine_mincylinderwidth").val(prindot_machines[prindot_machine]['min_width_mm']);
		$("#prindot_machine_actcylinderwidth").val(prindot_machines[prindot_machine]['act_width_mm']);
		$("#prindot_machine_maxcylinderwidth").val(prindot_machines[prindot_machine]['max_width_mm']);
		$("#prindot_machine_mincylinderperimeter").val(prindot_machines[prindot_machine]['min_perimeter_mm']);
		$("#prindot_machine_actcylinderperimeter").val(prindot_machines[prindot_machine]['act_perimeter_mm']);
		$("#prindot_machine_maxcylinderperimeter").val(prindot_machines[prindot_machine]['max_perimeter_mm']);
		$("#prindot_machine_minenginerpm").val(prindot_machines[prindot_machine]['min_rpm']);
		$("#prindot_machine_actenginerpm").val(prindot_machines[prindot_machine]['act_rpm']);
		$("#prindot_machine_maxenginerpm").val(prindot_machines[prindot_machine]['max_rpm']);
		$("#prindot_machine_mingougefreq").val(prindot_machines[prindot_machine]['min_gouge_hz']);
		$("#prindot_machine_actgougefreq").val(prindot_machines[prindot_machine]['act_gouge_hz']);
		$("#prindot_machine_maxgougefreq").val(prindot_machines[prindot_machine]['max_gouge_hz']);
		//headinitstartmm_1 ... 8 fehlen noch als CSV array in text-box

			// set job tab values
		$("#job_act_machine").text(prindot_machines[prindot_machine]['name'] + ' [' + prindot_machine + ']');

// TODO: hier auch global den aktuell verlinkten job setzen ?!

		// cookie setzen
		$.cookie('selected_machine', prindot_machine);

		handle_machine_progress();	// show actual progress

	});
});

$(function () {
	// binds form submission and fields to the validation engine
	$("#machine_form").validationEngine();
//	$("#machine_form").bind("jqv.form.validating", function (event) {
//		//$("#hookError").html("")
//	});
	$("#machine_form").bind("jqv.form.result", function (event, errorFound) {
		if (!errorFound) {
			alertify.set({labels: {ok: "Ja", cancel: "Nein"}});
			alertify.set({buttonFocus: "cancel"}); // "none", "ok", "cancel"
			alertify.confirm('<h3>Maschinendaten aktualisieren ?</h3><br/>', function (e) {
				if (e) {
			// DONE: hier daten in db auf server zuruecksichern
			prindot_machines[prindot_machine]['name'] = $("#prindot_machine_name").val();
			prindot_machines[prindot_machine]['comment'] = $("#prindot_machine_comment").val();
			prindot_machines[prindot_machine]['max_head_count'] = $("#prindot_machine_maxheadcount").val();
			prindot_machines[prindot_machine]['act_head_count'] = $("#prindot_machine_actheadcount").val();
			prindot_machines[prindot_machine]['headinitstartmm_1'] = $("#prindot_machine_head1startmm").val();
			prindot_machines[prindot_machine]['headinitstartmm_2'] = $("#prindot_machine_head2startmm").val();
			prindot_machines[prindot_machine]['min_width_mm'] = $("#prindot_machine_mincylinderwidth").val();
			prindot_machines[prindot_machine]['act_width_mm'] = $("#prindot_machine_actcylinderwidth").val();
			prindot_machines[prindot_machine]['max_width_mm'] = $("#prindot_machine_maxcylinderwidth").val();
			prindot_machines[prindot_machine]['min_perimeter_mm'] = $("#prindot_machine_mincylinderperimeter").val();
			prindot_machines[prindot_machine]['act_perimeter_mm'] = $("#prindot_machine_actcylinderperimeter").val();
			prindot_machines[prindot_machine]['max_perimeter_mm'] = $("#prindot_machine_maxcylinderperimeter").val();
			prindot_machines[prindot_machine]['min_rpm'] = $("#prindot_machine_minenginerpm").val();
			prindot_machines[prindot_machine]['act_rpm'] = $("#prindot_machine_actenginerpm").val();
			prindot_machines[prindot_machine]['max_rpm'] = $("#prindot_machine_maxenginerpm").val();
			prindot_machines[prindot_machine]['min_gouge_hz'] = $("#prindot_machine_mingougefreq").val();
			prindot_machines[prindot_machine]['act_gouge_hz'] = $("#prindot_machine_actgougefreq").val();
			prindot_machines[prindot_machine]['max_gouge_hz'] = $("#prindot_machine_maxgougefreq").val();
			writemachine(prindot_machine);
//			alertify.log("write back settings for machine: '" + prindot_machines[prindot_machine]['name'] + "' [" + prindot_machine + "]", "success", 2000);
			// name in select-box zuruecksichern
			$("#machineselector option:selected").text($("#prindot_machine_name").val());

				// set job tab values
			$("#job_act_machine").text(prindot_machines[prindot_machine]['name'] + ' [' + prindot_machine + ']');
				} else {
					//alertify.log("speichern abgebrochen");
				}
			});
		}
		else {
//			alertify.log("Missing/Wrong values ... NOT write back settings for machine: '" + prindot_machine + "'", "error", 0);
			alertify.log("Falsche/fehlende Werte ... Daten für Maschine '" + prindot_machine + "' nicht gesichert!", "error", 3000);
		}
	});
});

// set up timer to display progress of actual selected machine : ONLY WHEN machine changed
var prindot_machine_old = "unset";
var previewimgurl = '';
var previewimgurl_old = noimage;
var prindot_machine_job_progress = 0;
var imageprogressstring = '';
var prindot_machine_job_start_time = 0;
var prindot_machine_job_status = 0;
var previewimagesize_x = -1;
var previewimagesize_y = -1;

var prindot_machine_job_head_count = 0;
var prindot_machine_job_width_mm = 0.0;
var prindot_machine_job_headstart_mm = new Array();
var prindot_machine_job_headend_mm = new Array();

function machine_show_preview()
{
	var mycanvas = $("#myc");
	var ctx = mycanvas[0].getContext("2d");

	$("#myc").clearCanvas();
	$("#myc").drawImage({
		source: previewimgurl,
		x: 1, y: 1,
		fromCenter: false,
		load: mydrawings
	});

	function mydrawings() {
	//							myarc();
	//							mygitter(0.5, 500, 375, 10, 300, 300);
		myobox(1, 1, previewimagesize_x, previewimagesize_y);
	// TODO: hier fuer 2koepfe auch 2 boxen zeichnen (moeglichst auf richtige positionen)

		var i = 0;
		for (i=1; i<=prindot_machine_job_head_count; i++)
		{
			var start;
			var end;
			var width;
			start = previewimagesize_x / prindot_machine_job_width_mm * prindot_machine_job_headstart_mm[i];
			end = previewimagesize_x / prindot_machine_job_width_mm * prindot_machine_job_headend_mm[i];
			width = end - start;
			mybox(start+1, 1, prindot_machine_job_progress / 100 * width, previewimagesize_y);
		}

	//			mybox(0, 0, prindot_machine_job_progress / 100 * previewimagesize_x, previewimagesize_y);
		$("#imageprogressstring").html(imageprogressstring);
	//			$("#myc").attr('title', imageprogressstring);
	}

	// Draw a circle on the canvas
	function myarc() {
		mycanvas.drawArc({// auch $(my_canvas).drawArc ...
			fillStyle: "red",
			x: 750, y: 450,
			radius: 50
		});
	}

	//						$(document).oneTime(5000, 'machineprogresstimer1', function() {
	//							mybox(40,0,20,500);
	//						});

	function mybox(x, y, w, h) {
		$("#myc").drawRect({
	//								fillStyle: "#ff0000",
			fillStyle: "rgba(128,255,128,0.2)",
			strokeWidth: 1,
			fromCenter: false,
	//								compositing: 'lighter',
			x: x, y: y, width: w, height: h
		});
	}

	function myobox(x, y, w, h) {
		$("#myc").drawRect({
	//								fillStyle: "#ff0000",
			strokeStyle: "rgba(255,0,0,1)",
			strokeWidth: 1,
			fromCenter: false,
	//								compositing: 'lighter',
			x: x-1, y: y-1, width: w+2, height: h+2
		});
	}

	function mygitter(from, tox, toy, step, ox, oy) {
		for (x = from; x < tox; x += step) {
			$("#myc").drawLine({
				strokeStyle: "#dddddd",
				strokeWidth: 1,
				x1: x + ox, y1: 0 + oy,
				x2: x + ox, y2: toy + oy
			});
		}
		for (y = from; y < toy; y += step) {
			$("#myc").drawLine({
				strokeStyle: "#888888",
				strokeWidth: 1,
				x1: 0 + ox, y1: y + oy,
				x2: tox + ox, y2: y + oy
			});
		}
	}
}

/* wird nicht mehr gebraucht! */
function machine_show_preview_old()
{
	machinegetpreview_r = $.ajax({
		type: "POST",
		dataType: 'json',
		url: phpconfig['urls']['machines'],
		data: ({
			MID: prindot_machine,
			action: 'getpreview',
			relaxed: true
		})
	});
	machinegetpreview_r.done(function (result) {
		if (result['result'] !== 'success') {
			// TODO: error
//												alertify.error(print_r(result));
			prindot_machine_job_progress = 0;
			imageprogressstring = '';
		}
		else {
			// TODO: load image and display
//												alertify.success(print_r(result));
		}
	});
	machinegetpreview_r.fail(function (result, text) {
		// TODO: error
//											alertify.error("Request failed: " + result);
		// TODO: meldung in db-logs schreiben
		prindot_machine_job_progress = 0;
		imageprogressstring = '';
	});
	machinegetpreview_r.always(function (result, text) {

//alertify.error(print_r(result));

		var mycanvas = $("#myc");
		var ctx = mycanvas[0].getContext("2d");
		//							var loadImg = function(imgurl) {
		//								$.getImageData({
		//									url: imgurl,
		//									success: function(img) {
		//										mycanvas.attr("width", img.width).attr("height", img.height);
		//										ctx.drawImage(img, 0, 0, img.width, img.height);
		//									},
		//									error: function(xhr, text_status) {
		//									}
		//								});
		//							};


		if (result['result'] === 'success') {
			if (result['value'] !== '') {
				previewimgurl = phpconfig['paths']['storage_root_js'] + phpconfig['paths']['strands'] + '/' + result['value']['preview'];
				// TODO: testen, ob datei auch da ist ...
				previewimagesize_x = result['value']['size']['width'];
				previewimagesize_y = result['value']['size']['height'];
			}
			else {
				previewimgurl = noimage;
				prindot_machine_job_progress = 0;
				imageprogressstring = '';
//				alertify.error("result[value] is empty :" + result);
			}
		}
		else {
			previewimgurl = noimage;
			prindot_machine_job_progress = 0;
			imageprogressstring = '';
//alertify.error("result[result] not success :" + text);
		}


		$("#myc").clearCanvas();
		$("#myc").drawImage({
			source: previewimgurl,
			x: 1, y: 1,
			fromCenter: false,
			load: mydrawings
		});

		function mydrawings() {
//							myarc();
//							mygitter(0.5, 500, 375, 10, 300, 300);
			myobox(1, 1, previewimagesize_x, previewimagesize_y);
// TODO: hier fuer 2koepfe auch 2 boxen zeichnen (moeglichst auf richtige positionen)

			var i = 0;
			for (i=1; i<=prindot_machine_job_head_count; i++)
			{
				var start;
				var end;
				var width;
				start = previewimagesize_x / prindot_machine_job_width_mm * prindot_machine_job_headstart_mm[i];
				end = previewimagesize_x / prindot_machine_job_width_mm * prindot_machine_job_headend_mm[i];
				width = end - start;
				mybox(start+1, 1, prindot_machine_job_progress / 100 * width, previewimagesize_y);
			}

//			mybox(0, 0, prindot_machine_job_progress / 100 * previewimagesize_x, previewimagesize_y);
			$("#imageprogressstring").html(imageprogressstring);
//			$("#myc").attr('title', imageprogressstring);
		}

		// Draw a circle on the canvas
		function myarc() {
			mycanvas.drawArc({// auch $(my_canvas).drawArc ...
				fillStyle: "red",
				x: 750, y: 450,
				radius: 50
			});
		}

//						$(document).oneTime(5000, 'machineprogresstimer1', function() {
//							mybox(40,0,20,500);
//						});

		function mybox(x, y, w, h) {
			$("#myc").drawRect({
//								fillStyle: "#ff0000",
				fillStyle: "rgba(128,255,128,0.2)",
				strokeWidth: 1,
				fromCenter: false,
//								compositing: 'lighter',
				x: x, y: y, width: w, height: h
			});
		}

		function myobox(x, y, w, h) {
			$("#myc").drawRect({
//								fillStyle: "#ff0000",
				strokeStyle: "rgba(255,0,0,1)",
				strokeWidth: 1,
				fromCenter: false,
//								compositing: 'lighter',
				x: x-1, y: y-1, width: w+2, height: h+2
			});
		}

		function mygitter(from, tox, toy, step, ox, oy) {
			for (x = from; x < tox; x += step) {
				$("#myc").drawLine({
					strokeStyle: "#dddddd",
					strokeWidth: 1,
					x1: x + ox, y1: 0 + oy,
					x2: x + ox, y2: toy + oy
				});
			}
			for (y = from; y < toy; y += step) {
				$("#myc").drawLine({
					strokeStyle: "#888888",
					strokeWidth: 1,
					x1: 0 + ox, y1: y + oy,
					x2: tox + ox, y2: y + oy
				});
			}
		}
	});
}

var showmachinejobprogress_timer = false;
/**
* not used anymore!
*
*/
function showmachinejobprogress()
{
console.log("showmachinejobprogress() ...");
//progress(false, 'waiting ...');
	getjobinfos_r = $.ajax({
		type: "POST",
		dataType: 'json',
		url: phpconfig['urls']['machines'],
		data: ({
			MID: prindot_machine,
			action: 'getjobinfos',
			relaxed: true
		})
	});
	getjobinfos_r.done(function (result) {
		if (result['result'] !== 'success') {
			// TODO: error
//alertify.error(print_r(result));
			prindot_machine_job_progress = -1;
			prindot_machine_job_status = JOBSTATUS_NOJOB;	// NOJOB
console.log("showmachinejobprogress() found no job status=" + prindot_machine_job_status + "");
			progress(0, 'no job assigned to machine');
		}
		else {
			prindot_machine_job_start_time = parseInt(result['value']['start_time_s']);
			prindot_machine_job_progress = parseInt(result['value']['progress']);	// 0...100
			prindot_machine_job_status = parseInt(result['value']['status']);	// TODO

			prindot_machine_job_head_count = parseInt(result['value']['head_count']);	// TODO
			prindot_machine_job_width_mm = parseFloat(result['value']['width_mm']);	// TODO
//			prindot_machine_job_headstart_1_mm = parseFloat(result['value']['headstart_1_mm']);	// TODO
//			prindot_machine_job_headend_1_mm = parseFloat(result['value']['headend_1__mm']);	// TODO
//			prindot_machine_job_headstart_2_mm = parseFloat(result['value']['headstart_2_mm']);	// TODO
//			prindot_machine_job_headend_2__mm = parseFloat(result['value']['headend_2__mm']);	// TODO
			prindot_machine_job_headstart_mm[1] = parseFloat(result['value']['headstart_1_mm']);	// TODO
			prindot_machine_job_headend_mm[1] = parseFloat(result['value']['headend_1_mm']);	// TODO
			prindot_machine_job_headstart_mm[2] = parseFloat(result['value']['headstart_2_mm']);	// TODO
			prindot_machine_job_headend_mm[2] = parseFloat(result['value']['headend_2_mm']);	// TODO


//			console.log(print_r(result));
			if ((prindot_machine_job_status == JOBSTATUS_CALCSTRAND) || (prindot_machine_job_status == JOBSTATUS_RUNNING) || (prindot_machine_job_status == JOBSTATUS_MACHINE_FILETRANSFER) || (prindot_machine_job_status == JOBSTATUS_MACHINE_WAITFORMANUALACTION))	// calcstrand or running (but terminate when run, finish, cancel, error, nojob
			{
console.log("showmachinejobprogress() found running job (JID=" + result['value']['JID'] + ") status=" + prindot_machine_job_status + " ... set showmachinejobprogress_timer=true");
				status_string = '';
				switch (prindot_machine_job_status)
				{
				case JOBSTATUS_CALCSTRAND:
					status_string = 'Strangdaten berechnen ';
					break;
				case JOBSTATUS_RUNNING:
					status_string = 'Maschine ';
					break;
				case JOBSTATUS_MACHINE_FILETRANSFER:
					status_string = 'Maschine lädt Daten ';
					break;
				case JOBSTATUS_MACHINE_WAITFORMANUALACTION:
					status_string = 'Maschine wartet auf manuellen Eingriff ';
					break;
				}
				progress(prindot_machine_job_progress, status_string + 'running ');
				showmachinejobprogress_timer = true;
				$(document).oneTime(phpconfig['settings']['showmachinejobprogress_timer_sec'] * 1000, 'showmachinejobprogress_timer', function () {	// TODO: config
					showmachinejobprogress();
					showmachinejobprogress_timer = false;
				});
			}
			else
			{
				// TODO: switch message on status
				var status = '';
				switch (prindot_machine_job_status)
				{
				case JOBSTATUS_NEW:
					status = 'new';
				break;
				case JOBSTATUS_FINISHED:
					status = 'finished OK';
				break;
				case JOBSTATUS_CANCELED:
					status = 'canceled';
				break;
				case JOBSTATUS_ERROR:
					status = 'error';
				break;
				case JOBSTATUS_NOJOB:
					status = 'no job';
				break;
				default:
					status = 'undefined';
				break;
				}
				console.log("showmachinejobprogress() found non-running job (JID=" + result['value']['JID'] + ") status=" + prindot_machine_job_status + " (" + status + ")");
				progress(prindot_machine_job_progress, "machine='" + prindot_machines[prindot_machine]['name'] + "' / job='" + result['value']['JID'] + "' / status='" + status + "'");
			}
		}
	});
	getjobinfos_r.fail(function (result, text) {
//		alertify.error("Request failed: " + result);
		prindot_machine_job_progress = -1;
		prindot_machine_job_status = JOBSTATUS_NOJOB;	// NOJOB
		progress(0, 'failed receiving assigned job to for selected machine');
	});
}

function handle_machine_progress_show(result)
{
	//	console.log("handle_machine_progress_show(" + print_r(result) + ")");

	previewimgurl = noimage;
	previewimagesize_x = -1;
	previewimagesize_y = -1;
	prindot_machine_job_progress = 0;
	imageprogressstring = '';
	if (result['result'] !== "success")
	{
		JID = "-";
		status = "-";
		progress(prindot_machine_job_progress, "machine='" + prindot_machines[prindot_machine]['name'] + "' / job='" + JID + "' / status='" + status + "'");
		return;
	}

	if (result['value'] !== '' && result['value']['size'] !== '' && result['value']['size']['width'] !== '' && result['value']['size']['height'] !== '') {
		previewimagesize_x = parseInt(result['value']['size']['width']);
		previewimagesize_y = parseInt(result['value']['size']['height']);
		if (previewimagesize_x > 0 && previewimagesize_y > 0 && result['value']['preview'] !== '') {
			previewimgurl = phpconfig['paths']['storage_root_js'] + phpconfig['paths']['strands'] + '/' + result['value']['preview'];
		}
	}

	// load image and display
	//												alertify.success(print_r(result));
	prindot_machine_job_start_time = parseInt(result['value']['start_time_s']);
	prindot_machine_job_progress = parseInt(result['value']['progress']);	// 0...100
	prindot_machine_job_status = parseInt(result['value']['status']);	// TODO

	prindot_machine_job_head_count = parseInt(result['value']['head_count']);	// TODO
	prindot_machine_job_width_mm = parseFloat(result['value']['width_mm']);	// TODO
	prindot_machine_job_headstart_mm[1] = parseFloat(result['value']['headstart_1_mm']);	// TODO
	prindot_machine_job_headend_mm[1] = parseFloat(result['value']['headend_1_mm']);	// TODO
	prindot_machine_job_headstart_mm[2] = parseFloat(result['value']['headstart_2_mm']);	// TODO
	prindot_machine_job_headend_mm[2] = parseFloat(result['value']['headend_2_mm']);	// TODO

	prindot_machine_job_past_time = parseInt(result['value']['pasttime_s']);
	prindot_machine_job_calc_time = parseInt(result['value']['calctime_s']);
	prindot_machine_job_left_time = parseInt(result['value']['lefttime_s']);
	if (prindot_machine_job_progress > 0 && prindot_machine_job_past_time > 0) {
		if (prindot_machine_job_progress == 100) {
			imageprogressstring = "Fortschritt: " + prindot_machine_job_progress + "% - fertig!";
		}
		else
		{
			var diff = Math.floor(prindot_machine_job_left_time);
			var diffh = Math.floor(diff / 3600);
			var diffm = Math.floor((diff % 3600) / 60);
			var diffs = (diff % 60);
			imageprogressstring = "Fortschritt: " + prindot_machine_job_progress + "% - Restzeit : " + diffh + "h " + diffm + "m " + diffs + "s";
		}
	}
	else {
		if (prindot_machine_job_progress == 100) {
			imageprogressstring = "Fortschritt: " + prindot_machine_job_progress + "% - ... ";
		}
		else
		{
			imageprogressstring = "Fortschritt: " + prindot_machine_job_progress + "% - Restzeit : unbekannt";
		}
	}

	//			console.log(print_r(result));
	if ((prindot_machine_job_status == JOBSTATUS_CALCSTRAND) || (prindot_machine_job_status == JOBSTATUS_RUNNING) || (prindot_machine_job_status == JOBSTATUS_MACHINE_FILETRANSFER) || (prindot_machine_job_status == JOBSTATUS_MACHINE_WAITFORMANUALACTION))	// calcstrand or running (but terminate when run, finish, cancel, error, nojob
	{
	console.log("handle_machine_progress() found running job (JID=" + result['value']['JID'] + ") status=" + prindot_machine_job_status + "");
		status_string = '';
		switch (prindot_machine_job_status)
		{
		case JOBSTATUS_CALCSTRAND:
			status_string = 'Strangdaten berechnen ';
			break;
		case JOBSTATUS_RUNNING:
			status_string = 'Maschine ';
			break;
		case JOBSTATUS_MACHINE_FILETRANSFER:
			status_string = 'Maschine lädt Daten ';
			break;
		case JOBSTATUS_MACHINE_WAITFORMANUALACTION:
			status_string = 'Maschine wartet auf manuellen Eingriff ';
			break;
		}
		switch (prindot_machine_job_progress)
		{
		case 0:
			progress(false, status_string + 'running ... starting process ... please wait');
			break;
		case 100:
			progress(100, status_string + 'done ... wait for status change ');
			break;
		default:
			progress((prindot_machine_job_progress <= 0 ? false : prindot_machine_job_progress), status_string + 'running ' +  " - Restzeit : " + diffh + "h " + diffm + "m " + diffs + "s");
			break;
		}
	}
	else
	{
		// TODO: switch message on status
		var status = '';
		switch (prindot_machine_job_status)
		{
		case JOBSTATUS_NEW:
			status = 'new';
		break;
		case JOBSTATUS_FINISHED:
			status = 'finished OK';
		break;
		case JOBSTATUS_CANCELED:
			status = 'canceled';
		break;
		case JOBSTATUS_ERROR:
			status = 'error';
		break;
		case JOBSTATUS_NOJOB:
			status = 'no job';
		break;
		default:
			status = 'undefined';
		break;
		}
		console.log("handle_machine_progress() found non-running job (JID=" + result['value']['JID'] + ") status=" + prindot_machine_job_status + " (" + status + ")");
		progress(prindot_machine_job_progress, "machine='" + prindot_machines[prindot_machine]['name'] + "' / job='" + result['value']['JID'] + "' / status='" + status + "'");
	}
}

var jobtable_autoupdate = false;
function handle_machine_progress()
{
console.log("handle_machine_progress() ...");
//		showmachinejobprogress();
	//writelog(0, prindot_machine, 0, 0, 1, 'machineprogress_timer switched from "' + prindot_machine_old + '" to "' + prindot_machine + '"');
	prindot_machine_old = prindot_machine;
	jobtable_autoupdate_change = jobtable_autoupdate;

// TODO: auch nur 1x lesen, bis valid (und wenn job fertig, dann wieder zuruecksetzn ?!
	machinegetjobinfos_r = $.ajax({
		type: "POST",
		dataType: 'json',
		url: phpconfig['urls']['machines'],
		data: ({
			MID: prindot_machine,
			action: 'getjobinfos',
			relaxed: true
		})
	});
	machinegetjobinfos_r.done(function (result) {
		if (result['result'] !== 'success') {
			// TODO: error
//alertify.error(print_r(result));
handle_machine_progress_show(result);
		}
		else {
handle_machine_progress_show(result);

			// hier die steuerung des jobtables auto update und der progress anzeige (progress())
			// oben (!=success) und unten (fail) den timer auch zuruecksetzen/deaktivieren
//			console.log(print_r(result));
			if (prindot_machine_job_status == JOBSTATUS_CALCSTRAND || prindot_machine_job_status == JOBSTATUS_RUNNING || prindot_machine_job_status == JOBSTATUS_MACHINE_FILETRANSFER || prindot_machine_job_status == JOBSTATUS_MACHINE_WAITFORMANUALACTION)	// calcstrand or running
			{
				jobtable_autoupdate_change = true;
			}
			else
			{
				jobtable_autoupdate_change = false;
			}
		}
	});
	machinegetjobinfos_r.fail(function (result, text) {
		// TODO: error
//											alertify.error("Request failed: " + result);
		previewimgurl = noimage;
		prindot_machine_job_progress = 0;
		imageprogressstring = '';
		jobtable_autoupdate_change = false;
		// TODO: meldung in db-logs schreiben
	});
	machinegetjobinfos_r.always(function (result, text) {
		if (jobtable_autoupdate_change != jobtable_autoupdate)
		{
			if (jobtable_autoupdate_change == false)	// was true .. so turn off
			{
				$(document).stopTime('jobtable_timer');	// deaktiviere timer refresh
			}
			else	// was off ... so turn on
			{

				$(document).stopTime('jobtable_timer');	// deaktiviere timer refresh
				$(document).everyTime(phpconfig['settings']['jobtable_timer_sec'] *1000, 'jobtable_timer', function () {
					if (!jobtable_rowselected)
					{
						var active = $( "#tabs" ).tabs( "option", "active" );
						if (active === 4)
						{
							JobTable.fnDraw(false);
						}
					}
				});
			}
			jobtable_autoupdate = jobtable_autoupdate_change;
		}
//		var active = $( "#tabs" ).tabs( "option", "active" );
//		if (active === 5)
		{
			machine_show_preview();	// TODO: auch nicht immer wieder preview bildinfos laden ... einmal laden fuer aktuellen job und gut ist ...!
		}
	});
}

var machineprogress_timer_sec = phpconfig['settings']['machineprogress_timer_sec'] * 1000;
$(function () {
	$(document).everyTime(machineprogress_timer_sec, 'machineprogress_timer', function () {
		handle_machine_progress();
		if (!jobtable_rowselected)
		{
			var active = $( "#tabs" ).tabs( "option", "active" );
			if (active === 4)
			{
				JobTable.fnDraw(false);
			}
		}
	});
});


//////////////////////////////////////////////////
// IMAGES UPLOADER
function plupload_log() {
	var str = "";
	plupload.each(arguments, function (arg) {
		var row = "";
		if (typeof(arg) !== "string") {
			plupload.each(arg, function (value, key) {
				// Convert items in File objects to human readable form
				if (arg instanceof plupload.File) {
					// Convert status to human readable
					switch (value) {
						case plupload.QUEUED:
							value = 'QUEUED';
							break;
						case plupload.UPLOADING:
							value = 'UPLOADING';
							break;
						case plupload.FAILED:
							value = 'FAILED';
							break;
						case plupload.DONE:
							value = 'DONE';
							break;
					}
				}

				if (typeof(value) !== "function") {
					row += (row ? ', ' : '') + key + '=' + value;
				}
			});
			str += row + " ";
		} else {
			str += arg + " ";
		}
	});
	//$('#log').append(str + "\n");
	return str;
}

// Convert divs to queue widgets when the DOM is ready
$(function () {
	$("#uploader").plupload({
		// General settings
		runtimes: 'html5',
		url: phpconfig['urls']['plupload_uploader'],
		max_file_size: '1000mb',
		max_file_count: 3, // user can add no more then 20 files at a time
		//chunk_size: '1mb',
		//		unique_names : true,
		unique_names: false,
		multiple_queues: true,
		// Resize images on clientside if we can
//										resize: {
//											width: 200,
//											height: 200,
//											quality: 90,
//											//			crop: true // crop to exact dimensions
//											crop: false // crop to exact dimensions
//										},
		// Specify what files to browse for
		filters: [
			{title: "TIFF Dateien", extensions: "tif,tiff"}//,
//			{title: "andere Bild-Dateien", extensions: "jpg,gif,png,jpeg,bmp"}
		],
		// Flash settings
		//flash_swf_url: '../../js/Moxie.swf',
		// Silverlight settings
		//silverlight_xap_url: '../../js/Moxie.xap',
		// Rename files by clicking on their titles
		rename: true,
		// Sort files
		sortable: false,
		// Enable ability to drop files onto the widget (currently only HTML5 supports that)
		dragdrop: true,
		// Views to activate
		views: {
			list: true,
			thumbs: false // Show thumbs
		},
		default_view: 'list',
		remember_view: false, // requires jquery cookie plugin
		// Post init events, bound after the internal events
		init: {
/*			Refresh: function (up) {
				// Called when upload shim is moved
				//alertify.log('[Refresh]');
			},
			StateChanged: function (up) {
				// Called when the state of the queue is changed
				//alertify.log('[StateChanged] ' + up.state == plupload.STARTED ? "STARTED" : "STOPPED");
			},
			QueueChanged: function (up) {
				// Called when the files in queue are changed by adding/removing files
				//alertify.log('[QueueChanged]');
			},
			UploadProgress: function (up, file) {
				// Called while a file is being uploaded
				//alertify.log('[UploadProgress] File:' + file + " Total: " + up.total);
			},
			FilesAdded: function (up, files) {
				// Callced when files are added to queue

				plupload.each(files, function (file) {
					//alertify.log('[FilesAdded]: ' + file);
				});
			},
			FilesRemoved: function (up, files) {
				// Called when files where removed from queue

				plupload.each(files, function (file) {
					//alertify.log('[FilesRemoved]: ' + file);
				});
			},
*/
			FileUploaded: function (up, file, info) {
				// Called when a file has finished uploading
				alertify.log(plupload_log('[FileUploaded] File:', file, "Info:", info), "success", 10000);
				//refreshing file trees !!
				fileTreeSelect();
			},
/*			ChunkUploaded: function (up, file, info) {
				// Called when a file chunk has finished uploading
				//alertify.log(plupoad_log('[ChunkUploaded] File:', file, "Info:", info));
			},
*/
			Error: function (up, args) {
				// Called when a error has occured
				alertify.log('[error] ' + args);
			}
		}

	});
	// Client side form validation
	$('form').submit(function (e) {
		var uploader = $('#uploader').plupload('getUploader');
		// Files in queue upload them first
		if (uploader.files.length > 0) {
			// When all files are uploaded submit form
			uploader.bind('StateChanged', function () {
				if (uploader.files.length === (uploader.total.uploaded + uploader.total.failed)) {
					$('form')[0].submit();
					// TODO: change when tree list changes !
					fileTreeSelect();
				}
			});
			uploader.start();
		} else {
			//alert('You must at least upload one file.');
		}
		return false;
	});
});


//////////////////////////////////////////////////
// IMAGE TRANSFORMATION
prindot_imagerotate = 0;
prindot_imagemirror = 0;
$(function () {
	$("#fileTreeSelectUpdateButton").button({icons: {primary: 'ui-icon-arrowrefresh-1-s'}});
	$("#buttondeleteimage").button({icons: {primary: 'ui-icon-trash'}});
	$("#rotate").buttonset();
	$("#rotateimage2").button("option", "icons", {primary: 'ui-icon-arrow-1-e'});
	$("#rotateimage3").button("option", "icons", {primary: 'ui-icon-arrow-1-w'});
	$("#rotateimage4").button("option", "icons", {primary: 'ui-icon-arrow-1-s'});
	$("#mirror").buttonset();
//    $("##1").button("option", "icons", {primary:'ui-icon-radio-on'});
	$("#mirrorimage2").button("option", "icons", {primary: 'ui-icon-arrow-2-e-w'});
	$("#mirrorimage3").button("option", "icons", {primary: 'ui-icon-arrow-2-n-s'});
	$("#mirrorclass").addClass('mirror0');
	$("#mirrorclassb").addClass('mirror0');
	$("#rotateclass").addClass('rotate0');
	$("#rotateclassb").addClass('rotate0');
	$("#rotate").click(function () {
		prindot_imagerotate = parseInt($('input[name=rotateimage]').filter(':checked').val());
		//alertify.log("Rotate: " + prindot_imagerotate);
		//$("#rotateclass").removeClass();
		$("#rotateclass").removeAttr('class');
		$("#rotateclassb").removeAttr('class');
		//$("#rotateclass").removeClass('rotate0 rotate90 rotate270 rotate180');
		$("#rotateclass").addClass('rotate' + prindot_imagerotate);
		$("#rotateclassb").addClass('rotate' + prindot_imagerotate);
		//return false;
show_selectedfile_infos();
	});
	$("#mirror").click(function () {
		prindot_imagemirror = parseInt($('input[name=mirrorimage]').filter(':checked').val());
		//alertify.log("Mirror " + prindot_imagemirror);
		$("#mirrorclass").removeAttr('class');
		$("#mirrorclassb").removeAttr('class');
		$("#mirrorclass").addClass('mirror' + prindot_imagemirror);
		$("#mirrorclassb").addClass('mirror' + prindot_imagemirror);
show_selectedfile_infos();
	});
});


//////////////////////////////////////////////////
// HANDLE MODE SELECT ON TAB DEFINEJOBS
function handle_mode_button(b)
{
	switch (b)
	{
	case 1:	// gravure
		prindot_mode = 1;
		prindot_mode_str = "gravure";
		//alertify.log("Mode: " + prindot_mode_str);
		$("#prindot_mode").text(prindot_mode_str);
// TODO: enable settings and one track distance
		$("#job_settings").children().prop('disabled', false);
		//$("#job_settings").show("slide", {}, 600);
		$("#job_settings").show();
		$("#button_gravure").attr("checked", "checked");
		$("#button_groove").prop("checked", false);
		$("#button_gravure").button("refresh");
		$("#button_groove").button("refresh");
		break;
	case 2:	// groove
		prindot_mode = 2;
		prindot_mode_str = "groove";
		//alertify.log("Mode: " + prindot_mode_str);
		$("#prindot_mode").text(prindot_mode_str);
// TODO: enable settings and track distance as text (CSV)
		$("#job_settings").children().prop('disabled', false);
		//$("#job_settings").hide("slide", {}, 600);
		$("#job_settings").show();
		$("#button_groove").attr("checked", "checked");
		$("#button_gravure").prop("checked", false);
		$("#button_gravure").button("refresh");
		$("#button_groove").button("refresh");
		break;
	}
}
$(function () {
	$("#prindot_mode_checkbox").buttonset();
	$("#button_gravure").click(function () {
		handle_mode_button(1);
	});
	$("#button_groove").click(function () {
		handle_mode_button(2);
	});

	$("#job_settings").children().prop('disabled', true);
	//$("#job_settings").hide("slide", {}, 600);
	$("#job_settings").hide();


	$("#settings_form").children().prop('disabled', true);
	//$("#job_settings").hide("slide", {}, 600);
	$("#settings_form").hide();
});


//////////////////////////////////////////////////
// HANDLE INPUT ON TAB DEFINEJOBS
// enable tabs here
var prindot_mode = "unset";
jQuery(document).ready(function () {
	$("#button_tab0_gravure").click(function () {
		$("#tabs").tabs("enable", 4);
		//									$("#tabs").tabs("option", {enable: 4});
		//$("#tabs").tabs("option", {active: 1});
		prindot_mode = "gravure";
		alertify.log("Mode: " + prindot_mode);
		$("#prindot_mode").text(prindot_mode);
		return false;
	});
	$("#button_tab0_groove").click(function () {
		$("#tabs").tabs("enable", 4);
		//									$("#tabs").tabs("option", {enable: 4});
		//$("#tabs").tabs("option", {active: 1});
		prindot_mode = "groove";
		alertify.log("Mode: " + prindot_mode);
		$("#prindot_mode").text(prindot_mode);
		return false;
	});

	$("#IbuttonGetActualCylinderParams").click(function () {
		$("#zylinderbreitemm").val($("#prindot_machine_actcylinderwidth").val());
		$("#zylinderbreitemm").validationEngine('validate');
		$("#zylinderumfangmm").val($("#prindot_machine_actcylinderperimeter").val());
		$("#zylinderumfangmm").validationEngine('validate');
		$("#prindot_naepfchenabstandmm").validationEngine('validate');
		$("#prindot_spurabstandmm").validationEngine('validate');
	});
	$("#IbuttonGetActualImageParams").click(function () {
		w = ((prindot_imagerotate === 90) || (prindot_imagerotate === 270)) ? prindot_selectedfile_infos['size']['height_mm'] : prindot_selectedfile_infos['size']['width_mm'];
		$("#zylinderbreitemm").val(w);
		$("#zylinderbreitemm").validationEngine('validate');
		$("#prindot_spurabstandmm").validationEngine('validate');
	});
	$("#IbuttonGetActualMachineHeadValues").click(function () {
		$("#prindot_job_actheadcount").val(0);
		$("#prindot_job_actheadcount").val($("#prindot_machine_actheadcount").val());
		$("#prindot_job_actheadcount").validationEngine('validate');
		$("#prindot_job_head1startmm").val($("#prindot_machine_head1startmm").val());
		$("#prindot_job_head1startmm").validationEngine('validate');
		$("#prindot_job_head2startmm").val($("#prindot_machine_head2startmm").val());
//		if (parseInt($("#prindot_job_head1startmm").val()) > 1) {
			$("#prindot_job_head2startmm").validationEngine('validate');
//		}
	});
});

$(function () {
	$("#prindot_machine_head2startmm").prop('disabled', ($("#prindot_machine_actheadcount").val()) <= 1);	// init headcount=1 ... disable start2
	$('#prindot_machine_actheadcount').change(function () {
//		console.log("prindot_job_actheadcount changed");
		$("#prindot_machine_head2startmm").prop('disabled', ($("#prindot_machine_actheadcount").val()) <= 1);
		$("#prindot_machine_head2startmm").validationEngine('validate');
	});

	$("#prindot_job_head2startmm").prop('disabled', ($("#prindot_job_actheadcount").val()) <= 1);	// init headcount=1 ... disable start2
	$('#prindot_job_actheadcount').change(function () {
//		console.log("prindot_job_actheadcount changed");
		$("#prindot_job_head2startmm").prop('disabled', ($("#prindot_job_actheadcount").val()) <= 1);
		$("#prindot_job_head2startmm").validationEngine('validate');
	});
});

function validate_mhead2start(field, rules, i, options) {
	c = parseInt($("#prindot_machine_actheadcount").val());
	if (c > 1) {
		v1 = parseFloat($("#prindot_machine_head1startmm").val());
		v2 = parseFloat($("#prindot_machine_head2startmm").val());
		if (v2 <= v1) {
			return "noch kein g&uuml;ltiger Wert angegeben";
		}
	}
}

function validate_head2start(field, rules, i, options) {
	c = parseInt($("#prindot_job_actheadcount").val());
	if (c > 1) {
		v1 = parseFloat($("#prindot_job_head1startmm").val());
		v2 = parseFloat($("#prindot_job_head2startmm").val());
		if (v2 <= v1) {
			return "noch kein g&uuml;ltiger Wert angegeben";
		}
	}
}

$(function () {
	// binds form submission and fields to the validation engine
	jQuery("#settings_form").validationEngine();
//	$("#settings_form").bind("jqv.form.validating", function (event) {
//		//$("#hookError").html("")
//	});
	$("#settings_form").bind("jqv.form.result", function (event, errorFound) {
//alertify.log("rotate_image : " + $('input[name=rotateimage]').filter(':checked').val(), "success", 2000);
		if (!errorFound) {
//return;
// TODO: hier noch sicherheitsabfrage: wirklich speichern ? ja / nein
			alertify.set({labels: {ok: "Ja", cancel: "Nein"}});
			alertify.set({buttonFocus: "cancel"}); // "none", "ok", "cancel"
			alertify.confirm('<h3>Auftrag speichern ?</h3><br/>', function (e) {
				if (e) {

			//prindot_job['JID'] = $('#prindot_job_JID').val();
			prindot_job['MID'] = prindot_machine;
			prindot_job['name'] = $('#prindot_job_name').val();
			prindot_job['comment'] = $('#prindot_job_comment').val();
			prindot_job['mode'] = prindot_mode;
			prindot_job['perimeter_pit_count'] = $('#prindot_naepfchenanzahl').val();
			prindot_job['pit_dist_perimeter_mm'] = $('#prindot_naepfchenabstandmm').val();
			prindot_job['perimeter_mm'] = $('#zylinderumfangmm').val();
			prindot_job['track_count'] = $('#prindot_spuranzahl').val();
			prindot_job['width_mm'] = $('#zylinderbreitemm').val();
			prindot_job['trackoffset_mm'] = (prindot_mode === 1) ? (prindot_job['pit_dist_perimeter_mm'] / 2.0) : 0;
			prindot_job['pit_dist_horizontal_mm'] = $('#prindot_spurabstandmm').val();
//			prindot_job['head_count'] = prindot_machines[prindot_machine]['act_head_count'];
			prindot_job['head_count'] = $('#prindot_job_actheadcount').val();
			prindot_job['status'] = 0;	// 1=running, 0=new, 2=finished, 3=canceled, 4=error, -1=imageprogress
			prindot_job['progress'] = 0;
			prindot_job['input_image'] = prindot_selectedfile;
			prindot_job['image_rotation'] = prindot_imagerotate;
			prindot_job['image_mirror'] = prindot_imagemirror;
			prindot_job['image_scale_flag'] = prindot_job_image_scale_flag;
			prindot_job['image_scale_x'] = 1.0;	//TODO: $("#image_scalex").val();
			prindot_job['image_scale_y'] = 1.0;	//TODO: $("#image_scalex").val();
			prindot_job['image_offsetx_mm'] = 0;	//TODO: $("#image_offsetx_mm").val();
			prindot_job['image_offsety_mm'] = 0;	//TODO: $("#image_offsety_mm").val();
			// TODO: hier 1-head_count
			prindot_job['headstart_1_mm'] = $('#prindot_job_head1startmm').val();
			prindot_job['headstart_2_mm'] = $('#prindot_job_head2startmm').val();
			prindot_job['headend_1_mm'] = prindot_job['headstart_2_mm'] - prindot_job['pit_dist_horizontal_mm'];
			prindot_job['headend_2_mm'] = prindot_job['width_mm'] - prindot_job['pit_dist_horizontal_mm'];
//			for (i = 1; i < prindot_job['head_count']; i++) {
//				prindot_job['headstart_' + i] = prindot_machines[prindot_machine]['headinitstartmm_' + i] / prindot_job['pit_dist_horizontal_mm'];
//				prindot_job['headstart_' + i] = Math.min(prindot_job['headstart_' + i], prindot_job['track_count'] - 1);
//				prindot_job['headend_' + i] = prindot_machines[prindot_machine]['headinitstartmm_' + (i + 1)] / prindot_job['pit_dist_horizontal_mm'] - 1;	// TODO: possibly calculated by calcstrand
//				prindot_job['headend_' + i] = Math.min(prindot_job['headend_' + i], prindot_job['track_count'] - 1);
//			}
//			prindot_job['headstart_' + i] = prindot_machines[prindot_machine]['headinitstartmm_' + i] / prindot_job['pit_dist_horizontal_mm'];
//			prindot_job['headstart_' + i] = Math.min(prindot_job['headstart_' + i], prindot_job['track_count'] - 1);
//			prindot_job['headstart_' + i] = Math.max(prindot_job['headstart_' + i], prindot_job['headend_' + (i - 1)] + 1);
//			prindot_job['headend_' + i] = prindot_job['track_count'] - 1;	// TODO: possibly calculated by calcstrand
//			prindot_job['headend_' + i] = Math.min(prindot_job['headend_' + i], prindot_job['track_count'] - 1);
//										prindot_job['headstart_1'] = prindot_machines[prindot_machine]['headinitstartmm_1'] / prindot_job['pit_dist_horizontal_mm'];
//										prindot_job['headstart_2'] = prindot_machines[prindot_machine]['headinitstartmm_2'] / prindot_job['pit_dist_horizontal_mm'];
//										prindot_job['headstart_3'] = prindot_machines[prindot_machine]['headinitstartmm_3'] / prindot_job['pit_dist_horizontal_mm'];
//										prindot_job['headstart_4'] = prindot_machines[prindot_machine]['headinitstartmm_4'] / prindot_job['pit_dist_horizontal_mm'];
//										prindot_job['headstart_5'] = prindot_machines[prindot_machine]['headinitstartmm_5'] / prindot_job['pit_dist_horizontal_mm'];
//										prindot_job['headstart_6'] = prindot_machines[prindot_machine]['headinitstartmm_6'] / prindot_job['pit_dist_horizontal_mm'];
//										prindot_job['headstart_7'] = prindot_machines[prindot_machine]['headinitstartmm_7'] / prindot_job['pit_dist_horizontal_mm'];
//										prindot_job['headstart_8'] = prindot_machines[prindot_machine]['headinitstartmm_8'] / prindot_job['pit_dist_horizontal_mm'];
//										prindot_job['headend_1'] = prindot_job['headstart_2'] - 1;	// TODO: possibly calculated by calcstrand
//										prindot_job['headend_2'] = prindot_job['headstart_3'] - 1;	// TODO: possibly calculated by calcstrand
//										prindot_job['headend_3'] = prindot_job['headstart_4'] - 1;	// TODO: possibly calculated by calcstrand
//										prindot_job['headend_4'] = prindot_job['headstart_5'] - 1;	// TODO: possibly calculated by calcstrand
//										prindot_job['headend_5'] = prindot_job['headstart_6'] - 1;	// TODO: possibly calculated by calcstrand
//										prindot_job['headend_6'] = prindot_job['headstart_7'] - 1;	// TODO: possibly calculated by calcstrand
//										prindot_job['headend_7'] = prindot_job['headstart_8'] - 1;	// TODO: possibly calculated by calcstrand
//										prindot_job['headend_8'] = prindot_job['track_count'] - 1;	// TODO: possibly calculated by calcstrand
			createjob();
			//alertify.log("create job ...", "success", 2000);
			// TODO: name muss eindeutig sein (und ungleich leer ... falls schon vorhanden, dann alert, ob ueberschreiben (fals nicht status running oder fertig - also nur ungestartete jobs)
				} else {
					//alertify.log("speichern abgebrochen");
				}
			});
		}
		else {
			alertify.log("NOT create job ...", "error", 2000);
		}
	});
});
//
//
//TODO: cylinder und naepfchen-werte in settings speichern, wenn alles da ist
//TODO: wie bei maschine: liste laden, (letzten aktuell lesen und ausfuellen), auswaehlen lassen
function calc_naepfchen_mm_from_count(field, rules, i, options) {
	if (field.val() >= 1) {
		if ($("#zylinderumfangmm").val() === '') {
			if ($("#prindot_naepfchenabstandmm").val() <= 0) {
				return "noch nicht gen&uuml;gend Werte zur Berechnung ... (Zylinderumfang fehlt)";
			}
			else {
				$("#zylinderumfangmm").val($("#prindot_naepfchenabstandmm").val() * field.val());
				$("#zylinderumfangmm").validationEngine('validate');
			}
		}
		else {
//			console.log("calc_naepfchen_mm_from_count : " + field.val() + " cylumf=" + $("#zylinderumfangmm").val());
			$("#prindot_naepfchenabstandmm").val($("#zylinderumfangmm").val() / field.val());
			$("#prindot_naepfchenabstandmm").validationEngine('hide');
//			console.log("prindot_naepfchenabstandmm = " + $("#prindot_naepfchenabstandmm").val());
		}
	}
	else {
		return "noch kein g&uuml;ltiger Wert angegeben";
	}
}
function calc_naepfchen_count_from_mm(field, rules, i, options) {
	if (field.val() >= 0.00001) {
		if ($("#zylinderumfangmm").val() === '') {
			if ($("#prindot_naepfchenanzahl").val() <= 0) {
				return "noch nicht gen&uuml;gend Werte zur Berechnung ... (Zylinderumfang fehlt)";
			}
			else {
				$("#zylinderumfangmm").val($("#prindot_naepfchenanzahl").val() * field.val());
				$("#zylinderumfangmm").validationEngine('validate');
				$("#prindot_naepfchenanzahl").validationEngine('validate');
			}
		}
		else {
//			console.log("TODO: calc_naepfchen_count_from_mm : " + field.val() + " cylumf=" + $("#zylinderumfangmm").val());
			$("#prindot_naepfchenanzahl").val(Math.round($("#zylinderumfangmm").val() / field.val()));
			$("#prindot_naepfchenanzahl").validationEngine('hide');
//			console.log("prindot_naepfchenanzahl = " + $("#prindot_naepfchenanzahl").val());
			// recalc because of possible rounding error
			$("#prindot_naepfchenabstandmm").val($("#zylinderumfangmm").val() / $("#prindot_naepfchenanzahl").val());
			$("#prindot_naepfchenabstandmm").validationEngine('hide');
//			console.log("prindot_naepfchenabstandmm = " + $("#prindot_naepfchenabstandmm").val());
		}
	}
	else {
		return "noch kein g&uuml;ltiger Wert angegeben";
	}
}
// TODO: berechnung spuren anzahl und abstand aufgrund der zylinder-breite (wie naepfchen oben)
function calc_spuren_mm_from_count(field, rules, i, options) {
	if (field.val() >= 1) {
		if ($("#zylinderbreitemm").val() === '') {
			if ($("#prindot_spurabstandmm").val() <= 0) {
				return "noch nicht gen&uuml;gend Werte zur Berechnung ... (Zylinderbreite fehlt)";
			}
			else {
				$("#zylinderbreitemm").val($("#prindot_spurabstandmm").val() * field.val());
				$("#zylinderbreitemm").validationEngine('validate');
			}
		}
		else {
			var v = field.val();
			var trackstep = 0.0004165;
			// v muss ganzhalig vielfaches von konfigurierten trackstep sein (0.4165µm):
			x = $("#zylinderbreitemm").val() / v;
			a = Math.round(x / trackstep) * trackstep;
//			console.log("TODO: calc_spuren_mm_from_count : " + v + " cylbreite=" + $("#zylinderbreitemm").val());
//			$("#prindot_spurabstandmm").val($("#zylinderbreitemm").val() / v);
			$("#prindot_spurabstandmm").val(a);
			$("#prindot_spurabstandmm").validationEngine('hide');
//			console.log("prindot_spurabstandmm = " + $("#prindot_spurabstandmm").val());
		}
	}
	else {
		return "noch kein g&uuml;ltiger Wert angegeben";
	}
}
function calc_spuren_count_from_mm(field, rules, i, options) {
	if (field.val() >= 0.00001) {
		if ($("#zylinderbreitemm").val() === '') {
			if ($("#prindot_spuranzahl").val() <= 0) {
				return "noch nicht gen&uuml;gend Werte zur Berechnung ... (Zylinderbreite fehlt)";
			}
			else {
				$("#zylinderbreitemm").val($("#prindot_spuranzahl").val() * field.val());
				$("#zylinderbreitemm").validationEngine('validate');
				$("#prindot_spuranzahl").validationEngine('validate');
			}
		}
		else {
			var v = field.val();
			var trackstep = 0.0004165;
			// v muss ganzhalig vielfaches von konfigurierten trackstep sein (0.4165µm):
			v = Math.round(v / trackstep) * trackstep;
//			console.log("TODO: calc_spuren_count_from_mm : " + v + " cylumf=" + $("#zylinderbreitemm").val());
			$("#prindot_spuranzahl").val(Math.round($("#zylinderbreitemm").val() / v));
			$("#prindot_spuranzahl").validationEngine('hide');
//			console.log("prindot_spuranzahl = " + $("#prindot_spuranzahl").val());
			// recalc because of possible rounding error
//			$("#prindot_spurabstandmm").val($("#zylinderbreitemm").val() / $("#prindot_spuranzahl").val());
			$("#prindot_spurabstandmm").val(v);
			//$("#prindot_spurabstandmm").validationEngine('validate');
//			console.log("prindot_spurabstandmm = " + $("#prindot_spurabstandmm").val());
		}
	}
	else {
		return "noch kein g&uuml;ltiger Wert angegeben";
	}
}

var prindot_raster = 0;
jQuery(document).ready(function () {
	$('#rasterselector').change(function () {
		prindot_raster = $('#rasterselector').val();
//									alertify.log("selected raster: '" + prindot_raster + "'", "success", 2000);
		$("#prindot_naepfchenabstandmm").val(prindot_rasters[prindot_raster]['dx']);
		$("#prindot_naepfchenabstandmm").validationEngine('validate');
		$("#prindot_spurabstandmm").val(prindot_rasters[prindot_raster]['dy']);
		$("#prindot_spurabstandmm").validationEngine('validate');
	});
});

//////////////////////////////////////////////////
// HANDLE JOBTABLE IN TAB HANDLEJOBS
var jobtable_rowselected = false;
$(document).ready(function () {
	var jobtable_selectedrow = '';
		// default with "+"
	$("#jobsUpdateButton").button({icons: {primary: 'ui-icon-arrowrefresh-1-s'}});
	$("#jobsUpdateButton").click(function () {
// TODO: unset ... refresh ... set
		$('#button-loadjob').hide();
		$('#button-startjob').hide();
		$('#button-stopjob').hide();
		jobtable_selectedrow = '';
		jobtable_rowselected = false;
		JobTable.fnDraw(false);
	});
	$('#button-loadjob').hide();
	$('#button-startjob').hide();
	$('#button-stopjob').hide();
	/* Add a click handler to the rows - this could be used as a callback */
	//$("#JobTable tbody tr").click( function( e ) {
	$('#jobtable tbody').on('click', 'tr', function () {
		if ($(this).hasClass('row_selected')) {
			$(this).removeClass('row_selected');
			jobtable_selectedrow = '';
			jobtable_rowselected = false;
			$('#button-loadjob').hide();
			$('#button-startjob').hide();
			$('#button-stopjob').hide();
		}
		else {
			JobTable.$('tr.row_selected').removeClass('row_selected');
			$(this).addClass('row_selected');
			jobtable_selectedrow = JobTable.fnGetData(this);
			jobtable_rowselected = true;
			//alertify.log("Daten &uumlbernehmen:<br /><b>" + jobtable_selectedrow['name'] + "</b><br /> <br />");
// TODO: wichtig: hier autorefresh der table ausschalten!!!, sonst wird bei refresh wieder deselectiert!

// TODO abhaengig von admin/user und job-status die button anzeigen/verstecken!!!
			$('#button-loadjob').hide();
			$('#button-startjob').hide();
			$('#button-stopjob').hide();
			$('#button-loadjob').show();
			if (prindot_admin) {
				var status = parseInt(jobtable_selectedrow['status']);
//alertify.log("status: " + status + " type:" + typeof(status));
				if (status === -1 || status === 1) {
					$('#button-stopjob').show();
				}
				else {
					$('#button-startjob').show();
				}
			}
		}
	});

	$("#button-loadjob").click(function () {
		var anSelected = fnGetSelected(JobTable);
		if (anSelected.length !== 0) {
//			JobTable.fnDeleteRow(anSelected[0]);
			jobtable_rowselected = false;
			$('#button-loadjob').hide();
			$('#button-startjob').hide();
			$('#button-stopjob').hide();
//			alertify.log("TODO: Daten &uumlbernehmen:<br /><b>" + jobtable_selectedrow['name'] + "</b><br /> <br />");
//			$("#infotext").text("<br />jobtable_selectedrow<br />" + print_r(jobtable_selectedrow));
//								alertify.set({labels: {ok: "Daten &uuml;bernehmen", cancel: "schlie&szlig;en"}});
//								alertify.confirm("Daten &uumlbernehmen:<br /><b>" + jobtable_selectedrow['name'] + "</b><br /> <br />", function(e) {
//									if (e) {
//										alertify.success("TODO: Daten uebernehmen");
//									} else {
//										//alertify.error("Abgebrochen");
//									}
//								});
			prindot_raster = 0;	// TODO: sollte ggf. auch in jobtable gespeichert weren (db) und hier lesen und vorselektieren)
			$("#rasterselector option[value='" + prindot_raster + "']").attr('selected', true);
			$("#rasterselector").change();
			prindot_mode = parseInt(jobtable_selectedrow['mode']);
// TODO: hier nicht doch vielleicht per AJAX aus der Datenbank lesen ??
//			console.log("mode:" + jobtable_selectedrow['mode'] + " ...  : '" + prindot_mode + "'");
//			switch (prindot_mode) {
//				case 1:
//					prindot_mode_str = "gravure";
//					break;
//				case 2:
//					prindot_mode_str = "groove";
//					break;
//				default:
//					prindot_mode = 0;
//					prindot_mode_str = "---";
//					break;
//			}
//			$("#prindot_mode").text(prindot_mode_str);
//			if (prindot_mode !== 0) {
//				$("#job_settings").children().prop('disabled', false);
//				$("#job_settings").show();
//			}
handle_mode_button(prindot_mode);
			$("#prindot_job_name").val(jobtable_selectedrow['name']);
			$("#prindot_job_comment").val(jobtable_selectedrow['comment']);
			$("#zylinderumfangmm").val(jobtable_selectedrow['perimeter_mm']);
			$("#zylinderbreitemm").val(jobtable_selectedrow['width_mm']);
			$("#prindot_naepfchenanzahl").val(jobtable_selectedrow['perimeter_pit_count']);
			$("#prindot_naepfchenabstandmm").val(jobtable_selectedrow['pit_dist_perimeter_mm']);
			$("#prindot_spuranzahl").val(jobtable_selectedrow['track_count']);
			$("#prindot_spurabstandmm").val(jobtable_selectedrow['pit_dist_horizontal_mm']);
			$("#prindot_job_actheadcount").val(jobtable_selectedrow['head_count']);
			$("#prindot_job_head1startmm").val(jobtable_selectedrow['headstart_1_mm']);
			$("#prindot_job_head2startmm").val(jobtable_selectedrow['headstart_2_mm']);
// auch die bild-daten laden und setzen (dateiname, drehung, spiegelung etc) und tab freischalten
			prindot_selectedfile = jobtable_selectedrow['input_image'];
			prindot_imagerotate = parseInt(jobtable_selectedrow['image_rotation']);
			$("#rotateclass").removeAttr('class');
			$("#rotateclassb").removeAttr('class');
			$("#rotateclass").addClass('rotate' + prindot_imagerotate);
			$("#rotateclassb").addClass('rotate' + prindot_imagerotate);
	//		$("#rotateimage1").prop("checked", false);
	//		$("#rotateimage2").prop("checked", false);
	//		$("#rotateimage3").prop("checked", false);
	//		$("#rotateimage4").prop("checked", false);
			switch (prindot_imagerotate)
			{
			case 0:
	//			$("#rotateimage1").attr("checked", "checked");
				$("#rotateimage1").trigger('click.button');
				break;
			case 90:
	//			$("#rotateimage2").attr("checked", "checked");
				$("#rotateimage2").trigger('click.button');
				break;
			case 270:
	//			$("#rotateimage3").attr("checked", "checked");
				$("#rotateimage3").trigger('click.button');
				break;
			case 180:
	//			$("#rotateimage4").attr("checked", "checked");
				$("#rotateimage4").trigger('click.button');
				break;
			}
	//		$("#rotateimage1").button("refresh");
	//		$("#rotateimage2").button("refresh");
	//		$("#rotateimage3").button("refresh");
	//		$("#rotateimage4").button("refresh");

			prindot_imagemirror = parseInt(jobtable_selectedrow['image_mirror']);
			$("#mirrorclass").removeAttr('class');
			$("#mirrorclassb").removeAttr('class');
			var mstr = 'mirror' + prindot_imagemirror;
			$("#mirrorclass").addClass(mstr);
			$("#mirrorclassb").addClass('mirror' + prindot_imagemirror);
//			$("#mirrorimage1").prop("checked", false);
//			$("#mirrorimage2").prop("checked", false);
//			$("#mirrorimage3").prop("checked", false);
			switch (prindot_imagemirror)
			{
			case 0:
//				$("#mirrorimage1").attr("checked", "checked");
				$("#mirrorimage1").trigger('click.button');
				break;
			case 1:
//				$("#mirrorimage2").attr("checked", "checked");
				$("#mirrorimage2").trigger('click.button');
				break;
			case 2:
//				$("#mirrorimage3").attr("checked", "checked");
				$("#mirrorimage3").trigger('click.button');
				break;
			}
	//		$("#mirrorimage1").button("refresh");
	//		$("#mirrorimage2").button("refresh");
	//		$("#mirrorimage3").button("refresh");
			prindot_job_image_scale_flag = parseInt(jobtable_selectedrow['image_scale_flag']);
			update_job_image_scale_flag();
// TODO: selected_file noch ueberpruefen, ob ueberhaupt vorhanden ... (per getimageinfo-return-wert !?))
			thumbnail = phpconfig['paths']['storage_root_js'] + phpconfig['paths']['thumbnails'] + '/' + prindot_selectedfile + '.jpg';
			$('#selectedfilesrc').attr('src', thumbnail);
			$('#selectedfilesrcb').attr('src', thumbnail);
			getimageinfo(prindot_selectedfile);

			// enable jobdefinition tab end switch to ...
			//$("#tabs").tabs("option", "active", 3);
		}
		//return false;
	});
	$("#button-startjob").click(function () {
		var anSelected = fnGetSelected(JobTable);
//$("#tab_handlejob_all").block({message: null, showOverlay: true, theme: true});
$.blockUI({message: 'bitte warten ...', showOverlay: true, theme: true, timeout: 10000});
		//alert(anSelected);

		if (anSelected.length !== 0) {
// TODO: den ganzen kram in jobs.php packen!!!
// ... damit auch dann calcstrand (calcjob) und startjob ... ?! (also check, calc, start/=send) ... damit auch die "sind sie sicher-box" vor dem check ... nicht so schoen (aber sicherer)
// ... also dedizierten check (dann meldung oder sind sie sicher) und dann start mit trotzdem check, calc, send)
// TODO: auch pruefen. ob nicht noch irgendwo ein job mit dieser maschine verbunden ist und laeuft (status -1 oder 1)
// TODO: Image-RAW (pixel 1:1)
// TODO: Bildbreite/hoehe vs. Zylinder hier (bzw. im PHP) schon pruefen, bevor dies zum Fehler im Calcstrand fuehrt
// TODO: hier die auftragswerte (jobtable_selectedrow[]) mit den aktuellen maschinenwerten (prindot_Machines[jobtable_selectedrow['MID']][]) vergleichen
// ... nicht der aktuell gewaehlten maschiene !?
// !! act_job mit jobtable_selectedrow['JID'] aus jobs.php lesen, damit richtige werte (nicht durch table reduziert) ?!
//					$("#pleasewait").dialog("open");
//								$("#progressbar").progressbar({value: false});	// show indeterminated progress
			progress(false);	// show indeterminated progress
//								$("#progressbar").progressbar("enable");
			checkjob_r = $.ajax({
				type: "POST",
				dataType: 'json',
				url: phpconfig['urls']['jobs'],
				data: {action: 'check', JID: jobtable_selectedrow['JID']}
			});
			checkjob_r.done(function (result) {
				progress(0);
				if (result['result'] != 'success')
				{
					alertify.alert("<h1><font color=\"red\">FEHLER:</font></h1><p><h3>" + result['reason'] + "</h3>");
				}
				else
				{
					alertify.set({labels: {ok: "Ja", cancel: "Nein"}});
					alertify.confirm("<h1><font color=\"red\">Starte Auftrag</font></h1><p><h3><br/>#" + jobtable_selectedrow['JID'] + " : <b>" + jobtable_selectedrow['name'] + "</b></h3><br /> <br />", function (e) {
						if (e) {
//							JobTable.fnDeleteRow(anSelected[0]);	// ???
							$('#button-loadjob').hide();
							$('#button-startjob').hide();
							$('#button-stopjob').hide();
jobtable_rowselected = false;
//										alertify.success("TODO: wird gestartet");
							prindot_machines[MID]['JID'] = jobtable_selectedrow['JID'];	// dann braucht db nicht neu gelesen werden
startjob(jobtable_selectedrow['JID'], jobtable_selectedrow['MID']);
// sollte direkt startjob sein, der (per php) wenn keine strand-datei vorhanden ist diese selber erzeugen (damit koennte ggf die strand-datei ja auch wann anders erzeugt worden sein - z:B. beim hochladen)
// TODO: hier dann
// ... damit auch dann calcstrand (calcjob) und startjob ... ?! (also check, calc, start/=send) ... damit auch die "sind sie sicher-box" vor dem check ... nicht so schoen (aber sicherer)
// ... also dedizierten check (dann meldung oder sind sie sicher) und dann start mit trotzdem check, calc, send)

//		handle_machine_progress();	// show actual progress
						} else {
//										alertify.error("TODO: nicht gestartet");
						}
					});
				}
$.unblockUI();
			});
			checkjob_r.fail(function (jqXHR, textStatus) {
				alertify.error("Request failed: " + textStatus);
				progress(0);
//$("#tab_handlejob_all").unblock();
$.unblockUI();
// TODO: meldung in db-logs schreiben
			});
			checkjob_r.always(function () {
//					$("#pleasewait").dialog("close");
//$.unblockUI();
			});

if (false)
{


			loadjob_r = $.ajax({
				type: "POST",
				dataType: 'json',
				url: phpconfig['urls']['jobs'],
				data: {action: 'load', JID: jobtable_selectedrow['JID']}
			});
			loadjob_r.done(function (result) {
				if (result['result'] === 'success') {
					act_job = result['value'];
//										alertify.success("SUCCESS RESULT: " + print_r(act_job));

					loadmachine_r = $.ajax({
						type: "POST",
						dataType: 'json',
						url: phpconfig['urls']['machines'],
						data: {action: 'load', MID: act_job['MID']}
					});
					loadmachine_r.done(function (result) {
						if (result['result'] === 'success') {
							act_machine = result['value'];
//							$("#pleasewait").dialog("close");
							progress(0);
//												alertify.success("SUCCESS RESULT: " + print_r(act_machine));


							error = false;
							error_str = '<h1><font color=\"red\">FEHLER:</font></h1><p><h3>';
// // ebenso machine wg. heartbeat
// :: jobs.php action=load JID=... (schon irgendwo oben??)
// >> auch bei button-loadjob (oben!)
								$("#infotext").html("<br />jobtable_selectedrow<br />" + print_r(jobtable_selectedrow) + "<br /><br />prindot_machines[jobtable_selectedrow['MID']]<br />" + print_r(prindot_machines[jobtable_selectedrow['MID']]));
// machine hat aktuellen heartbeat? (-60sek)
							d = new Date();
							ds = d.getTime();
							//ds_str = ds.toLocaleString();
								ds2 = Date.parse("2013-05-14 15:30");
								dsx = new Date(ds);
								ds_str = dsx.toLocaleString();
							hb_str = act_machine['heartbeat'];
							hb = Date.parse(hb_str);
							diffs = Math.abs(hb - ds) / 1000;
								x = "<br>ds:" + ds + " ds2:" + ds2 + " hb_str:" + hb_str + " hb:" + hb + " diffs:" + diffs;
								$("#infotext").append(x);
							max_heartbeat_timeout_sec = phpconfig['settings']['max_heartbeat_timeout_sec'];
							if (diffs > max_heartbeat_timeout_sec) {
								error = true;
								error_str = error_str + "Maschine hat sich nicht innerhalb der letzten " + max_heartbeat_timeout_sec + " Sekunden am Server gemeldet! ('heartbeat zu alt')<br />";
							}
// mode moeglich
							if (!(parseInt(act_machine['mode']) & parseInt(act_job['mode']))) {
								error = true;
								error_str = error_str + "Modus der Maschine (" + act_machine['mode'] + ") passt nicht zum Job (" + act_job['mode'] + ")<br />";
							}
// jobtable_selectedrow[perimeter_mm] == prindot_machines[jobtable_selectedrow['MID']][act_perimeter_mm] ?? (+/-1mm)
							if (Math.abs(parseFloat(act_machine['act_perimeter_mm']) - parseFloat(act_job['perimeter_mm'])) >= 1.0) {
								error = true;
								error_str = error_str + "Umfang des Zylinders (" + act_machine['act_perimeter_mm'] + "mm) passt nicht zum Job (" + act_job['perimeter_mm'] + "mm)<br />";
							}
// width_mm <= act_width_mm
							if (parseFloat(act_machine['act_width_mm']) < parseFloat(act_job['width_mm'])) {
								error = true;
								error_str = error_str + "Breite des Zylinders (" + act_machine['act_width_mm'] + "mm) ist kleiner als Breite des Jobs (" + act_job['width_mm'] + "mm)<br />";
							}
// head_count == act_head_count (?)
							if (parseInt(act_machine['act_head_count']) != parseInt(act_job['head_count'])) {
								error = true;
								error_str = error_str + "Aktuelle Kopfanzahl (" + act_machine['act_head_count'] + ") der Maschine entspricht nicht der im Job (" + act_job['head_count'] + ")<br />";
							}

// TODO: kopfpositionen vergleichen ! (ungefaehr)

// TODO: pruefen, ob nicht schon ein anderer job mit der maschine verbunden ist und -1 oder 1 als status hat (als ein job laeuft auf der maschine)
// muesste eine global variable sein, die aus dem
// showmachinejobprogress() gefuettert wird !?


							if (error) {
								error_str = error_str + "</h3><p>";
								alertify.alert(error_str);
							}
							else {

//								alertify.log("Auftrag starten:<br /><b>" + jobtable_selectedrow['name'] + "</b><br /> <br />");
								alertify.set({labels: {ok: "Ja", cancel: "Nein"}});
								alertify.confirm("Starte Auftrag #" + jobtable_selectedrow['JID'] + " :<br /><b>" + jobtable_selectedrow['name'] + "</b><br /> <br />", function (e) {
									if (e) {
										JobTable.fnDeleteRow(anSelected[0]);
										$('#button-loadjob').hide();
										$('#button-startjob').hide();
										$('#button-stopjob').hide();
			jobtable_rowselected = false;
//										alertify.success("TODO: wird gestartet");
										prindot_machines[MID]['JID'] = jobtable_selectedrow['JID'];	// dann braucht db nicht neu gelesen werden
		calcjob(jobtable_selectedrow['JID'], jobtable_selectedrow['MID']);
//		handle_machine_progress();	// show actual progress
									} else {
//										alertify.error("TODO: nicht gestartet");
									}
								});

							}
						}
						else {
							alertify.error("ERROR RESULT: " + result['reason']);
						}
//$("#tab_handlejob_all").unblock();
$.unblockUI();
//console.log("unblock");
//				$(document).oneTime(2000, 'testtimer', function () {	// damit auch die 0% noch dargestellt werden
//console.log("timer 1");
//					progress(50, "test");
//				$(document).oneTime(2000, 'testtimer', function () {	// damit auch die 0% noch dargestellt werden
//console.log("timer 2");
//					progress(0);
//				});
//				});
					});
					loadmachine_r.fail(function (jqXHR, textStatus) {
						alertify.error("Request failed: " + textStatus);
						// TODO: meldung in db-logs schreiben
// TODO: function errorbox(header, body) schreiben, damit schoen fehler-dialog gezeigt wird!
//$("#tab_handlejob_all").unblock();
$.unblockUI();
					});
					loadmachine_r.always(function () {
//							$("#pleasewait").dialog("close");
						progress(0);
					});

				}
				else {
					alertify.error("ERROR RESULT: " + result['reason']);
					progress(0);
//$("#tab_handlejob_all").unblock();
$.unblockUI();
				}
			});
			loadjob_r.fail(function (jqXHR, textStatus) {
				alertify.error("Request failed: " + textStatus);
				progress(0);
//$("#tab_handlejob_all").unblock();
$.unblockUI();
// TODO: meldung in db-logs schreiben
			});
			loadjob_r.always(function () {
//					$("#pleasewait").dialog("close");
			});
}
		}
		//return false;
	});

	$("#button-stopjob").click(function () {
		var anSelected = fnGetSelected(JobTable);
		$('#button-loadjob').hide();
		$('#button-startjob').hide();
		$('#button-stopjob').hide();
			jobtable_rowselected = false;
		if (anSelected.length !== 0) {
//			JobTable.fnDeleteRow(anSelected[0]);
//								alertify.log("Auftrag abbrechen:<br /><b>" + jobtable_selectedrow['name'] + "</b><br /> <br />");
			alertify.set({labels: {ok: "Ja", cancel: "Nein"}});
			alertify.confirm("<h1><font color=\"red\">Auftrag abbrechen:</font></h1><p><br /><h3>" + jobtable_selectedrow['name'] + "</h3><br /> <br />", function (e) {
				if (e) {
//										alertify.success("TODO: wird abgebrochen");
					stopjob(jobtable_selectedrow['JID'], jobtable_selectedrow['MID']);
				} else {
//										alertify.error("TODO: nicht abgebrochen");
				}
			});
		}
		//return false;
	});
});


/* Get the rows which are currently selected */
function fnGetSelected(oTableLocal) {
	return oTableLocal.$('tr.row_selected');
}


//////////////////////////////////////////////////
// TAB DEV
$(document).ready(function () {
	$("#IbuttonTestShowMode").click(function () {
		//console.log("Mode : ");
		alertify.log("HOSTNAME: " + prindot_hostname + "<br />PATHNAME: " + prindot_pathname + "<br />Admin: " + prindot_admin + "<br />Mode: " + prindot_mode + "<br />SelectedFile: " + prindot_selectedfile + "<br />phpConfig: " + print_r(phpconfig));
		//$("#IbuttonTestShowModeDiv").hide();
		//alert("Mode is : " + prindot_mode);
//var jqxhr = $.ajax( "sleep.php" )
//var jqxhr = $.ajax( {
//	type: 'POST',
//	async: true,
//	data: ({x: 1}),
//	url: "sleep.php"
//})
//    .done(function(data) { alertify.log("done : " + print_r(data)); })
//    .fail(function() { alertify.log("fail"); })
//    .always(function(data) { alertify.log("always : " + print_r(data)); });
//TODO: testen: erst eine sessionID holen, und wenn .done als function den job mit der sessionID starten
//- dieser arbeitet und schreibt alle paar sekunden oder in die session variable den progress
//- und als zweites mit der sessionID je sekunde progress holen mit einem zweiten skript
		var jqxhr_1 = $.ajax({
			type: 'POST',
			async: true,
			data: ({}),
			url: "_old/getSID.php"
		})
			.done(function (data) {
				var SID;
				SID = data;
				alertify.log("getSID.php: done : " + print_r(SID));
				var jqxhr_2 = $.ajax({
					type: 'POST',
					async: true,
					data: ({action: "run", sleepsec: 10, SID: SID}),
					url: "_old/sleep.php"
				})
					.done(function (data) {
						$("#infotext").append("<br />-done-<br />" + print_r(data));
						alertify.log("done : " + print_r(data));
					})
					.fail(function () {
						alertify.log("fail");
					})
					.always(function (data) {
					});
				var process = 0;
				var checkPercentage = function () {
					var jqxhr_3 = $.ajax({
						type: 'POST',
						url: "_old/sleep.php",
						data: ({action: "progress", SID: SID}),
						success: function (data) {
							process = data;
							if (process < 100) {
								$("#infotext").html("<br />-progress-<br />" + print_r(data));
							}
						}
					});
					if (process < 100) {
						setTimeout(checkPercentage, 1000);
					}
				};
				setTimeout(checkPercentage, 4000);
			})
			.fail(function () {
				alertify.log("fail");
			})
			.always(function (data) {
			});
		result = $.ajax({
			type: 'POST',
			async: false, // WICHTIG!
			url: 'machines.php',
			data: ({
				MID: '1234-56-7890-abc',
				action: 'getpreview'
			})
		}).responseText;
		alertify.log(result);
	});
});
$(document).ready(function () {
	// run the currently selected effect
	function runEffect() {
		// get effect type from
		//	  var selectedEffect = $( "#effectTypes" ).val();
		var selectedEffect = "slide";
		// most effect types need no options passed by default
		var options = {};
		// some effects have required parameters
		if (selectedEffect === "scale") {
			options = {percent: 0};
		} else if (selectedEffect === "size") {
			options = {to: {width: 200, height: 60}};
		}

		// run the effect
		//							$("#effect").hide(selectedEffect, options, 1000).delay(600).fadeIn(400);
		$("#effect").hide(selectedEffect, options, 600);
		//							$( "#tabs" ).tabs("enable", 3);
		//							$( "#tabs" ).tabs("option", {enable: 3});
		//$( "#tabs" ).tabs("option", {active: 3});
	}
	;
	// set effect from select menu value
	$("#button-switchuser").click(function () {
		//runEffect();
		//							$("#tabs").tabs({disable: []});
		//							$("#tabs").tabs({active: 3});
		//$("#tabs").tabs("enable", 4);
		//							$("#tabs").tabs("option", {enable: 8});
		//							$("#tabs").tabs("option", {active: 3});
		//alert("ok");

		prindot_admin = !prindot_admin;
		alertify.log("jetzt: Admin: " + prindot_admin);
		writelog(0, 0, 0, LOG_TYPE_INFO, LOG_SUBTYPE_APPLICATION, 'application admin/user switched to (' + (prindot_admin ? 'admin' : 'user') + ')');
		adminuserhandling();
		return false;

	});
	$("#button-showmessagebox").click(function () {
		alertify.set({labels: {ok: "Accept", cancel: "Deny"}});
		alertify.confirm("Confirm dialog with custom button labels", function (e) {
			if (e) {
				alertify.success("You've clicked OK");
			} else {
				alertify.error("You've clicked Cancel");
			}
		});
		return false;
	});
});

//						$("#serveradresse").val(prindot_protocol + '//' + prindot_hostname + (prindot_port !== '' ? ':' : '') + prindot_port + prindot_pathname);
$(document).ready(function () {
	$("#serveradresseinfo").text(prindot_protocol + '//' + prindot_hostname + (prindot_port !== '' ? ':' : '') + prindot_port + prindot_pathname);
});

$(document).ready(function () {
	$("#tab_dev_id").click(function () {
		alertify.error('NUR FUER ENTWICKLUNGS-ZWECKE !!!');
	});
});

//////////////////////////////////////////////////
// PROGRESS BAR
var progressbar_prefix = '';
$(function() {
	var progressbar = $("#progressbar"),
	progressLabel = $(".progress-label");

	progressbar.progressbar({
		value: false,
		create: function() {
//console.log("create: progressbar progressbar_prefix=" + progressbar_prefix);
			progressLabel.text( progressbar_prefix);
		},
		change: function() {
			var val = progressbar.progressbar( "value" );	/// || 0;
//console.log("progressbar val=" + ((val === false) ? "false" : val) + " progressbar_prefix=" + progressbar_prefix);
			if (val === 0)
			{
//console.log("0: progressbar val=" + ((val === false) ? "false" : val) + " progressbar_prefix=" + progressbar_prefix);
				progressLabel.text( progressbar_prefix);
			}
			else
			if (val === false)
			{
//console.log("false: progressbar val=" + ((val === false) ? "false" : val) + " progressbar_prefix=" + progressbar_prefix);
				progressLabel.text( progressbar_prefix);
			}
			else
			{
//console.log("x: progressbar val=" + ((val === false) ? "false" : val) + " progressbar_prefix=" + progressbar_prefix);
				progressLabel.text( progressbar_prefix + ' ' + val + "%" );
			}
		},
		complete: function() {
//console.log("complete: progressbar progressbar_prefix=" + progressbar_prefix);
//			progressLabel.text( progressbar_prefix + " Complete!" );
			progressLabel.text( progressbar_prefix);
		}
	});

//	progress(0);
//	$(document).oneTime(2000, 'testtimer', function () {	// damit auch die 0% noch dargestellt werden
//		progress(false);
//		$(document).oneTime(2000, 'testtimer', function () {	// damit auch die 0% noch dargestellt werden
//			progress(30);
//			$(document).oneTime(2000, 'testtimer', function () {	// damit auch die 0% noch dargestellt werden
//				progress(80);
//				$(document).oneTime(2000, 'testtimer', function () {	// damit auch die 0% noch dargestellt werden
//					progress(100);
//					$(document).oneTime(2000, 'testtimer', function () {	// damit auch die 0% noch dargestellt werden
//						progress(0);
//					});
//				});
//			});
//		});
//	});
});

function progress(v, prefix) {
	var pf = prefix || '';
	if ((v === $("#progressbar").progressbar( "value" )) && (progressbar_prefix !== pf))	// no change in val but in text
	{
		progressbar_prefix = pf;
		$(".progress-label").text( progressbar_prefix);
	}
	else
	{
		progressbar_prefix = pf;
		$("#progressbar").progressbar("option", "value", v);
	}
}
$("#progressbar").progressbar({value: 0});


$(document).ready(function () {
//					$("#pleasewait").dialog({ autoOpen: false, modal: true });
//					$("#progressbar").progressbar({ value: 0 });
	progress(false, "Init");
//			$("#progressbar").progressbar({ value: false });
});



$(document).ready(function () {
	// refresh auftragslisten darstellung here !!!
	$("#tabs").tabs({
		activate: function (event, ui) {
//						alert(ui.newPanel.attr('id'));

			if (ui.newPanel.attr('id') === "tab_handlejob") {
				var anSelected = fnGetSelected(JobTable);
//var oTT = TableTools.fnGetInstance('jobtable');
//				var anSelected = JobTable.fnGetSelected();
				JobTable.fnDraw(false);
				if (anSelected.length !== 0) {
					sid = anSelected.attr('id');
//					alertify.log("Dev-Info: length " + anSelected.attr('id'));	// TODO: gives "length row_36 when JID=#36 was active before coming back to jobtable tab
					console.log("Dev-Info: length '" + sid + "'");	// TODO: gives "length row_36 when JID=#36 was active before coming back to jobtable tab
					$('#button-loadjob').hide();
					$('#button-startjob').hide();
					$('#button-stopjob').hide();
//							$('#'  + anSelected.attr('id')).addClass('row_selected');
//							$('#'  + anSelected.attr('id')).click();
//JobTable.$("#row_16").click();	// geht, aber zeile wird nicht selected dargestellt (aber click() wird aufgerufen!
//JobTable.$("#row_16").addClass('row_selected');
//JobTable.$("#" + sid).addClass('row_selected');
//JobTable.$("#" + sid).click();	// geht, aber zeile wird nicht selected dargestellt (aber click() wird aufgerufen!
//console.log("click() on #" + sid);
// try: http://datatables.net/forums/discussion/5301/restore-state-of-selected-rows/p1
				}
			}
		}
	});
});


//////////////////////////////////////////////////
// SHORT HELP ACCORDION
//			$(document).ready(function() {
$(function () {	// shortcut of "$(document).ready(function() {"
	$('#howto').accordion({
		collapsible: true,
		active: false,
		heightStyle: "content"
	});
});

//////////////////////////////////////////////////
var prindot_job_image_scale_flag = true;
function update_job_image_scale_flag() {
	if (prindot_job_image_scale_flag)
	{
		//			alertify.log("scale");
		$("#prindot_job_imagescale").button( "option", "label", "skalieren" );
		$("#prindot_job_imagescale").attr("checked","checked");
	}
	else
	{
		//			alertify.log("1:1");
		$("#prindot_job_imagescale").button( "option", "label", "1:1" );
		$("#prindot_job_imagescale").removeAttr("checked");
	}
	$("#prindot_job_imagescale").button('refresh');
}
$(function () {
	$("#prindot_job_imagescale").button({ label: "skalieren" , disabled: false});	// enabled only for development/testing ?!
//	$("#prindot_job_imagescale").attr("checked","checked");
//    $("#prindot_job_imagescale").button('refresh');
	update_job_image_scale_flag();
	$("#prindot_job_imagescale").click(function () {
		prindot_job_image_scale_flag = this.checked;
		update_job_image_scale_flag();
	});

});


//////////////////////////////////////////////////
$(function () {
	writelog(0, 0, 0, LOG_TYPE_INFO, LOG_SUBTYPE_APPLICATION, 'application starts up (' + (prindot_admin ? 'admin' : 'user') + ')');
});
//////////////////////////////////////////////////
