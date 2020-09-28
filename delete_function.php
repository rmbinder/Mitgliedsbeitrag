<?php
/**
 ***********************************************************************************************
 * Funktionen der Deleteroutine
 *
 * @copyright 2004-2020 The Admidio Team
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
            $members = list_members(array('PAID'.ORG_ID, 'FEE'.ORG_ID, 'CONTRIBUTORY_TEXT'.ORG_ID, 'DUEDATE'.ORG_ID), 0);

            foreach ($members as $key => $data)
            {
                $user = new User($gDb, $gProfileFields, $key);

                if (!empty($data['DUEDATE'.ORG_ID])
                    &&  isset($_POST['duedate_only']))
                {
                    $user->setValue('DUEDATE'.ORG_ID, '');
                }

                if (!empty($data['PAID'.ORG_ID])
                    && (isset($_POST['with_paid'])
                        || isset($_POST['paid_only'])
                        || isset($_POST['delete_all'])))
                {
                    $user->setValue('PAID'.ORG_ID, '');
                }

                if (!empty($data['FEE'.ORG_ID])
                    && ((isset($_POST['with_paid']) && !empty($data['PAID'.ORG_ID]))
                        || (isset($_POST['without_paid'])&& empty($data['PAID'.ORG_ID]))
                        || isset($_POST['delete_all'])))
                {
                    $user->setValue('FEE'.ORG_ID, '');
                }

                if (!empty($data['CONTRIBUTORY_TEXT'.ORG_ID])
                    && ((isset($_POST['with_paid']) && !empty($data['PAID'.ORG_ID]))
                        || (isset($_POST['without_paid'])&& empty($data['PAID'.ORG_ID]))
                        || isset($_POST['delete_all'])))
                {
                    $user->setValue('CONTRIBUTORY_TEXT'.ORG_ID, '');
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
