<?php
/**
 ***********************************************************************************************
 * Dieses Modul erzeugt Mandatsreferenzen
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:             keine
 *
 ***********************************************************************************************
 */
 
// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');
require_once($plugin_path. '/'.$plugin_folder.'/common_function.php');
require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php');

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

// only authorized user are allowed to start this module
if(!check_showpluginPMB($pPreferences->config['Pluginfreigabe']['freigabe']))
{
	$gMessage->setForwardUrl($gHomepage, 3000);
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$referenz = '';
$message = '';
$members = array();

if($pPreferences->config['Mandatsreferenz']['data_field']<>'-- User_ID --')
{
	$members = list_members(array('LAST_NAME', 'FIRST_NAME', 'DEBTOR', 'MANDATEID'.$gCurrentOrganization->getValue('org_id'), 'FEE'.$gCurrentOrganization->getValue('org_id'), 'CONTRIBUTORY_TEXT'.$gCurrentOrganization->getValue('org_id'), 'IBAN', $pPreferences->config['Mandatsreferenz']['data_field']), 0);
}
else
{
	$members = list_members(array('LAST_NAME', 'FIRST_NAME', 'DEBTOR', 'MANDATEID'.$gCurrentOrganization->getValue('org_id'), 'FEE'.$gCurrentOrganization->getValue('org_id'), 'CONTRIBUTORY_TEXT'.$gCurrentOrganization->getValue('org_id'), 'IBAN'), 0);
}

//alle Mitglieder löschen, bei denen kein Beitrag berechnet wurde
$members = array_filter($members, 'delete_without_BEITRAG');
                            
//alle Mitglieder löschen, bei denen keine IBAN vorhanden ist
$members = array_filter($members, 'delete_without_IBAN');
	
//alle Mitglieder löschen, bei denen bereits eine Mandatsreferenz vorhanden ist
$members = array_filter($members, 'delete_with_MANDATEID');
	
//alle übriggebliebenen Mitglieder durchlaufen und eine Mandatsreferenz erzeugen
foreach ($members as $member => $memberdata)
{
	$prefix = $pPreferences->config['Mandatsreferenz']['prefix_mem'];
		
	//wenn 'DEBTOR' nicht leer ist, dann gibt es einen Zahlungspflichtigen
	if($memberdata['DEBTOR']<>'')
	{
		$prefix = $pPreferences->config['Mandatsreferenz']['prefix_pay'];
	}
		
	foreach ($pPreferences->config['Familienrollen']['familienrollen_beschreibung'] as $famrolbesch )
	{
		if(substr_count($memberdata['CONTRIBUTORY_TEXT'.$gCurrentOrganization->getValue('org_id')], $famrolbesch)==1)
		{
			$prefix = $pPreferences->config['Mandatsreferenz']['prefix_fam'];
		}
	}
	if($pPreferences->config['Mandatsreferenz']['data_field']<>'-- User_ID --')
	{
		$suffix = str_replace(' ', '', replace_sepadaten($memberdata[$pPreferences->config['Mandatsreferenz']['data_field']]));
	}
	else
	{
		$suffix = $member;
	}
	
    $referenz = substr(str_pad($prefix, $pPreferences->config['Mandatsreferenz']['min_length']-strlen($suffix), '0').$suffix, 0, 35);
    
    //überprüfen, ob die lfd. Nummer (=$suffix) auch befüllt ist
    //u. U. wurde ein leeres Datenbankfeld ausgewählt; 
    //dabei würden dann Mandatsreferenzen mit endenden Nullen erzeugt
	if(!empty($suffix))
	{
        $user = new User($gDb, $gProfileFields, $member);
        $user->setValue('MANDATEID'.$gCurrentOrganization->getValue('org_id'), $referenz);
        $user->save();
        $message .= $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEID_RES1', $members[$member]['FIRST_NAME'], $members[$member]['LAST_NAME'], $referenz);
    }
    else
    {
        $message .= '<strong>'.$gL10n->get('SYS_ERROR').':</strong> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEID_RES3', $members[$member]['FIRST_NAME'], $members[$member]['LAST_NAME'], $referenz);
    }
}

// set headline of the script
$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_GENERATE');

// create html page object
$page = new HtmlPage($headline);

$form = new HtmlForm('mandateid_form', null, $page);

// Message ausgeben (wenn keinem Mitglied eine Mitgliedsnummer zugewiesen wurde, dann ist die Variable leer)
if ($message == '')
{
    $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEID_RES2'));
}
else
{
    $form->addDescription($message);
}

$form->addButton('next_page', $gL10n->get('SYS_NEXT'), array('icon' => THEME_PATH.'/icons/forward.png', 'link' => 'menue.php?show_option=mandategenerate', 'class' => 'btn-primary'));

$page->addHtml($form->show(false));
$page->show();
