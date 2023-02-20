<?php
/**
 ***********************************************************************************************
 * Check message information and save it
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * user_uuid  : Send email to this user
 * usf_uuid   : UUID of the (email) profile field which was transferred
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

//use PHPMailer\PHPMailer\Exception;

// Initialize and check the parameters
$getUserUuid = admFuncVariableIsValid($_GET, 'user_uuid', 'string');
$getUsfUuid  = admFuncVariableIsValid($_GET, 'usf_uuid', 'string');

// Check form values
$postFrom                  = admFuncVariableIsValid($_POST, 'mailfrom', 'string', array('defaultValue' => $gCurrentUser->getValue('EMAIL')));
$postName                  = admFuncVariableIsValid($_POST, 'namefrom', 'string', array('defaultValue' => $gCurrentUser->getValue('FIRST_NAME'). ' '. $gCurrentUser->getValue('LAST_NAME')));
$postSubject               = StringUtils::strStripTags($_POST['msg_subject']); 
$postBody                  = admFuncVariableIsValid($_POST, 'msg_body', 'html');
$postDeliveryConfirmation  = admFuncVariableIsValid($_POST, 'delivery_confirmation', 'bool');
$postCarbonCopy            = admFuncVariableIsValid($_POST, 'carbon_copy', 'boolean', array('defaultValue' => 0));

// save form data in session for back navigation
$_SESSION['pMembershipFee']['message_request'] = $_POST;

// save page in navigation - to have a check for a navigation back.
$gNavigation->addUrl(CURRENT_URL);

// Stop if mail should be send and mail module is disabled
if (!$gSettingsManager->getBool('enable_mail_module'))
{
    $gMessage->show($gL10n->get('SYS_MODULE_DISABLED'));
    // => EXIT
}

if ($postSubject === '')
{
    // message when no subject is given
    $gMessage->show($gL10n->get('SYS_FIELD_EMPTY', array($gL10n->get('SYS_SUBJECT'))));
    // => EXIT
}

if ($postBody === '')
{
    // message when no subject is given
    $gMessage->show($gL10n->get('SYS_FIELD_EMPTY', array($gL10n->get('SYS_MESSAGE'))));
    // => EXIT
}

// if Attachmentsize is higher than max_post_size from php.ini, then $_POST is empty.
if (empty($_POST))
{
    $gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
    // => EXIT
}

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

$sendMailResultMessage = '';
$sendMailResultSendOK = array('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MAILSEND_OK').'</strong>');
$sendMailResultMissingEmail = array('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MAILMISSING_EMAIL').'</strong>');
$sendMailResultAnotherError = array('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MAILANOTHER_ERROR').'</strong>');
$sendResult  = false;

$user = new User($gDb, $gProfileFields);
$userField = new TableUserField($gDb);

if ($getUserUuid !== '' && $getUsfUuid !== '')                          // ein einzelner E-Mail-Link wurde angeklickt
{
    $user->readDataByUuid($getUserUuid);
    $mailToArray = array($user->getValue('usr_id') => $getUsfUuid);
}
else                                                                    // wenn nicht, dann kann es nur eine Liste sein (erzeugt von pre_notification.php)
{
    $mailToArray = $_SESSION['pMembershipFee']['checkedArray'];
    foreach ($mailToArray as $userId => $usfUuid )
    {
        if ($usfUuid === '')
        {
            unset($mailToArray[$userId]);
        }
    }
}

foreach ($mailToArray as $userId => $usfUuid )
{
    // Create new Email Object
    $email = new Email();

    $user->readDataById($userId);
    $userField->readDataByUuid($usfUuid); 
    
    //Datensatz fuer E-Mail-Adresse zusammensetzen
    if($userField->getValue('usf_name_intern') === 'DEBTOR_EMAIL')                      // Problem: 'DEBTOR_EMAIL' ist als TEXT in der DB definiert
    {
        $receiverEmail = $user->getValue('DEBTOR_EMAIL');
        $receiverName = $user->getValue('DEBTOR');
    }
    else 
    {
        $receiverEmail = $user->getValue($userField->getValue('usf_name_intern'));
        $receiverName = $user->getValue('FIRST_NAME'). ' '. $user->getValue('LAST_NAME');
    }
   
    if (!StringUtils::strValidCharacters($receiverEmail, 'email'))
    {
        $sendMailResultMissingEmail[] = $receiverName;
        continue;
    }
    
    // evtl. definierte Parameter ersetzen
    $subject = replace_emailparameter($postSubject, $user);
    $body = replace_emailparameter($postBody, $user);   
    
    // object to handle the current message in the database
    $message = new TableMessage($gDb);
    $message->setValue('msg_type', TableMessage::MESSAGE_TYPE_EMAIL);
    $message->setValue('msg_subject', $subject);
    $message->setValue('msg_usr_id_sender', $gCurrentUserId);
    $message->addContent($body);
    $message->addUser((int) $user->getValue('usr_id'), $user->getValue('FIRST_NAME') . ' ' . $user->getValue('LAST_NAME'));
    
    // set sending address
    if ($email->setSender($postFrom, $postName))
    {
        // set subject
        if ($email->setSubject($subject))
        {
            // check for attachment
            if (isset($_FILES['userfile']))
            {
                // final check if user is logged in
                if (!$gValidLogin)
                {
                    $gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
                }

                $attachmentSize = 0;
                // add now every attachment
                for ($currentAttachmentNo = 0; isset($_FILES['userfile']['name'][$currentAttachmentNo]); ++$currentAttachmentNo)
                {
                    // check if Upload was OK
                    if (($_FILES['userfile']['error'][$currentAttachmentNo] !== UPLOAD_ERR_OK) 
                    &&  ($_FILES['userfile']['error'][$currentAttachmentNo] !== UPLOAD_ERR_NO_FILE))
                    {
                        $gMessage->show($gL10n->get('SYS_ATTACHMENT_TO_LARGE'));
                        // => EXIT
                    }
                
                    // check if a file was really uploaded
                    if(!file_exists($_FILES['userfile']['tmp_name'][$currentAttachmentNo]) || !is_uploaded_file($_FILES['userfile']['tmp_name'][$currentAttachmentNo]))
                    {
                        $gMessage->show($gL10n->get('SYS_FILE_NOT_EXIST'));
                        // => EXIT
                    }
                    
                    if ($_FILES['userfile']['error'][$currentAttachmentNo] === UPLOAD_ERR_OK)
                    {
                        // check the size of the attachment
                        $attachmentSize += $_FILES['userfile']['size'][$currentAttachmentNo];
                        if ($attachmentSize > Email::getMaxAttachmentSize())
                        {
                            $gMessage->show($gL10n->get('SYS_ATTACHMENT_TO_LARGE'));
                            // => EXIT
                        }

                        // set filetyp to standart if not given
                        if (strlen($_FILES['userfile']['type'][$currentAttachmentNo]) <= 0)
                        {
                            $_FILES['userfile']['type'][$currentAttachmentNo] = 'application/octet-stream';                        
                        }

                        // add the attachment to the mail
                        try
                        {
                            $email->AddAttachment($_FILES['userfile']['tmp_name'][$currentAttachmentNo], $_FILES['userfile']['name'][$currentAttachmentNo], $encoding = 'base64', $_FILES['userfile']['type'][$currentAttachmentNo]);
                            $message->addAttachment($_FILES['userfile']['tmp_name'][$currentAttachmentNo], $_FILES['userfile']['name'][$currentAttachmentNo]);
                        }
                        catch (phpmailerException $e)
                        {
                            $gMessage->show($e->errorMessage());
                        }             
                    }
                }
            }
        }
        else
        {
            $gMessage->show($gL10n->get('SYS_FIELD_EMPTY', array($gL10n->get('SYS_SUBJECT'))));
        }
    }
    else
    {
        $gMessage->show($gL10n->get('SYS_EMAIL_INVALID', array($gL10n->get('SYS_EMAIL'))));
    }

    // if possible send html mail
    if ($gValidLogin && $gSettingsManager->getBool('mail_html_registered_users'))
    {
        $email->setHtmlMail();
    }

    // set flag if copy should be send to sender
    if (isset($postCarbonCopy) && $postCarbonCopy)
    {
        $email->setCopyToSenderFlag();
    }

    //den User dem Mailobjekt hinzufuegen...
    $email->addRecipient($receiverEmail, $receiverName);
    
    // add confirmation mail to the sender
    if($postDeliveryConfirmation)
    {
        $email->ConfirmReadingTo = $gCurrentUser->getValue('EMAIL');
    }

    // load mail template and replace text
    $email->setTemplateText($body, $postName, $gCurrentUser->getValue('EMAIL'), $gCurrentUser->getValue('usr_uuid'), $message->getRecipientsNamesString());
    
    // finally send the mail
    $sendMailResult = $email->sendEmail();
    
    // within this mode an smtp protocol will be shown and the header was still send to browser
    if ($gDebug && headers_sent())
    {
        $email->isSMTP();
        $gMessage->showHtmlTextOnly(true);
    }

    if ($sendMailResult === TRUE)
    {
        $sendMailResultSendOK[] = $receiverName.' ('.$receiverEmail.')';
    }
    else
    {
        if(strlen($receiverEmail) > 0)
        {
            $sendMailResultAnotherError[] = $sendMailResult.$receiverName;
        }
    }
    
    // save mail to database
    $message->save();
}
 
// Erfolgsmeldung zusammensetzen
if(count($sendMailResultSendOK) > 1)
{
    foreach ($sendMailResultSendOK as $data)
    {
        $sendMailResultMessage .= $data.'<br/>';
    }
    $sendMailResultMessage .= '<br/>';
}
if(count($sendMailResultMissingEmail) > 1)
{
    foreach ($sendMailResultMissingEmail as $data)
    {
        $sendMailResultMessage .= $data.'<br/>';
    }
    $sendMailResultMessage .= '<br/>';
}
if(count($sendMailResultAnotherError) > 1)
{
    foreach ($sendMailResultAnotherError as $data)
    {
        $sendMailResultMessage .= $data.'<br/>';
    }
}

$gNavigation->deleteLastUrl();

// zur Ausgangsseite zurueck
$gMessage->setForwardUrl($gNavigation->getPreviousUrl(), 0);
$gMessage->show($sendMailResultMessage);
