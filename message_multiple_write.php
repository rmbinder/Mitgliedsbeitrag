<?php
/**
 ***********************************************************************************************
 * E-Mails versenden aus dem Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2021 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:   message_multiple_write.php ist eine modifizierte messages_write.php
 *
 * Parameters:
 *
 * usr_id       : E-Mail an den entsprechenden Benutzer schreiben
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

$getUserId      = admFuncVariableIsValid($_GET, 'usr_id', 'numeric', array('defaultValue' => 0));

$getSubject = '';

// check if the call of the page was allowed by settings
if ($gSettingsManager->getString('enable_mail_module') != 1)
{
    // message if the sending of PM is not allowed
    $gMessage->show($gL10n->get('SYS_MODULE_DISABLED'));
}

// check if user has email address for sending a email
if ($gValidLogin && strlen($gCurrentUser->getValue('EMAIL')) === 0)
{
    $gMessage->show($gL10n->get('SYS_CURRENT_USER_NO_EMAIL', '<a href="'. ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php">', '</a>'));
}

// Subject und Body erzeugen
$text = new TableText($gDb);

$text->readDataByColumns(array('txt_name' => 'PMBMAIL_PRE_NOTIFICATION', 'txt_org_id' => ORG_ID));

$mailSrcText = $text->getValue('txt_text');

// Betreff und Inhalt anhand von Kennzeichnungen splitten oder ggf. Default-Inhalte nehmen
if(strpos($mailSrcText, '#subject#') !== false)
{
    $getSubject = trim(substr($mailSrcText, strpos($mailSrcText, '#subject#') + 9, strpos($mailSrcText, '#content#') - 9));
}
else
{
    $getSubject = 'Nachricht von '. $gCurrentOrganization->getValue('org_longname');
}

if(strpos($mailSrcText, '#content#') !== false)
{
    $getBody   = trim(substr($mailSrcText, strpos($mailSrcText, '#content#') + 9));
}
else
{
    $getBody   = $mailSrcText;
}

$getBody = preg_replace('/\r\n/', '<br/>', $getBody);

if (strlen($getSubject) > 0)
{
    $headline = $gL10n->get('MAI_SUBJECT').': '.$getSubject;
}
else
{
    $headline = $gL10n->get('MAI_SEND_EMAIL');
}

// add current url to navigation stack
$gNavigation->addUrl(CURRENT_URL, $headline);

$page = new HtmlPage('plg-mitgliedsbeitrag-message-multiple-write', $headline);

$user_array = $_SESSION['pMembershipFee']['checkedArray'];
$userEmail = $gL10n->get('PLG_MITGLIEDSBEITRAG_MAILCOUNT', array(count($user_array)));

$form_values['name']         = '';
$form_values['mailfrom']     = '';
$form_values['subject']      = $getSubject;
$form_values['msg_body']     = $getBody;
$form_values['msg_to']       = 0;
$form_values['carbon_copy']  = 1;
$form_values['delivery_confirmation']  = 0;

 $formParams = array();
 
// if subject was set as param then send this subject to next script
if (strlen($getSubject) > 0)
{
     $formParams['subject'] = $getSubject;
}

// show form
$form = new HtmlForm('mail_send_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/message_multiple_send.php', $formParams), $page);
$form->openGroupBox('gb_mail_contact_details', $gL10n->get('SYS_CONTACT_DETAILS'));

$preload_data = '';
$form->addInput('msg_to', $gL10n->get('SYS_TO'), $userEmail, array('maxLength' => 50, 'property' => HtmlForm::FIELD_DISABLED));
$form->addLine();
$form->addInput('name', $gL10n->get('MAI_YOUR_NAME'), $gCurrentUser->getValue('FIRST_NAME'). ' '. $gCurrentUser->getValue('LAST_NAME'), array('maxLength' => 50, 'property' => HtmlForm::FIELD_DISABLED));
$form->addInput('mailfrom', $gL10n->get('MAI_YOUR_EMAIL'), $gCurrentUser->getValue('EMAIL'), array('maxLength' => 50, 'property' => HtmlForm::FIELD_DISABLED));
$form->addCheckbox('carbon_copy', $gL10n->get('MAI_SEND_COPY'), $form_values['carbon_copy']);

if (($gCurrentUser->getValue('usr_id') > 0 && $gSettingsManager->getString('mail_delivery_confirmation') == 2) || $gSettingsManager->getString('mail_delivery_confirmation') == 1)
{
    $form->addCheckbox('delivery_confirmation', $gL10n->get('MAI_DELIVERY_CONFIRMATION'), $form_values['delivery_confirmation']);
}

$form->closeGroupBox();

$form->openGroupBox('gb_mail_message', $gL10n->get('SYS_MESSAGE'));
$form->addInput('subject', $gL10n->get('MAI_SUBJECT'), $form_values['subject'], array('maxLength' => 77, 'property' => HtmlForm::FIELD_REQUIRED));

$form->addFileUpload('btn_add_attachment', $gL10n->get('MAI_ATTACHEMENT'), array('enableMultiUploads' => true,
                                                                                 'multiUploadLabel'   => $gL10n->get('MAI_ADD_ATTACHEMENT'),
                                                                                 'hideUploadField'    => true,
                                                                                 'helpTextIdLabel'    => $gL10n->get('MAI_MAX_ATTACHMENT_SIZE', array(Email::getMaxAttachmentSize(Email::SIZE_UNIT_MEBIBYTE)))));

// add textfield or ckeditor to form
if($gValidLogin == true && $gSettingsManager->getString('mail_html_registered_users') == 1)
{
    $form->addEditor('msg_body', null, $form_values['msg_body']);
}
else
{
    $form->addMultilineTextInput('msg_body', $gL10n->get('SYS_TEXT'), null, 10);
}

$form->closeGroupBox();

$form->addSubmitButton('btn_send', $gL10n->get('SYS_SEND'), array('icon' => 'fa-envelope'));

// add form to html page and show page
$page->addHtml($form->show(false));

// show page
$page->show();
