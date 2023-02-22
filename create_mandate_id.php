<?php
/**
 ***********************************************************************************************
 * Dieses Plugin erzeugt Mandatsreferenzen.
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

$user = new User($gDb, $gProfileFields);

// set headline of the script
$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_ID');

$gNavigation->addUrl(CURRENT_URL, $headline);

if ($getMode == 'preview')     //Default
{
    $page = new HtmlPage('plg-mitgliedsbeitrag-create-mandate-id-preview', $headline);
    
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
		$members = list_members(array('LAST_NAME', 'FIRST_NAME', 'DEBTOR', 'MANDATEID'.$gCurrentOrgId, 'FEE'.$gCurrentOrgId, 'CONTRIBUTORY_TEXT'.$gCurrentOrgId, 'IBAN', $pPreferences->config['Mandatsreferenz']['data_field']), $rols);
	}
	else
	{
		$members = list_members(array('LAST_NAME', 'FIRST_NAME', 'DEBTOR', 'MANDATEID'.$gCurrentOrgId, 'FEE'.$gCurrentOrgId, 'CONTRIBUTORY_TEXT'.$gCurrentOrgId, 'IBAN'), $rols);
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
	
	if (sizeof($members) > 0)
	{
    	$form = new HtmlForm('createmandateid_preview_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/create_mandate_id.php', array('mode' => 'write')), $page);
        
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
            $user->readDataById($member);
        
			$columnValues = array();
			$columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$data['LAST_NAME'].'</a>';
			$columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$data['FIRST_NAME'].'</a>';
			$columnValues[] = $data['referenz'];
			$table->addRowByArray($columnValues);
		}

		$page->addHtml($table->show(false));
		if (!$errorMarker)
		{
			$form->addSubmitButton('btn_next_page', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check'));
			$form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_ID_PREVIEW'));
		}
        $page->addHtml($form->show(false)); 
	}
	else 
	{
        $page->addHtml($gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_ID_NO_ASSIGN').'<br/><br/>');
	}
}
elseif ($getMode == 'write')
{
    $page = new HtmlPage('plg-mitgliedsbeitrag-create-mandate-id-write', $headline);

    $page->addPageFunctionsMenuItem('menu_item_print_view', $gL10n->get('SYS_PRINT_PREVIEW'), 'javascript:void(0);', 'fa-print');
    
	$page->addJavascript('
    	$("#menu_item_print_view").click(function() {
            window.open("'. SecurityUtils::encodeUrl(ADMIDIO_URL. FOLDER_PLUGINS . PLUGIN_FOLDER .'/create_mandate_id.php', array('mode' => 'print')). '", "_blank");
        });',
		true
	);
	
	$datatable = false;
	$hoverRows = true;
	$classTable  = 'table table-condensed';
    
	$table = new HtmlTable('table_saved_createmandateids', $page, $hoverRows, $datatable, $classTable);
	$table->setColumnAlignByArray(array('left', 'left', 'center'));
	$columnValues = array($gL10n->get('SYS_LASTNAME'), $gL10n->get('SYS_FIRSTNAME'), $gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_ID_NEW'));
	$table->addRowHeadingByArray($columnValues);
	
	foreach ($_SESSION['pMembershipFee']['createmandateid_user'] as $member => $data)
	{
        $user->readDataById($member);
    
		$columnValues = array();
		$columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$data['LAST_NAME'].'</a>';
		$columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$data['FIRST_NAME'].'</a>';
		$columnValues[] = $data['referenz'];
		$table->addRowByArray($columnValues);
		
		$user->setValue('MANDATEID'.$gCurrentOrgId, $data['referenz']);
		$user->save();
	}
	
	$page->addHtml('<div style="width:100%; height: 500px; overflow:auto; border:20px;">');
	$page->addHtml($table->show(false));
	$page->addHtml('</div><br/>');
    $page->addHtml('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_ID_SAVED').'</strong><br/><br/>');
}
elseif ($getMode == 'print')
{
	$hoverRows = false;
	$datatable = false;
	$classTable  = 'table table-condensed table-striped';
    
    $page = new HtmlPage('plg-mitgliedsbeitrag-create-mandate-id-print', $gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_IDS_NEW'));    
	$page->setPrintMode();
    
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


