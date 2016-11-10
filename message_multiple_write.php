<?php
/**
 ***********************************************************************************************
 * E-Mails versenden aus dem Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:   message_multiple_write.php ist eine modifizierte messages_write.php
 *
 * Parameters:
 *
 * usr_id    	: E-Mail an den entsprechenden Benutzer schreiben
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

$getUserId      = admFuncVariableIsValid($_GET, 'usr_id', 'numeric', array('defaultValue' => 0));

$getSubject = '';

// check if the call of the page was allowed by settings
if ($gPreferences['enable_mail_module'] != 1 )
{
    // message if the sending of PM is not allowed
    $gMessage->show($gL10n->get('SYS_MODULE_DISABLED'));
}

// check if user has email address for sending a email
if ($gValidLogin && strlen($gCurrentUser->getValue('EMAIL')) == 0)
{
    $gMessage->show($gL10n->get('SYS_CURRENT_USER_NO_EMAIL', '<a href="'. ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php">', '</a>'));
}

// Subject und Body erzeugen
$text = new TableText($gDb);

$text->readDataByColumns(array('txt_name' => 'PMBMAIL_PRE_NOTIFICATION', 'txt_org_id' => $gCurrentOrganization->getValue('org_id')));

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

$getBody = preg_replace ('/\r\n/', '<BR>', $getBody);

 
if (strlen($getSubject) > 0)
{
    $headline = $gL10n->get('MAI_SUBJECT').': '.$getSubject;
}
else
{
    $headline = $gL10n->get('MAI_SEND_EMAIL');
}

// create html page object
$page = new HtmlPage($headline);

// add current url to navigation stack
$gNavigation->addUrl(CURRENT_URL, $headline);

// create module menu with back link
$messagesWriteMenu = new HtmlNavbar('menu_messages_write', $headline, $page);
$messagesWriteMenu->addItem('menu_item_back', $gNavigation->getPreviousUrl(), $gL10n->get('SYS_BACK'), 'back.png');
$page->addHtml($messagesWriteMenu->show(false));
  
$user_array = $_SESSION['checkedArray'];
$userEmail = $gL10n->get('PLG_MITGLIEDSBEITRAG_MAILCOUNT', count($user_array));

$form_values['name']         = '';
$form_values['mailfrom']     = '';
$form_values['subject']      = $getSubject;
$form_values['msg_body']     = $getBody;
$form_values['msg_to']       = 0;
$form_values['carbon_copy']  = 1;
$form_values['delivery_confirmation']  = 0;

$formParam = '';

// if subject was set as param then send this subject to next script
if (strlen($getSubject) > 0)
{
    $formParam .= 'subject='.$getSubject.'&';
}
    
// show form
$form = new HtmlForm('mail_send_form', ADMIDIO_URL . FOLDER_PLUGINS . '/'.$plugin_folder.'/message_multiple_send.php?'.$formParam, $page);
$form->openGroupBox('gb_mail_contact_details', $gL10n->get('SYS_CONTACT_DETAILS'));
    
$preload_data = '';
$form->addInput('msg_to', $gL10n->get('SYS_TO'), $userEmail, array('maxLength' => 50, 'property' => FIELD_DISABLED)); 
$form->addLine();
$form->addInput('name', $gL10n->get('MAI_YOUR_NAME'), $gCurrentUser->getValue('FIRST_NAME'). ' '. $gCurrentUser->getValue('LAST_NAME'), array('maxLength' => 50, 'property' => FIELD_DISABLED));
$form->addInput('mailfrom', $gL10n->get('MAI_YOUR_EMAIL'), $gCurrentUser->getValue('EMAIL'), array('maxLength' => 50, 'property' => FIELD_DISABLED));
$form->addCheckbox('carbon_copy', $gL10n->get('MAI_SEND_COPY'), $form_values['carbon_copy']);
 
if (($gCurrentUser->getValue('usr_id') > 0 && $gPreferences['mail_delivery_confirmation']==2) || $gPreferences['mail_delivery_confirmation']==1)
{
    $form->addCheckbox('delivery_confirmation', $gL10n->get('MAI_DELIVERY_CONFIRMATION'), $form_values['delivery_confirmation']);
}

$form->closeGroupBox();

$form->openGroupBox('gb_mail_message', $gL10n->get('SYS_MESSAGE'));
$form->addInput('subject', $gL10n->get('MAI_SUBJECT'), $form_values['subject'], array('maxLength' => 77, 'property' => FIELD_REQUIRED));

$form->addFileUpload('btn_add_attachment', $gL10n->get('MAI_ATTACHEMENT'), array('enableMultiUploads' => true, 'multiUploadLabel' => $gL10n->get('MAI_ADD_ATTACHEMENT'), 
        'hideUploadField' => true, 'helpTextIdLabel' => array('MAI_MAX_ATTACHMENT_SIZE', Email::getMaxAttachementSize('mb'))));

// add textfield or ckeditor to form
if($gValidLogin == true && $gPreferences['mail_html_registered_users'] == 1)
{
    $form->addEditor('msg_body', null, $form_values['msg_body']);
}
else
{
    $form->addMultilineTextInput('msg_body', $gL10n->get('SYS_TEXT'), null, 10);
}

$form->closeGroupBox();

$form->addSubmitButton('btn_send', $gL10n->get('SYS_SEND'), array('icon' => THEME_URL .'/icons/email.png', 'class' => ' col-sm-offset-3'));

// add form to html page and show page
$page->addHtml($form->show(false));

// show page
$page->show();
