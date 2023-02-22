<?php
/**
 ***********************************************************************************************
 * Anzeigen einer Historie von Beitragszahlungen fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * history.php is a modified profile_field_history.php
 *
 * Parameters:
 *
 * mode:				Output(html, print, csv-ms, csv-oo, pdf, pdfl, xlsx)
 * filter_date_from: 	is set to actual date, if no date information is delivered
 * filter_date_to: 		is set to 31.12.9999, if no date information is delivered
 * filter_last_name:	Lastname for filter
 * filter_first_name:	Firstname for filter
 * export_and_filter:   additional menu for export and filter
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

// only authorized user are allowed to start this module
if (!isUserAuthorized($_SESSION['pMembershipFee']['script_name']))
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

$user = new User($gDb, $gProfileFields);

// calculate default date from which the contribution history should be shown
$filterDateFrom = DateTime::createFromFormat('Y-m-d', DATE_NOW);
$filterDateFrom->modify('-'.$gSettingsManager->getString('members_days_field_history').' day');

// Initialize and check the parameters
$getDateFrom   	    = admFuncVariableIsValid($_GET, 'filter_date_from', 'date',  array('defaultValue'  => $filterDateFrom->format($gSettingsManager->getString('system_date'))));
$getDateTo     	    = admFuncVariableIsValid($_GET, 'filter_date_to',   'date',  array('defaultValue'  => DATE_NOW));
$getLastName 	    = admFuncVariableIsValid($_GET, 'filter_last_name', 'string');
$getFirstName 	    = admFuncVariableIsValid($_GET, 'filter_first_name','string');
$getMode       	    = admFuncVariableIsValid($_GET, 'mode',             'string', array('defaultValue' => 'html', 'validValues' => array('csv-ms', 'csv-oo', 'html', 'print', 'pdf', 'pdfl', 'xlsx' )));
$getExportAndFilter = admFuncVariableIsValid($_GET, 'export_and_filter', 'bool', array('defaultValue' => false));

$title    = $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_HISTORY');
$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_HISTORY');
$filename = $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_HISTORY');

// filter_date_from and filter_date_to can have different formats
// now we try to get a default format for intern use and html output
$objDateFrom = DateTime::createFromFormat('Y-m-d', $getDateFrom);
if($objDateFrom === false)
{
    // check if date has system format
    $objDateFrom = DateTime::createFromFormat($gSettingsManager->getString('system_date'), $getDateFrom);
    if($objDateFrom === false)
    {
        $objDateFrom = DateTime::createFromFormat($gSettingsManager->getString('system_date'), '1970-01-01');
    }
}

$objDateTo = DateTime::createFromFormat('Y-m-d', $getDateTo);
if($objDateTo === false)
{
    // check if date has system format
    $objDateTo = DateTime::createFromFormat($gSettingsManager->getString('system_date'), $getDateTo);
    if($objDateTo === false)
    {
        $objDateTo = DateTime::createFromFormat($gSettingsManager->getString('system_date'), '1970-01-01');
    }
}

// DateTo should be greater than DateFrom
if($objDateFrom > $objDateTo)
{
    $gMessage->show($gL10n->get('SYS_DATE_END_BEFORE_BEGIN'));
    // => EXIT
}

$dateFromIntern = $objDateFrom->format('Y-m-d');
$dateFromHtml   = $objDateFrom->format($gSettingsManager->getString('system_date'));
$dateToIntern   = $objDateTo->format('Y-m-d');
$dateToHtml     = $objDateTo->format($gSettingsManager->getString('system_date'));

// initialize some special mode parameters
$separator   = '';
$valueQuotes = '';
$charset     = '';
$classTable  = '';
$orientation = '';
$csvStr 	 = ''; // CSV file as string
$header = array();              //'xlsx'
$rows   = array();              //'xlsx'

switch ($getMode)
{
	case 'csv-ms':
		$separator   = ';';  // Microsoft Excel 2007 or new needs a semicolon
		$valueQuotes = '"';  // all values should be set with quotes
		$getMode     = 'csv';
		$charset     = 'iso-8859-1';
		break;
	case 'csv-oo':
		$separator   = ',';   // a CSV file should have a comma
		$valueQuotes = '"';   // all values should be set with quotes
		$getMode     = 'csv';
		$charset     = 'utf-8';
		break;
	case 'pdf':
		$classTable  = 'table';
		$orientation = 'P';
		$getMode     = 'pdf';
		break;
	case 'pdfl':
		$classTable  = 'table';
		$orientation = 'L';
		$getMode     = 'pdf';
		break;
	case 'html':
		$classTable  = 'table table-condensed';
		break;
	case 'print':
		$classTable  = 'table table-condensed table-striped';
		break;
    case 'xlsx':
	    include_once(__DIR__ . '/libs/PHP_XLSXWriter/xlsxwriter.class.php');
	    $getMode     = 'xlsx';
	    break;
	default:
		break;
}

$sqlConditions = 'WHERE TRUE ';
if ($getLastName !== '' )
{
	$sqlConditions .= 'AND last_name.usd_value = \''. $getLastName.'\' ';
}
if ( $getFirstName !== '')
{
	$sqlConditions .= 'AND first_name.usd_value = \''. $getFirstName.'\' ';
}

// create select statement with all necessary data
$sql = 'SELECT usl_id, usl_usr_id, last_name.usd_value AS last_name, first_name.usd_value AS first_name, usl_usf_id, usl_value_new, usl_timestamp_create
          FROM '.TBL_USER_LOG.'
    INNER JOIN '.TBL_USER_DATA.' AS last_name
            ON last_name.usd_usr_id = usl_usr_id
           AND last_name.usd_usf_id =  ? -- $gProfileFields->getProperty(\'LAST_NAME\', \'usf_id\')
    INNER JOIN '.TBL_USER_DATA.' AS first_name
            ON first_name.usd_usr_id = usl_usr_id
           AND first_name.usd_usf_id =  ? -- $gProfileFields->getProperty(\'FIRST_NAME\', \'usf_id\')
			'. $sqlConditions.'
      ORDER BY usl_id ASC ';      

$queryParams = array(
	   $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
	   $gProfileFields->getProperty('FIRST_NAME', 'usf_id')
    );
       
$fieldHistoryStatement = $gDb->queryPrepared($sql, $queryParams);

if($fieldHistoryStatement->rowCount() === 0)
{
    $gMessage->show($gL10n->get('SYS_NO_CHANGES_LOGGED'));
    // => EXIT
}

if ($getMode !== 'csv' && $getMode != 'xlsx' )
{
	$datatable = false;
	$hoverRows = false;

	if ($getMode === 'print')
	{
		// create html page object without the custom theme files
		$page = new HtmlPage('plg-mitgliedsbeitrag-history-print', $headline);
		$page->setPrintMode();
		$page->setTitle($title);
		$table = new HtmlTable('history_table', $page, $hoverRows, $datatable, $classTable);
	}
	elseif ($getMode === 'pdf')
	{
		$pdf = new TCPDF($orientation, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Admidio');
		$pdf->SetTitle($headline);

		// remove default header/footer
		$pdf->setPrintHeader(true);
		$pdf->setPrintFooter(false);
		
		// set header and footer fonts
		$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set auto page breaks
		$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
		$pdf->SetMargins(10, 20, 10);
		$pdf->setHeaderMargin(10);
		$pdf->setFooterMargin(0);

		// headline for PDF
		$pdf->setHeaderData('', 0, $headline, '');

		// set font
		$pdf->SetFont('times', '', 10);

		// add a page
		$pdf->AddPage();

		// Create table object for display
		$table = new HtmlTable('history_table', null, $hoverRows, $datatable, $classTable);
		$table->addAttribute('border', '1');
		$table->addTableHeader();
		$table->addRow();
	}
	elseif ($getMode === 'html')
	{
		if ($getExportAndFilter)
        {
            $datatable = false;
        }
        else
        {
            $datatable = true;
        }
		$hoverRows = true;

		$gNavigation->addUrl(CURRENT_URL, $headline);
		
		$page = new HtmlPage('plg-mitgliedsbeitrag-history-html', $headline);

		$page->setTitle($title);

        $page->addJavascript('
            $("#menu_item_lists_print_view").click(function() {
                window.open("'.SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/history.php', array(
                    'filter_date_from'  => $getDateFrom,
                    'filter_date_to'    => $getDateTo,              
                    'filter_last_name'  => $getLastName,
                    'filter_first_name' => $getFirstName,
                    'export_and_filter' => $getExportAndFilter, 
                    'mode'              => 'print'
                )) . '", "_blank");
            });
            $("#export_and_filter").change(function() {
                $("#navbar_checkbox_form").submit();
            });
            $("#filter_date_from").change(function() {
                $("#navbar_filter_form").submit();
            });
            $("#filter_date_to").change(function() {
                $("#navbar_filter_form").submit();
            });
             $("#filter_last_name").change(function() {
                $("#navbar_filter_form").submit();
            });
            $("#filter_first_name").change(function() {
                $("#navbar_filter_form").submit();
            });
        ', true);             
        
        if ($getExportAndFilter)
        {
            // links to print and exports
            $page->addPageFunctionsMenuItem('menu_item_lists_print_view', $gL10n->get('SYS_PRINT_PREVIEW'), 'javascript:void(0);', 'fa-print');
        
            // dropdown menu item with all export possibilities
            $page->addPageFunctionsMenuItem('menu_item_lists_export', $gL10n->get('SYS_EXPORT_TO'), '#', 'fa-file-download');
            $page->addPageFunctionsMenuItem('menu_item_lists_xlsx', $gL10n->get('SYS_MICROSOFT_EXCEL').' (XLSX)',
                SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/history.php', array(
                    'filter_date_from'  => $getDateFrom,
                    'filter_date_to'    => $getDateTo,              
                    'filter_last_name'  => $getLastName,
                    'filter_first_name' => $getFirstName,
                    'export_and_filter' => $getExportAndFilter,
                    'mode'              => 'xlsx')),
                'fa-file-excel', 'menu_item_lists_export');
            $page->addPageFunctionsMenuItem('menu_item_lists_csv_ms', $gL10n->get('SYS_MICROSOFT_EXCEL').' (CSV)',
                SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/history.php', array(
                    'filter_date_from'  => $getDateFrom,
                    'filter_date_to'    => $getDateTo,              
                    'filter_last_name'  => $getLastName,
                    'filter_first_name' => $getFirstName,
                    'export_and_filter' => $getExportAndFilter,
                    'mode'              => 'csv-ms')),
                'fa-file-excel', 'menu_item_lists_export');
            $page->addPageFunctionsMenuItem('menu_item_lists_pdf', $gL10n->get('SYS_PDF').' ('.$gL10n->get('SYS_PORTRAIT').')',
                SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/history.php', array(
                    'filter_date_from'  => $getDateFrom,
                    'filter_date_to'    => $getDateTo,              
                    'filter_last_name'  => $getLastName,
                    'filter_first_name' => $getFirstName,
                    'export_and_filter' => $getExportAndFilter,
                    'mode'              => 'pdf')),
                'fa-file-pdf', 'menu_item_lists_export');
            $page->addPageFunctionsMenuItem('menu_item_lists_pdfl', $gL10n->get('SYS_PDF').' ('.$gL10n->get('SYS_LANDSCAPE').')',
                SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/history.php', array(
                    'filter_date_from'  => $getDateFrom,
                    'filter_date_to'    => $getDateTo,              
                    'filter_last_name'  => $getLastName,
                    'filter_first_name' => $getFirstName,
                    'export_and_filter' => $getExportAndFilter,
                    'mode'              => 'pdfl')),
                'fa-file-pdf', 'menu_item_lists_export');
            $page->addPageFunctionsMenuItem('menu_item_lists_csv', $gL10n->get('SYS_CSV').' ('.$gL10n->get('SYS_UTF8').')',
                SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/history.php', array(
                    'filter_date_from'  => $getDateFrom,
                    'filter_date_to'    => $getDateTo,              
                    'filter_last_name'  => $getLastName,
                    'filter_first_name' => $getFirstName,
                    'export_and_filter' => $getExportAndFilter,
                    'mode'              => 'csv-oo')),
                'fa-file-csv', 'menu_item_lists_export');
        }
  
        $form = new HtmlForm('navbar_checkbox_form', SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/history.php'),  $page, array('type' => 'navbar', 'setFocus' => false));
        $form->addCheckbox('export_and_filter', $gL10n->get('PLG_MITGLIEDSBEITRAG_EXPORT_AND_FILTER'), $getExportAndFilter);
        
        $page->addHtml($form->show());
 
        if ($getExportAndFilter)
        {    
            $form = new HtmlForm('navbar_filter_form', SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/history.php'),  $page, array('type' => 'navbar', 'setFocus' => false));
            $form->addInput('filter_date_from', $gL10n->get('SYS_START'), $dateFromHtml, array('type' => 'date', 'maxLength' => 10));
            $form->addInput('filter_date_to', $gL10n->get('SYS_END'), $dateToHtml, array('type' => 'date', 'maxLength' => 10));
            $form->addInput('filter_last_name', $gL10n->get('SYS_LASTNAME'), $getLastName) ;
            //$form->addInput('filter_first_name', $gL10n->get('SYS_FIRSTNAME'), $getFirstName);
            $form->addInput('export_and_filter', '', $getExportAndFilter, array('property' => HtmlForm::FIELD_HIDDEN));

            $page->addHtml($form->show());
        }
        
		$table = new HtmlTable('history_table', $page, $hoverRows, $datatable, $classTable);
		$table->setDatatablesRowsPerPage($gSettingsManager->getString('groups_roles_members_per_page'));
	}
	else
	{
		$table = new HtmlTable('history_table', $page, $hoverRows, $datatable, $classTable);
	}
}

//header definitions
$columnHeading = array();
$columnHeading[] = $gL10n->get('SYS_NAME');
$columnHeading[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_PAID_ON');
$columnHeading[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE');
$columnHeading[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE');
$columnHeading[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT');

$rows[] = $columnHeading;           // header for 'xlsx'

foreach($columnHeading as $headerData)
{
	if($getMode === 'csv')
	{
		$csvStr .= $separator. $valueQuotes. $headerData. $valueQuotes;
	}
	elseif($getMode === 'pdf')
	{
		$table->addColumn($headerData, array('style' => 'text-align: center;font-size:14;background-color:#C7C7C7;'), 'th');
	}
}

if($getMode === 'csv')
{
	$csvStr = substr($csvStr,strlen($separator))."\n";
}
elseif($getMode === 'html')
{
	$table->setColumnAlignByArray(array('left','left','left','left','left'));
	$table->setDatatablesOrderColumns(array(array(5, 'desc')));
	$table->addRowHeadingByArray($columnHeading);
}
elseif( $getMode === 'print')
{
	$table->setColumnAlignByArray(array('center','center','center','center','center'));
	$table->setDatatablesOrderColumns(array(array(5, 'desc')));
	$table->addRowHeadingByArray($columnHeading);
}
elseif ($getMode == 'xlsx')
{
    // nothing to do
}
else
{
	$table->addTableBody();
}

$columnValues       = array();
$presetColumnValues = array();

if ($getMode === 'csv' || $getMode == 'xlsx')
{
	$presetColumnValues[$gProfileFields->getProperty('PAID'.$gCurrentOrgId, 'usf_id')] =  '';
	$presetColumnValues[$gProfileFields->getProperty('DUEDATE'.$gCurrentOrgId, 'usf_id')] =  '';
	$presetColumnValues[$gProfileFields->getProperty('FEE'.$gCurrentOrgId, 'usf_id')] =  '';
	$presetColumnValues[$gProfileFields->getProperty('CONTRIBUTORY_TEXT'.$gCurrentOrgId, 'usf_id')] =  '';
}
else 
{
	$presetColumnValues[$gProfileFields->getProperty('PAID'.$gCurrentOrgId, 'usf_id')] =  '&nbsp;';
	$presetColumnValues[$gProfileFields->getProperty('DUEDATE'.$gCurrentOrgId, 'usf_id')] =  '&nbsp;';
	$presetColumnValues[$gProfileFields->getProperty('FEE'.$gCurrentOrgId, 'usf_id')] =  '&nbsp;';
	$presetColumnValues[$gProfileFields->getProperty('CONTRIBUTORY_TEXT'.$gCurrentOrgId, 'usf_id')] =  '&nbsp;';
}

while($row = $fieldHistoryStatement->fetch())
{    
 	if(!array_key_exists( $row['usl_usf_id'],$presetColumnValues))
 	{
 		continue;
 	}
 	
 	if(!isset($columnValues[$row['usl_usr_id']]))
 	{
 		$columnValues[$row['usl_usr_id']] = $presetColumnValues;
 	}
   
   	$columnValues[$row['usl_usr_id']][$row['usl_usf_id']] = $gProfileFields->getHtmlValue($gProfileFields->getPropertyById((int) $row['usl_usf_id'], 'usf_name_intern'), $row['usl_value_new']);    
   	
   	if(	$row['usl_usf_id'] == $gProfileFields->getProperty('PAID'.$gCurrentOrgId, 'usf_id') 
   		&& $row['usl_value_new'] != '' 
   		&& $row['usl_value_new'] >= $dateFromIntern 
   		&& $row['usl_value_new'] <= $dateToIntern )
   	{  		
   		//add last_name and first_name to the first column 
   		array_unshift($columnValues[$row['usl_usr_id']], $row['last_name'].', '.$row['first_name']);
   		
   		if ($getMode === 'csv')
   		{
   			$csvTmp = '';
   			foreach($columnValues[$row['usl_usr_id']] as $dummy => $data)
   			{
   				$csvTmp .= $separator.$valueQuotes.$data.$valueQuotes;
   			}
   			$csvStr .= substr($csvTmp,strlen($separator))."\n";
   		}
   		elseif($getMode === 'print' || $getMode === 'pdf')
   		{
   			$table->setColumnAlignByArray(array('center','center','center','center','center'));
   			$table->addRowByArray($columnValues[$row['usl_usr_id']], null, array('nobr' => 'true'));
   		}
        elseif($getMode === 'xlsx')
   		{
            $rows[] = $columnValues[$row['usl_usr_id']];
        }
   		else
   		{
            $user->readDataById($row['usl_usr_id']);
   			$table->addRowByArray($columnValues[$row['usl_usr_id']], null, array('style' => 'cursor: pointer', 'onclick' => 'window.location.href=\''. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))). '\''));
   		}
   		unset($columnValues[$row['usl_usr_id']]);
   	}
}

// Settings for export file
if($getMode === 'csv' || $getMode === 'pdf'|| $getMode == 'xlsx')
{
	$filename = FileSystemUtils::getSanitizedPathEntry($filename) . '.' . $getMode;

    header('Content-Disposition: attachment; filename="'.$filename.'"');
    
    // neccessary for IE6 to 8, because without it the download with SSL has problems
    header('Cache-Control: private');
    header('Pragma: public');
}

if($getMode === 'csv')							// send the CSV-File to the user
{
	header('Content-Type: text/comma-separated-values; charset='.$charset);

	if($charset === 'iso-8859-1')
	{
		echo utf8_decode($csvStr);
	}
	else
	{
		echo $csvStr;
	}
}
elseif($getMode === 'pdf')						// send the PDF-File to the User
{
	// output the HTML content
    $pdf->writeHTML($table->getHtmlTable(), true, false, true);

    $file = ADMIDIO_PATH . FOLDER_DATA . '/' . $filename;

    // Save PDF to file
    $pdf->Output($file, 'F');

    // Redirect
    header('Content-Type: application/pdf');

    readfile($file);
    ignore_user_abort(true);

    try
    {
        FileSystemUtils::deleteFileIfExists($file);
    }
    catch (\RuntimeException $exception)
    {
        $gLogger->error('Could not delete file!', array('filePath' => $file));
        // TODO
    }
}
elseif ($getMode == 'xlsx')
{
    header('Content-disposition: attachment; filename="'.XLSXWriter::sanitize_filename($filename).'"');
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    $writer = new XLSXWriter();
    $writer->setAuthor($gCurrentUser->getValue('FIRST_NAME').' '.$gCurrentUser->getValue('LAST_NAME'));
    $writer->setTitle($filename);
    $writer->setSubject($gL10n->get('PLG_GEBURTSTAGSLISTE_BIRTHDAY_LIST'));
    $writer->setCompany($gCurrentOrganization->getValue('org_longname'));
    $writer->setKeywords(array($gL10n->get('PLG_GEBURTSTAGSLISTE_BIRTHDAY_LIST'), $gL10n->get('PLG_GEBURTSTAGSLISTE_PATTERN')));
    $writer->setDescription($gL10n->get('PLG_GEBURTSTAGSLISTE_CREATED_WITH'));
    $writer->writeSheet($rows,'', $header);
    $writer->writeToStdOut();
}
elseif ($getMode == 'html' && $getExportAndFilter)
{
    $page->addHtml('<div style="width:100%; height: 500px; overflow:auto; border:20px;">');
    $page->addHtml($table->show(false));
    $page->addHtml('</div><br/>');
    
    $page->show();
}
elseif (($getMode == 'html' && !$getExportAndFilter) || $getMode == 'print')
{
    $page->addHtml($table->show(false));
    
    $page->show();
}

