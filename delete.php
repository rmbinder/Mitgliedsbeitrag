<?php
/**
 ***********************************************************************************************
 * Deleteroutine fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:  none
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

// only authorized user are allowed to start this module
if (!isUserAuthorizedForPreferences())
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$duedatecount = 0;
$paidcount = 0;

//alle Mitglieder einlesen
$members = list_members(array('DUEDATE'.$gCurrentOrgId, 'SEQUENCETYPE'.$gCurrentOrgId, 'CONTRIBUTORY_TEXT'.$gCurrentOrgId, 'PAID'.$gCurrentOrgId, 'FEE'.$gCurrentOrgId, 'MANDATEID'.$gCurrentOrgId, 'MANDATEDATE'.$gCurrentOrgId, 'IBAN', 'BIC'), 0);

//jetzt wird gezaehlt
foreach ($members as $member => $memberdata)
{
    if (!empty($memberdata['DUEDATE'.$gCurrentOrgId]))
    {
    	$duedatecount++;
    }
    if (!empty($memberdata['PAID'.$gCurrentOrgId]))
    {
        $paidcount++;
    }
}
unset($members);

$beitrag = analyse_mem();

$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE');

$gNavigation->addUrl(CURRENT_URL, $headline);

$page = new HtmlPage('plg-mitgliedsbeitrag-delete', $headline);

$page->addJavascript('
    $(".form-preferences").submit(function(event) {
        var id = $(this).attr("id");
        var action = $(this).attr("action");
        var formAlert = $("#" + id + " .form-alert");
        formAlert.hide();

        // disable default form submit
        event.preventDefault();

        $.post({
            url: action,
            data: $(this).serialize(),
            success: function(data) {
                if(data == "delete") {
                    var data = "success";
                    var replace = true;
                }

                if(data == "success") {
                    formAlert.attr("class", "alert alert-success form-alert");
                    formAlert.html("<i class=\"fas fa-check\"></i><strong>'.$gL10n->get('SYS_SAVE_DATA').'</strong>");
                    formAlert.fadeIn("slow");
                    formAlert.animate({opacity: 1.0}, 2500);
                    formAlert.fadeOut("slow");
                }
                else {
                    formAlert.attr("class", "alert alert-danger form-alert");
                    formAlert.fadeIn();
                    formAlert.html("<i class=\"fas fa-exclamation-circle\"></i>" + data);
                }
                if(replace == true) {
                   window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/delete.php').'");
                }
            }
        });
    });', 
    true
);  

$form = new HtmlForm('delete_all_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/delete_function.php', array('form' => 'delete')), $page, array('class' => 'form-preferences'));
$form->addInput('delete_all', $gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE_ALL'), ($beitrag['BEITRAG_kto_anzahl']+$beitrag['BEITRAG_rech_anzahl']), array('property' => HtmlForm::FIELD_READONLY, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_DELETE_ALL_DESC'));                             //HtmlForm::FIELD_DISABLED
$form->addSubmitButton('btn_delete_all', $gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE'), array('icon' => 'fa-trash-alt',  'class' => 'offset-sm-3'));
$page->addHtml($form->show(false));

$form = new HtmlForm('with_paid_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/delete_function.php', array('form' => 'delete')), $page, array('class' => 'form-preferences'));
$form->addLine();
$form->addInput('with_paid', $gL10n->get('PLG_MITGLIEDSBEITRAG_WITH_PAID'), ($beitrag['BEZAHLT_kto_anzahl']+$beitrag['BEZAHLT_rech_anzahl']), array('property' => HtmlForm::FIELD_READONLY, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_WITH_PAID_DESC'));                             //HtmlForm::FIELD_DISABLED
$form->addSubmitButton('btn_with_paid', $gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE'), array('icon' => 'fa-trash-alt',  'class' => 'offset-sm-3'));
$page->addHtml($form->show(false));

$form = new HtmlForm('without_paid_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/delete_function.php', array('form' => 'delete')), $page, array('class' => 'form-preferences'));
$form->addLine();
$form->addInput('without_paid', $gL10n->get('PLG_MITGLIEDSBEITRAG_WITHOUT_PAID'), (($beitrag['BEITRAG_kto_anzahl']+$beitrag['BEITRAG_rech_anzahl'])-($beitrag['BEZAHLT_kto_anzahl']+$beitrag['BEZAHLT_rech_anzahl'])), array('property' => HtmlForm::FIELD_READONLY, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_WITHOUT_PAID_DESC'));                             //HtmlForm::FIELD_DISABLED
$form->addSubmitButton('btn_without_paid', $gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE'), array('icon' => 'fa-trash-alt',  'class' => 'offset-sm-3'));
$page->addHtml($form->show(false));

$form = new HtmlForm('paid_only_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/delete_function.php', array('form' => 'delete')), $page, array('class' => 'form-preferences'));
$form->addLine();
$form->addInput('paid_only', $gL10n->get('PLG_MITGLIEDSBEITRAG_PAID_ONLY'), $paidcount, array('property' => HtmlForm::FIELD_READONLY, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_PAID_ONLY_DESC'));                             //HtmlForm::FIELD_DISABLED
$form->addSubmitButton('btn_paid_only', $gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE'), array('icon' => 'fa-trash-alt',  'class' => 'offset-sm-3'));
$page->addHtml($form->show(false));

$form = new HtmlForm('duedate_only_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/delete_function.php', array('form' => 'delete')), $page, array('class' => 'form-preferences'));
$form->addLine();
$form->addInput('duedate_only', $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE_ONLY'), $duedatecount, array('property' => HtmlForm::FIELD_READONLY, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_DUEDATE_ONLY_DESC'));
$form->addSubmitButton('btn_duedate_only', $gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE'), array('icon' => 'fa-trash-alt',  'class' => 'offset-sm-3'));

$page->addHtml($form->show(false));
$page->show();
