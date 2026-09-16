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
use Admidio\UI\Presenter\PagePresenter;
use Plugins\MembershipFee\classes\Config\ConfigTable;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/../../../system/login_valid.php');
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

    $beitrag = analyse_mem();
    $page->addHtml('<table class="table table-condensed">
 
    <tr>
        <td style="text-align: left;">' . $gL10n->get('PLG_MEMBERSHIPFEE_TOTAL') . ':</td>
        <td style="text-align: left;">' . ($beitrag['BEITRAG_kto'] + $beitrag['BEITRAG_rech']) . ' ' . $gSettingsManager->getString('system_currency') . '&#160;&#160;&#160;(#' . ($beitrag['BEITRAG_kto_anzahl'] + $beitrag['BEITRAG_rech_anzahl']) . ')</td>
        
        <td style="text-align: center;">' . $gL10n->get('PLG_MEMBERSHIPFEE_ALREADY_PAID') . ':</td>
        <td style="text-align: center;">' . ($beitrag['BEZAHLT_kto'] + $beitrag['BEZAHLT_rech']) . ' ' . $gSettingsManager->getString('system_currency') . '&#160;&#160;&#160;(#' . ($beitrag['BEZAHLT_kto_anzahl'] + $beitrag['BEZAHLT_rech_anzahl']) . ')</td>
        
        <td style="text-align: right;">' . $gL10n->get('PLG_MEMBERSHIPFEE_PENDING') . ':</td>
        <td style="text-align: right;">' . (($beitrag['BEITRAG_kto'] + $beitrag['BEITRAG_rech']) - ($beitrag['BEZAHLT_kto'] + $beitrag['BEZAHLT_rech'])) . ' ' . $gSettingsManager->getString('system_currency') . '&#160;&#160;&#160;(#' . (($beitrag['BEITRAG_kto_anzahl'] + $beitrag['BEITRAG_rech_anzahl']) - ($beitrag['BEZAHLT_kto_anzahl'] + $beitrag['BEZAHLT_rech_anzahl'])) . ')</td>
    </tr>
    </table>');

    $page->addPageFunctionsMenuItem('menu_fees', $gL10n->get('PLG_MEMBERSHIPFEE_FEES'), '#', 'bi-wallet');
    $page->addPageFunctionsMenuItem('menu_item_remapping', $gL10n->get('PLG_MEMBERSHIPFEE_REMAPPING'), '', 'bi-shuffle', 'menu_fees');
    $page->addPageFunctionsMenuItem('menu_item_recalculation', $gL10n->get('PLG_MEMBERSHIPFEE_RECALCULATION'), SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/recalculation.php'), 'bi-calculator', 'menu_fees');
    $page->addPageFunctionsMenuItem('menu_item_individualcontributions', $gL10n->get('PLG_MEMBERSHIPFEE_INDIVIDUAL_CONTRIBUTIONS'), '', 'bi-house-gear', 'menu_fees');
    $page->addPageFunctionsMenuItem('menu_item_payments', $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_PAYMENTS'), '', 'bi-cash-coin', 'menu_fees');
    $page->addPageFunctionsMenuItem('menu_item_analysis', $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_ANALYSIS'), '', 'bi-bar-chart', 'menu_fees');

    $page->addPageFunctionsMenuItem('menu_mandatemanagement', $gL10n->get('PLG_MEMBERSHIPFEE_MANDATE_MANAGEMENT'), '#', 'bi-basket');
    $page->addPageFunctionsMenuItem('menu_item_createmandateid', $gL10n->get('PLG_MEMBERSHIPFEE_CREATE_MANDATE_ID'), '', 'bi-list-ol', 'menu_mandatemanagement');
    $page->addPageFunctionsMenuItem('menu_item_mandates', $gL10n->get('PLG_MEMBERSHIPFEE_MANDATE_EDIT'), '', 'bi-pen', 'menu_mandatemanagement');

    $page->addPageFunctionsMenuItem('menu_export', $gL10n->get('PLG_MEMBERSHIPFEE_EXPORT'), '#', 'bi-download');
    $page->addPageFunctionsMenuItem('menu_item_sepa', $gL10n->get('PLG_MEMBERSHIPFEE_SEPA'), '', 'bi-file-earmark', 'menu_export');
    $page->addPageFunctionsMenuItem('menu_item_billexport', $gL10n->get('PLG_MEMBERSHIPFEE_BILL'), '', 'bi-file-earmark-spreadsheet', 'menu_export');

    $page->addPageFunctionsMenuItem('menu_extras', $gL10n->get('PLG_MEMBERSHIPFEE_EXTRAS'), '#', 'bi-option');
    $page->addPageFunctionsMenuItem('menu_item_producemembernumber', $gL10n->get('PLG_MEMBERSHIPFEE_PRODUCE_MEMBERNUMBER'), '', 'bi-123', 'menu_extras');
    $page->addPageFunctionsMenuItem('menu_item_familyrolesupdate', $gL10n->get('PLG_MEMBERSHIPFEE_FAMILY_ROLES_UPDATE'), '', 'bi-arrow-repeat', 'menu_extras');
    $page->addPageFunctionsMenuItem('menu_item_copy', $gL10n->get('PLG_MEMBERSHIPFEE_COPY'), '', 'bi-copy', 'menu_extras');
    $page->addPageFunctionsMenuItem('menu_item_tests', $gL10n->get('PLG_MEMBERSHIPFEE_TESTS'), '', 'bi-heart-pulse', 'menu_extras');
    $page->addPageFunctionsMenuItem('menu_item_roleoverview', $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_OVERVIEW'), '', 'bi-list-columns-reverse', 'menu_extras');
    if (isUserAuthorizedForPreferences()) {
        $page->addPageFunctionsMenuItem('menu_item_preferences', $gL10n->get('SYS_SETTINGS'), '', 'bi-gear-fill', 'menu_extras');
    }

    $page->addPageFunctionsMenuItem('menu_help', $gL10n->get('PLG_MEMBERSHIPFEE_HELP'), '#', 'bi-question-circle');
    $page->addPageFunctionsMenuItem('menu_item_menu', $gL10n->get('SYS_MENU'), '', 'bi-menu-button-wide', 'menu_help');
    $page->addPageFunctionsMenuItem('menu_item_documentation', $gL10n->get('PLG_MEMBERSHIPFEE_DOCUMENTATION'), '', 'bi-filetype-doc', 'menu_help');
    $page->addPageFunctionsMenuItem('menu_item_about', $gL10n->get('PLG_MEMBERSHIPFEE_ABOUT') . ' ' . $gL10n->get('PLG_MEMBERSHIPFEE_MEMBERSHIP_FEE'), '', 'bi-info-circle', 'menu_help');

    $page->show();
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
