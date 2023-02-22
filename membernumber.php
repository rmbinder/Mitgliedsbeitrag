<?php
/**
 ***********************************************************************************************
 * Dieses Plugin generiert fuer aktive Mitglieder der aktuellen Organisation eine Mitgliedsnummer.
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 * 
 ***********************************************************************************************
 */

/******************************************************************************
 * Parameters:
 *
 * mode       : preview - preview of the new member numbers
 *              write   - save the new member numbers
 *              print   - preview fpr printing  
 *
 *****************************************************************************/

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');
require_once(__DIR__ . '/classes/membernumbers.php');

// only authorized user are allowed to start this module
if (!isUserAuthorized($_SESSION['pMembershipFee']['script_name']))
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Initialize and check the parameters
$getMode    = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'preview', 'validValues' => array('preview', 'write', 'print')));
$postFormat = admFuncVariableIsValid($_POST, 'producemembernumber_format', 'string');

//an array can not be checked with admFuncVariableIsValid
$postRoleselection = isset($_POST['producemembernumber_roleselection']) ? $_POST['producemembernumber_roleselection'] : '';
$postFillGaps      = isset($_POST['producemembernumber_fill_gaps']) ? $_POST['producemembernumber_fill_gaps'] : '';

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

// set headline of the script
$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_PRODUCE_MEMBERNUMBER');

$gNavigation->addUrl(CURRENT_URL, $headline);

if ($getMode == 'preview')     //Default
{
    $page = new HtmlPage('plg-mitgliedsbeitrag-membernumber-preview', $headline);
    
	$membernumbers = new Membernumbers($gDb);

	if ($membernumbers->isDoubleNumber())
	{
		$gMessage->show($gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER_ERROR', array($membernumbers->isDoubleNumber())));
		// --> EXIT
	}

	$membernumbers->readUserWithoutMembernumber($postRoleselection);
	$membernumbers->separateFormatSegment($postFormat);
	$membernumbers->getMembernumber($postFillGaps);
	
	$_SESSION['pMembershipFee']['membernumber_rol_sel'] = $postRoleselection;
	$_SESSION['pMembershipFee']['membernumber_format'] = $postFormat;
	$_SESSION['pMembershipFee']['membernumber_fill_gaps'] = $postFillGaps;

	if ($membernumbers->userWithoutMembernumberExist)
	{
		// save new membernumbers in session (for mode write and mode print)
		$_SESSION['pMembershipFee']['membernumber_user'] = $membernumbers->mUserWithoutMembernumber;
	
		$datatable = true;
		$hoverRows = true;
		$classTable  = 'table table-condensed';
        
		$table = new HtmlTable('table_new_membernumbers', $page, $hoverRows, $datatable, $classTable);
		$table->setColumnAlignByArray(array('left', 'left', 'center'));
		$columnValues = array($gL10n->get('SYS_LASTNAME'), $gL10n->get('SYS_FIRSTNAME'), $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER_NEW'));
		$table->addRowHeadingByArray($columnValues);

		foreach ($membernumbers->mUserWithoutMembernumber as $data)
		{
			$columnValues = array();
			$columnValues[] = $data['last_name'];
			$columnValues[] = $data['first_name'];
			$columnValues[] = $data['membernumber'];
			$table->addRowByArray($columnValues);
		}

		$page->addHtml($table->show(false));
        
    	$form = new HtmlForm('membernumber_preview_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/membernumber.php', array('mode' => 'write')), $page);       
		$form->addSubmitButton('btn_next_page', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check'));
		$form->addDescription('<br/>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER_PREVIEW'));
        
        $page->addHtml($form->show(false));
	}
	else 
	{
        $page->addHtml($gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER_NO_ASSIGN').'<br/><br/>');
	}
}
elseif ($getMode == 'write')
{
    $page = new HtmlPage('plg-mitgliedsbeitrag-membernumber-write', $headline);

 	$page->addPageFunctionsMenuItem('menu_item_print_view', $gL10n->get('SYS_PRINT_PREVIEW'), 'javascript:void(0);', 'fa-print');
    
	$page->addJavascript('
    	$("#menu_item_print_view").click(function() {
            window.open("'. SecurityUtils::encodeUrl(ADMIDIO_URL. FOLDER_PLUGINS . PLUGIN_FOLDER .'/membernumber.php', array('mode' => 'print')). '", "_blank");
        });',
		true
	);
	
	$datatable = false;
	$hoverRows = true;
	$classTable  = 'table table-condensed';
    
	$table = new HtmlTable('table_saved_membernumbers', $page, $hoverRows, $datatable, $classTable);
	$table->setColumnAlignByArray(array('left', 'left', 'center'));
	$columnValues = array($gL10n->get('SYS_LASTNAME'), $gL10n->get('SYS_FIRSTNAME'), $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER_NEW'));
	$table->addRowHeadingByArray($columnValues);
	
	$user = new User($gDb, $gProfileFields);
	
	foreach ($_SESSION['pMembershipFee']['membernumber_user'] as $data)
	{
		$columnValues = array();
		$columnValues[] = $data['last_name'];
		$columnValues[] = $data['first_name'];
		$columnValues[] = $data['membernumber'];
		$table->addRowByArray($columnValues);
		
		$user->readDataById($data['usr_id']);
		$user->setValue('MEMBERNUMBER'.$gCurrentOrgId, $data['membernumber']);
		$user->save();
	}
	
	$page->addHtml('<div style="width:100%; height: 500px; overflow:auto; border:20px;">');
	$page->addHtml($table->show(false));
	$page->addHtml('</div><br/>');
    $page->addHtml('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER_SAVED').'</strong><br/><br/>');
	
	// save the format string in database
	$pPreferences->config['membernumber']['format'] = $_SESSION['pMembershipFee']['membernumber_format'];
	$pPreferences->config['membernumber']['fill_gaps'] = $_SESSION['pMembershipFee']['membernumber_fill_gaps'];
	$pPreferences->save();
}
elseif ($getMode == 'print')
{
	$hoverRows = false;
	$datatable = false;
	$classTable  = 'table table-condensed table-striped';
    
    $page = new HtmlPage('plg-mitgliedsbeitrag-membernumber-print', $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBERS_NEW'));
	$page->setPrintMode();

	$table = new HtmlTable('table_print_membernumbers', $page, $hoverRows, $datatable, $classTable);
	$table->setColumnAlignByArray(array('left', 'left', 'center'));
	$columnValues = array($gL10n->get('SYS_LASTNAME'), $gL10n->get('SYS_FIRSTNAME'), $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER_NEW'));
	$table->addRowHeadingByArray($columnValues);
	
	foreach ($_SESSION['pMembershipFee']['membernumber_user'] as $data)
	{
		$columnValues = array();
		$columnValues[] = $data['last_name'];
		$columnValues[] = $data['first_name'];
		$columnValues[] = $data['membernumber'];
		$table->addRowByArray($columnValues);
	}
	$page->addHtml($table->show(false));
}

$page->show();


