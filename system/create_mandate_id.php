<?php
/**
 ***********************************************************************************************
 * Dieses Plugin erzeugt Mandatsreferenzen.
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
 * mode : preview - preview of the new mandate ids
 * write - save the new mandate ids
 * print - preview for printing
 *
 * ***************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\SecurityUtils;
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

    $getCreateMandateIdRoleSelection = admFuncVariableIsValid($_GET, 'createmandateid_roleselection', 'array');

    // wenn alle Einträge in einer Rollenauswahl gelöscht wurden, wird ein Array mit einem leeren Eintrag übergeben
    // dies führt im weiteren Verlauf des Scripts zu Fehlern
    if (is_array($getCreateMandateIdRoleSelection)) {
        // zuerst mal leere Einträge löschen
        $getCreateMandateIdRoleSelection = array_filter($getCreateMandateIdRoleSelection);
    }
    $pPreferences = new ConfigTable();
    $pPreferences->read();

    $user = new User($gDb, $gProfileFields);

    // set headline of the script
    $headline = $gL10n->get('PLG_MEMBERSHIPFEE_CREATE_MANDATE_ID');

    $gNavigation->addUrl(CURRENT_URL, $headline);

    if ($getMode == 'preview') // Default
    {
        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-create-mandate-id-preview');
        $page->setHeadline($headline);

        $form = new FormPresenter('createmandateid_navbar', '../templates/createmandateid.navbar.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/create_mandate_id.php'), $page, array(
            'type' => 'navbar',
            'setFocus' => false
        ));

        $selectBoxEntriesAlleRollen = 'SELECT rol_id, rol_name, cat_name
          						 FROM ' . TBL_ROLES . '
    					   INNER JOIN ' . TBL_CATEGORIES . '
                                   ON cat_id = rol_cat_id
                                WHERE rol_valid   = true
                                  AND (  cat_org_id  = ' . $gCurrentOrgId . '
                                   OR cat_org_id IS NULL )
                             ORDER BY cat_sequence, rol_name';

        $form->addSelectBoxFromSql('createmandateid_roleselection', '', $gDb, $selectBoxEntriesAlleRollen, array(
            'defaultValue' => $getCreateMandateIdRoleSelection,
            'showContextDependentFirstEntry' => false,
            'helpTextId' => 'PLG_MEMBERSHIPFEE_CREATE_MANDATE_ID_DESC',
            'multiselect' => true
        ));

        $form->addSubmitButton('btn_createmandateid', $gL10n->get('SYS_OK'), array(
            'icon' => 'bi-calculator'
        ));

        $form->addToHtmlPage();
        $referenz = '';
        $errorMarker = false;
        $members = array();

        // pruefen, ob Eintraege in der Rollenauswahl bestehen
        if (is_array($getCreateMandateIdRoleSelection) && count($getCreateMandateIdRoleSelection) > 0) {

            $_SESSION['pMembershipFee']['createmandateid_rol_sel'] = $getCreateMandateIdRoleSelection;

            // Rollenwahl ist vorhanden, deshalb Daten aufbereiten fuer list_members
            $rols = array();
            $role = new Role($gDb);
            foreach ($getCreateMandateIdRoleSelection as $rol_id) {
                $role->readDataById($rol_id);
                $rols[$role->getValue('rol_name')] = 0;
            }
        } else {
            $rols = 0;
            unset($_SESSION['pMembershipFee']['createmandateid_rol_sel']);
        }

        if ($pPreferences->config['Mandatsreferenz']['data_field'] != '-- User_ID --') {
            $members = list_members(array(
                'LAST_NAME',
                'FIRST_NAME',
                'DEBTOR',
                'MANDATEID' . $gCurrentOrgId,
                'FEE' . $gCurrentOrgId,
                'CONTRIBUTORY_TEXT' . $gCurrentOrgId,
                'IBAN',
                $pPreferences->config['Mandatsreferenz']['data_field']
            ), $rols);
        } else {
            $members = list_members(array(
                'LAST_NAME',
                'FIRST_NAME',
                'DEBTOR',
                'MANDATEID' . $gCurrentOrgId,
                'FEE' . $gCurrentOrgId,
                'CONTRIBUTORY_TEXT' . $gCurrentOrgId,
                'IBAN'
            ), $rols);
        }

        // alle Mitglieder loeschen, bei denen keine IBAN vorhanden ist
        $members = array_filter($members, 'delete_without_IBAN');

        // alle Mitglieder loeschen, bei denen bereits eine Mandatsreferenz vorhanden ist
        $members = array_filter($members, 'delete_with_MANDATEID');

        // alle Beitragsrollen einlesen
        $contributingRolls = beitragsrollen_einlesen('fam', array(
            'FIRST_NAME',
            'LAST_NAME'
        ));

        // alle uebriggebliebenen Mitglieder durchlaufen und eine Mandatsreferenz erzeugen
        foreach ($members as $member => $memberdata) {
            $prefix = $pPreferences->config['Mandatsreferenz']['prefix_mem'];

            // wenn 'DEBTOR' nicht leer ist, dann gibt es einen Zahlungspflichtigen
            if ($memberdata['DEBTOR'] != '') {
                $prefix = $pPreferences->config['Mandatsreferenz']['prefix_pay'];
            }

            foreach ($contributingRolls as $role) {
                if (array_key_exists($member, $role['members'])) {
                    $prefix = $pPreferences->config['Mandatsreferenz']['prefix_fam'];
                    break;
                }
            }

            if ($pPreferences->config['Mandatsreferenz']['data_field'] != '-- User_ID --') {
                $suffix = str_replace(' ', '', replace_sepadaten($memberdata[$pPreferences->config['Mandatsreferenz']['data_field']]));
            } else {
                $suffix = $member;
            }

            $referenz = substr(str_pad($prefix, $pPreferences->config['Mandatsreferenz']['min_length'] - strlen($suffix), '0') . $suffix, 0, 35);

            if (! empty($suffix)) {
                $members[$member]['referenz'] = $referenz;
            } else {
                $members[$member]['referenz'] = $gL10n->get('PLG_MEMBERSHIPFEE_CREATE_MANDATE_ID_ERROR');
                $errorMarker = true;
            }
        }

        // save members with new mandate id in session (for mode save and mode print)
        $_SESSION['pMembershipFee']['createmandateid_user'] = $members;

        $table = new DataTables($page, 'table_new_createmandateids');

        $table->setRowsPerPage($gSettingsManager->getInt('groups_roles_members_per_page'));

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
            'center'
        );

        $data['headers'] = array(
            $gL10n->get('SYS_LASTNAME'),
            $gL10n->get('SYS_FIRSTNAME'),
            $gL10n->get('PLG_MEMBERSHIPFEE_CREATE_MANDATE_ID_NEW')
        );

        $data['column_width'] = array(
            '25%',
            '25%',
            '50%'
        );

        $listRowNumber = 1;
        foreach ($members as $member => $memberdata) {
            $user->readDataById($member);

            $columnValues = array();
            $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
                'user_uuid' => $user->getValue('usr_uuid')
            )) . '">' . $memberdata['LAST_NAME'] . '</a>';
            $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
                'user_uuid' => $user->getValue('usr_uuid')
            )) . '">' . $memberdata['FIRST_NAME'] . '</a>';
            $columnValues[] = $memberdata['referenz'];

            $data['rows'][] = array(
                'id' => 'row-' . $listRowNumber,
                'data' => $columnValues
            );

            ++ $listRowNumber;
        }

        $form = new FormPresenter('createmandateid_form', '../templates/createmandateid.preview.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/create_mandate_id.php', array(
            'mode' => 'save'
        )), $page);

        $form->addSubmitButton('btn_next_page', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'btn-primary'
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
        $smarty->assign('errorMarker', $errorMarker);

        $form->addToSmarty($smarty);

        // Fetch the HTML table from our Smarty template
        $htmlTable = $smarty->fetch('../templates/createmandateid.preview.plugin.membershipfee.tpl');
        // add table list to the page
        $page->addHtml($htmlTable);

        $page->show();
    } elseif ($getMode == 'save') {

        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-create-mandate-id-save');
        $page->setHeadline($headline);

        $page->addPageFunctionsMenuItem('menu_item_print_view', $gL10n->get('SYS_PRINT_PREVIEW'), 'javascript:void(0);', 'bi-printer');

        $page->addJavascript('
    	$("#menu_item_print_view").click(function() {
            window.open("' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/create_mandate_id.php', array(
            'mode' => 'print'
        )) . '", "_blank");
        });', true);

        $table = new DataTables($page, 'table_save_createmandateids');

        $table->setRowsPerPage($gSettingsManager->getInt('groups_roles_members_per_page'));

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
            'center'
        );

        $data['headers'] = array(
            $gL10n->get('SYS_LASTNAME'),
            $gL10n->get('SYS_FIRSTNAME'),
            $gL10n->get('PLG_MEMBERSHIPFEE_CREATE_MANDATE_ID_NEW')
        );

        $data['column_width'] = array(
            '33%',
            '33%',
            '34%'
        );

        $listRowNumber = 1;

        foreach ($_SESSION['pMembershipFee']['createmandateid_user'] as $member => $memberdata) {
            $user->readDataById($member);

            $columnValues = array();
            $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
                'user_uuid' => $user->getValue('usr_uuid')
            )) . '">' . $memberdata['LAST_NAME'] . '</a>';
            $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
                'user_uuid' => $user->getValue('usr_uuid')
            )) . '">' . $memberdata['FIRST_NAME'] . '</a>';
            $columnValues[] = $memberdata['referenz'];

            $data['rows'][] = array(
                'id' => 'row-' . $listRowNumber,
                'data' => $columnValues
            );

            ++ $listRowNumber;

            $user->setValue('MANDATEID' . $gCurrentOrgId, $memberdata['referenz']);
            $user->save();
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

        $htmlTable = $smarty->fetch('../templates/createmandateid.save.plugin.membershipfee.tpl');
        // add table list to the page
        $page->addHtml($htmlTable);

        $page->show();
    } elseif ($getMode == 'print') {

        $headline = $gL10n->get('PLG_MEMBERSHIPFEE_CREATE_MANDATE_IDS_NEW');

        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-createmandateid-print');

        $page->setHeadline($headline);
        $page->setPrintMode();

        $table = new DataTables($page, 'table_print_createmandateid');
        $table->setRowsPerPage($gSettingsManager->getInt('groups_roles_members_per_page'));

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
            'center'
        );

        $data['headers'] = array(
            $gL10n->get('SYS_LASTNAME'),
            $gL10n->get('SYS_FIRSTNAME'),
            $gL10n->get('PLG_MEMBERSHIPFEE_CREATE_MANDATE_ID_NEW')
        );

        $data['column_width'] = array(
            '33%',
            '33%',
            '34%'
        );

        $listRowNumber = 1;

        foreach ($_SESSION['pMembershipFee']['createmandateid_user'] as $memberdata) {
            $columnValues = array();
            $columnValues[] = $memberdata['LAST_NAME'];
            $columnValues[] = $memberdata['FIRST_NAME'];
            $columnValues[] = $memberdata['referenz'];

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
        $htmlTable = $smarty->fetch('../templates/createmandateid.print.plugin.membershipfee.tpl');
        // add table list to the page
        $page->addHtml($htmlTable);

        $page->show();
    }
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
