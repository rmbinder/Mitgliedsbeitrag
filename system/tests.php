<?php
/**
 ***********************************************************************************************
 * Anzeige von Prüfungen fuer das Admidio-Plugin Mitgliedsbeitrag
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

    $headline = $gL10n->get('PLG_MEMBERSHIPFEE_TESTS');
    $gNavigation->addUrl(CURRENT_URL, $headline);

    $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-tests');
    $page->setHeadline($headline);

    // Prüfungen nur anzeigen, wenn mindestens ein Einzeltest aktiviert ist
    if (in_array(1, $pPreferences->config['tests_enable'])) {

        $form = new FormPresenter('tests_form', '../templates/tests.plugin.membershipfee.tpl', '', $page);

        if ($pPreferences->config['tests_enable']['age_staggered_roles']) {
            $form->addDescription('AGE_STAGGERed_roles', '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_AGE_STAGGERED_ROLES_DESC') . '</strong>');
            $form->addDescription('AGE_STAGGERed_roles_result', showTestResultWithScrollbar(check_rols()));
        }

        // Pruefung der Rollenmitgliedschaften in den altersgestaffelten Rollen nur, wenn es mehrere Staffelungen gibt
        if ($pPreferences->config['tests_enable']['role_membership_age_staggered_roles'] && count($pPreferences->config['Altersrollen']['altersrollen_token']) > 1) {
            $form->addDescription('role_membership_AGE_STAGGERed_roles', '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES_DESC') . '</strong>');
            $form->addDescription('role_membership_AGE_STAGGERed_roles_result', showTestResultWithScrollbar(check_rollenmitgliedschaft_altersrolle()));
        }

        if ($pPreferences->config['tests_enable']['role_membership_duty_and_exclusion']) {
            $form->addDescription('role_membership_duty', '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_DUTY_DESC') . '</strong>');
            $form->addDescription('role_membership_duty_result', showTestResultWithScrollbar(check_rollenmitgliedschaft_pflicht()));

            $form->addDescription('role_membership_exclusion', '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_EXCLUSION_DESC') . '</strong>');
            $form->addDescription('role_membership_exclusion_result', showTestResultWithScrollbar(check_rollenmitgliedschaft_ausschluss()));
        }

        if ($pPreferences->config['tests_enable']['family_roles']) {
            $form->addDescription('family_roles', '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_FAMILY_ROLES_ROLE_TEST_DESC') . '</strong>');
            $form->addDescription('family_roles_result', showTestResultWithScrollbar(check_family_roles()));
        }

        if ($pPreferences->config['tests_enable']['account_details']) {
            $form->addDescription('account_details', '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_ACCOUNT_DATA_TEST_DESC') . '</strong>');
            $form->addDescription('account_details_result', showTestResultWithScrollbar(check_account_details()));
        }

        if ($pPreferences->config['tests_enable']['mandate_management']) {
            $form->addDescription('mandate_management', '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MANDATE_MANAGEMENT_DESC2') . '</strong>');
            $form->addDescription('mandate_management_result', showTestResultWithScrollbar(check_mandate_management()));
        }

        if ($pPreferences->config['tests_enable']['iban_check']) {
            $form->addDescription('iban_check', '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_IBANCHECK_DESC') . '</strong>');
            $form->addDescription('iban_check_result', showTestResultWithScrollbar(check_iban()));
        }

        if ($pPreferences->config['tests_enable']['bic_check']) {
            $form->addDescription('bic_check_result', showTestResultWithScrollbar(check_bic()));
        }

        $form->addToHtmlPage();
    }

    $page->show();
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
