<?php
/**
 ***********************************************************************************************
 * Neuzuordnung von Mitgliedern
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 * 
 ***********************************************************************************************
 */

/**
 * ****************************************************************************
 * Parameters:
 *
 * mode :
 * preview - preview of the new remapping
 * save - save the new remapping
 * print - preview for printing
 *
 * ***************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Roles\Entity\Membership;
use Admidio\Roles\Entity\Role;
use Admidio\UI\Component\DataTables;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Admidio\Users\Entity\User;
use Plugins\MembershipFee\classes\Config\ConfigTable;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');

    // only authorized user are allowed to start this module
    if (! isUserAuthorized()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    // Initialize and check the parameters
    $getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array(
        'defaultValue' => 'preview',
        'validValues' => array(
            'preview',
            'save',
            'print'
        )
    ));

    $pPreferences = new ConfigTable();
    $pPreferences->read();

    $user = new User($gDb, $gProfileFields);
    $role = new Role($gDb);

    // set headline of the script
    $headline = $gL10n->get('PLG_MEMBERSHIPFEE_REMAPPING_AGE_STAGGERED_ROLES');

    $gNavigation->addUrl(CURRENT_URL, $headline);

    if ($getMode == 'preview') // Default
    {
        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-remapping-preview');
        $page->setContentFullWidth();
        $page->setHeadline($headline);

        // Vor der Neuzuordnung die altersgestaffelten Rollen auf Luecken oder Ueberlappungen pruefen
        $arr = check_rols();
        if (! in_array($gL10n->get('PLG_MEMBERSHIPFEE_AGE_STAGGERED_ROLES_RESULT_OK'), $arr)) {
            throw new Exception('PLG_MEMBERSHIPFEE_AGE_STAGGERED_ROLES_RESULT_ERROR2');
        }
        unset($arr);

        $stack = array();
        $members = array();

        // alle Altersrollen einlesen
        $altersrollen = beitragsrollen_einlesen('alt', array(
            'FIRST_NAME',
            'LAST_NAME',
            'BIRTHDAY'
        ));

        // alle Altersrollen durchlaufen
        foreach ($altersrollen as $roleId => $roldata) {
            foreach ($altersrollen[$roleId]['members'] as $member => $memberdata) {
                if (strlen($memberdata['BIRTHDAY']) === 0) {
                    $user->readDataById($member);

                    throw new Exception('<strong>' . $gL10n->get('SYS_ERROR') . ':</strong> ' . $gL10n->get('PLG_MEMBERSHIPFEE_REMAPPING_MISSING_BIRTHDAY', array(
                        '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
                            'user_uuid' => $user->getValue('usr_uuid')
                        )) . '">' . $memberdata['FIRST_NAME'] . '</a>',
                        '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
                            'user_uuid' => $user->getValue('usr_uuid')
                        )) . '">' . $memberdata['LAST_NAME'] . '</a>'
                    )));
                }

                // das Alter des Mitglieds am Stichtag bestimmen
                $deadline = getDeadline($pPreferences->config['Altersrollen']['altersrollen_offset']);
                $age = ageCalculator(strtotime($memberdata['BIRTHDAY']), strtotime($deadline));

                // ist das Alter des Mitglieds außerhalb des Altersschemas der Rolle
                if (($age < $roldata['von']) || ($age > $roldata['bis'])) {
                    // wenn ja, dann Mitglied auf den Stack legen
                    $stack[] = array(
                        'LAST_NAME' => $memberdata['LAST_NAME'],
                        'FIRST_NAME' => $memberdata['FIRST_NAME'],
                        'user_id' => $member,
                        'alter' => $age,
                        'alterstyp' => $roldata['alterstyp']
                    );

                    $members[] = array(
                        'LAST_NAME' => $memberdata['LAST_NAME'],
                        'FIRST_NAME' => $memberdata['FIRST_NAME'],
                        'user_id' => $member,
                        'role_id' => $roleId,
                        'role' => $roldata['rolle'],
                        'age' => $age,
                        'toDo' => 'delete',
                        'icon_role_not_exist' => '&nbsp;',
                        'icon_role_new' => '&nbsp;',
                        'icon_role_old' => '<i class="bi bi-dash" alt="' . $gL10n->get('PLG_MEMBERSHIPFEE_OLD_ROLE') . '"></i>'
                    );
                }
            }
        }

        // wenn ein Mitglied Angehoeriger mehrerer Rollen war (duerfte eigentlich gar nicht vorkommen),
        // dann wurde er auch mehrfach in das Array $stack aufgenommen
        // --> doppelte Vorkommen loeschen
        $stack = array_map('unserialize', array_unique(array_map('serialize', $stack)));

        // den Stack abarbeiten
        foreach ($stack as $key => $stackdata) {
            // alle Altersrollen durchlaufen und pruefen, ob das Mitglied in das Altersschema der Rolle passt
            foreach ($altersrollen as $roleId => $roldata) {
                if (($stackdata['alter'] <= $roldata['bis']) && ($stackdata['alter'] >= $roldata['von']) && ($stackdata['alterstyp'] == $roldata['alterstyp']) && ! array_key_exists($stackdata['user_id'], $roldata['members'])) {
                    // das Mitglied passt in das Altersschema der Rolle und das Kennzeichen dieser Altersstaffelung passt auch
                    $members[] = array(
                        'LAST_NAME' => $stackdata['LAST_NAME'],
                        'FIRST_NAME' => $stackdata['FIRST_NAME'],
                        'user_id' => $stackdata['user_id'],
                        'role_id' => $roleId,
                        'role' => $roldata['rolle'],
                        'age' => $stackdata['alter'],
                        'toDo' => 'set',
                        'icon_role_not_exist' => '&nbsp;',
                        'icon_role_new' => '<i class="bi bi-plus" alt="' . $gL10n->get('PLG_MEMBERSHIPFEE_NEW_ROLE') . '"></i>',
                        'icon_role_old' => '&nbsp;'
                    );

                    unset($stack[$key]);
                }
            }
        }

        if (count($stack) > 0) {
            foreach ($stack as $stackdata) {
                $members[] = array(
                    'LAST_NAME' => $stackdata['LAST_NAME'],
                    'FIRST_NAME' => $stackdata['FIRST_NAME'],
                    'user_id' => $stackdata['user_id'],
                    'role_id' => '',
                    'role' => '',
                    'age' => $stackdata['alter'],
                    'toDo' => '',
                    'icon_role_not_exist' => '<i class="bi bi-exclamation" alt="' . $gL10n->get('PLG_MEMBERSHIPFEE_NEW_ROLE_MISSING') . '"></i>',
                    'icon_role_new' => '&nbsp;',
                    'icon_role_old' => '&nbsp;'
                );
            }
        }

        // save members in session (for mode write and mode print)
        $_SESSION['pMembershipFee']['remapping_user'] = $members;

        $table = new DataTables($page, 'table_preview_remapping');
        $table->setRowsPerPage(10);

        // data array
        $data = array(
            'headers' => array(),
            'rows' => array(),
            'column_align' => array(),
            'column_width' => array()
        );

        $data['column_align'] = array(
            'left',
            'left',
            'center',
            'center',
            'center',
            'center',
            'left'
        );

        $data['headers'] = array(
            $gL10n->get('SYS_LASTNAME'),
            $gL10n->get('SYS_FIRSTNAME'),
            '<i class="bi bi-cake2" data-bs-toggle="tooltip" title="' . $gL10n->get('PLG_MEMBERSHIPFEE_AGE_DESC') . '"></i>',
            '<i class="bi bi-dash" data-bs-toggle="tooltip" title="' . $gL10n->get('PLG_MEMBERSHIPFEE_OLD_ROLE_DESC') . '"></i>',
            '<i class="bi bi-plus" data-bs-toggle="tooltip" title="' . $gL10n->get('PLG_MEMBERSHIPFEE_NEW_ROLE_DESC') . '"></i>',
            '<i class="bi bi-exclamation" data-bs-toggle="tooltip" title="' . $gL10n->get('PLG_MEMBERSHIPFEE_NEW_ROLE_MISSING_DESC') . '"></i>',
            $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_NAME')
        );

        $data['column_width'] = array(
            '20%',
            '20%',
            '5%',
            '5%',
            '5%',
            '5%',
            '40%'
        );

        $listRowNumber = 1;
        foreach ($members as $memberdata) {
            $user->readDataById((int) $memberdata['user_id']);
            $role->readDataById((int) $memberdata['role_id']);

            $columnValues = array();
            $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
                'user_uuid' => $user->getValue('usr_uuid')
            )) . '">' . $memberdata['LAST_NAME'] . '</a>';
            $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
                'user_uuid' => $user->getValue('usr_uuid')
            )) . '">' . $memberdata['FIRST_NAME'] . '</a>';
            $columnValues[] = $memberdata['age'];
            $columnValues[] = $memberdata['icon_role_old'];
            $columnValues[] = $memberdata['icon_role_new'];
            $columnValues[] = $memberdata['icon_role_not_exist'];
            $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/groups-roles/groups_roles.php', array(
                'mode' => 'edit',
                'role_uuid' => $role->getValue('rol_uuid')
            )) . '">' . $memberdata['role'] . '</a>';

            $data['rows'][] = array(
                'id' => 'row-' . $listRowNumber,
                'data' => $columnValues
            );

            ++ $listRowNumber;
        }

        $form = new FormPresenter('remapping_form', '../templates/remapping.preview.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/remapping.php', array(
            'mode' => 'save'
        )), $page);

        $form->addSubmitButton('btn_next_page', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg'
        ));

        $table->createJavascript(count($data['rows']), count($data['headers']));
        $table->setColumnAlignByArray($data['column_align']);

        $smarty = $page->createSmartyObject();
        $smarty->assign('l10n', $gL10n);
        $smarty->assign('classTable', 'table table-condensed table-hover');

        $smarty->assign('columnAlign', $data['column_align']);
        $smarty->assign('columnWidth', $data['column_width']);
        $smarty->assign('headers', $data['headers']);
        $smarty->assign('rows', $data['rows']);

        $form->addToSmarty($smarty);

        // Fetch the HTML table from our Smarty template
        $htmlTable = $smarty->fetch('../templates/remapping.preview.plugin.membershipfee.tpl');
        // add table list to the page
        $page->addHtml($htmlTable);

        $page->show();
    } elseif ($getMode == 'save') {
        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-remapping-save');
        $page->setContentFullWidth();
        $page->setHeadline($headline);

        $page->addPageFunctionsMenuItem('menu_item_print_view', $gL10n->get('SYS_PRINT_PREVIEW'), 'javascript:void(0);', 'bi-printer');

        $tablemember = new Membership($gDb);
        $sql = '';

        $page->addJavascript('
    	$("#menu_item_print_view").click(function() {
            window.open("' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/remapping.php', array(
            'mode' => 'print'
        )) . '", "_blank");
        });', true);

        $table = new DataTables($page, 'table_save_remapping');
        $table->setRowsPerPage(10);

        // data array
        $data = array(
            'headers' => array(),
            'rows' => array(),
            'column_align' => array(),
            'column_width' => array()
        );

        $data['column_align'] = array(
            'left',
            'left',
            'center',
            'center',
            'center',
            'center',
            'left'
        );

        $data['headers'] = array(
            $gL10n->get('SYS_LASTNAME'),
            $gL10n->get('SYS_FIRSTNAME'),
            '<i class="bi bi-cake2" data-bs-toggle="tooltip" title="' . $gL10n->get('PLG_MEMBERSHIPFEE_AGE_DESC') . '"></i>',
            '<i class="bi bi-dash" data-bs-toggle="tooltip" title="' . $gL10n->get('PLG_MEMBERSHIPFEE_OLD_ROLE_DESC') . '"></i>',
            '<i class="bi bi-plus" data-bs-toggle="tooltip" title="' . $gL10n->get('PLG_MEMBERSHIPFEE_NEW_ROLE_DESC') . '"></i>',
            '<i class="bi bi-exclamation" data-bs-toggle="tooltip" title="' . $gL10n->get('PLG_MEMBERSHIPFEE_NEW_ROLE_MISSING_DESC') . '"></i>',
            $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_NAME')
        );

        $data['column_width'] = array(
            '20%',
            '20%',
            '5%',
            '5%',
            '5%',
            '5%',
            '40%'
        );

        $listRowNumber = 1;
        foreach ($_SESSION['pMembershipFee']['remapping_user'] as $memberdata) {
            $user->readDataById((int) $memberdata['user_id']);
            $role->readDataById((int) $memberdata['role_id']);

            $columnValues = array();
            $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
                'user_uuid' => $user->getValue('usr_uuid')
            )) . '">' . $memberdata['LAST_NAME'] . '</a>';
            $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
                'user_uuid' => $user->getValue('usr_uuid')
            )) . '">' . $memberdata['FIRST_NAME'] . '</a>';
            $columnValues[] = $memberdata['age'];
            $columnValues[] = $memberdata['icon_role_old'];
            $columnValues[] = $memberdata['icon_role_new'];
            $columnValues[] = $memberdata['icon_role_not_exist'];
            $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/groups-roles/groups_roles.php', array(
                'mode' => 'edit',
                'role_uuid' => $role->getValue('rol_uuid')
            )) . '">' . $memberdata['role'] . '</a>';

            $data['rows'][] = array(
                'id' => 'row-' . $listRowNumber,
                'data' => $columnValues
            );

            ++ $listRowNumber;

            if ($memberdata['toDo'] == 'delete') {
                $value = date('Y-m-d', strtotime('-1 day'));
                $sql = 'UPDATE ' . TBL_MEMBERS . '
			 		   SET mem_end = ? -- $value
			 	     WHERE mem_usr_id = ? -- $data[\'user_id\']
				       AND mem_rol_id = ? -- $data[\'role_id\'] ';

                $queryParams = array(
                    $value,
                    $memberdata['user_id'],
                    $memberdata['role_id']
                );

                $gDb->queryPrepared($sql, $queryParams);

                // stopMembership() kann nicht verwendet werden, da es unter best. Umstaenden Mitgliedschaften nicht loescht
                // Beschreibung von stopMembership()
                // only stop membership if there is an actual membership
                // the actual date must be after the beginning
                // and the actual date must be before the end date
                // $tablemember->stopMembership( $roleId, $member);
            } elseif ($memberdata['toDo'] == 'set') {
                // das Mitglied passt in das Altersschema der Rolle und das Kennzeichen dieser Altersstaffelung passt auch
                $tablemember->startMembership($memberdata['role_id'], $memberdata['user_id']);
            }
        }

        $table->createJavascript(count($data['rows']), count($data['headers']));
        $table->setColumnAlignByArray($data['column_align']);

        $smarty = $page->createSmartyObject();
        $smarty->assign('l10n', $gL10n);
        $smarty->assign('classTable', 'table table-condensed table-hover');
        $smarty->assign('columnAlign', $data['column_align']);
        $smarty->assign('columnWidth', $data['column_width']);
        $smarty->assign('headers', $data['headers']);
        $smarty->assign('rows', $data['rows']);

        $htmlTable = $smarty->fetch('../templates/remapping.save.plugin.membershipfee.tpl');
        // add table list to the page
        $page->addHtml($htmlTable);

        $page->show();
    } elseif ($getMode == 'print') {
        // date must be formated
        $dateUnformat = DateTime::createFromFormat('Y-m-d', DATE_NOW);
        $date = $dateUnformat->format($gSettingsManager->getString('system_date'));

        $headline = $gL10n->get('PLG_MEMBERSHIPFEE_REMAPPING_SUMMARY', array(
            $date
        ));

        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-remapping-print');
        $page->setHeadline($headline);
        $page->setPrintMode();

        $table = new DataTables($page, 'table_print_remapping');

        // data array
        $data = array(
            'headers' => array(),
            'rows' => array(),
            'column_align' => array(),
            'column_width' => array()
        );

        $data['column_align'] = array(
            'left',
            'left',
            'center',
            'center',
            'center',
            'center',
            'left'
        );

        $data['headers'] = array(
            $gL10n->get('SYS_LASTNAME'),
            $gL10n->get('SYS_FIRSTNAME'),
            '<i class="bi bi-cake2" alt="' . $gL10n->get('PLG_MEMBERSHIPFEE_AGE') . '"></i>',
            '<i class="bi bi-dash" alt="' . $gL10n->get('PLG_MEMBERSHIPFEE_OLD_ROLE') . '"></i>',
            '<i class="bi bi-plus" alt="' . $gL10n->get('PLG_MEMBERSHIPFEE_NEW_ROLE') . '"></i>',
            '<i class="bi bi-exclamation" alt="' . $gL10n->get('PLG_MEMBERSHIPFEE_NEW_ROLE_MISSING') . '"></i>',
            $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_NAME')
        );

        $data['column_width'] = array(
            '15%',
            '15%',
            '5%',
            '5%',
            '5%',
            '5%',
            '50%'
        );

        $listRowNumber = 1;

        foreach ($_SESSION['pMembershipFee']['remapping_user'] as $memberdata) {
            $columnValues = array();
            $columnValues[] = $memberdata['LAST_NAME'];
            $columnValues[] = $memberdata['FIRST_NAME'];
            $columnValues[] = $memberdata['age'];
            $columnValues[] = $memberdata['icon_role_old'];
            $columnValues[] = $memberdata['icon_role_new'];
            $columnValues[] = $memberdata['icon_role_not_exist'];
            $columnValues[] = $memberdata['role'];
            // $table->addRowByArray($columnValues);

            $data['rows'][] = array(
                'id' => 'row-' . $listRowNumber,
                'data' => $columnValues
            );

            ++ $listRowNumber;
        }

        $table->createJavascript(count($data['rows']), count($data['headers']));
        $table->setColumnAlignByArray($data['column_align']);

        $smarty = $page->createSmartyObject();
        $smarty->assign('l10n', $gL10n);
        $smarty->assign('classTable', 'table table-condensed table-hover');
        $smarty->assign('columnAlign', $data['column_align']);
        $smarty->assign('columnWidth', $data['column_width']);
        $smarty->assign('headers', $data['headers']);
        $smarty->assign('rows', $data['rows']);

        // Fetch the HTML table from our Smarty template
        $htmlTable = $smarty->fetch('../templates/remapping.print.plugin.membershipfee.tpl');
        // add table list to the page
        $page->addHtml($htmlTable);

        $page->show();
    }
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
