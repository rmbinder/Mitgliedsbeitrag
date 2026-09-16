<?php
/**
 ***********************************************************************************************
 * Erzeugt ein Modal-Fenster um neu erzeugte Beiträge und Beitragstexte zu editieren
 *
 * @copyright The Admidio Team
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/**
 * ****************************************************************************
 * Parameters:
 *
 * user_id : User_Id des Benutzer, dessen Daten angezeigt werden
 * mode : edit - editieren von Text und Beitragstext
 * savew - speichern von Text und Beitragstext
 *
 * ***************************************************************************
 */
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;

try {

    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');

    // Initialize and check the parameters
    $getUserId = admFuncVariableIsValid($_GET, 'user_id', 'int');
    $getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array(
        'defaultValue' => 'edit',
        'validValues' => array(
            'edit',
            'save'
        )
    ));
    $postFeeNew = admFuncVariableIsValid($_POST, 'fee_new', 'string');
    $postContributoryTextNew = admFuncVariableIsValid($_POST, 'contributory_text_new', 'string');

    if ($getMode === 'save') {
        $_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['FEE_NEW'] = $postFeeNew;
        $_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['CONTRIBUTORY_TEXT_NEW'] = $postContributoryTextNew;
        $gNavigation->deleteLastUrl();
        admRedirect($gNavigation->getUrl());
        // => EXIT
    }

    // set headline of the script
    $headline = $gL10n->get('PLG_MEMBERSHIPFEE_RECALCULATION') . ' - ' . $gL10n->get('PLG_MEMBERSHIPFEE_CORRECTION');
    $gNavigation->addUrl(CURRENT_URL, $headline);

    $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-recalculation-edit');
    $page->setInlineMode();

    $form = new FormPresenter('recalculation_edit_form', '../templates/recalculation.edit.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/recalculation_edit.php', array(
        'mode' => 'save',
        'user_id' => $getUserId
    )), $page);

    $form->addInput('fee', $gL10n->get('PLG_MEMBERSHIPFEE_FEE_PREVIOUS'), $_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['FEE' . $gCurrentOrgId], array(
        'property' => FormPresenter::FIELD_DISABLED
    ));

    $form->addInput('fee_new', $gL10n->get('PLG_MEMBERSHIPFEE_FEE_NEW'), $_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['FEE_NEW']);

    $form->addInput('contributory_text', $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTORY_TEXT_PREVIOUS'), $_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['CONTRIBUTORY_TEXT' . $gCurrentOrgId], array(
        'property' => FormPresenter::FIELD_DISABLED
    ));

    $form->addInput('contributory_text_new', $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTORY_TEXT_NEW'), $_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['CONTRIBUTORY_TEXT_NEW']);

    $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array(
        'icon' => 'bi-check-lg',
        'class' => ' offset-sm-3'
    ));

    $smarty = $page->createSmartyObject();
    $smarty->assign('headline', $headline);
    $smarty->assign('username', '<h5>' . $_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['FIRST_NAME'] . ' ' . $_SESSION['pMembershipFee']['recalculation_user'][$getUserId]['LAST_NAME'] . '</h5>');

    $form->addToSmarty($smarty);

    $page->addHtml($smarty->fetch('../templates/recalculation.edit.plugin.membershipfee.tpl'));

    $page->show();
} catch (Throwable $e) {
    handleException($e);
}
