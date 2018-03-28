<?php
/**
 ***********************************************************************************************
 * Dieses Plugin erzeugt Mandatsreferenzen.
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
 * mode       : preview - preview of the new mandate ids
 *              write   - save the new mandate ids
 *              print   - preview for printing  
 *
 *****************************************************************************/

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

// only authorized user are allowed to start this module
if (!isUserAuthorized($_SESSION['pMembershipFee']['script_name']))
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Initialize and check the parameters
$getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'preview', 'validValues' => array('preview', 'write', 'print')));

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

// set headline of the script
$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_ID');

// create html page object
$page = new HtmlPage($headline);

if ($getMode == 'preview')     //Default
{
	$referenz = '';
	$errorMarker = false;
	$members = array();
	
	if (isset($_POST['createmandateid_roleselection']) )				
	{	
		$_SESSION['pMembershipFee']['createmandateid_rol_sel'] = $_POST['createmandateid_roleselection'];
		
		// Rollenwahl ist vorhanden, deshalb Daten aufbereiten fuer list_members
		$rols = array();
		$role = new TableRoles($gDb);
		foreach ($_POST['createmandateid_roleselection'] as $rol_id)
		{
			$role->readDataById($rol_id);
			$rols[$role->getValue('rol_name')] = 0;
		}
	}
	else 
	{
		$rols = 0;
		unset($_SESSION['pMembershipFee']['createmandateid_rol_sel']);
	}
	
	if ($pPreferences->config['Mandatsreferenz']['data_field'] != '-- User_ID --')
	{
		$members = list_members(array('LAST_NAME', 'FIRST_NAME', 'DEBTOR', 'MANDATEID'.$gCurrentOrganization->getValue('org_id'), 'FEE'.$gCurrentOrganization->getValue('org_id'), 'CONTRIBUTORY_TEXT'.$gCurrentOrganization->getValue('org_id'), 'IBAN', $pPreferences->config['Mandatsreferenz']['data_field']), $rols);
	}
	else
	{
		$members = list_members(array('LAST_NAME', 'FIRST_NAME', 'DEBTOR', 'MANDATEID'.$gCurrentOrganization->getValue('org_id'), 'FEE'.$gCurrentOrganization->getValue('org_id'), 'CONTRIBUTORY_TEXT'.$gCurrentOrganization->getValue('org_id'), 'IBAN'), $rols);
	}
	
	//alle Mitglieder loeschen, bei denen keine IBAN vorhanden ist
	$members = array_filter($members, 'delete_without_IBAN');
	
	//alle Mitglieder loeschen, bei denen bereits eine Mandatsreferenz vorhanden ist
	$members = array_filter($members, 'delete_with_MANDATEID');
	
	//alle Beitragsrollen einlesen
	$contributingRolls = beitragsrollen_einlesen('fam', array('FIRST_NAME', 'LAST_NAME'));
	
	//alle uebriggebliebenen Mitglieder durchlaufen und eine Mandatsreferenz erzeugen
	foreach ($members as $member => $memberdata)
	{
		$prefix = $pPreferences->config['Mandatsreferenz']['prefix_mem'];
	
		//wenn 'DEBTOR' nicht leer ist, dann gibt es einen Zahlungspflichtigen
		if ($memberdata['DEBTOR'] != '')
		{
			$prefix = $pPreferences->config['Mandatsreferenz']['prefix_pay'];
		}
	
		foreach ($contributingRolls as $role)
		{
			if (array_key_exists($member, $role['members']))
			{
				$prefix = $pPreferences->config['Mandatsreferenz']['prefix_fam'];
				break;
			}
		}
		
		if ($pPreferences->config['Mandatsreferenz']['data_field'] != '-- User_ID --')
		{
			$suffix = str_replace(' ', '', replace_sepadaten($memberdata[$pPreferences->config['Mandatsreferenz']['data_field']]));
		}
		else
		{
			$suffix = $member;
		}
	
		$referenz = substr(str_pad($prefix, $pPreferences->config['Mandatsreferenz']['min_length']-strlen($suffix), '0').$suffix, 0, 35);
	
		if (!empty($suffix))
		{
			$members[$member]['referenz'] = $referenz;
		}
		else 
		{
			$members[$member]['referenz'] = $gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_ID_ERROR');
			$errorMarker = true;
		}
	}	
	
	$headerMenu = $page->getMenu();
	$headerMenu->addItem('menu_item_back', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mitgliedsbeitrag.php?show_option=createmandateid', $gL10n->get('SYS_BACK'), 'back.png');
	
	$form = new HtmlForm('createmandateid_preview_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/create_mandate_id.php?mode=write', $page);
	
	if (sizeof($members) > 0)
	{
		// save members with new mandate id in session (for mode write and mode print)
		$_SESSION['pMembershipFee']['createmandateid_user'] = $members;
	
		$datatable = true;
		$hoverRows = true;
		$classTable  = 'table table-condensed';
		$table = new HtmlTable('table_new_createmandateids', $page, $hoverRows, $datatable, $classTable);
		$table->setColumnAlignByArray(array('left', 'left', 'center'));
		$columnValues = array($gL10n->get('SYS_LASTNAME'), $gL10n->get('SYS_FIRSTNAME'), $gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_ID_NEW'));
		$table->addRowHeadingByArray($columnValues);

		foreach ($members as $member => $data)
		{
			$columnValues = array();
			$columnValues[] = '<a href="'.ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php?user_id='.$member.'">'.$data['LAST_NAME'].'</a>';
			$columnValues[] = '<a href="'.ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php?user_id='.$member.'">'.$data['FIRST_NAME'].'</a>';
			$columnValues[] = $data['referenz'];
			$table->addRowByArray($columnValues);
		}

		$page->addHtml($table->show(false));
		if (!$errorMarker)
		{
			$form->addSubmitButton('btn_next_page', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL .'/icons/disk.png', 'class' => 'btn-primary'));
			$form->addDescription('<br/>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_ID_PREVIEW'));
		}
	}
	else 
	{
		$form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_ID_NO_ASSIGN'));
		
		//seltsamerweise wird in diesem Abschnitt nichts angezeigt wenn diese Anweisung fehlt
		$form->addStaticControl('', '', '');
	}
	$page->addHtml($form->show(false));
}
elseif ($getMode == 'write')
{
	$page->addJavascript('
    	$("#menu_item_print_view").click(function() {
            window.open("'.ADMIDIO_URL. FOLDER_PLUGINS . PLUGIN_FOLDER .'/create_mandate_id.php?mode=print", "_blank");
        });',
		true
	);
	
	$headerMenu = $page->getMenu();
	$headerMenu->addItem('menu_item_back', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mitgliedsbeitrag.php?show_option=createmandateid', $gL10n->get('SYS_BACK'), 'back.png');
	$headerMenu->addItem('menu_item_print_view', '#', $gL10n->get('LST_PRINT_PREVIEW'), 'print.png');
	
	$form = new HtmlForm('createmandateid_saved_form', null, $page);
	
	$datatable = true;
	$hoverRows = true;
	$classTable  = 'table table-condensed';
	$table = new HtmlTable('table_saved_createmandateids', $page, $hoverRows, $datatable, $classTable);
	$table->setColumnAlignByArray(array('left', 'left', 'center'));
	$columnValues = array($gL10n->get('SYS_LASTNAME'), $gL10n->get('SYS_FIRSTNAME'), $gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_ID_NEW'));
	$table->addRowHeadingByArray($columnValues);
	
	$user = new User($gDb, $gProfileFields);
	
	foreach ($_SESSION['pMembershipFee']['createmandateid_user'] as $member => $data)
	{
		$columnValues = array();
		$columnValues[] = '<a href="'.ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php?user_id='.$member.'">'.$data['LAST_NAME'].'</a>';
		$columnValues[] = '<a href="'.ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php?user_id='.$member.'">'.$data['FIRST_NAME'].'</a>';
		$columnValues[] = $data['referenz'];
		$table->addRowByArray($columnValues);
		
		$user->readDataById($member);
		$user->setValue('MANDATEID'.$gCurrentOrganization->getValue('org_id'), $data['referenz']);
		$user->save();
	}
	
	$page->addHtml($table->show(false));
	$form->addDescription('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_ID_SAVED').'</strong>');
	
	//seltsamerweise wird in diesem Abschnitt nichts angezeigt wenn diese Anweisung fehlt
	$form->addStaticControl('', '', '');
	
	$page->addHtml($form->show(false));
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
	$page->setHeadline($gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_IDS_NEW'));
	$table = new HtmlTable('table_print_createmandateids', $page, $hoverRows, $datatable, $classTable);
	$table->setColumnAlignByArray(array('left', 'left', 'center'));
	$columnValues = array($gL10n->get('SYS_LASTNAME'), $gL10n->get('SYS_FIRSTNAME'), $gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_ID_NEW'));
	$table->addRowHeadingByArray($columnValues);
	
	foreach ($_SESSION['pMembershipFee']['createmandateid_user'] as $data)
	{
		$columnValues = array();
		$columnValues[] = $data['LAST_NAME'];
		$columnValues[] = $data['FIRST_NAME'];
		$columnValues[] = $data['referenz'];
		$table->addRowByArray($columnValues);
	}
	$page->addHtml($table->show(false));
}

$page->show();


