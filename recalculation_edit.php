<?php
/**
 ***********************************************************************************************
 * Erzeugt ein Modal-Fenster um neu erzeugte Beiträge und Beitragstexte zu editieren
 *
 * @copyright 2004-2023 The Admidio Team
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Parameters:   
 *
 * user_id    : User_Id des Benutzer, dessen Daten angezeigt werden
 * mode       : edit  - editieren von Text und Beitragstext
 *              savew - speichern von Text und Beitragstext
 *
 *****************************************************************************/

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');

// Initialize and check the parameters
$getUserId               = admFuncVariableIsValid($_GET, 'user_id', 'int');
$getMode                 = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'edit', 'validValues' => array('edit', 'save')));
$postFeeNew              = admFuncVariableIsValid($_POST, 'fee_new', 'string');
$postContributoryTextNew = admFuncVariableIsValid($_POST, 'contributory_text_new', 'string');

if ($getMode === 'save')
{
    $_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['FEE_NEW'] = $postFeeNew;
    $_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['CONTRIBUTORY_TEXT_NEW'] = $postContributoryTextNew;
    $gNavigation->deleteLastUrl();
    admRedirect($gNavigation->getUrl());
    // => EXIT
}

// set headline of the script
$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_RECALCULATION').' - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CORRECTION');
$gNavigation->addUrl(CURRENT_URL, $headline);

// create html page object
$page = new HtmlPage('plg-mitgliedsbeitrag-recalculation-edit', $headline);

header('Content-type: text/html; charset=utf-8');

$form = new HtmlForm('recalculation_edit_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/recalculation_edit.php', array('mode' => 'save', 'user_id' => $getUserId)), $page);

$form->addHtml('
    <div class="modal-header">
        <h3 class="modal-title">'.$headline.'</h3>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
    </div>
    <div class="modal-body">
    <h5>' .$_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['FIRST_NAME']. ' ' .$_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['LAST_NAME']. '</h5>
');
$form->addLine();

if (strlen($_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['FEE'.$gCurrentOrgId]) > 0)
{
    $form->addInput('fee', $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE_PREVIOUS'), $_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['FEE'.$gCurrentOrgId] , array('property' => HtmlForm::FIELD_DISABLED));
}
$form->addInput('fee_new', $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE_NEW'), $_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['FEE_NEW']);

$form->addLine();

if (strlen($_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['CONTRIBUTORY_TEXT'.$gCurrentOrgId]) > 0)
{
    $form->addInput('contributory_text', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT_PREVIOUS'), $_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['CONTRIBUTORY_TEXT'.$gCurrentOrgId] , array('property' => HtmlForm::FIELD_DISABLED));
}
$form->addInput('contributory_text_new', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT_NEW'), $_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['CONTRIBUTORY_TEXT_NEW']);

$form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$form->addHtml('</div>');
echo $form->show();


