<?php
/**
 ***********************************************************************************************
 * Creates the main view for the admidio plugin  Mitgliedsbeitrag / Membership fee
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\SecurityUtils;
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

    $headline = $gL10n->get('PLG_MEMBERSHIPFEE_MEMBERSHIP_FEE');

    $gNavigation->addStartUrl(CURRENT_URL, $headline, 'bi-person-fill-add');

    $page = PagePresenter::withHtmlIDAndHeadline('plg-membership-fee-main');
    $page->setHeadline($headline);

    $page->addPageFunctionsMenuItem('menu_fees', $gL10n->get('PLG_MEMBERSHIPFEE_FEES'), '#', 'bi-wallet');
    $page->addPageFunctionsMenuItem('menu_item_remapping', $gL10n->get('PLG_MEMBERSHIPFEE_REMAPPING'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/remapping.php'), 'bi-shuffle', 'menu_fees');
    $page->addPageFunctionsMenuItem('menu_item_recalculation', $gL10n->get('PLG_MEMBERSHIPFEE_RECALCULATION'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/recalculation.php'), 'bi-calculator', 'menu_fees');
    if ($pPreferences->config['individual_contributions']['access_to_module']) {
        $page->addPageFunctionsMenuItem('menu_item_individualcontributions', $gL10n->get('PLG_MEMBERSHIPFEE_INDIVIDUAL_CONTRIBUTIONS'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/individualcontributions.php'), 'bi-house-gear', 'menu_fees');
    }
    $page->addPageFunctionsMenuItem('menu_item_payments', $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_PAYMENTS'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/payments.php'), 'bi-cash-coin', 'menu_fees');
    $page->addPageFunctionsMenuItem('menu_item_analysis', $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_ANALYSIS'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/analysis.php'), 'bi-bar-chart', 'menu_fees');

    $page->addPageFunctionsMenuItem('menu_sepa', $gL10n->get('PLG_MEMBERSHIPFEE_SEPA'), '#', 'bi-cash-stack');
    $page->addPageFunctionsMenuItem('menu_item_duedates', $gL10n->get('PLG_MEMBERSHIPFEE_DUEDATE'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/duedates.php'), 'bi-calendar-check', 'menu_sepa');
    $page->addPageFunctionsMenuItem('menu_item_sepaexport', $gL10n->get('PLG_MEMBERSHIPFEE_EXPORT'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/sepa.php'), 'bi-download', 'menu_sepa');
    $page->addPageFunctionsMenuItem('menu_item_prenotification', $gL10n->get('PLG_MEMBERSHIPFEE_PRE_NOTIFICATION'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/pre_notification.php'), 'bi-send', 'menu_sepa');

    $page->addPageFunctionsMenuItem('menu_bill', $gL10n->get('PLG_MEMBERSHIPFEE_BILL'), '#', 'bi-cash-stack');
    $page->addPageFunctionsMenuItem('menu_item_billexport', $gL10n->get('PLG_MEMBERSHIPFEE_BILL_EDIT'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/bill.php'), 'bi-file-earmark-spreadsheet', 'menu_bill');

    $page->addPageFunctionsMenuItem('menu_extras', $gL10n->get('PLG_MEMBERSHIPFEE_EXTRAS'), '#', 'bi-option');
    $page->addPageFunctionsMenuItem('menu_item_producemembernumber', $gL10n->get('PLG_MEMBERSHIPFEE_PRODUCE_MEMBERNUMBER'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/membernumber.php'), 'bi-123', 'menu_extras');
    $page->addPageFunctionsMenuItem('menu_item_createmandateid', $gL10n->get('PLG_MEMBERSHIPFEE_CREATE_MANDATE_ID'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/create_mandate_id.php'), 'bi-list-ol', 'menu_extras');
    $page->addPageFunctionsMenuItem('menu_item_mandates', $gL10n->get('PLG_MEMBERSHIPFEE_MANDATE_EDIT'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/mandates.php'), 'bi-pen', 'menu_extras');
    $page->addPageFunctionsMenuItem('menu_item_familyrolesupdate', $gL10n->get('PLG_MEMBERSHIPFEE_FAMILY_ROLES_UPDATE'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/familyroles_update.php'), 'bi-arrow-repeat', 'menu_extras');
    $page->addPageFunctionsMenuItem('menu_item_copy', $gL10n->get('PLG_MEMBERSHIPFEE_COPY'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/copy.php'), 'bi-copy', 'menu_extras');
    $page->addPageFunctionsMenuItem('menu_item_tests', $gL10n->get('PLG_MEMBERSHIPFEE_TESTS'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/tests.php'), 'bi-heart-pulse', 'menu_extras');
    $page->addPageFunctionsMenuItem('menu_item_roleoverview', $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_OVERVIEW'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/roleoverview.php'), 'bi-list-columns-reverse', 'menu_extras');

    if (isUserAuthorizedForPreferences()) {
        $page->addPageFunctionsMenuItem('menu_item_preferences', $gL10n->get('SYS_SETTINGS'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php'), 'bi-gear-fill', 'menu_extras');
    }

    $page->addPageFunctionsMenuItem('menu_help', $gL10n->get('PLG_MEMBERSHIPFEE_HELP'), '#', 'bi-question-circle');
    $page->addPageFunctionsMenuItem('menu_item_menu', $gL10n->get('SYS_MENU'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/help_menu.php'), 'bi-menu-button-wide', 'menu_help');

    $docfile = 'documentation-en.pdf';
    if ($gSettingsManager->getString('system_language') === 'de' || $gSettingsManager->getString('system_language') === 'de-DE') {
        $docfile = 'documentation-de.pdf';
    }
    $page->addPageFunctionsMenuItem('menu_item_documentation', $gL10n->get('PLG_MEMBERSHIPFEE_DOCUMENTATION'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/docs/' . $docfile), 'bi-filetype-doc', 'menu_help');

    $javascriptCode = '
        $("#menu_item_about").attr("href", "javascript:void(0);");
        $("#menu_item_about").attr("data-href", "' . ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/about_membership_fee.php");
        $("#menu_item_about").attr("class", "icon-link dropdown-item openPopup");
    ';

    $page->addJavascript($javascriptCode, true);
    $page->addPageFunctionsMenuItem('menu_item_about', $gL10n->get('PLG_MEMBERSHIPFEE_ABOUT') . ' ' . $gL10n->get('PLG_MEMBERSHIPFEE_MEMBERSHIP_FEE'), ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/about_membership_fee.php', 'bi-info-circle', 'menu_help');

    $beitrag = analyse_mem();

    $table = new DataTables($page, 'table_membership_fee');

    // data array
    $data = array(

        'rows' => array(),
        'column_align' => array()
    );

    $data['column_align'] = array(

        'center',
        'center',
        'center'
    );

    $listRowNumber = 0;

    $columnValues = array();
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_TOTAL');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_ALREADY_PAID');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_PENDING');
    $data['rows'][] = array(
        'id' => 'row-' . ++ $listRowNumber,
        'data' => $columnValues
    );

    $columnValues = array();
    $columnValues[] = ($beitrag['BEITRAG_kto'] + $beitrag['BEITRAG_rech']) . ' ' . $gSettingsManager->getString('system_currency') . '&nbsp;&nbsp;&nbsp;(#' . ($beitrag['BEITRAG_kto_anzahl'] + $beitrag['BEITRAG_rech_anzahl']) . ')';
    $columnValues[] = ($beitrag['BEZAHLT_kto'] + $beitrag['BEZAHLT_rech']) . ' ' . $gSettingsManager->getString('system_currency') . '&nbsp;&nbsp;&nbsp;(#' . ($beitrag['BEZAHLT_kto_anzahl'] + $beitrag['BEZAHLT_rech_anzahl']) . ')';
    $columnValues[] = (($beitrag['BEITRAG_kto'] + $beitrag['BEITRAG_rech']) - ($beitrag['BEZAHLT_kto'] + $beitrag['BEZAHLT_rech'])) . ' ' . $gSettingsManager->getString('system_currency') . '&nbsp;&nbsp;&nbsp;(#' . (($beitrag['BEITRAG_kto_anzahl'] + $beitrag['BEITRAG_rech_anzahl']) - ($beitrag['BEZAHLT_kto_anzahl'] + $beitrag['BEZAHLT_rech_anzahl'])) . ')';
    $data['rows'][] = array(
        'id' => 'row-' . ++ $listRowNumber,
        'data' => $columnValues
    );

    // Zusätzliche Zeilen anfügen
    // $columnValues = array();
    // $columnValues[] = '&nbsp;';
    // $columnValues[] = '&nbsp;';
    // $columnValues[] = '&nbsp;';
    // $data['rows'][] = array(
    // 'id' => 'row-' . ++ $listRowNumber,
    // 'data' => $columnValues
    // );

    // $columnValues = array();
    // $columnValues[] = 'Hello';
    // $columnValues[] = 'new';
    // $columnValues[] = 'line';
    // $data['rows'][] = array(
    // 'id' => 'row-' . ++ $listRowNumber,
    // 'data' => $columnValues
    // );

    $table->setColumnAlignByArray($data['column_align']);
    $smarty = $page->createSmartyObject();
    $smarty->assign('classTable', 'table table-condensed table-hover');
    $smarty->assign('columnAlign', $data['column_align']);
    $smarty->assign('rows', $data['rows']);

    $htmlTable = $smarty->fetch('../templates/membershipfee.plugin.membershipfee.tpl');
    $page->addHtml($htmlTable);

    $page->show();
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
