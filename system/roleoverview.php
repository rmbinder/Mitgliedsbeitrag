<?php
/**
 ***********************************************************************************************
 * Rollenübersicht fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:  none
 *
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Roles\Entity\Role;
use Admidio\UI\Component\DataTables;
use Admidio\UI\Presenter\PagePresenter;
use Plugins\MembershipFee\classes\Config\ConfigTable;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');

    // only authorized user are allowed to start this module
    if (! isUserAuthorized()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    $pPreferences = new ConfigTable();
    $pPreferences->read();

    $role = new Role($gDb);

    $headline = $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_OVERVIEW');
    $gNavigation->addUrl(CURRENT_URL, $headline);

    $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-roleoverview');
    $page->setHeadline($headline);

    $table = new DataTables($page, 'table_role_overview');

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
        'right',
        'right'
    );

    $data['headers'] = array(
        $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_NAME'),
        'dummy',
        $gL10n->get('PLG_MEMBERSHIPFEE_MEMBER_ACCOUNT')
    );

    $data['column_width'] = array(
        '65%',
        '5%',
        '30%'
    );

    $rollen = beitragsrollen_einlesen('', array(
        'LAST_NAME'
    ));

    $listRowNumber = 1;
    foreach ($rollen as $rol_id => $memberdata) {
        $role->readDataById($rol_id);

        $columnValues = array();
        $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/groups-roles/groups_roles.php', array(
            'mode' => 'edit',
            'role_uuid' => $role->getValue('rol_uuid')
        )) . '">' . $memberdata['rolle'] . '</a>';
        $columnValues[] = expand_rollentyp($memberdata['rollentyp']);
        $columnValues[] = count($memberdata['members']);

        $data['rows'][] = array(
            'id' => 'row-' . $listRowNumber,
            'data' => $columnValues
        );

        ++ $listRowNumber;
    }
    $table->setGroupColumn(2);

    $table->createJavascript(count($data['rows']), count($data['headers']));
    $table->setColumnAlignByArray($data['column_align']);

    $smarty = $page->createSmartyObject();
    $smarty->assign('classTable', 'table table-condensed table-hover');
    $smarty->assign('columnAlign', $data['column_align']);
    $smarty->assign('columnWidth', $data['column_width']);
    $smarty->assign('headers', $data['headers']);
    $smarty->assign('rows', $data['rows']);

    $htmlTable = $smarty->fetch('../templates/roleoverview.plugin.membershipfee.tpl');
    // add table list to the page
    $page->addHtml($htmlTable);

    $page->show();
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
