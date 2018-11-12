<?php
/**
 ***********************************************************************************************
 * Dieses Plugin generiert fuer aktive Mitglieder der aktuellen Organisation eine Mitgliedsnummer.
 *
 * @copyright 2004-2018 The Admidio Team
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

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

// set headline of the script
$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_PRODUCE_MEMBERNUMBER');

// create html page object
$page = new HtmlPage($headline);

if ($getMode == 'preview')     //Default
{
	$membernumbers = new Membernumbers($gDb);

	if ($membernumbers->isDoubleNumber())
	{
		$gMessage->show($gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER_ERROR', $membernumbers->isDoubleNumber()));
		// --> EXIT
	}

	$membernumbers->readUserWithoutMembernumber($postRoleselection);
	$membernumbers->separateFormatSegment($postFormat);
	$membernumbers->getMembernumber();
	
	$_SESSION['pMembershipFee']['membernumber_rol_sel'] = $postRoleselection;
	$_SESSION['pMembershipFee']['membernumber_format'] = $postFormat;
	
	$headerMenu = $page->getMenu();
	$headerMenu->addItem('menu_item_back', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mitgliedsbeitrag.php?show_option=producemembernumber', $gL10n->get('SYS_BACK'), 'back.png');
	
	$form = new HtmlForm('membernumber_preview_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/membernumber.php?mode=write', $page);
	
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
		$form->addSubmitButton('btn_next_page', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL .'/icons/disk.png', 'class' => 'btn-primary'));
		$form->addDescription('<br/>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER_PREVIEW'));
	}
	else 
	{
		$form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER_NO_ASSIGN'));
		
		//seltsamerweise wird in diesem Abschnitt nichts angezeigt wenn diese Anweisung fehlt
		$form->addStaticControl('', '', '');
	}
	$page->addHtml($form->show(false));
}
elseif ($getMode == 'write')
{
	$page->addJavascript('
    	$("#menu_item_print_view").click(function() {
            window.open("'.ADMIDIO_URL. FOLDER_PLUGINS . PLUGIN_FOLDER .'/membernumber.php?mode=print", "_blank");
        });',
		true
	);
	
	$headerMenu = $page->getMenu();
	$headerMenu->addItem('menu_item_back', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mitgliedsbeitrag.php?show_option=producemembernumber', $gL10n->get('SYS_BACK'), 'back.png');
	$headerMenu->addItem('menu_item_print_view', '#', $gL10n->get('LST_PRINT_PREVIEW'), 'print.png');
	
	$form = new HtmlForm('membernumber_saved_form', null, $page);
	
	$datatable = true;
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
		$user->setValue('MEMBERNUMBER'.ORG_ID, $data['membernumber']);
		$user->save();
	}
	
	$page->addHtml($table->show(false));
	$form->addDescription('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER_SAVED').'</strong>');
	
	//seltsamerweise wird in diesem Abschnitt nichts angezeigt wenn diese Anweisung fehlt
	$form->addStaticControl('', '', '');
	
	$page->addHtml($form->show(false));
	
	// save the format string in database
	$pPreferences->config['membernumber']['format'] = $_SESSION['pMembershipFee']['membernumber_format'];
	$pPreferences->save();
}
elseif ($getMode == 'print')
{
	// create html page object without the custom theme files
	$hoverRows = false;
	$datatable = false;
	$classTable  = 'table table-condensed table-striped';
	$page->hideThemeHtml();
	$page->hideMenu();
	$page->setPrintMode();
	$page->setHeadline($gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBERS_NEW'));
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


