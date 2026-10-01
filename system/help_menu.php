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

    $pPreferences = new ConfigTable();
    $pPreferences->read();

    $headline = $gL10n->get('PLG_MEMBERSHIPFEE_HELP');
    $gNavigation->addUrl(CURRENT_URL, $headline);

    $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-help-menu');
    $page->setHeadline($headline);

    $form = new FormPresenter('help-menu', '../templates/help.menu.plugin.membershipfee.tpl', '', $page);

    $helpNumber = 0;

    $form->addDescription('help-' . ++ $helpNumber, '<h3>' . $gL10n->get('PLG_MEMBERSHIPFEE_FEES') . '</h3> ');
    $form->addCustomContent('help-' . ++ $helpNumber, $gL10n->get('PLG_MEMBERSHIPFEE_REMAPPING'), $gL10n->get('PLG_MEMBERSHIPFEE_REMAPPING_AGE_STAGGERED_ROLES_DESC'));
    $form->addCustomContent('help-' . ++ $helpNumber, $gL10n->get('PLG_MEMBERSHIPFEE_RECALCULATION'), $gL10n->get('PLG_MEMBERSHIPFEE_RECALCULATION_DESC'));
    $form->addCustomContent('help-' . ++ $helpNumber, $gL10n->get('PLG_MEMBERSHIPFEE_INDIVIDUAL_CONTRIBUTIONS'), $gL10n->get('PLG_MEMBERSHIPFEE_INDIVIDUAL_CONTRIBUTIONS_DESC'));
    $form->addCustomContent('help-' . ++ $helpNumber, $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_PAYMENTS_EDIT'), $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_PAYMENTS_DESC'));
    $form->addCustomContent('help-' . ++ $helpNumber, $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_ANALYSIS'), $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_ANALYSIS_DESC'));

    $form->addDescription('help-' . ++ $helpNumber, '<h3>' . $gL10n->get('PLG_MEMBERSHIPFEE_MANDATE_MANAGEMENT') . '</h3> ');
    $form->addCustomContent('help-' . ++ $helpNumber, $gL10n->get('PLG_MEMBERSHIPFEE_CREATE_MANDATE_ID'), $gL10n->get('PLG_MEMBERSHIPFEE_CREATE_MANDATE_ID_HELP', array(
        'SYS_SETTINGS',
        'PLG_MEMBERSHIPFEE_MANDATE_MANAGEMENT'
    )));
    $form->addCustomContent('help-' . ++ $helpNumber, $gL10n->get('PLG_MEMBERSHIPFEE_MANDATE_EDIT'), $gL10n->get('PLG_MEMBERSHIPFEE_MANDATE_EDIT_DESC'));

    $form->addDescription('help-' . ++ $helpNumber, '<h3>' . $gL10n->get('PLG_MEMBERSHIPFEE_EXPORT') . '</h3> ');
    $form->addCustomContent('help-' . ++ $helpNumber, $gL10n->get('PLG_MEMBERSHIPFEE_SEPA'), $gL10n->get('PLG_MEMBERSHIPFEE_SEPA_DESC'));
    $form->addCustomContent('help-' . ++ $helpNumber, $gL10n->get('PLG_MEMBERSHIPFEE_BILL'), $gL10n->get('PLG_MEMBERSHIPFEE_BILL_EDIT_DESC'));

    $form->addDescription('help-' . ++ $helpNumber, '<h3>' . $gL10n->get('PLG_MEMBERSHIPFEE_EXTRAS') . '</h3> ');
    $form->addCustomContent('help-' . ++ $helpNumber, $gL10n->get('PLG_MEMBERSHIPFEE_PRODUCE_MEMBERNUMBER'), $gL10n->get('PLG_MEMBERSHIPFEE_PRODUCE_MEMBERNUMBER_DESC'));
    $form->addCustomContent('help-' . ++ $helpNumber, $gL10n->get('PLG_MEMBERSHIPFEE_FAMILY_ROLES_UPDATE'), $gL10n->get('PLG_MEMBERSHIPFEE_FAMILY_ROLES_UPDATE_DESC'));
    $form->addCustomContent('help-' . ++ $helpNumber, $gL10n->get('PLG_MEMBERSHIPFEE_COPY'), $gL10n->get('PLG_MEMBERSHIPFEE_COPY_DESC'));
    $form->addCustomContent('help-' . ++ $helpNumber, $gL10n->get('PLG_MEMBERSHIPFEE_TESTS'), $gL10n->get('PLG_MEMBERSHIPFEE_TESTS_DESC'));
    $form->addCustomContent('help-' . ++ $helpNumber, $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_OVERVIEW'), $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_OVERVIEW_DESC'));
    $form->addCustomContent('help-' . ++ $helpNumber, $gL10n->get('SYS_SETTINGS'), $gL10n->get('PLG_MEMBERSHIPFEE_SETUP_DESC'));

    $form->addToHtmlPage();

    $page->show();
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
