<?php
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Easy set variables
	 */

	//$prindot = array();
	require_once('config.inc.php');
	require_once('functions.inc.php');
	require_once('connect2db.php');

	$debugfirst = false;
	$debug = false;

	if ($debugfirst)	error_log($_SERVER["SCRIPT_NAME"] . " : " . "_REQUEST[sEcho]='" . my_print_r($_REQUEST['sEcho'], true) . "'");

	/* Array of database columns which should be read and sent back to DataTables. Use a space where
	 * you want to insert a non-database field (for example a counter or static image)
	 */
	$aColumns = $prindot['database']['tablekeys']['jobs'];

	/* Indexed column (used for fast and accurate table cardinality) */
	$sIndexColumn = "JID";

	/* DB table to use */
	$sTable = $prindot['database']['tablenames']['jobs'];

	/* Database connection information */
//	$gaSql['user'] = $prindot['database']['username'];
//	$gaSql['password'] = $prindot['database']['password'];
//	$gaSql['db'] = $prindot['database']['name'];
//	$gaSql['server'] = $prindot['database']['host'];
//
//	if ($debug)	error_log($_SERVER["SCRIPT_NAME"] . " : " . "gaSql='" . my_print_r($gaSql, true) . "'");
//

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * If you just want to use the basic configuration for DataTables with PHP server-side, there is
	 * no need to edit below this line
	 */
	//TODO: umschreiben auf PDO (mysqli)

	/*
	 * Local functions
	 */
	function fatal_error ( $sErrorMessage = '' , $query)
	{
		header( $_SERVER['SERVER_PROTOCOL'] .' 500 Internal Server Error' );
		error_log($_SERVER["SCRIPT_NAME"] . " : '" . $sErrorMessage . "' with QUERY: '" . $query . "'");
		die( $sErrorMessage );
	}


	/*
	 * MySQL connection
	 */
//	if ( ! $gaSql['link'] = @mysqli_connect( "p:" . $gaSql['server'], $gaSql['user'], $gaSql['password']  ) )
//	{
//		if ( ! $gaSql['link'] = mysqli_connect( $gaSql['server'], $gaSql['user'], $gaSql['password']  ) )
//		{
//			fatal_error( 'Could not open connection to server', '');
//		}
//	}
//
//	if ( ! mysqli_select_db( $gaSql['link'], $gaSql['db'] ) )
//	{
//		fatal_error( 'Could not select database ', '');
//	}

	/*
	 * Paging
	 */
	$sLimit = "";
	if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
	{
		$sLimit = "LIMIT ".intval( $_GET['iDisplayStart'] ).", ".
			intval( $_GET['iDisplayLength'] );
	}


	/*
	 * Ordering
	 */
	$sOrder = "";
	if ( isset( $_GET['iSortCol_0'] ) )
	{
		$sOrder = "ORDER BY  ";
		for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
		{
			if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
			{
				$sOrder .= "`".$aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."` ".
					($_GET['sSortDir_'.$i]==='asc' ? 'asc' : 'desc') .", ";
			}
		}

		$sOrder = substr_replace( $sOrder, "", -2 );
		if ( $sOrder == "ORDER BY" )
		{
			$sOrder = "";
		}
	}


	/*
	 * Filtering
	 * NOTE this does not match the built-in DataTables filtering which does it
	 * word by word on any field. It's possible to do here, but concerned about efficiency
	 * on very large tables, and MySQL's regex functionality is very limited
	 */
	$sWhere = "";
	if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" )
	{
		$sWhere = "WHERE (";
		for ( $i=0 ; $i<count($aColumns) ; $i++ )
		{
			if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" )
			{
//				$sWhere .= "`".$aColumns[$i]."` LIKE '%".mysqli_real_escape_string( $gaSql['link'], $_GET['sSearch'] )."%' OR ";
				$sWhere .= "`".$aColumns[$i]."` LIKE '%".myescape( $_GET['sSearch'] )."%' OR ";
			}
		}
		$sWhere = substr_replace( $sWhere, "", -3 );
		$sWhere .= ')';
	}

	/* Individual column filtering */
	for ( $i=0 ; $i<count($aColumns) ; $i++ )
	{
		if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' )
		{
			if ( $sWhere == "" )
			{
				$sWhere = "WHERE ";
			}
			else
			{
				$sWhere .= " AND ";
			}
//			$sWhere .= "`".$aColumns[$i]."` LIKE '%".mysqli_real_escape_string($gaSql['link'], $_GET['sSearch_'.$i])."%' ";
			$sWhere .= "`".$aColumns[$i]."` LIKE '%".myescape($_GET['sSearch_'.$i])."%' ";
		}
	}


	/*
	 * SQL queries
	 * Get data to display
	 */
	$sQuery = "
		SELECT SQL_CALC_FOUND_ROWS " . $sIndexColumn . ", `".str_replace(" , ", " ", implode("`, `", $aColumns))."`
		FROM   $sTable
		$sWhere
		$sOrder
		$sLimit
		";
//	$rResult = mysqli_query( $gaSql['link'], $sQuery ) or fatal_error( 'MySQLi Error: ' . mysqli_errno($gaSql['link']), $sQuery );
$rResult = $db->prepare($sQuery) or fatal_error( 'MySQLi Error: ' . $db->errorCode(), $sQuery );
$rResult->execute();

	/* Data set length after filtering */
	$sQuery = "
		SELECT FOUND_ROWS()
	";
//	$rResultFilterTotal = mysqli_query( $gaSql['link'], $sQuery ) or fatal_error( 'MySQLi Error: ' . mysqli_errno($gaSql['link']), $sQuery );
$rResultFilterTotal = $db->prepare($sQuery) or fatal_error( 'MySQLi Error: ' . $db->errorCode(), $sQuery );
$rResultFilterTotal->execute();
//	$aResultFilterTotal = mysqli_fetch_array($rResultFilterTotal);
$aResultFilterTotal = $rResultFilterTotal->fetch(PDO::FETCH_NUM);
	$iFilteredTotal = $aResultFilterTotal[0];

	/* Total data set length */
	$sQuery = "
		SELECT COUNT(`".$sIndexColumn."`)
		FROM   $sTable
	";
//	$rResultTotal = mysqli_query( $gaSql['link'], $sQuery ) or fatal_error( 'MySQLi Error: ' . mysqli_errno($gaSql['link']), $sQuery );
$rResultTotal = $db->prepare($sQuery) or fatal_error( 'MySQLi Error: ' .$db->errorCode(), $sQuery );
$rResultTotal->execute();
//	$aResultTotal = mysqli_fetch_array($rResultTotal);
$aResultTotal = $rResultTotal->fetch(PDO::FETCH_NUM);
	$iTotal = $aResultTotal[0];


	/*
	 * Output
	 */
	$output = array(
		"sEcho" => intval($_GET['sEcho']),
		"iTotalRecords" => $iTotal,
		"iTotalDisplayRecords" => $iFilteredTotal,
		"aaData" => array()
	);

//	while ( $aRow = mysqli_fetch_array( $rResult ) )
	while ( $aRow = $rResult->fetch(PDO::FETCH_ASSOC))
	{
		$row = array();
		for ( $i=0 ; $i<count($aColumns) ; $i++ )
		{
//			if ( $aColumns[$i] == "version" )
//			{
//				/* Special output formatting for 'version' column */
//				$row[] = ($aRow[ $aColumns[$i] ]=="0") ? '-' : $aRow[ $aColumns[$i] ];
//			}
//			else if ( $aColumns[$i] != ' ' )
			{
				/* General output */
				$row[$aColumns[$i]] = $aRow[ $aColumns[$i] ];
			}
		}
		$row['DT_RowId'] = 'row_'.$aRow[$sIndexColumn];
		$row['DT_RowClass'] = 'status'.$aRow['status'];
		$output['aaData'][] = $row;
	}

	echo json_encode( $output );
?>