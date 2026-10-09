<?php
/**
 ***********************************************************************************************
 * Dieses Plugin führt einen Abgleich durch zwischen den Einträgen von Beitrag, Beitragszeitraum
 * und Beschreibung von Familienrollen mit den Angaben in Einstellungen-Familienrollen.
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
 * mode : preview - preview of the familiy roles update
 * save - save the new values for cost, cost period and description
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

    $role = new Role($gDb);

    // set headline of the script
    $headline = $gL10n->get('PLG_MEMBERSHIPFEE_FAMILY_ROLES_UPDATE');

    $gNavigation->addUrl(CURRENT_URL, $headline);

    if ($getMode == 'preview') // Default
    {
        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-familyrolesupdate-preview');
        $page->setHeadline($headline);

        $page->setContentFullWidth();

        $familyRolesToUpdate = array();

        // alle Familienkonfigurationen durchlaufen
        foreach ($pPreferences->config['Familienrollen']['familienrollen_prefix'] as $key => $data) {
            // Familienrollen anhand des Präfix bestimmen
            $sql = 'SELECT rol_id, rol_name, rol_cost, rol_cost_period, rol_description
                FROM ' . TBL_ROLES . ', ' . TBL_CATEGORIES . '
				WHERE rol_valid  = true
                AND rol_name LIKE ?
				AND rol_cat_id = cat_id
            	AND ( cat_org_id = ?
                OR cat_org_id IS NULL ) ';

            $statement = $gDb->queryPrepared($sql, array(
                $data . '%',
                $gCurrentOrgId
            ));

            // die Einträge von Beitrag, Beitragszeitraum und Beschreibung auslesen und mit den Einträgen im Setup vergleichen
            while ($row = $statement->fetch()) {
                if (($row['rol_cost'] != $pPreferences->config['Familienrollen']['familienrollen_beitrag'][$key]) || ($row['rol_cost_period'] != $pPreferences->config['Familienrollen']['familienrollen_zeitraum'][$key]) || ($row['rol_description'] != $pPreferences->config['Familienrollen']['familienrollen_beschreibung'][$key])) {
                    $familyRolesToUpdate[$row['rol_id']] = array(
                        'rol_name' => $row['rol_name'],

                        'rol_cost_is' => $row['rol_cost'],
                        'rol_cost_shall' => $pPreferences->config['Familienrollen']['familienrollen_beitrag'][$key],
                        'rol_cost_update' => ($row['rol_cost'] != $pPreferences->config['Familienrollen']['familienrollen_beitrag'][$key] ? true : false),

                        'rol_cost_period_is' => $row['rol_cost_period'],
                        'rol_cost_period_shall' => $pPreferences->config['Familienrollen']['familienrollen_zeitraum'][$key],
                        'rol_cost_period_update' => ($row['rol_cost_period'] != $pPreferences->config['Familienrollen']['familienrollen_zeitraum'][$key] ? true : false),

                        'rol_description_is' => $row['rol_description'],
                        'rol_description_shall' => $pPreferences->config['Familienrollen']['familienrollen_beschreibung'][$key],
                        'rol_description_update' => ($row['rol_description'] != $pPreferences->config['Familienrollen']['familienrollen_beschreibung'][$key] ? true : false)
                    );
                }
            }
        }

        $table = new DataTables($page, 'table_preview_familyrolesupdate');

        $table->setRowsPerPage($gSettingsManager->getInt('groups_roles_members_per_page'));
        $table->setMessageIfNoRowsFound('SYS_NO_ENTRIES');

        // data array
        $data = array(
            'headers' => array(),
            'rows' => array(),
            'column_align' => array(),
            'column_width' => array()
        );

        $data['column_align'] = array(
            'left',
            'center',
            'center',
            'center',
            'center',
            'center',
            'center'
        );

        $data['headers'] = array(
            $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_NAME'),
            $gL10n->get('PLG_MEMBERSHIPFEE_IS') . ' ' . $gL10n->get('SYS_CONTRIBUTION'),
            $gL10n->get('PLG_MEMBERSHIPFEE_SHALL') . ' ' . $gL10n->get('SYS_CONTRIBUTION'),
            $gL10n->get('PLG_MEMBERSHIPFEE_IS') . ' ' . $gL10n->get('SYS_CONTRIBUTION_PERIOD'),
            $gL10n->get('PLG_MEMBERSHIPFEE_SHALL') . ' ' . $gL10n->get('SYS_CONTRIBUTION_PERIOD'),
            $gL10n->get('PLG_MEMBERSHIPFEE_IS') . ' ' . $gL10n->get('SYS_DESCRIPTION'),
            $gL10n->get('PLG_MEMBERSHIPFEE_SHALL') . ' ' . $gL10n->get('SYS_DESCRIPTION')
        );

        $data['column_width'] = array(
            '28%',
            '8%',
            '8%',
            '14%',
            '14%',
            '14%',
            '14%'
        );

        if (sizeof($familyRolesToUpdate) > 0) {

            // save new values in session (for mode write and mode print)
            $_SESSION['pMembershipFee']['familyroles_update'] = $familyRolesToUpdate;

            $listRowNumber = 1;
            foreach ($familyRolesToUpdate as $rol_id => $memberdata) {
                $role->readDataById($rol_id);

                // Sonderfall absichern, wenn rol_cost_period_is oder rol_cost_period_shall nicht gesetzt, also null ist
                $rol_cost_period_is = $memberdata['rol_cost_period_is'] !== null ? Role::getCostPeriods($memberdata['rol_cost_period_is']) : '';
                $rol_cost_period_shall = $memberdata['rol_cost_period_shall'] !== null ? Role::getCostPeriods($memberdata['rol_cost_period_shall']) : '';

                $columnValues = array();
                $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/groups-roles/groups_roles.php', array(
                    'mode' => 'edit',
                    'role_uuid' => $role->getValue('rol_uuid')
                )) . '">' . $memberdata['rol_name'] . '</a>';
                $columnValues[] = ($memberdata['rol_cost_update'] ? '<strong>' . $memberdata['rol_cost_is'] . '</strong>' : $memberdata['rol_cost_is']);
                $columnValues[] = ($memberdata['rol_cost_update'] ? '<strong>' . $memberdata['rol_cost_shall'] . '</strong>' : $memberdata['rol_cost_shall']);
                $columnValues[] = ($memberdata['rol_cost_period_update'] ? '<strong>' . $rol_cost_period_is . '</strong>' : $rol_cost_period_is);
                $columnValues[] = ($memberdata['rol_cost_period_update'] ? '<strong>' . $rol_cost_period_shall . '</strong>' : $rol_cost_period_shall);
                $columnValues[] = ($memberdata['rol_description_update'] ? '<strong>' . $memberdata['rol_description_is'] . '</strong>' : $memberdata['rol_description_is']);
                $columnValues[] = ($memberdata['rol_description_update'] ? '<strong>' . $memberdata['rol_description_shall'] . '</strong>' : $memberdata['rol_description_shall']);

                $data['rows'][] = array(
                    'id' => 'row-' . $listRowNumber,
                    'data' => $columnValues
                );

                ++ $listRowNumber;
            }
        }

        $form = new FormPresenter('familyrolesupdate_preview_form', '../templates/familyrolesupdate.preview.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/familyroles_update.php', array(
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

        $form->addToSmarty($smarty);

        $htmlTable = $smarty->fetch('../templates/familyrolesupdate.preview.plugin.membershipfee.tpl');
        $page->addHtml($htmlTable);

        $page->show();
    } elseif ($getMode == 'save') {

        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-familyrolesupdate-save');

        $page->setContentFullWidth();

        $page->addPageFunctionsMenuItem('menu_item_print_view', $gL10n->get('SYS_PRINT_PREVIEW'), 'javascript:void(0);', 'bi-printer');

        $page->addJavascript('
    	$("#menu_item_print_view").click(function() {
            window.open("' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/familyroles_update.php', array(
            'mode' => 'print'
        )) . '", "_blank");
        });', true);

        $table = new DataTables($page, 'table_preview_familyrolesupdate');

        $table->setRowsPerPage($gSettingsManager->getInt('groups_roles_members_per_page'));
        $table->setMessageIfNoRowsFound('SYS_NO_ENTRIES');

        // data array
        $data = array(
            'headers' => array(),
            'rows' => array(),
            'column_align' => array(),
            'column_width' => array()
        );

        $data['column_align'] = array(
            'left',
            'center',
            'center',
            'center'
        );

        $data['headers'] = array(
            $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_NAME'),
            $gL10n->get('SYS_CONTRIBUTION'),
            $gL10n->get('SYS_CONTRIBUTION_PERIOD'),
            $gL10n->get('SYS_DESCRIPTION')
        );

        $data['column_width'] = array(
            '40%',
            '20%',
            '20%',
            '20%'
        );

        $listRowNumber = 1;

        foreach ($_SESSION['pMembershipFee']['familyroles_update'] as $rol_id => $memberdata) {
            $role->readDataById($rol_id);

            if ($memberdata['rol_cost_update']) {
                $role->setvalue('rol_cost', $memberdata['rol_cost_shall']);
            }
            if ($memberdata['rol_cost_period_update']) {
                $role->setvalue('rol_cost_period', $memberdata['rol_cost_period_shall']);
            }
            if ($memberdata['rol_description_update']) {
                $role->setvalue('rol_description', $memberdata['rol_description_shall']);
            }
            $role->save();

            $columnValues = array(
                '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/groups-roles/groups_roles.php', array(
                    'mode' => 'edit',
                    'role_uuid' => $role->getValue('rol_uuid')
                )) . '">' . $memberdata['rol_name'] . '</a>',
                $role->getValue('rol_cost'),
                ($role->getValue('rol_cost_period') !== null ? Role::getCostPeriods($role->getValue('rol_cost_period')) : ''),
                $role->getValue('rol_description')
            );

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

        $htmlTable = $smarty->fetch('../templates/familyrolesupdate.save.plugin.membershipfee.tpl');
        $page->addHtml($htmlTable);

        $page->show();
    } elseif ($getMode == 'print') {

        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-familyrolesupdate-print');

        $page->setHeadline($headline);
        $page->setPrintMode();

        $table = new DataTables($page, 'table_print_familyrolesupdate');

        $table->setRowsPerPage($gSettingsManager->getInt('groups_roles_members_per_page'));
        $table->setMessageIfNoRowsFound('SYS_NO_ENTRIES');

        // data array
        $data = array(
            'headers' => array(),
            'rows' => array(),
            'column_align' => array(),
            'column_width' => array()
        );

        $data['column_align'] = array(
            'left',
            'center',
            'center',
            'center'
        );

        $data['headers'] = array(
            $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_NAME'),
            $gL10n->get('SYS_CONTRIBUTION'),
            $gL10n->get('SYS_CONTRIBUTION_PERIOD'),
            $gL10n->get('SYS_DESCRIPTION')
        );

        $data['column_width'] = array(
            '40%',
            '20%',
            '20%',
            '20%'
        );

        $listRowNumber = 1;
        foreach ($_SESSION['pMembershipFee']['familyroles_update'] as $rol_id => $memberdata) {
            $role->readDataById($rol_id);

            $columnValues = array(
                $memberdata['rol_name'],
                $role->getValue('rol_cost'),
                ($role->getValue('rol_cost_period') !== null ? Role::getCostPeriods($role->getValue('rol_cost_period')) : ''),
                $role->getValue('rol_description')
            );

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

        $htmlTable = $smarty->fetch('../templates/familyrolesupdate.print.plugin.membershipfee.tpl');
        $page->addHtml($htmlTable);

        $page->show();
    }
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}

