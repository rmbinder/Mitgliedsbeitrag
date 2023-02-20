<?php
/**
 ***********************************************************************************************
 * Funktionen der Deleteroutine
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * form         : The name of the form that were submitted.
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');

// Initialize and check the parameters
$getForm = admFuncVariableIsValid($_GET, 'form', 'string');

$gMessage->showHtmlTextOnly(true);

$ret_message = 'success';

try
{
    switch($getForm)
    {
        case 'delete':
            $members = array();
            $members = list_members(array('PAID'.$gCurrentOrgId, 'FEE'.$gCurrentOrgId, 'CONTRIBUTORY_TEXT'.$gCurrentOrgId, 'DUEDATE'.$gCurrentOrgId), 0);

            foreach ($members as $key => $data)
            {
                $user = new User($gDb, $gProfileFields, $key);

                if (!empty($data['DUEDATE'.$gCurrentOrgId])
                    &&  isset($_POST['duedate_only']))
                {
                    $user->setValue('DUEDATE'.$gCurrentOrgId, '');
                }

                if (!empty($data['PAID'.$gCurrentOrgId])
                    && (isset($_POST['with_paid'])
                        || isset($_POST['paid_only'])
                        || isset($_POST['delete_all'])))
                {
                    $user->setValue('PAID'.$gCurrentOrgId, '');
                }

                if (!empty($data['FEE'.$gCurrentOrgId])
                    && ((isset($_POST['with_paid']) && !empty($data['PAID'.$gCurrentOrgId]))
                        || (isset($_POST['without_paid'])&& empty($data['PAID'.$gCurrentOrgId]))
                        || isset($_POST['delete_all'])))
                {
                    $user->setValue('FEE'.$gCurrentOrgId, '');
                }

                if (!empty($data['CONTRIBUTORY_TEXT'.$gCurrentOrgId])
                    && ((isset($_POST['with_paid']) && !empty($data['PAID'.$gCurrentOrgId]))
                        || (isset($_POST['without_paid'])&& empty($data['PAID'.$gCurrentOrgId]))
                        || isset($_POST['delete_all'])))
                {
                    $user->setValue('CONTRIBUTORY_TEXT'.$gCurrentOrgId, '');
                }
                $user->save();
            }
            $ret_message = 'delete';
            break;

        default:
            $gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
    }
}
catch(AdmException $e)
{
    $e->showText();
}

echo $ret_message;
