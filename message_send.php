<?php
/**
 ***********************************************************************************************
 * Check message information and save it
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:   message_send.php ist eine modifizierte messages_send.php
 *
 * Parameters:
 *
 * usr_id  : Send email to this user
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/../../adm_program/system/template.php');

// Initialize and check the parameters
$getUserId  = admFuncVariableIsValid($_GET, 'usr_id', 'numeric', array('defaultValue' => 0));

// Check form values
$postFrom                   = admFuncVariableIsValid($_POST, 'mailfrom', 'string', array('defaultValue' => ''));
$postName                   = admFuncVariableIsValid($_POST, 'name', 'string', array('defaultValue' => ''));
$postSubject                = admFuncVariableIsValid($_POST, 'subject', 'html', array('defaultValue' => ''));
$postSubjectSQL             = admFuncVariableIsValid($_POST, 'subject', 'string', array('defaultValue' => ''));
$postBody                   = admFuncVariableIsValid($_POST, 'msg_body', 'html', array('defaultValue' => ''));
$postBodySQL                = admFuncVariableIsValid($_POST, 'msg_body', 'string', array('defaultValue' => ''));
$postDeliveryConfirmation   = admFuncVariableIsValid($_POST, 'delivery_confirmation', 'boolean', array('defaultValue' => 0));
$postCarbonCopy             = admFuncVariableIsValid($_POST, 'carbon_copy', 'boolean', array('defaultValue' => 0));

//vorbelegen
$getMsgType      = 'EMAIL';

// Stop if mail should be send and mail module is disabled
if($gPreferences['enable_mail_module'] != 1)
{
    $gMessage->show($gL10n->get('SYS_MODULE_DISABLED'));
}

// if Attachmentsize is higher than max_post_size from php.ini, then $_POST is empty.
if (empty($_POST))
{
    $gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
}

// if user is logged in then show sender name and email
if ($gCurrentUser->getValue('usr_id') > 0)
{
    $postName = $gCurrentUser->getValue('FIRST_NAME'). ' '. $gCurrentUser->getValue('LAST_NAME');
    $postFrom = $gCurrentUser->getValue('EMAIL');
}

// if no User is set, he is not able to ask for delivery confirmation
if(!($gCurrentUser->getValue('usr_id')>0 && $gPreferences['mail_delivery_confirmation']==2) && $gPreferences['mail_delivery_confirmation']!=1)
{
    $postDeliveryConfirmation = 0;
}

// put values into SESSION
$_SESSION['message_request'] = array(
    'name'                  => $postName,
    'msgfrom'               => $postFrom,
    'subject'               => $postSubject,
    'msg_body'              => $postBody,
    'carbon_copy'           => $postCarbonCopy,
    'delivery_confirmation' => $postDeliveryConfirmation,
);

$receiver = array();

// Create new Email Object
$email = new Email();

$user = new User($gDb, $gProfileFields, $getUserId);

// error if no valid Email for given user ID
if (!strValidCharacters($user->getValue('EMAIL'), 'email'))
{
    $gMessage->show($gL10n->get('SYS_USER_NO_EMAIL', $user->getValue('FIRST_NAME').' '.$user->getValue('LAST_NAME')));
}

// save page in navigation - to have a check for a navigation back.
$gNavigation->addUrl(CURRENT_URL);

// check if name is given
if(strlen($postName) === 0)
{
    $gMessage->show($gL10n->get('SYS_FIELD_EMPTY', $gL10n->get('SYS_NAME')));
}

// check sending attributes for user, to be sure that they are correct
if ($gValidLogin
    && ($postFrom != $gCurrentUser->getValue('EMAIL')
       || $postName != $gCurrentUser->getValue('FIRST_NAME').' '.$gCurrentUser->getValue('LAST_NAME')))
{
    $gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
}

// set sending address
if ($email->setSender($postFrom, $postName))
{
    // set subject
    if ($email->setSubject($postSubject))
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
            for($currentAttachmentNo = 0; isset($_FILES['userfile']['name'][$currentAttachmentNo]) == true; $currentAttachmentNo++)
            {
                // check if Upload was OK
                if (($_FILES['userfile']['error'][$currentAttachmentNo] != 0) &&  ($_FILES['userfile']['error'][$currentAttachmentNo] != 4))
                {
                    $gMessage->show($gL10n->get('MAI_ATTACHMENT_TO_LARGE'));
                }

                if ($_FILES['userfile']['error'][$currentAttachmentNo] == 0)
                {
                    // check the size of the attachment
                    $attachmentSize = $attachmentSize + $_FILES['userfile']['size'][$currentAttachmentNo];
                    if($attachmentSize > $email->getMaxAttachementSize('b'))
                    {
                        $gMessage->show($gL10n->get('MAI_ATTACHMENT_TO_LARGE'));
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
        $gMessage->show($gL10n->get('SYS_FIELD_EMPTY', $gL10n->get('MAI_SUBJECT')));
    }
}
else
{
    $gMessage->show($gL10n->get('SYS_EMAIL_INVALID', $gL10n->get('SYS_EMAIL')));
}

// if possible send html mail
if($gValidLogin == true && $gPreferences['mail_html_registered_users'] == 1)
{
    $email->sendDataAsHtml();
}

// set flag if copy should be send to sender
if (isset($postCarbonCopy) && $postCarbonCopy == true)
{
    $email->setCopyToSenderFlag();

    // if mail was send to user than show recipients in copy of mail if current user has a valid login
    if($gValidLogin)
    {
        $email->setListRecipientsFlag();
    }
}

$email->addRecipient($user->getValue('EMAIL'), $user->getValue('FIRST_NAME').' '.$user->getValue('LAST_NAME'));

// add confirmation mail to the sender
if($postDeliveryConfirmation == 1)
{
    $email->ConfirmReadingTo = $gCurrentUser->getValue('EMAIL');
}

// load the template and set the new email body with template
$emailTemplate = admReadTemplateFile('template.html');
$emailTemplate = str_replace('#message#', $postBody, $emailTemplate);

// set Text
$email->setText($emailTemplate);

// finally send the mail
$sendResult = $email->sendEmail();

// message if send/save is OK
if ($sendResult === TRUE)
{
    $sql = 'INSERT INTO '. TBL_MESSAGES. " (msg_type, msg_subject, msg_usr_id_sender, msg_usr_id_receiver, msg_timestamp, msg_read)
            VALUES ('".$getMsgType."', '".$postSubjectSQL."', ".$gCurrentUser->getValue('usr_id').', '.$user->getValue('usr_id').', CURRENT_TIMESTAMP, 0)';

    $gDb->query($sql);
    $getMsgId = $gDb->lastInsertId();

    $sql = 'INSERT INTO '. TBL_MESSAGES_CONTENT. ' (msc_msg_id, msc_part_id, msc_usr_id, msc_message, msc_timestamp)
            VALUES ('.$getMsgId.', 1, '.$gCurrentUser->getValue('usr_id').", '".$postBodySQL."', CURRENT_TIMESTAMP)";

    $gDb->query($sql);

    // after sending remove the actual Page from the NaviObject and remove also the send-page
    $gNavigation->deleteLastUrl();
    $gNavigation->deleteLastUrl();

    // message if sending was OK
    if($gNavigation->count() > 0)
    {
        $gMessage->setForwardUrl($gNavigation->getUrl(), 2000);
    }
    else
    {
        $gMessage->setForwardUrl($gHomepage, 2000);
    }

    //$gMessage->show($gL10n->get('SYS_EMAIL_SEND', $user->getValue('FIRST_NAME').' '.$user->getValue('LAST_NAME')));
    $gMessage->show($gL10n->get('SYS_EMAIL_SEND'));
}
else
{
    $gMessage->show($sendResult.'<br />'.$gL10n->get('SYS_EMAIL_NOT_SEND', $sendResult));
}
