<?php
/**
 ***********************************************************************************************
 * Beitragsanalyse fuer das Admidio-Plugin Mitgliedsbeitrag
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

    $headline = $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_ANALYSIS');
    $gNavigation->addUrl(CURRENT_URL, $headline);

    $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-remapping-preview');
    $page->setHeadline($headline);

    $beitrag = analyse_mem();
    $sum = 0;

    $table = new DataTables($page, 'table_members_contribution');
    $table->setRowsPerPage($gSettingsManager->getInt('groups_roles_members_per_page'));
    $table->disableColumnsSort(array(
        1,
        2,
        3,
        4,
        5,
        6,
        7
    ));

    // data array
    $data = array(
        'rows' => array(),
        'column_align' => array(),
        'column_width' => array()
    );

    $data['column_align'] = array(
        'left',
        'right',
        'right',
        'right',
        'right',
        'right',
        'right'
    );

    $data['column_width'] = array(
        '40%',
        '10%',
        '10%',
        '10%',
        '10%',
        '10%',
        '10%'
    );

    $columnValues = array();
    $columnValues[] = '';
    $columnValues[] = $gL10n->get('SYS_CONTRIBUTION');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_NUMBER');
    $columnValues[] = $gL10n->get('SYS_CONTRIBUTION');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_NUMBER');
    $columnValues[] = $gL10n->get('SYS_CONTRIBUTION');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_NUMBER');

    $data['rows'][] = array(
        'id' => 'row-1',
        'data' => $columnValues
    );

    $columnValues = array();
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_DUES');
    $columnValues[] = $beitrag['BEITRAG_kto'] . ' ' . $gSettingsManager->getString('system_currency');
    $columnValues[] = $beitrag['BEITRAG_kto_anzahl'];
    $columnValues[] = $beitrag['BEITRAG_rech'] . ' ' . $gSettingsManager->getString('system_currency');
    $columnValues[] = $beitrag['BEITRAG_rech_anzahl'];
    $columnValues[] = ($beitrag['BEITRAG_kto'] + $beitrag['BEITRAG_rech']) . ' ' . $gSettingsManager->getString('system_currency');
    $columnValues[] = ($beitrag['BEITRAG_kto_anzahl'] + $beitrag['BEITRAG_rech_anzahl']);

    $data['rows'][] = array(
        'id' => 'row-2',
        'data' => $columnValues
    );

    $columnValues = array();
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_ALREADY_PAID');
    $columnValues[] = $beitrag['BEZAHLT_kto'] . ' ' . $gSettingsManager->getString('system_currency');
    $columnValues[] = $beitrag['BEZAHLT_kto_anzahl'];
    $columnValues[] = $beitrag['BEZAHLT_rech'] . ' ' . $gSettingsManager->getString('system_currency');
    $columnValues[] = $beitrag['BEZAHLT_rech_anzahl'];
    $columnValues[] = ($beitrag['BEZAHLT_kto'] + $beitrag['BEZAHLT_rech']) . ' ' . $gSettingsManager->getString('system_currency');
    $columnValues[] = ($beitrag['BEZAHLT_kto_anzahl'] + $beitrag['BEZAHLT_rech_anzahl']);

    $data['rows'][] = array(
        'id' => 'row-3',
        'data' => $columnValues
    );

    $columnValues = array();
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_PENDING');
    $columnValues[] = ($beitrag['BEITRAG_kto'] - $beitrag['BEZAHLT_kto']) . ' ' . $gSettingsManager->getString('system_currency');
    $columnValues[] = ($beitrag['BEITRAG_kto_anzahl'] - $beitrag['BEZAHLT_kto_anzahl']);
    $columnValues[] = ($beitrag['BEITRAG_rech'] - $beitrag['BEZAHLT_rech']) . ' ' . $gSettingsManager->getString('system_currency');
    $columnValues[] = ($beitrag['BEITRAG_rech_anzahl'] - $beitrag['BEZAHLT_rech_anzahl']);
    $columnValues[] = (($beitrag['BEITRAG_kto'] + $beitrag['BEITRAG_rech']) - ($beitrag['BEZAHLT_kto'] + $beitrag['BEZAHLT_rech'])) . ' ' . $gSettingsManager->getString('system_currency');
    $columnValues[] = (($beitrag['BEITRAG_kto_anzahl'] + $beitrag['BEITRAG_rech_anzahl']) - ($beitrag['BEZAHLT_kto_anzahl'] + $beitrag['BEZAHLT_rech_anzahl']));

    $data['rows'][] = array(
        'id' => 'row-4',
        'data' => $columnValues
    );

    $table->createJavascript(count($data['rows']), 7);
    $table->setColumnAlignByArray($data['column_align']);

    $smarty = $page->createSmartyObject();
    $smarty->assign('l10n', $gL10n);
    $smarty->assign('classTable', 'table table-condensed table-hover');
    $smarty->assign('columnAlign', $data['column_align']);
    $smarty->assign('columnWidth', $data['column_width']);
    $smarty->assign('rows', $data['rows']);

    $htmlTable = $smarty->fetch('../templates/analysis.members.plugin.membershipfee.tpl');
    // add table list to the page
    $page->addHtml($htmlTable);

    $table = new DataTables($page, 'table_roles_contribution');
    $table->setRowsPerPage($gSettingsManager->getInt('groups_roles_members_per_page'));
    $table->setGroupColumn(2);

    // data array
    $data = array(
        'headers' => array(),
        'rows' => array(),
        'column_align' => array(),
        'column_width' => array()
    );

    $data['headers'] = array(
        $gL10n->get('PLG_MEMBERSHIPFEE_ROLE'),
        'dummy',
        $gL10n->get('SYS_CONTRIBUTION'),
        $gL10n->get('PLG_MEMBERSHIPFEE_NUMBER'),
        $gL10n->get('PLG_MEMBERSHIPFEE_SUM')
    );

    $data['column_align'] = array(
        'left',
        'right',
        'right',
        'right',
        'right'
    );

    $data['column_width'] = array(
        '70%',
        '0%',
        '10%',
        '10%',
        '10%'
    );

    $rollen = analyse_rol();

    $listRowNumber = 1;
    foreach ($rollen as $rol => $roldata) {
        $columnValues = array();
        $columnValues[] = $roldata['rolle'];
        $columnValues[] = expand_rollentyp($roldata['rollentyp']);
        $columnValues[] = $roldata['rol_cost'] . ' ' . $gSettingsManager->getString('system_currency');
        $columnValues[] = count($roldata['members']);
        $columnValues[] = ((float) $roldata['rol_cost'] * count($roldata['members'])) . ' ' . $gSettingsManager->getString('system_currency');

        $sum += ((float) $roldata['rol_cost'] * count($roldata['members']));

        $data['rows'][] = array(
            'id' => 'row-' . $listRowNumber,
            'data' => $columnValues
        );

        ++ $listRowNumber;
    }

    $columnValues = array(
        $gL10n->get('PLG_MEMBERSHIPFEE_TOTAL'),
        $gL10n->get('PLG_MEMBERSHIPFEE_SUM'),
        '',
        '',
        $sum . ' ' . $gSettingsManager->getString('system_currency')
    );

    $data['rows'][] = array(
        'id' => 'row-' . $listRowNumber,
        'data' => $columnValues
    );

    $table->createJavascript(count($data['rows']), count($data['headers']));
    $table->setColumnAlignByArray($data['column_align']);

    $smarty->assign('columnAlign', $data['column_align']);
    $smarty->assign('columnWidth', $data['column_width']);
    $smarty->assign('headers', $data['headers']);
    $smarty->assign('rows', $data['rows']);

    $htmlTable = $smarty->fetch('../templates/analysis.roles.plugin.membershipfee.tpl');
    // add table list to the page
    $page->addHtml($htmlTable);

    $page->show();
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
