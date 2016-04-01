<?php
/******************************************************************************
 * deinstallation.php
 *   
 * Deinstallationsroutine fuer das Admidio-Plugin Mitgliedsbeitrag
 * 
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.result
 *
 * Parameters:
 *
 * mode         - start 	: Startbildschirm anzeigen
 *                delete 	: Löschen der Daten
 * 			     
 *****************************************************************************/

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');
require_once($plugin_path. '/'.$plugin_folder.'/common_function.php');
require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php'); 

// Initialize and check the parameters
$getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'start', 'validValues' => array('start','delete')));

$pPreferences = new ConfigTablePMB;

$headline = $gL10n->get('PMB_DEINSTALLATION');

// create html page object
$page = new HtmlPage($headline);
	
if($getMode == 'start' )     //Default
{
	// get module menu
    $headerMenu = $page->getMenu();
    $headerMenu->addItem('menu_item_back', $g_root_path.'/adm_plugins/'.$plugin_folder.'/preferences.php?choice=deinstallation', $gL10n->get('SYS_BACK'), 'back.png');

    $form = new HtmlForm('deinstallations_form', $g_root_path.'/adm_plugins/'.$plugin_folder.'/deinstallation.php?mode=delete', $page); 
	
    $form->addDescription($gL10n->get('PMB_DEINSTALLATION_DESC'));
    $html = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('PMB_DEINSTALLATION_FORM_DESC').'</div>';
    $form->addDescription($html);

    $form->openGroupBox('orgchoice', $headline = $gL10n->get('PMB_ORG_CHOICE'));
    $form->addDescription($gL10n->get('PMB_ORG_CHOICE_DESC'));
    $radioButtonEntries = array('0' => $gL10n->get('PMB_DEINST_ACTORGONLY'), '1' => $gL10n->get('PMB_DEINST_ALLORG') );
    $form->addRadioButton('deinst_org_select','',$radioButtonEntries);                    	
    $form->closeGroupBox(); 
     
    $form->openGroupBox('configdata', $headline = $gL10n->get('PMB_CONFIGURATION_DATA'));
    $form->addDescription($gL10n->get('PMB_CONFIGURATION_DATA_DESC'));
    $html = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('PMB_CONFIGURATION_DATA_ALERT_DESC').'</div>';	
    $form->addDescription($html);
    $form->addCheckbox('configurationdata', $gL10n->get('PMB_CONFIGURATION_DATA'), 0  ); 
    $form->closeGroupBox(); 
    
    $form->openGroupBox('memberdata', $headline = $gL10n->get('PMB_MEMBER_DATA'));
    $form->addDescription($gL10n->get('PMB_MEMBER_DATA_DESC'));
    $html = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('PMB_MEMBER_DATA_ALERT_DESC').'</div>';	
    $form->addDescription($html);
                   
    $form->openGroupBox('masterdata', $headline = $gL10n->get('SYS_MASTER_DATA'));
    $form->addDescription($gL10n->get('PMB_DELETE_IN_ALL_ORGS'));
    $form->addCheckbox('membernumber', $gL10n->get('PMB_MEMBERNUMBER'), 0  );                      	
    $form->closeGroupBox(); 
    
    $form->openGroupBox('accountdata', $headline = $gL10n->get('PMB_ACCOUNT_DATA'));
    $form->addDescription($gL10n->get('PMB_DELETE_IN_ALL_ORGS'));
    $form->addCheckbox('accountnumber', $gL10n->get('PMB_ACCOUNT_NUMBER'), 0  );
    $form->addCheckbox('bankcodenumber', $gL10n->get('PMB_BANK_CODE_NUMBER'), 0  );
    $form->addCheckbox('accountholder', $gL10n->get('PMB_ACCOUNT_HOLDER'), 0  );
    $form->addCheckbox('iban', $gL10n->get('PMB_IBAN'), 0  );
    $form->addCheckbox('bic', $gL10n->get('PMB_BIC'), 0  );
    $form->addCheckbox('bank', $gL10n->get('PMB_BANK'), 0  );
    $form->addCheckbox('address', $gL10n->get('PMB_ADDRESS'), 0  );
    $form->addCheckbox('postcode', $gL10n->get('PMB_POSTCODE'), 0  );
    $form->addCheckbox('city', $gL10n->get('PMB_CITY'), 0  );
    $form->addCheckbox('origdebtoragent', $gL10n->get('PMB_ORIG_DEBTOR_AGENT'), 0  );
    $form->addCheckbox('origiban', $gL10n->get('PMB_ORIG_IBAN'), 0  );
    $form->addCheckbox('email', $gL10n->get('PMB_EMAIL'), 0  );                        	
    $form->closeGroupBox(); 
                  	
    $form->openGroupBox('membership', $headline = $gL10n->get('PMB_MEMBERSHIP'));
    $form->addCheckbox('accession', $gL10n->get('PMB_ACCESSION'), 0  );                      	
    $form->closeGroupBox(); 
    
    $form->openGroupBox('membershipfee', $headline = $gL10n->get('PMB_MEMBERSHIP_FEE'));
    $form->addCheckbox('paid', $gL10n->get('PMB_PAID'), 0  );  
    $form->addCheckbox('fee', $gL10n->get('PMB_FEE'), 0  );   
    $form->addCheckbox('contributorytext', $gL10n->get('PMB_CONTRIBUTORY_TEXT'), 0  ); 
    $form->addCheckbox('sequencetype', $gL10n->get('PMB_SEQUENCETYPE'), 0  ); 
    $form->addCheckbox('duedate', $gL10n->get('PMB_DUEDATE'), 0  );                     	
    $form->closeGroupBox(); 					
    
	$form->openGroupBox('mandate', $headline = $gL10n->get('PMB_MANDATE'));
    $form->addCheckbox('mandateid', $gL10n->get('PMB_MANDATEID'), 0  );  
    $form->addCheckbox('mandatedate', $gL10n->get('PMB_MANDATEDATE'), 0  );   
    $form->addCheckbox('orig_mandateid', $gL10n->get('PMB_ORIG_MANDATEID'), 0  );                     	
    $form->closeGroupBox();  

    $form->openGroupBox('others', $headline = $gL10n->get('PMB_OTHERS'));
    $form->addCheckbox('mailtexts', $gL10n->get('PMB_MAIL_TEXTS'), 0  );  
    $form->closeGroupBox(); 
    $form->closeGroupBox(); 

    $form->addSubmitButton('btn_deinstall', $gL10n->get('PMB_DEINSTALLATION'), array('icon' => THEME_PATH.'/icons/delete.png', 'class' => ' col-sm-offset-3')); 
}
elseif($getMode == 'delete')
{
	$deinst_config_data_message='';
	if(isset($_POST['configurationdata']))
	{
	 	$deinst_config_data_message = $pPreferences->delete_config_data($_POST['deinst_org_select']);
	}
	
	$deinst_member_data_message=''; 
  	if (isset($_POST['membernumber']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data(3,'MEMBERNUMBER',$gL10n->get('PMB_MEMBERNUMBER'));
	}
  	if (isset($_POST['accountnumber']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data(3,'KONTONUMMER',$gL10n->get('PMB_ACCOUNT_NUMBER'));
	}
  	if (isset($_POST['bankcodenumber']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data(3,'BANKLEITZAHL',$gL10n->get('PMB_BANK_CODE_NUMBER'));
	}
    if (isset($_POST['accountholder']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data(3,'KONTOINHABER',$gL10n->get('PMB_ACCOUNT_HOLDER'));
	}			
    if (isset($_POST['iban']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data(3,'IBAN',$gL10n->get('PMB_IBAN'));
	}	
	if (isset($_POST['bic']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data(3,'BIC',$gL10n->get('PMB_BIC'));
	}			
  	if (isset($_POST['bank']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data(3,'BANKNAME',$gL10n->get('PMB_BANK'));
	}			
  	if (isset($_POST['address']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data(3,'DEBTORADDRESS',$gL10n->get('PMB_ADDRESS'));
	}
  	if (isset($_POST['postcode']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data(3,'DEBTORPOSTCODE',$gL10n->get('PMB_POSTCODE'));
	}
  	if (isset($_POST['city']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data(3,'DEBTORCITY',$gL10n->get('PMB_CITY'));
	}		
    if (isset($_POST['origdebtoragent']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data(3,'ORIGDEBTORAGENT',$gL10n->get('PMB_ORIG_DEBTOR_AGENT'));
	}
  	if (isset($_POST['origiban']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data(3,'ORIGIBAN',$gL10n->get('PMB_ORIG_IBAN'));
	}
    if (isset($_POST['email']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data(3,'DEBTOREMAIL',$gL10n->get('PMB_EMAIL'));
	}
  	if (isset($_POST['accession']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'],'BEITRITT',$gL10n->get('PMB_ACCESSION'));
	}
  	if (isset($_POST['paid']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'],'BEZAHLT',$gL10n->get('PMB_PAID'));
	}
  	if (isset($_POST['fee']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'],'BEITRAG',$gL10n->get('PMB_FEE'));
	}
  	if (isset($_POST['contributorytext']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'],'BEITRAGSTEXT',$gL10n->get('PMB_CONTRIBUTORY_TEXT'));
	}
  	if (isset($_POST['sequencetype']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'],'SEQUENCETYPE',$gL10n->get('PMB_SEQUENCETYPE'));
	}
  	if (isset($_POST['duedate']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'],'DUEDATE',$gL10n->get('PMB_DUEDATE'));
	}		
	if (isset($_POST['mandateid']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'],'MANDATEID',$gL10n->get('PMB_MANDATEID'));
	}
 	if (isset($_POST['mandatedate']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'],'MANDATEDATE',$gL10n->get('PMB_MANDATEDATE'));
	}
  	if (isset($_POST['orig_mandateid']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'],'ORIGMANDATEID',$gL10n->get('PMB_ORIG_MANDATEID'));
	}
  	if (isset($_POST['mailtexts']))
	{
	 	$deinst_member_data_message .= $pPreferences->delete_mail_data($_POST['deinst_org_select']);
	}
	
	$deinstMessage = $gL10n->get('PMB_DEINST_STARTMESSAGE');
	if($deinst_config_data_message<>'')
	{
		$deinstMessage .= '<strong>'.$gL10n->get('PMB_CONFIGURATION_DATA').'</strong><BR>';
		$deinstMessage .= $deinst_config_data_message;
	}
  	if($deinst_member_data_message<>'')
	{
		$deinstMessage .= '<BR><strong>'.$gL10n->get('PMB_MEMBER_DATA').'</strong>';
		$deinstMessage .= $deinst_member_data_message;
	}

	$form = new HtmlForm('deinstallations_form', null, $page); 
  	if($deinstMessage <> $gL10n->get('PMB_DEINST_STARTMESSAGE'))
	{
		$form->addDescription($deinstMessage);
		$html = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('PMB_DEINST_ENDMESSAGE').'</div>';
    	$form->addDescription($html);
    	
    	//seltsamerweise wird in diesem Abschnitt nichts angezeigt wenn diese Anweisung fehlt 
        $form->addStaticControl('','','');
        
		$_SESSION['pmbDeinst'] = true;
	}
	else 
	{
		$form->addDescription($gL10n->get('PMB_DEINST_NO_SELECTED_DATA'));
		$form->addButton('next_page', $gL10n->get('SYS_NEXT'), array('icon' => THEME_PATH.'/icons/forward.png', 'link' => $gHomepage, 'class' => 'btn-primary'));
	}
}
$page->addHtml($form->show(false));
$page->show();

?>