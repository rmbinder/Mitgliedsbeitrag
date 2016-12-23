<?php
/**
 ***********************************************************************************************
 * Deinstallationsroutine fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
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

// Initialize and check the parameters
$getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'start', 'validValues' => array('start', 'delete')));

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

// only authorized user are allowed to start this module
if(!check_showpluginPMB($pPreferences->config['Pluginfreigabe']['freigabe_config']))
{
    $gMessage->setForwardUrl($gHomepage, 3000);
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION');

// create html page object
$page = new HtmlPage($headline);

if($getMode == 'start')     //Default
{
    // get module menu
    $headerMenu = $page->getMenu();
    $headerMenu->addItem('menu_item_back', ADMIDIO_URL . FOLDER_PLUGINS . $plugin_folder .'/preferences.php?choice=deinstallation', $gL10n->get('SYS_BACK'), 'back.png');

    $form = new HtmlForm('deinstallations_form', ADMIDIO_URL . FOLDER_PLUGINS . $plugin_folder .'/deinstallation.php?mode=delete', $page);

    $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION_DESC'));
    $html = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION_FORM_DESC').'</div>';
    $form->addDescription($html);

    $form->openGroupBox('orgchoice', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_ORG_CHOICE'));
    $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_ORG_CHOICE_DESC'));
    $radioButtonEntries = array('0' => $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINST_ACTORGONLY'), '1' => $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINST_ALLORG'));
    $form->addRadioButton('deinst_org_select', '', $radioButtonEntries);
    $form->closeGroupBox();

    $form->openGroupBox('configdata', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_CONFIGURATION_DATA'));
    $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_CONFIGURATION_DATA_DESC'));
    $html = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONFIGURATION_DATA_ALERT_DESC').'</div>';
    $form->addDescription($html);
    $form->addCheckbox('configurationdata', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONFIGURATION_DATA'), 0);
    $form->closeGroupBox();

    $form->openGroupBox('memberdata', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBER_DATA'));
    $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBER_DATA_DESC'));
    $html = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBER_DATA_ALERT_DESC').'</div>';
    $form->addDescription($html);

    $form->openGroupBox('masterdata', $headline = $gL10n->get('SYS_MASTER_DATA'));
    $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE_IN_ALL_ORGS'));
    $form->addCheckbox('membernumber', $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER'), 0);
    $form->closeGroupBox();

    $form->openGroupBox('accountdata', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_DATA'));
    $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE_IN_ALL_ORGS'));
    $form->addCheckbox('accountholder', $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_HOLDER'), 0);
    $form->addCheckbox('iban', $gL10n->get('PLG_MITGLIEDSBEITRAG_IBAN'), 0);
    $form->addCheckbox('bic', $gL10n->get('PLG_MITGLIEDSBEITRAG_BIC'), 0);
    $form->addCheckbox('bank', $gL10n->get('PLG_MITGLIEDSBEITRAG_BANK'), 0);
    $form->addCheckbox('address', $gL10n->get('PLG_MITGLIEDSBEITRAG_ADDRESS'), 0);
    $form->addCheckbox('postcode', $gL10n->get('PLG_MITGLIEDSBEITRAG_POSTCODE'), 0);
    $form->addCheckbox('city', $gL10n->get('PLG_MITGLIEDSBEITRAG_CITY'), 0);
    $form->addCheckbox('origdebtoragent', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_DEBTOR_AGENT'), 0);
    $form->addCheckbox('origiban', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_IBAN'), 0);
    $form->addCheckbox('email', $gL10n->get('PLG_MITGLIEDSBEITRAG_EMAIL'), 0);
    $form->closeGroupBox();

    $form->openGroupBox('membership', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP'));
    $form->addCheckbox('accession', $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCESSION'), 0);
    $form->closeGroupBox();

    $form->openGroupBox('membershipfee', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP_FEE'));
    $form->addCheckbox('paid', $gL10n->get('PLG_MITGLIEDSBEITRAG_PAID'), 0);
    $form->addCheckbox('fee', $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE'), 0);
    $form->addCheckbox('contributorytext', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT'), 0);
    $form->addCheckbox('sequencetype', $gL10n->get('PLG_MITGLIEDSBEITRAG_SEQUENCETYPE'), 0);
    $form->addCheckbox('duedate', $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE'), 0);
    $form->closeGroupBox();

    $form->openGroupBox('mandate', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE'));
    $form->addCheckbox('mandateid', $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEID'), 0);
    $form->addCheckbox('mandatedate', $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEDATE'), 0);
    $form->addCheckbox('orig_mandateid', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_MANDATEID'), 0);
    $form->closeGroupBox();

    $form->openGroupBox('others', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_OTHERS'));
    $form->addCheckbox('mailtexts', $gL10n->get('PLG_MITGLIEDSBEITRAG_MAIL_TEXTS'), 0);
    $form->closeGroupBox();
    $form->closeGroupBox();

    $form->addSubmitButton('btn_deinstall', $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION'), array('icon' => THEME_URL .'/icons/delete.png', 'class' => ' col-sm-offset-3'));
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
        $deinst_member_data_message .= $pPreferences->delete_member_data(3, 'MEMBERNUMBER', $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER'));
    }
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
    if (isset($_POST['address']))
    {
        $deinst_member_data_message .= $pPreferences->delete_member_data(3, 'DEBTOR_ADDRESS', $gL10n->get('PLG_MITGLIEDSBEITRAG_ADDRESS'));
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
    if($deinst_config_data_message != '')
    {
        $deinstMessage .= '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONFIGURATION_DATA').'</strong><br/>';
        $deinstMessage .= $deinst_config_data_message;
    }
    if($deinst_member_data_message != '')
    {
        $deinstMessage .= '<br/><strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBER_DATA').'</strong>';
        $deinstMessage .= $deinst_member_data_message;
    }

    $form = new HtmlForm('deinstallations_form', null, $page);
    if($deinstMessage != $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINST_STARTMESSAGE'))
    {
        $form->addDescription($deinstMessage);
        $html = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DEINST_ENDMESSAGE').'</div>';
        $form->addDescription($html);

        //seltsamerweise wird in diesem Abschnitt nichts angezeigt wenn diese Anweisung fehlt
        $form->addStaticControl('', '', '');

        $_SESSION['pmbDeinst'] = true;
    }
    else
    {
        $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_DEINST_NO_SELECTED_DATA'));
        $form->addButton('next_page', $gL10n->get('SYS_NEXT'), array('icon' => THEME_URL .'/icons/forward.png', 'link' => $gHomepage, 'class' => 'btn-primary'));
    }
}
$page->addHtml($form->show(false));
$page->show();
