<?php
/**
 ***********************************************************************************************
 * Check message information and save it
 *
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:   message_multiple_send.php ist eine modifizierte messages_send.php
 *
 * Parameters:       keine
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/../../adm_program/system/template.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

// $pPreferences ist auch fuer die korrekte Aufloesung des Parameters #creditor_id# erforderlich
$pPreferences = new ConfigTablePMB();
$pPreferences->read();

// only authorized user are allowed to start this module
if(!check_showpluginPMB($pPreferences->config['Pluginfreigabe']['freigabe']))
{
    $gMessage->setForwardUrl($gHomepage, 3000);
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Initialize and check the parameters
$postFrom                   = admFuncVariableIsValid($_POST, 'mailfrom', 'string', array('defaultValue' => ''));
$postName                   = admFuncVariableIsValid($_POST, 'name', 'string', array('defaultValue' => ''));
$postSubjectOrig            = admFuncVariableIsValid($_POST, 'subject', 'html', array('defaultValue' => ''));
$postSubjectSQL             = admFuncVariableIsValid($_POST, 'subject', 'string', array('defaultValue' => ''));
$postBodyOrig               = admFuncVariableIsValid($_POST, 'msg_body', 'html', array('defaultValue' => ''));
$postBodySQL                = admFuncVariableIsValid($_POST, 'msg_body', 'string', array('defaultValue' => ''));
$postDeliveryConfirmation   = admFuncVariableIsValid($_POST, 'delivery_confirmation', 'boolean', array('defaultValue' => 0));
$postCarbonCopy             = admFuncVariableIsValid($_POST, 'carbon_copy', 'boolean', array('defaultValue' => 0));

//vorbelegen
$getMsgType      = 'EMAIL';

$postName = $gCurrentUser->getValue('FIRST_NAME'). ' '. $gCurrentUser->getValue('LAST_NAME');
$postFrom = $gCurrentUser->getValue('EMAIL');
$empfaenger = '';

$sendMailResultMessage = '';
$sendMailResultSendOK = array('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MAILSEND_OK').'</strong>');
$sendMailResultMissingEmail = array('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MAILMISSING_EMAIL').'</strong>');
$sendMailResultAnotherError = array('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MAILANOTHER_ERROR').'</strong>');

$user_array = $_SESSION['pMembershipFee']['checkedArray'];

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

// if no User is set, he is not able to ask for delivery confirmation
if(!($gCurrentUser->getValue('usr_id') > 0 && $gPreferences['mail_delivery_confirmation'] == 2) && $gPreferences['mail_delivery_confirmation'] != 1)
{
    $postDeliveryConfirmation = 0;
}

//$receiver = array();
foreach ($user_array as $userId)
{
    // Create new Email Object
    $email = new Email();

    $user = new User($gDb, $gProfileFields, $userId);

    // save page in navigation - to have a check for a navigation back.
    $gNavigation->addUrl(CURRENT_URL);
    $postTo = '';

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

    //Datensatz fuer E-Mail-Adresse zusammensetzen
    if(strlen($user->getValue('DEBTOR')) > 0)
    {
        if(strlen($user->getValue('DEBTOR_EMAIL')) > 0)
        {
            $postTo = $user->getValue('DEBTOR_EMAIL');
        }
        $empfaenger =   $user->getValue('DEBTOR');

    }
    else
    {
        if(strlen($user->getValue('EMAIL')) > 0)
        {
            $postTo = $user->getValue('EMAIL');
        }
        $empfaenger = $user->getValue('FIRST_NAME').' '.$user->getValue('LAST_NAME');

    }

    if (!strValidCharacters($postTo, 'email'))
    {
        $sendMailResultMissingEmail[] = $empfaenger;
        continue;
    }

    // evtl. definierte Parameter ersetzen
    $postSubject = replace_emailparameter($postSubjectOrig, $user);
    $postBody = replace_emailparameter($postBodyOrig, $user);

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

    //den gefundenen User dem Mailobjekt hinzufuegen...
    $email->addRecipient($postTo, $empfaenger);

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
    $sendMailResult = $email->sendEmail();

    if ($sendMailResult === TRUE)
    {
        $sendMailResultSendOK[] = $empfaenger.' ('.$postTo.')';
    }
    else
    {
        if(strlen($postTo) > 0)
        {
            $sendMailResultAnotherError[] = $sendMailResult.$empfaenger;
        }
    }
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

// zur Ausgangsseite zurueck
$gMessage->setForwardUrl($gNavigation->getPreviousUrl(), 0);
$gMessage->show($sendMailResultMessage);
