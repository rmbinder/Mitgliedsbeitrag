<?php
/**
 ***********************************************************************************************
 * Berechnung von Individualbeiträgen
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
 * mode       : preview - preview of the new individual contributions
 *              write   - save the new individual contributions
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
$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_INDIVIDUAL_CONTRIBUTIONS');

$gNavigation->addUrl(CURRENT_URL, $headline);

for ($i = 0; $i < count($pPreferences->config['individual_contributions']['desc']); $i++)
{
    if (($pPreferences->config['individual_contributions']['role'][$i] == 0) || ($pPreferences->config['individual_contributions']['amount'][$i] == ''))
    {
     	$getMode = 'error';
    }      
} 

if ($getMode == 'preview')     //Default
{
    $page = new HtmlPage('plg-mitgliedsbeitrag-individualcontributions-preview', $headline);

	$members = array();
	$message = '';

	// alle aktiven Mitglieder einlesen
	$members = list_members(array('FIRST_NAME', 'LAST_NAME', 'FEE'.$gCurrentOrgId, 'CONTRIBUTORY_TEXT'.$gCurrentOrgId), 0);

 	foreach ($members as $member => $memberdata)
	{
        $members[$member]['FEE_NEW'] = 0;
		$members[$member]['CONTRIBUTORY_TEXT_NEW'] = '';   

 	    $user->readDataById($member);
 	    $user->getRoleMemberships();
        
        for ($i = 0; $i < count($pPreferences->config['individual_contributions']['desc']); $i++)
     	{
     	   
     		if (!$user->isMemberOfRole((int) $pPreferences->config['individual_contributions']['role'][$i]))
     		{
     			 continue;
     		}
            
            $multiplikator = 1;
            if ($pPreferences->config['individual_contributions']['profilefield'][$i] <> '')
     		{
     		    $usfid = $pPreferences->config['individual_contributions']['profilefield'][$i];
     		    $multiplikator = $user->getValue($gProfileFields->getPropertyById((int) $usfid, 'usf_name_intern'));
     		    
     		    //wenn das Profilfeld leer ist, dann wäre $multiplikator = ""; mit "" kann aber nicht multipliziert werden
     		    $multiplikator = ($multiplikator === "") ?  0 : $multiplikator;
     		} 
            
            $amount =   $pPreferences->config['individual_contributions']['amount'][$i] * $multiplikator;
            
            // Einzelbeträge auf 2 Nachkommastellen runden
			$amount = round($amount, 2);
 
			$members[$member]['FEE_NEW'] += $amount;
			if ($pPreferences->config['individual_contributions']['short_desc'][$i] != '')
			{
				$members[$member]['CONTRIBUTORY_TEXT_NEW'] .= ' '.$pPreferences->config['individual_contributions']['short_desc'][$i].':'.$amount.' ';
			}            
		}  

		// Gesamtbetrag auf 2 Nachkommastellen runden
		$members[$member]['FEE_NEW'] = round($members[$member]['FEE_NEW'], 2);
		
		//ggf. abrunden
		if ($pPreferences->config['Beitrag']['beitrag_abrunden'] == true)
		{
		    $members[$member]['FEE_NEW'] = floor($members[$member]['FEE_NEW']);
		}
	
		// letzte Datenaufbereitung
		if ($members[$member]['FEE_NEW'] > $pPreferences->config['Beitrag']['beitrag_mindestbetrag'])
		{
		    $members[$member]['FEE_NEW'] += $members[$member]['FEE'.$gCurrentOrgId];
            $members[$member]['CONTRIBUTORY_TEXT_NEW'] = $members[$member]['CONTRIBUTORY_TEXT'.$gCurrentOrgId].' '.$members[$member]['CONTRIBUTORY_TEXT_NEW'];
		
		    //fuehrende und nachfolgene Leerstellen im Beitragstext loeschen
		    $members[$member]['CONTRIBUTORY_TEXT_NEW'] = trim($members[$member]['CONTRIBUTORY_TEXT_NEW']);
		    //zwei aufeinanderfolgende Leerzeichen durch ein Leerzeichen ersetzen
		    $members[$member]['CONTRIBUTORY_TEXT_NEW'] = str_replace('  ', ' ', $members[$member]['CONTRIBUTORY_TEXT_NEW']);
		}
		else
		{
		    unset($members[$member]);        //wenn kein neuer Beitrag errechnet wurde, dann dieses Mitglied in der Liste loeschen
		}
 	}
	
	if (sizeof($members) > 0)
	{
		// save members in session (for mode write and mode print)
		$_SESSION['pMembershipFee']['individualcontributions_user'] = $members;
	
		$datatable = true;
		$hoverRows = true;
		$classTable  = 'table table-condensed';
		$table = new HtmlTable('table_new_individualcontributions', $page, $hoverRows, $datatable, $classTable);
		$table->setColumnAlignByArray(array('left', 'left', 'center', 'center', 'center','center'));
		$columnValues = array($gL10n->get('SYS_LASTNAME'), 
							  $gL10n->get('SYS_FIRSTNAME'), 
							  $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE_NEW'),
							  $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT_NEW'),
							  $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE_PREVIOUS'),
							  $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT_PREVIOUS'));
		$table->addRowHeadingByArray($columnValues);

		foreach ($members as $member => $data)
		{
         	$user->readDataById($member);
                
			$columnValues = array();
			$columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$data['LAST_NAME'].'</a>';
			$columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$data['FIRST_NAME'].'</a>';
			$columnValues[] = $data['FEE_NEW'];
			$columnValues[] = $data['CONTRIBUTORY_TEXT_NEW'];
			$columnValues[] = $data['FEE'.$gCurrentOrgId];
			$columnValues[] = $data['CONTRIBUTORY_TEXT'.$gCurrentOrgId];
			$table->addRowByArray($columnValues);
		}

		$page->addHtml($table->show(false));

        $form = new HtmlForm('individualcontributions_preview_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/individualcontributions.php', array('mode' => 'write')), $page);
		$form->addSubmitButton('btn_next_page', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => 'btn btn-primary'));
		$form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_INDIVIDUAL_CONTRIBUTIONS_PREVIEW'));
        
        $page->addHtml($form->show(false));
	}
	else 
	{
        $page->addHtml($gL10n->get('PLG_MITGLIEDSBEITRAG_INDIVIDUAL_CONTRIBUTIONS_NO_DATA').'<br/>');
	}
	
	if (!empty($message))
	{
        $page->addHtml($message);
	}
}
elseif ($getMode == 'write')
{
    $page = new HtmlPage('plg-mitgliedsbeitrag-individualcontributions-write', $headline);

 	$page->addPageFunctionsMenuItem('menu_item_print_view', $gL10n->get('SYS_PRINT_PREVIEW'), 'javascript:void(0);', 'fa-print');
        
	$page->addJavascript('
    	$("#menu_item_print_view").click(function() {
            window.open("'. SecurityUtils::encodeUrl(ADMIDIO_URL. FOLDER_PLUGINS . PLUGIN_FOLDER .'/individualcontributions.php', array('mode' => 'print')). '", "_blank");
        });',
		true
	);

	$datatable = true;
	$hoverRows = true;
	$classTable  = 'table table-condensed';
    
	$table = new HtmlTable('table_saved_individualcontributions', $page, $hoverRows, $datatable, $classTable);
	$table->setColumnAlignByArray(array('left', 'left', 'center', 'center'));
	$columnValues = array($gL10n->get('SYS_LASTNAME'),
						  $gL10n->get('SYS_FIRSTNAME'),
						  $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE_NEW'),
						  $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT_NEW'));
	$table->addRowHeadingByArray($columnValues);
	
	foreach ($_SESSION['pMembershipFee']['individualcontributions_user'] as $member => $data)
	{
    	$user->readDataById($member);
            
		$columnValues = array();
		$columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$data['LAST_NAME'].'</a>';
		$columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$data['FIRST_NAME'].'</a>';
		$columnValues[] = $data['FEE_NEW'];
		$columnValues[] = $data['CONTRIBUTORY_TEXT_NEW'];
		$table->addRowByArray($columnValues);
		
		$user->setValue('FEE'.$gCurrentOrgId, $data['FEE_NEW']);
		$user->setValue('CONTRIBUTORY_TEXT'.$gCurrentOrgId, $data['CONTRIBUTORY_TEXT_NEW']);
		$user->save();         
	}

	$page->addHtml($table->show(false));
	$page->addHtml('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_INDIVIDUAL_CONTRIBUTIONS_SAVED').'</strong><br/><br/>');
}
elseif ($getMode == 'print')
{
	// create html page object without the custom theme files
	$hoverRows = false;
	$datatable = false;
	$classTable  = 'table table-condensed table-striped';

	$page = new HtmlPage('plg-mitgliedsbeitrag-individualcontributions-print', $gL10n->get('PLG_MITGLIEDSBEITRAG_INDIVIDUAL_CONTRIBUTIONS_NEW'));
	$page->setPrintMode();
	
	$table = new HtmlTable('table_print_individualcontributions', $page, $hoverRows, $datatable, $classTable);
	$table->setColumnAlignByArray(array('left', 'left', 'center', 'center'));
	$columnValues = array($gL10n->get('SYS_LASTNAME'),
						  $gL10n->get('SYS_FIRSTNAME'),
						  $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE_NEW'),
						  $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT_NEW'));
	$table->addRowHeadingByArray($columnValues);
	
	foreach ($_SESSION['pMembershipFee']['individualcontributions_user'] as $data)
	{
		$columnValues = array();
		$columnValues[] = $data['LAST_NAME'];
		$columnValues[] = $data['FIRST_NAME'];
		$columnValues[] = $data['FEE_NEW'];
		$columnValues[] = $data['CONTRIBUTORY_TEXT_NEW'];
		$table->addRowByArray($columnValues);
	}
	$page->addHtml($table->show(false));
}
else          // $getMode = error
{
    $page = new HtmlPage('plg-mitgliedsbeitrag-individualcontributions-error', $headline);
    $page->addHtml('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_WRONG_INDIVIDUAL_CONTRIBUTION').'</strong><br/><br/>');
}

$page->show();


