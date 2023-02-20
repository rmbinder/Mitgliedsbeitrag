<?php
/**
 ***********************************************************************************************
 * E-Mails versenden aus dem Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 * 
 * user_uuid    : send message to the given user UUID
 * usf_uuid     : UUID of the (email) profile field which was transferred
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

$getUserUuid = admFuncVariableIsValid($_GET, 'user_uuid', 'string');
$getUsfUuid  = admFuncVariableIsValid($_GET, 'usf_uuid', 'string');

// only authorized user are allowed to start this module
if (!isUserAuthorized($_SESSION['pMembershipFee']['script_name']))
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
	// => EXIT
}

// check if the call of the page was allowed by settings
if (!$gSettingsManager->getBool('enable_mail_module'))
{
    // message if the sending of PM is not allowed
    $gMessage->show($gL10n->get('SYS_MODULE_DISABLED'));
    // => EXIT
}

// check if user has email address for sending a email
if (!$gCurrentUser->hasEmail())
{
    $gMessage->show($gL10n->get('SYS_CURRENT_USER_NO_EMAIL', array('<a href="'.ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php">', '</a>')));
    // => EXIT
}

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

$mailSubject = '';
$mailBody    = '';
$mailToArray = array();

$user = new User($gDb, $gProfileFields);
$userField = new TableUserField($gDb);

$singleMail = false;
if ($getUserUuid !== '' && $getUsfUuid !== '')                          // ein E-Mail-Link wurde angeklickt
{
    $user->readDataByUuid($getUserUuid);
    $userField->readDataByUuid($getUsfUuid);
    $singleMail = true;
}
elseif (isset($_SESSION['pMembershipFee']['checkedArray']))
{
    $mailToArray = $_SESSION['pMembershipFee']['checkedArray'];
    
    foreach ($mailToArray as $userId => $usfUuid )
    {
        if ($usfUuid === '')
        {
            unset($mailToArray[$userId]);
        }
    }
    
    if (count($mailToArray) < 1)
    {
        $gMessage->show($gL10n->get('PLG_MITGLIEDSBEITRAG_EMAIL_EMPTY'));
        // => EXIT
    }
    elseif (count($mailToArray) === 1)                                      // der Mail-Button wurde angeklickt (aber es gibt nur eine Mail-Adresse in der Liste)
    {
        $user->readDataById(key($mailToArray));
        $getUserUuid = $user->getValue('usr_uuid');
        $getUsfUuid = current($mailToArray);
        $userField->readDataByUuid($getUsfUuid);
        $singleMail = true;
    }
}
else 
{
    $gMessage->show($gL10n->get('PLG_MITGLIEDSBEITRAG_EMAIL_EMPTY'));
    // => EXIT
}

// Subject und Body erzeugen
$text = new TableText($gDb);

//abhaengig vom aufrufenden Modul Text einlesen
if (substr_count($gNavigation->getUrl(), 'pre_notification') === 1)
{
    $text->readDataByColumns(array('txt_name' => 'PMBMAIL_PRE_NOTIFICATION', 'txt_org_id' => $gCurrentOrgId));
    //wenn noch nichts drin steht (=> "Einstellungen" und "Sichern" wurde noch nicht aufgerufen), dann vorbelegen (aber nicht speichern)
    if ($text->getValue('txt_text') == '')
    {
        // convert <br /> to a normal line feed
        $value = preg_replace('/<br[[:space:]]*\/?[[:space:]]*>/', chr(13).chr(10), $gL10n->get('PLG_MITGLIEDSBEITRAG_PMBMAIL_PRE_NOTIFICATION'));
        $text->setValue('txt_text', $value);
    }
}
elseif (substr_count($gNavigation->getUrl(), 'payments') === 1)
{
    $text->readDataByColumns(array('txt_name' => 'PMBMAIL_CONTRIBUTION_PAYMENTS', 'txt_org_id' => $gCurrentOrgId));
    //wenn noch nichts drin steht (=> "Einstellungen" und "Sichern" wurde noch nicht aufgerufen), dann vorbelegen (aber nicht speichern)
    if ($text->getValue('txt_text') == '')
    {
        // convert <br /> to a normal line feed
        $value = preg_replace('/<br[[:space:]]*\/?[[:space:]]*>/', chr(13).chr(10), $gL10n->get('PLG_MITGLIEDSBEITRAG_PMBMAIL_CONTRIBUTION_PAYMENTS'));
        $text->setValue('txt_text', $value);
    }
}
elseif (substr_count($gNavigation->getUrl(), 'bill') === 1)
{
    $text->readDataByColumns(array('txt_name' => 'PMBMAIL_BILL', 'txt_org_id' => $gCurrentOrgId));
    //wenn noch nichts drin steht (=> "Einstellungen" und "Sichern" wurde noch nicht aufgerufen), dann vorbelegen (aber nicht speichern)
    if ($text->getValue('txt_text') == '')
    {
        // convert <br /> to a normal line feed
        $value = preg_replace('/<br[[:space:]]*\/?[[:space:]]*>/', chr(13).chr(10), $gL10n->get('PLG_MITGLIEDSBEITRAG_PMBMAIL_BILL'));
        $text->setValue('txt_text', $value);
    }
}

$mailSrcText = $text->getValue('txt_text');

if ($singleMail)
{
    $mailSrcText = replace_emailparameter($mailSrcText, $user);             // nur wenn eine einzige Empfängeradresse vorhanden ist, die Parameter ersetzen
}

// Betreff und Inhalt anhand von Kennzeichnungen splitten oder ggf. Default-Inhalte nehmen
if(strpos($mailSrcText, '#subject#') !== false)
{
    $mailSubject = trim(substr($mailSrcText, strpos($mailSrcText, '#subject#') + 9, strpos($mailSrcText, '#content#') - 9));
}
else
{
    $mailSubject = 'Nachricht von '. $gCurrentOrganization->getValue('org_longname');
}

if(strpos($mailSrcText, '#content#') !== false)
{
    $mailBody   = trim(substr($mailSrcText, strpos($mailSrcText, '#content#') + 9));
}
else
{
    $mailBody   = $mailSrcText;
}

$mailBody = preg_replace('/\r\n/', '<br/>', $mailBody);

if ($mailSubject !== '')
{
    $headline = $gL10n->get('SYS_SUBJECT').': '.$mailSubject;
}
else
{
    $headline = $gL10n->get('SYS_SEND_EMAIL');
}

if ($singleMail)
{
    //Datensatz fuer E-Mail-Adresse zusammensetzen
    if($userField->getValue('usf_name_intern') === 'DEBTOR_EMAIL')                      // Problem: 'DEBTOR_EMAIL' ist als TEXT in der DB definiert
    {
        if(StringUtils::strValidCharacters($user->getValue('DEBTOR_EMAIL'), 'email'))
        {
            $userEmail = $user->getValue('DEBTOR').' <'.$user->getValue('DEBTOR_EMAIL').'>';
        }
        else 
        {
            $gMessage->show($gL10n->get('SYS_USER_NO_EMAIL', array($user->getValue('DEBTOR'))));
            // => EXIT
        }
    }
    else 
    {
        if(StringUtils::strValidCharacters($user->getValue($userField->getValue('usf_name_intern')), 'email'))
        {
            $userEmail = $user->getValue('FIRST_NAME'). ' '. $user->getValue('LAST_NAME').' <'.$user->getValue($userField->getValue('usf_name_intern')).'>';
        }
        else
        {
            $gMessage->show($gL10n->get('SYS_USER_NO_EMAIL', array($user->getValue('FIRST_NAME').' '.$user->getValue('LAST_NAME'))));
            // => EXIT
        }
    }
}
else 
{
    $userEmail = $gL10n->get('PLG_MITGLIEDSBEITRAG_MAILCOUNT', array(count($mailToArray)));
}

// Wenn die letzte URL in der Zuruecknavigation die des Scriptes message_send.php ist,
// dann soll das Formular gefuellt werden mit den Werten aus der Session
// dieser Fall tritt i.d.R. nur ein, wenn der Mailversand fehlgeschlagen ist
if (strpos($gNavigation->getUrl(), 'message_send.php') > 0 && isset($_SESSION['pMembershipFee']['message_request']))
{
    // Das Formular wurde also schon einmal ausgef�llt,
    // da der User hier wieder gelandet ist nach der Mailversand-Seite
    $formValues = $_SESSION['pMembershipFee']['message_request'];
    unset($_SESSION['pMembershipFee']['message_request']);
    $gNavigation->deleteLastUrl();                                  
    if(!isset($formValues['carbon_copy']))
    {
        $formValues['carbon_copy'] = false;
    }
    if(!isset($formValues['delivery_confirmation']))
    {
        $formValues['delivery_confirmation'] = false;
    }
    if(!isset($formValues['mailfrom']))
    {
        $formValues['mailfrom'] = $gCurrentUser->getValue('EMAIL');
    }    
}
else
{
    $formValues['msg_subject']  = $mailSubject;
    $formValues['msg_body']     = $mailBody;
    $formValues['namefrom']     = '';
    $formValues['mailfrom']     = $gCurrentUser->getValue('EMAIL');
    $formValues['carbon_copy']  = false;
    $formValues['delivery_confirmation'] = false;
}

// add current url to navigation stack
$gNavigation->addUrl(CURRENT_URL, $headline);

$page = new HtmlPage('plg-mitgliedsbeitrag-message-write', $headline);

// show form
$form = new HtmlForm('mail_send_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/message_send.php', array('user_uuid' => $getUserUuid, 'usf_uuid' => $getUsfUuid)), $page, array('enableFileUpload' => true)); 

$form->openGroupBox('gb_mail_contact_details', $gL10n->get('SYS_CONTACT_DETAILS'));
$form->addInput('msg_to', $gL10n->get('SYS_TO'), $userEmail, array('maxLength' => 50, 'property' => HtmlForm::FIELD_DISABLED));
$form->addLine();
$form->addInput('namefrom', $gL10n->get('SYS_YOUR_NAME'), $gCurrentUser->getValue('FIRST_NAME'). ' '. $gCurrentUser->getValue('LAST_NAME'), 
    array('maxLength' => 50, 'property' => HtmlForm::FIELD_DISABLED)
);

//Auswahlmöglichkeit für Sender-EMail-Adresse (falls es mehrere gibt)
$sql = 'SELECT COUNT(*) AS count
          FROM '.TBL_USER_FIELDS.'
    INNER JOIN '. TBL_USER_DATA .'
            ON usd_usf_id = usf_id
         WHERE usf_type = \'EMAIL\'
           AND usd_usr_id = ? -- $gCurrentUserId
           AND usd_value IS NOT NULL';

$pdoStatement = $gDb->queryPrepared($sql, array($gCurrentUserId));
$possibleEmails = $pdoStatement->fetchColumn();

if($possibleEmails > 1)
{
    $sqlData = array();
    $sqlData['query'] = 'SELECT email.usd_value AS ID, email.usd_value AS email
                           FROM '.TBL_USERS.'
                     INNER JOIN '.TBL_USER_DATA.' AS email
                             ON email.usd_usr_id = usr_id
                            AND LENGTH(email.usd_value) > 0
                     INNER JOIN '.TBL_USER_FIELDS.' AS field
                             ON field.usf_id = email.usd_usf_id
                            AND field.usf_type = \'EMAIL\'
                          WHERE usr_id = ? -- $gCurrentUserId
                            AND usr_valid = 1
                       GROUP BY email.usd_value, email.usd_value';
    $sqlData['params'] = array($gCurrentUserId);

    $form->addSelectBoxFromSql(
        'mailfrom', $gL10n->get('SYS_YOUR_EMAIL'), $gDb, $sqlData,
        array('maxLength' => 50, 'defaultValue' => $formValues['mailfrom'], 'showContextDependentFirstEntry' => false)
    );
}
else
{
    $form->addInput(
        'mailfrom', $gL10n->get('SYS_YOUR_EMAIL'), $formValues['mailfrom'],
        array('maxLength' => 50, 'property' => HtmlForm::FIELD_DISABLED)
    );
}

$form->addCheckbox('carbon_copy', $gL10n->get('SYS_SEND_COPY'), $formValues['carbon_copy']);

// if preference is set then show a checkbox where the user can request a delivery confirmation for the email
if (( (int) $gSettingsManager->get('mail_delivery_confirmation') === 2) || (int) $gSettingsManager->get('mail_delivery_confirmation') === 1)
{
    $form->addCheckbox('delivery_confirmation', $gL10n->get('SYS_DELIVERY_CONFIRMATION'), $formValues['delivery_confirmation']);
}

$form->closeGroupBox();

$form->openGroupBox('gb_mail_message', $gL10n->get('SYS_MESSAGE'));
$form->addInput('msg_subject', $gL10n->get('SYS_SUBJECT'), $formValues['msg_subject'], array('maxLength' => 77, 'property' => HtmlForm::FIELD_REQUIRED));

if (($gSettingsManager->getInt('max_email_attachment_size') > 0) && PhpIniUtils::isFileUploadEnabled())
{
    $form->addFileUpload(
        'btn_add_attachment', $gL10n->get('SYS_ATTACHMENT'),
        array(
            'enableMultiUploads' => true,
            'maxUploadSize'      => Email::getMaxAttachmentSize(),
            'multiUploadLabel'   => $gL10n->get('SYS_ADD_ATTACHMENT'),
            'hideUploadField'    => true,
            'helpTextIdLabel'    => $gL10n->get('SYS_MAX_ATTACHMENT_SIZE', array(Email::getMaxAttachmentSize(Email::SIZE_UNIT_MEBIBYTE))),
            'icon'               => 'fa-paperclip'
        )
    );
}

$form->addEditor('msg_body', '', $formValues['msg_body'], array('property' => HtmlForm::FIELD_REQUIRED));
$form->closeGroupBox();

$form->addSubmitButton('btn_send', $gL10n->get('SYS_SEND'), array('icon' => 'fa-envelope'));

$page->addHtml($form->show());

$page->show();
