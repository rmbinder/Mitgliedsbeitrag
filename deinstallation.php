<?php
/**
 ***********************************************************************************************
 * Deinstallationsroutine fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * mode         : start  - Startbildschirm anzeigen
 *                delete - Loeschen der Daten
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

// only authorized user are allowed to start this module
if (!$gCurrentUser->isAdministrator())
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Initialize and check the parameters
$getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'start', 'validValues' => array('start', 'delete')));

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION');

$page = new HtmlPage('plg-mitgliedsbeitrag-deinstallation', $headline);
  
if ($getMode == 'start')     //Default
{
    //$gNavigation->addUrl(SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php', array('choice' => 'deinstallation')));
    $gNavigation->addUrl(CURRENT_URL, $headline);
    
    $form = new HtmlForm('deinstallation_start_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/deinstallation.php', array('mode' => 'delete')), $page);

    $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION_DESC'));
    $html = '<div class="alert alert-warning alert-small" role="alert"><i class="fas fa-exclamation-triangle"></i>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION_FORM_DESC').'</div>';
    $form->addDescription($html);

    $form->openGroupBox('orgchoice', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_ORG_CHOICE'));
    $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_ORG_CHOICE_DESC'));
    $radioButtonEntries = array('0' => $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINST_ACTORGONLY'), '1' => $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINST_ALLORG'));
    $form->addRadioButton('deinst_org_select', '', $radioButtonEntries);
    $form->closeGroupBox();

    $form->openGroupBox('configdata', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_CONFIGURATION_DATA'));
    $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_CONFIGURATION_DATA_DESC'));
    $html = '<div class="alert alert-warning alert-small" role="alert"><i class="fas fa-exclamation-triangle"></i>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONFIGURATION_DATA_ALERT_DESC').'</div>';
    $form->addDescription($html);
    $form->addCheckbox('configurationdata', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONFIGURATION_DATA'), 0);
    $form->closeGroupBox();

    $form->openGroupBox('memberdata', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBER_DATA'));
    $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBER_DATA_DESC'));
    $html = '<div class="alert alert-warning alert-small" role="alert"><i class="fas fa-exclamation-triangle"></i>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBER_DATA_ALERT_DESC').'</div>';
    $form->addDescription($html);

    $form->openGroupBox('accountdata', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_DATA'));
    $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE_IN_ALL_ORGS'));
    $form->addCheckbox('accountholder', $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_HOLDER'), 0);
    $form->addCheckbox('iban', $gL10n->get('PLG_MITGLIEDSBEITRAG_IBAN'), 0);
    $form->addCheckbox('bic', $gL10n->get('PLG_MITGLIEDSBEITRAG_BIC'), 0);
    $form->addCheckbox('bank', $gL10n->get('PLG_MITGLIEDSBEITRAG_BANK'), 0);
    $form->addCheckbox('street', $gL10n->get('PLG_MITGLIEDSBEITRAG_STREET'), 0);
    $form->addCheckbox('postcode', $gL10n->get('PLG_MITGLIEDSBEITRAG_POSTCODE'), 0);
    $form->addCheckbox('city', $gL10n->get('PLG_MITGLIEDSBEITRAG_CITY'), 0);
    $form->addCheckbox('origdebtoragent', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_DEBTOR_AGENT'), 0);
    $form->addCheckbox('origiban', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_IBAN'), 0);
    $form->addCheckbox('email', $gL10n->get('PLG_MITGLIEDSBEITRAG_EMAIL'), 0);
    $form->closeGroupBox();
    $form->addLine();
    $form->openGroupBox('membership', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP'));
    $form->addCheckbox('membernumber', $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER'), 0);
    $form->addCheckbox('accession', $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCESSION'), 0);
    $form->closeGroupBox();
    $form->addLine();
    $form->openGroupBox('membershipfee', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP_FEE'));
    $form->addCheckbox('paid', $gL10n->get('PLG_MITGLIEDSBEITRAG_PAID'), 0);
    $form->addCheckbox('fee', $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE'), 0);
    $form->addCheckbox('contributorytext', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT'), 0);
    $form->addCheckbox('sequencetype', $gL10n->get('PLG_MITGLIEDSBEITRAG_SEQUENCETYPE'), 0);
    $form->addCheckbox('duedate', $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE'), 0);
    $form->closeGroupBox();
    $form->addLine();
    $form->openGroupBox('mandate', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE'));
    $form->addCheckbox('mandateid', $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEID'), 0);
    $form->addCheckbox('mandatedate', $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEDATE'), 0);
    $form->addCheckbox('orig_mandateid', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_MANDATEID'), 0);
    $form->closeGroupBox();
    $form->addLine();
    $form->openGroupBox('others', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_OTHERS'));
    $form->addCheckbox('mailtexts', $gL10n->get('PLG_MITGLIEDSBEITRAG_MAIL_TEXTS'), 0);
    $form->closeGroupBox();
    $form->closeGroupBox();

    $form->addSubmitButton('btn_deinstall', $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION'), array('icon' => 'fa-trash-alt', 'class' => 'btn-primary'));
}
elseif ($getMode == 'delete')
{
    $deinst_config_data_message = '';
    if (isset($_POST['configurationdata']))
    {
        $deinst_config_data_message = $pPreferences->delete_config_data($_POST['deinst_org_select']);
    }

    $deinst_member_data_message = '';
    if (isset($_POST['accountholder']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data(3, 'DEBTOR', $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_HOLDER'));
    }
    if (isset($_POST['iban']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data(3, 'IBAN', $gL10n->get('PLG_MITGLIEDSBEITRAG_IBAN'));
    }
    if (isset($_POST['bic']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data(3, 'BIC', $gL10n->get('PLG_MITGLIEDSBEITRAG_BIC'));
    }
    if (isset($_POST['bank']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data(3, 'BANK', $gL10n->get('PLG_MITGLIEDSBEITRAG_BANK'));
    }
    if (isset($_POST['street']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data(3, 'DEBTOR_STREET', $gL10n->get('PLG_MITGLIEDSBEITRAG_STREET'));
    }
    if (isset($_POST['postcode']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data(3, 'DEBTOR_POSTCODE', $gL10n->get('PLG_MITGLIEDSBEITRAG_POSTCODE'));
    }
    if (isset($_POST['city']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data(3, 'DEBTOR_CITY', $gL10n->get('PLG_MITGLIEDSBEITRAG_CITY'));
    }
    if (isset($_POST['origdebtoragent']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data(3, 'ORIG_DEBTOR_AGENT', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_DEBTOR_AGENT'));
    }
    if (isset($_POST['origiban']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data(3, 'ORIG_IBAN', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_IBAN'));
    }
    if (isset($_POST['email']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data(3, 'DEBTOR_EMAIL', $gL10n->get('PLG_MITGLIEDSBEITRAG_EMAIL'));
    }
    if (isset($_POST['membernumber']))
    {
    	$deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'], 'MEMBERNUMBER', $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER'));
    }
    if (isset($_POST['accession']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'], 'ACCESSION', $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCESSION'));
    }
    if (isset($_POST['paid']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'], 'PAID', $gL10n->get('PLG_MITGLIEDSBEITRAG_PAID'));
    }
    if (isset($_POST['fee']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'], 'FEE', $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE'));
    }
    if (isset($_POST['contributorytext']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'], 'CONTRIBUTORY_TEXT', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT'));
    }
    if (isset($_POST['sequencetype']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'], 'SEQUENCETYPE', $gL10n->get('PLG_MITGLIEDSBEITRAG_SEQUENCETYPE'));
    }
    if (isset($_POST['duedate']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'], 'DUEDATE', $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE'));
    }
    if (isset($_POST['mandateid']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'], 'MANDATEID', $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEID'));
    }
    if (isset($_POST['mandatedate']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'], 'MANDATEDATE', $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEDATE'));
    }
    if (isset($_POST['orig_mandateid']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data($_POST['deinst_org_select'], 'ORIG_MANDATEID', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_MANDATEID'));
    }
    if (isset($_POST['mailtexts']))
    {
        $deinst_member_data_message .= $pPreferences->delete_mail_data($_POST['deinst_org_select']);
    }

    $deinstMessage = $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINST_STARTMESSAGE');
    if ($deinst_config_data_message != '')
    {
        $deinstMessage .= '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONFIGURATION_DATA').'</strong><br/>';
        $deinstMessage .= $deinst_config_data_message;
    }
    if ($deinst_member_data_message != '')
    {
        $deinstMessage .= '<br/><strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBER_DATA').'</strong>';
        $deinstMessage .= $deinst_member_data_message;
    }
    
    if ($deinstMessage != $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINST_STARTMESSAGE'))
    {
        $page->addHtml($deinstMessage);
        $page->addHtml('<div class="alert alert-warning alert-small" role="alert"><i class="fas fa-exclamation-triangle"></i>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DEINST_ENDMESSAGE').'</div>');
       
        $_SESSION['pMembershipFee']['deinst'] = true;
    }
    else
    {
        $page->addHtml($gL10n->get('PLG_MITGLIEDSBEITRAG_DEINST_NO_SELECTED_DATA'));
    }
    
    $form = new HtmlForm('deinstallation_delete_form', null, $page);
    $gNavigation->clear();
    $form->addButton('next_page', $gL10n->get('SYS_NEXT'), array('icon' => 'fa-arrow-circle-right', 'link' => $gHomepage, 'class' => 'btn-primary'));
}

$page->addHtml($form->show(false));
$page->show();
