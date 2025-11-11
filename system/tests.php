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
use Plugins\MembershipFee\classes\Config\ConfigTable;

require_once(__DIR__ . '/../../../system/common.php');
require_once(__DIR__ . '/common_function.php');

// only authorized user are allowed to start this module
if (!isUserAuthorized())
{
    throw new Exception('SYS_NO_RIGHTS');   
}

$pPreferences = new ConfigTable();
$pPreferences->read();

$headline = $gL10n->get('PLG_MEMBERSHIPFEE_TESTS');

$page = new HtmlPage('plg-mitgliedsbeitrag-tests', $headline);
  
$gNavigation->addUrl(CURRENT_URL, $headline);

//Prüfungen nur anzeigen, wenn mindestens ein Einzeltest aktiviert ist
if (in_array(1, $pPreferences->config['tests_enable']))
{
    $form = new HtmlForm('tests_form', '', $page);
    
    if ($pPreferences->config['tests_enable']['age_staggered_roles'])
    {
        $form->openGroupBox('AGE_STAGGERed_roles', $gL10n->get('PLG_MEMBERSHIPFEE_AGE_STAGGERED_ROLES'));
        $form->addDescription('<strong>'.$gL10n->get('PLG_MEMBERSHIPFEE_AGE_STAGGERED_ROLES_DESC').'</strong>');
        $form->addDescription(showTestResultWithScrollbar(check_rols()));
        $form->closeGroupBox();
    }
    
    // Pruefung der Rollenmitgliedschaften in den altersgestaffelten Rollen nur, wenn es mehrere Staffelungen gibt
    if ($pPreferences->config['tests_enable']['role_membership_age_staggered_roles'] && count($pPreferences->config['Altersrollen']['altersrollen_token']) > 1)
    {
        $form->openGroupBox('role_membership_AGE_STAGGERed_roles', $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES'));
        $form->addDescription('<strong>'.$gL10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES_DESC').'</strong>');
        $form->addDescription(showTestResultWithScrollbar(check_rollenmitgliedschaft_altersrolle()));
        $form->closeGroupBox();
    }
    
    if ($pPreferences->config['tests_enable']['role_membership_duty_and_exclusion'])
    {
        $form->openGroupBox('role_membership_duty', $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_DUTY'));
        $form->addDescription('<strong>'.$gL10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_DUTY_DESC').'</strong>');
        $form->addDescription(showTestResultWithScrollbar(check_rollenmitgliedschaft_pflicht()));
        $form->closeGroupBox();
        
        $form->openGroupBox('role_membership_exclusion', $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_EXCLUSION'));
        $form->addDescription('<strong>'.$gL10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_EXCLUSION_DESC').'</strong>');
        $form->addDescription(showTestResultWithScrollbar(check_rollenmitgliedschaft_ausschluss()));
        $form->closeGroupBox();
    }
    
    if ($pPreferences->config['tests_enable']['family_roles'])
    {
        $form->openGroupBox('family_roles', $gL10n->get('PLG_MEMBERSHIPFEE_FAMILY_ROLES'));
        $form->addDescription('<strong>'.$gL10n->get('PLG_MEMBERSHIPFEE_FAMILY_ROLES_ROLE_TEST_DESC').'</strong>');
        $form->addDescription(showTestResultWithScrollbar(check_family_roles()));
        $form->closeGroupBox();
    }
    
    if ($pPreferences->config['tests_enable']['account_details'])
    {
        $form->openGroupBox('account_details', $gL10n->get('PLG_MEMBERSHIPFEE_ACCOUNT_DATA'));
        $form->addDescription('<strong>'.$gL10n->get('PLG_MEMBERSHIPFEE_ACCOUNT_DATA_TEST_DESC').'</strong>');
        $form->addDescription(showTestResultWithScrollbar(check_account_details()));
        $form->closeGroupBox();
    }
    
    if ($pPreferences->config['tests_enable']['mandate_management'])
    {
        $form->openGroupBox('mandate_management', $gL10n->get('PLG_MEMBERSHIPFEE_MANDATE_MANAGEMENT'));
        $form->addDescription('<strong>'.$gL10n->get('PLG_MEMBERSHIPFEE_MANDATE_MANAGEMENT_DESC2').'</strong>');
        $form->addDescription(showTestResultWithScrollbar(check_mandate_management()));
        $form->closeGroupBox();
    }
    
    if ($pPreferences->config['tests_enable']['iban_check'])
    {
        $form->openGroupBox('iban_check', $gL10n->get('PLG_MEMBERSHIPFEE_IBANCHECK'));
        $form->addDescription('<strong>'.$gL10n->get('PLG_MEMBERSHIPFEE_IBANCHECK_DESC').'</strong>');
        $form->addDescription(showTestResultWithScrollbar(check_iban()));
        $form->closeGroupBox();
    }
    
    if ($pPreferences->config['tests_enable']['bic_check'])
    {
        $form->openGroupBox('bic_check', $gL10n->get('PLG_MEMBERSHIPFEE_BICCHECK'));
        $form->addDescription('<strong>'.$gL10n->get('PLG_MEMBERSHIPFEE_BICCHECK_DESC').'</strong>');
        $form->addDescription(showTestResultWithScrollbar(check_bic()));
        $form->closeGroupBox();
    }
    
    $page->addHtml($form->show(false));
}

$page->show();
