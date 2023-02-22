<?php
/**
 ***********************************************************************************************
 * Routine um eine Mandatsaenderung (Zahlungspflichtiger) zu bearbeiten
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:   mandate_change.php ist eine modifizierte members_assignment.php
 *
 * Parameters:
 *
 * mode             : html   - Standardmodus zum Anzeigen einer html-Liste
 *                    assign - Schreiben der Aenderungen in die Datenbank
 * usr_uuid         : Uuid des Benutzers, fuer den die Mandatsaenderungen durchgefuehrt werden
 * iban             : die neue IBAN des Zahlungspflichtigen
 * origiban         : die urspruengliche IBAN des Zahlungspflichtigen
 * mandateid        : die neue Mandatsreferenz des Zahlungspflichtigen
 * origmandateid    : die urspruengliche Mandatsreferenz des Zahlungspflichtigen
 * bankchanged      : die Bankverbindung wurde geaendert
 * bank             : die neue Bank des Zahlungspflichtigen
 * bic              : der neue BIC des Zahlungspflichtigen
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

// only authorized user are allowed to start this module
if (!isUserAuthorized($_SESSION['pMembershipFee']['script_name']))
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

if(isset($_GET['mode']) && $_GET['mode'] == 'assign')
{
    // ajax mode then only show text if error occurs
    $gMessage->showTextOnly(true);
}

// Initialize and check the parameters
$getMode            = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'html', 'validValues' => array('html', 'assign')));
$getUserUuid        = admFuncVariableIsValid($_GET, 'user_uuid', 'string');
$getIBAN            = admFuncVariableIsValid($_GET, 'iban', 'string');
$getOrigIBAN        = admFuncVariableIsValid($_GET, 'origiban', 'string');
$getMandateID       = admFuncVariableIsValid($_GET, 'mandateid', 'string');
$getOrigMandateID   = admFuncVariableIsValid($_GET, 'origmandateid', 'string');
$getBankChanged     = admFuncVariableIsValid($_GET, 'bankchanged', 'string');
$getBank            = admFuncVariableIsValid($_GET, 'bank', 'string', array('defaultValue' => ''));
$getBIC             = admFuncVariableIsValid($_GET, 'bic', 'string', array('defaultValue' => ''));

$user = new User($gDb, $gProfileFields);
$user->readDataByUuid($getUserUuid);

if($getMode == 'assign')
{
    $ret_txt = 'error_nothing_changed';
    $iban_change = 'false';
    $bank_change = 'false';
    $mandateid_change = 'false';

    $gMessage->showTextOnly(true);

    // wurde die Bank geaendert?
    if ($getBankChanged == 'false')             //nein, dieselbe Bank
    {
        //hat eine Aenderung der IBAN stattgefunden?
        if ($getIBAN != $user->getValue('IBAN'))
        {
            //ja, dann muss origIBAN befuellt sein
            if (strlen($getOrigIBAN) !== 0)
            {
                $iban_change = 'true';
                $ret_txt = 'success';
            }
            else
            {
                $ret_txt = 'error_origiban_missing';
            }
        }
    }
    else               //die Bank wurde geaendert
    {
        //bei einer Aenderung der Bank muss es eine andere IBAN geben
        if ($getIBAN != $user->getValue('IBAN'))
        {
            $bank_change = 'true';
            $ret_txt = 'success';
        }
        else
        {
            $ret_txt = 'error_bank_changed';
        }
    }

    // wurde die Mandatsreferenz geaendert?
    if($getMandateID != $user->getValue('MANDATEID'.$gCurrentOrgId))
    {
        //bei einer Aenderung muss origMandateID befuellt sein
        if (strlen($getOrigMandateID) !== 0)
        {
            $mandateid_change = 'true';
            $ret_txt = 'success';
        }
        else
        {
            $ret_txt = 'error_origmandateid_missing';
        }
    }

    if($ret_txt == 'success')
    {
        if($iban_change == 'true')
        {
            $user->setValue('IBAN', $getIBAN);
            $user->setValue('ORIG_IBAN', $getOrigIBAN);
        }
        if($bank_change == 'true')
        {
            $user->setValue('IBAN', $getIBAN);
            $user->setValue('BIC', $getBIC);
            $user->setValue('BANK', $getBank);
            $user->setValue('SEQUENCETYPE'.$gCurrentOrgId, '');
            $user->setValue('ORIG_DEBTOR_AGENT', 'SMNDA');

            // wenn die Bank gewechselt wurde, braucht die neue Bank die urspruengliche IBAN nicht zu kennen
            $user->setValue('ORIG_IBAN', '');
        }
        if($mandateid_change == 'true')
        {
            $user->setValue('MANDATEID'.$gCurrentOrgId, $getMandateID);
            $user->setValue('ORIG_MANDATEID'.$gCurrentOrgId, $getOrigMandateID);
        }
        $user->save();
    }
    echo $ret_txt;
}
else
{
    $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE').' ('. $user->getValue('LAST_NAME').' '.$user->getValue('FIRST_NAME').')';

    //$gNavigation->addUrl(SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mandates.php'));
    $gNavigation->addUrl(CURRENT_URL, $headline);
    
    $page = new HtmlPage('plg-mitgliedsbeitrag-mandate-change', $headline);
  
    $page->addJavascript('
        function ibanschieben(){
          var iban = $("input[type=text]#iban").val();
          var origiban = $("input[type=text]#origiban").val(iban);
          $("input[type=text]#iban").val("");
       };
       function mandatschieben(){
          var mandateid = $("input[type=text]#mandateid").val();
          var origmandateid = $("input[type=text]#origmandateid").val(mandateid);
          $("input[type=text]#mandateid").val("");
       };
    ');            // !!!: ohne true

    $page->addJavascript('

        // checkbox "Kontoverbindung bei anderer Bank" wurde gewaehlt
        $("input[type=checkbox].bank_changed_checkbox").click(function(){
            var bankchanged = $("input[type=checkbox]#bankchanged").prop("checked");
            if(bankchanged) {
                $("input[type=text]#bic").val("");
                $("input[type=text]#bic").prop("disabled", false);
                $("input[type=text]#bank").val("");
                $("input[type=text]#bank").prop("disabled", false);
                $("input[type=text]#origiban").val("");
                $("input[type=text]#origdebtoragent").val("SMNDA");
            }
            else {
                window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mandate_change.php', array('user_uuid' => $getUserUuid)).'");
            }
        }); 
   
        $(".form-mandate_change").submit(function(event) {
            var id = $(this).attr("id");
            var iban = $("input[type=text]#iban").val(); 
            var origiban = $("input[type=text]#origiban").val();
            var mandateid = $("input[type=text]#mandateid").val(); 
            var origmandateid = $("input[type=text]#origmandateid").val();
            var bank = $("input[type=text]#bank").val();
            var bic = $("input[type=text]#bic").val();
            var bankchanged = $("input[type=checkbox]#bankchanged").prop("checked");
        
            var action ="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mandate_change.php', array('user_uuid' => $getUserUuid, 'mode' => 'assign')) .'&iban=" + iban + "&origiban=" + origiban + "&mandateid=" + mandateid + "&origmandateid=" + origmandateid + "&bank=" + bank + "&bic=" + bic + "&bankchanged=" + bankchanged;
 
            var formAlert = $("#" + id + " .form-alert");
            formAlert.hide();

            // disable default form submit
            event.preventDefault();

            $.post({
                url: action,
                data:    $(this).serialize(),
                success: function(data) {
                    if (data === "success") {
                        $("#"+id+" .form-alert").attr("class", "alert alert-success form-alert");
                        formAlert.html("<i class=\"fas fa-check\"></i><strong>'.$gL10n->get('SYS_SAVE_DATA').'</strong>");
                        formAlert.fadeIn("slow");
                        formAlert.animate({opacity: 1.0}, 2500);
                        formAlert.fadeOut("slow");
                    }
                    else if(data === "error_nothing_changed") {
                        formAlert.attr("class", "alert alert-danger form-alert");
                        formAlert.html("<i class=\"fas fa-exclamation-circle\"></i><strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ERROR_NOTHING_CHANGED').'</strong>");
                        formAlert.fadeIn("slow");
                        formAlert.animate({opacity: 1.0}, 5000);
                        formAlert.fadeOut("slow");
                    }
                    else if(data === "error_origmandateid_missing") {
                        formAlert.attr("class", "alert alert-danger form-alert");
                        formAlert.html("<i class=\"fas fa-exclamation-circle\"></i><strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ERROR_ORIGMANDATEID_MISSING').'</strong>");
                        formAlert.fadeIn("slow");
                        formAlert.animate({opacity: 1.0}, 5000);
                        formAlert.fadeOut("slow");
                    }
                    else if(data === "error_origiban_missing") {
                        formAlert.attr("class", "alert alert-danger form-alert");
                        formAlert.html("<i class=\"fas fa-exclamation-circle\"></i><strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ERROR_ORIGIBAN_MISSING').'</strong>");
                        formAlert.fadeIn("slow");
                        formAlert.animate({opacity: 1.0}, 5000);
                        formAlert.fadeOut("slow");
                    }
                    else if(data === "error_bank_changed") {
                        formAlert.attr("class", "alert alert-danger form-alert");
                        formAlert.html("<i class=\"fas fa-exclamation-circle\"></i><strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ERROR_BANK_CHANGED').'</strong>");
                        formAlert.fadeIn("slow");
                        formAlert.animate({opacity: 1.0}, 5000);
                        formAlert.fadeOut("slow");
                    }
                    else {
                        formAlert.attr("class", "alert alert-danger form-alert");
                        formAlert.fadeIn();
                        formAlert.html("<i class=\"fas fa-exclamation-circle\"></i>"+data);
                    }
                }
            });
        });

    ', true);

    $form = new HtmlForm('mandate_change_form', null, $page, array('class' => 'form-mandate_change'));
    $form->addInput('mandateid', $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEID'), $user->getValue('MANDATEID'.$gCurrentOrgId), array('property' => HtmlForm::FIELD_REQUIRED));
    $html = '<a class="iconLink" id="mandatschieben" href="javascript:mandatschieben()">
            <i class="fas fa-arrow-down" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MOVE_MANDATEID').'"></i> </a>';
    $form->addCustomContent('', $html);
    $form->addInput('origmandateid', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_MANDATEID'), $user->getValue('ORIG_MANDATEID'.$gCurrentOrgId), array('property' => HtmlForm::FIELD_DISABLED));
    $form->addInput('iban', $gL10n->get('PLG_MITGLIEDSBEITRAG_IBAN'), $user->getValue('IBAN'), array('property' => HtmlForm::FIELD_REQUIRED));
    $html = '<a class="iconLink" id="ibanschieben" href="javascript:ibanschieben()">
            <i class="fas fa-arrow-down"  title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MOVE_IBAN').'"></i> </a>';
    $form->addCustomContent('', $html);
    $form->addInput('origiban', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_IBAN'), $user->getValue('ORIG_IBAN'), array('property' => HtmlForm::FIELD_DISABLED));
    $form->addCheckbox('bankchanged', $gL10n->get('PLG_MITGLIEDSBEITRAG_BANK_CHANGED'), 0, array('class' => 'bank_changed_checkbox'));
    $form->addInput('bic', $gL10n->get('PLG_MITGLIEDSBEITRAG_BIC'), $user->getValue('BIC'), array('property' => HtmlForm::FIELD_DISABLED));
    $form->addInput('bank', $gL10n->get('PLG_MITGLIEDSBEITRAG_BANK'), $user->getValue('BANK'), array('property' => HtmlForm::FIELD_DISABLED));
    $form->addInput('origdebtoragent', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_DEBTOR_AGENT'), $user->getValue('ORIG_DEBTOR_AGENT'), array('property' => HtmlForm::FIELD_DISABLED));
    $html = '<div class="alert alert-warning alert-small" role="alert"><i class="fas fa-exclamation-triangle"></i>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE_DBTR_INFO').'</div>';
    $form->addCustomContent('', $html);

    $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

    $page->addHtml($form->show(false));

    $page->show();
}
